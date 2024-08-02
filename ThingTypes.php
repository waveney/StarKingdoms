<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");

  A_Check('GM');
  global $NOTBY,$SETNOT;

  dostaffhead("Thing Types");

  global $db, $GAME, $GAMEID;
  global $ModFormulaes,$ModValues,$Fields,$Tech_Cats,$CivMil;

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

  $Ts = Get_ThingTypes($AllG);
  if (UpdateMany('ThingTypes','Put_ThingType',$Ts,1,'','','Name','','Properties')) $Ts = Get_ThingTypes($AllG);

  echo "<h1>Thing Types</h1>";

  echo "Props (Hex) 1=Districts, 2=Modules, 4=Leveled, 8=Ship, 10=Gadgets, 20=Army, 40=Mil, 80=Civil, " .
       "100=Normal Move, 200=Army Move, 400= Two Factions, 800=Inherit Minerals,<br>" .
       "1000=Can be Advanced, 2000=Instant Create, 4000=Need Cargo Space, 8000=No Named Chars, " .
       "10000=Do Projects (Wo Dists), 20000=Direct Move, 40000=Move 2nd, 80000=Has Health<br>" .
       "100000=Has Control, 200000=Needs Support, 400000=Hostile, 800000=Can Splat, 1000000=Can Leave Debris," .
       "2000000=Is Small, 4000000=Blueprints<p>\n";
  echo "Eyes:  1 = in space, 2= sens, 4= neb sens, 8=ground 1, 16 ground 2 etc<br>\n"; // TODO no ground differntion yet
  echo "SeenBy: What Eyes can see this<br>\n";
//  echo "Game 0 = All, N = Game N<br>";
  echo "<form method=post action=ThingTypes.php>";
  if ($AllG) echo fm_hidden('AllGames',1);


    $coln = 0;
    echo "<div class=tablecont><table id=indextable border width=100% style='min-width:1400px'>\n";
    echo "<thead><tr>";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>id</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Name</a>\n";
    if ($AllG) echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>NotBy</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Properties</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Gate</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Eyes</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>SeenBy</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Max Level</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Evasion Mod</a>\n";
    //    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Game</a>\n";
    echo "</thead><tbody>";

    foreach($Ts as $T) {
      $i = $T['id'];
      echo "<tr><td><a href=TechEdit.php?id=$i>$i</a>";
      echo fm_text1("",$T,'Name',1,'','',"Name$i");
      echo fm_notby($T,$i,$AllG);
      echo fm_hex1('',$T,'Properties','','',"Properties$i");
      echo fm_text1("",$T,'Gate',1,'','',"Gate$i");
      echo fm_number1('',$T,'Eyes','','min=0 max=100',"Eyes$i");
      echo fm_number1('',$T,'SeenBy','','min=0 max=100',"SeenBy$i");
      echo fm_number1('',$T,'MaxLvl','','min=0 max=100',"MaxLvl$i");
      echo fm_number1('',$T,'EvasionMod','','min=0 max=100',"EvasionMod$i");
      echo fm_hidden("GameId$i",$T['GameId']);
      }

  $T = ['MaxLvl'=>10];
  echo "<tr><td>" . fm_text1("",$T,'Name',1,'','',"Name0");
  echo fm_hidden('NotBy0',$SETNOT);
  if ($AllG) echo "<td>$SETNOT";
  echo fm_hex1('',$T,'Properties','','',"Properties0");
      echo fm_text1("",$T,'Gate',1,'','',"Gate0");
      echo fm_number1('',$T,'Eyes','','min=0 max=100',"Eyes0");
      echo fm_number1('',$T,'SeenBy','','min=0 max=100',"SeenBy0");
      echo fm_number1('',$T,'MaxLvl','','min=0 max=100',"MaxLvl0");
      echo fm_number1('',$T,'EvasionMod','','min=0 max=100',"EvasionMod0");
      echo fm_hidden("GameId0",$GAMEID);
  echo "</tbody></table></div>\n";

  echo "<h2><input type=submit name=Update value=Update></h2>";
  echo "</form></div>";
  dotail();
?>
