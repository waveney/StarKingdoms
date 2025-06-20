<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  A_Check('GM');

//  var_dump($_REQUEST);echo"<p>";
  global $NOTBY,$SETNOT;
  dostaffhead("Manage Project Types");

  $AllG = 0;
  if (isset($_REQUEST['AllGames'])) {
    // Button for cur game
    // Show current NotBy Mask
    echo "<div class=floatright><h2>Showing All Games - Switch to <a href=ProjTypes.php>Current Game</a></h2></div>";
    echo "The current NotBy Mask is : $SETNOT<p>\n";
    $AllG = 1;
  } else {
    echo "<div class=floatright><h2>Showing current game -  Switch to <a href=ProjTypes.php?AllGames>All Games</a></h2></div>";
  }

  $DT=Get_ProjectTypes($AllG);
  $Techs = Get_Techs(0,$AllG);
  $TechNames = Tech_Names($Techs);

  if (UpdateMany('ProjectTypes','Put_ProjectType',$DT,0,'','','Name','','Props'))  $DT=Get_ProjectTypes($AllG);
  $coln = 0;

  echo "Category 1=Academic,2=Ship Yard,4=Miltary,8=Intelligence,16=Construction, 32=Deep Space<p>";
  echo "Props 1=Can Rush, 2=Thing, 4=Two things, 8=Other Faction, 10=Tight Loc match, 20=Instruction, 40=Don't Check Level, " .
       "80 Raise 1 Level, 100 Exist Things, " .
       "200=Refit type action, 400=Servicing<p>";
  echo "Do NOT change the project names - code depends on them<p>";

  echo "<form method=post action=ProjTypes.php>";
  if ($AllG) echo fm_hidden('AllGames',1);
  echo "<div class=tablecont><table id=indextable border>\n";
  echo "<thead><tr>";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Index</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Name</a>\n";
  if ($AllG) echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>NotBy</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Category</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Description</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Std Costs</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Level</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>CompTarget</a>\n";
 // echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Cost</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Props</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Based On</a>\n";


  echo "</thead><tbody>";
  foreach($DT as $D) {
    $i = $Did = $D['id'];
    echo "<tr><td>$i";
    echo fm_text1("",$D,'Name',1,'','',"Name$i");
    echo fm_notby($D,$i,$AllG);
    echo fm_number1("",$D,'Category','','',"Category$i");
    echo "<td>" . fm_basictextarea($D,'Description',1,1,"Description$i");
    echo fm_number1("",$D,'StandardCosts','','',"StandardCosts$i");
    echo fm_number1("",$D,'Level','','',"Level$i");
    echo fm_number1("",$D,'CompTarget','','',"CompTarget$i");
 //   echo fm_number1("",$D,'Cost','','',"Cost$i");
    echo fm_hex1("",$D,'Props','','',"Props$i");
    echo "<td>" . fm_select($TechNames,$D,'BasedOn',1,'',"BasedOn$i");
  }
  $D = [];
  echo "<tr><td><td><input type=text name=Name0 >";
  echo fm_hidden('NotBy0',$SETNOT);
  if ($AllG) echo "<td>$SETNOT";
  echo fm_number1("",$D,'Category','','',"Category0");
    echo "<td>" . fm_basictextarea($D,'Description',1,1,"Description0");
    echo fm_number1("",$D,'StandardCosts','','',"StandardCosts0");
    echo fm_number1("",$D,'Level','','',"Level0");
    echo fm_number1("",$D,'CompTarget','','',"CompTarget0");
//    echo fm_number1("",$D,'Cost','','',"Cost0");
    echo fm_hex1("",$D,'Props','','',"Props0");
    echo "<td>" . fm_select($TechNames,$D,'BasedOn',1,'',"BasedOn0");
  echo "</table></div>\n";
  echo "<input type=submit name=Update value=Update>\n";
  echo "</form></div>";

  dotail();

?>
