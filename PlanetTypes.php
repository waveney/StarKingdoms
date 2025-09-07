<?php
  include_once("sk.php");
  include_once("GetPut.php");
  A_Check('GM');

  global $NOTBY,$SETNOT;
  dostaffhead("Manage Planet Types");

  $AllG = 0;
  if (isset($_REQUEST['AllGames'])) {
    // Button for cur game
    // Show current NotBy Mask
    echo "<div class=floatright><h2>Showing All Games - Switch to <a href=PlanetTypes.php>Current Game</a></h2></div>";
    echo "The current NotBy Mask is : $SETNOT<p>\n";
    $AllG = 1;
  } else {
    echo "<div class=floatright><h2>Showing current game -  Switch to <a href=PlanetTypes.php?AllGames>All Games</a></h2></div>";
  }

  $DT=Get_PlanetTypes($AllG);
  if (UpdateMany('PlanetTypes','Put_PlanetType',$DT,0))  $DT=Get_PlanetTypes($AllG);

  $coln = 0;

  echo "<form method=post action=PlanetTypes.php>";
  if ($AllG) echo fm_hidden('AllGames',1);

  echo "Hospitable: 1 - starting Biospheres, 2= other hospitable, 3=Habitable - see below<p>";
  echo "Habitable is an interim state these will all become Temperate/Water/Desert/Desolte/Actic when anlysis complete<p>";

  echo "<div class=tablecont><table id=indextable border>\n";
  echo "<thead><tr>";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Index</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Name</a>\n";
  if ($AllG) echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>NotBy</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Hospitable</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Moon Factor</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Append Planet/Moon</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Image Prefix</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Mass Formula</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Density Formula</a>\n";
  echo "</thead><tbody>";
  foreach($DT as $D) {
    $i = $Did = $D['id'];
    echo "<tr><td>$i" . fm_text1("",$D,'Name',1,'','',"Name$i");

    if ($AllG) echo fm_number1('',$D,'NotBy','','',"NotBy$i");
    echo fm_number1("",$D,'Hospitable','','',"Hospitable$i");
    echo fm_number1("",$D,'MoonFactor','','',"MoonFactor$i");
    echo fm_number1("",$D,'Append','','',"Append$i");
    echo fm_text1("",$D,'ImgPrefix',1,'','',"ImgPrefix$i");
    echo fm_text1("",$D,'MassForm',1,'','',"MassForm$i");
    echo fm_text1("",$D,'DenForm',1,'','',"DenForm$i");
  }
  echo "<tr><td><td><input type=text name=Name0 >";
  echo fm_hidden('NotBy0',$SETNOT);
  if ($AllG) echo "<td>$SETNOT";
  echo "<td><input type=number name=Hospitable0 value=0>";
  echo "<td><input type=number name=MoonFactor0 value=0>";
  echo "<td><input type=number name=Append0 value=0>";
  echo "<td><input type=text name=ImgPrefix0>";
  echo "<td><input type=text name=MassForm0>";
  echo "<td><input type=text name=DenForm0>";
  echo "</table></div>\n";
  echo "<input type=submit name=Update value=Update>\n";
  echo "</form></div>";

  dotail();

?>
