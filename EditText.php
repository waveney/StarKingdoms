<?php
include_once("sk.php");
global $FACTION,$GAMEID,$USER,$GAME;
include_once("GetPut.php");
include_once("PlayerLib.php");
global $PlayerState,$PlayerStates;

A_Check('GM');

dostaffhead('Edit Turn Text');
$Fid = ($FACTION['id']??0);

if (!$Fid) {
  echo "<h1 class=Err>Not In a Faction</h1>";
  dotail();
}
/*
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
  echo "<h2>Sorry that turn does not have any text on file</h2>";
  dotail();
}

$FILE = fopen($FileName,'r');
while ($txt = fgets($FILE)) echo "$txt<br>";

