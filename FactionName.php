<?php
  include_once("sk.php");
  global $FACTION,$GAMEID,$USER;
  include_once("GetPut.php");
  include_once("PlayerLib.php");

  A_Check('GM');
// START HERE
// var_dump($_REQUEST);



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

  dostaffhead('Naming Fudge for early turns');
  
  echo "<h1>Naming Fudge for early turns</h1>";
  echo "Faction: " . $F['Name'] . "<p>";

  $Force = 0;
  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
    case 'Name' :
      echo "<p>";
      $Name = $_REQUEST['Name'];
      if (isset($_REQUEST['sys']) && strlen($_REQUEST['sys']) > 0 ) {
        $sys = $_REQUEST['sys'];
        if (is_numeric($sys)) {
          $FS = Get_FactionSystemFS($Fid,$sys);
          if (isset($FS['id']) || isset($_REQUEST['FORCE'])) {
            $FS['Name'] = $Name;
            Put_FactionSystem($FS);
          } else {
            echo "<h2>They have never been there - repeat to force<h2>";
            $Force = 1;
          }
        } else {
          $N = Get_SystemR($sys);
          if (!$N) {
            echo "System $sys not known<p>";
          } else {
            $FS = Get_FactionSystemFS($Fid,$N['id']);
            if (isset($FS['id']) || isset($_REQUEST['FORCE'])) {
              $FS['Name'] = $Name;
              Put_FactionSystem($FS);
            } else {
              echo "<h2 class=Err>They have never been there - repeat to force<h2>";
              $Force = 1;
            }
          }
        }
      } else if (isset($_REQUEST['pid']) && $_REQUEST['pid']) {
        $FP = Get_FactionPlanetFS($Fid, $_REQUEST['pid']);
        $FP['Name'] = $Name;
        Put_FactionPlanet($FP);
      } else if (isset($_REQUEST['mid']) && $_REQUEST['mid']) {
        $FP = Get_FactionMoonFS($Fid, $_REQUEST['mid']);
        $FP['Name'] = $Name;
        Put_FactionMoon($FP);
      }
      break;
           
    default: 
      break;
    }
  }
  
  $dat = ($Force? $_REQUEST : []);
  echo "<form method=post action=FactionName.php?ACTION=Name>" . fm_hidden('F',$Fid);
  
  echo "Give System id/Ref, Planet id or Moon id to name THEN the name<br>";
  
  echo "<table><tr>" . fm_text('System Id or Ref',$dat,'sys');
  if ($Force) echo fm_hidden('FORCE',1);
  echo "<tr>" . fm_number('Planet Id',$dat,'pid');
  echo "<tr>" . fm_number('Moon Id',$dat,'mid');
  $val = (empty($dat['Name'])? '' : "value='" . $dat['Name'] . "'" );
  echo "<tr><td>Name:<td><input type=text name=Name $val onchange=this.form.submit()>";

  echo "</form></table><p>";
  
  if ($Force) echo "<input type=submit value=SET>";
    
  dotail();

?>
