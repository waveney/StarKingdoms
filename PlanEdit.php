<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("SystemLib.php");
  
  A_Check('GM');

  dostaffhead("Edit Planet",["js/dropzone.js","css/dropzone.css" ]);

  global $db, $GAME;

//  var_dump($_REQUEST);
  if (isset($_REQUEST['P'])) {
    $Pid = $_REQUEST['P'];
  } else if (isset($_REQUEST['id'])) {
    $Pid = $_REQUEST['id'];
  } else { 

    echo "<h2>No Systems Requested</h2>";
    dotail();
  }

  $P = Get_Planet($Pid);
  $N = get_System($P['SystemId']);
  $Factions = Get_Factions();
  $Dists = Get_Districts($Pid);
  
  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
    case 'Delete Planet' :
      db_delete('Planets',$Pid);

      Show_System($N,1);
      dotail();
      break;
      
    case 'Auto Populate' :
    
      break;
    default: 
      break;
    }
  }
  
  
  Show_Planet($P,1);
  

  echo "<center>" .
       "<form method=post action=SysEdit.php>" . fm_hidden('id', $P['SystemId']) .
       "<input type=submit name=NOACTION value='Back to System' class=Button> " .
       "</form></center>";
       
  if (Access('God')) {
    echo "<center>" .
         "<form method=post action=PlanEdit.php>" . fm_hidden('id', $Pid) .
         "<input type=submit name=ACTION value='Delete Planet' class=Button> " .
         "<input type=submit name=ACTION value='Auto Populate' class=Button></form></center>";
  }
  dotail();
?>
