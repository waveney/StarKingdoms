<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  
  global $GAMEID,$LinkStates;
  A_Check('GM');

  dostaffhead("Manage Planet Types");
// var_dump($_REQUEST);exit;

  $DT=Get_LinksGame((isset($_REQUEST['USAGE'])?' ORDER BY (UseCount/Weight) DESC':''));
  if (UpdateMany('Links','Put_Link',$DT,1,'','','Level',0))  $DT=Get_LinksGame();

  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
    case 'Clear Safe Mode':
      foreach($DT as $D) {
        if ( $D['Status'] == 2) {
          $D['Status'] = 0;
          Put_Link($D);
        }
      }
      $DT=Get_LinksGame();
      break;
    }
  }

  $coln = 0;


  $LLevels = Get_LinkLevels();
  $LLs = [0=>''];
  foreach($LLevels as $L) {
    $LLs[$L['Level']] = $L['Name'];
    $LLs[-$L['Level']] = "Under Repair " . $L['Name'];
  }
  $Systems = Get_SystemRefs();
  $Ssys = [0=>''];
  
  foreach($Systems as $S) $Ssys[$S] = $S;
  
  echo "Set Level to 0 to remove a link<p>";
  
  if (isset($_REQUEST['USAGE'])) {
    echo "<h2><a href=EditLinks.php>Sort by Link Id</a></h2>\n";
  } else {
    echo "<h2><a href=EditLinks.php?USAGE>Sort by Wear</a></h2>\n";
  };
  
  echo "<form method=post action=EditLinks.php>";
  echo "<div class=tablecont><table id=indextable border>\n";
  echo "<thead><tr>";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Index</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Game</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Sys 1</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Sys 2</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Level</a>\n";

  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Status</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Weight</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Usage</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Wear</a>\n";
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
    echo "<td>" . fm_select($LinkStates,$D,'Status',0,'',"Status$i");
    echo fm_number1('',$D,'Weight','','',"Weight$i");
    echo fm_number1('',$D,'UseCount','','',"UseCount$i");
    echo "<td>" . ($D['UseCount']/$D['Weight']);
    echo fm_number1('',$D,'MinedA','','',"MinedA$i");
    echo fm_number1('',$D,'MinedB','','',"MinedB$i");
//    echo fm_number1("",$D,'Level','','',"Level$i");
  }
  $D = ['Weight'=>1];
  echo "<tr><td><td><input type=number name=GameId0 value=$GAMEID>"; 
    echo "<td>" . fm_select($Ssys,$D,'System1Ref0');
    echo "<td>" . fm_select($Ssys,$D,'System2Ref0');
    echo "<td>" . fm_select($LLs,$D,'Level',0,'',"Level0");
    echo "<td>" . fm_select($LinkStates,$D,'Status',0,'',"Status0");
    echo fm_number1('',$D,'Weight','','',"Weight0");
//  echo "<td><input type=text name=Level0 value=0>"; 
  echo "</table></div>\n";
  echo "<input type=submit name=Update value=Update>\n";
  echo "<input type=submit name=ACTION value='Clear Safe Mode'>\n";
  echo "</form></div>";

  dotail();

?>
