<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");

  A_Check('GM');
  global $NOTBY,$SETNOT;

  dostaffhead("List Technoiologies");

  global $db, $GAME, $ModuleCats;
  global $ModuleCats,$ModFormulaes,$ModValues,$Fields,$Tech_Cats,$CivMil;

  $AllG = 0;
  if (isset($_REQUEST['AllGames'])) {
    // Button for cur game
    // Show current NotBy Mask
    echo "<div class=floatright><h2>Showing All Games - Switch to <a href=TechList.php>Current Game</a></h2></div>";
    echo "The current NotBy Mask is : $SETNOT<p>\n";
    $AllG = 1;
  } else {
    echo "<div class=floatright><h2>Showing current game -  Switch to <a href=TechList.php?AllGames>All Games</a></h2></div>";
  }


  $Ts = Get_Techs(0,$AllG);

  $CTs = Get_CoreTechs($AllG);
  $CTNs = [];
  $CTNs[0] = '';
  foreach ($CTs as $T) $CTNs[$T['id']] = $T['Name'];
  $UseCount = [];
  $CivMil = Feature('CivMilShips');

  $Facts = Get_Factions($AllG,$AllG);
  $Techuses = Gen_Get_Basic_Table('FactionTechs');

  foreach ($Techuses as $T) {
    if (($T['Faction_Id']==0) || (!isset($Facts[$T['Faction_Id']]))) continue;
    $Tec = $T['Tech_Id'];
    $UseCount[$Tec] = ($UseCount[$Tec] ?? 0) + 1;
  }

  $MFN = ModFormulaes();
//var_dump($CTNs);

  echo "<h1>Technologies</h1>";
  echo "click on id to edit description<p>";
  echo "Properties: 1=Ground Combat, 2=Space Combat, 4=Espionage<p>\n";
  echo "<form method=post action=TechList.php>";
  if ($AllG) echo fm_hidden('AllGames',1);


    $coln = 0;
    echo "<div class=tablecont><table id=indextable border width=100% style='min-width:1400px'>\n";
    echo "<thead><tr>";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>id</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Cat</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Name</a>\n";
    if ($AllG) echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>NotBy</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Field</a>\n";
    if ($CTs) echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>PreReq</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>PreReq Lvl</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Properties</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Slots</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Min Level</a>\n";
    if ($CivMil) echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Civ Mil</a>\n";
//    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Formula</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Usage</a>\n";
    echo "</thead><tbody>";

    foreach($Ts as $T) {
      if (($T['Properties'] & 8) && !isset($_REQUEST['ALL'])) continue;
      $i = $T['id'];
      echo "<tr><td><a href=TechEdit.php?id=$i>$i</a>";
      echo "<td>" . $Tech_Cats[$T['Cat']];
      echo "<td><a href=TechEdit.php?id=$i>" . $T['Name'] . "</a>";
      if ($AllG) echo "<td>" . $T['NotBy'];
      echo "<td>" . $Fields[$T['Field']];
      if ($CTs) {
        if (isset($CTNs[$T['PreReqTech']])) {
          echo "<td>" . $CTNs[$T['PreReqTech']];
        } else {
          echo "<td class=Err>UNKNOWN";

        }
      }
      echo "<td>" . $T['PreReqLevel'];
      echo "<td>" . $T['Properties'];
      echo "<td>" . $T['Slots'];
      echo "<td>" . $T['MinThingLevel'];
      if ($CivMil) echo "<td>" . $CivMil[$T['CivMil']];
//      echo "<td>" . ($T['Formula']>0 ? $MFN[$T['Formula']]:'');
      echo "<td>" . ($UseCount[$i] ?? 0);

      }

  echo "</tbody></table></div>\n";

  echo "<h2><a href=TechEdit.php?id=0>New Tech</a></h2>";
  echo "</form></div>";
  dotail();
?>

