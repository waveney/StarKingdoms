<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("PlayerLib.php");
  include_once("SystemLib.php");
  include_once("ProjLib.php");
  include_once("HomesLib.php");
  include_once("TurnTools.php");    
  A_Check('GM');

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



function ForceReport($Sid,$Cat) {
  global $Facts, $Homes, $TTypes, $ModTypes, $N, $Techs, $ThingProps ;
  $Things = Get_Things_Cond(0,"SystemId=$Sid AND ( BuildState=2 OR BuildState=3) ORDER BY Whose");
  $LastF = $Home = $Control = 0;
  $txt = $ftxt = $htxt = $Battct = '';
  $TMsk = ($Cat=='G'?1:2);
  $PlanMoon = [];
  $FirePower = $Wid = $Bat = 0;
  
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
  
  echo "<h2>" . ($Cat =='G' ?'Ground':'Space') . " Force Report for " . $N['Ref'] . ($Cat =='G' ? " - " . $PlanMoon['Name'] . " ($HomeType)" :'') . "</h2>\n";
  if ($Cat =='G' && $Wid) echo "<h2><a href=WorldEdit.php?ACTION=Militia&id=$Wid>Create /Update Militia</a></h2>";
  
  echo "<table border>";
  echo "<tr><td>What<td>Type<td>Level<td>Health<td>Attack<td>Speed<td>Actions\n";
  foreach($Things as $T) {
    if ((($Cat == 'S') && (($TTypes[$T['Type']]['Properties'] & 8) != 0)) || 
        (($Cat == 'G') && (($TTypes[$T['Type']]['Properties'] & 0x800020) != 0))) {
      if (($T['CurHealth'] == 0) && ($T['Type'] == 20)) continue; // Skip Militia at zero
      if ($T['PrisonerOf'] != 0) continue; // Prisoners
      $Tid = $T['id'];
      if ($LastF != $T['Whose']) {
        if ($LastF) {
          echo $htxt;
          if ($Bat) echo "Battle Tactics: Effectively $Bat ( $Battct ) <br>";
          echo  $ftxt. "<br>Total Firepower: $FirePower" . $txt;
        }
        $BD = $Bat = 0;
        $LastF = $T['Whose'];
        $FirePower = 0;
        $htxt = "<tr><td colspan=7 style='background:" . $Facts[$LastF]['MapColour'] . "'><h2>" . $Facts[$LastF]['Name'] . "</h2><tr><td colspan=7>";
        $txt = $Battct = $ftxt = '';
        $txt .= "<br>Damage recieved: <span id=DamTot$Cat$LastF>0</span>";
        
        $FTs = Get_Faction_Techs($LastF);

        foreach ($FTs as $FT) {
          if (empty($FT['Tech_Id'])) continue;

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
            
            case 'Army Tactics - Arctic':
            case 'Army Tactics - Desert':
            case 'Army Tactics - Desolate':
            case 'Army Tactics - Temperate':
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
      $BD = Calc_Damage($T,$Resc);
      $tprops = $ThingProps[$T['Type']];
      
      $txt .= "<td>" . $TTypes[$T['Type']]['Name'] . "<td>" . $T['Level'];
      $txt .= "<td><span id=StartingHealth$Tid hidden>" . $T['CurHealth'] . "</span><span id=CurHealth$Tid>" . $T['CurHealth'] . "</span> / <span id=OrigHealth$Tid>" .
           $T['OrigHealth'] . "</span>" . "<td><span id=Attack$Tid>$BD</span><td>" . 
           (($TTypes[$T['Type']]['Properties'] & THING_CAN_MOVE)? "Speed: " . sprintf("%0.3g ",$T['Speed']) :'') ;
      $txt .=  fm_number1(" Do",$T,'Damage', ''," class=Num3 onchange=Do_Damage($Tid,$LastF,'$Cat')","Damage:$Tid") . " damage"; 
      
      $FirePower += $BD;
    }

  }
  if ($htxt) {
    echo $htxt; 
    if ($Bat) echo "Battle Tactics: Effectively $Bat ( $Battct ) <br>";
    echo  $ftxt. "<br>Total Firepower: <span id=FirePower:$LastF>$FirePower</span>" . $txt;
  }
  echo "</table>";
}

// Planetary Defences + Militia for Ground Combat
  
  $TurnP = '';
  if (isset($_REQUEST['TurnP'])) $TurnP = "&TurnP=1";
  
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
  
//  echo "Checked Homes<p>";
  
  $Things = Get_AllThings();
  foreach ($Things as $T){
    if ($T['BuildState'] <2 || $T['BuildState'] >3 || ($T['LinkId'] < 0 && $T['LinkId'] > -5)) continue; // Don't exist
    $Sid = $T['SystemId'];
    $Eyes = $TTypes[$T['Type']]['Eyes'];
    $Hostile = ($TTypes[$T['Type']]['Properties'] & THING_IS_HOSTILE);
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
  
//  var_dump($_REQUEST);
  
  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
    case 'Do ALL Damage':
      foreach ($_REQUEST as $RN=>$RV) {
        if ($RV && preg_match('/Damage:(\d*)/',$RN,$Mtch)) {
          $Tid = $Mtch[1];
//var_dump($Tid,$RN,$RV);
          $T = Get_Thing($Tid);
          $T['CurHealth'] = max(0,min($T['OrigHealth'],$T['CurHealth'] - $RV));
          if (($ThingProps[$T['Type']] & (THING_HAS_SHIPMODULES | THING_HAS_ARMYMODULES)) != 0) {
            $T['BuildState'] = 4;
            // Anything aboard?
            // Armies die, named characters -> FollowUps
          }
          Put_Thing($T);
          
          // Reports??
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

      if (preg_match('/<div class=FullD hidden>/',$txt,$mtch)) {
        echo "<button class='floatright FullD' onclick=\"($('.FullD').toggle())\">Show Remains of Things and Named Characters</button>";
      }

      echo "<form method=post action=Meetings.php?ACTION=Check&S=$Sid onkeydown=\"return event.key != 'Enter';\">";
//      Register_AutoUpdate('Meetings',$Sid);
      
      ForceReport($Sid,'G');
      ForceReport($Sid,'S');

      echo "<button type=submit name=ACTION value='Do ALL Damage'>Do All Damage</button>";
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

  echo "<span class=NotHostile>Factions</span> thus marked have only Never Hostile Things<p>";
//  echo "Checked Things<p>";
  
  foreach ($Systems as $N) {
    $Sid = $N['id'];
    $NumF = 0;
    $Fs = [];
    foreach($Facts as $Fid=>$F) {
      if (isset($Sys[$Sid][$Fid])) {
        $NumF++;
        $Fs[] = $F;
      }
    }
    if (isset($Sys[$Sid][0])) {
      $NumF++;
      $Fs[] = [];
    }
    if ($NumF > 1) {
      echo "System: <a href=Meetings.php?ACTION=Check&S=$Sid$TurnP>" . $N['Ref'] . "</a> has ";
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
  
  if ($TurnP) echo "<h2><a href=TurnActions.php?ACTION=StageDone&Stage=Meetups&S=35>Back To Turn Processing</a></h2>";
  
  dotail();
  
?>
