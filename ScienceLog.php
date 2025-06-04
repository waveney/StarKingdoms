<?php
  include_once("sk.php");
  global $FACTION,$GAMEID,$USER;
  include_once("GetPut.php");
  include_once("PlayerLib.php");
  include_once("ThingLib.php");

global $GAME,$ModValues,$Fields,$Tech_Cats,$CivMil,$ThingInstrs,$ThingInclrs;

$GM = Access('GM');

dostaffhead("Science Point Log");

if ($GM && !isset($FACTION)) {
  $Logs = Gen_Get_All_GameId('SciencePointLog');
  $Fid = 0;
} else {
  $Fid = $FACTION['id'];
  $Logs = Gen_Get_Cond('SciencePointLog',"FactionId=$Fid");
}
$Facts = Get_Factions();
$TechCats = [['Unknown','??'],['Engineering','EngineeringSP'],['Physics','PhysicsSP'],['Xenology','XenologySP'],['General','']];

if (!$Logs) {
  echo "No Science Point Logs recorded yet<p>";
  dotail();
}


TableStart();
if ($Fid == 0) TableHead('Who');
TableHead('Turn','N');
TableHead('Science Point Type');
TableHead('Amount','N');
TableHead('Reason');
TableTop();

foreach(array_reverse($Logs) as $L) {
  echo "<tr>";
  if ($Fid == 0) echo "<td>" . ($Facts[$L['FactionId']]['Name']??'Unknown');
  echo "<td>" . $L['Turn'];
  echo "<td>" . $TechCats[$L['Type']][0];
  echo "<td>" . $L['Number'];
  echo "<td>" . $L['Note'];

}
TableEnd();

dotail();
