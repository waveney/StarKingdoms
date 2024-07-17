<?php
// Direct improved....

  include_once("sk.php");
  global $FACTION,$GAMEID,$USER,$Access_Type,$SkipAccessCheck,$FID;
  include_once("GetPut.php");
  include_once("PlayerLib.php");

  if ( !isset($_REQUEST['id']) ) Error_Page("Invalid link"); // No return

  $FID = $_GET['id'];
  $key = $_GET['Key'];


// Hacking prevention
  Sanitise($FID,10,'num');

  $FACTION = $F = Get_Faction($FID);
  Check_Login();

  if (!$F) Error_Page("Faction not known");

  if ($F['AccessKey'] != $key) Error_Page("Sorry - This is not the right key");

  setcookie('SKF',$FID);
  $_COOKIE['SKF'] = $FID;

  $USER['AccessLevel'] = $Access_Type['Player'];

  $Gid = $F['GameId'];
  if ($Gid != $GAMEID) {
    setcookie('SKG',$Gid);
    $_COOKIE['SKG'] = $Gid;
    Get_Game($Gid);
  }

//  Player_Page();
  $SkipAccessCheck = 1;
  include("Player.php");

  dotail();
?>
