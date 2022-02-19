<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ProjLib.php");

  A_Check('GM');

  dostaffhead("List Projects");

  global $db, $GAME;
  global $Project_Status;

  $Projects = Get_Projects_Cond("id<1000000");
  $Factions = Get_Factions();
  
  if (!$Projects) {
    echo "<h2>No Projects found</h2>";
    dotail();
  }
  
  // TODO allow selecting by status and age and who
  
  echo "<h1>Projects</h1>";
  
  $coln = 0;
  echo "<div class=tablecont><table id=indextable border width=100% style='min-width:1400px'>\n";
  echo "<thead><tr>";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Id</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Name</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Who</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Status</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Start Turn</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Cost</a>\n";  
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Progress</a>\n";  
  echo "</thead><tbody>";

  foreach($Projects as $P) {
    $pid = $P['id'];
    echo "<tr><td>$pid";
    echo "<td><a href=ProjEdit.php?id=$pid>" . $P['Name'] . "</a>";
    echo "<td>" . $Factions[$P['FactionId']]['Name'];
    echo "<td>" . $Project_Status[$P['Status']];
    echo "<td>" . $P['TurnStart'];
    echo "<td>" . $P['Costs'];
    echo "<td>" . $P['Progress'] . "/" . $P['ProgNeeded'];
  }
      
  echo "</tbody></table></div>\n";


  dotail();
?>
