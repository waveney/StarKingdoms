<?php
include_once("sk.php");
include_once("GetPut.php");
include_once("SystemLib.php");
global $GAMEID;

A_Check('GM');

dostaffhead("Fix Minerals",["js/dropzone.js","css/dropzone.css" ]);

$Plans = Gen_Get_All('Planets'," WHERE GameId=$GAMEID");

$PTypes = Get_PlanetTypes();

foreach($Plans as $pi=>$P) {
  if ($P['Control']) {
    $P['Minerals'] = 3;
  } else if ($PTypes[$P['Type']]['Name'] == 'Asteroid Belt') {
    $P['Minerals'] = Minerals4AsteroidBelt();
  } else {
    $P['Minerals'] = Minerals4Planet();
  }

  Put_Planet($P);
}

echo "All done";
dotail();