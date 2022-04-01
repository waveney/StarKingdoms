<?php

// Lib of player stuff
include_once("sk.php");
include_once("GetPut.php");

$PlayerState = ['Setup', 'Turn Planning' , 'Turn Submitted', 'Turn Being Processed'];
$PlayerStateColours = ['Orange','lightblue','LightGreen','pink'];
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
  global $FACTION,$PlayerState,$PlayerStates,$PlayerStateColours,$GAME;


  dostaffhead("Things",["js/ProjectTools.js"]);  
  $GM = Access('GM');
  $FF = 1; //FactionFeature('AllowActions',$GM);  // Eventually change GM to 1

  $FACTION['LastActive'] = time();
  
  if (!$GM || $FACTION['NPC'] ) Put_Faction($FACTION);
  
//var_dump($PlayerState,$FACTION);
  echo "<h1>Player Actions: " . $FACTION['Name'] . "</h1>\n";
  
  $TState = $PlayerState[$FACTION['TurnState']] ;
  
  echo "<h2>Player state: <span style='background:" . $PlayerStateColours[$FACTION['TurnState']] . "'>$TState</span> Turn:" .
       $GAME['Turn'] . "</h2>";
  echo "<div class=Player>";
  echo "The only current actions are:";
  echo "<ul>";

  echo "<p><li><a href=UserGuide.php>User Guide</a><p>\n";
  
  if (isset($_REQUEST['SEEALL'])) $TState = 'Turn Planning';
  switch ($TState) {
  case 'Setup':
    echo "<li><a href=TechShow.php?PLAYER>Technologies</a>\n";    
    echo "<li><a href=Setup.php>Setup</a>\n";
    echo "<li><a href=ThingSetup.php?T=Ships>Setup Ships</a>\n";    
    echo "<li><a href=ThingSetup.php?T=Army>Setup Armys</a>\n";    
    echo "<li><a href=ThingSetup.php?T=Agent>Setup Agents</a>\n";    
    break;
  
  
  case 'Turn Planning':
    echo "<li><a href=MapFull.php>Faction Map</a>\n";
    if (Has_Tech($FACTION['id'],'Astral Mapping')) echo "<li><a href=MapFull.php?Hex>Faction Map</a> - with spatial location of nodes\n";
    echo "<li><a href=TechShow.php?PLAYER>Technologies</a><p>\n";
    echo "<li><a href=WhatCanIC.php>What Things can I See?</a>\n";
    echo "<li><a href=WorldList.php>Worlds and Colonies</a> - High Level info only\n";  
    echo "<li><a href=NamePlaces.php>Name Places</a> - Systems, Planets etc<p>\n";      
    echo "<li><a href=ProjDisp.php>Projects</a>\n";
    echo "<li><a href=PThingList.php>List of Things</a> - List of Things (Ships, Armies, Agents, Space stations etc)";
    echo "<li><a href=ThingPlan.php>Plan a Thing</a> - Planning Things (Ships, Armies, Agents, Space stations etc)<P>";
//    if ($FACTION['PhysicsSP'] >=5 || $FACTION['EngineeringSP'] >=5 || $FACTION['XenologySP'] >=5 ) echo "<li><a href=SciencePoints.php>Spend Science Points</a>";
    echo "<li><a href=Economy.php>Economy</a>";
    echo "<li><a href=Banking.php>Banking</a> - Sending credits to others and statements<p>";
    echo "<li><a href=PlayerTurnTxt.php>Turn Actions Automated Response Text</a>";
//    echo "<li><a href=PlayerTurn.php>Submit Player Turn text</a> - For now a link to a Google Docs file.<p>\n";
    echo "<li><a href=Player.php?ACTION=Submit>Submit Turn</a><p>\n";
    echo "<li><a href=FactionEdit.php>Faction Information</a> - Mostly read only once set up.\n";
    echo "<li><a href=FactionCarry.php>Carry Others Control</a> - To allow individuals and armies aboard.\n";
    break;
      
  case 'Turn Submitted':
    echo "<li><a href=MapFull.php?PLAYER>Faction Map</a>\n";
    echo "<li><a href=TechShow.php?PLAYER>Technologies</a>\n";  
    echo "<li><a href=Player.php?ACTION=Unsub>Cancel Submission</a>\n";        

    if ($GM) echo "<p><li><a href=Player.php?SEEALL>(GM) See All Actions</a>\n";
    break;
      
  case 'Turn Being Processed':
    echo "<li><a href=TechShow.php?PLAYER>Technologies</a>\n";
    
    if ($GM) echo "<p><li><a href=Player.php?SEEALL>(GM) See All Actions</a>\n";
    break;  

  }

  echo "</ul>";
  echo "</div>";
  
  dotail();

}

function Has_Trait($fid,$Name) {
  global $FACTION;
  if ($fid == 0) {
    if (!isset($FACTION)) return false;
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

function Spend_Credit($Who,$Amount,$Why) { // Ammount is negative to gain credits
  global $GAME;
  $Fact = Get_Faction($Who);
  $StartC = $Fact['Credits'];
  $CR = ['Whose'=>$Who, 'StartCredits'=>$StartC, 'Amount'=>$Amount, 'YourRef'=>$Why, 'Turn'=>$GAME['Turn']];
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
    $Fact['PhysicsSP'] += $Amount;
    break;
  case 2: 
    $Fact['EngineeringSP'] += $Amount;
    break;
  case 3: 
    $Fact['XenologySP'] += $Amount;
    break;
  case 4: // Random
    for($sp =1; $sp <= $Amount; $sp++) {
      switch (rand(1,3)) {
      case 1: 
        $Fact['PhysicsSP'] ++;
        break;
      case 2: 
        $Fact['EngineeringSP'] ++;
        break;
      case 3: 
        $Fact['XenologySP'] ++;
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
  $OutPosts = $AstMines = $AstVal = $Embassies = $OtherEmbs = 0;
  foreach ($Worlds as $W) {
    $H = Get_ProjectHome($W['Home']);
    if (!$H) continue;
    $PH = Project_Home_Thing($H);
    if (!$PH) continue;

    $ECon = $H['Economy'] = Recalc_Economic_Rating($H,$W,$Fid);
      
    if ($H['Devastation']) {
      $ECon = $ECon - $H['Devastation'];
    }
    $ECon = ceil($ECon*$H['EconomyFactor']/100);
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

?>
