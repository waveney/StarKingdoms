<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  
  global $GAMEID,$LinkStates;
  A_Check('GM');

  dostaffhead("Link Usage");
// var_dump($_REQUEST);exit;

  $Warnings = ['','Green','Yellow','Amber','Red'];
  $WLvls = [25,40,55,70,85];
  $Thresh = (isset($_REQUEST['THRESHOLD'])?$_REQUEST['THRESHOLD']:40);
  $DT=Get_LinksGame(" AND UseCount >= $Thresh ORDER BY UseCount DESC");

  $coln = 0;

  $LLevels = Get_LinkLevels();
  $LLs = [0=>''];
  foreach($LLevels as $L) {
    $LLs[$L['Level']] = $L['Name'];
    $LLs[-$L['Level']] = "Under Repair " . $L['Name'];
  }
  $Systems = Get_SystemRefs();
  $Ssys = [];
  foreach($Systems as $S) $Ssys[$S] = $S;
  
  echo "<form method=post action=EditLinks.php>";
  echo "<div class=tablecont><table id=indextable border>\n";
  echo "<thead><tr>";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Index</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Sys 1</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Sys 2</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Level</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Status</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Usage</a>\n";

  echo "</thead><tbody>";
  foreach($DT as $D) {
    $i = $Did = $D['id'];
//  var_dump($D);exit;
  
    echo "<tr><td>$i";
    echo "<td>" . $D['System1Ref'];
    echo "<td>" . $D['System2Ref'];
    echo "<td><span style='color:" . $LLevels[abs($D['Level'])]['Colour'] . ";'>" . $LLevels[abs($D['Level'])]['Name'] . "</span>";
    echo "<td>" . $LinkStates[$D['Status']];
    echo "<td>" . $D['UseCount'];
  }
  echo "</table></div>\n";
  echo "<input type=submit name=Update value=Update>\n";
  echo "</form></div>";

  dotail();

?>
