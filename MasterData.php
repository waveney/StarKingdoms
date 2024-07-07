<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("DateTime.php");

  A_Check('God');
  global $GAME,$GAMEID,$GameStatus,$GAMESYS;

  dostaffhead("Master Game Settings");

  echo "<div class='content'><h2>General System Settings</h2>\n";

  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
     }
  }
// var_dump($GAMESYS);
  echo "<form method=post>\n";
  echo "<div class=tablecont><table width=90% border>\n";
  Register_AutoUpdate('MasterData',1);

  echo "<tr>" . fm_number('Current Game',$GAMESYS,'CurGame' );
  echo "<tr>" . fm_textarea("Features",$GAMESYS,'Features',10,20);
  echo "<tr>" . fm_textarea("Capabilities",$GAMESYS,'Capabilities',2,10);
  echo "<tr>" . fm_text('Update Version #',$GAMESYS,'CurVersion') . "<td>Never edit";
  echo "<tr>" . fm_text('Version Changed',$GAMESYS,'VersionDate') . "<td>Never edit";

  if (Access('God')) echo "</tbody><tfoot><tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea>";
  echo "</table></div>\n";

//  echo "<Center><input type=Submit name=ACTION value='Create New Game'></center>\n";
  echo "</form>\n";

  dotail();
?>
