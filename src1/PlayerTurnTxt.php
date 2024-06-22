<?php
  include_once("sk.php");
  global $FACTION,$GAMEID,$USER,$GAME;
  include_once("GetPut.php");
  include_once("PlayerLib.php");
  include_once("vendor/erusev/parsedown/Parsedown.php");
  global $PlayerState,$PlayerStates;

  A_Check('Player');

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
    $Turn = $GAME['Turn'] - 1; // Default is last turn
  }

  if (isset($FACTION)) {
    $Fid = $FACTION['id'];
    $xtra = "";
  } else if (Access('GM')) {
    if (isset($_REQUEST['id'])) {
      $Fid = $_REQUEST['id'];
      $xtra = "&id=$Fid";
    } else {
      echo "No Faction identified<p>\n";
      dotail();
    }
  } else {
    "Who are you?<p>";
    dotail();
  }
  if (!Access('GM') && $FACTION['TurnState'] > 2) Player_Page();

  echo "<div class=floatright><h2>";
  if ($GAME['Turn'] <5 ) {
    echo "Older Turns =&gt;";
    if (file_exists("Turns/$GAMEID/0/$Fid.txt")) echo " <a href=PlayerTurnTxt.php?Turn=0$xtra>Setup</a>";
    if ($GAME['Turn']) for($turn=1; $turn <= $GAME['Turn']; $turn++) {
      echo ", <a href=PlayerTurnTxt.php?Y=$turn$xtra>$turn</a>";
    }
  } else {
    echo "Older Turns =&gt; <div id=ExpandTurnsDots class=InLine><b onclick=ExpandTurns()>...</b></div><div id=HiddenTurns hidden>";
    if (file_exists("Turns/$GAMEID/0/$Fid.txt")) echo " <a href=PlayerTurnTxt.php?Turn=0$xtra>Setup</a>";
    for($turn=1; $turn <= $GAME['Turn']; $turn++) {
      if ($turn == ($GAME['Turn'] - 5)) echo "</div><div class=InLine>";
      if (file_exists("Turns/$GAMEID/$turn/$Fid.txt")) echo ", <a href=PlayerTurnTxt.php?Turn=$turn$xtra>$turn</a>";
    }
    echo "</div>";
  }
  echo "</h2></div>";

  /// List of turns

  echo "<h1>Automated Actions Turn: $Turn</h1>\n";
 // $Parsedown = new Parsedown();

  $FileName = "Turns/$GAMEID/$Turn/$Fid.txt";

  if (!file_exists($FileName)) {
    echo "<h2>Sorry that turn does not have any text on file</h2>";
    dotail();
  }

  $FILE = fopen($FileName,'r');
  while ($txt = fgets($FILE)) echo "$txt<br>";
/*
  $Text = file_get_contents($FileName);
  // display selected turn

  echo $Parsedown->text($Text) . "<p>";
*/

  Player_Page();
  dotail();
?>
