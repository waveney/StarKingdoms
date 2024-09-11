<?php
include_once("sk.php");
include_once("GetPut.php");
include_once("ThingLib.php");
include_once("SystemLib.php");
include_once("OrgLib.php");
global $FACTION,$GAMEID;

// var_dump($_REQUEST);

dostaffhead("Branch editing and creation");

$GM = Access('GM');
$OrgTypes = Get_OrgTypes();
$FactNames = Get_Faction_Names();
$BTypes = Get_BranchTypes();
$BTypeNames = NamesList($BTypes);

echo "<form method=post>";

if (isset($_REQUEST['Action'])) {
  switch ($_REQUEST['Action']) {
    case 'Add':
      $Wid = $_REQUEST['W'];
      $World = Get_World($Wid);
      $AOrgs = Gen_Get_Cond('Organisations',"GameId=$GAMEID");
      $AOrgList = NamesList($AOrgs);

      echo "<table border>";
      $B = [];
      echo "Select who and branch type and Organisation.<p>";
      echo fm_hidden('HostType',$World['ThingType']) . fm_hidden('HostId',$World['ThingId']);
      echo "<tr><td>Whose:<td>" . fm_select($FactNames,$B,'Whose');
      echo "<tr><td>Branch Type:<td>" . fm_select($BTypeNames,$B,'Type');
      echo "<tr>" . fm_text('Name (not yet used)',$B,'Name');
      echo "<tr><td>Organisation:<td>" . fm_select($AOrgList,$B,'Organisation',1);
      echo "</table><br><input type=Submit name=Action value=Create><p>";

      dotail();

    case 'New Here':
      $Bid = $_REQUEST['AutoRefBranches'];
      $B = Gen_Get('Branches',$Bid);
      unset($B['id']);
      $Bid = Gen_Put('Branches',$B);
      break;

    case 'Create':
      $B = [];
      $Bid = insert_db_post('Branches',$B);
      break;

    case 'Delete':
      $Bid = $_REQUEST['AutoRefBranches'];
      db_delete('Branches',$Bid);
      echo "Branch Deleted";
      dotail();

    case 'Refresh':
      $Bid = ($_REQUEST['id'] ?? $_REQUEST['AutoRefBranches']?? 0);
      break;
  }
} else {
  $Bid = ($_REQUEST['id'] ?? $_REQUEST['AutoRefBranches']??0);
}

$B = Gen_Get('Branches',$Bid);
$OrgId = $B['Organisation'];
$Org = Gen_Get('Organisations',$OrgId);
$Orgs = Gen_Get_Cond('Organisations',"Whose=" . $B['Whose']);
$OrgList = NamesList($Orgs);

if ($GM) {
  $Fid = 0;
  if (!empty($FACTION)) $Fid = $FACTION['id'];
} else {
  $Fid = $FACTION['id'];
  if ($FACTION['TurnState'] > 2) Player_Page();
  Check_MyThing($B,$Fid);
}

$Force = (isset($_REQUEST['FORCE'])?1:0);
switch ($B['HostType']) {
  case 1: // Planet
    $P = Get_Planet($B['HostId']);
    $Sys = Get_System($P['SystemId']);
    $Where = $P['Name'] . " in " . System_Name($Sys,$Fid);
    break;
  case 2: // Moon
    $M = Get_Moon($B['HostId']);
    $P = Get_Planet($M['PlanetId']);
    $Sys = Get_System($P['SystemId']);
    $Where = $M['Name'] . " a moon of " . $P['Name'] . " in " . System_Name($Sys,$Fid);
    break;
  case 3:// Thing
    $T = Get_Thing($B['HostId']);
    $Sys = Get_System($T['SystemId']);
    $Where = $T['Name'] . " in " . System_Name($Sys,$Fid);
    break;
}

echo "<table border>";
Register_AutoUpdate('Branches', $Bid);
echo "<tr><td>id: $Bid";
if (Access('God')) echo fm_number('Host Type',$B,'HostType') . fm_number('Host Id',$B,'HostId');
echo "<tr><td>Whose:<td>" . fm_select($FactNames,$B,'Whose');
echo "<tr><td>Branch Type:<td>" . fm_select($BTypeNames,$B,'Type');
echo "<tr>" . fm_text('Name (not yet used)',$B,'Name');
echo "<tr><td>Organisation:<td>" . fm_select($OrgList,$B,'Organisation',1);
if ($Org) echo "<tr><td>Org is type:<td>" . ($OrgTypes[$Org['OrgType']]['Name']??'Unknown') . "<td>Refresh page if Org changed";
echo "<tr><td>Location:<td>$Where<td>Only changeable by Richard";
echo "</table>";

if ($GM) {
  echo "<input type=submit Name=Action Value=Refresh> ";
  echo "<input type=submit Name=Action Value=Delete> <input type=submit name=Action value='New Here'>";

}

dotail();