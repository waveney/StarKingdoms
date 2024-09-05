<?php
include_once("sk.php");
include_once("GetPut.php");
include_once("OrgLib.php");

A_Check('GM');
global $NOTBY,$SETNOT;

dostaffhead("Branch Types");

global $db, $GAME, $GAMEID;

// var_dump($_REQUEST);
$AllG = 0;
if (isset($_REQUEST['AllGames'])) {
  // Button for cur game
  // Show current NotBy Mask
  echo "<div class=floatright><h2>Showing All Games - Switch to <a href=BranchTypes.php>Current Game</a></h2></div>";
  echo "The current NotBy Mask is : $SETNOT<p>\n";
  $AllG = 1;
} else {
  echo "<div class=floatright><h2>Showing current game -  Switch to <a href=BranchTypes.php?AllGames>All Games</a></h2></div>";
}

$Ts = Get_BranchTypes($AllG);
if (UpdateMany('BranchTypes','Put_BranchType',$Ts,1,'','','Name','','Props')) $Ts = Get_BranchTypes($AllG);

echo "<h1>Branch Types</h1>";

echo "Props (Hex) 1 =Hidden, 2=No Space\n";

echo "<form method=post action=BranchTypes.php>";
if ($AllG) echo fm_hidden('AllGames',1);

$OrgTypes = Get_OrgTypes();
$OrgTypeNames = [];
foreach ($OrgTypes as $i=>$Ot) $OrgTypeNames[$i] = $Ot['Name'];

$coln = 0;
echo "<div class=tablecont><table id=indextable border width=100% style='min-width:1400px'>\n";
echo "<thead><tr>";
echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>id</a>\n";
echo "<th colspan=2><a href=javascript:SortTable(" . $coln++ . ",'T')>Name</a>\n";
if ($AllG) echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>NotBy</a>\n";
echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Properties</a>\n";
echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>OrgType</a>\n";
echo "</thead><tbody>";

foreach($Ts as $T) {
  $i = $T['id'];
  echo "<tr><td>$i";
  echo fm_text1("",$T,'Name',2,'','',"Name$i");
  echo fm_notby($T,$i,$AllG);
  echo fm_hex1('',$T,'Props','','',"Props$i");
  echo "<td>" . fm_select($OrgTypeNames, $T,'OrgType',0,'',"OrgType$i");
 }

$T = [];
echo "<tr><td>" . fm_text1("",$T,'Name',2,'','',"Name0");
echo fm_hidden('NotBy0',$SETNOT);
if ($AllG) echo "<td>$SETNOT";
echo fm_hex1('',$T,'Props','','',"Props0");
echo "<td>" . fm_select($OrgTypeNames, $T,'OrgType',0,'', 'OrgType0');
echo "</tbody></table></div>\n";

echo "<h2><input type=submit name=Update value=Update></h2>";
echo "</form></div>";
dotail();
?>
