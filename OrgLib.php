<?php
include_once("sk.php");
include_once("GetPut.php");

define('OPER_LEVEL',0xf);
define('OPER_HIDDEN',0x10);

function Recalc_Offices() { // Recount offices for each org
  global $GAMEID;
  $Offs = Gen_Get_All('Offices', " WHERE GameId=$GAMEID");
  $Orgs = Gen_Get_All('Organisations', " WHERE GameId=$GAMEID");
  $OCount = [];

  foreach ($Orgs as &$O) $O['OfficeCount'] = 0;

  foreach ($Offs as $oi=>$O) {
    $Org = $O['Organisation'];
    if (isset($Orgs[$Org])) {
      $Orgs[$Org]['OfficeCount']++;
      if ($O['OrgType'] != $Orgs[$Org]['OrgType']) {
        $O['OrgType'] = $Orgs[$Org]['OrgType'];
        Gen_Put('Offices',$O);
      }
    } else {
      echo "Office $oi does not match any current Organisation - needs fixing<p>";
    }
  }

  foreach ($Orgs as &$O) Gen_Put('Organisations',$O);
}

function SocPrinciples($Fid) {
  $SocPs = Gen_Get_Cond('SocialPrinciples',"Whose=$Fid");
  $A = [];
  foreach ($SocPs as $i=>$P) $A[$i] = $P['Principle'];
  return $A;
}

function Link_Search($Sys,$lnks) { // Part of Op_Level (Recusive)
  global $Min,$Targets,$SysLnks,$Depth,$BeenHere;
echo "LS: $Sys, $lnks<p>";
//var_dump($BeenHere);
  if ($lnks > $Depth) return -2;
  if (isset($Targets[$Sys])) return $lnks;
  if ($BeenHere[$Sys] || ($lnks>=$Min)) return -3;
  echo "A";
  $BeenHere[$Sys] = $lnks+1;
  // if ($Sys)
  echo "A";
  $Any = 0;
  foreach ($SysLnks[$Sys] as $Si) {
    echo "B$Si";
    if ($BeenHere[$Si]??99) continue;
    echo "C";
    $D = Link_Search($Si,$lnks+1);
echo "<br>D$D=$Min=";
    if (($D >= 0) && ($D < $Min)) {
      $Min = $D;
      $Any = 1;
    }
  }
  $BeenHere[$Sys] = 0;
echo "E$Any=";
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
  foreach($SKnown1 as &$K) $SKnown[$K['SystemId']] = $K;
  $Links = Get_LinksGame();
  $Depth = $Org['OfficeCount']-$Mod;

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
        $Targets[$P['SystemId']] = 1;
        break;
      case 2: // Moon
        $M = Get_Moon($W['ThingId']);
        $P = Get_Planet($M['PlanetId']);
        $Targets[$P['SystemId']] = 1;
        break;
      case 3: // Thing
        $P = Get_Thing($W['ThingId']);
        $Targets[$B['SystemId']] = 1;
        break;
    }
  }

  // Targets should now identify the systems it needs to reach
  if (isset($_REQUEST['SHOWTARGS'])) var_dump($Targets,$SKnown);
  if (isset($Targets[$Sys])) return 0; // Range 0

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
  foreach ($SKnown as $Si=>$K) $BeenHere[$Si] = 0;
  if (!isset($SysLnks[$Sys])) return -1; // No known links there
//  $BeenHere[$Sys] = 1;
//var_dump($BeenHere,$Depth);
  $Min = 100;
  $Min = Link_Search($Sys,0);
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
