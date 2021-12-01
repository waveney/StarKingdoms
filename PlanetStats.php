<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("SystemLib.php");
  
  A_Check('GM');

  dostaffhead("Planet Statistics");

  global $db, $GAME;

  $PTD = Get_PlanetTypes();
  $Systems = Get_Systems();
  $unpop = 0;
  
  foreach ($Systems as $N) {
    $Sid = $N['id'];
    $Ps = Get_Planets($Sid);
    foreach ($Ps as $P) {
      $pt = $P['Type'];
      if (!isset($PTD[$pt]['Count'])) $PTD[$pt]['Count']=0;
      $PTD[$pt]['Count']++;
      $Ms = Get_Moons($P['id']);
      foreach ($Ms as $M) {
        $pt = $M['Type'];
        if (!isset($PTD[$pt]['MCount'])) $PTD[$pt]['MCount']=0;
        $PTD[$pt]['MCount']++;
        }
      }
    if (!$Ps) $unpop++;
    }
  
  echo "<table border=1><tr><td>Type<td>Number Planets<td>Number Moons<td>Hospitable\n";
  foreach($PTD as $PT) {
    if (!isset($PT['Count'])) $PT['Count']=0;
    if (!isset($PT['MCount'])) $PT['MCount']=0;
    echo "<tr><td>" . $PT['Name'] . "<td>" . $PT['Count'] . "<td>" . $PT['MCount'] . "<td>" . $PT['Hospitable'];
  }
  echo "<tr><td>Unpopulated<td>$unpop<td>\n";
  echo "</table>";

  dotail();
?>
