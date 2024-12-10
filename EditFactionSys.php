<?php
include_once("sk.php");
include_once("GetPut.php");
include_once("SystemLib.php");
include_once("PlayerLib.php");
include_once("ThingLib.php");

global $FACTION,$GAMEID,$GAME;

A_Check('GM');

// var_dump($_REQUEST);
dostaffhead("Edit FS data");

$FSid = $_REQUEST['id'];
$FS = Get_FactionSystem($FSid);
$Fid = $FS['FactionId'];
$Sys = $FS['SystemId'];
$Fact = Get_Faction($Fid);

if (isset($_REQUEST['ACTION'])) {
  switch ($_REQUEST['ACTION']) {
  case 'REMOVE':
    db_delete('FactionSystem',$FSid);
    echo "Entry removed<p>";
    dotail();


  case 'Planet Scan':
    $FS['PlanetTurn'] = $GAME['Turn'];
    Record_PlanetScan($FS);
    break;

  case 'Space Scan':
    $FS['SpaceTurn'] = $GAME['Turn'];
    Record_SpaceScan($FS);
    break;

  }
}

$Sensors = Has_Tech($Fid,'Sensors');
$N = Get_System($Sys);

echo "<form method=post>";
echo "<h2>Faction System Data for " . $Fact['Name'] . " in " . $N['Ref'] . "</h2>";
echo "If you change the Planet/Space scans, click the relevant Scan buttons after the changes.<p>";
Register_AutoUpdate('FactionSystem',$FSid);
echo fm_hidden('id',$FSid);

echo $Fact['Name'] . " Sensors Level: $Sensors<p>";
echo "<table border>";
echo "<tr>" . fm_number1('Passive Scan',$FS,'ScanLevel');
echo "<tr>" . fm_number1('Planetary Scan',$FS,'PlanetScan') . fm_number1('Turn',$FS,'PlanetTurn') . fm_submit('ACTION','Planet Scan');
echo "<tr>" . fm_number1('Space Scan',$FS,'SpaceScan') . fm_number1('Turn',$FS,'SpaceTurn') . fm_submit('ACTION','Space Scan');
echo "</table><p>";

echo fm_submit('ACTION','REMOVE');

echo "<h2><a href=SysEdit.php?N=$Sys>Back to System</a></h2>";
dotail();
