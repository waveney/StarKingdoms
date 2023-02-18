<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  A_Check('GM');

  dostaffhead("Manage District Types");

  $DT=Get_DistrictTypes();
  if (UpdateMany('DistrictTypes','Put_DistrictType',$DT,1))  $DT=Get_DistrictTypes();

  $Techs = Get_Techs();
  $TechNames = Tech_Names($Techs);

  $coln = 0;
  
  echo "Properties 1 = Prime, 2= Do projects, 4=Mining Income, 8=Don't have levels, 16=Valid on Space stations<p>\n";
  
  echo "<form method=post action=DTList.php>";
  echo "<div class=tablecont><table id=indextable border>\n";
  echo "<thead><tr>";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Index</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Name</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Properties</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Enabling Tech</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Gate</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Notes</a>\n";
  echo "</thead><tbody>";
  foreach($DT as $D) {
    $i = $Did = $D['id'];
    echo "<tr><td>$i" . fm_text1("",$D,'Name',1,'','',"Name$i");
    echo fm_number1('',$D,'Props','','',"Props$i");
    echo "<td>" . fm_select($TechNames,$D,'BasedOn',1,'',"BasedOn$i");
    echo fm_text1("",$D,'Gate',1,'','',"Gate$i");
    echo fm_text1('',$D,'Notes',1,'','',"Notes$i");
  }
  echo "<tr><td><td><input type=text name=Name0 >";
  echo "<td><input type=number name=Props0 >";
  echo "<td>" . fm_select($TechNames,$D,'BasedOn',1,'',"BasedOn0");
  echo "<td><input type=text name=Gate0 >";
  echo "<td><input type=text name=Notes0 >";
  echo "</table></div>\n";
  echo "<input type=submit name=Update value=Update>\n";
  echo "</form></div>";

  dotail();

?>

