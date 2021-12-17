<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  
  A_Check('GM');

  dostaffhead("List Things");

  global $db, $GAME,$BuildState;

  $Systems = Get_SystemRefs();
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
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Project</a>\n";
//  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Location</a>\n";

  echo "</thead><tbody>";

  foreach($Things as $T) {
    $tid = $T['id'];
    $Name = $T['Name'];
    $who = $T['Whose'];
    $Ref = $Systems[$T['SystemId']];
    if ($T['WithinSysLoc'] == 1) {
      $Loc = "Deep Space";
    } else {
      $N = Get_System($T['SystemId']);
      $Locs = Within_Sys_Locs($N);
      $Loc = $Locs[$T['WithinSysLoc']];
    }
    
    echo "<tr><td><a href=ThingEdit.php?id=$tid>$tid</a>";
    echo "<td>" . $ThingTypes[$T['Type']] . "<td>" . $T['SubType'] . "<td>" . $T['Level'];
    echo "<td><a href=ThingsAt.php?id=$Ref>$Ref</a><td>$Loc";
    echo "<td><a href=ThingEdit.php?id=$tid>$Name</a>";
    echo "<td>" . ($who? $Factions[$T['Whose']]['Name'] : "");
    echo "<td>" . $T['ProjectId'];

  }
      
  echo "</tbody></table></div>\n";
  
  echo "<h2><a href=ThingEdit.php?ACTION=NEW>New Thing</a></h2>";

  dotail();
?>
