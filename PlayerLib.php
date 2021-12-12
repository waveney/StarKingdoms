<?php

// Lib of player stuff
include_once("sk.php");
include_once("GetPut.php");

function Player_Page() {
  global $FACTION;
  dostaffhead("Player Actions");
  
  echo "<h1>Player Actions</h1>\n";
  echo "<div class=Player>";
  echo "The only current actions are:";
  echo "<ul>";
  echo "<li><a href=MapFull.php?PLAYER>Faction Map</a>\n";
  echo "<li><a href=TechShow.php?PLAYER>Technologies</a>\n";  
  echo "</ul>";
  echo "</div>";
  
  dotail();

}

function TraitQ($Name) {
  global $FACTION;
  
  if (!isset($FACTION)) return false;
  if ($FACTION['Trait1'] == $Name || $FACTION['Trait2'] == $Name || $FACTION['Trait2'] == $Name) return true;
  return false;
}

function Ships() {
  global $FACTION;
  
}

?>
