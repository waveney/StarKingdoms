<?php
  include_once("sk.php");
  include_once("GetPut.php");

  A_Check('GM');

  dostaffhead("List Factions");

  global $db, $GAME;

  $Factions = Get_Factions();
  
  if (!$Factions) {
    echo "<h2>No Factions found</h2>";
    dotail();
  }
  
  echo "<h1>Factions</h1>";
  
  $coln = 0;
  echo "<div class=tablecont><table id=indextable border width=100% style='min-width:1400px'>\n";
  echo "<thead><tr>";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'n')>id</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Faction</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Who</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Map Colour</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Last Active</a>\n";
  echo "</thead><tbody>";

  foreach($Factions as $F) {
    $Fid = $F['id'];
    echo "<tr><td><a href=FactionEdit.php?F=$Fid>$Fid</a>";
    echo "<td><a href=FactionEdit.php?F=$Fid>" . $F['Name'] . "</a>";
    echo "<td>" . $F['Player'];
    echo "<td style='background:" . $F['MapColour'] . ";'>";
    echo "<td>" . ($F['LastActive']? date('d/m/y H:i:s',$F['LastAccess']) :"Never");
  }
      
  echo "</tbody></table></div>\n";


  dotail();
?>
