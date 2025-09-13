<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("SystemLib.php");

  global $FACTION,$GAnomStates;
  $Fid = 0;

  A_Check('Player');
  if (Access('Player')) {
    if (!$FACTION) {
      if (!Access('GM') ) Error_Page("Sorry you need to be a GM or a Player to access this");
    } else {
      $Fid = $FACTION['id'];
      $Faction = &$FACTION;
    }
  }
  $GM = Access('GM');
  if ($GM) {
    if (isset( $_REQUEST['F'])) {
      $Fid = $_REQUEST['F'];
    } else if (isset( $_REQUEST['f'])) {
      $Fid = $_REQUEST['f'];
    } else if (isset( $_REQUEST['id'])) {
      $Fid = $_REQUEST['id'];
    }
    if (isset($Fid)) $Faction = Get_Faction($Fid);
  } else {
    if ($FACTION['TurnState'] > 2) Player_Page();
  }

  dostaffhead("List Anomalies");

  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
    case 'MOVE':
      break;
    }
  }

  global $db, $GAME, $FAnomalyStates, $GAMEID;

  $AnStateCols = ['White','Lightgreen','Yellow','Pink','Green'];
  $FAs = Gen_Get_Cond('FactionAnomaly',"FactionId=$Fid AND State>0");

  echo "<form><div class=floatright><button class=AnomDone type=button onclick=ToggleClass('AnomDone')>Show Completed Anomalies</button>" .
       "<button type=button class=AnomDone hidden onclick=ToggleClass('AnomDone')>Only Show Uncompleted Anomalies</button></div>";

  if ($FAs) {
    $coln = 0;
    echo "<div class=tablecont><table id=indextable border width=100% style='min-width:1400px'>\n";
    echo "<thead><tr>";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Name</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Where</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Ground /<br>Space</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>State</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Progress</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Description</a>\n";
    echo "</thead><tbody>";

    foreach($FAs as $FA) {
      $Aid = $FA['AnomalyId'];
      $A = Get_Anomaly($Aid);
      if (empty($A['Name'])) continue;
      $Loc = 'Space';
      $LocGr = intdiv($A['WithinSysLoc'],100);
      if (($A['WithinSysLoc'] == 3) || ($LocGr == 2) || ($LocGr ==4)) $Loc = 'Ground';


      $N = Get_System($A['SystemId']);

      $txt = "<td>" . $A['Name'] . "<td><a href=SurveyReport.php?R=" . $N['Ref'] . ">" . $N['Ref'] . "<td>$Loc";
      $cl = '';

//      var_dump($FA['State'], $A);
      if (($A['OtherReq']??0) && ($FA['State']==1)) {
        $txt .= "<td style='Background:orange'>Found, but has requirements to analyse you don't yet meet";
      } else if (($A['Complete']<2) || ($FA['State']>1)) {
        $txt .=  "<td style='Background:" . $AnStateCols[$FA['State']] . ";'>" . $FAnomalyStates[$FA['State']];
        if ($FA['State'] ==4) $cl = "class=AnomDone hidden";
      } else {
        $txt .=  "<td style='Background:coral'>" . $GAnomStates[$A['Complete']] . ($A['Complete'] ==2?' by someone else': ' for some reason');
        $cl = "class=AnomDone hidden";
      }
      $txt .=   "<td>" . $FA['Progress'] . " / " . $A['AnomalyLevel'] . "<td colspan=4>" .  ParseText($A['Description']);

      if (($FA['State']>=3) && ($A['Completion'])){
        $txt .=  "<p>Complete:<p>" . ParseText($A['Completion']);
      }

      echo "<tr $cl>$txt";
//      echo "</tr>";
    }
    echo "</tbody></table></div>";
  } else {
    echo "<h2>You haven't found any yet</h2>\n";

  }

  dotail();
?>

