<?php
include_once("sk.php");
include_once("GetPut.php");
// include_once("Login.php");
global $FACTION,$USER,$USERID,$GameStatus,$PlayerLevel,$FID,$GAME,$GAMEID;

$God = Access('God');
$Gid = ($_REQUEST['G'] ?? 0);

$Game = Gen_Get('Games',$Gid);

if ($God) {
  setcookie('SKG',$Gid);
  $GAME=$Game;
  $GAMEID = $Gid;
  include_once("Staff.php");
}

$Person = Gen_Get_Cond1('GamePlayers', "GameId=$Gid AND PlayerId=$USERID");

if (!$Person) {
  Error_Page("You are not recorded as part of that game");
}
$GAME=$Game;
$GAME['FactionLevel'] = $Person['Type'];
$GAMEID = $Gid;

setcookie('SKG',$Gid);
$_COOKIE['SKG'] = $Gid;
if ($Person['Type'] == 0) {
  $User = $USER['Login'];
  $FACTION = Gen_Get_Cond1('Factions',"GameId=$Gid AND Player='$User'");
  if (!$FACTION && $Game['Status']) {
    Error_Page("You don't have a faction in that game");
  }
  $FID = $FACTION['id'];
  setcookie('SKF',$FID);
  include_once("Player.php");
} else {
  include_once("Staff.php");
}
