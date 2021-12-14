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
      if (!isset($PTD[$pt]['Count'])) { 
        $PTD[$pt]['Count']=0;
        $PTD[$pt]['Where']='';
      }
      $PTD[$pt]['Count']++;
      $PTD[$pt]['Where'] .= ' <a href=SysEdit.php?R=' . $N['Ref'] . ">" . $N['Ref'] . "</a> ";
      $Ms = Get_Moons($P['id']);
      foreach ($Ms as $M) {
        $pt = $M['Type'];
        if (!isset($PTD[$pt]['MCount'])) $PTD[$pt]['MCount']=0;
        $PTD[$pt]['MCount']++;
        }
      }
    if (!$Ps) { 
      $unpop++;
      echo "Unpopulated system:  <a href=SysEdit.php?R=" . $N['Ref'] . ">" . $N['Ref'] . "</a><p> ";
      }
    }
  
  echo "<table border=1><tr><td>Type<td>Number Planets<td>Number Moons<td>Hospitable<td>Where\n";
  foreach($PTD as $PT) {
    if (!isset($PT['Count'])) $PT['Count']=0;
    if (!isset($PT['MCount'])) $PT['MCount']=0;
    echo "<tr><td>" . $PT['Name'] . "<td>" . $PT['Count'] . "<td>" . $PT['MCount'] . "<td>" . $PT['Hospitable'] . "<td>" . (isset($PT['Where'])?$PT['Where']:"");
  }
  echo "<tr><td>Unpopulated<td>$unpop<td>\n";
  echo "</table>";

  dotail();
?>
