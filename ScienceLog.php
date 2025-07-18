<?php
  include_once("sk.php");
  global $FACTION,$GAMEID,$USER;
  include_once("GetPut.php");
  include_once("PlayerLib.php");
  include_once("ThingLib.php");

global $GAME,$ModValues,$Fields,$Tech_Cats,$CivMil,$ThingInstrs,$ThingInclrs;

$GM = Access('GM');

dostaffhead("Science Point Log");

echo "<h1>Resource Type Logs, ";
if ($FACTION['PhysicsSP'] >=5 || $FACTION['EngineeringSP'] >=5 || $FACTION['XenologySP'] >=5 ) echo " <a href=SciencePoints.php>Spend Science Points</a>, ";
if ($FACTION['Currency1']) echo " <a href=FluxCrystals.php>Use Flux Crystals</a>, ";
echo "</h1>";


if ($GM && !isset($FACTION['id'])) {
  $Logs = Gen_Get_All_GameId('SciencePointLog');
  $Fid = 0;
} else {
  $Fid = $FACTION['id'];
  $Logs = Gen_Get_Cond('SciencePointLog',"FactionId=$Fid");
}
$Facts = Get_Factions();
$TechFields = [0=>['Unknown','??'],1=>['Engineering','EngineeringSP'],2=>['Physics','PhysicsSP'],3=>['Xenology','XenologySP'],4=>['General',''],
  -1=>[Feature('Currency1'),'Currency1'], -2=>[Feature('Currency2'),'Currency2'], -3=>[Feature('Currency3'),'Currency3'],
];

if (!$Logs) {
  echo "<h2>No Logs recorded yet</h2>";
  dotail();
}


TableStart();
if ($Fid == 0) TableHead('Who');
TableHead('Turn','N');
TableHead('Resource Type');
TableHead('Amount','N');
TableHead('Reason');
TableTop();

foreach(array_reverse($Logs) as $L) {
  echo "<tr>";
  if ($Fid == 0) echo "<td>" . ($Facts[$L['FactionId']]['Name']??'Unknown');
  echo "<td>" . $L['Turn'];
  echo "<td>" . $TechFields[$L['Type']][0];
  echo "<td>" . $L['Number'];
  echo "<td>" . $L['Note'];

}
TableEnd();

dotail();
