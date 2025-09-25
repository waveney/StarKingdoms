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
  $Logs = Gen_Get_Cond('SciencePointLog',"Game=$GAMEID ORDER BY id DESC");
  $Fid = 0;
} else {
  $Fid = $FACTION['id'];
  $Logs = Gen_Get_Cond('SciencePointLog',"FactionId=$Fid ORDER BY id DESC");
}
$Facts = Get_Factions();
//$TechFields = [0=>['Unknown','??'],1=>['Engineering','EngineeringSP'],2=>['Physics','PhysicsSP'],3=>['Xenology','XenologySP'],4=>['General',''],
//  -1=>[Feature('Currency1'),'Currency1'], -2=>[Feature('Currency2'),'Currency2'], -3=>[Feature('Currency3'),'Currency3'],
//];

if (!$Logs) {
  echo "<h2>No Logs recorded yet</h2>";
  dotail();
}

$Tracks = TrackIndexes();
$Tracked = [];

var_dump($Tracks);
// Scan for used types
foreach($Logs as $L) {
  $Tracked[$L['Type']] = 1;
}

$Reses = ['All'];
foreach ($Tracks as $RTi=>$RT) {
  if (isset($Tracked[$RTi])) $Reses[$RTi] = $RT[1];
}

$Show['Show'] = 0;
echo "<div class=floatright ><b>" . fm_radio("Show",$Reses,$Show,'Show',' onchange=ListFilter("Show")') . "</div>";

//var_dump($Tracks);
TableStart('striped');
if ($Fid == 0) TableHead('Who');
TableHead('Turn','N');
TableHead('Resource Type');
TableHead('Amount','N');
TableHead('Total','N');
TableHead('Reason');
TableTop();

$Runtot = GameFeature('RunningResTotals',0);

foreach($Logs as $L) {
  $LType = $L['Type'];
  echo "<tr class='ShowAll Show$LType'>";
  if ($Fid == 0) echo "<td>" . ($Facts[$L['FactionId']]['Name']??'Unknown');
  echo "<td>" . $L['Turn'];
  echo "<td>" . $Tracks[$LType][1];
  echo "<td>" . $L['Number'];
  echo "<td>" . ((($L['Turn']<$Runtot) || ($Tracks[$LType][3] &16))?'?':$L['EndVal']);
  echo "<td>" . $L['Note'];

}
TableEnd();

dotail();
