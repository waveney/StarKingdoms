<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("PlayerLib.php");
  global $NOTBY,$SETNOT,$GAMEID;

  $Traits = [];
  $TraitNames = [];
  $Facts = [];

  dostaffhead("All Planet Traits");

  $AllG = 0;
  if (isset($_REQUEST['AllGames'])) {
    echo "<div class=floatright><h2>Showing All Games - Switch to <a href=PlanetTraits.php>Current Game</a></h2></div>";
    echo "The current NotBy Mask is : $SETNOT<p>\n";
    $AllG = 1;
  } else {
    echo "<div class=floatright><h2>Showing current game -  Switch to <a href=PlanetTraits.php?AllGames>All Games</a></h2></div>";
  }

  echo "<h1>List of all System/Planet/Moon Traits</h1>";
  echo "Game No : System : Planet [Conceal] <b>(A)</b> = Automated<P>";

  $Systems = Get_Systems($AllG);
  $Planets = Gen_Get_Cond('Planets',($AllG?'id>0':"GameId=$GAMEID"));
  $Moons = Gen_Get_Cond('Moons',($AllG?'id>0':"GameId=$GAMEID"));
  $SysRefs = [];
  foreach($Systems as $Sid=>$F) {
    $SysRefs[$F['id']] = $Sid;
    if ($F['Trait1']) $TraitNames[$F['Trait1']][] = [$Sid,0,0,1, $F['Trait1Auto'], $F['Trait1Conceal']];
    if ($F['Trait2']) $TraitNames[$F['Trait2']][] = [$Sid,0,0,2, $F['Trait2Auto'], $F['Trait2Conceal']];
    if ($F['Trait3']) $TraitNames[$F['Trait3']][] = [$Sid,0,0,3, $F['Trait3Auto'], $F['Trait3Conceal']];
  };

  foreach($Planets as $Pid=>$F) {
  //  var_dump($F);
    if ($F['Trait1']) $TraitNames[$F['Trait1']][] = [$SysRefs[$F['SystemId']],$Pid,0,1, $F['Trait1Auto'], $F['Trait1Conceal']];
    if ($F['Trait2']) $TraitNames[$F['Trait2']][] = [$SysRefs[$F['SystemId']],$Pid,0,2, $F['Trait2Auto'], $F['Trait2Conceal']];
    if ($F['Trait3']) $TraitNames[$F['Trait3']][] = [$SysRefs[$F['SystemId']],$Pid,0,3, $F['Trait3Auto'], $F['Trait3Conceal']];
  };

  foreach($Moons as $Mid=>$F) {
    $Sid = $SysRefs[$Planets[$F['PlanetId']]['SystemId']];
    if ($F['Trait1']) $TraitNames[$F['Trait1']][] = [$Sid,$F['PlanetId'],$Mid,1, $F['Trait1Auto'], $F['Trait1Conceal']];
    if ($F['Trait2']) $TraitNames[$F['Trait2']][] = [$Sid,$F['PlanetId'],$Mid,2, $F['Trait2Auto'], $F['Trait2Conceal']];
    if ($F['Trait3']) $TraitNames[$F['Trait3']][] = [$Sid,$F['PlanetId'],$Mid,3, $F['Trait3Auto'], $F['Trait3Conceal']];
  };

  ksort($TraitNames);
  echo "<table border><tr><td>Name<Td>Use\n";

  foreach($TraitNames as $Name=>$TraitUse) {
    echo "<tr><td>$Name<td>";
    $use = 0;
    foreach($TraitUse as $Use) {
//      var_dump($Use);
      [$Sid,$Pid,$Mid,$TN,$Auto,$Conceal] = $Use;
      $Link = '';
      if ($Mid != 0) {
        $Link = "MoonEdit.php?id=$Mid";
        $Label = "Moon";
      } else if ($Pid != 0) {
        $Link = "PlanEdit.php?id=$Pid";
        $Label = "Planet";
      } else {
        $Link = "SysEdit.php?id=$Sid";
        $Label = "System";
      }
      echo ($use++?', ':'') . "$Label : <a href=$Link>$Sid</a> "
         . ($Conceal?"<span class=red>C:[$Conceal]</span>":" [0]") . ($Auto?" <b>(A)</b>":'') . "</a>";
    }
  }

  echo "</table>";

  dotail();

