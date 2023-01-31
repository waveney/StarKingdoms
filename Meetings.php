<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("PlayerLib.php");
  include_once("SystemLib.php");
  include_once("ProjLib.php");
  include_once("HomesLib.php");
    
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

function ForceReport($Sid,$Cat) {
  global $Facts, $Homes, $TTypes, $ModTypes, $N, $Techs ;
  $Things = Get_Things_Cond(0,"SystemId=$Sid ORDER BY Whose");
  $LastF = $Home = $Control = 0;
  $txt = $ftxt = '';
  $TMsk = ($Cat=='G'?1:2);
  $PlanMoon = [];
  $Wid = 0;
  
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
        break; // CHANGE For Multi targets
      }
    }
  }
  
  echo "<h2>" . ($Cat =='G' ?'Ground':'Space') . " Force Report for " . $N['Ref'] . ($Cat =='G' ? " - " . $PlanMoon['Name'] . " ($HomeType)" :'') . "</h2>\n";
  if ($Cat =='G' && $Wid) echo "<h2><a href=WorldEdit.php?ACTION=Militia&id=$Wid>Create /Update Militia</a></h2>";
  
  echo "<table border>";
  echo "<tr><td>What<td>Type<td>Level<td>Health<td>Attack<td>Speed\n";
  foreach($Things as $T) {
    if ((($Cat == 'S') && (($TTypes[$T['Type']]['Properties'] & 8) != 0)) || 
        (($Cat == 'G') && (($TTypes[$T['Type']]['Properties'] & 0x800020) != 0))) {

      if ($LastF != $T['Whose']) {
        if ($LastF) {
          echo $ftxt . "<br>Total Firepower: $FirePower" . $txt;
        }
        $BD = 0;
        $LastF = $T['Whose'];
        $FirePower = 0;
        $ftxt = "<tr><td colspan=6 style='background:" . $Facts[$LastF]['MapColour'] . "'><h2>" . $Facts[$LastF]['Name'] . "</h2><tr><td colspan=6>";
        $txt = '';
        
        $FTs = Get_Faction_Techs($LastF);

        foreach ($FTs as $FT) {
          if (empty($FT['Tech_Id'])) continue;
          $Tech = $Techs[$FT['Tech_Id']];
          if ($Tech['Properties'] & $TMsk) $ftxt .= $Tech['Name'] . (($Tech['Cat'] == 0)? ( " Level: " . $FT['Level']) : '') . "<br>\n";
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
      $txt .= "<td>" . $TTypes[$T['Type']]['Name'] . "<td>" . $T['Level'];
      $txt .= "<td>" . $T['CurHealth'] . " / " . $T['OrigHealth'] . "<td>$BD<td>" . 
           (($TTypes[$T['Type']]['Properties'] & THING_CAN_MOVE)? "Speed: " . sprintf("%0.3g ",$T['Speed']) :'') ;
      $FirePower += $BD;
    }

  }
  if ($ftxt) echo $ftxt . "<br>Total Firepower: $FirePower" . $txt;
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
    case 'Check':
      $Sid = $_REQUEST['S'];
      $N = Get_System($Sid);
      $txt = SeeInSystem($Sid,31,1,0,-1,1);
      
      echo $txt;

      echo "Make sure you have unloaded troops BEFORE looking at the Ground Combat Report.<p>";
      ForceReport($Sid,'G');
      ForceReport($Sid,'S');
                  
      if (preg_match('/<div class=FullD hidden>/',$txt,$mtch)) {
        echo "<button class='floatright FullD' onclick=\"($('.FullD').toggle())\">Show Remains of Things</button>";
      }
      

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
