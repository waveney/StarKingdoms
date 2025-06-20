<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("PlayerLib.php");
  include_once("SystemLib.php");
  include_once("ProjLib.php");
  include_once("HomesLib.php");


//  function FixFudge() { // Tempcode called to fix thi9ngs
  global $db,$GAMEID,$GAME;

  dostaffhead("Fred");

  echo "<h1>Fudges...</h1>";

  // Save What can I see data
  $Factions = Get_Factions();
  if (!file_exists("Turns/$GAMEID/" . $GAME['Turn'])) mkdir(("Turns/$GAMEID/" . $GAME['Turn']));
  foreach($Factions as $F) {
    $Fid = $F['id'];
    $CouldC = WhatCanBeSeenBy($Fid,1);
    $CB = fopen("Turns/$GAMEID/" . $GAME['Turn'] . "/CouldC$Fid.html", "w");
    fwrite($CB,$CouldC);
    fclose($CB);
    echo "Saved What could be seen for " . $F['Name'] ." on turn " . ($GAME['Turn']) . "<br>\n";
  }

  dotail();
?>


