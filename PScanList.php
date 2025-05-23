<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("SystemLib.php");

  dostaffhead("Move Things",["js/dropzone.js","css/dropzone.css" ]);

  global $db, $GAME, $GAMEID, $Factions, $Dot, $FAnomalyStates, $AnStateCols;
  global $FACTION;

  include_once("vendor/erusev/parsedown/Parsedown.php");
  $Parsedown = new Parsedown();
  $AnStateCols = ['White','Lightgreen','Yellow','Pink'];

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

  dostaffhead("System scanning");

  echo "<h1>Known systems and scan levels</h1>";

  $FSs = Gen_Get_Cond('FactionSystem',"FactionId=$Fid");

  if ($FSs) {
    $coln = 0;
    echo "<div class=tablecont><table id=indextable border width=100% style='min-width:1400px'>\n";
    echo "<thead><tr>";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Ref</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Name</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>State</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Progress</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Description</a>\n";
    echo "</thead><tbody>";

    foreach($FSs as $FA) {
      $Aid = $FA['AnomalyId'];
      $A = Get_Anomaly($Aid);
      if (empty($A['Name'])) continue;
      $N = Get_System($A['SystemId']);
      echo "<tr><td>" . $A['Name'] . "<td>" . $N['Ref'] . "<td style='Background:" . $AnStateCols[$FA['State']] . ";'>" . $FAnomalyStates[$FA['State']] . "<td>";
      echo $FA['Progress'] . " / " . $A['AnomalyLevel'] . "<td colspan=4>" .  $Parsedown->text(stripslashes($A['Description']));
//      echo "</tr>";
    }
    echo "</tbody></table></div>";
  } else {
    echo "<h2>You haven't found any yet</h2>\n";

  }



  dotail();

?>
