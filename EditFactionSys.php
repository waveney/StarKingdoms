<?php
include_once("sk.php");
include_once("GetPut.php");
include_once("SystemLib.php");
include_once("PlayerLib.php");

global $FACTION,$GAMEID;

A_Check('GM');

$FSid = $_REQUEST['id'];
$FS = Get_FactionSystem($FSid);
$Fid = $FS['FactionId'];
$Fact = Get_Faction($Fid);
$Sensors = Has_Tech($Fid,'Sensors');
$Sys = $FS['SystemId'];
$N = Get_System($Sys);

dostaffhead("Edit FS data");
echo "<h2>Faction System Data for " . $Fact['Name'] . " in " . $N['Ref'] . "</h2>";
Register_AutoUpdate('FactionSystem',$FSid);
echo $Fact['Name'] . " Sensors Level: $Sensors<p>";
echo "<table border>";
echo "<tr>" . fm_number1('Passive Scan',$FS,'ScanLevel');
echo "<tr>" . fm_number1('Planetary Scan',$FS,'PlanetScan');
echo "<tr>" . fm_number1('Space Scan',$FS,'SpaceScan');
echo "</table><p>";

echo "<h2><a href=SysEdit.php?N=$Sys>Back to System</a></h2>";
dotail();
