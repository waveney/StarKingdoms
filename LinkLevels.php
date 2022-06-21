<?php
  include_once("sk.php");
  include_once("GetPut.php");
  
  global $GAMEID;
  A_Check('GM');

  dostaffhead("Manage Planet Types");

  $DT=Get_LinkLevels();
  if (UpdateMany('LinkLevel','Put_LinkLevel',$DT,0))  $DT=Get_LinkLevels();

  $coln = 0;
  
  echo "<form method=post action=LinkLevels.php>";
  echo "<div class=tablecont><table id=indextable border>\n";
  echo "<thead><tr>";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Index</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Name</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Level</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Colour</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Game</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Cost</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Agent Cost</a>\n";
  echo "</thead><tbody>";
  foreach($DT as $D) {
    $i = $Did = $D['id'];
    echo "<tr><td>$i" . fm_text1("",$D,'Name',1,'','',"Name$i");
    echo fm_number1("",$D,'Level','','',"Level$i");
    echo fm_text1("",$D,'Colour',1,'','',"Colour$i");
    echo fm_number1("",$D,'GameId','','',"GameId$i");
    echo fm_number1("",$D,'Cost','','',"Cost$i");
    echo fm_number1("",$D,'AgentCost','','',"AgentCost$i");
  }
  echo "<tr><td><td><input type=text name=Name0 >";
  echo "<td><input type=number name=Level0 value=0>"; 
  echo "<td><input type=text name=Colour0 >";
  echo "<td><input type=number name=GameId0 value=$GAMEID>"; 
  echo "<td><input type=number name=Cost0 value=0>"; 
  echo "<td><input type=number name=AgentCost0 value=0>"; 
  echo "</table></div>\n";
  echo "<input type=submit name=Update value=Update>\n";
  echo "</form></div>";

  dotail();

?>
