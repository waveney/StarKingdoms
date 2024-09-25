<?php
include_once("sk.php");
include_once("GetPut.php");
// include_once("Login.php");
global $FACTION,$USER,$USERID,$GameStatus,$PlayerLevel;

// debug_print_backtrace();
$God = Access('God');

dostaffhead('Games');
global $ErrorMessage;

if (isset($ErrorMessage)) echo "<h2 class=Err>$ErrorMessage</h2>\n";

$AllGames = Gen_Get_All('Games'," ORDER BY id DESC");

if ($God) {
  $AllPlayGames = Gen_Get_All('GamePlayers');
} else {
  $AllPlayGames = Gen_Get_Cond('GamePlayers',"PlayerId=$USERID");
}
echo "<h1>Games</h1>";

echo "<table border>";
$GCount = 0;

foreach ($AllGames as $Gid=>$G) {
  $CodePrefix = $G['CodePrefix'];
  if (!$God) {
    foreach ($AllPlayGames as $P) {
      if ($P['GameId'] == $Gid) {
        echo "<tr><td><a href=$CodePrefix/AccessGame.php?G=$Gid>" . $G['Name'] . '</a> - ' . $PlayerLevel[$P['Type']] .
             " - Game is " . $GameStatus[$G['Status']];
        $GCount++;
      }
    }
  } else {
    echo "<tr><td><a href=$CodePrefix/AccessGame.php?G=$Gid>$Gid - " . $G['Name'] . '</a> ' . " - Game is " . $GameStatus[$G['Status']];;
    $GCount++;
  }
}

if ($GCount ==0 ) echo "<tr><td>No Games found for you";

echo "</table>";
if (Access('God')) echo "<h2><a href=/Staff.php>Top level Staff Pages</a></h2>";
dotail();
