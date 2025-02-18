<?php
  include_once("sk.php");
  include_once("GetPut.php");

  A_Check('GM');

  dostaffhead("Show Full Map",['js/imageMapResizer.min.js']);

  global $db, $GAME,$GAMEID;

  $typ='';
  if (isset($_REQUEST['Hex'])) $typ = 'Hex';

  $Rand = rand(1,100000);
  $f1 = file_exists("cache/$GAMEID/Fullmap0$typ.png");
  $f2 = file_exists("cache/$GAMEID/Fullmap0$typ.map");
  if ($f1 && $f2) {
    // if (file_exists("cache/$GAMEID/Fullmap0$typ.png") && file_exists("cache/Fullmap0$typ.map")) {
    echo "<div id=MapDiv style='width:100%;max-width:100%;object-fit:contain'><img src=cache/$GAMEID/Fullmap0$typ.png?$Rand usemap='#skmap'>";
    readfile("cache/$GAMEID/Fullmap0$typ.map");
    echo "</div>";
    dotail();
  }
  include("MapFull.php");

?>
