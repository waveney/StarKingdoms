<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("PlayerLib.php");
  global $NOTBY,$SETNOT,$GAMEID;

  $Traits = [];
  $TraitNames = [];
  $Facts = [];

  dostaffhead("All Traits"); // TODO ONLY FACTION traits at the moment

  $AllG = 0;
  if (isset($_REQUEST['AllGames'])) {
    // Button for cur game
    // Show current NotBy Mask
    echo "<div class=floatright><h2>Showing All Games - Switch to <a href=ListTraits.php>Current Game</a></h2></div>";
    echo "The current NotBy Mask is : $SETNOT<p>\n";
    $AllG = 1;
  } else {
    echo "<div class=floatright><h2>Showing current game -  Switch to <a href=ListTraits.php?AllGames>All Games</a></h2></div>";
  }


  echo "<h1>List of all Traits</h1>";
  echo "Game No : Faction (:Cn) <b>(A)</b> = Automated - Cn means concealed value n<P>";

  $Factions = Gen_Get_Cond('Factions','id>0' . ($AllG?'':" AND GameId=$GAMEID"));
  foreach($Factions as $F) {
    $Fid = $F['id'];
    $Facts[$Fid] = $F;
    if ($F['Trait1']) $TraitNames[$F['Trait1']][] = [$Fid,1, $F['Trait1Auto'], $F['Trait1Conceal']];
    if ($F['Trait2']) $TraitNames[$F['Trait2']][] = [$Fid,2, $F['Trait2Auto'], $F['Trait2Conceal']];
    if ($F['Trait3']) $TraitNames[$F['Trait3']][] = [$Fid,3, $F['Trait3Auto'], $F['Trait3Conceal']];
  };

  ksort($TraitNames);
  echo "<table border><tr><td>Name<Td>Use\n";

  foreach($TraitNames as $Name=>$TraitUse) {
    echo "<tr><td>$Name<td>";
    $use = 0;
    foreach($TraitUse as $Use) {
      [$Fid,$TN,$Auto,$Conceal] = $Use;
      echo ($use++?', ':'') . "<a href=FactionEdit.php?F=$Fid>" . $Facts[$Fid]['GameId']  . ':' . $Facts[$Fid]['Name']
      . ($Conceal?"<span class=red>C:$Conceal</span>":"") . ($Auto?['','<b>(A)</b>','<b>(P)</b>'][$Auto]:'') . "</a>";
    }
  }

  echo "</table>";

  dotail();
?>

