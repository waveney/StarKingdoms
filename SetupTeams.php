<?php
include_once("sk.php");
include_once("GetPut.php");
include_once("ThingLib.php");
include_once("PlayerLib.php");
include_once("SystemLib.php");
include_once("ProjLib.php");
include_once("OrgLib.php");

global $FACTION,$ADDALL,$GAME,$ARMY,$GAMEID;

A_Check('God');

$OpTypes = Get_OpTypes();
$Operations = Gen_Get_Cond('Operations', "GameId=$GAMEID AND Status=1" );
$Orgs = Gen_Get_Cond('Organisations',"GameId=$GAMEID");
$Facts = Get_Factions();

$OrgTypes = Get_OrgTypes();
$TTYpes = Get_ThingTypes();
$TTNames = NamesList($TTYpes);
$NamesTTypes = array_flip($TTNames);

dostaffhead("Set up alll Teams");

foreach ($Operations as $Oid=>$O) {
  $OrgId = $O['OrgId'];
  $Fid = $O['Whose'];
  $Wh = $O['SystemId'];

  $Team = Gen_Get_Cond1('Things', "Whose=$Fid AND Type=" . $NamesTTypes['Team'] . " AND Dist1=$OrgId");

  if (!$Team) {
    $Team = ['Whose'=>$Fid,'Type'=>$NamesTTypes['Team'], 'Dist1'=>$OrgId,'BuildState'=>3,
      'Name'=>("Operations team for " . $Orgs[$OrgId]['Name'])];
    Put_Thing($Team);
    $Orgs[$OrgId]['Team'] = $Team['id'];
    Gen_Put('Organisations',$Orgs[$OrgId]);
  }
  $Team['SystemId'] = $Wh;
  $Team['ProjectId'] = $Oid;
  $Team['WithinSysLoc'] = (($OpTypes[$O['Type']]['TeamProps'] & TEAM_INSPACE)?0:3);
  Put_Thing($Team);

  Put_Operation($O);
  $N = Get_System($Wh);
  echo "Created Team for " . $Orgs[$OrgId]['Name'] . " in $Wh<p>";
}

echo "All Done..";
dotail();