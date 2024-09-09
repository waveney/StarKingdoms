<?php

// Lib of player stuff
include_once("sk.php");
include_once("GetPut.php");

$PlayerState = ['Setup', 'Turn Planning' , 'Turn Submitted', 'Turn Being Processed','Frozen'];
$PlayerStateColours = ['Orange','lightblue','LightGreen','pink','White'];
$PlayerStates = array_flip($PlayerState);

$Currencies = ['Credits','Physics Science Points','Engineering Science Points','Xenology Science Points','General Science Points'];

global $PlayerState,$PlayerStates,$Currencies;

function CheckFaction($Prog='Player',$Fid=0) {
  if (!Access('Player') && !Access('GM')) {
    dostaffhead("Who are you?");
    echo "<h2>Who are you?</h2>";
    echo "If you are are a player, reuse your personal link.<p>";
    echo "If you are a GM go to the login prompt and type your username and password.<p>";
    dotail();
  }
  if (!$Fid) {
      dostaffhead("Faction needed");
      echo "<h1>For What faction?</h1>";
      echo "<form method=post action=$Prog.php>";
      $Facts = Get_Faction_Names();
      echo fm_select($Facts,$_REQUEST,'F');
      echo "<input type=submit value=Submit>";
      dotail();
    }
}

function AddCurrencies() {
  global $Currencies;
  if ($Nam = GameFeature('Currency1')) $Currencies[5] = $Nam;
  if ($Nam = GameFeature('Currency2')) $Currencies[6] = $Nam;
  if ($Nam = GameFeature('Currency3')) $Currencies[7] = $Nam;
  return $Currencies;
}

function TradeableCurrencies() {
  global $Currencies;
  $Trade = [1,0,0,0,0];
  if (GameFeature('TradeCurrency1')) $Trade[5] = 1;
  if (GameFeature('TradeCurrency2')) $Trade[6] = 1;
  if (GameFeature('TradeCurrency3')) $Trade[7] = 1;
  return $Trade;
}

function FactionFeature($Name,$default='') {  // Return value of feature if set from GAMESYS
  static $Features;
  global $FACTION;
  if (!$Features) {
    $Features = [];
    foreach (explode("\n",$FACTION['Features']) as $i=>$feat) {
      $Dat = explode(":",$feat,4);
      if ($Dat[0] && isset($Dat[1])) {
        $Features[$Dat[0]] = trim($Dat[1]);
      } elseif ($Dat[0] && isset($Dat[4])) {
        $Features[$Dat[0]] = trim($Dat[4]);
      }
    }
  }
  if (isset($Features[$Name])) return $Features[$Name];
  return $default;
}

function Player_Page() {
  global $FACTION,$PlayerState,$PlayerStates,$PlayerStateColours,$GAME,$ARMY;


  dostaffhead("Things",["js/ProjectTools.js"]);
  $GM = Access('GM');
  $FF = 1; //FactionFeature('AllowActions',$GM);  // Eventually change GM to 1

  if (empty($FACTION['id'])) {
    echo "<h1>No Faction Selected</h1>";
    dotail();
  }

  $Fid = $FACTION['id'];
  $FACTION['LastActive'] = time();

  if (!$GM || $FACTION['NPC'] ) Put_Faction($FACTION);

//var_dump($PlayerState,$FACTION);
  echo "<h1>Player Actions: " . $FACTION['Name'] . "</h1>\n";

  $TState = $PlayerState[$FACTION['TurnState']] ;


  echo "<h2>Player state: <span style='background:" . $PlayerStateColours[$FACTION['TurnState']] . "'>$TState</span> Turn:" .
       $GAME['Turn'] . "</h2>";
  if (($GM && $TState != 'Setup') || isset($_REQUEST['SEEALL'])) $TState = 'Turn Planning';

  echo "<div class=Player>";
  if ((!$GM) && $TState == 'Turn Submitted') echo "<b>To change anything, cancel the turn submission first.</b><br>";
  echo "The only current actions are:";
  echo "<ul>";

  echo "<p><li><a href=UserGuide.php>User Guide</a><p>\n";
  if ($GM) echo "<li>GM: <a href=TechShow.php?SETUP&id=$Fid>Edit Technologies</a>\n";

  switch ($TState) {
  case 'Setup':
    echo "<li><a href=TechShow.php?PLAYER>Technologies</a>\n";
    echo "<li><a href=SetupFaction.php>Faction Setup<a>\n";
    echo "<li><a href=PThingList.php>List of Things</a> - List of Things (Ships, Armies, Space stations etc)";
    echo "<li><a href=ThingPlan.php>Plan a Thing</a> - Planning Things (Ships, Armies, Space stations etc)";
    if (!Access('God')) break;

  case 'Frozen':
  case 'Turn Submitted':
    if (!$GM) fm_addall('readonly');
  case 'Turn Planning':
    echo "<li><a href=MapFull.php>Faction Map</a>\n";
    if (Has_Tech($FACTION['id'],'Astral Mapping')) {
      echo "<li><a href=MapFull.php?Hex&Links=0>Faction Map</a> - with spatial location of nodes\n";
      echo "(<a href=MapFull.php?Hex>With Link Numbers)</a>\n";
    }
    echo "<li><a href=TechShow.php?PLAYER>Technologies</a>\n";
    echo "<p><li><a href=WhatCanIC.php>What Things can I See?</a>\n";
    echo "<li><a href=WorldList.php>Worlds and Colonies</a> - High Level info only\n";
    echo "<li><a href=OrgList.php>Organisations</a>\n";

    echo "<li><a href=NamePlaces.php>Name Places</a> - Systems, Planets etc\n";
//    echo "<li><a href=PScanList.php>Systems and Scans</a>\n";
    echo "<li><a href=PAnomalyList.php>Anomalies that have been seen</a><p>\n";
    echo "<li><a href=ProjDisp.php>Projects</a>\n";
    if (Feature('Orgs')) echo "<li><a href=OpsDisp.php>Operations</a>\n";
    echo "<li><a href=PThingList.php>List of Things</a> - List of Things (Ships, Armies, Agents, Space stations etc)";
     if ($PlayerState[$FACTION['TurnState']] != 'Frozen') echo "<li><a href=ThingPlan.php>Plan a Thing</a> - Planning Things (Ships, Armies, Agents, Space stations etc)";
    if ($FACTION['PhysicsSP'] >=5 || $FACTION['EngineeringSP'] >=5 || $FACTION['XenologySP'] >=5 ) echo "<li><a href=SciencePoints.php>Spend Science Points</a>";
    echo "<P><li><a href=Economy.php>Economy</a>";
    echo "<li><a href=Banking.php>Banking</a> - Sending credits to others and statements<p>";
    echo "<li><a href=PlayerTurnTxt.php>Turn Actions Automated Response Text</a>";
//    echo "<li><a href=PlayerTurn.php>Submit Player Turn text</a> - For now a link to a Google Docs file.<p>\n";
    if ($PlayerState[$FACTION['TurnState']] == 'Turn Planning') {
      echo "<li><a href=Player.php?ACTION=Submit>Submit Turn</a><p>\n";
    } else {
      echo "<li><a href=Player.php?ACTION=Unsub>Cancel Submission</a><p>\n";
    }
    echo "<li><a href=FactionEdit.php>Faction Information</a> - Mostly read only once set up.\n";
    echo "<li><a href=FactionCarry.php>Allow Others Access</a> - To allow individuals and armies aboard, use of Warp gates and repairing.\n";
    if ($GM) echo "<p><li>GM: <a href=SplitFaction.php?ACTION=Start>Split Faction</a>\n";
    break;

  case 'Turn Being Processed':
    echo "<li><a href=TechShow.php?PLAYER>Technologies</a>\n";

    if ($GM) echo "<p><li><a href=Player.php?SEEALL>(GM) See All Actions</a>\n";
    break;

  }

  echo "</ul>";
  echo "</div>";

  echo "<li><a href=../StarKingdoms.php>Index of Star Kingdom's Games</a>";


  dotail();

}

function Has_Trait($fid,$Name) {
  global $FACTION;
  if ($fid == 0) {
    if (!isset($FACTION) || empty($FACTION)) return false;
    if ($FACTION['Trait1'] == $Name || $FACTION['Trait2'] == $Name || $FACTION['Trait3'] == $Name) return true;
    return false;
  }
  $Fact = Get_Faction($fid);
  if ($Fact['Trait1'] == $Name || $Fact['Trait2'] == $Name || $Fact['Trait3'] == $Name) return true;
  return false;
}

function Ships() {
  global $FACTION;

}

function Spend_Credit($Who,$Amount,$Why,$From='') { // Ammount is negative to gain credits
  global $GAME;
  $Fact = Get_Faction($Who);
  $StartC = $Fact['Credits'];
  $CR = ['Whose'=>$Who, 'StartCredits'=>$StartC, 'Amount'=>$Amount, 'YourRef'=>$Why, 'Turn'=>$GAME['Turn'], 'FromWho'=>$From];
  if ($StartC-$Amount < 0) {
    $CR['Status'] = 0;
    Put_CreditLog($CR);
    return 0;
  } else {
    $Fact['Credits'] -= $Amount;
    Put_Faction($Fact);
    $CR['Status'] = 1;
    $CR['EndCredits'] = $Fact['Credits'];
    Put_CreditLog($CR);
    return 1;
  }
}

function Gain_Science($Who,$What,$Amount,$Why) { // Ammount is negative to gain credits
  global $GAME;
  $Fact = Get_Faction($Who);
  switch ($What) {
  case 1:
    $Fact['PhysicsSP'] = ($Fact['PhysicsSP'] ?? 0) + $Amount;
    break;
  case 2:
    $Fact['EngineeringSP'] = ($Fact['EngineeringSP'] ?? 0) + $Amount;
    break;
  case 3:
    $Fact['XenologySP'] = ($Fact['XenologySP'] ?? 0) + $Amount;
    break;
  case 4: // Random
    for($sp =1; $sp <= $Amount; $sp++) {
      switch (rand(1,3)) {
      case 1:
        $Fact['PhysicsSP'] = ($Fact['PhysicsSP'] ?? 0) + 1;
        break;
      case 2:
        $Fact['EngineeringSP'] = ($Fact['EngineeringSP'] ?? 0) + 1;
        break;
      case 3:
        $Fact['XenologySP'] = ($Fact['XenologySP'] ?? 0) + $Amount + 1;
        break;
      }
    }
  }

//  var_dump($Fact);
  Put_Faction($Fact);
}

function Gain_Currency($Who,$What,$Amount,$Why) { // Ammount is negative to gain
  if ($What <=4) return 0; // Should be handled by spend_credit and Gain Science TODO generalise in long term
  global $GAME,$Currencies;
  $Fact = Get_Faction($Who);
  $CNum = $What-4;
  if ($CNum > 3) { echo "INVALID CURRENCY!!!"; exit; }
  if (($Fact["Currency$CNum"] + $Amount) < 0 ) return 0;
  $Fact["Currency$CNum"] += $Amount;
  Put_Faction($Fact);
  return 1;
}

function Link_Cost($Fid,$LinkLevel,$ShipLevel) {
  if ($LinkLevel == 1) return '';

}

function Credit() {
  return "&#8373;";
}

function Income_Estimate($Fid) {
  include_once("ThingLib.php");
  include_once("HomesLib.php");

  $TTypes = Get_ThingTypes();

  $Worlds = Get_Worlds($Fid);
  $EconVal = 0;
  $OutPosts = $AstMines = $AstVal = $Embassies = $OtherEmbs = $MineFields = 0;
  foreach ($Worlds as $W) {
    $H = Get_ProjectHome($W['Home']);
    if (!$H) continue;
    $PH = Project_Home_Thing($H);
    if (!$PH) continue;

    $ECon = $H['Economy'] = Recalc_Economic_Rating($H,$W,$Fid);

    if ($W['Revolt']) {
      $ECon = 0;
    } else {
      if ($H['Devastation']) {
        $ECon = $ECon - $H['Devastation'];
      }

      if ($W['Blockade'] ) { //&& $Fid != 9) {
        $ECon /= 2;
      }

      $ECon = ceil($ECon*$H['EconomyFactor']/100);
    }
    $EconVal += $ECon;
  }

  $Things = Get_Things($Fid);
  foreach ($Things as $T) {
    if (empty($TTypes[$T['Type']])) continue;
    switch ($TTypes[$T['Type']]['Name']) {
    case "Outpost":
      $OutPosts ++;
      break;

    case "Asteroid Mine":
      $AstMines ++;
      $AstVal += $T['Level'];
      break;

    case "Embassy":
      $Embassies ++;
      break;

    case "Minefield":
      $MineFields ++;
      break;

    default:
      continue 2;
    }
  }

  $OtherTs = Get_Things_Cond(0,"Type=17 AND OtherFaction=$Fid");
  foreach($OtherTs as $OT) $OtherEmbs++;

  if ($OutPosts) $EconVal += $OutPosts*2;

  if ($AstMines) {
    $AstVal *= Has_Tech($Fid,'Deep Space Construction');
    $EconVal += $AstVal;
  }
  if ($Embassies) $EconVal += $Embassies;

  if ($OtherEmbs) $EconVal += $OtherEmbs;

  if ($MineFields) $EconVal -= $MineFields;

  $Logistics = [0,0,0]; // Ship, Army, Intelligence
  foreach ($Things as $T) {
    if (empty($T['Type'])) continue;
    $Props = $TTypes[$T['Type']]['Properties'];
    if ($T['BuildState'] == 2 || $T['BuildState'] == 3) {
      if ($Props & THING_HAS_ARMYMODULES) $Logistics[1] += $T['Level'];
      if ($Props & THING_HAS_GADGETS) $Logistics[2] += $T['Level'];
      if ($Props & ( THING_HAS_MILSHIPMODS | THING_HAS_CIVSHIPMODS)) $Logistics[0] += $T['Level'];
    };
  }


  $LogAvail = LogisticalSupport($Fid);
  $LogCats = ['Ships','Armies','Agents'];

  foreach ($LogCats as $i => $n) {
    if ($Logistics[$i]) {
      $pen = min(0,$LogAvail[$i]-$Logistics[$i]);
      if ($pen < 0) $EconVal += $pen;
    }
  }
  return $EconVal*10;
}

function WhatCanBeSeenBy($Fid,$Mode=0) {
  $MyThings = Get_Things($Fid);
  $MyHomes = Get_ProjectHomes($Fid);
  $ThingTypes = Get_ThingTypes();
  $SRefs = Get_SystemRefs();

  $Factions = Get_Factions();

  $Places = [];
  $Hosts = [];

  $txt = '';

  foreach ($MyThings as $T) {
    if ($T['BuildState'] < 2 || $T['BuildState']> 3) continue; // Ignore things not in use
    $Sid = $T['SystemId'];
    if ($T['LinkId'] == -1 || $T['LinkId'] == -3) {
      $Hosts[$T['SystemId']][] = $T['id']; // On board something
      continue;
    } else if ($Sid == 0) continue; // Not Anywhere

 //echo "Adding " .$T['Name'] . " " .$T['id'] . " " . $T['SystemId'] . "<br>";
    $Eyes = EyesInSystem($Fid,$Sid);
    $Places[$Sid] = (empty($Places[$Sid])? $Eyes : ($Places[$Sid] | $Eyes));
    $OthersOnBoard = Get_Things_Cond(0,"Whose!=$Fid AND LinkId<0 AND SystemId=" . $T['id']);
    if ($OthersOnBoard) {
      foreach($OthersOnBoard as $O) $Hosts[$T['id']][] = $O['id'];
    }
  }

  $txt .= "Everything of yours and what they can see.<p>";
//  echo "Note: Things on board other things (e.g. Named Characters) do not show up in this display - currently<p>";

  foreach ($MyHomes as $H) {
    switch ($H['ThingType']) {
    case 1:
      $P = Get_Planet($H['ThingId']);
      $Sid = $P['SystemId'];
      break;
    case 2:
      $M = Get_Moon($H['ThingId']);
      if ($M) $P = Get_Planet($M['PlanetId']);
      if (!empty($P)) $Sid = $P['SystemId'];
      break;
    case 3: // Thing - already done
      continue 2;
    }
    if (!$Sid) continue;
    $Places[$Sid] = (empty($Places[$Sid])? 8 : ($Places[$Sid] | 8));
  }

  foreach ($SRefs as $Sid=>$Ref) {
    if (!isset($Places[$Sid])) continue;
    $Eyes = $Places[$Sid];
    $txt .= SeeInSystem($Sid,$Eyes,1,1,$Fid);
  }

  $LastWhose = 0;

  if (!empty($Hosts)) {
    foreach($Hosts as $Hid=>$H) {
      if (empty($H)) continue;
      $HostT = isset($MyThings[$Hid]) ? $MyThings[$Hid] : Get_Thing($Hid);
      $txt .= "<h2>On Board " . (empty ($HostT['Name'])? "Unknown Thing" : $HostT['Name'])  . " is:</h2>";
      foreach($H as $Tid) {
        $T = Get_Thing($Tid);
        $txt .= SeeThing($T,$LastWhose,15,$T['Whose'],1,$Mode);
      }
    }
  }

  return $txt;
}

function PlanConst($Fid,$worldid) { // Effective Plan Construction
  global $FACTION;
  $PTypes = Get_PlanetTypes();

  $PC = Has_Tech($Fid,"Planetary Construction");

  if ($worldid == $FACTION['HomeWorld']) $PC++;
  $World = Get_World($worldid);

  switch ($World['ThingType']) {
    case 1: // Planet
      $P = Get_Planet($World['ThingId']);
      $Target = $P['Type'];
      break;

    case 2: // Moon
      $P = Get_Moon($World['ThingId']);
      $Target = $P['Type'];
      break;

    case 3: // Thing
      $Target = $FACTION['BioSphere'];
      break;
  }

  if (($Target != $FACTION['BioSphere']) && ($Target != $FACTION['BioSphere2']) && ($Target != $FACTION['BioSphere3'])) {
    if ($Target == 4) {
      $PC-=2;
    } else {
      $PC--;
    }
  }
  if (($Target == 2) && Has_Tech($Fid,"Construction Techniques (Arctic)")) $PC++;
  if (($Target == 5) && Has_Tech($Fid,"Construction Techniques (Temperate)")) $PC++;
  if (($Target == 6) && Has_Tech($Fid,"Construction Techniques (Desert)")) $PC++;
  if (($Target == 16) && Has_Tech($Fid,"Construction Techniques (Water)")) $PC++;
  if (($Target == 4) && Has_Tech($Fid,"Construction Techniques (Desolate)")) $PC+=2;
  return max(0,$PC);
}
