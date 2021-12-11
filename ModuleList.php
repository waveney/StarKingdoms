<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");

  A_Check('GM');

  dostaffhead("List Module Types");

  global $db, $GAME, $ModuleCats;

  $MTs = Get_ModuleTypes();
  if (UpdateMany('ModuleTypes','Put_ModuleType',$MTs,0))    $MTs = Get_ModuleTypes();

  echo "<h1>Module Types</h1>";
  echo "<form method=post action=ModuleList.php>";

  
    $coln = 0;
    echo "<div class=tablecont><table id=indextable border width=100% style='min-width:1400px'>\n";
    echo "<thead><tr>";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'n')>id</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Name</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Desc</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>CMA</a>\n";
//    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Base</a>\n";
    echo "<th width=50><a href=javascript:SortTable(" . $coln++ . ",'N')>Space</a>\n";
    echo "<th width=50><a href=javascript:SortTable(" . $coln++ . ",'N')>Def</a>\n";
    echo "<th width=50><a href=javascript:SortTable(" . $coln++ . ",'N')>Atk</a>\n";
    echo "<th width=50><a href=javascript:SortTable(" . $coln++ . ",'N')>PrioT</a>\n";
    echo "<th width=50><a href=javascript:SortTable(" . $coln++ . ",'N')>PrioV</a>\n";
    echo "<th width=50><a href=javascript:SortTable(" . $coln++ . ",'N')>FireOrd</a>\n";
    echo "<th width=50><a href=javascript:SortTable(" . $coln++ . ",'N')>FireRate</a>\n";
    echo "</thead><tbody>";

    foreach($MTs as $MT) {
      $i = $MT['id'];
      echo "<tr><td>$i" . fm_text1("",$MT,'Name',1,'','',"Name$i");
      echo "<td>" . fm_basictextarea($MT,'Description',1,2,'',"Description$i");
      echo fm_select($ModuleCats,'',$MT,'CivMil',1,"CivMil$i");
//      echo "<td>?";
      echo fm_number1("",$MT,'SpaceUsed',"width=50","SpaceUsed$i");
      echo fm_number1("",$MT,'DefenceVal',"width=50","DefenceVal$i") . fm_number1("",$MT,'AttackVal',"width=50","AttackVal$i");
      echo fm_number1("",$MT,'PriorityType',"width=50","PriorityType$i") . fm_number1("",$MT,'PriorityEffect',"width=50","PriorityEffect$i");
      echo fm_number1("",$MT,'FireOrder',"width=50","FireOrder$i") . fm_number1("",$MT,'FireRate',"width=50","FireRate$i");     
      }   


  echo "<tr><td><td><input type=text name=Name0 >";
  echo "<td>" . fm_basictextarea($MT,'Description',1,2,'',"Description0");
  echo "<td>" . fm_select($ModuleCats,'',$MT,'CivMil',1,"CivMil0");
//  echo "<td>?";
  echo fm_number1("",$MT,'SpaceUsed',"width=50","SpaceUsed0");
  echo fm_number1("",$MT,'DefenceVal',"width=50","DefenceVal0") . fm_number1("",$MT,'AttackVal',"width=50","AttackVal0");
  echo fm_number1("",$MT,'PriorityType',"width=50","PriorityType0") . fm_number1("",$MT,'PriorityEffect',"width=50","PriorityEffect0");
  echo fm_number1("",$MT,'FireOrder',"width=50","FireOrder0") . fm_number1("",$MT,'FireRate',"width=50","FireRate0");     

  echo "</tbody></table></div>\n";

  echo "<h2><input type=submit name=Update value=Update></h2>";
  echo "</form></div>";
  dotail();
?>

