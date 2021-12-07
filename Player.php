<?php
  include_once("sk.php");
  global $FACTION,$GAMEID,$USER;
  include_once("GetPut.php");
  include_once("PlayerLib.php");

  A_Check('Player');

  $FACTION['LastAccess'] = time();
  
  Put_Faction($FACTION);
  Player_Page();
  dotail();  
?>
