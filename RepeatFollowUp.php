<?php
include_once("sk.php");
global $FACTION,$GAMEID,$USER,$GAME;
include_once("GetPut.php");
include_once("PlayerLib.php");
include_once("vendor/erusev/parsedown/Parsedown.php");
global $PlayerState,$PlayerStates;

A_Check('GM');

dostaffhead("Repeat Follow Ups needed");

echo "<h2>Set up Follow Ups to be reapeated every turn until stopped</h2>";

echo "Status:0 live<p>";

if (isset($_REQUEST['ACTION'])) {
  switch ($_REQUEST['ACTION']) {
    case 'Add':
      $Up = ['Name'=>$_REQUEST['Name'], 'Status'=>$_REQUEST['Status'], 'GameId'=>$GAMEID];
      Gen_Put('RepeatFollow',$Up);
      break;
    case 'Remove':
      $Ti = $_REQUEST['Ti'];
      db_delete('RepeatFollow',$Ti);
      break;
  }
}

$coln = $todo = 0;
$People = Get_People();
$Rups = Gen_Get_Cond('RepeatFollow',"GameId=$GAMEID");

echo "<form method=post action=RepeatFollowUp.php>";
Register_AutoUpdate('Generic',0);
echo "<div class=tablecont><table id=indextable border>\n";
echo "<thead><tr>";
echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Index</a>\n";
echo "<th colspan=4><a href=javascript:SortTable(" . $coln++ . ",'T')>Message</a>\n";
echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Status</a>\n";
echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Actions</a>\n";
echo "</thead><tbody>";
foreach($Rups as $Up) {
  $i = $Up['id'];
  echo "<tr><td>$i" . fm_text1('',$Up,'Name',4,'','',"RepeatFollow:Name:$i") . fm_number1('',$Up,'Status','','',"RepeatFollow:Status:$i");
  echo fm_submit('ACTION','Remove',1,"formaction=RepeatFollowUp.php?Ti=$i");

}
$Up = [];
Cancel_AutoUpdate();
echo "<tr><td>" . fm_text1('',$Up,'Name',4) . fm_number1('',$Up,'Status') . fm_submit('ACTION','Add');
if (Access('God')) echo "<tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea>";

echo "</table>";
dotail();


