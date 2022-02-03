<?php
  include_once("sk.php");
  global $FACTION,$GAMEID,$USER;
  include_once("GetPut.php");
  include_once("PlayerLib.php");
  include_once("ThingLib.php");
  
global $ModuleCats,$ModFormulaes,$ModValues,$Fields,$ShipTypes,$Tech_Cats,$CivMil,$BuildState;

//var_dump($_COOKIE,$_REQUEST);
  if (Access('Player')) {
    if (!$FACTION) {
      if (!Access('GM') ) Error_Page("Sorry you need to be a GM or a Player to access this");
    } else {
      $Fid = $FACTION['id'];
      $Faction = &$FACTION;
    }
  } 
  if (Access('GM') ) {
    A_Check('GM');
    if (isset( $_REQUEST['F'])) {
      $Fid = $_REQUEST['F'];
    } else if (isset( $_REQUEST['f'])) {
      $Fid = $_REQUEST['f'];
    } else if (isset( $_REQUEST['id'])) {
      $Fid = $_REQUEST['id'];
    }
    if (isset($Fid)) $Faction = Get_Faction($Fid);
  }

  dostaffhead("Things",["js/ProjectTools.js"]);
  
/* Select types 
  Name, Class, What, sub cat, where, move, Level, Action
  */
  
  $Things = Get_Things($Fid);
  $ThingTypes = Get_ThingTypes();
  $Systems = Get_SystemRefs();

  echo "<h1>Things</h1>";
  echo "To see more information about each thing and to do movement and changes click on the name<p>\n";
  echo "Click on column heading to sort by column - toggles up/down<br>\n";
  echo "Buttons to selectively list only one type will follow at some point<br>\n";
  
  $coln = 0;
  echo "<div class=tablecont><table id=indextable border width=100% style='min-width:1400px'>\n";
  echo "<thead><tr>";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Name</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Class</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Type</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Level</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Orders</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>State</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Where</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Moving to</a>\n";

  echo "</thead><tbody>";
  
  $Logistics = [0,0,0]; // Ship, Army, Intelligence
  
  foreach ($Things as $T) {
    if (empty($T['Type'])) continue;
    $Tid = $T['id'];
    $Name = $ThingTypes[$T['Type']]['Name'];
    if (!$Name) $Name = "Unknown Thing $Tid";
    
    echo "<tr><td><a href=" . ($T['BuildState']? "ThingEdit.php" : "ThingPlan.php") . "?id=$Tid&FORCE>" . $T['Name'] . "</a>";
    echo "<td>" . $T['Class'];
    echo "<td>" . $Name;
    echo "<td>" . $T['Level'];
    echo "<td>" . $T['Orders'];
    echo "<td>" . $BuildState[$T['BuildState']];
    echo "<td>" . (empty($T['SystemId']) ?'': $Systems[$T['SystemId']]);
    echo "<td>" . (isset($Systems[$T['NewSystemId']]) ? $Systems[$T['NewSystemId']] :"") ;
    
    if ($T['BuildState'] == 2 || $T['BuildState'] == 3) {
      if ($ThingTypes[$T['Type']]['Properties'] & THING_HAS_ARMYMODULES) $Logistics[1] += $T['Level'];
      if ($ThingTypes[$T['Type']]['Properties'] & THING_HAS_GADGETS) $Logistics[2] += $T['Level'];
      if ($ThingTypes[$T['Type']]['Properties'] & ( THING_HAS_MILSHIPMODS | THING_HAS_CIVSHIPMODS)) $Logistics[0] += $T['Level'];
    };
    
  }
  
  echo "</table></div>\n";
  echo "<h1>Logistics</h1>";
  
  $LogAvail = LogsticalSupport($Fid);
  
  $LogCats = ['Ships','Armies','Agents'];
  
  echo "<table border>";
  echo "<tr><td>Category<td>Logistical Support<td>Logistics needed<td>Logistics Penalty\n";
  foreach ($LogCats as $i => $n) {
    if ($Logistics[$i]) {
      $pen = min(0,$LogAvail[0]-$Logistics[0]);
      echo "<tr><td>$n<td>" . $LogAvail[$i] . "<td>" . $Logistics[$i] . "<td " . ($pen < 0? " class=Err":'') . ">$pen" ;
    }
  }
  echo "</table><p>\n";

  echo "<h2><a href=ThingPlan.php?F=$Fid>Plan a new thing</a></h2>\n";

  dotail();  
?>
