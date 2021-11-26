<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("SystemLib.php");
  
  A_Check('GM');

  dostaffhead("Edit System");

  global $db, $GAME;

  function Auto_Populate($N) {
  
  
  
  }

// START HERE
//  var_dump($_REQUEST);
  if (isset($_REQUEST['N'])) {
    $Sysid = $_REQUEST['N'];
  } else if (isset($_REQUEST['id'])) {
    $Sysid = $_REQUEST['id'];
  } else { 

    echo "<h2>No Systems Requested</h2>";
    dotail();
  }

  $N = Get_System($Sysid);
  $Factions = Get_Factions();
  
  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
    case 'Add Planet' :
      $plan = ['SystemId'=>$Sysid];
      Put_Planet($plan);
      break;
      
    case 'Auto Populate' :
      Auto_Populate($N);
      break;
    default: 
      break;
    }
  }
  
  
  Show_System($N,1);
  
  dotail();
?>
