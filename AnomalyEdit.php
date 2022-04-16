<?php

  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("PlayerLib.php");
  include_once("SystemLib.php");
  
  global $FACTION,$GAME,$GAMEID,$AnomalyStates;
  
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
  
  $Facts = Get_Factions();
  $Systems = Get_SystemRefs();
  
  $Others = Gen_Get_Cond('Anomalies',"SystemId=" . $A['SystemId'] . " AND id!=$Aid");
  echo "<form method=post action=AnomalyEdit.php><table border>";
  Register_Autoupdate("Anomaly",$Aid);
  echo fm_hidden('id',$Aid);
  
  echo "<tr><td>Anomaly Id:<td>$Aid" . fm_number("Game",$A,'GameId');
  echo "<tr>" . fm_text("Anomaly Name", $A,'Name',4);
  echo "<tr>" . fm_number('Scan Level',$A,'ScanLevel') .  fm_number('Anomaly Level',$A,'AnomalyLevel');
  echo "<tr><td>Location:<td>" . fm_select($Systems,$A,'SystemId',1);
  if ($Others) {
    echo "<tr><td>Other Anomalies here:<td colspan=8>";
    foreach($Others as $O) {
      echo "<a href=AnomalyEdit.php?id=" . $O['id'] . ">" . $O['Name'] ."</a><br>";
    }
  }

  echo "<tr>" . fm_textarea("Description",$A,'Description',8,4);
  echo "<tr>" . fm_textarea('Notes',$A,'Notes',8,4);
  echo "<tr>" . fm_textarea('Reward',$A,'Reward',8,2);
  echo "<tr>" . fm_textarea('Comments',$A,'Comments',8,2);
    
  echo "<tr>" . fm_number('Complete',$A,'Complete') .  fm_number('Story Level',$A,'StoryLevel');

  echo "<tr><td colspan=8><h2>Factions:</h2>";
  echo "<tr><td>Faction<td colspan=2>State<td>Progress<td colspan=7>Notes\n";
  foreach ($Facts as $F) {
    $Fid = $F['id'];
    $FA = Gen_Get_Cond('FactionAnomaly',"AnomalyId=$Aid AND FactionId=$Fid");
    if ($FA) $FA = $FA[0];
    echo "<form method=post action=AnomalyEdit.php>";    
    echo "<tr><td style='background:" . $F['MapColour'] . ";'>" . $F['Name'];
    echo "<td colspan=2>". fm_radio('',$AnomalyStates,$FA,'State','',0,'',"State:$Fid");
    echo fm_number1('',$FA,'Progress','','',"Progress:$Fid");
    echo fm_text1('',$FA,'Notes',7,'','',"Notes:$Fid");
    echo "</tr>\n";
  }
  if (Access('God')) echo "<tr><td class=NotSide>Debug<td colspan=8 class=NotSide><textarea id=Debug></textarea>";
  
  echo "</table><p>\n";
  
  echo "<input type=submit name=ACTION value=Refresh> <input type=submit name=ACTION value=Delete>\n";
  echo "<h2><a href=AnomalyList.php>Back to Anomaly List</a></h2>\n";
   
  dotail();
?>
  
