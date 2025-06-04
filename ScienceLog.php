<?php
  include_once("sk.php");
  global $FACTION,$GAMEID,$USER;
  include_once("GetPut.php");
  include_once("PlayerLib.php");
  include_once("ThingLib.php");

global $GAME,$ModValues,$Fields,$Tech_Cats,$CivMil,$ThingInstrs,$ThingInclrs;

A_Check('GM');

dostaffhead("Science Point Log");

$Logs = Gen_Get_All_GameId('SciencePointLog');
$Facts = Get_Factions();
$TechCats = [['Unknown','??'],['Engineering','EngineeringSP'],['Physics','PhysicsSP'],['Xenology','XenologySP'],['General','']];


TableStart();
TableHead('Who');
TableHead('Turn','N');
TableHead('Science Point Type');
TableHead('Amount','N');
TableHead('Reason');
TableTop();

foreach(array_reverse($Logs) as $L) {
  echo "<tr><td>" . ($Facts[$L['FactionId']]['Name']??'Unknown');
  echo "<td>" . $L['Turn'];
  echo "<td>" . $TechCats[$L['Type']][0];
  echo "<td>" . $L['Number'];
  echo "<td>" . $L['Note'];

}
TableEnd();

dotail();
