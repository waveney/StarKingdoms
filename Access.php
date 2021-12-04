<?php
// Direct improved....

  include_once("sk.php");
  global $FACTION,$GAMEID,$USER;
  include_once("GetPut.php");
  include_once("PlayerLib.php");

  if ( !isset($_REQUEST['id']) ) Error_Page("Invalid link"); // No return

  $Fid = $_GET['id'];
  $key = $_GET['Key'];


// Hacking prevention
  if (strlen($Fid)>6 || strlen($key)!=40 || preg_match('/[^A-Z]/',$key) || !is_numeric($Fid) ) Error_Page("Invalid Access link");
//var_dump($_REQUEST);

  $FACTION = $F = Get_Faction($Fid);
  
  if (!$F) Error_Page("Faction not known");
  
  if ($F['AccessKey'] != $key) Error_Page("Sorry - This is not the right key");

  $Cake = sprintf("%s:%d:%06d",'Player',$Access_Type['Player'],$Fid ); 
  $biscuit = openssl_encrypt($Cake,'aes-128-ctr','Quarterjack',0,'BrianMBispHarris');
  setcookie('SKD',$biscuit,0,'/');

  $USER{'AccessLevel'} = $Access_Type['Player'];
  $USER{'UserId'} =  $Fid;


  Player_Page();

  dotail();
?>
