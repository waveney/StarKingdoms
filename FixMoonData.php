<?php

include_once("sk.php");

dostaffhead('Fix Moon Data');

$Moons = Gen_Get_All('Moons');

foreach ($Moons as $M) {
  $P = Get_Planet($M['PlanetId']);
  $M['SystemId'] = $P['SystemId'];
  Put_Moon($M);

  echo "Done Moon " . $M['id'] . "<br>";
}

echo "Moon Data converted";
dotail();

//$SocPs Get_S
