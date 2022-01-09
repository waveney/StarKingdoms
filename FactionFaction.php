<?php
  include_once("sk.php");
  include_once("GetPut.php");
  /* Remove any Participant overlay */

  A_Check('GM');

  $Facts = Get_Factions();
  
  dostaffhead("A Knows B");
  
  echo "<h1>What Factions know of others</h1>";
  
  echo "<form method=post action=FactionFaction.php>";
  Register_Autoupdate('FFaction',0);
  
  echo "<table border>";
  echo "<tr><th>Do These V know about &gt;";
  $K = ['Y'=>1, 'N'=> 0];
  foreach ($Facts as $F) echo "<th>" . $F['Name'];
  foreach ($Facts as $F1) {
    $FF = Get_FactionFactions($F1['id']);
    echo "<tr><td>" . $F1['Name'];
    foreach ($Facts as $F2) {
      if ($F1['id'] == $F2['id'] ) {
        echo "<td>";
        continue; 
      }
      if (isset($FF[$F2['id']])) {
        echo "<td>" . fm_checkbox('',$K,'Y','',"Know" . $F1['id'] . ":" . $F2['id']);
      } else {
        echo "<td>" . fm_checkbox('',$K,'N','',"Know" . $F1['id'] . ":" . $F2['id']);      
      }
    }
  }
  echo "</table></form>";

  if (Access('God')) {
    echo "<table border>";
    echo "<tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea>";
    echo "</table>";
  }

  
  dotail();

?>
