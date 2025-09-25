<?php

// For logging turn processing events that need following up or long term record keeping, set e to echo to GM
function GMLog($text,$Bold=0) {
  global $GAME,$GAMEID;
  static $LF;
  if (!isset($LF)) {
     if (!file_exists("Turns/$GAMEID/" . $GAME['Turn'])) mkdir("Turns/" . $GAMEID . "/" . $GAME['Turn'],0777,true);
     $LF = fopen("Turns/$GAMEID/" . $GAME['Turn'] . "/0.txt", "a+");
     fwrite($LF,'</table></table></table></table></table></table></table></table></table>');
  }
  fwrite($LF,"$text\n");
  echo "$text<br>";
}

function Error($text) {
  GMLog("<span class=Err>$text</span>");
}

function SKLog($text,$e=0) {
  global $Sand,$USER;
  $Sand['ActivityLog'] .= date("Y-m-d H:i:s - ") . $USER['Login'] . " - " . $text . "\n";  // Who?
  if ($e) GMLog($text. "<br>\n",1);
}

function FollowUp($Fid,$Msg) {
  global $GAME,$GAMEID;

  $rec = ['GameId'=>$GAMEID, 'Turn'=>$GAME['Turn'], 'FactionId'=>$Fid, 'ActionNeeded'=>$Msg ];
  Gen_Put('FollowUp',$rec);
}

// Log to the turn text, and optionally aa history record of T
function TurnLog($Fid,$text,&$T=0) {
  global $GAME,$GAMEID;
  static $LF = [];
  if ($Fid == 0) {
    echo "<h2 class=Err>Loging a turn action for Faction 0! - Call Richard</h2>\n";
    debug_print_backtrace();
    dotail();
  }
  if (!isset($LF[$Fid])) {
     if (!file_exists("Turns/$GAMEID/" . $GAME['Turn'])) mkdir("Turns/" . $GAMEID . "/" . $GAME['Turn'],0777,true);
     $LF[$Fid] = fopen("Turns/$GAMEID/" . $GAME['Turn'] . "/$Fid.txt", "a+");
  }
  fwrite($LF[$Fid],"$text\n");
  if ($T) {
    $rec = ['ThingId'=>$T['id'],'TurnNum'=>$GAME['Turn'],'Text'=>strip_tags($text)];
    Gen_Put('ThingHistory',$rec);
/*    if (isset($T['History'])) {
      $T['History'] .= "Turn#" . ($GAME['Turn']) . ": " . $text . "\n";
    } else {
      $T['History'] = "Turn#" . ($GAME['Turn']) . ": " . $text . "\n";
    }*/
  }
}

function Report_Others($Who, $Where, $SeenBy, $Message) {
  // Find all others in Where
  // if eyes see report message, then skip rest of faction

//var_dump($Who,$Where,$SeenBy,$Message);exit;
  static $Factions;
  if (!isset($Factions)) $Factions = Get_Factions();

  foreach ($Factions as $F) {
    if ($F['id'] == $Who) continue;
    $Eyes = EyesInSystem($F['id'],$Where);
    if (($Eyes & $SeenBy) == 0) continue;
    if ($Who) {
      TurnLog($F['id'],"$Message by " . $Factions[$Who]['Name']);
    } else {
      TurnLog($F['id'],$Message);
    }
  }
}


function Done_Stage($Name) {
  global $Sand;  // If you need to add something, replace a spare if poss, then nothing breaks
  global $TurnActions;

  $SName = preg_replace('/ /','',$Name);
  for($S =0; $S <64 ; $S++) {
    $act = $TurnActions[$S][2];
    $act = preg_replace('/ /','',$act);
    if ($SName == $act) break;
  }

//echo "Done $S<br>";
  if ($S > 63) {
    GMLog("Stage $SName not found");
  } else {
    $Sand['Progress'] |= 1<<$S;
  }
}

function SetAllLinks($Ref, $Sid, $LinkState) {
  $Lnks = Gen_Get_Cond('Links',"System1Ref='$Ref' OR System2Ref='$Ref'");
  foreach ($Lnks as $L) {
    $L['Status'] = $LinkState;
    Put_Link($L);
  }
}


function AnomalyComplete($Aid,$Fid) {
  static $Systems;
  if (!$Aid) return;
  $Fact = Get_Faction($Fid);

  $A = Get_Anomaly($Aid);
  $FA = Gen_Get_Cond1('FactionAnomaly',"FactionId=$Fid AND AnomalyId=$Aid");
  if (!$FA) return;

  if (($FA['Progress'] >= $A['AnomalyLevel']) && ($FA['State'] < 3)) {
    $FA['State'] = 3;
    Gen_Put('FactionAnomaly',$FA);
    TurnLog($Fid ,'<p>Anomaly study on ' . $A['Name'] .
      " has been completed - See sperate response from the GMs for what you get");
    if (!empty($A['Completion'])) {
      TurnLog($Fid ,"Completion Text: " . ParseText($A['Completion']) );
      $ctxt = '';
    } else {
      $ctxt = "  AND the completion text.";
    }
    GMLog($Fact['Name'] . " has completed anomaly study : <a href=AnomalyEdit.php?id=$Aid>" . $A['Name'] .
      "</a> has been completed - give them the reward.  $ctxt");
    FollowUp($Fid,$Fact['Name'] . " has completed anomaly study : <a href=AnomalyEdit.php?id=$Aid>" . $A['Name'] .
      "</a> has been completed - give them the reward. $ctxt");
    $T['ProjectId'] = 0;

    if ($A['Complete'] == 1) { // ONe Use
      $A['Complete'] = 2;
      Put_Anomaly($A);
    }

    for ($i=1; $i<=4; $i++) {
      if (!empty($A["ChainedOn$i"])) {
        if (empty($Systems)) $Systems = Get_SystemRefs();

        $Xid = $A["ChainedOn$i"];
        $XA = Get_Anomaly($Xid);
        $FS = Get_System($XA['SystemId']);
        if (empty($FS['id'])) continue;

        $FA = Gen_Get_Cond1('FactionAnomaly',"FactionId=$Fid AND AnomalyId=$Xid");
        if (!$FA) {
          $FA = ['FactionId'=>$Fid, 'AnomalyId'=>$Xid, 'State' =>2,  'Notes'=>''];
        } else {
          if ($FA['State'] >=2) continue;
          $FA['State'] = 2;
        }
        Gen_Put('FactionAnomaly',$FA);

        TurnLog($Fid , "Completing " . $A['Name'] . " has opened up another anomaly that could be studied: " . $XA['Name'] .
          " in " . $Systems[$XA['SystemId']] . "\n" .  ParseText($XA['Description']) .
          "\nIt will take " . $XA['AnomalyLevel'] . " scan level actions to complete.\n\n");
        GMLog($Fact['Name'] . " Have been told about anomaly " . $XA['Name']);
      }
    }
  }
}


?>
