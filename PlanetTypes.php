<?php
  include_once("sk.php");
  include_once("GetPut.php");
  A_Check('GM');

  dostaffhead("Manage Planet Types");

  $DT=Get_PlanetTypes();
  if (UpdateMany('PlanetTypes','Put_PlanetType',$DT,0))  $DT=Get_PlanetTypes();

  $coln = 0;
  
  echo "<form method=post action=PlanetTypes.php>";
  echo "<div class=tablecont><table id=indextable border>\n";
  echo "<thead><tr>";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Index</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Name</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Hospitable</a>\n";
  echo "</thead><tbody>";
  foreach($DT as $D) {
    $i = $Did = $D['id'];
    echo "<tr><td>$i" . fm_text1("",$D,'Name',1,'','',"Name$i");
    echo fm_number1("",$D,'Hospitable','','',"Hospitable$i");
  }
  echo "<tr><td><td><input type=text name=Name0 >";
  echo "<td><input type=number name=Hospitable0 value=0>"; 
  echo "</table></div>\n";
  echo "<input type=submit name=Update value=Update>\n";
  echo "</form></div>";

  dotail();

?>
