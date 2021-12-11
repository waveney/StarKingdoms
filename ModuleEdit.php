<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  
  A_Check('GM');

  dostaffhead("Edit Module Type",["js/dropzone.js","css/dropzone.css" ]);

  global $db, $GAME;
  
// START HERE
//  var_dump($_REQUEST);
  if (isset($_REQUEST['id'])) {
    $MTid = $_REQUEST['id'];
    $MT = Get_ModuleType($MTid);
  } else if (isset($_REQUEST['NEW'])) { 
    $MT = [];
  } else
    echo "<h2>No Systems Requested</h2>";
    dotail();
  }
  
  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
    case 'CREATE' :
      
    
    
    default: 
      break;
    }
  }
  
  
  Show_System($N,1);
  
  if (isset($_REQUEST['NEW'])) echo "<h2><a href=ModuleEdit.php?ACTION=CREATE>Create</a></h2>";
  
  dotail();
?>
