<?php

// Lib of player stuff
include_once("sk.php");
include_once("GetPut.php");

global $PlayerState,$PlayerStates,$Currencies,$FoodTypes,$Relations;

$PlayerState = ['Setup', 'Turn Planning' , 'Turn Submitted', 'Turn Being Processed','Frozen'];
$PlayerStateColours = ['Orange','lightblue','LightGreen','pink','White'];
$PlayerStates = array_flip($PlayerState);
$FoodTypes = ['Omnivore','Herbivore','Carnivore'];

$Currencies = ['Credits','Physics Science Points','Engineering Science Points','Xenology Science Points','General Science Points'];
$Relations = [9=>['Allied','lightgreen'],7=>['Friendly','lightyellow'],5=>['Neutral','lightblue'],3=>['Wary','Orange'],1=>['Hostile','Red']];
$PayFactionRates = ['Once Only','Every Turn','Every 2nd Turn','Every 3rd Turn'];

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

function Player_Page() {
  global $FACTION,$PlayerState,$PlayerStates,$PlayerStateColours,$GAME,$ARMY,$USER,$ARMIES;


  dostaffhead("Things",["js/ProjectTools.js"]);
  $GM = Access('GM');
  $FF = 1; //Faction_Feature('AllowActions',$GM);  // Eventually change GM to 1

  if (empty($FACTION['id'])) {
    echo "<h1>No Faction Selected</h1>";
    dotail();
  }

  $Fid = $FACTION['id'];
  $Designs = Feature('Designs');

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
  if (Faction_Feature('BackHereHelp',1) ) echo "The only current actions are:";
  echo "<ul>";


  switch ($TState) {
  case 'Setup':
    echo "<li><a href=SetupFaction.php>Faction Setup</a>\n";
    if (!$GM && $FACTION['Horizon'] == 0) break; // This prevents access to things until setup completed
    echo "<li><a href=PThingList.php>List of Things</a> - List of Things (Ships, $ARMIES, Named Characters, Space stations etc)";
    if ($Designs) {
      echo "<li><a href=PlanDesign.php>Plan a Design</a> - Designing Classes of Things (Ships, $ARMIES, Space stations etc)";
      echo "<li><a href=CreateNamed.php>Create a Named Character</a>";
    } else {
      echo "<li><a href=ThingPlan.php>Plan a Thing</a> - Planning Things (Ships, $ARMIES, Named Characters, Space stations etc)";
    }
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
      echo "<li><a href=PThingList.php>List of Things</a> - List of Things (Ships, $ARMIES, Designs, Named Characters, Space stations etc)";
      if ($PlayerState[$FACTION['TurnState']] != 'Frozen') {
        if ($Designs) {
          echo "<li><a href=PlanDesign.php>Plan a Design</a> - Designing Classes of Things (Ships, $ARMIES, Space stations etc)";
          echo "<li><a href=CreateNamed.php>Create a Named Character</a>";
        } else {
          echo "<li><a href=ThingPlan.php>Plan a Thing</a> - Planning Things (Ships, $ARMIES, Named Characters, Space stations etc)";
        }
      }
      if ($FACTION['PhysicsSP'] >=5 || $FACTION['EngineeringSP'] >=5 || $FACTION['XenologySP'] >=5 ) {
        echo "<li><a href=SciencePoints.php>Spend Science Points</a> - Also <a href=ScienceLog.php>Resource Logs</a>";
//        echo "<li><a href=ScienceLog.php>Science Point Logs</a>";
      }
      if ($Facts || $FACTION['NPC']) {
        echo "<li><a href=MapTransfer.php>Transfer Mapping knowledge to another Faction</a> - Also transfer logs\n";
//        echo "<li><a href=MapTransferLogs.php>Logs of Mapping Transfers</a>\n";
      }
      if ((Feature('Currency1') == 'Flux Crystals') && $FACTION['Currency1'] && Has_Tech($Fid,'Flux Crystal Use')) {
        echo "<li><a href=FluxCrystals.php>Use Flux Crystals</a>\n";
      }
    echo "</ul>";

    echo "<p><li><a href=PlayerTurnTxt.php>Turn Actions Automated Response Text</a>";
    echo "<ul>";
//    if (Access('God')) echo "<li>God: <a href=EditText.php>Edit Auto Text</a>";
    echo "<li><a href=WhatCanIC.php>What Things can I See?</a></ul>\n";


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
    if (0 && Access('God')) {
      echo "<li>God: <a href=DoSurvey.php>Do Survey - local only</a>\n";

    }
  }

  echo "</ul>";
  echo "</div>";

  echo "<li><a href=https://starkingdoms.eaglesroost.org target=_blank>Rules Wiki</a> - opens in a separate tab<p>\n";
  echo "<li><a href=UserGuide.php>User Guide</a> - Warning this is VERY OUT OF DATE...\n";
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
  if ($Fact && ($Fact['Trait1'] == $Name || $Fact['Trait2'] == $Name || $Fact['Trait3'] == $Name)) return true;
  return false;
}

function Has_PTraitW($Wid,$Name) { // Has World got trait Name
  if ($Wid <=0) return false;
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
  if ($Hid <=0) return false;
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
  if ($Pid <=0) return false;
  $P = Get_Planet($Pid);
  if ($P && ($P['Trait1'] == $Name || $P['Trait2'] == $Name || $P['Trait3'] == $Name)) return true;
  return false;
}

function Has_PTraitM($Pid,$Name) { // Has Moon got trait Name
  if ($Pid <=0) return false;
  $P = Get_Moon($Pid);
  if ($P && ($P['Trait1'] == $Name || $P['Trait2'] == $Name || $P['Trait3'] == $Name)) return true;
  return false;
}

function Has_PTraitT($Tid,$Name) { // Has Thing got trait Name - from system it is in
  if ($Tid <=0) return false;
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
  if ($GAME['id'] != $Fact['GameId']) Get_Game( $Fact['GameId']);
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

function Gain_Science($Who,$What,$Amount,$Why='') { // Ammount is negative to gain credits
//  var_dump($Who,$What,$Amount,$Why);
  global $GAME;
  $Fact = Get_Faction($Who);
  $ltype = 0;
  $Now = 0;
  switch ($What) {
  case 1:
  case 'Physics':
    $Now = $Fact['PhysicsSP'] = ($Fact['PhysicsSP'] ?? 0) + $Amount;
    $ltype = 2;
    break;
  case 2:
  case 'Engineering':
    $Now = $Fact['EngineeringSP'] = ($Fact['EngineeringSP'] ?? 0) + $Amount;
    $ltype = 1;
    break;
  case 3:
  case 'Xenology':
    $Now = $Fact['XenologySP'] = ($Fact['XenologySP'] ?? 0) + $Amount;
    $ltype = 3;
    break;
  case 4: // Random
  case 'General':
    $ltype = 4;
    $Why .= " - ";
    for($sp =1; $sp <= $Amount; $sp++) {
      switch (rand(1,3)) {
      case 1:
        $Fact['PhysicsSP'] = ($Fact['PhysicsSP'] ?? 0) + 1;
        $Why .= "P";
        break;
      case 2:
        $Fact['EngineeringSP'] = ($Fact['EngineeringSP'] ?? 0) + 1;
        $Why .= "E";
        break;
      case 3:
        $Fact['XenologySP'] = ($Fact['XenologySP'] ?? 0) + 1;
        $Why .= "X";
        break;
      }
    }
  }

//  var_dump($Fact);
  Put_Faction($Fact);
  $Spog = ['GameId'=>$GAME['id'],'Turn'=>$GAME['Turn'],'FactionId'=>$Who, 'Type'=>$ltype, 'Number'=>$Amount, 'Note'=>$Why, 'EndVal'=>$Now];
  Gen_Put('SciencePointLog',$Spog);
}

function Gain_Currency($Who,$What,$Amount,$Why) { // Ammount is negative to gain
  global $GAME;
  $Fact = Get_Faction($Who);
  if (is_numeric($What)) {
    if ($What <=4) return 0; // Should be handled by spend_credit and Gain Science TODO generalise in long term
    global $GAME,$Currencies;
    $CNum = $What-4;
    if ($CNum > 3) { echo "INVALID CURRENCY!!! $What - $Who - $Amount - $Why "; debug_print_backtrace(); exit; }
  } else {
    for ($CNum=1;$CNum<4;$CNum++) {
      if ($What == Feature("Currency$CNum")) break;
    }
    if ($CNum > 3) { echo "INVALID CURRENCY!!! $What - $Who - $Amount - $Why "; debug_print_backtrace(); exit; }
  }

  if (($Fact["Currency$CNum"] + $Amount) < 0 ) return 0;
  $Fact["Currency$CNum"] += $Amount;
  Put_Faction($Fact);

  $Spog = ['GameId'=>$GAME['id'],'Turn'=>$GAME['Turn'],'FactionId'=>$Who, 'Type'=>$CNum+10, 'Number'=>$Amount, 'Note'=>$Why,
    'EndVal'=>$Fact["Currency$CNum"]];
  Gen_Put('SciencePointLog',$Spog);

  return 1;
}

function Link_Cost($Fid,$LinkLevel,$ShipLevel) {
  if ($LinkLevel == 1) return '';

}

function Credit() {
  return "&#8373;";
}

function WhatCanBeSeenBy($Fid,$Move=0) { // If Move = 1, it will report previous moves of things if the eyes are static
  include_once("SeeThings.php");
  $Terrains = ['All','Space','Ground'];
  $MyThings = Get_Things($Fid);
  $MyHomes = Get_ProjectHomes($Fid);
  $ThingTypes = Get_ThingTypes();
  $SRefs = Get_SystemRefs();

  $Factions = Get_Factions();

  $Places = [];
  $Hosts = [];

  $Where = ['Where'=>0];
  $Jtxt = '<h2>Jump to: ';
  $txt = "<div class=floatright>" . fm_radio('Show',$Terrains,$Where,'Where',' onchange=WhereFilter()') . "</div>";


  foreach ($MyThings as $T) {
    if ($T['BuildState'] < BS_SERVICE || $T['BuildState']> BS_COMPLETE) continue; // Ignore things not in use
    $Sid = $T['SystemId'];
    if ($T['LinkId'] == -1 || $T['LinkId'] == -3) {
      $Host = Get_Thing($T['SystemId']);
      if ($Host['Whose'] != $Fid)  $Hosts[$T['SystemId']][] = $T['id']; // On board something
      continue;
    } else if ($Sid == 0) continue; // Not Anywhere

 //echo "Adding " .$T['Name'] . " " .$T['id'] . " " . $T['SystemId'] . "<br>";
    $Eyes = EyesInSystem($Fid,$Sid);
    $Places[$Sid] = (empty($Places[$Sid])? $Eyes : ($Places[$Sid] | $Eyes));
//    $OthersOnBoard = Get_Things_Cond(0,"Whose!=$Fid AND LinkId<0 AND SystemId=" . $T['id']);
//    if ($OthersOnBoard) {
//      foreach($OthersOnBoard as $O) $Hosts[$T['id']][] = $O['id'];
//    }
  }

  // Check all branches

  $Branches = Gen_Get_Cond('Branches',"Whose=$Fid");
  if ($Branches) {
    $BCheck = [];
    foreach($Branches as $B) {
      if (isset($BCheck[$B['HostType']][$B['HostId']])) continue;
      $BCheck[$B['HostType']][$B['HostId']] = 1;

      switch ($B['HostType']) {
        case 1: // Planet
          $P = Get_Planet($B['HostId']);
          $Sid = $P['SystemId'];
          $Places[$Sid] = (empty($Places[$Sid])? 8 : ($Places[$Sid] | 8));
          break;

        case 2: // Moon
          $M = Get_Moon($B['HostId']);
          $P = Get_Planet($M['PlanetId']);
          $Sid = $P['SystemId'];
          $Places[$Sid] = (empty($Places[$Sid])? 8 : ($Places[$Sid] | 8));
          break;

        case 3: // Thing
          $T = Get_Thing($B['HostId']);
          if (!$T) {
            GMLog("Branch " . $B['id'] . " has a host error - call Richard");
            break;
          }

          $Sid = $T['SystemId'];
          $Places[$Sid] = (empty($Places[$Sid])? 1 : ($Places[$Sid] | 1));
          break;
      }
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
    $Places[$Sid] = 15; // See all -> old code for on planet -> (empty($Places[$Sid])? 15 : ($Places[$Sid] | 8));
  }

  foreach ($SRefs as $Sid=>$Ref) {
    if (!isset($Places[$Sid])) continue;
    $Eyes = $Places[$Sid];
//    if (!$Move) $Eyes = ($Eyes&15); - don't work...
    $Jtxt .= " <a href='#Locn:$Ref'>$Ref</a> ";
    $txt .= "<div id='Locn:$Ref'></div>" . SeeInSystem($Sid,$Eyes,1,1,$Fid);
  }

  $LastWhose = 0;

  if (!empty($Hosts)) {
    $Facts = Get_Factions();
    foreach($Hosts as $Hid=>$H) {
      if (empty($H)) continue;
      $HostT = isset($MyThings[$Hid]) ? $MyThings[$Hid] : Get_Thing($Hid);

      $LocClass='Space';
      if (isset($HostT['WithinSysLoc'])) {
        $LocType = intdiv($HostT['WithinSysLoc'],100);
        if (($HostT['WithinSysLoc'] == 3) || $LocType==2 || $LocType==4 ) $LocClass = 'Ground';
      }

      $txt .= "<hr><div  class=$LocClass><h2>On Board: " . (empty ($HostT['Name'])? "Unknown Thing" : $HostT['Name'])  .
        " ( <span " . FactColours($HostT['Whose']) . ">" .
        ($Facts[$HostT['Whose']]['Adjective']?$Facts[$HostT['Whose']]['Adjective']:$Facts[$HostT['Whose']]['Name']) .
        "</span> ) is:</h2>";
      foreach($H as $Tid) {
        $T = Get_Thing($Tid);
        $txt .= SeeThing($T,$LastWhose,15,$T['Whose'],1);
      }
      $txt .= "</div>";
    }
  }

  return $Jtxt . "</h2>" . $txt;
}

function PlanConst($Fid,$worldid) { // Effective Plan Construction
  $Fact = Get_Faction($Fid);
//  $PTypes = Get_PlanetTypes();

  $PC = Has_Tech($Fid,"Planetary Construction");

  if ($worldid == $Fact['HomeWorld']) $PC++;
  if ($worldid >0) {
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
  if ($HasHomeLogistics || $HomeArmyLogistics) {
    $Home = $Faction['HomeWorld'];
    if ($Home > 0) {
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
    if ($T['BuildState'] == BS_SERVICE || $T['BuildState'] == BS_COMPLETE) {
      if ($TTypes[$T['Type']]['Prop2'] & THING_ALWAYS_OTHER) continue;
      if ($HasHomeLogistics && ($T['SystemId'] == $Facts[$T['Whose']]['HomeWorld'])) $T['Level'] /=2;
      if ($HomeArmyLogistics && ($Props & THING_HAS_ARMYMODULES) && ($T['SystemId'] == $FactionHome)) {
        continue; // Nocost
      } else {
        if ($Props & THING_HAS_ARMYMODULES) $Logistics[1] += $LogistCost[$T['Level']];
        if ($Props & THING_HAS_GADGETS) $Logistics[2] += $LogistCost[$T['Level']];
        if ($Props & ( THING_HAS_MILSHIPMODS | THING_HAS_CIVSHIPMODS)) {
          if (( $TTypes[$T['Type']]['Prop2'] & THING_NO_LOGISTICS) && ($T['LinkId']<0) ) continue;
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
  include_once("SystemLib.php");

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

    $Offs = Gen_Get_Cond('Offices',"World=$Wid AND (OrgType=2 OR OrgType2=2) AND Whose=$Fid");
    if ($Offs && !$HasOwnGalaxy ) {
      foreach ($Offs as $Off ) {
        $Org = Gen_Get('Organisations',$Off['Organisation']);
        if ($Org) {
          $ECon += $Org['OfficeCount'];
          $EccTxt .= "Plus " . $Org['OfficeCount'] . " From an office of " . $Org['Name'] . "<br>\n";
        } else {
          GMLog4Later("Failed to fetch org " . $Off['Organisation'] . " When doing Income Calc on $Wid for $Fid - For Richard");
        }
      }
    }
    if ($W['Revolt']) {
      $ECon = 0;
      $EccTxt .=  "It is in <b>Revolt</b> no income<br>\n";
    } else {
      if ($H['Devastation']) {
        $ECon = $ECon - $H['Devastation']*2;
        $EccTxt .= " It has devastation reducing it to: $ECon <br>\n";
      }

      if ($W['Blockade'] ) {
        $ECon = ceil($ECon*(10-$W['Blockade'])/10);
        $EccTxt .=  "It is blockaded income is reduced to $ECon\n";
      }
    }

    if ($ECon && Has_PTraitW($W['id'],'Thin Atmosphere')) {
      $ECon = max(0,$ECon-2);
      $EccTxt .=  " Reduced by 2 due to a Thin Atmosphere leaving you with a rating of $ECon<br>\n";
    }

    if ($ECon <=0 && $Name) {
      $EccTxt .= "Economic value is None\n";
    } else {
      if ($H['EconomyFactor'] != 100) $ECon = ceil($ECon*$H['EconomyFactor']/100);
      $EccTxt .= "Economic value is: $ECon ";
      if ($H['EconomyFactor'] < 100) {
        $EccTxt .= " - at " . $H['EconomyFactor'] . "%<br>\n";
      } else if ($H['EconomyFactor'] > 100) {
        $EccTxt .= " - at " . $H['EconomyFactor'] . "%<br>\n";
      } else {
        $EccTxt .= "<br>\n";
      }
      $EconVal += $ECon;
    }

    $OtherPTSBranches = Gen_Get_Cond('Branches',"Whose!=$Fid AND HostType!=3 AND HostId=" . $W['ThingId'] . " AND Type=" . ($NameBType['Trading Station']??0));
    if ($OtherPTSBranches) {
      $OtherTrade = 0;
      foreach( $OtherPTSBranches as $B) {
        $Org = Gen_Get('Organisations',$B['Organisation']);
        if ($Org['OfficeCount']) {
          $EccTxt .= "Plus incomming trade from " . $Org['Name'] . " (<span " . FactColours($Org['Whose']) . ">" . $Facts[$Org['Whose']]['Name'] .
            "</span>) branch worth: " . $Org['OfficeCount'] . "<br>\n";
          $EconVal += $Org['OfficeCount'];
        }
      }
    }

    $OtherPBMPBranches = Gen_Get_Cond('Branches',"HostType!=3 AND HostId=" . $W['ThingId'] .
      " AND Suppressed=0 AND Type=" . ($NameBType['Black Market Trading Station']??0));
    if ($OtherPBMPBranches) {
      $BM2 = min($EconVal,count($OtherPBMPBranches)*2);
      $EccTxt .= "Lost $BM2 due to Black Market" . Plural($BM2,'','','s') . "<br>\n";
      $EconVal -= $BM2;
    }


    $OtherOffices = Gen_Get_Cond('Offices',"Whose!=$Fid AND World=$Wid AND (OrgType=2 OR OrgType2=2)");
    if ($OtherOffices) {
      $OtherTrade = 0;
      foreach( $OtherOffices as $O) {
        $Org = Gen_Get('Organisations',$O['Organisation']);
        if ($Org['OfficeCount']) {
          $EccTxt .= "Plus incomming trade from " . $Org['Name'] . " (<span " . FactColours($Org['Whose']) . ">" . $Facts[$Org['Whose']]['Name'] .
          "</span>) trade organisations offices worth: " . $Org['OfficeCount'] . "<br>\n";
          $EconVal += $Org['OfficeCount'];
        }
      }
    }


    $EccTxt .=  "<br>\n";
  }
  $EccTxt .=  "<br>";

  $OutPosts = count(Get_Things_Cond($Fid,'Type=' . TTName('Outpost') . ' AND BuildState=' . BS_COMPLETE));
  $Embassies = count(Get_Things_Cond($Fid,'Type=' . TTName('Embassy') . ' AND BuildState=' . BS_COMPLETE));
  $MineFields = count(Get_Things_Cond($Fid,'Type=' . TTName('Minefield') . ' AND BuildState=' . BS_COMPLETE));
  $AstMines = Get_Things_Cond($Fid,'Type=' . TTName('Asteroid Mine') . ' AND BuildState=' . BS_COMPLETE . " ORDER BY SystemId");
  $StripMines = Get_Things_Cond($Fid,'Type=' . TTName('Strip Mine') . ' AND BuildState=' . BS_COMPLETE);

  $LastSid = 0;
  $Asts = [];

  foreach ($AstMines as $AM) {
    if ($AM['SystemId'] != $LastSid) {
      $LastSid = $AM['SystemId'];
      $Asts = Gen_Get_Cond('Planets', "SystemId=$LastSid AND Type=3 ORDER BY Minerals DESC");
    }
    $Ast = array_shift($Asts);
    if ($Ast) {
      $AstVal += (($Ast['Minerals']??0) +
        ((Has_PTraitP($Ast['id'],'Rare Mineral Deposits') && Has_Tech($Fid,'Advanced Mineral Extraction'))?3:0))*$AM['Level'];
    }
  }

  foreach ($StripMines as $SM) {
    $Bid = $SM['Dist1'];
    if ($Bid>0) {
      $Body = Get_Planet($Bid);
    } else {
      $Body = Gen_Moon(-$Bid);
    }
    $SMSys = Get_System($SM['SystemId']);
    $EconVal += $Body['Minerals'] * 3;
    $EccTxt .= "Plus " . $Body['Minerals'] * 3 . " From a Strip Mine on " . $Body['Name'] . " in " . $SMSys['Ref'] . "<br>\n";
  }

  $Things = Get_Things($Fid);

  $MyPTSBranches = Gen_Get_Cond('Branches',"Whose=$Fid AND HostType!=3 AND Type=" . ($NameBType['Trading Station']??0));
  $MyPBMPBranches = Gen_Get_Cond('Branches',"Whose=$Fid AND HostType!=3 AND Type=" . ($NameBType['Black Market Trading Station']??0));
  $MyOPBranches = Gen_Get_Cond('Branches',"Whose=$Fid AND HostType=3 AND Type=" . ($NameBType['Trading Station']??0));
// var_dump($MyPTSBranches,$MyPBMPBranches,$MyOPBranches,$OtherPTSBranches);
  $MyTrade = 0;
  $Orgs = [];

  if ($MyPTSBranches || $MyOPBranches || $MyPBMPBranches) {
    $OGtrade = '';
    if ($MyPTSBranches && !$HasOwnGalaxy ) {
      foreach ($MyPTSBranches as $Bid=>$B ) {
        if ($B['HostType'] ==1 ) {
          $Body = Get_Planet($B['HostId']);
          $Sys = Get_System($Body['SystemId']);
        } else {
          $Body = Get_Moon($B['HostId']);
          $Plan = Get_Planet($Body['PlanetId']);
          $Sys = Get_System($Plan['SystemId']);
        }
        $BW = Gen_Get_Cond1('Worlds',"ThingType=" . $B['HostType'] . " AND ThingId=" . $B['HostId']);
        if ($BW && $BW['Blockade']) {
          $OGtrade .= "Due to a blockade nothing from the Trading Station " . ($B['Name']??'') . " on " . $Body['Name'] .
          " in " . System_Name($Sys, $Fid). "<br>";
        } else {
          if (!isset($Orgs[$B['Organisation']])) $Orgs[$B['Organisation']] = Gen_Get('Organisations',$B['Organisation']);
          $MyTrade += $Orgs[$B['Organisation']]['OfficeCount'];
          $OGtrade .= $Orgs[$B['Organisation']]['OfficeCount'] . " from Trading Station " . ($B['Name']??'') . " on " . $Body['Name'] .
            " in " . System_Name($Sys, $Fid). "<br>";
        }
      }
    }

    if ($MyPBMPBranches && !$HasOwnGalaxy ) {
      foreach ($MyPBMPBranches as $Bid=>$B ) {
          if (!isset($Orgs[$B['Organisation']])) $Orgs[$B['Organisation']] = Gen_Get('Organisations',$B['Organisation']);
          $MyTrade += $Orgs[$B['Organisation']]['OfficeCount']*2;
          if ($B['HostType'] ==1 ) {
            $Body = Get_Planet($B['HostId']);
            $Sys = Get_System($Body['SystemId']);
          } else {
            $Body = Get_Moon($B['HostId']);
            $Plan = Get_Planet($Body['PlanetId']);
            $Sys = Get_System($Plan['SystemId']);
          }
          $OGtrade .= $Orgs[$B['Organisation']]['OfficeCount']*2 . " from Black Market Trading Station " . ($B['Name']??'') . " on " .
            $Body['Name'] . " in " . System_Name($Sys, $Fid). "<br>";
        }
    }
    if ($MyOPBranches && !$HasOwnGalaxy ) {
      foreach ($MyOPBranches as $Bid=>$B ) {
        $Out = Get_Thing($B['HostId']);
        $Sys = Get_System($Out['SystemId']);
        $BW = Gen_Select("SELECT W.Blockade FROM Worlds W INNER JOIN ProjectHomes H ON W.Home=H.id WHERE H.SystemId=" . $Out['SystemId'] . " LIMIT 1");
        if ($BW && $BW[0]['Blockade']) {
          $OGtrade .= "Due to a blockade nothing from Trading Station " . ($B['Name']??'') . " on the Outpost " . $Out['Name'] . " in " .
          System_Name($Sys, $Fid). "<br>";
        } else {
          $MyTrade += 2;
          $OGtrade .= "2 from Trading Station " . ($B['Name']??'') . " on the Outpost " . $Out['Name'] . " in " . System_Name($Sys, $Fid). "<br>";
        }
      }
    }

    if ($MyTrade){
      $EccTxt .= "Plus Outgoing trade of my trade organisations worth: $MyTrade " .
        "<button type=button class=OGBreakdown$Wid onclick=ToggleClass('OGBreakdown$Wid')>Details</button><span class=OGBreakdown$Wid hidden><br>$OGtrade</span><br>\n";
      $EconVal += $MyTrade;
    }
  }

  if (Has_Trait($Fid,'Goverwhat now?')) { //Look for remote offices
    $Offs = Gen_Get_Cond('Offices',"Whose=$Fid AND (OrgType=2 OR OrgType2=2)");
    foreach ($Offs as $Off) {
      $W = Get_World($Off['World']);
      if ($W['FactionId'] != $Fid) { // Remote office...
        $H = Get_ProjectHome($W['Home']);
        $N = Get_System($H['SystemId']);
        $Org = Gen_Get('Organisations',$Off['Organisation']);
        $EconVal += $Org['OfficeCount'];
        $EccTxt .= "Plus " . $Org['OfficeCount'] . " From an office of " . $Org['Name'] . " in " . $N['Ref'] . "<br\n";
      }
    }

  }

  $OtherTs = Get_Things_Cond(0,"Type=17 AND OtherFaction=$Fid AND BuildState=" . BS_COMPLETE);
  foreach($OtherTs as $OT) {
    $OtherEmbs++;
  }

  if (Feature('OutPostTrade') && $OutPosts) {
    $EccTxt .= "Plus $OutPosts Outposts worth 2 each<br>\n";
    $EconVal += $OutPosts*2;
  }
  if ($AstMines) {
    if (Feature('AstmineDSC')) $AstVal *= Has_Tech($Fid,'Space Construction Gear');
    $EccTxt .= "Plus " . count($AstMines) . " Asteroid Mines worth a total of $AstVal<br>\n";
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
  $Have = Gen_Get_Cond1('Resources',"Whose=$Fid AND Type=$Trackid");
  return $Have['Value']??0;
}

// index (0=credits 1-9 SPs, 11-19 Currencies, 20+ Tracked resources
// Ref Name (used in code), Name - if blank Ref Name used, Field name, bits 0-3 datatype 0= Faction, 1= Resourc, bit 4 no running total
function &TrackIndexes() {
  static $LTracks;
  if ($LTracks) return $LTracks;
  $LTracks = [0=>['Credits','Credits','Credits',0],
    1=>['Engineering','Engineering Science Points','EngineeringSP',0],
    2=>['Physics','Physics Science Points','PhysicsSP',0],
    3=>['Xenology','Xenology Science Points','XenologySP',0],
    4=>['General','General Science Points','',16],

    11=>[$n = Feature('Currency1'),$n,'Currency1',0],
    12=>[$n = Feature('Currency2'),$n,'Currency2',0],
    13=>[$n = Feature('Currency3'),$n,'Currency3',0],
  ];

  $RTracks = Tracks();
  foreach ($RTracks as $Rid=>$R) {
    $LTracks[$Rid+20] = [$R['Name'],$R['Name'],'',1];
  }

  return $LTracks;
}

function FactColours($Fid,$deflt='White',$xtra='') {
  static $FCols = [];
  if (!isset($FCols[$Fid])) {
    $Fact = Get_Faction($Fid);
    if ($Fact ) {
      $FCols[$Fid] = "style='background:" . $Fact['MapColour'] . "; color: " . ($Fact['MapText']?$Fact['MapText']:'black') . ";$xtra' ";
    } else {
      $FCols[$Fid] = "style='background:$deflt; $xtra'";
    }
  }
  return $FCols[$Fid];
}

function Relationship($Fac1,$Fac2,$Atleast) {
  global $Relations;
  static $Friends = [];
  static $Relate = [];
  if ($Fac1 == $Fac2) return true;
  if (empty($Friends)) {
    foreach($Relations as $V => $R) $Friends[$R[0]] = $V;
  }
  if (isset($Relate[$Fac1][$Fac2])) return ($Relate[$Fac1][$Fac2]>=$Friends[$Atleast]);

  $FF = Get_FactionFactionFF($Fac1,$Fac2);
// var_dump($FF,$Friends,$Atleast);
  if (!$FF) {
    $Relate[$Fac1][$Fac2] = 0;
    return false;
  }
  if ($FF['Relationship'] == 0) $FF['Relationship'] = 5;
  $Relate[$Fac1][$Fac2] = $FF['Relationship'];

  return ($FF['Relationship'] >= $Friends[$Atleast]);
}
