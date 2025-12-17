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
        $B['OrgType2'] = $Org['OrgType2'];
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

      $AOrgs = Gen_Get_Cond('Organisations',"GameId=$GAMEID ORDER BY Name");
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

    case 'SPAWN_HS':
      // Get each branch on world, Get Dets for branch, Create/ update as nes
      $Sid = $_REQUEST['Sid'];
      $Hid = $_REQUEST['Hid'];
      $Wid = $_REQUEST['Wid'];
      $Hyp = $_REQUEST['Hyp'];
      $TTypes = Get_ThingTypes();
      $NTypes = array_flip(NamesList($TTypes));
      $BTypes = Get_BranchTypes();

      $Branches = Gen_Get_Cond('Branches',"HostType=$Hyp AND HostId=$Hid AND (OrgType=3 OR OrgType2=3)");

      $Sys = Get_System($Sid);
      $Offices = [];
      if ($Sys['WorldList']) {
        $Offices = Gen_Get_Cond('Offices',"World=$Wid AND (OrgType=3 OR OrgType2=3)");
        if ($Offices) foreach($Offices as $Oid=>$Of) {
          $Branches[-$Oid] = $Of;
        }
      }
//  var_dump($Branches);
      $TotC = 0;
      if ($Branches) {
        foreach ($Branches as $Bid=>$B) {
//          if ($BTypes[$B['Type']]['Props'] &1) continue; // Hidden
          $Fid = $B['Whose'];
          $DefLevel = Has_Tech($Fid,'Infantry Defences');
          $OffLevel = Has_Tech($Fid,'Infantry Weapons');
          $Def = 3*($DefLevel*3+12);
          $Off = 3*($OffLevel+4);

          $Ox = ($Bid<0)?" Office ":'';
          $Rid = abs($Bid);

          $Org = Gen_Get('Organisations',$B['Organisation']);
          $Num = $Org['OfficeCount'];

          $Teams = Get_Things_Cond($B['Whose'],"Type=" . $NTypes['Heavy Security'] . " AND ProjectId=$Rid");
          $Count = 0;
          if ($Teams) {
            foreach($Teams as $T) {
              if ($T['OrigHealth'] < $Def) {
                $T['CurHealth'] = $Def + $T['CurHealth'] - $T['OrigHealth'];
                $T['OrigHealth'] = $Def;
              }
              $T['Damage'] = $Off;
              $T['SystemId'] = $T['WhereBuilt'] = $Sid;
              if ($T['CurHealth']) {
                $T['WithinSysLoc'] = 3;
                $T['LinkId'] = 0;
                if (!preg_match('/\:.*\:/',$T['Name']))
                  $T['Name'] = ($B['Name']?$B['Name']:"Heavy $Ox Security $Rid") . ":" . $Org['Name'] . ":" . ($Count+1);
                echo $T['Name'] . " deployed.<br>";
                $TotC++;
              } else {
                echo $T['Name'] . " not deployed as it is currently at 0 health<p>";
                $T['LinkId'] = LINK_INBRANCH;
              }
              $T['Evasion'] = 40;
              $T['ProjectId'] = $Rid;
              Put_Thing($T);
              $Count++;
            }
          }
          if ($Count < $Num) { // New ones
            while ($Count < $Num) {
              $Count++;
              $T = ['Whose'=>$Fid, 'Type'=>$NTypes['Heavy Security'], 'BuildState'=>BS_COMPLETE, 'CurHealth'=>$Def, 'OrigHealth'=>$Def,
                'ActDamage'=>$Off,
                'SystemId'=>$Sid, 'WithinSysLoc'=>3, 'Class'=>'Heavy Security',
                'Name'=>($B['Name']?$B['Name']:"Heavy $Ox Security $Rid") . ":" . $Org['Name'] . ":$Count" ,
                'Evasion'=>40, 'ProjectId'=>$Rid, 'LinkId'=>0];
              if ($Bid>0 && ($BTypes[$B['Type']]['Props'] &1)) $T['Name'] .= " (Hidden)";
              Put_Thing($T);
              echo "Created " . $T['Name'] . " $Count<br>";
              $TotC++;
            }
          }
        }
      }
      echo "$TotC Heavy Security Forces have been deployed.<p><h2><a href=Meetings.php?ACTION=Check&S=$Sid>Return to Meetups</a></h2>";
      dotail();

    case 'SPAWN_FS':
      $Sid = $_REQUEST['Sid'];
      $Oid = $_REQUEST['Oid'];
      $TTypes = Get_ThingTypes();
      $NTypes = array_flip(NamesList($TTypes));

      $Branches = Gen_Get_Cond('Branches',"HostType=3 AND HostId=$Oid AND (OrgType=3 OR OrgType2=3)");
      // var_dump($Branches);
      $TotC = 0;
      if ($Branches) {
        foreach ($Branches as $Bid=>$B) {
          $Fid = $B['Whose'];
          $DefLevel = Has_Tech($Fid,'Starship Defences');
          $OffLevel = Has_Tech($Fid,'Starship Weapons');
          $EngLevel = Has_Tech($Fid,'Engines');
          $Def = ($DefLevel*3+12);
          $Off = 2*($OffLevel+4);
          $Speed = 2*$EngLevel;

          $Org = Gen_Get('Organisations',$B['Organisation']);
          $Num = $Org['OfficeCount'];

          $Squads = Get_Things_Cond_Ordered($B['Whose'],"Type=" . $NTypes['Fighter Defences'] . " AND ProjectId=$Bid");
          $Count = 0;
          if ($Squads) foreach($Squads as $Tid=>$T) {
            $T['CurHealth'] = $T['OrigHealth'] = 1;
            $T['ActDamage'] = $Off;
            $T['SystemId'] = $Sid;
            $T['Speed'] = $Speed;
            $T['WithinSysLoc'] = 1;
            $T['LinkId'] = 0;
            $T['ProjectId'] = $Bid;
            $T['Evasion'] = 40;
            $T['ProjectId'] = $Bid;
            //var_dump($T);
            Put_Thing($T);
            $Count++;
          }
          if ($Count < $Num) { // New ones
            while ($Count < $Num) {
              $Count++;
              $T = ['Whose'=>$Fid, 'Type'=>$NTypes['Fighter Defences'], 'BuildState'=>BS_COMPLETE, 'CurHealth'=>$Def, 'OrigHealth'=>$Def, 'ActDamage'=>$Off,
                'SystemId'=>$Sid, 'WithinSysLoc'=>1, 'Class'=>'Defence Fighter Squadron', 'Name'=>($B['Name']?$B['Name']:"Squadron $Bid") . ":$Count" ,
                'Evasion'=>40, 'ProjectId'=>$Bid, 'Speed'=>$Speed, 'LinkId'=>0];
              //var_dump($T);
              Put_Thing($T);
            }
          }
          $TotC += $Count;
        }
      }
      echo "$TotC Fighter Defences Squadron have been deployed.<p><h2><a href=Meetings.php?ACTION=Check&S=$Sid>Return to Meetups</a></h2>";
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
if ($Org) {
  echo "<tr><td>Org is type:<td>" . ($OrgTypes[$Org['OrgType']]['Name']??'Unknown') . "<td>Refresh page if Org changed";
  if ($Org['OrgType2']) echo "<tr><td>Org also type:<td>" . ($OrgTypes[$Org['OrgType2']]['Name']??'') . "<td>Refresh page if Org changed";
}
echo "<tr><td>Location:<td>$Where<td>Only changeable by Richard";
if ($BTypeNames[$B['Type']] == 'Science Space Base') {
  global $Currencies;
  $RTypes = $Currencies;
  $RTypes[-1] = 'No Reward';
  $RTypes[0] = 'Not Setup yet';
  echo "<tr><td>Ongoing Reward:<td>" . fm_select($RTypes,$B,'RewardType') . fm_number("Amount",$B,'RewardNumber');
}
echo "<tr>" . fm_number('Suppressed',$B,'Suppressed') . fm_number('From Turn', $B,'SuppressStart');

echo "</table>";

if ($GM) {
  echo "<input type=submit Name=Action Value=Refresh> ";
  echo "<input type=submit Name=Action Value=Delete> <input type=submit name=Action value='New Here'>";

}

dotail();