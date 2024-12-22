<?php
include_once("sk.php");
include_once("GetPut.php");
include_once("ThingLib.php");
include_once("OrgLib.php");
global $NOTBY,$GAME,$FACTION;

A_Check('Player');
$DTs = Get_OpTypes(0);
$OTs = Get_OrgTypes(0);

$coln = 0;
$Fid = $FACTION['id']??0;

dostaffhead("Types of Operation");

echo "<form method=post>";
echo "<div class=tablecont><table id=indextable border>\n";
echo "<thead><tr>";
echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Name</a>\n";
echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Organisation type</a>\n";
echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Level</a>\n";
echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Description</a>\n";

echo "</thead><tbody>";
if ($DTs) foreach($DTs as $D) {
  if ($D['Gate'] && !eval("return " . $D['Gate'] . ";" )) continue;

  echo "<tr><td>" . $D['Name'] . "<td>" . $OTs[$D['Office']]['Name'];

  $Ltxt = "Level";
  if ($D['Props']&3) $Ltxt .= "+" . ($D['Props']&3);
  if (($D['Props']& 0Xc)) {
    $Ltxt .= "+" . ((($D['Props']&7)>>2)?(($D['Props']&7)>>2)>1:'') . "X";
  }
  echo "<td>$Ltxt<td>" . $D['Description'];
}
echo "</table></div>";
dotail();

