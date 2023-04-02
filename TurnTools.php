<?php

// For logging turn processing events that need following up or long term record keeping, set e to echo to GM
function GMLog($text,$Bold=0) {
  global $GAME,$GAMEID;
  static $LF;
  if (!isset($LF)) {
     if (!file_exists("Turns/$GAMEID/" . $GAME['Turn'])) mkdir("Turns/" . $GAMEID . "/" . $GAME['Turn'],0777,true);
     $LF = fopen("Turns/$GAMEID/" . $GAME['Turn'] . "/0.txt", "a+");
  }
  if ($Bold) $text = "<b>" . $text . "</b>";
  fwrite($LF,"$text\n");
  echo "$text<br>";  
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
  if ($T) $T['History'] .= "Turn#" . ($GAME['Turn']) . ": " . $text . "\n";
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
  global $Stages,$Coded;

  $SName = preg_replace('/ /','',$Name);
  for($S =0; $S <64 ; $S++) {
    $act = $Stages[$S];
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

?>
