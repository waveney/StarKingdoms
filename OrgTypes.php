<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  A_Check('GM');

//  var_dump($_REQUEST);echo"<p>";
  global $NOTBY,$SETNOT;
  dostaffhead("Manage Organisation Types");

  $AllG = 0;
  if (isset($_REQUEST['AllGames'])) {
    // Button for cur game
    // Show current NotBy Mask
    echo "<div class=floatright><h2>Showing All Games - Switch to <a href=OrgTypes.php>Current Game</a></h2></div>";
    echo "The current NotBy Mask is : $SETNOT<p>\n";
    $AllG = 1;
  } else {
    echo "<div class=floatright><h2>Showing current game -  Switch to <a href=OrgTypes.php?AllGames>All Games</a></h2></div>";
  }

  $DTs=Get_OrgTypes($AllG);
//  $Techs = Get_Techs(0,$AllG);
//  $TechNames = Tech_Names($Techs);

  if (UpdateMany('OfficeTypes','Put_OrgType',$DTs,1))  $DTs=Get_OrgTypes($AllG);

  $coln = 0;
echo "Do NOT change the shortnames - code depends on them<p>";
//  echo "Category 1=Academic,2=Ship Yard,4=Miltary,8=Intelligence,16=Construction, 32=Deep Space<p>";
  echo "Props TBD<p>";
  echo "Do NOT change the Org short names - code depends on them<p>";

  echo "<form method=post>";
  if ($AllG) echo fm_hidden('AllGames',1);
  echo "<div class=tablecont><table id=indextable border>\n";
  echo "<thead><tr>";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Index</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Name</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Short Name</a>\n";
  if ($AllG) echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>NotBy</a>\n";
//  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Category</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Description</a>\n";
//  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Std Costs</a>\n";
//  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Level</a>\n";
//  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>CompTarget</a>\n";
//  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Cost</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Props</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Colour</a>\n";


  echo "</thead><tbody>";
  if ($DTs) foreach($DTs as $D) {
    $i = $Did = $D['id'];
    echo "<tr><td>$i";
    echo fm_text1("",$D,'Name',1,'','',"Name$i");
    echo fm_text1("",$D,'ShortName',1,'','',"ShortName$i");
    echo fm_notby($D,$i,$AllG);
//    echo fm_number1("",$D,'Category','','',"Category$i");
    echo "<td>" . fm_basictextarea($D,'Description',1,1,"Description$i");
//    echo fm_number1("",$D,'StandardCosts','','',"StandardCosts$i");
//    echo fm_number1("",$D,'Level','','',"Level$i");
//    echo fm_number1("",$D,'CompTarget','','',"CompTarget$i");
//    echo fm_number1("",$D,'Cost','','',"Cost$i");
    echo fm_number1("",$D,'Props','','',"Props$i");
    echo fm_text1("",$D,'Colour',1,'','',"Colour$i");
  }
  $D = [];
  echo "<tr><td><td><input type=text name=Name0 >";
  echo "<td><input type=text name=ShortName0 >";
  echo fm_hidden('NotBy0',$SETNOT);
  if ($AllG) echo "<td>$SETNOT";
//  echo fm_number1("",$D,'Category','','',"Category0");
    echo "<td>" . fm_basictextarea($D,'Description',1,1,"Description0");
//    echo fm_number1("",$D,'StandardCosts','','',"StandardCosts0");
//    echo fm_number1("",$D,'Level','','',"Level0");
//    echo fm_number1("",$D,'CompTarget','','',"CompTarget0");
//    echo fm_number1("",$D,'Cost','','',"Cost0");
    echo fm_number1("",$D,'Props','','',"Props0");
    echo "<td><input type=text name=Colour0 >";
    echo "</table></div>\n";
  echo "<input type=submit name=Update value=Update>\n";
  echo "</form></div>";

  dotail();

?>
