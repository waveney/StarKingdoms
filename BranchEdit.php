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
      echo "<tr>" . fm_text('Name',$B,'Name');
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
      $_POST['GameId'] = $GAMEID;
      $Bid = insert_db_post('Branches',$B);
      $Org = Gen_Get('Organisations',$B['Organisation']);
      if ($B['Whose'] != $Org['Whose']) {
        echo "<h2 class=err>Owner of Org not same as owner of Branch...</h2>";
      } else {
        $B['OrgType'] = $Org['OrgType'];
        Gen_Put('Branches',$B);
      }

      break;

    case 'Delete':
      $Bid = $_REQUEST['id'] ?? $_REQUEST['AutoRefBranches'] ?? 0;
      if ($Bid) {
        db_delete('Branches',$Bid);
        echo "Branch Deleted";
      } else {
        echo 'No Branch Specified';
      }
      dotail();

    case 'Refresh':
      $Bid = ($_REQUEST['id'] ?? $_REQUEST['AutoRefBranches']?? 0);
      break;

    case 'Add Branch': // From Edit Thing
      $Tid = $_REQUEST['T'];
      $AOrgs = Gen_Get_Cond('Organisations',"GameId=$GAMEID");
      $AOrgList = NamesList($AOrgs);

      echo "<table border>";
      $B = [];
      echo "Select who and branch type and Organisation.<p>";
      echo fm_hidden('HostType',3) . fm_hidden('HostId',$Tid);
      echo "<tr><td>Whose:<td>" . fm_select($FactNames,$B,'Whose');
      echo "<tr><td>Branch Type:<td>" . fm_select($BTypeNames,$B,'Type');
      echo "<tr>" . fm_text('Name',$B,'Name');
      echo "<tr><td>Organisation:<td>" . fm_select($AOrgList,$B,'Organisation',1);
      echo "</table><br><input type=Submit name=Action value=Create><p>";

      dotail();

    case 'New Branch': // From Planet EDit - may not be a world yet
      $Pid = $_REQUEST['Planid'];
      $Plan = Get_Planet($Pid);
      if (!$Plan['ProjHome']) {
        $HomeData = ['ThingType'=>1,'ThingId'=>$Pid, 'GameId'=>$GAMEID, 'SystemId'=>$Plan['SystemId']];
        Put_ProjectHome($HomeData);
        $Plan['ProjHome'] = $HomeData['id'];
        Put_Planet($Plan);
        $World = ['Home'=>$HomeData['id'],'ThingType'=>1, 'ThingId'=>$Pid,'GameId'=>$GAMEID ];
        Put_World($World);
        $Wid = $World['id'];
      } else {
        $World = Gen_Get_Cond1('Worlds',"ThingType=1 AND ThingId=$Pid");
        if (!$World){
          echo "<h2 class=err>World/home data is in a confused state give Richard Planet id: $Pid</h2>";
          dotail();
        }
        $Wid = $World['id'];
      }

      $AOrgs = Gen_Get_Cond('Organisations',"GameId=$GAMEID");
      $AOrgList = NamesList($AOrgs);

      echo "<table border>";
      $B = [];
      echo "Select who and branch type and Organisation.<p>";
      echo fm_hidden('HostType',$World['ThingType']) . fm_hidden('HostId',$World['ThingId']);
      echo "<tr><td>Whose:<td>" . fm_select($FactNames,$B,'Whose');
      echo "<tr><td>Branch Type:<td>" . fm_select($BTypeNames,$B,'Type');
      echo "<tr>" . fm_text('Name',$B,'Name');
      echo "<tr><td>Organisation:<td>" . fm_select($AOrgList,$B,'Organisation',1);
      echo "</table><br><input type=Submit name=Action value=Create><p>";

      dotail();

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
    if ($T) {
      $Sys = Get_System($T['SystemId']);
      $Where = $T['Name'] . " in " . System_Name($Sys,$Fid);
    } else {
      $Sys = [];
      $Where = "?";
    }
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