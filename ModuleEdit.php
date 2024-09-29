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
    $M = Get_ModuleType($MTid);
  } else if (isset($_REQUEST['NEW'])) {
    $M = [];
  } else {
    echo "<h2>No Module Requested</h2>";
    dotail();
  }

  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
    case 'CREATE' :



    default:
      break;
    }
  }

  echo "<h1>Edit the description for " . $M['Name'] . "</h1>";

  echo "<form method=post><table border>";
  Register_AutoUpdate('ModuleTypes', $M['id']);
  echo "<tr>" . fm_textarea('Description',$M,'Description',5,5);
  echo "</table><p>";

  echo "<h2><a href=ModuleList.php>Back to List of Modules</a></h2>";

  dotail();
?>
