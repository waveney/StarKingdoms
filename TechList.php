<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");

  A_Check('GM');

  dostaffhead("List Technoiologies");

  global $db, $GAME, $ModuleCats;
  global $ModuleCats,$ModFormulaes,$ModValues,$Fields,$ShipTypes,$Tech_Cats,$CivMil;

  $Ts = Get_Techs();
  if (UpdateMany('Technologies','Put_Tech',$Ts,1))    $Ts = Get_Techs();

  $CTs = Get_CoreTechs();
  $CTNs = [];
  $CTNs[0] = '';
  foreach ($CTs as $T) $CTNs[$T['id']] = $T['Name'];

//var_dump($CTNs);
  
  echo "<h1>Technologies</h1>";
  echo "click on id to edit description<p>";
  echo "<form method=post action=TechList.php>";

  
    $coln = 0;
    echo "<div class=tablecont><table id=indextable border width=100% style='min-width:1400px'>\n";
    echo "<thead><tr>";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>id</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Cat</a>\n";
    echo "<th colspan=2><a href=javascript:SortTable(" . $coln++ . ",'T')>Name</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Field</a>\n";
    if ($CTs) echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>PreReq</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>PreReq Lvl</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Slots</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Min Level</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Civ Mil</a>\n";
    echo "</thead><tbody>";

    foreach($Ts as $T) {
      $i = $T['id'];
      echo "<tr><td><a href=TechEdit.php?id=$i>$i</a><td>" . fm_select($Tech_Cats,$T,'Cat',0,'',"Cat$i");
      echo fm_text1("",$T,'Name',2,'','',"Name$i");
      echo "<td>" . fm_select($Fields,$T,'Field',0,'',"Field$i");
      if ($CTs) echo "<td>" . fm_select($CTNs,$T,'PreReqTech',0,'',"PreReqTech$i");
      echo fm_number1('',$T,'PreReqLevel','','',"PreReqLevel$i");
      echo fm_number1("",$T,'Slots','','',"Slots$i");
      echo fm_number1("",$T,'MinThingLevel',"",'',"MinThingLevel$i");
      echo "<td>" . fm_select($CivMil,$T,'CivMil',0,'',"CivMil$i");
      echo fm_hidden("Description$i",$T['Description']);
      }   

  $T = ['PreReqLevel'=>0,'Slots'=>0,'MinThingLevel'=>0];
  echo "<tr><td><td>" . fm_select($Tech_Cats,$T,'Cat',0,'',"Cat0");
  echo fm_text1("",$T,'Name',2,'','',"Name0");
      echo "<td>" . fm_select($Fields,$T,'Field',1,'',"Field0");
      if ($CTs) echo "<td>" . fm_select($CTNs,$T,'PreReqTech',0,'',"PreReqTech0");
      echo fm_number1('',$T,'PreReqLevel','','',"PreReqLevel0");
      echo fm_number1("",$T,'Slots','','',"Slots0");
      echo fm_number1("",$T,'MinThingLevel','',"","MinThingLevel0");
      echo "<td>" . fm_select($CivMil,$T,'CivMil',0,'',"CivMil0");
  echo "</tbody></table></div>\n";

  echo "<h2><input type=submit name=Update value=Update></h2>";
  echo "</form></div>";
  dotail();
?>

