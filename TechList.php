<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");

  A_Check('GM');

  dostaffhead("List Technoiologies");

  global $db, $GAME, $ModuleCats;
  global $ModuleCats,$ModFormulaes,$ModValues,$Fields,$Tech_Cats,$CivMil;

  $Ts = Get_Techs();

  $CTs = Get_CoreTechs();
  $CTNs = [];
  $CTNs[0] = '';
  foreach ($CTs as $T) $CTNs[$T['id']] = $T['Name'];
  $UseCount = [];
  
  $Facts = Get_Factions();
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

  
    $coln = 0;
    echo "<div class=tablecont><table id=indextable border width=100% style='min-width:1400px'>\n";
    echo "<thead><tr>";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>id</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Cat</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Name</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Field</a>\n";
    if ($CTs) echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>PreReq</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>PreReq Lvl</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Properties</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Slots</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Min Level</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Civ Mil</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Formula</a>\n";
    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Usage</a>\n";
    echo "</thead><tbody>";

    foreach($Ts as $T) {
      if (($T['Properties'] & 8) && !isset($_REQUEST['ALL'])) continue;
      $i = $T['id'];
      echo "<tr><td><a href=TechEdit.php?id=$i>$i</a>";
      echo "<td>" . $Tech_Cats[$T['Cat']];
      echo "<td><a href=TechEdit.php?id=$i>" . $T['Name'] . "</a>";
      echo "<td>" . $Fields[$T['Field']];
      if ($CTs) echo "<td>" . $CTNs[$T['PreReqTech']];
      echo "<td>" . $T['PreReqLevel'];
      echo "<td>" . $T['Properties'];
      echo "<td>" . $T['Slots'];
      echo "<td>" . $T['MinThingLevel'];
      echo "<td>" . $CivMil[$T['CivMil']];
      echo "<td>" . ($T['Formula']>0 ? $MFN[$T['Formula']]:'');
      echo "<td>" . ($UseCount[$i] ?? 0);
      
      }   

  echo "</tbody></table></div>\n";

  echo "<h2><a href=TechEdit.php?id=0>New Tech</a></h2>";
  echo "</form></div>";
  dotail();
?>

