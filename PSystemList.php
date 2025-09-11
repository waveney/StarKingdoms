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

$Details = []; $ODetails = []; $Windxs = [];

$Worlds = Get_Worlds($Fid);
foreach ($Worlds as $Wid=>$W) {
  $Home = Get_ProjectHome($W['Home']);
  $Details[$Home['SystemId']] = 1;
  $Windxs[$Wid] = $Home['SystemId'];
}

if (Has_Trait($Fid,'Goverwhat now?')) {
  $Offices = Gen_Get_Cond('Offices',"Whose=$Fid");
  foreach ($Offices as $O) {
    if (!isset($Windxs[$O['World']])) {
      $W = Get_World($O['World']);
      $Home = Get_ProjectHome($W['Home']);
      $Details[$Home['SystemId']] = 1;
      $Windxs[$Wid] = $Home['SystemId'];
    }
  }
}

$Branches = Gen_Get_Cond("Branches","Whose=$Fid");
foreach ($Branches as $B) {
  switch ($B['HostType']) {
    case 1://Planet
      $P = Get_Planet($B['HostId']);
      $Sid = $P['SystemId'];
      $Details[$Sid] = 1;
      break;
    case 2://Moon
      $M = Get_Moon($B['HostId']);
      $P = Get_Planet($M['PlanetId']);
      $Sid = $P['SystemId'];
      $Details[$Sid] = 1;
      break;
    case 3://Thing
      $T = Get_Thing($B['HostId']);
      $Sid = $T['SystemId'];
      $ODetails[$Sid] = 1;
      break;
  }
}


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

echo "World details are where you have branches, offices or districts on the ground - lots of info.<P>" .
     "Outpost details currently only gives you details of other branches on the outpost.<p>";

echo "<div class=tablecont><table class=striped id=indextable border>\n";
echo "<thead><tr>";
echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Ref</a>\n";
echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Name</a>\n";
echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Whose</a>\n";
echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Space Scan</a>\n";
echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Planet Scan</a>\n";
echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>World Details</a>\n";
echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Outpost Details</a>\n";
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
    echo "<td " . FactColours($N['Control']) . ">" . $Facts[$N['Control']]['Name'];
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
  echo "<td>" . (empty($Details[$Sid])?'':"<a href=Details.php?R=$R>Details</a>");
  echo "<td>" . (empty($ODetails[$Sid])?'':"<a href=Details.php?O=$R>Details</a>");
  echo "<td>" . floor($N['GridX']) . "," . floor($N['GridY']);
}

echo "</table></div><p>";

dotail();
