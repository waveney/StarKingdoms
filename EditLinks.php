<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");

  global $GAMEID,$LinkStates;
  A_Check('GM');

  dostaffhead("Manage Planet Types");
// var_dump($_REQUEST);exit;

  $LinkMethod = Feature('LinkMethod','Gates'); // 'Gates','Wormholes'

//  var_dump($_REQUEST);

  $DT=Get_LinksGame((isset($_REQUEST['USAGE'])?' ORDER BY (UseCount/Weight) DESC':''));
  if ($LinkMethod == 'Gates') {
    if (UpdateMany('Links','Put_Link',$DT,1,'','','Level',0))  $DT=Get_LinksGame();
  } else if ($LinkMethod == 'Wormholes') {
    if (UpdateMany('Links','Put_Link',$DT,1,'','','Concealment',-1))  $DT=Get_LinksGame();
  }

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
  if ($LinkMethod == 'Gates') {
    echo "Set Level to 0 to remove a link<p>";
    if (isset($_REQUEST['USAGE'])) {
      echo "<h2><a href=EditLinks.php>Sort by Link Id</a></h2>\n";
    } else {
      echo "<h2><a href=EditLinks.php?USAGE>Sort by Wear</a></h2>\n";
    }

  } elseif ($LinkMethod == 'Wormholes') {
    echo "Set Concealment to -1 to remove a link<p>";
  }


  echo "<form method=post action=EditLinks.php>";
  echo "<div class=tablecont><table id=indextable border>\n";
  echo "<thead><tr>";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Index</a>\n";
//  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Game</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Sys 1</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Sys 2</a>\n";
  if ($LinkMethod == 'Gates') {
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Level</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Status</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Weight</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Usage</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Wear</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Mined 1</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Mined 2</a>\n";
  } elseif ($LinkMethod == 'Wormholes') {
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Instability</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Concealment</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Name</a>\n";
  }

  echo "</thead><tbody>";
  foreach($DT as $D) {
    $i = $Did = $D['id'];

    $NameB = $D['System1Ref'] . $D['Instability'] . $D['Concealment']. $D['System2Ref'];
    if ($NameB != ($D['Name'] ?? '')) {
      $D['Name'] = $NameB;
      Put_Link($D);
    }
//  var_dump($D);exit;

    echo "<tr><td>$i" . fm_hidden("GameId$i",$D["GameId"]);
    echo "<td>" . fm_select($Ssys,$D,'System1Ref',0,'',"System1Ref$i");
    echo "<td>" . fm_select($Ssys,$D,'System2Ref',0,'',"System2Ref$i");
    if ($LinkMethod == 'Gates') {
      echo "<td>" . fm_select($LLs,$D,'Level',0,'',"Level$i");
      echo "<td>" . fm_select($LinkStates,$D,'Status',0,'',"Status$i");
      echo fm_number1('',$D,'Weight','','',"Weight$i");
      echo fm_number1('',$D,'UseCount','','',"UseCount$i");
      echo "<td>" . ($D['UseCount']/max($D['Weight'],1));
      echo fm_number1('',$D,'MinedA','','',"MinedA$i");
      echo fm_number1('',$D,'MinedB','','',"MinedB$i");
    } elseif ($LinkMethod == 'Wormholes') {
      echo fm_number1('',$D,'Instability','','',"Instability$i");
      echo fm_number1('',$D,'Concealment','','',"Concealment$i");
      echo fm_text1('',$D,'Name',1,'','',"Name$i");
    }
  }
  $D = ['Weight'=>1,'Concealment'=>-1];
  echo "<tr><td>" . fm_hidden('GameId0',$GAMEID);
    echo "<td>" . fm_select($Ssys,$D,'System1Ref0');
    echo "<td>" . fm_select($Ssys,$D,'System2Ref0');
    if ($LinkMethod == 'Gates') {
      echo "<td>" . fm_select($LLs,$D,'Level',0,'',"Level0");
      echo "<td>" . fm_select($LinkStates,$D,'Status',0,'',"Status0");
      echo fm_number1('',$D,'Weight','','',"Weight0");
    } elseif ($LinkMethod == 'Wormholes') {
      echo fm_number1('',$D,'Instability','','',"Instability0");
      echo fm_number1('',$D,'Concealment','','',"Concealment0");
      echo "<td>Name is auto generated";
    }
//  echo "<td><input type=text name=Level0 value=0>";
  echo "</table></div>\n";
  echo "<input type=submit name=Update value=Update>\n";
  if ($LinkMethod == 'Gates') {
    echo "<input type=submit name=ACTION value='Clear Safe Mode'>\n";
  }
  echo "</form></div>";

  dotail();

?>
