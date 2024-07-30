<?php
  include_once("sk.php");
  include_once("GetPut.php");

  global $GAMEID;
  global $NOTBY,$SETNOT;

  A_Check('GM');

  dostaffhead("Manage Instability Levels");
  echo "Width: 0 to 4 (more if not solid)?<br>Style: solid, dashed, dotted<p>";
  $AllG = 0;
  if (isset($_REQUEST['AllGames'])) {
    echo "<div class=floatright><h2>Showing All Games - Switch to <a href=LinkInstaLevels.php>Current Game</a></h2></div>";
    echo "The current NotBy Mask is : $SETNOT<p>\n";
    $AllG = 1;
  } else {
    echo "<div class=floatright><h2>Showing current game -  Switch to <a href=LinkInstaLevels.php?AllGames>All Games</a></h2></div>";
  }

  $DT=Get_LinkInstaLevels($AllG);
  if (UpdateMany('LinkInstaLevel','Put_LinkInstaLevel',$DT,0,'','','Instability',-1))  $DT=Get_LinkInstaLevels($AllG);
  $coln = 0;

  echo "The Colour,Width,Style override those set by the concealment, UNLESS colour=blank, width=0, style=blank<p>";
  echo "<form method=post action=LinkInstaLevels.php>";
  echo "<div class=tablecont><table id=indextable border>\n";
  echo "<thead><tr>";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Index</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Instability</a>\n";
  if ($AllG) echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>NotBy</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Colour</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Width</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Style</a>\n";
  echo "</thead><tbody>";
  foreach($DT as $D) {
    $i = $Did = $D['id'];
    echo "<tr><td>$i" . fm_number1("",$D,'Instability','','',"Instability$i");
    echo fm_notby($D,$i,$AllG);
    echo fm_text1("",$D,'Colour',1,'','',"Colour$i");
    echo fm_number1("",$D,'Width','','',"Width$i");
    echo fm_text1("",$D,'Style',1,'','',"Style$i");
  }
  echo "<tr><td><td><input type=number name=Instability0 value=0>";
  echo fm_hidden('NotBy0',$SETNOT);
  if ($AllG) echo "<td>$SETNOT";
  echo "<td><input type=text name=Colour0 >";
  echo "<td><input type=number name=Width0 value=0>";
  echo "<td><input type=text name=Style0 >";
  echo "</table></div>\n";
  echo "<input type=submit name=Update value=Update>\n";
  echo "</form></div>";

  dotail();

?>
