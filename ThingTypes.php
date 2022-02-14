<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");

  A_Check('GM');

  dostaffhead("Thing Types");

  global $db, $GAME, $ModuleCats;
  global $ModuleCats,$ModFormulaes,$ModValues,$Fields,$Tech_Cats,$CivMil;

  $Ts = Get_ThingTypes();
  if (UpdateMany('ThingTypes','Put_ThingType',$Ts,1))    $Ts = Get_ThingTypes();

  echo "<h1>Thing Types</h1>";
  
  echo "Props 1=Districts, 2=Modules, 4=Leveled, 8=Ship, 16=Gadgets, 32=Army, 64=Mil, 128=Civil, 256=Normal Move, 512=Army Move, 1024= Two Factions<br>\n";
  echo "Eyes:  1 = in space, 2= sens, 4= neb sens, 8=ground 1, 16 ground 2 etc<br>\n"; // TODO no ground differntion yet 
  echo "SeenBy: What Eyes can see this<br>\n";
  echo "<form method=post action=ThingTypes.php>";

  
    $coln = 0;
    echo "<div class=tablecont><table id=indextable border width=100% style='min-width:1400px'>\n";
    echo "<thead><tr>";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>id</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Name</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Properties</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Gate</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Eyes</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>SeenBy</a>\n";
    echo "</thead><tbody>";

    foreach($Ts as $T) {
      $i = $T['id'];
      echo "<tr><td><a href=TechEdit.php?id=$i>$i</a>";
      echo fm_text1("",$T,'Name',1,'','',"Name$i");
      echo fm_number1('',$T,'Properties','','',"Properties$i");
      echo fm_text1("",$T,'Gate',1,'','',"Gate$i");
      echo fm_number1('',$T,'Eyes','','',"Eyes$i");
      echo fm_number1('',$T,'SeenBy','','',"SeenBy$i");
      }   

  $T = [];
  echo "<tr><td>" . fm_text1("",$T,'Name',1,'','',"Name0");
      echo fm_number1('',$T,'Properties','','',"Properties0");
      echo fm_text1("",$T,'Gate',1,'','',"Gate0");
      echo fm_number1('',$T,'Eyes','','',"Eyes0");
      echo fm_number1('',$T,'SeenBy','','',"SeenBy0");
  echo "</tbody></table></div>\n";

  echo "<h2><input type=submit name=Update value=Update></h2>";
  echo "</form></div>";
  dotail();
?>
