<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  
  A_Check('GM');

  dostaffhead("List Things");

  global $db, $GAME,$BuildState;

  $Systems = Get_Systems();
  $Factions = Get_Factions();
  $Things = Get_AllThings();
  $ThingTypes = Thing_Type_Names();
  
  if (!$Systems) {
    echo "<h2>No Things found</h2>";
    dotail();
  }
  
  echo "<h1>Things</h1>";
  
  $coln = 0;
  echo "<div class=tablecont><table id=indextable border width=100% style='min-width:1400px'>\n";
  echo "<thead><tr>";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Id</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Type</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>SubType</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Level</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>System</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Location</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Name</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Whose</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>BuildState</a>\n";
//  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Location</a>\n";

  echo "</thead><tbody>";

  foreach($Things as $T) {
    $tid = $T['id'];
    $Name = $T['Name'];
    $who = $N['Whose'];
    echo "<tr><td><a href=ThingEdit.php?id=$tid>$tid</a>";
    echo "<td>" . $ThingTypes[$t['Type']] . "<td>" . $t['SubType'] . "<td>" . $t['Level'];
    echo "<td>" . $Systems[$t['SystemId']]['Ref'] .  "<td>" . $t['Location'];
    echo "<td><a href=ThingEdit.php?id=$tid>$Name</a>";
    echo "<td>" . ($who? $Factions[$t['Whose']]['Name'] : "");
    echo "<td>" . $BuildState[$t['BuildState']];

  }
      
  echo "</tbody></table></div>\n";
  
  echo "<h2><a href=ThingEdit.php?NEW>New Thing</a></h2>";

  dotail();
?>
