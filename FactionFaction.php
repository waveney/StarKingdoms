<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("PlayerLib.php");
/* Remove any Participant overlay */
  global $Relations;
  A_Check('GM');

  $Facts = Get_Factions();

  dostaffhead("A Knows B");

  echo "<h1>What Factions know of others</h1>";
  echo "If their are details there is a * after the relationship.  Click on the Faction in the left column to see any details.<p>";

  echo "<form method=post action=FactionFaction.php>";
  Register_Autoupdate('FFaction',0);

  echo "<table border>";
  echo "<tr><th>Do These V know about &gt;<th>Aliens";
  $K = ['Y'=>1, 'N'=> 0];
  foreach ($Facts as $F) echo "<th>" . $F['Name'];
  foreach ($Facts as $F1) {
//    var_dump($F1);
    $FF = Get_FactionFactions($F1['id']);
    echo "<tr><td><a href=FactionCarry.php?F=" . $F1['id'] . ">". $F1['Name'] . "</a>";
    if ($F1['DefaultRelations'] == 0) $F1['DefaultRelations']= 5;

    $DR = $Relations[$F1['DefaultRelations']];
//    var_dump($DR);
    echo "<td style='background:" . $DR[1]  . "'>" . $DR[0] . ($F1['AlienDescription']?' *':'');
    foreach ($Facts as $F2) {
      if ($F1['id'] == $F2['id'] ) {
        echo "<td>";
        continue;
      }
      $R = $FF[$F2['id']]['Relationship']??5;
      if ($R ==0) $R = 5;
      $DR = $Relations[$R];
      $bg = " style='background:" . $DR[1]  . "'";
      if (isset($FF[$F2['id']])) {
        echo "<td $bg>" . fm_checkbox('',$K,'Y','',"Know" . $F1['id'] . ":" . $F2['id']);
        echo " " . $DR[0] . ($FF[$F2['id']]['Description']?' *':'');
      } else {
        echo "<td $bg>" . fm_checkbox('',$K,'N','',"Know" . $F1['id'] . ":" . $F2['id']);
        echo " " . $DR[0];
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
