<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");

  A_Check('GM');
  global $NOTBY,$SETNOT;

  dostaffhead("Variant Types");

  global $db, $GAME, $GAMEID,$NOTBY;
  global $ModFormulaes,$ModValues,$Fields,$Tech_Cats,$CivMil;
  $PTypes = ['Fixed Mod','% Mod','Value'];

// var_dump($_REQUEST);
  $AllG = 0;
  if (isset($_REQUEST['AllGames'])) {
    // Button for cur game
    // Show current NotBy Mask
    echo "<div class=floatright><h2>Showing All Games - Switch to <a href=ThingTypes.php>Current Game</a></h2></div>";
    echo "The current NotBy Mask is : $SETNOT<p>\n";
    $AllG = 1;
  } else {
    echo "<div class=floatright><h2>Showing current game -  Switch to <a href=ThingTypes.php?AllGames>All Games</a></h2></div>";
  }

  $Ts = Gen_Get_Cond('Variants', ($AllG?'id=id':" (NotBy&$NOTBY)=0") );
  if (UpdateMany('Variants','',$Ts,1,'','','Name','','','',':')) $Ts = Gen_Get_Cond('Variants', ($AllG?'id=id':" (NotBy&$NOTBY)=0") );
  echo "<h1>Variant Types</h1>";

//  echo "<h2 class=red>YOU NEED TO CLICK UPDATE ON THIS PAGE - Autoupdate does not work with Hex</h2>";

  echo "Props (Hex) bits 0-3 Devastation Effect (0=normal,1=none,2=double),<br>" .
       "16=Can operate on ground, 32=Not 1st round, 64=Grant evasion, 128=Only 1st Round, 256=No Devastation<p>\n";

  echo "Note for Firepower, Evasion and To Hit Modifier - there are 3 options.<br><ul><li>Fixed Mod - A fixed numerical modification<br>" .
    "<li>% Mod - % change to property<br><li>Value - Replaced by fixed number</ul><p>";
  echo "<form method=post action=VariantTypes.php>";
  Register_AutoUpdate('Generic', 0); // Not compatible with Hex

  if ($AllG) echo fm_hidden('AllGames',1);
    $coln = 0;
    echo "<div class=tablecont><table id=indextable border width=100% style='min-width:1400px'>\n";
    echo "<thead><tr>";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>id</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Name</a>\n";
    if ($AllG) echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>NotBy</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Props</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Firepower Type</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Firepower</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Evasion Type</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Evasion</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>To Hit Modifier Type</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>To Hit Modifier</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Shot Bonus Type</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Shot Bonus</a>\n";

    echo "</thead><tbody>";

    foreach($Ts as $T) {
      $i = $T['id'];
      echo "<tr><td>$i";
      echo fm_text1("",$T,'Name',1,'','',"Variants:Name:$i");
      echo fm_notby($T,$i,$AllG,':');
      echo fm_number1('',$T,'Props','','',"Variants:Props:$i");
      echo "<td>" . fm_select($PTypes,$T,'FireType',0,'',"Variants:FireType:$i");
      echo fm_number1('',$T,'Firepower','','min=-100 max=1000',"Variants:Firepower:$i");
      echo "<td>" . fm_select($PTypes,$T,'EvasionType',0,'',"Variants:EvasionType:$i");
      echo fm_number1('',$T,'Evasion','','min=-100 max=1000',"Variants:Evasion:$i");
      echo "<td>" . fm_select($PTypes,$T,'TargetType',0,'',"Variants:TargetType:$i");
      echo fm_number1('',$T,'TargetEvasion','','min=-100 max=1000',"Variants:TargetEvasion:$i");
      echo "<td>" . fm_select($PTypes,$T,'ShotType',0,'',"Variants:ShotType:$i");
      echo fm_number1('',$T,'ShotBonus','','min=-100 max=1000',"Variants:ShotBonus:$i");
    }

  $T = ['MaxLvl'=>10];
  echo "<tr><td>" . fm_text1("",$T,'Name',1,'','',"Variants:Name:0");
  echo fm_hidden('Variants:NotBy:0',$SETNOT);
  if ($AllG) echo "<td>$SETNOT";
  echo fm_number1('',$T,'Props','','',"Variants:Props:0");
  echo "<td>" . fm_select($PTypes,$T,'FireType',0,'',"Variants:FireType:0");
  echo fm_number1('',$T,'Firepower','','min=-100 max=1000',"Variants:Firepower:0");
  echo "<td>" . fm_select($PTypes,$T,'TargetType',0,'',"Variants:EvasionType:0");
  echo fm_number1('',$T,'Evasion','','min=-100 max=1000',"Variants:Evasion:0");
  echo "<td>" . fm_select($PTypes,$T,'EvasionType',0,'',"Variants:EvasionType:0");
  echo fm_number1('',$T,'TargetEvasion','','min=-100 max=1000',"Variants:EvasionEvasion:0");
  echo "<td>" . fm_select($PTypes,$T,'ShotType',0,'',"Variants:ShotType:0");
  echo fm_number1('',$T,'ShotBonus','','min=-100 max=1000',"Variants:ShotBonus:0");
  if (Access('God')) echo "<tr><td class=NotSide>Debug<td colspan=10 class=NotSide><textarea id=Debug></textarea>";

  echo "</tbody></table></div>\n";

  echo "<h2><input type=submit name=Update value=Update></h2>";
  echo "</form></div>";
  dotail();
?>
