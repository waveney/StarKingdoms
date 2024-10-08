<?php
include_once("sk.php");
include_once("GetPut.php");
include_once("ThingLib.php");
include_once("SystemLib.php");
include_once("OrgLib.php");
global $FACTION,$GAMEID;

// var_dump($_REQUEST);

dostaffhead("Office editing and creation");

A_Check('GM');
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

      echo "<table border>";
      $B = ['World'=>$Wid];
      echo "Select who and branch type.  Click Create and it will then ask for the organisation.<p>";
      echo fm_number('World',$B,'World');
      echo "<tr><td>Whose:<td>" . fm_select($FactNames,$B,'Whose');
      echo "<tr>" . fm_text('Name (not yet used)',$B,'Name');
      echo "</table><br><input type=Submit name=Action value=Create><p>";

      dotail();

    case 'New Here':
      $Oid = $_REQUEST['AutoRefOffices'];
      $B['Number'] = 1;
      $B = Gen_Get('Offices',$Oid);
      unset($B['id']);
      $Oid = Gen_Put('Offices',$B);
      break;

    case 'Create':
      $_POST['GameId'] = $GAMEID;
      $_Post['Number'] = 1;
      $Oid = insert_db_post('Offices',$B);
      break;

    case 'Delete':
      $Oid = $_REQUEST['id']??$_REQUEST['AutoRefOffices']??0;
      if ($Oid) {
        db_delete('Offices',$Oid);
        echo "Office Deleted";
      } else {
        echo "Office not found";
      }
      dotail();

    case 'Refresh':
      $Oid = ($_REQUEST['id'] ?? $_REQUEST['AutoRefOffices']?? 0);
      break;
  }
} else {
  $Oid = ($_REQUEST['id'] ?? $_REQUEST['AutoRefOffices']?? 0);
}

$B = Gen_Get('Offices',$Oid);
$OrgId = $B['Organisation'];
$Org = Gen_Get('Organisations',$OrgId);
$Orgs = Gen_Get_Cond('Organisations',"Whose=" . $B['Whose']);
$OrgList = NamesList($Orgs);

  $Fid = 0;
  if (!empty($FACTION)) $Fid = $FACTION['id'];

  $W = Get_World($B['World']);

switch ($W['ThingType']) {
  case 1: // Planet
    $P = Get_Planet($W['ThingId']);
    $Sys = Get_System($P['SystemId']);
    $Where = $P['Name'] . " in " . System_Name($Sys,$Fid);
    break;
  case 2: // Moon
    $M = Get_Moon($W['ThingId']);
    $P = Get_Planet($M['PlanetId']);
    $Sys = Get_System($P['SystemId']);
    $Where = $M['Name'] . " a moon of " . $P['Name'] . " in " . System_Name($Sys,$Fid);
    break;
  case 3:// Thing
    $T = Get_Thing($W['ThingId']);
    $Sys = Get_System($T['SystemId']);
    $Where = $T['Name'] . " in " . System_Name($Sys,$Fid);
    break;
}

echo "<table border>";
Register_AutoUpdate('Offices', $Oid);
echo "<tr><td>id: $Oid";
echo fm_number('World',$B,'World');

echo "<tr><td>Whose:<td>" . fm_select($FactNames,$B,'Whose');
echo "<tr>" . fm_text('Name (not yet used)',$B,'Name');
echo "<tr><td>Organisation:<td>" . fm_select($OrgList,$B,'Organisation',1);
if ($Org) echo "<tr><td>Org is type:<td>" . $OrgTypes[$Org['OrgType']]['Name'] . "<td>Refresh page if Org changed";
echo "<tr><td>Location:<td>$Where";
echo "</table>";

  echo "<input type=submit Name=Action Value=Refresh> ";
  echo "<input type=submit Name=Action Value=Delete> <input type=submit name=Action value='New Here'>";


dotail();