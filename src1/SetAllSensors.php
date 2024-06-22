<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("ProjLib.php");
  include_once("HomesLib.php");
 
  
  A_Check('GM');

  dostaffhead("Set all Speeds");

  global $db, $GAME,$BuildState;

  echo "<h1>Set All Scanners</h1>";
  
  $TTypes = Get_ThingTypes();
  $Things = Get_AllThings(0);
  foreach($Things as $T) {
    if ($T['Type'] < 4 || $T['Type'] == 9 || $T['Type'] == 12)  {
      Calc_Scanners($T);
      Put_Thing($T);
    }
  }
  
  echo "All done\n";
  dotail();
?>
