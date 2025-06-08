<?php
  include_once("sk.php");
  global $FACTION,$GAMEID,$USER,$GAME;
  include_once("GetPut.php");
  include_once("PlayerLib.php");
  include_once("vendor/erusev/parsedown/Parsedown.php");
  global $PlayerState,$PlayerStates;

  A_Check('Player');

  dostaffhead("Automated Turn Text",["js/dropzone.js","css/dropzone.css" ]);
  $Turn = $GAME['Turn'] - 1; // Default is last turn

  if (Access('GM') && isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
      case 'Submit' :  // TODO add checking of turn
        break;

      case 'Edit':
        $Filename = $_REQUEST['F'];
        echo "<h2>Edit - Click Save to make changes</h2>";
        echo "<form method=post action='PlayerTurnTxt.php?F=$Filename'>";

//        var_dump($Filename);
        $File['Text'] = file_get_contents($Filename);
        echo fm_basictextarea($File,'Text',20,10);
        echo fm_submit('ACTION','Save');
        dotail();

      case 'Save':
        $Filename = $_REQUEST['F'];
        $Text = $_REQUEST['Text'];
        file_put_contents($Filename, $Text);
        echo "<h2>Text Updated</h2>";
        break;
    }
  }

  if (isset($_REQUEST['Turn'])) {
    $Turn = $_REQUEST['Turn'];
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


  $FileName = "Turns/$GAMEID/$Turn/$Fid.txt";

  if (!file_exists($FileName)) {
    $FileName = "Turns/$GAMEID/" . ($Turn-1) . "/$Fid.txt";
    if (!file_exists($FileName)) {
      echo "<h2>Sorry that turn does not have any text on file</h2>";
      dotail();
    }
  }
  if (Access('GM')) echo "<h2><a href='PlayerTurnTxt.php?F=$FileName&ACTION=Edit'>GM Edit</a></h2>";

  $FILE = fopen($FileName,'r');
  while ($txt = fgets($FILE)) echo "$txt<br>";

  Player_Page();
  dotail();
?>
