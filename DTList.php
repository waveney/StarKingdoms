<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  A_Check('GM');

  global $NOTBY;

  dostaffhead("Manage District Types");

  $AllG = 0;
  if (isset($_REQUEST['AllGames'])) {
    // Button for cur game
    // Show current NotBy Mask
    echo "<div class=floatright><h2>Showing All Games - Switch to <a href=DTList.php>Current Game</a></h2></div>";
    echo "The current NotBy Mask is : " . ~$NOTBY . "<p>\n";
    $AllG = 1;
  } else {
    echo "<div class=floatright><h2>Showing current game -  Switch to <a href=DTList.php?AllGames>All Games</a></h2></div>";
  }

  $DT=Get_DistrictTypes($AllG);
  if (UpdateMany('DistrictTypes','Put_DistrictType',$DT,1))  $DT=Get_DistrictTypes($AllG);

  $Techs = Get_Techs($AllG);
  $TechNames = Tech_Names($Techs);

  $coln = 0;

  echo "Properties 1 = Prime, 2= Do projects, 4=Mining Income, 8=Don't have levels, 16=Valid on Space stations, 32=Industrial<p>\n";

  echo "<form method=post action=DTList.php>";
  if ($AllG) echo fm_hidden('AllGames',1);
  echo "<div class=tablecont><table id=indextable border>\n";
  echo "<thead><tr>";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Index</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Name</a>\n";
  if ($AllG) echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>NotBy</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Properties</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Enabling Tech</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Gate</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Max Number</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Notes</a>\n";
  echo "</thead><tbody>";
  foreach($DT as $D) {
    $i = $Did = $D['id'];
    echo "<tr><td>$i" . fm_text1("",$D,'Name',1,'','',"Name$i");
    echo fm_notby($D,$i,$AllG);
    echo fm_number1('',$D,'Props','','',"Props$i");
    echo "<td>" . fm_select($TechNames,$D,'BasedOn',1,'',"BasedOn$i");
    echo fm_text1("",$D,'Gate',1,'','',"Gate$i");
    echo fm_number1('',$D,'MaxNum','','',"MaxNum$i");
    echo fm_text1('',$D,'Notes',1,'','',"Notes$i");
  }
  echo "<tr><td><td><input type=text name=Name0 >";
  echo fm_hidden('NotBy0',~$NOTBY);
  if ($AllG) echo "<td>" . ~$NOTBY;
  echo "<td><input type=number name=Props0 >";
  echo "<td>" . fm_select($TechNames,$D,'BasedOn',1,'',"BasedOn0");
  echo "<td><input type=text name=Gate0 >";
  echo "<td><input type=number name=MaxNum0 >";
  echo "<td><input type=text name=Notes0 >";
  echo "</table></div>\n";
  echo "<input type=submit name=Update value=Update>\n";
  echo "</form></div>";

  dotail();

?>

