<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  
  A_Check('GM');

  dostaffhead("Edit and Create Things");

  global $db, $GAME,$BuildState;
  
// START HERE
//  var_dump($_REQUEST);
  if (isset($_REQUEST['id'])) {
    $tid = $_REQUEST['id'];
    $t = Get_Thing($tid);
  } else if (isset($_REQUEST['NEW'])) {
    New_Thing();
    ????
  } else {{ 
    echo "<h2>No Thing Requested</h2>";
    dotail();
  }

  if (!isset($N)) $N = Get_System($Sysid);
  $Factions = Get_Factions();
  
  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
     case 'Redo Moons' :
      break;
            
    default: 
      break;
    }
  }
  
  Show_Thing($t);
  
  
  
  dotail();
  
?>
  
  
  
  
  
  
  
  
  
  
  
