<?php
  include_once("sk.php");
  include_once("GetPut.php");

  global $GAMEID;
  global $NOTBY,$SETNOT;

  A_Check('GM');

  dostaffhead("Manage Planet Types");

  $AllG = 0;
  if (isset($_REQUEST['AllGames'])) {
    echo "<div class=floatright><h2>Showing All Games - Switch to <a href=LinkLevels.php>Current Game</a></h2></div>";
    echo "The current NotBy Mask is : $SETNOT<p>\n";
    $AllG = 1;
  } else {
    echo "<div class=floatright><h2>Showing current game -  Switch to <a href=LinkLevels.php?AllGames>All Games</a></h2></div>";
  }

  $DT=Get_LinkLevels($AllG);
  if (UpdateMany('LinkLevel','Put_LinkLevel',$DT,0))  $DT=Get_LinkLevels($AllG);
  $coln = 0;

  echo "Default Colour=Black, Width=1, Style=Solid.  These may be overriden by Instability values<p>";
  echo "<form method=post action=LinkLevels.php>";
  echo "<div class=tablecont><table id=indextable border>\n";
  echo "<thead><tr>";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Index</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Name</a>\n";
  if ($AllG) echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>NotBy</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Level</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Colour</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Width</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Style</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Game</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Cost</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Agent Cost</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Make Cost</a>\n";
  echo "</thead><tbody>";
  foreach($DT as $D) {
    $i = $Did = $D['id'];
    echo "<tr><td>$i" . fm_text1("",$D,'Name',1,'','',"Name$i");
    echo fm_notby($D,$i,$AllG);

    echo fm_number1("",$D,'Level','','min=0 max=100',"Level$i");
    echo fm_text1("",$D,'Colour',1,'','',"Colour$i");
    echo fm_number1("",$D,'Width','','min=0 max=10',"Width$i");
    echo fm_text1("",$D,'Style',1,'','',"Style$i");
    echo fm_number1("",$D,'GameId','','min=0 max=100',"GameId$i");
    echo fm_number1("",$D,'Cost','','min=0 max=100',"Cost$i");
    echo fm_number1("",$D,'AgentCost','','min=0 max=100',"AgentCost$i");
    echo fm_number1("",$D,'MakeCost','','min=0 max=10000',"MakeCost$i");
  }
  echo "<tr><td><td><input type=text name=Name0 >";
  echo fm_hidden('NotBy0',$SETNOT);
  if ($AllG) echo "<td>$SETNOT";
  echo "<td><input type=number name=Level0 value=0 max=100 min=0>";
  echo "<td><input type=text name=Colour0 >";
  echo "<td><input type=number name=Width0 value=0 max=10 min=0>";
  echo "<td><input type=text name=Style0 >";
  echo "<td><input type=number name=GameId0 value=$GAMEID min=0 max=100>";
  echo "<td><input type=number name=Cost0 value=0 max=100 min=0>";
  echo "<td><input type=number name=AgentCost0 value=0 max=100 min=0>";
  echo "<td><input type=number name=MakeCost0 value=0 max=10000 min=0>";
  echo "</table></div>\n";
  echo "<input type=submit name=Update value=Update>\n";
  echo "</form></div>";

  dotail();

?>
