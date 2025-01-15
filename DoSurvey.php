<?php
include_once("sk.php");
include_once("GetPut.php");
include_once("ThingLib.php");
include_once("PlayerLib.php");
include_once("SystemLib.php");
include_once("ProjLib.php");
include_once("OrgLib.php");

global $FACTION,$GAMEID,$ARMIES;

A_Check('God');
$Facts = Get_Factions();
$Systems = Gen_Get_Cond('Systems',"GameId=$GAMEID");
$SRefs = Get_SystemRefs();

dostaffhead("Scan by Faction, Dont store");

if (isset($_REQUEST['ACTION'])) {
  $Sysid = $_REQUEST['System'];
  $N = Get_System($Sysid);
  $FS = Get_FactionSystemFS($FACTION['id'],$Sysid);
  if (!isset($FS['id'])) {
    echo "System unknown to " . $FACTION['Name'];
    $_REQUEST['ACTION'] = 'Skip';
  }


  switch ($_REQUEST['ACTION']) {
    case 'Passive':


    case 'Space':

    case 'Planet':

    case 'Skip':
  }
}

echo "<form method=post action=DoSurvey.php>";
echo fm_select('System',$_REQUEST,'System') . "<p>";
echo fm_submit('Passive','Passive');
echo fm_submit('Space','Space');
echo fm_submit('Planet','Planet');

dotail();
