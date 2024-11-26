<?php
include_once("sk.php");
include_once("GetPut.php");

define('OPER_LEVEL',0xf);
define('OPER_TECH',0x10);
define('OPER_SOCP',0x20);
define('OPER_OUTPOST',0x40);
define('OPER_CREATE_OUTPOST',0x80);
define('OPER_BRANCH',0x100);
define('OPER_HIDDEN',0x200);
define('OPER_SOCPTARGET',0x400);
define('OPER_WORMHOLE',0x800);
define('OPER_CIVILISED',0x1000);
define('OPER_MONEY',0x2000);
define('OPER_DESC',0x4000);
define('OPER_ANOMALY',0x8000);
define('OPER_SCIPOINTS',010000);


define('ORG_HIDDEN',1);
define('ORG_ALLOPS',2);
define('ORG_NO_BRANCHES',4);
define('ORG_SPECIAL_POWER',8);

define('BRANCH_HIDDEN',1);
define('BRANCH_NOSPACE',2);

define('TEAM_HIDDEN',1);
define('TEAM_INSPACE',2);
define('TEAM_ONGROUND',4);

function Recalc_Offices() { // Recount offices for each org
  global $GAMEID;
  $Offs = Gen_Get_All('Offices', " WHERE GameId=$GAMEID");
  $Orgs = Gen_Get_All('Organisations', " WHERE GameId=$GAMEID");
  $OrgTypes = Get_OrgTypes();
  $OCount = [];

  foreach ($Orgs as $i=>$O) {
    if (($OrgTypes[$O['OrgType']]['Props'] & ORG_SPECIAL_POWER) == 0) {
      $Orgs[$i]['OfficeCount'] = 0;
    } else {
      if (($Pow = Has_Tech($O['Whose'],'Ascension'))) $Orgs[$i]['OfficeCount'] = $Pow;
    }
  }
  foreach ($Offs as $oi=>$Of) {
    $Org = $Of['Organisation'];
    if (isset($Orgs[$Org])) {
//      if (!isset($Orgs[$Org]['OfficeCount'])) var_dump($Of);
      $Orgs[$Org]['OfficeCount']+= max($Of['Number'],1);
      if ($Of['OrgType'] != $Orgs[$Org]['OrgType']) {
        $Of['OrgType'] = $Orgs[$Org]['OrgType'];
        Gen_Put('Offices',$Of);
      }
    } else {
      echo "Office $oi does not match any current Organisation - needs fixing<p>";
    }
  }

  foreach ($Orgs as $O) Gen_Put('Organisations',$O);

}

function SocPrinciples($Fid) {
  $SocPs = Gen_Get_Cond('SocialPrinciples',"Whose=$Fid");
  $A = [];
  foreach ($SocPs as $i=>$P) $A[$i] = $P['Principle'];
  return $A;
}

function Link_Search($Sys,$lnks) { // Part of Op_Level (Recusive) - no longer used
  global $Min,$Targets,$SysLnks,$Depth,$BeenHere;
//echo "LS: $Sys, $lnks<p>";
//var_dump($BeenHere);
  if ($lnks > 20) return -2;
  if (isset($Targets[$Sys])) return $lnks;
  if (($BeenHere[$Sys]>($lnks+1)) || ($lnks>=$Min)) return -3;
//  echo "A";
  $BeenHere[$Sys] = $lnks+1;
  // if ($Sys)
//  echo "A$lnks<br>";
  $Any = 0;
  foreach ($SysLnks[$Sys] as $Si) {
//    echo "B$lnks:$Si<br>";
    if ($BeenHere[$Si]??99) continue;
 //   echo "Call LS - $lnks:<br>";
    $D = Link_Search($Si,$lnks+1);
//echo "<br>D$lnks:$D=$Min=<br>";
    if (($D >= 0) && ($D < $Min)) {
      $Min = $D;
      $Any = 1;
    }
  }
  $BeenHere[$Sys] = 0;
//echo "E$lnks:$Min=<br>";
  return $Min;
}

function Op_Level($Orgid,$Sys,$Mod=0) {
  global $Min,$Targets,$SysLnks,$Depth,$BeenHere,$GAMEID;
  // Get list off all systems with worlds of Factions and all locations of branches (ignore duplicates eg Outpost/planet)

  // get all faction link knowledge
  // get all links for those
  // Offices = Level.  Scan depth = Level

  // Returns -1 impossible currently, 0-N=level

  $Org = Gen_Get('Organisations',$Orgid) ;
  $Fid = $Org['Whose'];
  $LKnown = Gen_Get_Cond('FactionLink', "FactionId=$Fid");
  $SKnown1 = Gen_Get_Cond('FactionSystem', "FactionId=$Fid");
  $SKnown = [];
  foreach($SKnown1 as $K) $SKnown[$K['SystemId']] = $K;
  $Links = Get_LinksGame();

  $ValidLinks=[];
  foreach($LKnown as $LK) $ValidLinks[$LK['LinkId']] = 1;

//  $Depth = $Org['OfficeCount']-$Mod;

// var_dump($Links, $LKnown,$SKnown1,$SKnown);
  if (!isset($SKnown[$Sys])) return -1; // Unknown target system

  $SRefs = Get_SystemRefs();
  $RSyss = array_flip($SRefs);
  $Targets = [];
  $Worlds = Get_Worlds($Fid);
  $Branches = Gen_Get_Cond('Branches', "Whose=$Fid AND Organisation=$Orgid");
  if ($Branches) foreach ($Branches as $B) {
    if ($B['Suppressed']) continue;
    switch ($B['HostType']) {
      case 1: //Planets
        $P = Get_Planet($B['HostId']);
        $Targets[$P['SystemId']] = 1;
        break;
      case 2: // Moon
        $M = Get_Moon($B['HostId']);
        $P = Get_Planet($M['PlanetId']);
        $Targets[$P['SystemId']] = 1;
        break;
      case 3: // Thing
        $P = Get_Thing($B['HostId']);
        if ($P['SystemId']) $Targets[$P['SystemId']] = 1;
        break;
    }
  }
  foreach ($Worlds as $W) {
    switch ($W['ThingType']) {
      case 1: //Planets
        $P = Get_Planet($W['ThingId']);
        $Targets[$P['SystemId']] = 2;
        break;
      case 2: // Moon
        $M = Get_Moon($W['ThingId']);
        $P = Get_Planet($M['PlanetId']);
        $Targets[$P['SystemId']] = 2;
        break;
      case 3: // Thing
        $P = Get_Thing($W['ThingId']);
        $Targets[$P['SystemId']] = 2;
        break;
    }
  }

  // Targets should now identify the systems it needs to reach
  if (isset($_REQUEST['SHOWTARGS'])) var_dump($Targets,$SKnown,$ValidLinks);
  if (isset($Targets[$Sys])) return 0; // Range 0
  $BeenHere = [];

  $Depth = 0;
  $ThisList[] = $Sys;
  while (($Depth < 8) && !empty($ThisList)) {
//    echo "Doing Depth $Depth<p>";
    $NextList = [];
    foreach($ThisList as $Sid) {
      if (isset($BeenHere[$Sid])) continue;
      if (isset($Targets[$Sid])) return $Depth;
      $BeenHere[$Sid] = 1;
      $SidRef = $SRefs[$Sid];
      $LinksHere = Gen_Get_Cond('Links', "GameId=$GAMEID AND (System1Ref='$SidRef' OR System2Ref='$SidRef')");
      if ($LinksHere) foreach ($LinksHere as $L) {
        if (isset($ValidLinks[$L['id']])) {
          $LinkToRef = (($L['System1Ref']==$SidRef) ?$L['System2Ref']:$L['System1Ref']);
          $LinkToId = $RSyss[$LinkToRef];
          if (!isset($BeenHere[$LinkToId])) {
            $NextList[] = $LinkToId;
          }
        }
      }
    }
    $Depth++;
    $ThisList = $NextList;
  }
  return -1;
}
  /*
   * STart at sys, go though each link,
   * if system seen, skip,
   * if found result,
   * add links to next level list
   *
   */

function OrgColours() {
  static $Cols = [];
  if (empty($Cols)) {
    $OTypes = Get_OrgTypes();
    $Cols = NamesList($OTypes,'Colour');
  }
  return $Cols;
}

function WorldFromSystem($Sid,$Fid=0) {
  $Ps = Get_Planets($Sid);
  $PTypes = Get_PlanetTypes();
  $Hab = [];
  $Planet = 0;
  foreach ($Ps as $P) {
    if ($Fid && ($P['Control']==$Fid)) {
      $Planet = $P;
      break;
    }
    if ($PTypes[$P['Type']]['Hospitable']) $Hab[] = $P;
  }
  if (!$Planet) {
    if ( count($Hab) == 1) {
      $Planet = $Hab[0];
    } else {
      return 0;
    }
  }

  $World = Gen_Get_Cond1('Worlds', "ThingType=1 AND ThingId=" . $Planet['id']);
  return $World['id'];
}

function HabPlanetFromSystem($Sid) {
  $PTypes = Get_PlanetTypes();
  $Ps = Get_Planets($Sid);
  foreach ($Ps as $P) if ($PTypes[$P['Type']]['Hospitable']) return $P['id'];
  return 0;
}

function CheckBranches() {
  global $GAMEID;
  $OrgTypes = Get_OrgTypes();
  $BTypes = Get_BranchTypes();
  $Orgs = Gen_Get_Cond('Organisations',"GameId=$GAMEID");
  $Branches = Gen_Get_Cond('Branches',"GameId=$GAMEID");

  foreach ($Branches as $B) {
    $B['OrgType'] = $Orgs[$B['Organisation']]['OrgType'];
    $B['Whose'] = $Orgs[$B['Organisation']]['Whose'];
    Gen_Put('Branches',$B);
  }
}

function OrgType($Short) {
  $OT = Gen_Get_Cond1('OfficeTypes',"ShortName='$Short'");
  return $OT['id'];
}

function Outpost_In($Sid,$Who,$Create=1) {
  global $GAMEID;
  $TTYpes = Get_ThingTypes();
  $TTNames = array_flip(NamesList($TTYpes));
  $OutPs = Get_Things_Cond(0,"Type=" . $TTNames['Outpost'] . " AND SystemId=$Sid AND BuildState=3");
  if ($OutPs) {
    $OutP = $OutPs[0];
  } else if ($Create) {
    $OutP = ['Type'=>$TTNames['Outpost'],'SystemId'=>$Sid,'Whose'=>$Who,'BuildState'=>3, 'GameId'=>$GAMEID];
    Put_Thing($OutP);
    $Sys = Get_System($Sid);
    if ($Sys['Control'] == 0) {
      $Sys['Control'] = $Who;
      $FS = Get_FactionSystem($Who,$Sys);
      if (!empty($FS['Name'])) $Sys['Name'] = $FS['Name'];
      Put_System($Sys);
    }
  } else {
    return 0;
  }
  return $OutP;
}

function World_In($Sid,$Who) {
  global $GAMEID;
  $Plan = HabPlanetFromSystem($Sid);
  if ($Plan == 0) return 0;
  $World = Gen_Get_Cond1('Worlds',"ThingType=1 AND ThingId=$Plan");
  $P = Get_Planet($Plan);

  if (empty($World)) {
    $World = ['ThingType'=>1, 'ThingId'=>$Plan, 'Minerals'=>$P['Minerals'],'FactionId'=>$Who, 'Home'=>0, 'GameId'=>$GAMEID];
    Put_World($World);
    if (empty($P['Control'])) {
      $FP = Get_FactionPlanetFS($Who,$Plan);
      $P['Control'] = $Who;
      if (!empty($FP['Name'])) $P['Name'] = $FP['Name'];
      Put_Planet($P);
    }
  }
  $World['Name'] = $P['Name'];

  return $World;
}

function New_Branch(&$Data,$Type,&$O,&$Org) {
  global $GAMEID;

  $B = ['HostType'=>(isset($Data['Minerals'])?1:3), 'HostId'=>$Data['id'], 'Whose'=>$O['Whose'], 'Type'=>$Type,
    'Organisation'=>$O['OrgId'],'OrgType'=>$Org['OrgType'], 'GameId'=>$GAMEID];
  Gen_Put('Branches',$B);
}

function World_Name($Wid,$Fid=0) {
  $World = Get_World($Wid);
  switch ($World['ThingType']) {
    case 1: // Planet
      $P = Get_Planet($World['ThingId']);
      $Sys = $P['SystemId'];
      $FP = Get_FactionPlanetFS($Fid,$P['id']);
      $N = Get_System($Sys);
      $Name = ($FP['Name']?$FP['Name']:$P['Name']) . " in " . System_Name($N,$Fid);
      return $Name;

    case 2 : // Moon
      $M = Get_Moon($World['ThingId']);
      $FM = Get_FactionMoonFS($Fid,$M['id']);
      $P = Get_Planet($M['PlanetId']);
      $Sys = $P['SystemId'];
      $FP = Get_FactionPlanetFS($Fid,$P['id']);
      $N = Get_System($Sys);
      $Name = ($FM['Name']?$FM['Name']:$M['Name']) . " a moon of " . ($FP['Name']?$FP['Name']:$P['Name']) . " in " . System_Name($N,$Fid);
      return $Name;

    case 3: // Thing
      $T = Get_Thing($World['ThingId']);
      $Sys = $T['SystemId'];
      $N = Get_System($Sys);
      $Name = $T['Name'] . " currently in " . System_Name($N,$Fid);
      return $Name;
  }
}

function Report_SP_Change(&$World) {
  include_once("TurnTools.php");
  $Fid = $World['FactionId'];
  if (empty($Fid)) return;
  TurnLog($Fid,"There has been a change of Social Principles on " . World_Name($World['id'],$Fid));
}
