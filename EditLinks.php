<?php
  include_once("sk.php");
  include_once("GetPut.php");
  
  global $GAMEID;
  A_Check('GM');

  dostaffhead("Manage Planet Types");
// var_dump($_REQUEST);exit;

  $DT=Get_LinksGame((isset($_REQUEST['USAGE'])?' ORDER BY UseCount DESC':''));
  if (UpdateMany('Links','Put_Link',$DT,1,'','','Level',0))  $DT=Get_LinksGame();

  $coln = 0;


  $LLevels = Get_LinkLevels();
  $LLs = [0=>''];
  foreach($LLevels as $L) $LLs[$L['Level']] = $L['Name'];
  $Systems = Get_SystemRefs();
  $Ssys = [];
  foreach($Systems as $S) $Ssys[$S] = $S;
  
  echo "Set Level to 0 to remove a link<p>";
  
  if (isset($_REQUEST['USAGE'])) {
    echo "<h2><a href=EditLinks.php>Sort by Link Id</a></h2>\n";
  } else {
    echo "<h2><a href=EditLinks.php?USAGE>Sort by Usage</a></h2>\n";
  };
  
  echo "<form method=post action=EditLinks.php>";
  echo "<div class=tablecont><table id=indextable border>\n";
  echo "<thead><tr>";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Index</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Game</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Sys 1</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Sys 2</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Level</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Usage</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Mined 1</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Mined 2</a>\n";

  echo "</thead><tbody>";
  foreach($DT as $D) {
    $i = $Did = $D['id'];
//  var_dump($D);exit;
  
    echo "<tr><td>$i" . fm_number1("",$D,'GameId','','',"GameId$i");
    echo "<td>" . fm_select($Ssys,$D,'System1Ref',0,'',"System1Ref$i");
    echo "<td>" . fm_select($Ssys,$D,'System2Ref',0,'',"System2Ref$i");
    echo "<td>" . fm_select($LLs,$D,'Level',0,'',"Level$i");
    echo fm_number1('',$D,'UseCount','','',"UseCount$i");
    echo fm_number1('',$D,'MinedA','','',"MinedA$i");
    echo fm_number1('',$D,'MinedB','','',"MinedB$i");
//    echo fm_number1("",$D,'Level','','',"Level$i");
  }
  $D = [];
  echo "<tr><td><td><input type=number name=GameId0 value=$GAMEID>"; 
    echo "<td>" . fm_select($Ssys,$D,'System1Ref0');
    echo "<td>" . fm_select($Ssys,$D,'System2Ref0');
    echo "<td>" . fm_select($LLs,$D,'Level',0,'',"Level0");
//  echo "<td><input type=text name=Level0 value=0>"; 
  echo "</table></div>\n";
  echo "<input type=submit name=Update value=Update>\n";
  echo "</form></div>";

  dotail();

?>
