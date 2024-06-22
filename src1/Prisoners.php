<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("ThingShow.php");
 
 
   dostaffhead("Prisoner Actions");

  global $db, $GAME, $GAMEID,$BuildState;
  
// START HERE
//  var_dump($_REQUEST);

  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
      case 'RECALC':
        Recalc_Prisoner_Counts();
        dotail();
    }
  }
  
  echo "Should never get here...<p>";
  dotail();
?>
