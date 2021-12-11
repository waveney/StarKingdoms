<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");

  A_Check('GM');

  dostaffhead("List Module Types");

  global $db, $GAME, $ModuleCats;

  $MFs = Get_ModFormulaes();
  $MFN = ModFormulaes();
  if (UpdateMany('ModFormulae','Put_ModFormula',$MFs,1)) {
    Reset_ModFormulaes();
    $MFs = Get_ModFormulaes();
    $MFN = ModFormulaes();
// var_dump($MFs); var_dump($MFN);
  }   

  echo "<h1>Module Fomulas</h1>";
  echo "<form method=post action=ModFormulae.php>";

  
    $coln = 0;
    echo "<div class=tablecont><table id=indextable border width=100% style='min-width:1400px'>\n";
    echo "<thead><tr>";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'n')>id</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Name</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Fomulae</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Num1</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Num2</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Num3</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Base Tech</a>\n";
    echo "</thead><tbody>";

    foreach($MFs as $M) {
      $i = $M['id'];
      if (!isset($MFN[$i])) continue;
      echo "<tr><td>$i" . fm_text1("",$M,'Name',1,'','',"Name$i");
      echo "<td>" . $MFN[$i];
      echo fm_number1("",$M,'Num1x','','',"Num1x$i");
      echo fm_number1("",$M,'Num2x','','',"Num2x$i");
      echo fm_number1("",$M,'Num3x','','',"Num3x$i");
      echo fm_number1("",$M,'BaseTech','','',"BaseTech$i"); // Will be select later
      }   


  echo "<tr><td>";
  $M = [];
  echo fm_text1("",$M,'Name',1,'','',"Name0") . "<td>";
  echo fm_number1("",$M,'Num1x','','',"Num1x0");
  echo fm_number1("",$M,'Num2x','','',"Num2x0");
  echo fm_number1("",$M,'Num3x','','',"Num3x0");
  echo fm_number1("",$M,'BaseTech','','',"BaseTech0");     

  echo "</tbody></table></div>\n";

  echo "<h2><input type=submit Name=Update value=Update></h2>";
  echo "</form></div>";
  dotail();
?>

