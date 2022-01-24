<?php
  include_once("sk.php");
  global $FACTION,$GAMEID,$USER;
  include_once("GetPut.php");
  include_once("PlayerLib.php");
  include_once("ThingLib.php");
  
global $ModuleCats,$ModFormulaes,$ModValues,$Fields,$ShipTypes,$Tech_Cats,$CivMil,$BuildState;


  if (Access('GM')) {
    if (isset($_REQUEST['id'])) {
      $Fid = $_REQUEST['id'];
    } else if (isset($_REQUEST['F'])) {
      $Fid = $_REQUEST['F'];
    } else if (isset($_REQUEST['f'])) {
      $Fid = $_REQUEST['f'];
    } else {
      $Fid = 0;
    }
    
    $Faction = Get_Faction($Fid);
  } else if  (Access('Player')) {
    A_Check('Player');
    $Fid = $FACTION['id'];
    $Faction = &$FACTION;
  } else {
    dotail();
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
//  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>SubType</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Level</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Orders</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>State</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Where</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Moving to</a>\n";

  echo "</thead><tbody>";
//  echo "<tr><td>Name<td>Class<td>Type<td>Subtype<td>Level<td>Orders(s)<td>State<td>Where<td>Moving to\n";
  
  foreach ($Things as $T) {
    $Tid = $T['id'];
    $Name = $ThingTypes[$T['Type']]['Name'];
    if (!$Name) $Name = "Unknown Thing $Tid";
    
    echo "<tr><td><a href=ThingEdit.php?id=$Tid&FORCE>" . $T['Name'] . "</a>";
    echo "<td>" . $T['Class'];
    echo "<td>" . $Name;
//    echo "<td>" . $ShipTypes[$T['SubType']];
    echo "<td>" . $T['Level'];
    echo "<td>" . $T['Orders'];
    echo "<td>" . $BuildState[$T['BuildState']];
    echo "<td>" . $Systems[$T['SystemId']];
    echo "<td>" . (isset($Systems[$T['NewSystemId']]) ? $Systems[$T['NewSystemId']] :"") ;
  }
  
  echo "</table></div>\n";
  echo "<h1>Logistics</h1>";
  
  /* Logistics Info */
  echo "To follow<p>";

  echo "<h2><a href=ThingPlan.php?F=$Fid>Plan a new thing</a></h2>\n";

  dotail();  
?>
