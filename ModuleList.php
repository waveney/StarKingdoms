<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");

  A_Check('GM');

  dostaffhead("List Module Types");

  global $db, $GAME, $ModuleCats;
  $DefWep = ['','Defence','Weapon'];

  $MTs = Get_ModuleTypes();
  if (UpdateMany('ModuleTypes','Put_ModuleType',$MTs,0))    $MTs = Get_ModuleTypes();
  
  $Techs = Get_Techs();
  $TechNames = Tech_Names($Techs);
  $Forms = ModFormulaes();
  

  echo "<h1>Module Types</h1>";
  echo "Fire order: 5 = normal, 1 early, 9 late, -1 not first round.  Fire rate 1= every round, 0=once, 5=once every 5 rounds, -2 double first round<p>";
  echo "<form method=post action=ModuleList.php>";

  
    $coln = 0;
    echo "<div class=tablecont><table id=indextable border width=800 style='min-width:800'>\n";
    echo "<thead><tr>";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'n')>id</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Name</a>\n";
//    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Desc</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>CMA</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Wep / Def</a>\n";    
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Based on</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Space</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Min Ship Lvl</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>FireOrd</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>FireRate</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Formula</a>\n";
    echo "</thead><tbody>";

    foreach($MTs as $MT) {
      $i = $MT['id'];
      echo "<tr><td>$i" . fm_text1("",$MT,'Name',1,'','',"Name$i");
//      echo "<td>" . fm_basictextarea($MT,'Description',1,2,'',"Description$i");
      echo "<td>" . fm_select($ModuleCats,$MT,'CivMil',1,'',"CivMil$i");
      echo "<td>" . fm_select($DefWep,$MT,'DefWep',0,'',"DefWep$i");
      echo "<td>" . fm_select($TechNames,$MT,'BasedOn',1,'',"BasedOn$i");
      echo fm_number1("",$MT,'SpaceUsed','','',"SpaceUsed$i");
      echo fm_number1("",$MT,'MinShipLevel','','',"MinShipLevel$i");
      echo fm_number1("",$MT,'FireOrder','','',"FireOrder$i") . fm_number1("",$MT,'FireRate',"",'',"FireRate$i");
      echo "<td>" . fm_select($Forms,$MT,'Formula',1,'',"Formula$i");
      }   


  $MT = [];
  echo "<tr><td><td><input type=text name=Name0 >";
//  echo "<td>" . fm_basictextarea($MT,'Description',1,2,'',"Description0");
  echo "<td>" . fm_select($ModuleCats,$MT,'CivMil',1,'',"CivMil0");
  echo "<td>" . fm_select($DefWep,$MT,'DefWep',0,'',"DefWep0");
  echo "<td>" . fm_select($TechNames,$MT,'BasedOn',1,'',"BasedOn0");
  echo fm_number1("",$MT,'SpaceUsed','','',"SpaceUsed0");
  echo fm_number1("",$MT,'MinShipLevel','','',"MinShipLevel0");
  echo fm_number1("",$MT,'FireOrder',"",'',"FireOrder0") . fm_number1("",$MT,'FireRate',"",'',"FireRate0");     
  echo "<td>" . fm_select($Forms,$MT,'Formula',1,'',"Formula0");
  echo "</tbody></table></div>\n";

  echo "<h2><input type=submit name=Update value=Update></h2>";
  echo "</form></div>";
  dotail();
?>

