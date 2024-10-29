<?php
include_once("sk.php");
include_once("GetPut.php");
include_once("SystemLib.php");
include_once("PlayerLib.php");

global $FACTION,$GAMEID;

A_Check('GM');

// var_dump($_REQUEST);
dostaffhead("Edit FS data");

if (isset($_REQUEST['ACTION'])) {
  switch ($_REQUEST['ACTION']) {
  case 'REMOVE':
    $FSid = $_REQUEST['id'];
    db_delete('FactionSystem',$FSid);
    echo "Entry removed<p>";
    dotail();
  }
}

$FSid = $_REQUEST['id'];
$FS = Get_FactionSystem($FSid);
$Fid = $FS['FactionId'];
$Fact = Get_Faction($Fid);
$Sensors = Has_Tech($Fid,'Sensors');
$Sys = $FS['SystemId'];
$N = Get_System($Sys);

echo "<form method=post>";
echo "<h2>Faction System Data for " . $Fact['Name'] . " in " . $N['Ref'] . "</h2>";
Register_AutoUpdate('FactionSystem',$FSid);
echo fm_hidden('id',$FSid);

echo $Fact['Name'] . " Sensors Level: $Sensors<p>";
echo "<table border>";
echo "<tr>" . fm_number1('Passive Scan',$FS,'ScanLevel');
echo "<tr>" . fm_number1('Planetary Scan',$FS,'PlanetScan');
echo "<tr>" . fm_number1('Space Scan',$FS,'SpaceScan');
echo "</table><p>";

echo fm_submit('ACTION','REMOVE');

echo "<h2><a href=SysEdit.php?N=$Sys>Back to System</a></h2>";
dotail();
