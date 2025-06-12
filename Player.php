<?php
  include_once("sk.php");
  global $FACTION,$GAMEID,$USER,$GAME;
  include_once("GetPut.php");
  include_once("PlayerLib.php");
  global $PlayerState,$PlayerStates;

function ValidateTurn($For = 0) {
  global $FACTION,$GAME;
  dostaffhead("Player Actions");
  if ($For) {
    $Fid = $For;
    $Fact = Get_Faction($Fid);
  } else {
    $Fid = $FACTION['id'];
    $Fact = $FACTION;
  }
  $Turn = $GAME['Turn'];
  $Valid = 1;

  $ProjTypes = Get_ProjectTypes();
  $PostItType = 0;
  foreach($ProjTypes as $Pti=>$Pt) if ($Pt['Name'] == 'Post It') { $PostItType=$Pti; break;}

  // Check for Post it notes
  $Projects = Get_Projects_Cond("FactionId=$Fid AND Type=$PostItType AND ((TurnStart=$Turn AND Status=0 ) OR (Status=1 AND TurnStart<=$Turn " .
    "AND ( TurnEnd=0 OR TurnEnd>=$Turn)))");
  if ($Projects) {
    echo "<h2 class=Err>Warning - you have " . count($Projects) . " Post It Note projects this turn</h2>\n";
    $Valid = 0;
  }

  $OpTypes = Get_OpTypes();
  $PostItType = 0;
  foreach($OpTypes as $Pti=>$Pt) if ($Pt['Name'] == 'Post It') { $PostItType=$Pti; break;}

  // Check for Post it notes
  $Operations = Gen_Get_Cond('Operations',"Whose=$Fid AND Type=$PostItType AND ((TurnStart=$Turn AND Status=0 ) OR (Status=1 AND TurnStart<=$Turn " .
    "AND ( TurnEnd=0 OR TurnEnd>=$Turn)))");
  if ($Operations) {
    echo "<h2 class=Err>Warning - you have " . count($Operations) . " Post It Note operations this turn</h2>\n";
    $Valid = 0;
  }

  $Projects = Get_Projects_Cond("FactionId=$Fid AND TurnStart=$Turn");
  $TTypes = Get_ThingTypes();
  $TNames = array_flip(NamesList($TTypes));
  foreach ($Projects as $P) {
    if (($ProjTypes[$P['Type']]['Props'] & 6) == 2) { // Has one thing but not 2
      $Pid = $P['id'];
      if (empty($P['Name'])) $P['Name'] = 'Nameless';
      $Tid = $P['ThingId'];
      if ($Tid == 0) {
        dostaffhead("Player Actions");
        echo "<h2 class=Err>Warning - your project: <a href=ProjEdit.php?id=$Pid>" . $P['Name'] . "</a> is level " . $P['Level'] .
             " trying to make an unknown thing</h2>\n";
        $Valid = 0;
        continue;
      }
      $T = Get_Thing($Tid);
//var_dump($T); echo "<p>";

      if (empty($T)) {
        dostaffhead("Player Actions");
        echo "<h2 class=Err>Warning - your project: <a href=ProjEdit.php?id=$Pid>" . $P['Name'] . "</a> is level " . $P['Level'] .
             " trying to make an unknown thing</h2>\n";
        $Valid = 0;
      } else if (($ProjTypes[$P['Type']]['Props'] & 0x40) == 0) { //Surpress level check
        if ($P['Level'] != $T['Level']) {
          if ($P['Level'] < $T['Level']) {
            dostaffhead("Player Actions");
            echo "<h2 class=Err>Warning - your project: <a href=ProjEdit.php?id=$Pid>" . $P['Name'] . "</a>  is level " . $P['Level'] .
                 " trying to make a level " . $T['Level'] . " thing</h2>\n";
            $Valid = 0;
          } elseif ($T['Type'] != $TNames['Fighter']) {
            dostaffhead("Player Actions");
            echo "<h2 class=Err>Warning - your project: <a href=ProjEdit.php?id=$Pid>" . $P['Name'] . "</a>  is level " . $P['Level'] .
                 " trying to make a level " . $T['Level'] . " thing</h2>\n";
            $Valid = 0;
          }
        }
      }
    }
  }

 /*
  $Things = Get_Things_Cond($Fid,"Instruction!=0") {
  foreach ($Things as $T) {
    switch ($T['Instruction']) {
    switch ($ThingInstrs[abs($T['Instruction'])]) {
      case 'Colonise':
        $Prog = Has_Tech($T['Whose'], 'Planetary Construction');
        $Mods = Get_ModulesType($Tid, 'Colonisation Gear');
        if ($Prog*$Mods[0]['Number'] == 0) {
          GMLog("Colonisation by <a href=ThingEdit.php?id=$Tid>" . $T['Name'] . "Has zero progress - Tell Richard");
          FollowUp($T['Whose'],"Colonisation by <a href=ThingEdit.php?id=$Tid>" . $T['Name'] . "Has zero progress - Tell Richard");
        }
        break;
      case 'Make Outpost':
      case 'Make Asteroid Mine':
      case 'Make Minefield':
      case 'Make Orbital Repair Yard':
      case 'Build Space Station':
      case 'Expand Space Station' :
      case 'Make Adbanced Deep Space Sensor':
      case 'Make Deep Space Sensor':
      case 'Make Advanced Asteroid Mine':
      case 'Dismantle Stargate':
        // specified?
      case 'Build Stargate':
      case 'Make Planet Mine':
      case 'Construct Command Relay Station':
      case 'Repair Command Node': // Not coded yet
      case 'Make Something':
      case 'Make Warpgate':
      case 'Link Repair':
        $Prog = Has_Tech($T['Whose'],'Space Construction Gear');
        $Mods = Get_ModulesType($Tid, 'Space Construction Gear');
        $ProgGain = $Prog*$Mods[0]['Number'];
        GMLog("$ProgGain progress on " . $ThingInstrs[abs($T['Instruction'])] . " for " . $Facts[$T['Whose']]['Name'] . ":" . $T['Name']);


        $T['Progress'] = min($T['ActionsNeeded'],$T['Progress']+$ProgGain);
        Put_Thing($T);
        break;

      case 'Collaborative DSC': // Dist1 has Thing number being helped
        $Prog = Has_Tech($T['Whose'],'Space Construction Gear');
        $Mods = Get_ModulesType($Tid, 'Space Construction Gear');
        $ProgGain = $Prog*$Mods[0]['Number'];
        $HT = Get_Thing($T['Dist1']);
        if ($HT && $HT['Instruction']) {
          $HT['Progress'] = min($HT['ActionsNeeded'],$HT['Progress']+$ProgGain);
          GMLog("$ProgGain progress on " . $ThingInstrs[abs($HT['Instruction'])] . " for " . $Facts[$HT['Whose']]['Name'] . ":" . $HT['Name']);
          TurnLog($HT['Whose'],$T['Name'] . " did $ProgGain towards completing " . $ThingInstrs[abs($HT['Instruction'])] . " by " . $HT['Name']);
          TurnLog($T['Whose'],$T['Name'] . " did $ProgGain towards completing " . $ThingInstrs[abs($HT['Instruction'])] . " by " . $HT['Name']);

          Put_Thing($HT);
        }
        break;

      case 'Analyse Anomaly':
        $Aid = $T['ProjectId'];
        $Fid = $T['Whose'];
        if ($Aid) {
          $A = Get_Anomaly($Aid);
          $FAs = Gen_Get_Cond('FactionAnomaly',"FactionId=$Fid AND AnomalyId=$Aid");
          if ($FAs) {
            $FA = $FAs[0];
            $Pro = $T['Sensors']*$T['SensorLevel'];
            $T['Progress'] = $FA['Progress'] = min($FA['Progress']+$Pro, $A['AnomalyLevel']);
            Gen_Put('FactionAnomaly',$FA);
            Put_Thing($T);
            TurnLog($Fid,$T['Name'] . " did $Pro towards completing anomaly " . $A['Name'] . " now at " . $FA['Progress'] . " / " . $A['AnomalyLevel'],$T);
          } else {
            TurnLog($Fid, $T['Name'] . " is supposed to be analysing an anomaly - but there isn't one selected",$T);
          }
        } else { // No anomaly is there one here?
          $Anoms = Gen_Get_Cond('Anomalies',"SystemId=" . $T['SystemId']);
          if ($Anoms) {
            foreach($Anoms as $A) {
              $Aid = $A['id'];
              $FAs = Gen_Get_Cond('FactionAnomaly',"AnomalyId=$Aid AND FactionId=$Fid");
              if (empty($FAs[0]['id'])) continue;
              $FA = $FAs[0];
              if ($FA['Progress'] < $A['AnomalyLevel']) {
                $Pro = $T['Sensors']*$T['SensorLevel'];
                $T['Progress'] = $FA['Progress'] = min($FA['Progress']+$Pro, $A['AnomalyLevel']);
                Gen_Put('FactionAnomaly',$FA);
                $T['ProjectId'] = $Aid;
                Put_Thing($T);
                TurnLog($Fid,$T['Name'] . " did $Pro towards completing anomaly " . $A['Name'] . " now at " . $FA['Progress'] . " / " . $A['AnomalyLevel'],$T);
              }
              break 2;
            }
          }
          TurnLog($Fid,$T['Name'] . " is supposed to be analysing an anomaly - but there isn't one",$T);
        }
        break;

      case 'Salvage':
        $Prog = Has_Tech($T['Whose'],'Salvage Rigs');
        GMLog("$Prog progress on " . $ThingInstrs[abs($T['Instruction'])] . " for " . $Facts[$T['Whose']]['Name'] . ":" . $T['Name']);
        $T['Progress'] = min($T['ActionsNeeded'],$T['Progress']+$Prog);
        Put_Thing($T);
        break;

      default:
        break;
     }
   }

    }
  }
  */
  if (Has_Trait($Fid, 'This can be optimised')) {
    $Ws = Get_Worlds($Fid);
    $TTypes = Get_ThingTypes();
    $PlanetTypes = Get_PlanetTypes();
    $DTs = Get_DistrictTypes();

    foreach($Ws as $W) {
      $H = Get_ProjectHome($W['Home']);
      switch ($W['ThingType']) {
      case 1: //Planet
        $WH = $P = Get_Planet($W['ThingId']);
        $type = $PlanetTypes[$P['Type']]['Name'];
        if ($PlanetTypes[$P['Type']]['Append']) $type .= " Planet";
        $Name = $P['Name'];
        break;

      case 2: /// Moon
        $WH = $M = Get_Moon($W['ThingId']);
        $type = $PlanetTypes[$M['Type']]['Name'];
        if ($PlanetTypes[$M['Type']]['Append']) $type .= " Moon";
        $Name = $M['Name'];
        break;

      case 3: // Thing
        $WH = $T = Get_Thing($W['ThingId']);
        $type = $TTypes[$T['Type']]['Name'];
        $Name = $T['Name'];
        break;
      }

      $Dists = Get_DistrictsH($H['id']);

      $DeltaSum = $Used = 0;
      foreach($Dists as $D) {
        if ($D['Delta']) {
          $Used++;
          $DeltaSum += $D['Delta'];
        }
      }
      if ($Used == 0) {
        dostaffhead("Player Actions");
        echo "<h2 class=Err>Warning - No use of <b>This can be optimised</b> on $Name</h2>";
        $Valid = 0;
      } elseif ($Used > 2) {
        dostaffhead("Player Actions");
        echo "<h2 class=Err>Warning - <b>This can be optimised</b> can only be used once on $Name</h2>";
        $Valid = 0;
      } elseif ($DeltaSum  != 0) {
        dostaffhead("Player Actions");
        echo "<h2 class=Err>Warning - <b>This can be optimised</b> on $Name Does not Sum to Zero</h2>";
        $Valid = 0;
      }
    }
  }


  return $Valid;
}

global $SkipAccessCheck;

  if (!isset($SkipAccessCheck)) A_Check('Player');
//  dostaffhead("Player Actions");

  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
      case 'Submit' :  // TODO add checking of turn
        if (!ValidateTurn()) {
          echo "<h2><a href=Player.php>Return to faction menu</a></h2>\n";
          echo "<h2><a href=Player.php?ACTION=SubmitForce>Force the submission past the errors</a></h2>\n";
          dotail();
        }

        $FACTION['TurnState'] = $PlayerStates['Turn Submitted'];
        Put_Faction($FACTION);
        break;

      case 'SubmitForce' :  // Yes to submit unfinished
        $FACTION['TurnState'] = $PlayerStates['Turn Submitted'];
        Put_Faction($FACTION);
        break;

      case 'Unsub' :
        if ($FACTION['TurnState'] == $PlayerStates['Turn Submitted']) {
          $FACTION['TurnState'] = (($GAME['Turn'] == 0)? $PlayerStates['Setup']: $PlayerStates['Turn Planning']);
          Put_Faction($FACTION);
        } else {
          echo "<h1 class=Err>Sorry, not allowed now</h1>";
        }

        break;
    }
  }
  $Facts = Get_Factions();
  $Fs = [];
  if (isset($FACTION['Player'])) foreach($Facts as $F) {
    if ((!empty($FACTION['Player']) && ($F['Player'] == $FACTION['Player'])) ||
        (!empty($FACTION['Player2']) && ($F['Player'] == $FACTION['Player2']))) {
      $Fs[] = $F['id'];
    }
  }

//var_dump($Fs);
  dostaffhead("Player Actions");
  global $ErrorMessage;

  if (isset($ErrorMessage)) echo "<h2 class=Err>$ErrorMessage</h2>\n";

  if (count($Fs) > 1) {
    foreach($Fs as $Fid) {
      $F = $Facts[$Fid];
      echo "<a href=Access.php?id=$Fid&Key=" . $F['AccessKey'] . " style='background:" . $F['MapColour'] . "; color: " . ($F['MapText']?$F['MapText']:'black') .
         ";text-shadow: 2px 2px 3px white;padding:2px'><b>"  . $F['Name'] . "</b></a> ";
    }

    echo "<p>\n";
  }


  if (Faction_Feature('BackHereHelp',1) ) echo "You can always get back here by clicking on 'Faction Menu' on the bar above.<br>\n";

  Player_Page();
  dotail();
?>
