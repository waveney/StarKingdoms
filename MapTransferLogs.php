<?php
include_once("sk.php");
include_once("SystemLib.php");
include_once("PlayerLib.php");
global $FACTION,$GAME;

dostaffhead('Mapping Transfer Logs');

$Fid = 0;
//var_dump($_COOKIE,$_REQUEST);
A_Check('Player');
if (Access('Player')) {
  if (!$FACTION) {
    if (!Access('GM') ) Error_Page("Sorry you need to be a GM or a Player to access this");
  } else {
    $Fid = $FACTION['id'];
    $F = $FACTION;
  }
}
$GM = (Access('GM') && ! isset($_REQUEST['FORCE'])) ;
if ($GM) {
  $Fid = ($FACTION['id']??0);
  $F = $FACTION;
}
if (!$Fid) {
  echo "No faction selected<p>";
  dotail();
}

$Facts = Get_Factions();
$SRefs = Get_SystemRefs();
$FSs = Gen_Get_Cond('FactionSystem', "FactionId=$Fid");
$WRefs = [];
foreach ($FSs as $FS) {
  $WRefs[$FS['SystemId']] = $SRefs[$FS['SystemId']];
}
asort($WRefs);

$OutLogs = Gen_Get_Cond('TransferLog',"FromFact=$Fid");
$InLogs = Gen_Get_Cond('TransferLog',"DestFact=$Fid");

// var_dump($OutLogs,$InLogs);

if ($InLogs) {
  echo "<h1>Incoming Transfers</h1>";
  $coln = 0;

  echo "<div class=tablecont><table class=striped id=indextable border>\n";
  echo "<thead><tr>";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>From</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>System</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Survey Data</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Turn</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>When</a>\n";
  echo "</thead><tbody>";

  foreach ($InLogs as $Tr) {
    echo "<tr><td style=Background:" . $Facts[$Tr['FromFact']]['MapColour'] . ">" .$Facts[$Tr['FromFact']]['Name'];
    echo "<td>" . $WRefs[$Tr['SystemId']] . "<td>" . ($Tr['Survey']?'Yes':'No');
    echo "<td>" . $Tr['Turn'] . "<td>" . date("d/m/Y H:i:s", $Tr['XferWhen']);
  }
  echo "</table><p>";

} else {
  echo "<h1>No Incoming Transfers Logged</h1>";
}

if ($OutLogs) {
  echo "<h1>Outgoing Transfers</h1>";
  $coln = 0;

  echo "<div class=tablecont><table class=striped id=indextable border>\n";
  echo "<thead><tr>";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>To</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>System</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Survey Data</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Turn</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>When</a>\n";
  echo "</thead><tbody>";

  foreach ($OutLogs as $Tr) {
    echo "<tr><td style=Background:" . $Facts[$Tr['DestFact']]['MapColour'] . ">" .$Facts[$Tr['DestFact']]['Name'];
    echo "<td>" . $WRefs[$Tr['SystemId']] . "<td>" . ($Tr['Survey']?'Yes':'No');
    echo "<td>" . $Tr['Turn'] . "<td>" . date("d/m/Y H:i:s", $Tr['XferWhen']);
  }
  echo "</table><p>";

} else {
  echo "<h1>No Outgoing Transfers Logged</h1>";
}

dotail();

