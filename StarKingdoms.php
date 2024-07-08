<?php
include_once("sk.php");
include_once("GetPut.php");
// include_once("Login.php");
global $FACTION,$USER,$USERID,$GameStatus,$PlayerLevel;

// debug_print_backtrace();
$God = Access('God');

dostaffhead('Games');

if (empty($USERID)) Error_page("You need to be logged in...");

$AllGames = Gen_Get_All('Games'," ORDER BY id DESC");

if ($God) {
  $AllPlayGames = Gen_Get_All('GamePlayers');
} else {
  $AllPlayGames = Gen_Get_Cond('GamePlayers',"PlayerId=$USERID");
}
echo "<h1>Games</h1>";

echo "<table border>";

foreach ($AllGames as $Gid=>$G) {

  $CodePrefix = $G['CodePrefix'];
  if (!$God) {
    $GCount = 0;
    foreach ($AllPlayGames as $P) {
      if ($P['GameId'] == $Gid) {
        echo "<tr><td><a href=$CodePrefix/AccessGame.php?G=$Gid>" . $G['Name'] . '</a> - ' . $PlayerLevel[$P['Type']] .
             " - Game is " . $GameStatus[$G['Status']];
        $GCount++;
        continue;
      }
    }
    if ($GCount ==0 ) echo "<tr><td>No Games found for you";
  } else {
    echo "<tr><td><a href=$CodePrefix/AccessGame.php?G=$Gid>$Gid - " . $G['Name'] . '</a> ' . " - Game is " . $GameStatus[$G['Status']];;
  }
}

echo "</table>";
if (Access('God')) echo "<h2><a href=/Staff.php>Top level Staff Pages</a></h2>";
dotail();
