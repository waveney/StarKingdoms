<?php
include_once('sk.php');
include_once("GetPut.php");

A_Check('GM');

dostaffhead("Links Stats");

$LLevels = Get_LinkLevels();
$Insta = Get_LinkInstaLevels();

$Links = Get_LinksGame();
$LUse = [];
$MaxC = $MaxI = 0;

foreach ($Links as $L) {
  $C = $L['Concealment'];
  $I = $L['Instability'];
  if (isset($LUse[$C][$I])) {
    $LUse[$C][$I]++;
  } else {
    $LUse[$C][$I]=1;
  }
  if ($C>$MaxC) $MaxC = $C;
  if ($I>$MaxI) $MaxI = $I;
}

echo "<table border style='width:800'>";
echo "<tr><th>Concealment-><br>Instability V";
for($i = 0; $i <=$MaxC; $i++) echo "<th>$i";

for ($I = 0; $I <=$MaxI; $I++) {
  echo "<tr><td>$I";
  for ($C = 0; $C <=$MaxC; $C++) {
    echo "<td>" . ($LUse[$C][$I]??'');
  }
}

echo "</table>";
dotail();

// Get Links, Link Levels, Insta levels

// table conceal accross, total, Instab width, colour style
// V Instability V Totals V Width, Colour, Style

;