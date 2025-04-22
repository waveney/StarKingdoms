<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("SystemLib.php");

  A_Check('GM');

  dostaffhead("Edit Moon",["js/dropzone.js","css/dropzone.css" ]);

  global $db, $GAME;

//  var_dump($_REQUEST);
  if (isset($_REQUEST['M'])) {
    $Mid = $_REQUEST['M'];
  } else if (isset($_REQUEST['id'])) {
    $Mid = $_REQUEST['id'];
  } else {

    echo "<h2>No Systems Requested</h2>";
    dotail();
  }

  $M = Get_Moon($Mid);
  $Pid = $M['PlanetId'];
  $P = Get_Planet($Pid);
  $Sid = $P['SystemId'];
  $N = get_System($Sid);
  $Factions = Get_Factions();
  $Dists = Get_DistrictsM($Mid);
  $Mns = Get_Moons($Pid);
  $MC = count($Mns);
  $PTD = Get_PlanetTypes();
  $PTs = Get_PlanetTypeNames();
  $N2Ps = array_flip($PTs);



  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
    case 'Delete Moon' :
      db_delete('Moons',$Mid);

      Show_Planet($P,1,1);
      dotail();
      break;

    case 'Tidy Districts':
      $Ds = Get_DistrictsM($Mid,1);
      foreach ($Ds as $D) {
        if ($D['Number'] == 0) {
          db_delete('Districts',$D['id']);
        }
      };
      echo "<h2>Districts Tidied Up</h2>";
      break;

    default:
      break;
    }
  }


  Show_Moon($M,1);

  echo "<center>" .
       "<form method=post action=SysEdit.php>" . fm_hidden('id', $Sid) .
       "<input type=submit name=NOACTION value='Back to System' class=Button> " .
       "</form>" .
       "<form method=post action=PlanEdit.php>" . fm_hidden('id', $Pid) .
       "<input type=submit name=NOACTION value='Back to Planet' class=Button> " .
       "</form>";

    echo "<form method=post action=MoonEdit.php>" . fm_hidden('id', $Mid) .
         "<input type=submit name=ACTION value='Delete Moon' class=Button> " .
         "</form></center>";

  dotail();
?>
