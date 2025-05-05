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
      echo "<tr><td>" . $A['Name'] . "<td><a href=SurveyReport.php?R=" . $N['Ref'] . ">" . $N['Ref'] . "<td>$Loc";

      if (($A['Complete']<2) || ($FA['State']>1)) {
        echo "<td style='Background:" . $AnStateCols[$FA['State']] . ";'>" . $FAnomalyStates[$FA['State']];
      } else {
        echo "<td style='Background:coral'>" . $GAnomStates[$A['Complete']] . ($A['Complete'] ==2?' by someone else': ' for some reason');
      }
      echo  "<td>" . $FA['Progress'] . " / " . $A['AnomalyLevel'] . "<td colspan=4>" .  ParseText($A['Description']);

      if (($FA['State']>=3) && ($A['Completion'])){
        echo "<p>Complete:<p>" . ParseText($A['Completion']);
      }
//      echo "</tr>";
    }
    echo "</tbody></table></div>";
  } else {
    echo "<h2>You haven't found any yet</h2>\n";

  }

  dotail();
?>

