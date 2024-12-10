<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");

  A_Check('GM');

  dostaffhead("List Anomalies");

  global $db, $GAME, $GAMEID;
  global $ModFormulaes,$ModValues,$Fields,$Tech_Cats,$CivMil;

  $As = Gen_Get_Cond('Anomalies',"GameId=$GAMEID ORDER BY SystemId");

  echo "<h1>List Anomalies</h1>";
  echo "click on id or name to edit description<p>";

  $Systems = Get_SystemRefs();

  $coln = 0;
  echo "<div class=tablecont><table id=indextable border style='min-width:1400px'>\n";
  echo "<thead><tr>";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>id</a>\n";
  echo "<th colspan=2><a href=javascript:SortTable(" . $coln++ . ",'T')>Name</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Scan Level</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Anomaly Lvl</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Limited</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Other Reqs</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Location</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Ground /<br>Space</a>\n";
  echo "</thead><tbody>";

  foreach($As as $A) {
    $i = $A['id'];
    echo "<tr><td><a href=AnomalyEdit.php?id=$i>$i</a><td colspan=2><a href=AnomalyEdit.php?id=$i>" . $A['Name'] . "</a>";
    echo "<td>" . $A['ScanLevel'];
    echo "<td>" . $A['AnomalyLevel'];
    echo "<td>" . ($A['Properties']?'Yes':'');
    echo "<td>" . ($A['OtherReq']?'Yes':'');
    echo "<td>" . ($Systems[$A['SystemId']]??'???');
    $Loc = 'Space';
    $LocGr = intdiv($A['WithinSysLoc'],100);
    if (($A['WithinSysLoc'] == 3) || ($LocGr == 2) || ($LocGr ==4)) $Loc = 'Ground';
    echo "<td>$Loc";
  }
  echo "</tbody></table></div>\n";

  echo "<h2><a href=AnomalyEdit.php?ACTION=NEW>New Anomaly</a></h2>";
  dotail();
?>

