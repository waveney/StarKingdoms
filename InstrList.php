<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");

  A_Check('GM');

  dostaffhead("Thing Types");

  global $db, $GAME, $ModuleCats;
  global $ModuleCats,$ModFormulaes,$ModValues,$Fields,$Tech_Cats,$CivMil;

  $Is = Get_Instructions();
  if (UpdateMany('Instructions','Put_Instruction',$Is,1)) $Is = Get_Instructions();

  echo "<h1>Instructions</h1>";
  
  echo "Props 1=Ships<p>";
  echo "<form method=post action=InstrList.php>";

  
    $coln = 0;
    echo "<div class=tablecont><table id=indextable border width=100% style='min-width:1400px'>\n";
    echo "<thead><tr>";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>id</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Name</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Props</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Actions</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Cost</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Gate</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Message</a>\n";
    echo "</thead><tbody>";

    foreach($Is as $I) {
      $i = $I['id'];
      echo "<tr><td>$i";
      echo fm_text1("",$I,'Name',1,'','',"Name$i");
      echo fm_number1('',$I,'Props','','',"Props$i");
      echo fm_number1('',$I,'Actions','','',"Actions$i");
      echo fm_number1('',$I,'Cost','','',"Cost$i");
      echo fm_text1("",$I,'Gate',1,'','',"Gate$i");
      echo fm_text1("",$I,'Message',1,'','',"Message$i");
      }   

  $I = []; $i = 0;
  echo "<tr><td>";
  
      echo fm_text1("",$I,'Name',1,'','',"Name$i");
      echo fm_number1('',$I,'Props','','',"Props$i");
      echo fm_number1('',$I,'Actions','','',"Actions$i");
      echo fm_number1('',$I,'Cost','','',"Cost$i");
      echo fm_text1("",$I,'Gate',1,'','',"Gate$i");
      echo fm_text1("",$I,'Message',1,'','',"Message$i");

  echo "</tbody></table></div>\n";

  echo "<h2><input type=submit name=Update value=Update></h2>";
  echo "</form></div>";
  dotail();
?>
