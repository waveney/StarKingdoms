<?php
  include_once("sk.php");
  global $FACTION,$GAMEID,$USER;
  include_once("GetPut.php");
  include_once("PlayerLib.php");

  // OLD CODE NO LONGER USED
  /*
  A_Check('GM');
// START HERE
// var_dump($_REQUEST);


// Check for double ended links from FS2, while at FS1 ommiting Lid
function Check_System($Fid,&$FS1,&$FS2,$Lid) {
return;
  $Ls = Get_Links($FS2['Ref']);
  foreach ($Ls as $L) {
    if ($L['id'] == $Lid) continue;
    $FL = Get_FactionLinkFL($Fid,$Lid);
    if ($FL['Known']) continue;
//    $FarSys = Get_System($
  }
}

// START HERE


  if (isset($_REQUEST['f'])) {
    $Fid = $_REQUEST['f'];
  } else if (isset($_REQUEST['F'])) {
    $Fid = $_REQUEST['F'];
  } else if (isset($_REQUEST['id'])) {
    $Fid = $_REQUEST['id'];
  } else {
    echo "<h2>No Faction Requested</h2>";
    dotail();
  }

  $F = Get_Faction($Fid);

  dostaffhead('Movement Fudge for early turns');

  echo "<h1>Movement Fudge for early turns</h1>";
  echo "Faction: " . $F['Name'] . "<p>";


  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
    case 'Move' :
      $Lid = $_REQUEST['link'];

      $L = Get_Link($Lid);

      $SR1 = Get_SystemR($L['System1Ref']);
      $SR2 = Get_SystemR($L['System2Ref']);

      $FL = Get_FactionLinkFL($Fid,$Lid);
      $FL['Known'] = 1;
      Put_FactionLink($FL);

      $FS1 = Get_FactionSystemFS($Fid,$SR1['id']);


      if (isset($FS1['ScanLevel'])) { // OLD CODE BEWARE
        echo "Already seen system " . $L['System1Ref'] . " at level " . $FS1['ScanLevel'];
      } else {
        echo "System " . $L['System1Ref'] . " is new give a survey report";
      }
      echo "<p>";

      $FS2 = Get_FactionSystemFS($Fid,$SR2['id']);
      if (isset($FS2['ScanLevel'])) {
        echo "Already seen system " . $L['System2Ref'] . " at level " . $FS2['ScanLevel'];
      } else {
        echo "System " . $L['System2Ref'] . " is new give a survey report";
      }
      Check_System($Fid,$FS1,$FS2,$Lid);
      Check_System($Fid,$FS2,$FS1,$Lid);
      echo "<p>";

      echo "Done";
      break;

    default:
      break;
    }
  }

  echo "<form method=post action=PlayerMove.php?ACTION=Move>" . fm_hidden('F',$Fid);
  echo "<br>Move along link #<input type=number name=link onchange=this.form.submit()>";
  echo "</form><p>";


  dotail();

?>*/
