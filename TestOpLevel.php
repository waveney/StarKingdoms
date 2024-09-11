<?php
include_once("sk.php");
include_once("GetPut.php");
include_once("ThingLib.php");
include_once("SystemLib.php");
include_once("OrgLib.php");
global $FACTION,$GAMEID;

dostaffhead('Testing 1 2 3');

$Tests = [[35,80,0],[35,81,0],[35,83,0]];

foreach ($Tests as $i=>$T) {
  $Res = Op_Level($T[0],$T[1],$T[2]);
  echo "<p>Result $i: $Res<p>";

}


dotail();
