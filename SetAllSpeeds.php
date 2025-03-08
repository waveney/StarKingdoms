<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("ProjLib.php");
  include_once("HomesLib.php");


  A_Check('GM');

  dostaffhead("Set all Speeds");

  global $db, $GAME;

  echo "<h1>Set All Speeds</h1>";

  $TTypes = Get_ThingTypes();
  $Things = Get_AllThings(0);
  foreach($Things as $T) {
    if ($T['CurHealth'] == $T['OrigHealth'] && ($TTypes[$T['Type']]['Properties'] & THING_CAN_MOVE) && !($TTypes[$T['Type']]['Properties'] & THING_HAS_GADGETS)) {
      RefitRepair($T);
    }
  }

  echo "All done\n";
  dotail();
?>
