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

define('ORG_HIDDEN',1);
define('ORG_ALLOPS',2);
define('ORG_NO_BRANCHES',4);
define('ORG_SPECIAL_POWER',8);

define('BRANCH_HIDDEN',1);
define('BRANCH_NOSPACE',2);

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

  foreach ($Orgs as $O) if (($OrgTypes[$O['OrgType']]['Props'] & ORG_SPECIAL_POWER) == 0) Gen_Put('Organisations',$O);

}

function SocPrinciples($Fid) {
  $SocPs = Gen_Get_Cond('SocialPrinciples',"Whose=$Fid");
  $A = [];
  foreach ($SocPs as $i=>$P) $A[$i] = $P['Principle'];
  return $A;
}

function Link_Search($Sys,$lnks) { // Part of Op_Level (Recusive)
  global $Min,$Targets,$SysLnks,$Depth,$BeenHere;
//echo "LS: $Sys, $lnks<p>";
//var_dump($BeenHere);
  if ($lnks > 20) return -2;
  if (isset($Targets[$Sys])) return $lnks;
  if ($BeenHere[$Sys] || ($lnks>=$Min)) return -3;
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
  global $Min,$Targets,$SysLnks,$Depth,$BeenHere;
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
//  $Depth = $Org['OfficeCount']-$Mod;

// var_dump($Links, $LKnown,$SKnown1,$SKnown);
  if (!isset($SKnown[$Sys])) return -1; // Unknown target system

  $SRefs = Get_SystemRefs();
  $RSyss = array_flip($SRefs);
  $Targets = [];
  $Worlds = Get_Worlds($Fid);
  $Branches = Gen_Get_Cond('Branches', "Whose=$Fid AND Organisation=$Orgid");
  if ($Branches) foreach ($Branches as $B) {
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
        $Targets[$B['SystemId']] = 1;
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
        $Targets[$B['SystemId']] = 2;
        break;
    }
  }

  // Targets should now identify the systems it needs to reach
  if (isset($_REQUEST['SHOWTARGS'])) var_dump($Targets,$SKnown);
  if (isset($Targets[$Sys])) return 0; // Range 0
// var_dump($LKnown);
  // Make double linked list of links to systems
  $SysLnks = [];
  $BeenHere = [];
  foreach($LKnown as $si=>$K) {
    $L = $Links[$K['LinkId']];
    $S1 = $RSyss[$L['System1Ref']];
    $S2 = $RSyss[$L['System2Ref']];
    if (isset($SysLnks[$S1])) {
      $SysLnks[$S1][] = $S2;
    } else {
      $SysLnks[$S1] = [$S2];
    }
    if (isset($SysLnks[$S2])) {
      $SysLnks[$S2][] = $S1;
    } else {
      $SysLnks[$S2] = [$S1];
    }
    $BeenHere[$S1] = $BeenHere[$S2] = 0;
  }
  foreach (array_keys($SKnown) as $Si) $BeenHere[$Si] = 0;
  if (!isset($SysLnks[$Sys])) return -1; // No known links there
//  $BeenHere[$Sys] = 1;
//var_dump($BeenHere,$Depth);
  $Min = 100;
  $Min = Link_Search($Sys,0);
//  var_dump($Min2);
  return $Min;// ($Min>99?-1:$Min);
}

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
