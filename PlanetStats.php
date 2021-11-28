<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("SystemLib.php");
  
  A_Check('GM');

  dostaffhead("Planet Statistics");

  global $db, $GAME;

  $PTD = Get_PlanetTypes();
  $Systems = Get_Systems();
  
  foreach ($Systems as $N) {
    $Sid = $N['id'];
    $Ps = Get_Planets($Sid);
    foreach ($Ps as $P) {
      $pt = $P['Type'];
      if (!isset($PTD[$pt]['Count'])) $PTD[$pt]['Count']=0;
      $PTD[$pt]['Count']++;
      }
    }
  
  echo "<table border=1><tr><td>Type<td>Number<td>Hospitable\n";
  foreach($PTD as $PT) {
      if (!isset($PT['Count'])) $PT['Count']=0;
    echo "<tr><td>" . $PT['Name'] . "<td>" . $PT['Count'] . "<td>" . $PT['Hospitable'];
  }
  echo "</table>";

  dotail();
?>
