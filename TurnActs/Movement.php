<?php

function DirectMoves() {
  // EG for sharks
  $Things = Get_Things_Cond(0,'LinkId=-6 AND NewSystemId!=0 ');
  $TTypes = Get_ThingTypes();
  $SysRefs = Get_SystemRefs();
  foreach($Things as $T) {
    $Tid = $T['id'];
    $T['SystemId'] = $T['NewSystemId'];
    $T['WithinSysLoc'] = $T['NewLocation'];
    $T['NewSystemId'] = $T['LinkId'] = 0;
    Put_Thing($T);
    GMLog("Directly moved " . (empty($T['Name'])?"Nameless $Tid": $T['Name']) . " a " . $TTypes[$T['Type']]['Name'] . " to " . $SysRefs[$T['SystemId']]);
  }
  return 1;
}

function Follow() {
  // find everything with a follow order
  // Do follows if possible, if not set again needed
  // If again needed and depth < Limit repeat
  $Factions = Get_Factions();
  $Depth = $Again = 0;
  do {
    $Again = 0;
    $Things = Get_Things_Cond(0," LinkId=" . LINK_FOLLOW);
    foreach ($Things as $T) {
      $Folid = $T['NewSystemId'];
      $Fol = Get_Thing($Folid);
      $Fid = $T['Whose'];
      if ($Fol) {
        $Lid = $Fol['LinkId'];
        if ($Lid >= 0) {
          $L = Get_Link($Lid);
          $EInst = $L['Instability'];
          if ($L['ThisTurnMod']) $EInst = max(1,$EInst+$L['ThisTurnMod']);
          if ($T['Stability'] < $EInst) {
            TurnLog($T['Whose'],  $T['Name'] . " Can not follow " . $Fol['Name'] . " as the link's Instability is too high " .
              ($L['ThisTurnMod']? " it is currently $EInst" : (" it is " . $L['Instability']) ));
            GMLog($Factions[$T['Whose']]['Name'] . " - " . $T['Name'] . " Can not follow " . $Fol['Name'] . " as the link's Instability is too high " .
              ($L['ThisTurnMod']? " it is currently $EInst" : (" it is " . $L['Instability']) ));
            $T['LinkId'] = $T['LinkCost'] = $T['LinkPay'] = 0;
            Put_Thing($T);
            continue;
          }

          $FS = Get_FactionSystemFS($Fid,$T['SystemId']);
          $FLK = Gen_Get_Cond1('FactionLinkKnown',"FactionId=$Fid AND LinkId=$Lid");

          if (($L['Concealment'] == 0) || ($FLK['Used']??0) || ($FS['SpaceScan'] >= $L['Concealment'])) { // Valid
            $T['LinkId'] = $Fol['LinkId'];
            $T['LinkCost'] = $Fol['LinkCost'];
            $T['LinkPay'] = 1;
            $T['NewSystemId'] = $Fol['NewSystemId'];
            Put_Thing($T);
            GMLog($T['Name'] . " ( " . $Factions[$T['Whose']]['Name'] . " ) is following " . $Fol['Name'] . " ( " . $Factions[$Fol['Whose']]['Name'] . " )" );
          } else {
            GMLog($Factions[$Fid]['Name'] . " - " . $T['Name'] . " Can not follow " . $Fol['Name'] .
              " as the link they used is unknown to you ");
            $T['LinkId'] = $T['LinkCost'] = $T['LinkPay'] = 0;
            Put_Thing($T);
            continue;
          }

        } else {
          $Again = 1;
        }
      } else {
        $T['LinkId'] = 0;
      }
    }
  } while ($Again && $Depth++ < 10);

  if ($Again) {
    GMLog("Follows aborted after $Depth");
  }
  GMLog("Follows evaluated.<br>\n");
  return 1;
}

function PayForStargates() {

  //  echo "Called Pay4SGates<p>";
  $LOWho = GameFeature('LinkOwner',0);
  if ($LOWho == 0) return 1; // Not 4 this gaame

  // echo "HERE!";
  $Facts = Get_Factions();

  $TotalPay = 0;
  $Things = Get_Things_Cond(0," LinkId>0 AND LinkPay=1 AND LinkCost>0 ORDER BY Whose");
  // var_dump($Things);
  foreach($Things as $T) {
    $Cost = $T['LinkCost'];
    $Ref = $T['Name'] . " along link " . $T['LinkId'];
    if (Spend_Credit($T['Whose'],$Cost,'Moving ' . $Ref, $LOWho)) {
      TurnLog($T['Whose'],"Transfered " . Credit() . $Cost . " for $Ref to " . $Facts[$LOWho]['Name']);
      $TotalPay += $Cost;
      $T['LinkPay'] = 2;
      GMLog("Payed for $Ref");
    } else {
      TurnLog($T['Whose'],"Failed to transfer " . Credit() . $Cost . " for $Ref to " . $Facts[$LOWho]['Name']);
      GMLog("Failed to pay for $Ref",1);
      $T['LinkPay'] = 0;
    }
    Put_Thing($T);
  }

  if ($TotalPay) {
    Spend_Credit($LOWho,-$TotalPay,'Link Receipts');
    TurnLog($LOWho,"Recieved " . Credit() . $TotalPay  . " for Link use");
    GMLog("Transfered " . Credit() . $TotalPay  . " for Link use");
  }

  GMLog("Finished paying for stargates");
  return 1;
}

function AgentsMoveCheck() {
  ShipMoveCheck(1);
  return 1;
}

function AgentsMovements() {
  ShipMovements(1);
  return 1;
}

function LoadTroops() {
  $Things = Get_Things_Cond(0,"(BuildState=" . BS_SERVICE . " OR BuildState=" . BS_COMPLETE . ") AND (LinkId=-2 OR LinkId=-4)");
  $TTypes = Get_ThingTypes();
  $Facts = Get_Factions();
  $MTs = Get_ModuleTypes();
  $ModNames = NamesList($MTs);
  $NamesMod = array_flip($ModNames);

  foreach($Things as $T) {
    $HostId = $T['NewSystemId'];
    $H = Get_Thing($HostId);
    $NeedCargo = ($TTypes[$T['Type']]['Properties'] & THING_NEEDS_CARGOSPACE);
    if ($H['Whose'] != $T['Whose']) {
      $FF = Get_FactionFactionsCarry($T['Whose']);
      $Carry = (empty($FF[$H['Whose']])? 0 : $FF[$H['Whose']]);
      if (!$NeedCargo) $Carry >>= 4;
      if (($Carry&15) <2) {
        TurnLog($T['Whose'],"You tried to load " . $T['Name'] . " on to " . $H['Name'] . " access was denied by " . $Facts[$H['Whose']]['Name'],$T);
        TurnLog($H['Whose'],  $Facts[$T['Whose']]['Name'] . " tried to load " . $T['Name'] . " on to " . $H['Name'] . " you denied access",$T);
        GMLog($Facts[$T['Whose']]['Name'] . " tried to load " . $T['Name'] . " on to " . $H['Name'] . " access was denied by " . $Facts[$H['Whose']]['Name'],1);
        $T['LinkId'] = 0;
        Put_Thing($T);
        continue;
      }
    }

    if ($NeedCargo) {
      $Mods = Get_Modules($T['id']);
      $OnBoard = Get_Things_Cond(0,"(LinkId=-1 OR LinkId=-3) AND SystemId=" . $H['id']);
      $OnBoard[]= $T;
      $CargoSpace = ($Mods[$NamesMod['Cargo Space']]['Number']??0);
      $CryoSpace = ($Mods[$NamesMod['Cryo Pods']]['Number']??0)*2;

      foreach($OnBoard as $OB) if ($TTypes[$OB['Type']]['Properties'] & THING_NEEDS_CARGOSPACE) {
        $Need = $OB['Level'];
        if (($CryoSpace>0) && ($TTypes[$OB['Type']]['Properties'] & THING_HAS_ARMYMODULES)) {
          $CryoSpace -= $Need;
          if ($CryoSpace >= 0) {
            $Need = 0;
          } else {
            $Need = - $CryoSpace;
          }
        }
        if ($Need && $CargoSpace) {
          $CargoSpace -= $Need;
          if ($CargoSpace >= 0) {
            $Need = 0;
          } else {
            $Need -= $CargoSpace;
          }
        }
        if ($Need) break;
      }
      if ($Need) {
        TurnLog($T['Whose'],"You tried to load " . $T['Name'] . " on to " . $H['Name'] . " there is not enough space",$T);
        if ($H['Whose'] != $T['Whose'])
          TurnLog($H['Whose'],  $Facts[$T['Whose']]['Name'] . " tried to load " . $T['Name'] . " on to " . $H['Name'] . " there is not enough space");
          GMLog($Facts[$T['Whose']]['Name'] . " tried to load " . $T['Name'] . " on to " . $H['Name'] . " there is not enough space",1);
          $T['LinkId'] = 0;
          Put_Thing($T);
          continue;
      }
    }

    TurnLog($T['Whose'],"Loaded " . $T['Name'] . " on to " . $H['Name'], $T);
    if ($H['Whose'] != $T['Whose']) TurnLog($H['Whose'],  $Facts[$T['Whose']]['Name'] . " loaded " . $T['Name'] . " on to " . $H['Name'] );
    GMLog($Facts[$T['Whose']]['Name'] . " loaded " . $T['Name'] . " on to " . $H['Name']);


    $T['SystemId'] = $HostId;
    if ($T['LinkId'] == -2) {
      $T['LinkId'] = -1;
    } else {
      $T['LinkId'] = -3;
    }
    // var_dump($T); echo "<P>";

    Put_Thing($T);
  }


  GMLog("<br>Load Troops	is Complete<p>");
  return 1;
}


function ShipMoveCheck($Mode=0) {  // Show all movements to allow for blocking
  global $GAME,$GAMEID,$LinkStates;
  $LinkLevels = Get_LinkLevels();
  $Things = Get_AllThings();
  $TTypes = Get_ThingTypes();
  $Facts = Get_Factions();
  $LOWho = GameFeature('LinkOwner',0);

  GMLog("<h2>These movements are planned - to stop one, tick the stop box and say why</h2>");
  //  GMLog("<form method=Post action=TurnActions.php?ACTION=Complete>" . fm_hidden('S',($Mode?34:32)));
  GMLog("<form method=Post action=TurnActions.php?ACTION=DoStage2>" . fm_hidden('Stage',($Mode?'Agents Move Check':'Ship Move Check')));


  $UsedLinks = [];
  if ($Mode) {
    foreach ($Things as $T) {
      if ($T['BuildState'] != BS_COMPLETE || $T['LinkId'] <= 0 ) continue;
      if ($TTypes[$T['Type']]['Properties'] & THING_MOVES_AFTER) {
      } else {
        $UsedLinks[$T['LinkId']][$T['Whose']] = ($T['SystemId'] == $T['NewSystemId']);
      }
    }
  }
  // var_dump($UsedLinks);

  GMLog("<table border><tr><td>Who<td>What<td>Level<td>From<td>Link<td>To<td>" . ($LOWho?"Paid<td>":'') . "Stop<td>Why Stopping\n");
  foreach ($Things as $T) {
    if ($T['BuildState'] != BS_COMPLETE || $T['LinkId'] <= 0 || $T['Whose']==0 || $T['CurHealth']==0) continue;
    if (( $Mode == 0 &&  ($TTypes[$T['Type']]['Properties'] & THING_MOVES_AFTER)) ||
      ( ($Mode ==1) &&  ($TTypes[$T['Type']]['Properties'] & THING_MOVES_AFTER) ==0 ) ) continue;

      $CheckNeeded = ( ($Mode ==1) && ($TTypes[$T['Type']]['Properties'] & THING_MOVES_AFTER) );

      if ($T['LinkId']>0 && $T['NewSystemId'] != $T['SystemId'] ) {
        $Tid = $T['id'];
        $Lid = $T['LinkId'];

        $L = Get_Link($Lid);

        $Fid = $T['Whose'];
        $SR1 = Get_SystemR($L['System1Ref']);
        $SR2 = Get_SystemR($L['System2Ref']);

        GMLog("<tr><td>" . $Facts[$Fid]['Name'] . "<td><a href=ThingEdit.php?id=$Tid>" . $T['Name']  . "<td>" . $T['Level']);
        if ($T['SystemId'] == $SR1['id']) {
          GMLog("<td>" . $L['System1Ref'] . "<td style=color:" . $LinkLevels[abs($L['Level'])]['Colour'] . ";>" .
            ($L['Name']?$L['Name']:"#$Lid"). "<td>" . $L['System2Ref']);
        } else {
          GMLog("<td>" . $L['System2Ref'] . "<td style=color:" . $LinkLevels[abs($L['Level'])]['Colour'] . ";>" .
            ($L['Name']?$L['Name']:"#$Lid"). "<td>" . $L['System1Ref']);
        }

        // var_dump($CheckNeeded,$T['LinkPay']);

        if ($LOWho) {
          if ($L['Status'] != 0) {
            GMLog("<td class=Err>" . $LinkStates[$L['Status']]);
          } else if ($L['Level'] ==1 || $T['LinkPay']<0 || ($LOWho>0 && $Fid == $LOWho)) {
            GMLog("<td>Free");
          } elseif ($T['LinkPay'] > 0) {
            GMLog("<td>Yes");
          } elseif ($CheckNeeded && isset($UsedLinks[$Lid][$T['Whose']]) && $UsedLinks[$Lid][$T['Whose']]) {
            GMLog("<td>Following");
          } else {
            GMLog("<td><b>No</b");
          }
        }

        //      GMLog("<td>" . (($L['Level'] ==1 || $T['LinkPay']<0)?'Free':($T['LinkPay'] > 0?'Yes':
        //            ($CheckNeeded && isset($UsedLinks[$Lid][$T['Whose']]) && $UsedLinks[$Lid][$T['Whose']])?'Following':'<b>No</b>')));
        GMLog("<td>" . fm_checkbox('',$_REQUEST,"Prevent$Tid") . fm_text1('', $_REQUEST,"Reason$Tid"));
      }
  }
  GMLog("</table><input type=submit value='Click to Proceed'></form>\n");
  dotail();

}

// Mode 0 = Normal, 1 = Agents, 2 = Retreats
function ShipMovements($Mode=0) {
  global $GAME,$GAMEID,$LinkStates;
  // Foreach thing, do moves, generate list of new survey reports & levels, update "knowns"

  if (!file_exists("Turns/" . $GAMEID . "/" . $GAME['Turn'])) $LF = mkdir("Turns/" . $GAMEID . "/" . $GAME['Turn'],0777,true);
  $LinkLevels = Get_LinkLevels();

  switch ($Mode) {
    case 0:
      $Things = Gen_Get_Cond('Things',"GameId=$GAMEID ORDER BY RAND()");
      $Done = $GAME['Turn'];
      break;
    case 1:
      $Things = Get_AllThings();
      $Done = $GAME['Turn'];
      break;
    case 2:
      $Things = Gen_Get_Cond('Things', "GameId=$GAMEID AND Retreat!=0");
      $Done = -$GAME['Turn'];
      break;
  }

  $TTypes = Get_ThingTypes();
  $Facts = Get_Factions();
  $LOwner = GameFeature('LinkOwner',0);
  $LinkState = array_flip($LinkStates);
  $LinkMethod = Feature('LinkMethod','Gates');

  foreach ($Things as $T) {
    if ($T['BuildState'] != BS_COMPLETE || $T['LinkId'] <= 0 || $T['CurHealth']==0) continue;
    if (( ($Mode == 0) &&  ($TTypes[$T['Type']]['Properties'] & THING_MOVES_AFTER)) ||
      ( ($Mode == 1) &&  ($TTypes[$T['Type']]['Properties'] & THING_MOVES_AFTER) ==0 ) ) continue;
      if ($T['LastMoved'] == $Done) continue; // Already done

      $Tid = $T['id'];
      $Fid = $T['Whose'];
      $Lid = $T['LinkId'];
      $Retreat = 0;
      if ($Lid > 0) $L = Get_Link($Lid);

      if (isset($_REQUEST["Prevent$Tid"]) && $_REQUEST["Prevent$Tid"] ) {
        TurnLog($Fid,$T['Name'] . " was <b>unable to take link</b> <span style=color:" . $LinkLevels[abs($L['Level'])]['Colour'] . ">" .
          ($L['Name']?$L['Name']:"#$Lid") . " </span> because of " .
          (isset($_REQUEST["Reason$Tid"])? $_REQUEST["Reason$Tid"]:"Unknown reasons"), $T);
        $T['LastMoved'] = $Done;
        Put_Thing($T);
        continue;
      }

      if ($T['LinkId']>0 && $T['NewSystemId'] != $T['SystemId'] ) {
        // if link out & not spider - cant move

        if ($L && ($L['Status'] > 0) && ($Fid != $LOwner) && ($L['GameId']>0)) {
          TurnLog($Fid,$T['Name'] . " was <b>unable to take link</b> <span style=color:" . $LinkLevels[abs($L['Level'])]['Colour'] . ">" .
            ($L['Name']?$L['Name']:"#$Lid") . " </span> beause of it is " .
            $LinkStates[$L['Status']],$T);
          $T['LastMoved'] = $Done;
          Put_Thing($T);
          continue;
        } else if (!$L || ($L['GameId'] < 0)) {
          GMLog("Not Moving " . $T['Name']);
          TurnLog($Fid,$T['Name'] . " was <b>unable to take link</b>" . ($L['Name']?$L['Name']:"#$Lid"). " because it no longer exists",$T);
          $T['LastMoved'] = $Done;
          Put_Thing($T);
          continue;
        }
        GMLog("Moving " . $T['Name']);

        $ShipScanLevel = $T['SensorLevel'];
        $ShipNebScanLevel = NebScanners($T);

        $SR1 = Get_SystemR($L['System1Ref']);
        $SR2 = Get_SystemR($L['System2Ref']);

        if ($T['SystemId'] == $SR1['id']) {
          $Sid = $T['NewSystemId'] = $SR2['id'];
          $Ref = $SR2['Ref']; // Names...
          $N = $SR2;
          $OldSid = $SR1['id'];
          $OldN = $SR1;
          $GFrom = 'A';
          $GTo = 'B';
        } else {
          $Sid = $T['NewSystemId'] = $SR1['id'];
          $Ref = $SR1['Ref'];
          $N = $SR1;
          $OldSid = $SR2['id'];
          $OldN = $SR2;
          $GFrom = 'B';
          $GTo = 'A';
        }

        if (($Mode != 1) && ($T['Whose'] != $LOwner)) {
          $L['UseCount'] += $T['Level'];
          if (Feature('LinksExplode') && ($L['UseCount'] > 100*$L['Weight'])) {// something breaks
            $BadProb = $T['Level']*($L['UseCount'] - 100*$L['Weight']);
            if (rand(0,100*$L['Weight']) > $BadProb) { // There she blows!!!!
              $DamageDice = (abs($L['Level'])+1)*2;
              GMLog("<span class=Red>LINK EXPLOSION</span> on link " . $L['id'] . " from " . $L['System1Ref'] . " to " . $L['System2Ref'] );
              GMLog("Movement will be paused.  Do ($DamageDice D10) x 10 to everything (Including Outposts, Space Stations etc) in " .
                "<a href=Meetings.php?ACTION=Check&R=" . $L['System1Ref'] . ">" . $L['System1Ref'] . "</a> And " .
                "<a href=Meetings.php?ACTION=Check&R=" . $L['System2Ref'] . ">" . $L['System2Ref'] . "</a>");
              // Destroy Link
              //            db_delete('Links',$L['id']);

              // Emergency lockdown both ends

              SetAllLinks($L['System1Ref'], $SR1['id'],$LinkState['In Safe Mode']);
              SetAllLinks($L['System2Ref'], $SR2['id'],$LinkState['In Safe Mode']);

              Report_Others(0, $SR1['id'], 31, "Link " . ($L['Name']?$L['Name']:"#$Lid"). " Exploded.  All other links in " . $L['System1Ref'] .
                " have been put in Safe Mode");
              Report_Others(0, $SR2['id'], 31, "Link " . ($L['Name']?$L['Name']:"#$Lid"). " Exploded.  All other links in " . $L['System2Ref'] .
                " have been put in Safe Mode");

              // Remove the link!

              $L['GameId'] = - $L['GameId'];
              Put_Link($L);
              $SetBreak = 1;
            }
          }

          if ($T['BuildFlags'] & BUILD_FLAG1) $L['NextTurnMod']++;

          $EInst = $L['Instability'];
          if ($L['ThisTurnMod']) $EInst = max(1,$EInst+$L['ThisTurnMod']);

          if (($LinkMethod == 'Wormholes') && $T['Stability'] < $EInst) {
            TurnLog($Fid,$T['Name'] . " Did not have enough Stability to go through Link " . ($L['Name']?$L['Name']:"#$Lid") .
              " it currently has instability $EInst.\nA few small fragments where spayed into $Ref the rest is deposited across the multiverse.", $T);
            GMLog($Facts[$Fid]['Name'] . " - <a href=ThingEdit.php?id=$Tid>" . $T['Name'] . "</a> did not have enough Stability to go through Link " .
              ($L['Name']?$L['Name']:"#$Lid") . " it currently has instability $EInst It should be destroyed (no debris)");
            Report_Others(0, $Sid, 31, "A spray of debris that may once have been part of a ship is seen leaving the wormhole " .
              ($L['Name']?$L['Name']:"#$Lid"));
          }

          Put_Link($L);
        }

        $MineChecks = ['From'=>$GFrom, 'To'=>$GTo];
        foreach ($MineChecks as $Dir=>$MC) {
          //      var_dump($L);
          if ($L["Mined$MC"]) {
            $Mine = Get_Thing($L["Mined$MC"]);
            if ($Mine) {
              // echo "Mine Damage "; var_dump($T,$N,$OldN,$Dir); echo "<P>";
              if ($Dir == 'To' ) {
                Do_Mine_Damage($T,$Mine, $N,1);
              } else {
                Do_Mine_Damage($T,$Mine, $OldN,1);
              }
            }
            if ($T['BuildState'] != BS_COMPLETE) continue 2;
          }
        }

        if ($Fid) {
          $FS = Get_FactionSystemFS($Fid,$Sid);
          if ($N['Nebulae'] > $T['NebSensors']) {
            // is there a ship with neb sensors?
            $Ship = Gen_Get_Cond1('Things',"SystemId=$Sid AND NebSensors>0 AND Whose=$Fid");
            //echo "Testing on Retreat - is there a ship? $Sid"; var_dump($Ship);
            if (!$Ship) $T['Retreat'] = 1;
            if (!isset($FS['id']) || (($FS['ScanLevel'] < $ShipScanLevel) && ($N['Nebulae']<=$ShipNebScanLevel))) {
              $SP = ['FactionId'=>$Fid, 'Sys'=> $Sid, 'Scan'=>-1, 'Type'=>0,
                'Turn'=>$GAME['Turn'], 'ThingId'=>$T['id'], 'GameId'=>$GAMEID];
              Insert_db('ScansDue', $SP);
            }
          } else {
            if (!isset($FS['id']) || (($FS['ScanLevel'] < $ShipScanLevel) && ($N['Nebulae']<=$ShipNebScanLevel))) {
              $SP = ['FactionId'=>$Fid, 'Sys'=> $Sid, 'Scan'=>($N['Nebulae']?$ShipNebScanLevel:$ShipScanLevel), 'Type'=>0,
                'Turn'=>$GAME['Turn'], 'ThingId'=>$T['id'], 'GameId'=>$GAMEID];
              Insert_db('ScansDue', $SP);
            }
          }

/*
          $FL = Get_FactionLinkFL($Fid,$Lid);
          $FL['Known'] = 1;
          Put_FactionLink($FL);
          */
          $FLK = Gen_Get_Cond1('FactionLinkKnown',"FactionId=$Fid AND LinkId=$Lid");
          if ($FLK && !$FLK['Used']) {
            $FLK['Used'] = 1;
            Gen_Put('FactionLinkKnown',$FLK);
          } else {
            $FLK = ['FactionId' => $Fid,'LinkId'=>$Lid,'Used'=>1];
            Gen_Put('FactionLinkKnown',$FLK);
          }

          $pname = System_Name($N,$Fid);
        } else {
          $pname = $N['Ref'];
        }

        // Leaving Minefields
        if ($Mode == 0) Move_Thing_Within_Sys($T,1,1);
        //      $N = Get_System($T['NewSystemId']);
        $EndLocs = Within_Sys_Locs($N);
        $T['SystemId'] = $T['NewSystemId'];
        $T['WithinSysLoc'] = $T['NewLocation'];
        if ($Mode == 0) Move_Thing_Within_Sys($T,$T['NewLocation'],1);
        //      SKLog("Moved to $pname along " . $LinkLevels[$L['Level']]['Colour']. " link #$Lid to " . $EndLocs[$T['NewLocation']]);
        if ($Fid) {
          if (isset($SetBreak)) {
            TurnLog($Fid,$T['Name'] . " attempted to move from " . System_Name($OldN,$Fid) . " along <span style='color:" . $LinkLevels[abs($L['Level'])]['Colour'] .
              ";'>link " . ($L['Name']?$L['Name']:"#$Lid"). " </span>to $pname " . ($T['NewLocation'] > 2?( " to " .
                $EndLocs[$T['NewLocation']]): "") . " unfortunately the link was " .
              "overloaded and exploded - see turn report" ,$T);
                $T['CurHealth'] = 0;
                $T['SystemId'] = 0;
          } else {
            TurnLog($Fid,$T['Name'] . " has " . (($Mode<2)?"moved":("<b>retreated</b> " . ['','From a Nebula','From Combat'][$T['Retreat']])) .
              " from " . System_Name($OldN,$Fid) . " along <span style='color:" .
              $LinkLevels[abs($L['Level'])]['Colour'] . ";'>link " . ($L['Name']?$L['Name']:"#$Lid"). " </span>to $pname " .
              ($T['NewLocation'] > 2?( " to " . $EndLocs[$T['NewLocation']]): ""),$T);
            GMLog($T['Name'] . " has " . (($Mode<2)?"moved":"retreated") . " from " . System_Name($OldN,$Fid) . " along <span style='color:" .
              $LinkLevels[abs($L['Level'])]['Colour'] . ";'>link " . ($L['Name']?$L['Name']:"#$Lid"). " </span>to $pname " .
              ($T['NewLocation'] > 2?( " to " . $EndLocs[$T['NewLocation']]): ""));
          }
        }
        //    $T['LinkId'] = 0;
        if ($T['Instruction'] != 0 && !Has_Tech($Fid,'Stargate Construction') ) $T['Instruction'] = 0;
        $T['LastMoved'] = $Done;
        Put_Thing($T);
        if (isset($SetBreak)) return 0; // Will need to come back in to finish movements after damage
      } else if ( $T['WithinSysLoc'] != $T['NewLocation'] && $T['NewLocation']>1) {
        if ($Mode == 0) {
          Move_Thing_Within_Sys($T,1,1);
          Move_Thing_Within_Sys($T,$T['NewLocation'],1);
        } else {
          $T['WithinSysLoc'] = $T['NewLocation'];
        };
        $N = Get_System($T['SystemId']);
        $Sid = $T['SystemId'];
        $Fid = $T['Whose'];

        $pname = System_Name($N,$Fid);

        $EndLocs = Within_Sys_Locs($N);
        //      SKLog("Moved to " . $EndLocs[$T['NewLocation']] . " within $pname");
        if ($Fid) TurnLog($Fid,$T['Name'] . " moved to " . $EndLocs[$T['NewLocation']] .  " within $pname",$T);
        $T['LastMoved'] = $Done;
        Put_Thing($T);
      }

  }
  return 1;
}

function SeeAfterMove() {
  global $db,$GAMEID,$GAME;
  // Save What can I see data
  $Factions = Get_Factions();
  foreach($Factions as $F) {
    $Fid = $F['id'];
    $CouldC = WhatCanBeSeenBy($Fid,1);
    $CB = fopen("Turns/$GAMEID/" . $GAME['Turn'] . "/CouldM$Fid.html", "w");
    fwrite($CB,$CouldC);
    fclose($CB);
    GMLog("Saved What could be seen for " . $F['Name']);
  }
  return 1;

}

function SaveWhatCanBeSeen() {
  global $db,$GAMEID,$GAME;
  // Save What can I see data
  $Factions = Get_Factions();
  foreach($Factions as $F) {
    $Fid = $F['id'];
    $CouldC = WhatCanBeSeenBy($Fid,1);
    $CB = fopen("Turns/$GAMEID/" . $GAME['Turn'] . "/CouldC$Fid.html", "w");
    fwrite($CB,$CouldC);
    fclose($CB);
    GMLog("Saved What could be seen for " . $F['Name']);
  }
  return 1;
}


function UnloadTroops() {
  $Things = Get_Things_Cond(0,"BuildState=" . BS_COMPLETE . " AND ( LinkId=" . LINK_BOARDING . " OR LinkId=" . LINK_LOAD_AND_UNLOAD . ")");
  $TTypes = Get_ThingTypes();
  $Facts = Get_Factions();

  foreach($Things as $T) {
    $HostId = $T['SystemId'];
    $H = Get_Thing($HostId);

    $N = Get_System($H['SystemId']);
    $Syslocs = Within_Sys_Locs($N,0,0,0,1);

    $T['SystemId'] = $H['SystemId'];
    if (isset($Syslocs[$T['NewLocation']])) {
      $T['WithinSysLoc'] = $T['NewLocation'];
    } else {
      $T['WithinSysLoc'] = 1;
    }
    $T['LinkId'] = 0;

    TurnLog($T['Whose'], $T['Name'] . " has been unloaded from " . $H['Name'] . " in " . $N['Ref'] . " to " . $Syslocs[$T['WithinSysLoc']], $T);
    if ($T['Whose'] != $H['Whose']) TurnLog($H['Whose'], $T['Name'] . " has been unloded from " . $H['Name'] . " in " . $N['Ref'] . " to " .
      $Syslocs[$T['WithinSysLoc']]);
    GMLog($T['Name'] . " has been unloaded from " . $H['Name'] . " in " . $N['Ref'] . " to " . $Syslocs[$T['WithinSysLoc']]);
    Put_Thing($T);
  }

  GMLog("Unload Troops is complete<p>");
  return 1;
}

function RetreatsSelection() {
  global $GAME,$GAMEID;
  $Things = Get_Things_Cond_Ordered(0,"(BuildState=" . BS_COMPLETE . " ) AND ( Retreat!=0 ) AND (GameId=$GAMEID)");
  $TTypes = Get_ThingTypes();
  $Facts = Get_Factions();
  $SysHasNeb = [];
  $SRefs = Get_SystemRefs();
  $RefSs = array_flip($SRefs);

  if ($Things) {
    GMLog("<h2>These could retreat - Do check, tick the stop box and say why if needed</h2>");
    GMLog("<form method=Post action=TurnActions.php?ACTION=DoStage2>" . fm_hidden('Stage','Retreats Selection'));
    GMLog("<table border><tr><th>Whose<th>What<th>Reason<th>From<th>To<th>Stop<th>Why");
    foreach ($Things as $Tid=>$T) {
      $Loc = $T['SystemId'];
      $Fid = $T['Whose'];

      switch ($T['Retreat']) {
        case 1: // Nebula
          // Is there a ship with a sensor there?
          if (isset($SysHasNeb[$Loc][$Fid])) {
            if ($SysHasNeb[$Loc][$Fid]) continue 2;
          } else {
            $Ship = Gen_Get_Cond1('Things',"SystemId=$Loc AND NebSensors>0 AND Whose=$Fid");
            // echo "Testing on Retreat - is there a ship?"; var_dump($Ship);
            if ($Ship) {
              $SysHasNeb[$Loc][$Fid] = $Ship;
              continue 2;
            }
          }
          // Drop Through
        case 2: // Combat

          if ($T['LinkId']> 0) {
            $L = Get_Link($T['LinkId']);
            $To = (($L['System1Ref'] == $SRefs[$Loc])? $RefSs[$L['System2Ref']] :  $RefSs[$L['System1Ref']]);
            $Totxt = "(" . $L['Name'] . ") to " . $SRefs[$To] . fm_hidden("RetreatLink$Tid",$T['LinkId']);
          } else {
            $res = Moves_4_Thing($T,0, 1);
            [$Links, $SelLinks, $SelCols ] = $res;
            $Totxt = fm_select($SelLinks,$T,'LinkId',0," style=color:" . $SelCols[$T['LinkId']] ,"RetreatLink$Tid",0,$SelCols) .
               ' <span class=red>SET THIS!</span>';
          }


          GMLog("<tr><td>" . $Facts[$Fid]['Name'] . "<td><a href=ThingEdit.php?id=$Tid>" . $T['Name'] . "</a><td>" .
            ['','From Nebula','From Combat'][$T['Retreat']] . "<td>" . $SRefs[$Loc] . "<td>$Totxt<td>" .
            fm_checkbox('',$_REQUEST,"RetreatBox$Tid") . fm_text1('', $_REQUEST,"RetreatTxt$Tid"));
          break;
       }
    }
    GMLog("</table><input type=submit value='Click to Proceed'></form>\n");
    dotail();

  }
  return 2;
}

function Retreats() {
  global $GAME,$GAMEID;
  $Things = Get_Things_Cond_Ordered(0,"(BuildState=" . BS_COMPLETE . ") AND ( Retreat!=0 ) AND (GameId=$GAMEID)");
  $TTypes = Get_ThingTypes();
  $Facts = Get_Factions();
  $SysHasNeb = [];
  $SRefs = Get_SystemRefs();
  $RefSs = array_flip($SRefs);
  $Call = 0;

//  var_dump($_REQUEST);

  if ($Things) {
    foreach ($Things as $Tid=>$T) {
      $Loc = $T['SystemId'];
      $Fid = $T['Whose'];
      if (isset($_REQUEST["RetreatLink$Tid"])) {
        $T['NewSystemId'] = -1;
        if (($_REQUEST["RetreatBox$Tid"]??0) == 'on') {
          TurnLog($Fid,$T['Name'] . " could have retreated, but didn't because " . ($_REQUEST["RetreatTxt$Tid"]??'Unknown reason'));
          $T['Retreat'] = 0;
        } else {
          $Call = 1;
          if ($_REQUEST["RetreatLink$Tid"] != $T['LinkId']) {
            $T['LinkId'] = $_REQUEST["RetreatLink$Tid"];
          }
        }
        Put_Thing($T);
      }
    }

    if ($Call) ShipMovements(2);
  }

  GMLog("Retreats have been completed<p>");
  return 1;

}

