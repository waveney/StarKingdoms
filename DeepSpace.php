<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  
  A_Check('GM');

  dostaffhead("List Deep Space Prrojects");

  global $db, $GAME,$BuildState,$ShipTypes;

  $Projs = Get_DeepSpaces();

  if (UpdateMany('DeepSpaceProjects','Put_DeepSpace',$Projs,0)) $Projs = Get_DeepSpaces();

  $Techs = Get_Techs();
  $TechNames = Tech_Names($Techs);
  $Forms = ModFormulaes();

 
  echo "<h1>Deep Space Construction Projects</h1>";

  echo "<form method=post action=DeepSpace.php>";
  echo "Non public projects only seen if the faction has relevant tech<p>";
  
    $coln = 0;
    echo "<div class=tablecont><table id=indextable border width=800 style='min-width:800'>\n";
    echo "<thead><tr>";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'n')>id</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Name</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Level</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Tech</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Const Rating</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Public</a>\n";
    echo "</thead><tbody>";

    foreach($Projs as $P) {
      $i = $P['id'];
      echo "<tr><td>$i" . fm_text1("",$P,'Name',1,'','',"Name$i");
      echo fm_number1("",$P,'Level','','',"Level$i");
      echo "<td>" . fm_select($TechNames,$P,'PreReqTech',1,'',"PreReqTech$i");

      echo fm_number1("",$P,'ConstRating','','',"ConstRating$i");
      echo fm_number1("",$P,'Public','','',"Public$i");
      }   

  $P = [];
  echo "<tr><td><td><input type=text name=Name0 >";
  echo fm_number1("",$P,'Level','','',"Level0");
  echo "<td>" . fm_select($TechNames,$P,'PreReqTech',1,'',"PreReqTech0");
  echo fm_number1("",$P,'ConstRating','','',"ConstRating0");
  echo fm_number1("",$P,'Public',"",'',"Public0");  
  echo "</tbody></table></div>\n";

  echo "<h2><input type=submit name=Update value='Create / Update'></h2>";
  echo "</form></div>";



  dotail();
?>
