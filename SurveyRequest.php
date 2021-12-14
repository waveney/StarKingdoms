<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("SystemLib.php");
  include_once("vendor/erusev/parsedown/Parsedown.php");
  
  A_Check('GM'); // For now, will be player version

  dostaffhead("Survey Request",["js/dropzone.js","css/dropzone.css" ]);

  global $db, $GAME, $FACTION, $SurveyLevels;
  
  $Facts = Get_Faction_Names();
  $Z = [];
  echo "<h1>Request Survey report for</h1>";
  echo "<form method=post action=SurveyReport.php>";
  echo "<table border>";
  echo "<tr><td>Faction:<td>" . fm_Select($Facts,$Z,'F');
  echo "<tr>" . fm_text("System",$Z,'R');
  echo "<tr><td>Level<td>" . fm_Select($SurveyLevels,$Z,'V');
  echo "<tr><td><td>" . fm_checkbox("Mark as Known? ",$Z,'M');
  echo "</table>";
  echo "<tr><td><input type=submit value=Survey></form>\n";
  
  dotail();
?>

