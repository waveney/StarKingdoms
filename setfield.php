<?php
  include_once("sk.php");
  include_once("GetPut.php");

//  var_dump($_REQUEST);
  $Table = $_REQUEST['T'];
  $Id = $_REQUEST['I'];
  $Field = $_REQUEST['F'];
  $Value = $_REQUEST['V'];

  $Entry = Gen_Get($Table,$Id);
  $Entry[$Field] = $Value;
  echo Gen_Put($Table,$Entry);
//  var_dump($Entry);
?>
