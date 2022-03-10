<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  A_Check('GM');

  dostaffhead("Manage Project Types");

  $DT=Get_ProjectTypes();
  $Techs = Get_Techs();
  $TechNames = Tech_Names($Techs);

  
  if (UpdateMany('ProjectTypes','Put_ProjectType',$DT,0))  $DT=Get_ProjectTypes();

  $coln = 0;
  
  echo "Category 1=Academic,2=Ship Yard,4=Miltary,8=Intelligence,16=Construction, 32=Deep Space<p>";
  echo "Props 1=Can Rush, 2=Thing, 4=Two things, 8=Other Faction, 16=Tight Loc match, 32=Instruction<p>";
  
  echo "<form method=post action=ProjTypes.php>";
  echo "<div class=tablecont><table id=indextable border>\n";
  echo "<thead><tr>";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Index</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Name</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Category</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Description</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Std Costs</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Level</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>CompTarget</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Cost</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Props</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Based On</a>\n";


  echo "</thead><tbody>";
  foreach($DT as $D) {
    $i = $Did = $D['id'];
    echo "<tr><td>$i";
    echo fm_text1("",$D,'Name',1,'','',"Name$i");
    echo fm_number1("",$D,'Category','','',"Category$i");
    echo "<td>" . fm_basictextarea($D,'Description',1,1,"Description$i");
    echo fm_number1("",$D,'StandardCosts','','',"StandardCosts$i");
    echo fm_number1("",$D,'Level','','',"Level$i");
    echo fm_number1("",$D,'CompTarget','','',"CompTarget$i");
    echo fm_number1("",$D,'Cost','','',"Cost$i");
    echo fm_number1("",$D,'Props','','',"Props$i");   
    echo "<td>" . fm_select($TechNames,$D,'BasedOn',1,'',"BasedOn$i");
  }
  $D = [];
  echo "<tr><td><td><input type=text name=Name0 >";
    echo fm_number1("",$D,'Category','','',"Category0");
    echo "<td>" . fm_basictextarea($D,'Description',1,1,"Description0");
    echo fm_number1("",$D,'StandardCosts','','',"StandardCosts0");
    echo fm_number1("",$D,'Level','','',"Level0");
    echo fm_number1("",$D,'CompTarget','','',"CompTarget0");
    echo fm_number1("",$D,'Cost','','',"Cost0");
    echo fm_number1("",$D,'Props','','',"Props0");   
    echo "<td>" . fm_select($TechNames,$D,'BasedOn',1,'',"BasedOn0");
  echo "</table></div>\n";
  echo "<input type=submit name=Update value=Update>\n";
  echo "</form></div>";

  dotail();

?>
