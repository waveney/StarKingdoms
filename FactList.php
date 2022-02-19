<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("PlayerLib.php");

  A_Check('GM');

  dostaffhead("List Factions");

  global $db, $GAME;
  global $PlayerState,$PlayerStates,$PlayerStateColours;
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
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Use</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Faction</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Who</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Map Colour</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Last Active</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>State</a>\n";

  echo "</thead><tbody>";

  foreach($Factions as $F) {
    $Fid = $F['id'];
    echo "<tr><td><a href=FactionEdit.php?F=$Fid>$Fid</a>";
    echo "<td><a href=Access.php?id=$Fid&Key=" . $F['AccessKey'] . " ><b>Use</b></a>";
    echo "<td><a href=FactionEdit.php?F=$Fid>" . $F['Name'] . "</a>";
    echo "<td>" . $F['Player'];
    echo "<td style='background:" . $F['MapColour'] . ";'>";
    echo "<td>" . (isset($F['LastActive']) && $F['LastActive']? date('d/m/y H:i:s',$F['LastActive']) :"Never");
    echo "<td <span style='background:" . $PlayerStateColours[$F['TurnState']] . "'>"  . $PlayerState[$F['TurnState']];
  }
      
  echo "</tbody></table></div>\n";


  dotail();
?>
