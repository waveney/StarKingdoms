<?php

  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("PlayerLib.php");
  include_once("SystemLib.php");

  global $FACTION,$GAME,$GAMEID,$FAnomalyStates,$GAnomStates;

  A_Check('GM');

  dostaffhead("Edit an Anomaly");

//var_dump($_REQUEST);
  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
      case 'NEW':
        $A = ['GameId'=>$GAMEID, 'Name'=>'Nameless', 'SystemId'=>0 ];
        Put_Anomaly($A);
        $Aid = $A['id'];
        break;

      case 'Delete':
        $Aid = $_REQUEST['id'];
        $A = Get_Anomaly($Aid);
        db_delete('Anomalies',$Aid);

        echo "<h2>Anomaly: " . $A['Name'] . " has been deleted</h2>";
        echo "<h2><a href=AnomalyList.php>Back to Anomaly List</a></h2>\n";
        dotail();

      case 'View':
        $Aid = $_REQUEST['id'];
        $A = Get_Anomaly($Aid);

        echo "<h2>Description:</h2>";
        echo ParseText($A['Description']);
        echo "<h2>Completion:</h2>";
        echo ParseText($A['Completion']);
        break;

      default:
    }
  }

  if (isset($Aid)) { // Already done
  } else if (isset($_REQUEST['id'])) {
    $Aid = $_REQUEST['id'];
    $A = Get_Anomaly($Aid);
  } else {
    echo "No Anomaly given";
    dotail();
  }

  $As = Gen_Get_Cond('Anomalies',"GameId=$GAMEID ORDER BY SystemId");
  $OtherAnoms = [];
  foreach ($As as $i=>$OA) if ($i != $Aid) $OtherAnoms[$i] = $OA['Name'] . " ($i)";


  echo "<h1>Anomaly Edit</h1>\n";
  echo "Note for Factions: Known - Seen the Anomaly, Analysed - Done it.  Can Analyse - to enable analysis when there are Other requirements - no effect when none.<p>\n";
  $Facts = Get_Factions();
  $Systems = Get_SystemRefs();
  $Others = [];
  if ($A['SystemId']) $Others = Gen_Get_Cond('Anomalies',"SystemId=" . $A['SystemId'] . " AND id!=$Aid");
  echo "<form method=post action=AnomalyEdit.php><table border>";
  Register_Autoupdate("Anomaly",$Aid);
  echo fm_hidden('id',$Aid);

  echo "<tr><td>Anomaly Id:<td>$Aid" . fm_number("Game",$A,'GameId');
  echo "<tr>" . fm_text("Anomaly Name", $A,'Name',4);
  echo "<tr>" . fm_number('Scan Level',$A,'ScanLevel') .  "<td>-1 for invisible" . fm_number('Anomaly Level',$A,'AnomalyLevel');
  echo "<tr><td>Location:<td>" . fm_select($Systems,$A,'SystemId',1,' onchange=SetSysLoc("SystemId","AnomalyLoc","WithinSysLoc")');
  if ($A['SystemId']??0) {
    $N = Get_System($A['SystemId']);
    $syslocs = Within_Sys_Locs($N);
    echo "<td id=AnomalyLoc>" . fm_select($syslocs,$A,'WithinSysLoc');
  } else {
    echo "<td id=AnomalyLoc>";
  }
  echo "<td>" . fm_checkbox('Visible from Space',$A,'VisFromSpace');
  if ($Others) {
    echo "<tr><td>Other Anomalies here:<td colspan=6>";
    foreach($Others as $O) {
      echo "<a href=AnomalyEdit.php?id=" . $O['id'] . ">" . $O['Name'] ."</a><br>";
    }
  }

  echo "<tr>" . fm_textarea("Description",$A,'Description',6,4);
  echo "<tr>" . fm_textarea("Completion",$A,'Completion',6,4);
  echo "<tr>" . fm_textarea('Notes',$A,'Notes',6,3,'class=NotSide');
  echo "<tr>" . fm_textarea('Reward',$A,'Reward',6,2,'class=NotSide');
  echo "<tr>" . fm_textarea('Comments',$A,'Comments',6,2,'class=NotSide');
  echo "<tr>" . fm_textarea('Other Requirements',$A,'OtherReq',6,1);
  echo "<tr><td>" . fm_checkbox("Limit Factions",$A,'Properties') . "<td>Refresh after change" .  fm_number('Story Level',$A,'StoryLevel');
  echo "<td colspan=4>Chained (on completion): " . fm_select($OtherAnoms,$A,'ChainedOn1',1) . ", " .
    fm_select($OtherAnoms,$A,'ChainedOn2',1) . ", " . fm_select($OtherAnoms,$A,'ChainedOn3',1) . ", " .
    fm_select($OtherAnoms,$A,'ChainedOn4',1);
  echo "<tr>" . fm_radio('Status',$GAnomStates,$A,'Complete','',1,'colspan=6') .
                "One Use - Complete after analysis, Completed - resolved, Removed - No longer completeable";

  echo "<tr><td colspan=8><h2>Factions:</h2>";
  echo "<tr><td>Faction<td colspan=3>State<td>Progress<td colspan=7>Notes\n";

  if (empty($A['OtherReq'])) unset($FAnomalyStates[2]);
  if (empty($A['Properties'])) unset($FAnomalyStates[-1]);

  foreach ($Facts as $F) {
    if ($F['NoAnomalies']) continue;
    $Fid = $F['id'];
    $FA = Gen_Get_Cond('FactionAnomaly',"AnomalyId=$Aid AND FactionId=$Fid");
    if ($FA) $FA = array_pop($FA);
    echo "<form method=post action=AnomalyEdit.php>";
    echo "<tr><td style='background:" . $F['MapColour'] . ";'>" . $F['Name'];
    echo "<td colspan=3>". fm_radio('',$FAnomalyStates,$FA,'State','',0,'',"State:$Fid");
    echo fm_number1('',$FA,'Progress','','',"Progress:$Fid");
    echo fm_text1('',$FA,'Notes',5,'','',"Notes:$Fid");
    echo "</tr>\n";
  }
  if (Access('God')) echo "</tbody><tfoot><tr><td class=NotSide>Debug<td colspan=6 class=NotSide><textarea id=Debug></textarea>";

  echo "</table><p>\n";

  echo "<input type=submit name=ACTION value=View> <input type=submit name=ACTION value=Refresh> <input type=submit name=ACTION value=Delete>\n";
  echo "<h2><a href=AnomalyList.php>Back to Anomaly List</a></h2>\n";

  dotail();
?>

