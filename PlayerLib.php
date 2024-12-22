<?php

// Lib of player stuff
include_once("sk.php");
include_once("GetPut.php");

$PlayerState = ['Setup', 'Turn Planning' , 'Turn Submitted', 'Turn Being Processed','Frozen'];
$PlayerStateColours = ['Orange','lightblue','LightGreen','pink','White'];
$PlayerStates = array_flip($PlayerState);
$FoodTypes = ['Omnivore','Herbivore','Carnivore'];

$Currencies = ['Credits','Physics Science Points','Engineering Science Points','Xenology Science Points','General Science Points'];
$Relations = [9=>['Allied','lightgreen'],7=>['Friendly','lightyellow'],5=>['Neutral','lightblue'],3=>['Wary','Orange'],1=>['Hostile','Red']];

global $PlayerState,$PlayerStates,$Currencies,$FoodTypes,$Relations;

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
  global $FACTION,$PlayerState,$PlayerStates,$PlayerStateColours,$GAME,$ARMY,$USER,$ARMIES;


  dostaffhead("Things",["js/ProjectTools.js"]);
  $GM = Access('GM');
  $FF = 1; //FactionFeature('AllowActions',$GM);  // Eventually change GM to 1

  if (empty($FACTION['id'])) {
    echo "<h1>No Faction Selected</h1>";
    dotail();
  }

  $Fid = $FACTION['id'];

  if (!$GM || $FACTION['NPC'] ) {
    $FACTION['LastActive'] = time();
    Put_Faction($FACTION);
  }

  $Factions = Get_Factions();
  $Facts = Get_FactionFactions($Fid);

  echo "<h1>Player Actions: " . $FACTION['Name'] . "</h1>\n";

  $TState = $PlayerState[$FACTION['TurnState']] ;


  echo "<h2>Player state: <span style='background:" . $PlayerStateColours[$FACTION['TurnState']] . "'>$TState</span>" .
      (($FACTION['TurnState']<3)?" Turn:" . $GAME['Turn']:'') . "</h2>";
  if (($GM && $TState != 'Setup') || isset($_REQUEST['SEEALL'])) $TState = 'Turn Planning';

  echo "<div class=Player>";
  if ((!$GM) && $TState == 'Turn Submitted') echo "<b>To change anything, cancel the turn submission first.</b><br>";
  if (FactionFeature('BackHereHelp',1) ) echo "The only current actions are:";
  echo "<ul>";


  switch ($TState) {
  case 'Setup':
    echo "<li><a href=SetupFaction.php>Faction Setup</a>\n";
    if (!$GM && $FACTION['Horizon'] == 0) break; // This prevents access to things until setup completed
    echo "<li><a href=PThingList.php>List of Things</a> - List of Things (Ships, $ARMIES, Named Characters, Space stations etc)";
    echo "<li><a href=ThingPlan.php>Plan a Thing</a> - Planning Things (Ships, $ARMIES, Named Characters, Space stations etc)";
    if (!Access('God')) break;

  case 'Frozen':
  case 'Turn Submitted':
    if (!$GM) fm_addall('readonly');
  case 'Turn Planning':
    if (Feature('NodeMap')) {
      echo "<li><a href=MapFull.php?Links=0>Faction Map</a>";
      echo " (<a href=MapFull.php?Hex&Links=1>With Link identities)</a>\n";
    }
    if (!Feature('NodeMap') || Has_Tech($FACTION['id'],'Astral Mapping')) {
      echo "<li><a href=MapFull.php?Hex&Links=0>Faction Map</a> - with spatial location of nodes\n";
      echo " (<a href=MapFull.php?Hex>With Link identities)</a>\n";
    }
    echo "<ul>";
      echo "<li><a href=PSystemList.php>List of Known Systems</a>\n";
      echo "<li><a href=WorldList.php>Worlds and Colonies</a> - High Level info only\n";
      echo "<li><a href=NamePlaces.php>Name Places</a> - Systems, Planets etc\n";
      echo "<li><a href=PAnomalyList.php>Anomalies that have been seen</a><p>\n";
    echo "</ul>";


    echo "<li>Doing Stuff<ul>";
      echo "<li><a href=ProjDisp.php>Projects</a>\n";
      if (Feature('Orgs')) echo "<li><a href=OpsDisp.php>Operations</a>\n";
      echo "<li><a href=PThingList.php>List of Things</a> - List of Things (Ships, $ARMIES, Named Characters, Space stations etc)";
      if ($PlayerState[$FACTION['TurnState']] != 'Frozen') {
        echo "<li><a href=ThingPlan.php>Plan a Thing</a> - Planning Things (Ships, $ARMIES, Named Characters, Space stations etc)";
      }
      if ($FACTION['PhysicsSP'] >=5 || $FACTION['EngineeringSP'] >=5 || $FACTION['XenologySP'] >=5 ) {
        echo "<li><a href=SciencePoints.php>Spend Science Points</a>";
      }
      if ($Facts || $FACTION['NPC']) {
        echo "<li><a href=MapTransfer.php>Transfer Mapping knowledge to another Faction</a>\n";
      }
    echo "</ul>";

    echo "<p><li><a href=PlayerTurnTxt.php>Turn Actions Automated Response Text</a>";
    echo "<ul><li><a href=WhatCanIC.php>What Things can I See?</a></ul>\n";

    if ($PlayerState[$FACTION['TurnState']] == 'Turn Planning') {
      echo "<li><a href=Player.php?ACTION=Submit>Submit Turn</a><p>\n";
    } else {
      echo "<li><a href=Player.php?ACTION=Unsub>Cancel Submission</a><p>\n";
    }

    echo "<p><li><a href=FactionEdit.php>Faction Information</a> - Mostly read only once set up.\n";
    echo "<ul>";
      if ($Facts || $FACTION['NPC']) {
        echo "<li><a href=FactionCarry.php>Relationship with Other Factions</a> - Also to allow Individuals and $ARMIES aboard and repairing.\n";
      }
      echo "<li><a href=Tracked.php>Tracked Resources and Properties</a>\n";
      if (Feature('Orgs')) echo "<li><a href=OrgList.php>Organisations</a>\n";
      if (Feature('Orgs')) echo "<li><a href=ListSocial.php>Social Principles</a>\n";
      echo "<li><a href=Economy.php>Economy</a>";
      echo "<li><a href=Banking.php>Banking</a><p>";
    echo "</ul>";

    echo "<li>Information: ";
    echo "<ul>";
      echo "<li><a href=TechShow.php?PLAYER>Technologies</a> (inc current levels)";
      echo "<li><a href=ModuleShow.php?PLAYER>Module Types</a> ";
      echo "<li><a href=OperTypesShow.php>Operation Types</a>";
    echo "</ul>";

    break;

  case 'Turn Being Processed':
    if ($GM) echo "<p><li><a href=Player.php?SEEALL>(GM) See All Actions</a>\n";
    break;
  }

  if ($GM) {
    echo "<p><li>GM: <a href=TechShow.php?SETUP&id=$Fid>Edit Technologies</a>";
    echo "<li>GM: <a href=SplitFaction.php?ACTION=Start>Split Faction</a>\n";
  }

  echo "</ul>";
  echo "</div>";

  echo "<li><a href=UserGuide.php>User Guide</a> - Warning this is VERY OUT OF DATE...<p>\n";
  echo "<li><a href=../StarKingdoms.php>Index of Star Kingdom's Games.</a>";
  echo "<li><a href=Login.php?ACTION=NEWPASSWD>New Password</a>\n";


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

function Has_PTraitW($Wid,$Name) { // Has World got trait Name
  $W = Get_World($Wid);
  if (!$W) return false;
  switch ($W['ThingType']){
    case 1:// Planet
      $P = Get_Planet($W['ThingId']);
      if ($P['Trait1'] == $Name || $P['Trait2'] == $Name || $P['Trait3'] == $Name) return true;
      return false;
    case 1:// Moon
      $P = Get_Moon($W['ThingId']);
      if ($P['Trait1'] == $Name || $P['Trait2'] == $Name || $P['Trait3'] == $Name) return true;
      return false;
    case 1:// Thing
     return Has_PTraitT($W['ThingId'],$Name);
  }
}

function Has_PTraitH($Hid,$Name) { // Has Home got trait Name
  $W = Get_ProjectHome($Hid);
  switch ($W['ThingType']){
    case 1:// Planet
      $P = Get_Planet($W['ThingId']);
      if ($P['Trait1'] == $Name || $P['Trait2'] == $Name || $P['Trait3'] == $Name) return true;
      return false;
    case 1:// Moon
      $P = Get_Moon($W['ThingId']);
      if ($P['Trait1'] == $Name || $P['Trait2'] == $Name || $P['Trait3'] == $Name) return true;
      return false;
    case 1:// Thing
      return Has_PTraitT($W['ThingId'],$Name);
  }
}

function Has_PTraitP($Pid,$Name) { // Has Planet got trait Name
  $P = Get_Planet($Pid);
  if ($P && ($P['Trait1'] == $Name || $P['Trait2'] == $Name || $P['Trait3'] == $Name)) return true;
  return false;
}

function Has_PTraitM($Pid,$Name) { // Has Planet got trait Name
  $P = Get_Moon($Pid);
  if ($P && ($P['Trait1'] == $Name || $P['Trait2'] == $Name || $P['Trait3'] == $Name)) return true;
  return false;
}

function Has_PTraitT($Tid,$Name) { // Has Thing got trait Name - from system it is in
  $T = Get_Thing($Tid);
  $N = Get_System($T['SystemId']);
  if ($N && ($N['Trait1'] == $Name || $N['Trait2'] == $Name || $N['Trait3'] == $Name)) return true;
// NOT COMPLETE
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
  $Fact = Get_Faction($Fid);
//  $PTypes = Get_PlanetTypes();

  $PC = Has_Tech($Fid,"Planetary Construction");

  if ($worldid == $Fact['HomeWorld']) $PC++;
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
      $Target = $Fact['Biosphere'];
      break;
  }

  if (($Target != $Fact['Biosphere']) && ($Target != $Fact['Biosphere2']) && ($Target != $Fact['Biosphere3'])) {
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

function IsPrime($n) {
  $Primes = [2,3,5,7,11,13,17,19,23,29,31,37,41,43,47,53,59,61,67,71,73,79,83,89,97];
  return in_array($n,$Primes);
}

function Logistics($Fid,&$Things) {
  global $LogistCost;
  $Facts = Get_Factions();
  $Faction = $Facts[$Fid];
  $TTypes = Get_ThingTypes();

  $HasHomeLogistics = Has_Tech($Fid,'Simplified Home Logistics');
  $HomeArmyLogistics = Has_PTraitW($Faction['HomeWorld'],'Universal Hunting Ground');
  $HasOwnGalaxy = (Has_Trait($Fid,'Own the Galaxy'));

  $FactionHome = 0;
  if ($HasHomeLogistics) {
    $Home = $Faction['HomeWorld'];
    if ($Home) {
      $W = Get_World($Home);
      if ($W) {
        switch ($W['ThingType']) {
          case 1: // Planet
            $P = Get_Planet($W['ThingId']);
            $FactionHome = $P['SystemId'];
            break;
          case 2: // Moon
            $M = Get_Moon($W['ThingId']);
            $P = Get_Planet($W['PlanetId']);
            $FactionHome = $P['SystemId'];
            break;
          case 3: // Things
            $TH = Get_Thing($W['ThingId']);
            $FactionHome = $TH['SystemId'];
            break;
        }
      }
    }
  }

  $Logistics = [0,0,0]; // Ship, Army, Intelligence
  foreach ($Things as $T) {
    if (empty($T['Type'])) continue;
    $Props = $TTypes[$T['Type']]['Properties'];
    if ($T['BuildState'] == 2 || $T['BuildState'] == 3) {
      if ($HasHomeLogistics && ($T['SystemId'] == $Facts[$T['Whose']]['HomeWorld'])) $T['Level'] /=2;
      if ($HomeArmyLogistics && ($Props & THING_HAS_ARMYMODULES) && ($T['SystemId'] == $FactionHome)) {
        continue; // Nocost
      } else {
        if ($Props & THING_HAS_ARMYMODULES) $Logistics[1] += $LogistCost[$T['Level']];
        if ($Props & THING_HAS_GADGETS) $Logistics[2] += $LogistCost[$T['Level']];
        if ($Props & ( THING_HAS_MILSHIPMODS | THING_HAS_CIVSHIPMODS)) {
          if ($HasOwnGalaxy && str_contains($T['Class'],'Freighter')) {
            $Logistics[0] += $LogistCost[$T['Level']-1];
          } else {
            $Logistics[0] += $LogistCost[$T['Level']];
          }
        }
      }
    };
  }

  foreach($Logistics as &$Log) $Log = floor($Log);

  return $Logistics;
}


function Income_Calc($Fid) {
  global $GAMEID,$ARMY,$ARMIES,$LogistCost;
  include_once("HomesLib.php");

  $Worlds = Get_Worlds($Fid);
  $Facts = Get_Factions();
  $Faction = $Facts[$Fid];
  $BTypes = Get_BranchTypes();
  $BTypeNames = NamesList($BTypes);
  $NameBType = array_flip($BTypeNames);
  $TTypes = Get_ThingTypes();

  $HasOwnGalaxy = (Has_Trait($Fid,'Own the Galaxy'));

  $EconVal = 0;
  $EccTxt = "\nEconomy:<p>";
  $OutPosts = $AstMines = $AstVal = $Embassies = $OtherEmbs = $MineFields = 0;

  foreach ($Worlds as $Wid=>$W) {
    $H = Get_ProjectHome($W['Home']);
    if (empty($H)) continue;
    $PH = Project_Home_Thing($H);
    if (!$PH) continue;
    $Name = $PH['Name'];
    $EccTxt .= "<br>\n$Name: <br>";
    [$ECon,$Rtxt] = Recalc_Economic_Rating($H,$W,$Fid);
    $H['Economy'] = $ECon;
    $EccTxt .= $Rtxt;

    $Offs = Gen_Get_Cond('Offices',"World=$Wid AND OrgType=2");
    if ($Offs && !$HasOwnGalaxy ) {
      foreach ($Offs as $Off ) {
        $Org = Gen_Get('Organisations',$Off['Organisation']);
        $ECon += $Org['OfficeCount'];
        $EccTxt .= "Plus " . $Org['OfficeCount'] . " From an office of " . $Org['Name'] . "<br\n";
      }
    }
    if ($W['Revolt']) {
      $ECon = 0;
      $EccTxt .=  "It is in <b>Revolt</b> no income<br>\n";
    } else {
      if ($H['Devastation']) {
        $ECon = $ECon - $H['Devastation'];
        $EccTxt .= " It has devastation reducing it to: $ECon <br>\n";
      }

      if ($W['Blockade'] ) {
        $ECon = ceil($ECon*(10-$W['Blockade'])/10);
        $EccTxt .=  "It is blockaded income is reduced to $ECon\n";
      } else {
        $ECon = ceil(($ECon - $H['Devastation'])*$H['EconomyFactor']/100);
      }
    }

    if (Has_PTraitW($W['id'],'Thin Atmosphere')) {
      $ECon = max(0,$ECon-2);
      $EccTxt .=  " Reduced by 2 due to a Thin Atmosphere leaving you with a rating of $ECon<br>\n";
    }

    if ($ECon <=0 && $Name) {
      $EccTxt .= "Economic value is None\n";
    } else {
      $EccTxt .= "Economic value is: $ECon " . ($H['Devastation']? " after devastation effect of -" . $H['Devastation'] : "");
      if ($H['EconomyFactor'] < 100) {
        $EccTxt .= " - at " . $H['EconomyFactor'] . "%<br>\n";
      } else if ($H['EconomyFactor'] > 100) {
        $EccTxt .= " - at " . $H['EconomyFactor'] . "%<br>\n";
      }
      $EconVal += $ECon;
    }

    $OtherPTSBranches = Gen_Get_Cond('Branches',"Whose!=$Fid AND HostType!=3 AND HostId=$Wid AND Type=" . ($NameBType['Trading Station']??0));
    if ($OtherPTSBranches) {
      $OtherTrade = 0;
      foreach( $OtherPTSBranches as $B) {
        $Org = Gen_Get('Organisations',$B['Organisation']);
        $OtherTrade += $Org['OfficeCount'];
      }
      if ($OtherTrade){
        $EccTxt .= "Plus incomming trade of other's trade organisations worth: $OtherTrade<br>\n";
        $EconVal += $OtherTrade;
      }
    }

    $EccTxt .=  "<br>\n";
  }
  $EccTxt .=  "<br>";
  $Things = Get_Things_Cond($Fid,'BuildState=3');
  foreach ($Things as $T) {
    if (empty($TTypes[$T['Type']])) continue;
    switch ($TTypes[$T['Type']]['Name']) {
      case "Outpost":
        $OutPosts ++;
        break;

      case "Asteroid Mine":
        $AstMines ++;
        if (Feature('AstmineDSC')) {
          $AstVal += $T['Level'];
        } else {
          $Plan = Get_Planet($T['Dist1']);
          $AstVal += (($Plan['Minerals']??0) + ((Has_PTraitP($W['id'],'Rare Mineral Deposits') && Has_Tech($Fid,'Advanced Mineral Extraction'))?3:0))*$T['Level'];
        }
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

  $MyPTSBranches = Gen_Get_Cond('Branches',"Whose=$Fid AND HostType!=3 AND Type=" . ($NameBType['Trading Station']??0));
  $MyPBMPBranches = Gen_Get_Cond('Branches',"Whose=$Fid AND HostType!=3 AND Type=" . ($NameBType['Black Market Trade Station']??0));
  $MyOPBranches = Gen_Get_Cond('Branches',"Whose=$Fid AND HostType=3 AND Type=" . ($NameBType['Trading Station']??0));
// var_dump($MyPTSBranches,$MyPBMPBranches,$MyOPBranches,$OtherPTSBranches);
  $MyTrade = 0;
  $Orgs = [];

  if ($MyPTSBranches || $MyOPBranches || $MyPBMPBranches) {
    if ($MyPTSBranches && !$HasOwnGalaxy ) {
      foreach ($MyPTSBranches as $Bid=>$B ) {
        if (!isset($Orgs[$B['Organisation']])) $Orgs[$B['Organisation']] = Gen_Get('Organisations',$B['Organisation']);
        $MyTrade += $Orgs[$B['Organisation']]['OfficeCount'];
      }
    }
    if ($MyPBMPBranches&& !$HasOwnGalaxy ) {
      foreach ($MyPTSBranches as $Bid=>$B ) {
        if (!isset($Orgs[$B['Organisation']])) $Orgs[$B['Organisation']] = Gen_Get('Organisations',$B['Organisation']);
        $MyTrade += $Orgs[$B['Organisation']]['OfficeCount'];
      }
    }
    if ($MyOPBranches && !$HasOwnGalaxy ) {
      foreach ($MyOPBranches as $Bid=>$B ) {
  //      if (!isset($Orgs[$B['Organisation']])) $Orgs[$B['Organisation']] = Gen_Get('Organisations',$B['Organisation']);
        $MyTrade += 2;
      }
    }

    if ($MyTrade){
      $EccTxt .= "Plus Outgoing trade of my trade organisations worth: $MyTrade<br>\n";
      $EconVal += $MyTrade;
    }
  }


  $OtherTs = Get_Things_Cond(0,"Type=17 AND OtherFaction=$Fid AND BuildState=3");
  foreach($OtherTs as $OT) {
    $OtherEmbs++;
  }

  if (Feature('OutPostTrade') && $OutPosts) {
    $EccTxt .= "Plus $OutPosts Outposts worth 2 each<br>\n";
    $EconVal += $OutPosts*2;
  }
  if ($AstMines) {
    if (Feature('AstmineDSC')) $AstVal *= Has_Tech($Fid,'Deep Space Construction');
    $EccTxt .= "Plus $AstMines Asteroid Mines worth a total of $AstVal<br>\n";
    $EconVal += $AstVal;
  }
  if ($Embassies) {
    $EccTxt .= "Plus $Embassies of your Embassies worth 1 each<br>\n";
    $EconVal += $Embassies;
  }
  if ($OtherEmbs) {
    $EccTxt .= "Plus $OtherEmbs of other Embassies worth 1 each<br>\n";
    $EconVal += $OtherEmbs;
  }

  if ($MineFields) {
    $EccTxt .= "Less $MineFields of Minefields worth 1 each<br>\n";
    $EconVal -= $MineFields;
  }

  $Logistics = Logistics($Fid,$Things);
  $LogAvail = LogisticalSupport($Fid);
  $LogCats = ['Ships',$ARMIES,'Agents'];

  foreach ($LogCats as $i => $n) {
    if ($Logistics[$i]) {
      $pen = min(0,$LogAvail[$i]-$Logistics[$i]);
      if ($pen < 0) {
        $EconVal += $pen;
        $EccTxt .= "<p>Logistical penalty of $pen for $n<p>\n";
      }
    }
  }

  $EccTxt .= "Total Economy is $EconVal worth " . $EconVal*10 . "<p>\n\n";
  return [$EconVal,$EccTxt];

}

function Faction_Feature($Name,$Default=0){
  global $FACTION;
  static $Features;
  if (empty($FACTION) || empty($FACTION['Features'])) return $Default;
  if (empty($Features)) {
    $Features = parse_ini_string($FACTION['Features']?? '');
  }
  return ($Features[$Name] ?? Feature($Name,$Default));
}

function Tracks() {
  static $Tracks;
  if ($Tracks) return $Tracks;
  $Tracks = Gen_Get_All('ResourceTypes');
  return $Tracks;
}

function Has_Track($Fid,$Name) {
  $TNames = array_flip(NamesList(Tracks()));
  $Trackid = $TNames[$Name];
  $Have = Gen_Get_Cond('Resources',"Whose=$Fid AND Type=$Trackid");
  return $Have['Value']??0;
}

