<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("PlayerLib.php");
  include_once("SystemLib.php");
  include_once("ProjLib.php");
  include_once("HomesLib.php");
  include_once("TurnTools.php");
  include_once("OrgLib.php");
  A_Check('GM');

  global $Relations;

  $FactFact = [];

/* Get all systems and Factions

  Go through each Home - record systems

  Go through each Things - record systems

  For each system with more than 1 faction - report (Embassy?) - simple list and details button - details == WWhatCanIC
*/

  dostaffhead("Meetups");

  $Systems = Get_Systems();
  $Facts = Get_Factions();

  $Homes = Get_ProjectHomes();
  $TTypes = Get_ThingTypes();
  $ModTypes = Get_ModuleTypes();
  $Techs = Get_Techs();
  $ThingProps = Thing_Type_Props();

//  var_dump($_REQUEST);

function ForceReport($Sid,$Cat) {
  global $Facts, $Homes, $TTypes, $ModTypes, $N, $Techs, $ThingProps,$ARMY ;
  $Things = Get_Things_Cond(0,"SystemId=$Sid AND ( BuildState=2 OR BuildState=3) AND LinkId>=0 ORDER BY Whose");
  $LastF = $Home = $Control = -1;
  $txt = $ftxt = $htxt = $Battct = '';
  $TMsk = ($Cat=='G'?1:2);
  $PlanMoon = [];
  $FirePower = $Wid = $Bat = 0;
  $HomeType = "Unknown";

  if ($Cat == 'G') {
    $PTD = Get_PlanetTypes();  // ONLY works for single taget at present
    $Planets = Get_Planets($Sid);
    foreach($Planets as $Pl) {
      if ($PTD[$Pl['Type']]['Hospitable']) {
        $PlanMoon = $Pl;
        $HomeType = $PTD[$Pl['Type']]['Name'];
        $Control = (!empty($Pl['Control']) ? $Pl['Control'] : $N['Control']);
//echo "Looking for " . $Pl['id'] . "<p>";
        foreach ($Homes as $H) if (($H['ThingType'] == 1) && ($H['ThingId'] == $Pl['id'])) {
          $Home = $H;
          $World = Gen_Get_Cond1('Worlds',"ThingType=" . $H['ThingType'] . " AND ThingId=" . $H['ThingId']);
// var_dump($World);
          if (isset($World['id'])) $Wid = $World['id'];
          break 2;
        }
        echo "Home not found";
        $Home = $Wid = 0;
        break; // CHANGE For Multi targets
      }
    }
  }

  echo "<h2>" . ($Cat =='G' ?'Ground':'Space') . " Force Report for " . $N['Ref'] . ($Cat =='G' ? " - " . ($PlanMoon['Name'] ?? 'Nameless') .
       " ($HomeType)" :'') . "</h2>\n";
  if ($Cat =='G' && $Wid) {
    echo "<h2><a href=WorldEdit.php?ACTION=MilitiaDeploy&id=$Wid>Deploy Militia</a></h2>";
    $MilOrg = Gen_Get_Cond('Branches',"HostType=" . $H['ThingType'] . " AND HostId=" . $H['ThingId'] . " AND (OrgType=3 OR OrgType2=3)");
    if ($MilOrg) {
      echo "<h2><a href=BranchEdit.php?Action=SPAWN_HS&Hid=" . $H['ThingId'] . "&Sid=$Sid>Deploy Heavy Security</a></h2>";
    }
  } else if ($Cat == 'S') {
    $Outpost = Outpost_In($Sid,0,0);
    if ($Outpost) {
      $MilOrg = Gen_Get_Cond('Branches',"HostType=3 AND HostId=" . $Outpost['id'] . " AND (OrgType=3 OR OrgType2=3)");
      if ($MilOrg) {
        echo "<h2><a href=BranchEdit.php?Action=SPAWN_FS&Oid=" . $Outpost['id'] . "&Sid=$Sid>Deploy Defensive Fighters</a></h2>";
      }

    }
  }

  echo "<table border>";
  echo "<tr><td>What<td>Type<td>Level<td>Evasion<td>Health<td>Attack<td>To Hit<td>Speed<td>Actions\n";
  foreach($Things as $T) {
    if ((($Cat == 'S') && ((($TTypes[$T['Type']]['Properties']??0) & 8) != 0)) ||
        (($Cat == 'G') && ((($TTypes[$T['Type']]['Properties']??0) & 0x20) != 0))) {
      if (($T['CurHealth'] == 0) && ($T['Type'] == 20)) continue; // Skip Militia at zero
      if ($T['PrisonerOf'] != 0) continue; // Prisoners
      $Tid = $T['id'];
      if ($LastF != $T['Whose']) {
        if ($LastF >=0) {
          echo $htxt;
          if ($Bat) echo "Battle Tactics: Effectively $Bat ( $Battct ) <br>";
          echo  $ftxt. "<br>Total Firepower: $FirePower" . $txt;
        }
        $BD = $Bat = 0;
        $LastF = $T['Whose'];
        $FirePower = 0;
        $htxt = "<tr><td colspan=9 style='background:" . ($Facts[$LastF]['MapColour']??'Bisque') . "'><h2>" .
          ($Facts[$LastF]['Name']??'Independant') . "</h2><tr><td colspan=9>";
        $txt = $Battct = $ftxt = '';
        $txt .= "<br>Damage recieved: <span id=DamTot$Cat$LastF>0</span>";

        $FTs = Get_Faction_Techs($LastF);

        foreach ($FTs as $FT) {
          if (empty($FT['Tech_Id']) || empty($Techs[$FT['Tech_Id']])) continue;

          $Tech = $Techs[$FT['Tech_Id']];
          if ($Tech['Properties'] & $TMsk) {
            switch ($Tech['Name']) {
            case 'Battle Tactics':
              $Bat += $FT['Level'];
              $Battct .= " Base: " . $FT['Level'];
              continue 2;

            case 'Combined Arms':
              $Bat += 1;
              $Battct .= ", Combined Arms +1 ";
              break;

            case 'Battlefield Intelligence':
              $Agents = Get_Things_Cond($LastF," Type=5 AND Class='Military' AND SystemId=" . $T['SystemId'] . " ORDER BY Level DESC");
              if ($Agents) {
                $Bi = ($Agents[0]['Level']/2);
                $Bat += $Bi;
                $Battct .= ", Battlefield Intelligence +$Bi ";
              }
              break;

            case "$ARMY Tactics - Arctic":
            case "$ARMY Tactics - Desert":
            case "$ARMY Tactics - Desolate":
            case "$ARMY Tactics - Temperate":
              if (strstr($Tech['Name'],$HomeType)) {
                $Bat += 1;
                $Battct .= ", " . $Tech['Name'] . " +1 ";
                break;
              }
              continue 2;

            default:

            }
            $ftxt .= $Tech['Name'] . (($Tech['Cat'] == 0)? ( " Level: " . $FT['Level']) : '') . "<br>\n";
          }
        }
      }

      $txt .= "<tr><td><a href=ThingEdit.php?id=" . $T['id'] . ">" . $T['Name'] . "</a>";

      $Mods = Get_Modules($T['id']);
      foreach($Mods as $M) {
        if ($ModTypes[$M['Type']]['Leveled'] & 6) {
          $txt .= "<br>" . $ModTypes[$M['Type']]['Name'] . (($ModTypes[$M['Type']]['Leveled'] & 1)?" Lvl:" . $M['Level'] :'') . " Num: " . $M['Number'] . "\n";
        }
      }
      $Resc = 0;
      [$BD,$ToHit] = Calc_Damage($T,$Resc);
      $tprops = $ThingProps[$T['Type']];

      $txt .= fm_hidden("OrigData$Tid",implode(":",[$T['CurHealth'], $T['OrigHealth'],0,$T['CurShield'],$T['ShieldPoints']]));
      $txt .= "<td>" . $TTypes[$T['Type']]['Name'] . "<td>" . $T['Level'] . "<td>" . $T['Evasion'];
      $txt .= "<td><span id=StateOf$Tid>" . $T['CurHealth'] . " / " . $T['OrigHealth'];
      if ($T['ShieldPoints']) $txt .= " (" . $T['CurShield'] . "/" . $T['ShieldPoints'] . ") ";
      $txt .= "</span><td><span id=Attack$Tid>$BD</span><td>" . $T['ToHitBonus'] . "<td>";
      if ($TTypes[$T['Type']]['Properties'] & THING_CAN_MOVE) {
        if ($TTypes[$T['Type']]['Properties'] & THING_HAS_ARMYMODULES) {
          $txt .= "Mobility: " . sprintf("%0.3g ",$T['Mobility']);
        } else {
          $txt .= "Speed: " . sprintf("%0.3g ",$T['Speed']);
        }
      }
      $txt .=  fm_number1(" Do",$T,'Damage', ''," class=Num3 onchange=Do_Damage($Tid,$LastF,'$Cat')","Damage:$Tid") . " damage";
      $txt .= fm_checkbox(', Retreat?',$T,'RetreatMe','',"RetreatMe:$Tid");

      $FirePower += $BD;
    } else {
    }

  }
  if ($htxt) {
    echo $htxt;
    if ($Bat) echo "Battle Tactics: Effectively $Bat ( $Battct ) <br>";
    echo  $ftxt. "<br>Total Firepower: <span id=FirePower:$LastF>$FirePower</span>" . $txt;
  }
//  var_dump($txt,$htxt);
  echo "</table>";
}

// Planetary Defences + Militia for Ground Combat

  $TurnP = '';
  if (isset($_REQUEST['TurnP'])) $TurnP = "&TurnP=1";
  $Things = Get_AllThings();
  $Sys = Get_Systems();
  $Hostiles = [];
  $Mtch = [];

  /* NO idea what this was for
  foreach($Homes as $H) {
    switch ($H['ThingType']) {
    case 1: // Planet
      $P = Get_Planet($H['ThingId']);
      $Sid = $P['SystemId'];
      break;
    case 2: // Moon
      $M = Get_Moon($H['ThingId']);
      $P = Get_Planet($M['PlanetId']);
      $Sid = $P['SystemId'];
      break;
    case 3:// Thing - do later
      continue 2;
    }
    $Sys[$Sid][$H['Whose']] = 1024;
  }
*/

//  echo "Checked Homes<p>";

  foreach ($Things as $T){
    if ($T['BuildState'] <2 || $T['BuildState'] >3 || ($T['LinkId'] < 0 && $T['LinkId'] > -5)) continue; // Don't exist
    $Sid = $T['SystemId'];
    $Eyes = ($TTypes[$T['Type']]['Eyes']??0);
    $Hostile = (($TTypes[$T['Type']]['Properties']??0) & THING_IS_HOSTILE) && ($T['PrisonerOf'] == 0);
    if ($Eyes) {
      if (isset($Sys[$Sid][$T['Whose']])) {
        $Sys[$Sid][$T['Whose']] |= $Eyes;
      } else {
        $Sys[$Sid][$T['Whose']] = $Eyes;
      }
      if (isset($Hostiles[$Sid][$T['Whose']])) {
        $Hostiles[$Sid][$T['Whose']] |= $Hostile;
      } else {
        $Hostiles[$Sid][$T['Whose']] = $Hostile;
      }
    }
  }

  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
    case 'Do ALL Damage':
//    var_dump($_REQUEST);
      $IgnoreShield = ($_REQUEST['IgnoreShield'] ?? 0);
      foreach ($_REQUEST as $RN=>$RV) {
        if ($RV && preg_match('/Damage:(\d*)/',$RN,$Mtch)) {
          $Tid = $Mtch[1];
//var_dump($Tid,$RN,$RV);
          $T = Get_Thing($Tid);
          $RDam = $Dam = $RV;
          $T['Conflict'] = 1;
          if (($IgnoreShield == 0) && $T['CurShield']) {
            $shldD = min($T['CurShield'],$Dam);
            $T['CurShield'] = $T['CurShield'] - $shldD;
            $RDam -= $shldD;
          }
          if ($RDam) {
            $T['CurHealth'] = min(max(0,$T['CurHealth'] - $RDam), $T['OrigHealth']);
          }

          if (($T['CurHealth'] == 0) && ($ThingProps[$T['Type']] & (THING_HAS_SHIPMODULES | THING_HAS_ARMYMODULES)) != 0) {
            $T['BuildState'] = 4;
            TurnLog($T['Whose'],$T['Name'] . " took $RV damage and has been destroyed\n",$T);
            GMLog($T['Name'] . " took $RV damage and has been destroyed\n",$T);
            Empty_Thing($T);
          } else {
            TurnLog($T['Whose'],$T['Name'] . " took $RV damage\n",$T);
          }
          Put_Thing($T);

          // Reports??
        }
        if ($RV && preg_match('/RetreatMe:(\d*)/',$RN,$Mtch)) {
          $Tid = $Mtch[1];
          $T = Get_Thing($Tid);

          $T['Retreat'] = 2;
          TurnLog($T['Whose'],$T['Name'] . " Will retreat from combat\n",$T);
          GMLog($T['Name'] . " will retreat from combat\n",$T);
          Put_Thing($T);
        }

      }


      // Deliberate fall through

    case 'Check':
      if (isset($_REQUEST['S'])) {
        $Sid = $_REQUEST['S'];
        $N = Get_System($Sid);
      } else if (isset($_REQUEST['R'])) {
        $Ref = $_REQUEST['R'];
        $N = Get_SystemR($Ref);
        $Sid = $N['id'];
      } else {
        echo "Should Never Get Here";
        exit;
      }

      $txt = SeeInSystem($Sid,31,1,0,-1,1);

      echo $txt;

      echo "Make sure you have unloaded troops BEFORE looking at the Ground Combat Report.<p>";


      echo "<form method=post action=Meetings.php?ACTION=Check&S=$Sid onkeydown=\"return event.key != 'Enter';\">";

      $_REQUEST['IgnoreShield'] = 0;
      echo "<h2>" . fm_checkbox('Bipass shields - (eg missiles)',$_REQUEST,'IgnoreShield') . "</h2><p>";
      $mtch = [];
      if (preg_match('/<div class=FullD hidden>/',$txt,$mtch)) {
        echo "<button class='floatright FullD' onclick=\"($('.FullD').toggle())\">Show Remains of Things and Named Characters</button>";
      }


//      Register_AutoUpdate('Meetings',$Sid);

      ForceReport($Sid,'G');
      ForceReport($Sid,'S');

      echo fm_submit("ACTION",'Do ALL Damage',0);
      echo "</form>";


//      echo "<p><h2><a href=Meetings.php?S=$Sid&ACTION=FRGROUND>Force Report for Ground Combat</a>, " .
//                  "<a href=Meetings.php?S=$Sid&ACTION=FRSPACE>Force Report for Space Combat</a></h2>";
      break;



    case 'FRGROUND':
      $Sid = $_REQUEST['S'];
      $N = Get_System($Sid);
      ForceReport($Sid,'G');
      break;

    case 'FRSPACE':
      $Sid = $_REQUEST['S'];
      $N = Get_System($Sid);
      ForceReport($Sid,'S');
      break;

    }
  }
  echo "<h1>Checking</h1>";

  echo "<span class=NotHostile>Factions</span> thus marked have only Never Hostile Things.<br>" .
       "The most hostile rating between factions present is displayed.<p>";
//  echo "Checked Things<p>";


  foreach ($Systems as $N) {
    $Sid = $N['id'];
    $NumF = 0;
    $Fs = [];
    foreach($Facts as $Fid=>$F) {
      if (isset($Sys[$Sid][$Fid])) {
        $NumF++;
        $Fs[$Fid] = $F;
      }
    }
    if (isset($Sys[$Sid][0])) {
      $NumF++;
      $Fs[] = [];
    }
    if ($NumF > 1) {
      echo "System: <a href=Meetings.php?ACTION=Check&S=$Sid$TurnP>" . $N['Ref'] . "</a> has ";

      $React = 9;
      foreach ($Fs as $F1=>$FS1) {
        if ($F1 == 0) continue;
        $R1 = $Facts[$F1]['DefaultRelations'];
        if ($R1 == 0) $R1 = 5;
        foreach($Fs as $F2=>$FS2) {
          if ($F1 == $F2) continue;
          $R2 = $Facts[$F2]['DefaultRelations'];
          if ($R2 == 0) $R2 = 5;
          if (!isset($FactFact[$F1][$F2])) {
            $FF = Gen_Get_Cond1('FactionFaction',"FactionId1=$F1 AND FactionId2=$F2");
            if ($FF) {
              $FactFact[$F1][$F2] = (($FF['Relationship']??0)?$FF['Relationship']:5);
            } else {
              $FactFact[$F1][$F2] = $R1;
            }
          }
          if (!isset($FactFact[$F2][$F1])) {
            $FF = Gen_Get_Cond1('FactionFaction',"FactionId1=$F2 AND FactionId2=$F1");
            if ($FF) {
              $FactFact[$F2][$F1] = (($FF['Relationship']??0)?$FF['Relationship']:5);
            } else {
              $FactFact[$F2][$F1] = $R2;
            }
          }
          $React = min($React,$FactFact[$F1][$F2],$FactFact[$F2][$F1]);
        }
      }

      $DR = $Relations[$React];
      echo " <span style='background:" . $DR[1]  . "'>" . $DR[0] . "</span> ";

      foreach($Fs as $F) {
        if (empty($F['Name'])) {
          echo 'Other , ';
        } else {
          $Fid = $F['id'];
          if (isset($Hostiles[$Sid][$Fid]) && $Hostiles[$Sid][$Fid]) {
//if (isset($Hostiles[$Sid][$Fid])) echo $Hostiles[$Sid][$Fid] . ": ";
            echo $F['Name'] . " , ";
          } else {
//echo "NOT HOSTILE ";
            echo "<span class=NotHostile>" . $F['Name'] . "</span> , ";
          }
        }
      }
      echo "<br>";
    }
  }

  echo "<P>Scan Finished<p>";

  if ($TurnP) echo "<h2><a href=TurnActions.php?ACTION=Complete&Stage=Meetups>Back To Turn Processing</a></h2>";

  dotail();

?>
