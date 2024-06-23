<?php
  include_once("sk.php");
  include_once("GetPut.php");

  A_Check('GM');

  dostaffhead("Show Full Map");

  global $db, $GAME;

  $typ='';
  if (isset($_REQUEST['Hex'])) $typ = 'Hex';

  $Rand = rand(1,100000);
  $f1 = file_exists("cache/Fullmap0$typ.png");
  $f2 = file_exists("cache/Fullmap0$typ.map");
  if ($f1 && $f2) {
  // if (file_exists("cache/Fullmap0$typ.png") && file_exists("cache/Fullmap0$typ.map")) {
    echo "<img src=cache/Fullmap0$typ.png?$Rand maxwidth=100% usemap='#skmap'>";
    readfile("cache/Fullmap0$typ.map");
    dotail();
  }
  include("MapFull.php");

?>
