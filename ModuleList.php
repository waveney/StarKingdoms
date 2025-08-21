<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");

  A_Check('GM');
  global $NOTBY,$SETNOT;

  dostaffhead("List Module Types");

  global $db, $GAME;
  $DefWep = ['','Defence','Weapon','Shield'];
  $ModuleCats = ModuleCats();

  $AllG = 0;
  if (isset($_REQUEST['AllGames'])) {
    // Button for cur game
    // Show current NotBy Mask
    echo "<div class=floatright><h2>Showing All Games - Switch to <a href=ModuleList.php>Current Game</a></h2></div>";
    echo "The current NotBy Mask is : $SETNOT<p>\n";
    $AllG = 1;
  } else {
    echo "<div class=floatright><h2>Showing current game -  Switch to <a href=ModuleList.php?AllGames>All Games</a></h2></div>";
  }

  $MTs = Get_ModuleTypes($AllG);
  if (UpdateMany('ModuleTypes','Put_ModuleType',$MTs,0,'','','Name','','Leveled','',':'))    $MTs = Get_ModuleTypes($AllG);
  //function UpdateMany($table,$Putfn,&$data,$Deletes=1,$Dateflds='',$Timeflds='',$Mstr='Name',$MstrNot='',$Hexflds='',$MstrChk='',$Sep='') {

  $Techs = Get_Techs(0,$AllG);
  $TechNames = Tech_Names($Techs,$AllG);
  $TechNames[0] = 'Nothing';
  $Forms = ModFormulaes();
  $Slots = Feature('ModuleSlots');

  echo "<h1>Module Types</h1>";
  echo "Fire order: 5 = normal, 1 early, 9 late, -1 not first round.  Fire rate 1= every round, 0=once, 5=once every 5 rounds, -2 double first round<p>";
  echo "Properties: 1 = Leveled, 2=Non Std Def, 4=Non Std Atk, 8=Blueprints only - invalid real things, 10=Not Visible unless have based on tech<br>";
  echo "100=Need 1 Curn1, 200=Need 1 Curn2, 400=Need 1 Curn3<p>";
  echo "Click on the Desc by each type to edit the description.<p>";

  echo "If Evasion or To Hit is -100 it will use a formula<p>";

  echo "<form method=post action=ModuleList.php>";
  Register_AutoUpdate('Generic', 0);
  if ($AllG) echo fm_hidden('AllGames',1);

    $coln = 0;
    echo "<div class=tablecont><table id=indextable border width=800 style='min-width:800'>\n";
    echo "<thead><tr>";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'n')>id</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Name</a>\n";
    if ($AllG) echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>NotBy</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>CMA</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Wep / Def</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Based on</a>\n";
    if ($Slots) echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Space</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Min Ship Lvl</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>FireOrd</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>FireRate</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Formula</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Properties</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Evasion Mod</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>To Hit Mod</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Extra Cost</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Desc</a>\n";

    echo "</thead><tbody>";

    foreach($MTs as $MT) {
      $i = $MT['id'];
      echo "<tr><td>$i" . fm_text1("",$MT,'Name',1,'','',"ModuleTypes:Name:$i") . fm_hidden("ModuleTypes:Description:$i",$MT['Description']);
      echo fm_notby($MT,$i,$AllG,':','ModuleTypes');
      echo "<td>" . fm_select($ModuleCats,$MT,'CivMil',1,'',"ModuleTypes:CivMil:$i");
      echo "<td>" . fm_select($DefWep,$MT,'DefWep',0,'',"ModuleTypes:DefWep:$i");
      echo "<td>" . fm_select($TechNames,$MT,'BasedOn',1,'',"ModuleTypes:BasedOn:$i");
      if ($Slots) echo fm_number1("",$MT,'SpaceUsed','','min=0 max=10',"ModuleTypes:SpaceUsed:$i");
      echo fm_number1("",$MT,'MinShipLevel','','min=0 max=10',"Modules:MinShipLevel$i");
      echo fm_number1("",$MT,'FireOrder','','min=-5 max=10',"ModuleTypes:FireOrder:$i") .
           fm_number1("",$MT,'FireRate',"",'min=0 max=10',"ModuleTypes:FireRate:$i");
      echo "<td>" . fm_select($Forms,$MT,'Formula',1,'',"ModuleTypes:Formula:$i");
      echo fm_hex1("",$MT,'Leveled','','',"ModuleTypes:Leveled:$i");
      echo fm_number1("",$MT,'EvasionMod','','min=-100 max=100',"ModuleTypes:EvasionMod:$i");
      echo fm_number1("",$MT,'ToHitMod','','min=-100 max=100',"ModuleTypes:ToHitMod:$i");
      echo fm_number1('',$MT,'ExtraCost','','min=0 max=100',"ModuleTypes:ExtraCost:$i");
      echo "<td><a href=ModuleEdit.php?id=$i>Desc</a>";
    }

  $MT = [];
  echo "<tr><td><td><input type=text name=ModuleTypes:Name:0 >";
//  echo "<td>" . fm_basictextarea($MT,'Description',1,2,'',"Description0");
  echo fm_hidden('ModuleTypes:NotBy:0',$SETNOT);
  if ($AllG) echo "<td>$SETNOT";
  echo "<td>" . fm_select($ModuleCats,$MT,'CivMil',1,'',"ModuleTypes:CivMil:0");
  echo "<td>" . fm_select($DefWep,$MT,'DefWep',0,'',"ModuleTypes:DefWep:0");
  echo "<td>" . fm_select($TechNames,$MT,'BasedOn',1,'',"ModuleTypes:BasedOn:0");
  if ($Slots) echo fm_number1("",$MT,'SpaceUsed','','min=0 max=10',"ModuleTypes:SpaceUsed:0");
  echo fm_number1("",$MT,'MinShipLevel','','min=0 max=10',"ModuleTypes:MinShipLevel:0");
  echo fm_number1("",$MT,'FireOrder',"",'min=-5 max=10',"ModuleTypes:FireOrder:0") . fm_number1("",$MT,'FireRate',"",'min=0 max=10',"ModuleTypes:FireRate:0");
  echo "<td>" . fm_select($Forms,$MT,'Formula',1,'',"ModuleTypes:Formula:0");
  echo fm_number1("",$MT,'Leveled','','min=0 max=100',"ModuleTypes:Leveled:0");
  echo fm_number1("",$MT,'EvasionMod','','min=-100 max=100',"ModuleTypes:EvasionMod:0");
  echo fm_number1("",$MT,'ToHitMod','','min=-100 max=100',"ModuleTypes:ToHitMod:0");
  echo fm_number1('',$MT,'ExtraCost','','min=0 max=100',"ModuleTypes:ExtraCost:0");
  echo "</tbody></table></div>\n";

  if (Access('God')) {
    echo "<table border>";
    echo "<tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea>";
    echo "</table>";
  }


  echo "<h2><input type=submit name=Update value=Update></h2>";
  echo "</form></div>";
  dotail();
?>

