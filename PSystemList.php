<?php
include_once("sk.php");
include_once("GetPut.php");
include_once("ThingLib.php");
include_once("PlayerLib.php");
include_once("SystemLib.php");
include_once("ProjLib.php");
include_once("OrgLib.php");

global $FACTION,$GAMEID,$ARMIES;

A_Check('Player');
$Facts = Get_Factions();
$Fid = $FACTION['id'];
$Systems = Gen_Get_Cond('Systems',"GameId=$GAMEID");

dostaffhead("Known Systems");

$FSs = Gen_Get_Cond('FactionSystem',"FactionId=$Fid");

//var_dump($Systems);
$OFSs = [];
foreach ($FSs as $i=>$FS) {
  if (!isset($Systems[$FS['SystemId']]) || $FS['ScanLevel']<0) continue;
  $N = $Systems[$FS['SystemId']];
  $FS['FName'] = System_Name($N,$Fid);
  $OFSs[$N['Ref']] = $FS;
}
$PlanetTypes = Get_PlanetTypes();

$coln = 0;

echo "<div class=tablecont><table class=striped id=indextable border>\n";
echo "<thead><tr>";
echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Ref</a>\n";
echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Name</a>\n";
echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Whose</a>\n";
echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Space Scan</a>\n";
echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Planet Scan</a>\n";
echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Grid</a>\n";
echo "</thead><tbody>";

ksort($OFSs);
foreach($OFSs as $R=>$FS) {
  $N = $Systems[$FS['SystemId']];

  $Sid = $N['id'];
  $Hospitable = 0;
  $Planets = Get_Planets($Sid);
  foreach ($Planets as $Pid=>$P) {
    if ($PlanetTypes[$P['Type']]['Hospitable']) {
      $Hospitable = 1;
      break;
    }
    $Moons = Get_Moons($Pid);
    foreach ($Moons as $Mid=>$M) {
      if ($PlanetTypes[$M['Type']]['Hospitable']) {
        $Hospitable = 1;
        break 2;
      }
    }
  }

  echo "<tr><td><a href=SurveyReport.php?R=$R>$R</a><td>" . ($Hospitable?'<b>':'') . "<a href=SurveyReport.php?R=$R>" .
    ($FS['Name']??$FS['FName']) . "</a>" . ($Hospitable?'</b>':'');
  if ($N['Control']) {
    echo "<td style='background:" . $Facts[$N['Control']]['MapColour']. "'>" . $Facts[$N['Control']]['Name'];
  } else {
    echo "<td>";
  }
  if ($FS['SpaceScan']>0) {
    echo "<td>L" . $FS['SpaceScan'] . " T" . $FS['SpaceTurn'];
  } else {
    echo "<td>-";
  }
  if ($FS['PlanetScan']>0) {
    echo "<td>L" . $FS['PlanetScan'] . " T" . $FS['PlanetTurn'];
  } else {
    echo "<td>-";
  }
  echo "<td>" . floor($N['GridX']) . "," . floor($N['GridY']);
}

echo "</table></div><p>";

dotail();
