<?php
  include_once("sk.php");
  global $FACTION,$GAMEID,$USER,$GAME;
  include_once("GetPut.php");
  include_once("PlayerLib.php");
  include_once("vendor/erusev/parsedown/Parsedown.php");
  global $PlayerState,$PlayerStates;

  A_Check('GM');

  dostaffhead("Automated Turn Text",["js/dropzone.js","css/dropzone.css" ]);

  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
      case 'Submit' :  // TODO add checking of turn
        break;
    }
  }

  if (isset($_REQUEST['Turn'])) {
    $Turn = $_REQUEST['Turn'];
  } else {
    $Turn = $GAME['Turn']; // Default is current turn
  }

  $xtra = '';

//echo php_ini_loaded_file() . "<P>";
  echo "<div class=floatright><h2>Other Turns =&gt;";
  if (file_exists("Turns/$GAMEID/0/0.txt")) echo " <a href=GMTurnTxt.php?Turn=0$xtra>Setup</a>";
  if ($GAME['Turn']) for($turn=1; $turn <= $GAME['Turn']; $turn++) {
    if (file_exists("Turns/$GAMEID/$turn/0.txt")) echo ", <a href=GMTurnTxt.php?Turn=$turn$xtra>$turn</a>";
  }
  echo "</h2></div>";

  /// List of turns

  echo "<h1>Automated Actions Turn: $Turn</h1>\n";

  echo "<h2><a href=#END>Jump to End</a></h2>\n";

  $FileName = "Turns/$GAMEID/$Turn/0.txt";

  if (!file_exists($FileName)) {
    $Turn--;
    $FileName = "Turns/$GAMEID/$Turn/0.txt";
    if (!file_exists($FileName)) {
      echo "<h2>Sorry that turn does not have any text on file</h2>";
      dotail();
    }
  }

  $FILE = fopen($FileName,'r');
  while ($txt = fgets($FILE)) echo "$txt<br>";

  echo "<h2><a id=END href=GMTurnTxt.php?Turn=$turn$xtra>Refresh</a></h2>";
  dotail();
?>
