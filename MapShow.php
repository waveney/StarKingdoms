<?php
  include_once("sk.php");
  include_once("GetPut.php");

  A_Check('GM');

  dostaffhead("Show Full Map");

  global $db, $GAME;

  $typ='';
  if (isset($_REQUEST['Hex'])) $typ = 'Hex';

  echo "<img src=cache/Fullmap0$typ.png>";

  dotail();
?>
