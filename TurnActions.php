<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("PlayerLib.php");
  include_once("SystemLib.php");
  include_once("ProjLib.php");
  include_once("HomesLib.php");
  include_once("BattleLib.php");
  include_once("TurnTools.php");
  include_once("OrgLib.php");

  A_Check('GM');

  global $TurnActions;


  $TurnActions = [ // Order, Prefix, Function, Code comment
    [ 0,'',         'Check Turns Ready','Coded'],
    [ 1,'',         'Spare',''],
    [ 2,'',         'Start Turn Process','Coded'],
    [ 3,'',         'Save All Locations','Coded'],
    [ 4,'',         'Spare',''], //Remove Unsupported Minefields
    [ 5,'',         'Cash Transfers','Coded'],
    [ 6,'',         'Spare',''],
    [ 7,'Movement',  'Follow','Coded'],
    [ 8,'',         'Faction Actions','Coded'],
    [ 9,'',         'Scientific Breakthroughs','Coded'],
    [10,'Projects',  'Start Projects','Coded,M'],
    [11,'Operations','Start Operations','Coded,M'],
    [12,'Operations','Start Operations Stage 2','Coded'],
    [13,'Instruct',  'Instructions','Coded,M'],
    [14,'Instruct',  'Instructions Stage 2','Coded'],
    [15,'',         'Clear Paid For','Coded'],
    [16,'',         'Spare',''],// Agents Start Missions
    [17,'Projects',  'Pay For Rushes','Coded'],
    [18,'',         'Spare',''],
    [19,'',         'Economy','Coded'],
    [20,'',         'Trait Incomes','Coded'],
    [21,'Movement',  'Direct Moves','Coded'],
    [22,'Movement',  'Load Troops','Coded'],
    [23,'',         'Spare',''],
    [24,'Movement',  'Ship Move Check','Coded,M'],
    [25,'Movement',  'Ship Movements','Coded'],
    [26,'Movement',  'See After Move','Coded'],
    [27,'Combat',    'Meetups','Coded,M'],
    [28,'Combat',    'Space Combat','Help'],
    [29,'Movement',  'Unload Troops','Coded'],
    [30,'Combat',    'Planetary Defence','No'],
    [31,'Combat',    'Orbital Bombardment','No'],
    [32,'Combat',    'Ground Combat','Help'],
    [33,'Combat',    'Devastation Selection','Coded,M'],
    [34,'Combat',    'Devastation','Coded'],
    [35,'',         'Ownership Change','Coded'],
    [36,'Movement',  'Retreats Selection','Coded'],
    [37,'Movement',  'Retreats','Coded'],
    [38,'',         'Affect Activities',''],
    [39,'',         'Affect Activities Actions',''],
    [40,'Projects',  'Project Progress','Coded'],
    [41,'Operations','Operations Progress','Coded'],
    [42,'Instruct',  'Instructions Progress','Coded'],
    [43,'Instruct',  'Collaborative Progress','Coded'],
    [44,'',         'Handle Co Op Projects','Coded,M'],
    [45,'',         'Spare',''], //Finish Shakedowns
    [46,'Projects',  'Refit Projects Complete','Coded'],
    [47,'Projects',  'Projects Complete','Coded'],
    [48,'Operations','Operations Complete','Coded'],
    [49,'Instruct',  'Instructions Complete','Coded'],
    [50,'Survey',    'Check Survey Reports','Coded,M'],
    [51,'Survey',    'Give Survey Reports','Coded'],
    [52,'Survey',    'Check Spot Anomalies','Coded'],//
    [53,'Survey',    'Spot Anomalies','Coded'],
 //   [51,'Survey',    'Check Survey And Anomalies','Coded,M'],
 //   [52,'Survey',    'Give Survey And Anomalies','Coded'],
    [54,'',         'Militia Army Recovery','Coded'],
    [55,'',         'Generate Turns','No'],
    [56,'Combat',   'Return Mil Org Forces','Coded'],
    [57,'',         'Clear Conflict Flags','Coded'],
    [58,'',         'Check Follow Ups','Coded'],
    [59,'',         'Actually Delete Stuff','To be Coded'],
    [60,'Movement',  'Save What Can Be Seen','Coded'],
    [61,'',         'Recalc Project Homes','Coded'],
    [62,'',         'Tidy Ups','Coded'],
    [63,'',         'Enable Factions Access','Coded'],

  ];

function CheckTurnsReady() {
  global $PlayerStates,$PlayerState, $PlayerStateColours;
  $Factions = Get_Factions();
  $AllOK = 1;
  $coln = 0;

  GMLog( "<div class=tablecont><table id=indextable border width=100% style='min-width:1400px'>\n");
  GMLog( "<thead><tr>");
  GMLog( "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Faction</a>\n");
  GMLog( "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Who</a>\n");
  GMLog( "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Last Active</a>\n");
  GMLog( "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>State</a>\n");

  GMLog( "</thead><tbody>");

  foreach($Factions as $F) {
    $Fid = $F['id'];
    GMLog( "<tr><td><a href=FactionEdit.php?F=$Fid>" . $F['Name'] . "</a>");
    GMLog( "<td>" . $F['Player']);
    GMLog( "<td " . FactColours($Fid) . ">"
         . (isset($F['LastActive']) && $F['LastActive']? date('d/m/y H:i:s',$F['LastActive']) :"Never") );
    GMLog( "<td <span style='background:" . $PlayerStateColours[$F['TurnState']] . "'>"  . $PlayerState[$F['TurnState']]);
    if ($F['TurnState'] < 2) {
      $AllOK = 0;
    }
  }
  GMLog( "</table></div>\n");
  if ($AllOK) return 1;
  GMLog( "To proceed you must mark them as submitted<p>\n");
  return 0;
}

function RemoveUnsupportedMinefields() {
  global $ThingInstrs;
  $Rev_Insts = array_flip($ThingInstrs);
  $Facts = Get_Factions();
  $TTypes = Get_ThingTypes();
  $Things = Get_Things_Cond(0,"Instruction=" . $Rev_Insts['Stop Support']);

  foreach ($Things as $T) {
    $Tid = $T['id'];
    $Who = $T['Whose'];
    $SystemRefs = Get_SystemRefs();
    $Discs = Get_DistrictsT($Tid);
    if ($Discs) foreach ($Discs as $D) db_delete('Districts',$D['id']);
    $Mods = Get_DistrictsT($Tid);
    if ($Mods) foreach ($Mods as $M) db_delete('Modules',$M['id']);

    db_delete('Things',$Tid);

    $SysLocs = Within_Sys_Locs($T['SystemId']);
    $LocText = $SysLocs[$T['WithinSysLoc']];
    $TName = $TTypes[$T['Type']]['Name'];
    TurnLog($Who,"The $TName " . $T['Name'] . " in " . $SystemRefs[$T['SystemId']] . " $LocText has been removed.");
    GMLog("The $TName " . $T['Name'] . " in " . $SystemRefs[$T['SystemId']] . " $LocText has been removed.");
  }

  GMLog("Removed Unsupported Things");
  return 1;
}

function StartTurnProcess() {
  global $GAME,$GAMEID,$db;
  // Lock players out
  $Facts = Get_Factions();
  foreach ($Facts as $F) {
    $F['TurnState'] = 3;
    Put_Faction($F);
  }
  GMLog("<br>All Factions marked as Turn Processing<p>");
  if (!file_exists("Turns/$GAMEID/" . $GAME['Turn'])) {
    $LF = mkdir("Turns/$GAMEID/" . $GAME['Turn'],0777,true);
  }

/*
  $Things = Get_AllThings();
  foreach ($Things as $T) {
    if ($T['CurHealth'] && ($T['StartHealth'] != $T['CurHealth'])) {
      $T['StartHealth'] = $T['CurHealth'];
      Put_Thing($T);
    }
  }
*/

  $Rups = Gen_Get_Cond('RepeatFollow',"GameId=$GAMEID");
  if ($Rups) {
    foreach($Rups as $R) {
      FollowUp(0,$R['Name']);
    }
  }

  GMLog("Started Turn Processing");
  return 1;
}

function SaveAllLocations() {
  global $GAME,$GAMEID;
  // Copy location of everything
  $Things = Get_AllThings();
  foreach ($Things as $T) {
    if ($T['BuildState'] == 0) continue;
    $S = ['ThingId'=>$T['id'], 'SystemId'=>$T['SystemId'], 'Turn' => $GAME['Turn'],'BuildState'=> $T['BuildState'],
      'CurHealth'=>$T['CurHealth'], 'Game'=>$GAMEID];
    Insert_db('LocationSave',$S);
  }

  GMLog("Saved all Location data");
  return 1;
}

function CashTransfers() {
  global $GAME,$Currencies;

  $Facts = Get_Factions();
  $Facts[-1]['Name'] = $Facts[0]['Name'] = "Other";
  $Bs = Get_BankingFT(0,$GAME['Turn']);
  AddCurrencies();

  foreach($Bs as $B) {
    if ($B['FactionId'] > 0 && !isset($Facts[$B['FactionId']])) {
      GMLog('Impossible Cash transfer from ' . $B['FactionId'] . "Bank ref " . $B['id']);
      continue;
    }
    if ($B['Recipient'] > 0 && !isset($Facts[$B['Recipient']])) {
      GMLog('Impossible Cash transfer to ' . $B['Recipient'] . "Bank ref " . $B['id']);
      continue;
    }
    if ($B['DoneTurn'] >= $GAME['Turn']) continue; // Already done
    if ($B['What'] == 0) {
      if ($B['FactionId'] == 0) {
          Spend_Credit($B['Recipient'], - $B['Amount'],$B['YourRef']);
          if ($B['Recipient'] > 0) {
            TurnLog($B['Recipient'],  $Facts[$B['FactionId']]['Name'] . " transfered " . Credit() . $B['Amount'] . " to you for " . $B['YourRef'] );
            GMLog('Cash transfer  to ' . $Facts[$B['Recipient']]['Name'] . ' of ' . $B['Amount'] . ' for ' . $B['YourRef']);
          }
      } else if (Spend_Credit($B['FactionId'],$B['Amount'],$B['YourRef'],$B['Recipient'])) {
        TurnLog($B['FactionId'],"Transfered " . Credit() . $B['Amount'] . " for " . $B['YourRef'] . " to " . $Facts[$B['Recipient']]['Name']);

        if ($B['Recipient'] > 0) {
          Spend_Credit($B['Recipient'], - $B['Amount'],$B['YourRef'],$B['FactionId']);
          TurnLog($B['Recipient'],  $Facts[$B['FactionId']]['Name'] . " transfered " . Credit() . $B['Amount'] . " to you for " . $B['YourRef'] );
        }
        GMLog('Cash transfer from ' . $Facts[$B['FactionId']]['Name']. ' to ' . $Facts[$B['Recipient']]['Name'] . ' of ' . $B['Amount'] . ' for ' . $B['YourRef']);
      } else {
        TurnLog($B['FactionId'],"Failed to transfer " . Credit() . $B['Amount'] . " for " . $B['YourRef'] . " to " . $Facts[$B['Recipient']]['Name'] .
                 " you only have " . $Facts[$B['FactionId']]['Credits']);
        if ($B['Recipient'] > 0) TurnLog($B['Recipient'],  $Facts[$B['FactionId']]['Name'] . " Failed to transfer " . Credit() . $B['Amount'] . " for " . $B['YourRef'] );
        GMLog('Cash transfer from ' . $Facts[$B['FactionId']]['Name']. ' to ' . $Facts[$B['Recipient']]['Name'] .
              ' of ' . Credit() . $B['Amount'] . ' for ' . $B['YourRef'] .  ' Bounced');
      }
    } else if ($B['What'] <= 4) {
      Gain_Science($B['Recipient'],$B['What'],$B['Amount'],$B['YourRef']);
      TurnLog($B['Recipient'],"Gained " . $B['Amount'] . " Science points for " . $B['YourRef']);
    } else { // Other Currencies
      if ($B['FactionId'] > 0) {
        if (Gain_Currency($B['FactionId'],$B['What'],-$B['Amount'],$B['YourRef'])) {
          TurnLog($B['FactionId'], "Transfered " . $B['Amount'] . " of " . $Currencies[$B['What']] . " for " . $B['YourRef'] . " to " . $Facts[$B['Recipient']]['Name']);
          if ($B['Recipient'] > 0) {
            Gain_Currency($B['Recipient'], $B['What'], $B['Amount'],$B['YourRef']);
            TurnLog($B['Recipient'],  $Facts[$B['FactionId']]['Name'] . " transfered " . $B['Amount'] . " of " . $Currencies[$B['What']] . " for " . $B['YourRef'] );
          }
          GMLog('Transfer from ' . $Facts[$B['FactionId']]['Name'] . ' to ' . $Facts[$B['Recipient']]['Name'] . ' of ' . $B['Amount'] . ' ' .$Currencies[$B['What']]
                 . ' for ' . $B['YourRef']);
        } else {
          TurnLog($B['FactionId'],"Failed to transfer " . $B['Amount']  . " of " . $Currencies[$B['What']] . " for " . $B['YourRef'] .
                  " to " . $Facts[$B['Recipient']]['Name']);
          if ($B['Recipient'] > 0) TurnLog($B['Recipient'],  $Facts[$B['FactionId']]['Name'] . " Failed to transfer " . $B['Amount']  . " of " . $Currencies[$B['What']]
              . " for " . $B['YourRef'] );
          GMLog('Transfer from ' . $Facts[$B['FactionId']]['Name']. ' to ' . $Facts[$B['Recipient']]['Name'] .
                ' of ' . $B['Amount'] . " of " . $Currencies[$B['What']] . ' for ' . $B['YourRef'] .  ' Bounced');
        }
      } else {
        if ($B['Recipient'] > 0) {
          Gain_Currency($B['Recipient'], $B['What'], $B['Amount'],$B['YourRef']);
          TurnLog($B['Recipient'],  "Gained " . $B['Amount'] . " of " . $Currencies[$B['What']] . " for " . $B['YourRef'] );
        }
      }
    }

    if ($B['DecayRate']) {
      $B['Amount'] -= $B['DecayRate'];
      if ($B['Amount']<=0) $B['EndTurn'] = $GAME['Turn'];
    }
    $B['DoneTurn'] = $GAME['Turn'];
    Put_Banking($B);
  }

  GMLog("All Cash trasfered complete.<br>\n");
  return 1;
}

function FactionActions() { // Faction level actions other the science points
  global $GAME,$GAMEID;

  return 1; // code here is redundant currently

  $Facts = Get_Factions();

  $FCUs = Gen_Get_Cond("FluxCrystalUse","GameId=$GAMEID AND Turn=" . $GAME['Turn'] . " ORDER BY rand()");
  if ($FCUs) {
    foreach($FCUs as $F) {
      $L = Get_Link($F['LinkId']);
      if (!$Facts[$F['FactionId']]['Currency1']) {
        TurnLog($F['FactionId'],"You attemptted to use of Flux Crystal on Link " . $L['Name'] . " but you don't have any left");
        GMLog($Facts[$F['FactionId']]['Name'] . " attemptted to use of Flux Crystal on Link " . $L['Name'] . " but they don't have any left");
        continue;
      }
      $Facts[$F['FactionId']]['Currency1']--;
      Put_Faction($Facts[$F['FactionId']]);
      if ($L['FluxCrystals']) {
        TurnLog($F['FactionId'],"You attemptted to use a Flux Crystal on Link " . $L['Name'] . ", one has already been used this turn it had no effect");
        GMLog($Facts[$F['FactionId']]['Name'] . " attemptted to use a Flux Crystal on Link " . $L['Name'] .
          ", one has already been used this turn it had no effect");
        continue;
      }
      $L['FluxCrystals'] = 1;
      $L['ThisTurnMod']--;
      $EInst = $L['Instability'];
      if ($L['ThisTurnMod']) $EInst = max(0,$EInst+$L['ThisTurnMod']);
      Put_Link($L);
      TurnLog($F['FactionId'],"You have used a Flux Crystal on Link " . $L['Name'] . ", its now effectvely instability $EInst for the rest of this turn");
      GMLog($Facts[$F['FactionId']]['Name'] . "  have used a Flux Crystal on Link " . $L['Name'] .
        ", its now effectvely instability $EInst for the rest of this turn");
    }
  }
  return 1;
}


function ScientificBreakthroughs() {
  global $GAME,$GAMEID;
//  GMLog("Scientific Breakthroughs is currently Manual<p>");

  $TechFields = [['Engineering','EngineeringSP'],['Physics','PhysicsSP'],['Xenology','XenologySP']];
  $Breaks = Gen_Get_Cond('Breakthroughs',"Game=$GAMEID AND Turn=" . $GAME['Turn'] . " AND DoneTurn=0");

// var_dump($Breaks);

  foreach($Breaks as $Br) {
    $Fid = $Br['FactionId'];
    $Fact = Get_Faction($Fid);
    $Tid = $Br['TechId'];

    $CTech = Get_Faction_TechFT($Fid,$Tid);
    $Tech = Get_Tech($Tid);

    if ($Tech['Cat'] == 0) { // Core
      if ($CTech['Level'] < $Br['Level']) {
        $CTech['Level'] = $Br['Level'];
        Put_Faction_Tech($CTech);
        TurnLog($Br['FactionId'],'Using ' . $Br['Cost'] . $TechFields[$Br['Field']][0] . " science points " . $Tech['Name'] . " has been raised to level " . $Br['Level']);
        Gain_Science($Fid,$TechFields[$Br['Field']][0],-$Br['Cost'],"Raising " . $Tech['Name'] . " to level " . $Br['Level']);
      } else {
        GMLog("Faction: " . $Fact['Name'] . " attempted to use science points to raise " . $Tech['Name'] . " to level " . $Br['Level'] .
              " already at level " . $CTech['Level']);
        TurnLog($Br['FactionId'],"You attempted to use science points to raise " . $Tech['Name'] . " to level " . $Br['Level'] .
                " it is already at level " . $CTech['Level']);
      }
    } else if ($CTech['Level'] == 0) { // Supp
      $CTech['Level'] = 1;
      Put_Faction_Tech($CTech);
      TurnLog($Br['FactionId'],'Using ' . $Br['Cost'] . $TechFields[$Br['Field']][0] . " science points " . $Tech['Name'] . " has been reserched");
      Gain_Science($Fid,$TechFields[$Br['Field']][0],-$Br['Cost'],"Researching " . $Tech['Name'] . " (Level: " . $Br['Level'] . ")");
    } else {
      GMLog("Faction: " . $Fact['Name'] . " attempted to use science points to research " . $Tech['Name'] . " it is already known");
      TurnLog($Br['FactionId'],"You attempted to use science points to research " . $Tech['Name'] . " it is already known");
    }
    $Br['DoneTurn'] = $GAME['Turn'];
    Gen_Put('Breakthroughs',$Br);
  }

  return 1;
}


/*
$ThingInstrs = ['None','Colonise','Voluntary Warp Home','Decommision','Analyse Anomaly','Establish Embassy','Make Outpost','Make Asteroid Mine','Make Minefield',
                'Make Orbital Repair Yard','Build Space Station','Expand Space Station','Make Deep Space Sensor','Make Advanced Asteroid Mine','Build Stargate',
                'DSC Special'];
*/

function ClearPaidFor() {
  $Things = Get_Things_Cond(0,"Instruction<0");
  foreach ($Things as $T) {
    $T['Instruction'] = -$T['Instruction'];
    Put_Thing($T);
  }
  return 1;
}

function Economy() {
  global $db,$GAMEID,$LogistCost,$ARMIES;
  // TWork out economies and generate income for each faction.
  // Blockades, theft and new things affect - this needs to be done BEFORE projects complete
//  echo "The Economy is currently Manual<p>";
//  return true;
  // For each faction recalc Economic rating of Each world
  // Add Outpost and Ast mining, Embassy income
  // Work out logistic penalties
  // Do main sums and present ansers, update records

  $Facts = Get_Factions();
  $TTypes = Get_ThingTypes();

  foreach ($Facts as $F) {
    $Fid = $F['id'];

    [$EconVal,$EccTxt] = Income_Calc($Fid);

    $EccTxt .= "Total Economy is $EconVal worth " . $EconVal*10 . "\n\n";
    Spend_Credit($Fid, -$EconVal*10, "Turn Income");
    TurnLog($Fid,$EccTxt);
    GMLog("Done Economy for " . $F['Name'] . "<br>");

  }
  return 1;
}

function TraitIncomes() {
  // Incomes from Planetary Traits and Faction Traits (If not handled elsewhere
  global $GAMEID,$GAME;

  $SPs = [
    ['Academic','Abundant Wildlife','Xenology',1],
    ['Academic','Radioactive Crystals','Xenology',1],
    ['Sensors','Deep Ocean Trenches','Xenology',2],
    ['Academic','Thin Atmosphere','Physics',1],
    [2,'High Tectonic Activity','Engineering',3],
    ['Industrial','Necessity is the Mother of Invention','Engineering',1],
    ['Industrial','Cret-Chath Deposits','Currency3',4],
    ['Industrial','Deep Flux Crystal Deposits','Currency1',5],
  ];

  $DTypes = Get_DistrictTypes();
  $DTNames = NamesList($DTypes);
  $NamesDT = array_flip($DTNames);
  $Facts = Get_Factions();
  $Systems = Get_Systems();

  $Planets = Gen_Get_Cond('Planets',"GameId=$GAMEID AND (Trait1Auto!=0 OR Trait2Auto!=0 OR Trait3Auto!=0) ");
  $Moons = Gen_Get_Cond('Moons',"GameId=$GAMEID AND (Trait1Auto!=0 OR Trait2Auto!=0 OR Trait3Auto!=0 OR (Attributes&14)!=0 ) ");

//  var_dump($Planets);
//  var_dump($Moons);

  for ($List =0;$List<2;$List++) {
    if ($List) {
      $Bodies = $Planets;
      $DistF = 'Get_DistrictsP';
    } else {
      $Bodies = $Moons;
      $DistF = 'Get_DistrictsM';
    }

    if (empty($Bodies)) continue;
    foreach ($Bodies as $Bid=>$Bod) {
      if ($List) {
        $Sid = $Bod['SystemId'];
        $P = $Bod;
        $LoopLim = 3;
      } else {
        $Pid = $Bod['PlanetId'];
        $Plan = Get_Planet($Pid);
        $LoopLim = 6;
      }
      if (!$Bod['Control']) continue;
      $Fid = $Bod['Control'];
      for($ii=1;$ii<=$LoopLim;$ii++) {

//        echo "Looping $ii $DistF<p>";

        $i = $ii;
        if ($i>3) {
          $i = $ii-3;
          if (($Bod['Attributes']&(1<<$i))==0) continue;
          $P = $Plan;
        }

        foreach ($SPs as $SP) {
          if ($Bod["Trait$i"] == $SP[1]) {
//            echo "Checking $ii - $i " . $SP[1] . "<p>";
            switch ($SP[3]) {
              case 1: // Districts
                $Ds = $DistF($Bid);
                $D = ($Ds[$NamesDT[$SP[0]]]??0);
                if ($D) {
                  Gain_Science($Fid, $SP[2], $D['Number'],"Planetary trait " .$SP[1] . " in " . $Bod['Name']);
//                  $Facts[$Fid][$SP[2] . "SP"] += $D['Number'];
                  TurnLog($Fid,"Gained " . $D['Number'] . " " . $SP[2] . " points from the planetary trait " .$SP[1] . " in " . $Bod['Name']);
                  GMLog($Facts[$Fid]['Name'] . " Gained " . $D['Number'] . " " . $SP[2] . " points from the planetary trait " .$SP[1] . " in " . $Bod['Name']);
                }
                break;
              case 2:// Tech
                $T = Has_Tech($Fid,$SP[0]);
                if ($T) {
                  Gain_Science($Fid, $SP[2], $T,"Planetary trait " .$SP[1] . " in " . $Bod['Name']);

//                  $Facts[$Fid][$SP[2] . "SP"] += $T;
                  TurnLog($Fid,"Gained " . $T . " " . $SP[2] . " points from the planetary trait " .$SP[1] . " in " . $Bod['Name']);
                  GMLog($Facts[$Fid]['Name'] . " Gained " . $T . " " . $SP[2] . " points from the planetary trait " .$SP[1] . " in " . $Bod['Name']);
                }
                break;
              case 3: //Fixed
                Gain_Science($Fid, $SP[2], $SP[0],"Planetary trait " .$SP[1] . " in " . $Bod['Name']);
//                $Facts[$Fid][$SP[2] . "SP"] += $SP[0];
                TurnLog($Fid,"Gained " . $SP[0] . " " . $SP[2] . " points from the planetary trait " .$SP[1] . " in " . $Bod['Name']);
                GMLog($Facts[$Fid]['Name'] . " Gained " . $SP[0] . " " . $SP[2] . " points from the planetary trait " .$SP[1] . " in " . $Bod['Name']);
                break;
              case 4: // Cret-Chath
                $Ds = $DistF($Bid);
                $D = ($Ds[$NamesDT[$SP[0]]]??0);
                if ($D) {
                  $Div = (Has_Tech($Fid,'Advanced Mineral Extraction')?1:2);
                  Gain_Currency($Fid, Feature($SP[2],'Unknown'), ceil($D['Number']/$Div),"Planetary trait " .$SP[1] . " in " . $Bod['Name']);
//                  $Facts[$Fid][$SP[2]] += ceil($D['Number']/$Div);
                  TurnLog($Fid,"Gained " . ceil($D['Number']/$Div) . " " . Feature($SP[2],'Unknown') . " from the planetary trait " .
                    $SP[1] . " in " . $Bod['Name']);
                  GMLog($Facts[$Fid]['Name'] . " Gained " . ceil($D['Number']/$Div) . " " . Feature($SP[2],'Unknown') . " from the planetary trait " .
                    $SP[1] . " in " . $Bod['Name']);
                }
                break;

              case 5: // Deep Flux Crystal Deposits
                $Ds = $DistF($Bid);
                $D = ($Ds[$NamesDT[$SP[0]]]??0);
                if (($D >= 2) && (Has_Tech($Fid,'Advanced Mineral Extraction'))) {
                  Gain_Currency($Fid, Feature($SP[2],'Unknown'), 2,"Planetary trait " .$SP[1] . " in " . $Bod['Name']);
                  TurnLog($Fid,"Gained 2 " . Feature($SP[2],'Unknown') . " from the planetary trait " .
                    $SP[1] . " in " . $Bod['Name']);
                  GMLog($Facts[$Fid]['Name'] . " Gained 2 " . Feature($SP[2],'Unknown') . " from the planetary trait " .
                    $SP[1] . " in " . $Bod['Name']);
                }


            }
          }

        }
      }
    }
  }

/*
  foreach ($Facts as $F){
    Put_Faction($F);
  }*/

  GMLog("Done Planetary Trait Incomes<br>");

  // Science Points for Offices and Branches
  $Sci = OrgType('Science');

  $Orgs = Gen_Get_Cond("Organisations", "GameId=$GAMEID AND (OrgType=$Sci OR OrgType2=$Sci)");
  $BTypes = Get_BranchTypes();
  $BTindex = array_flip(NamesList($BTypes));

  foreach ($Orgs as $Oid=>$O) {
    $Who = $O['Whose'];
    $Pow = $O['OfficeCount'];
    if ($Pow == 0) continue;
    Gain_Science($Who,'General',$Pow*$Pow,'Offices of ' . $O['Name']);
    TurnLog($Who,"Gained " . ($Pow*$Pow) . " Science points for offices of " . $O['Name']);

    $Branches = Gen_Get_Cond('Branches', "GameId=$GAMEID AND Organisation=$Oid");
    $SPs = [0,0,0,0];
    foreach ($Branches as $B) {
      switch ($BTypes[$B['Type']]['Name']) {
        case 'Science Branch (Xenology)':
          Gain_Science($Who,'Xenology',$Pow,"From a branch");
          $SPs[3] += $Pow;
          break;
        case 'Science Branch (Physics)':
          Gain_Science($Who,'Physics',$Pow,"From a branch");
          $SPs[1] += $Pow;
          break;
        case 'Science Branch (Engineering)':
          Gain_Science($Who,'Engineering',$Pow,"From a branch");
          $SPs[2] += $Pow;
          break;
        case 'Science Space Base':
          $T = Get_Thing($B['HostId']);
          $N = Get_System($T['SystemId']);
          $Ref = $N['Ref'];

          FollowUp($Who, "A Science base of " . $O['Name'] . " (" . $Facts[$Who]['Name'] . ") power $Pow on an outpost in $Ref may get something");
          // No idea what goes here...
      }
    }
    if ($SPs[1] || $SPs[2] || $SPs[3]) {
      TurnLog($Who,"Gained " . ($SPs[1]?$SPs[1] . " Physics points, ":'') .
         ($SPs[2]?$SPs[2] . " Engineering points, ":'') . ($SPs[3]?$SPs[3] . " Xenology points, ":'') . " for branches of " . $O['Name']);
    }

    if ($O['Name'] == 'Ecoline') {
      $Wildlife = Has_Track($Who,'Alien Wildlife');
      $Xeno = [125=>5, 80=>4, 45=>3, 20=>2, 5=> 1];
      $Mult = 0;
      foreach ($Xeno as $XV=>$XP ) {
        if ($Wildlife >= $XV) {
          $Mult = $XP;
          break;
        }
      }
      if ($Mult) {
        Gain_Science($Who,'Xenology',$Pow*$Mult,"From Ecoline");
        TurnLog($Who,"Gained " . $Pow*$Mult . " Xenology points from Ecoline");
      }
    }

  }

  GMLog("Done Org/Branch based Incomes<br>");

  $Confl = Gen_Get_Cond1('SocialPrinciples',"GameId=$GAMEID AND Principle='Confluence'");
  $Benev = Gen_Get_Cond1('ResourceTypes',"GameId=$GAMEID AND Name='Benevolence'");
  if ($Confl && $Benev) {
    $CnfSP = $Confl['id'];
    $BenevR = $Benev['id'];
    $SPws = Gen_Get_Cond('SocPsWorlds', "Principle=$CnfSP");
    if ($SPws) {
      foreach($Facts as $Fid=>$F) $Facts[$Fid]['Confl'] = 0;

      foreach($SPws as $Spw) {
        $W = Get_World($Spw['World']);
        $Facts[$W['FactionId']]['Confl'] += $Spw['Value'];
      }

      foreach($Facts as $Fid=>$F) if ($F['Confl']) {
        $C = Gen_Get_Cond1('Resources', "Type=$BenevR AND Whose=$Fid");
        if ($C) {
          $C['Value'] += $F['Confl'];
          Gen_Put('Resources',$C);
        } else {
          $C = ['Type'=>$BenevR,'Whose'=>$Fid,'Value'=>$F['Confl']];
          Gen_Put('Resources',$C);
        }
        TurnLog($Fid,"Gained " . $F['Confl'] . " Benevolence from Confluence");
        $Spog = ['GameId'=>$GAME['id'],'Turn'=>$GAME['Turn'],'FactionId'=>$Fid, 'Type'=>$BenevR+20, 'Number'=>$F['Confl'], 'Note'=>"From Confluence",
          'EndVal'=>$C['Value']];
        Gen_Put('SciencePointLog',$Spog);
      }

      GMLog("Done Confluence based Incomes<br>");
    }
  }

  return 1;
}

function OwnershipChange() {
  GMLog("<h2>If worlds have changed hands after conflict, do that NOW</h2>");
  return 1;
}

function HandleCoOpProjects() {
  GMLog("Now handle any co-op projects or instructions adding progress where needed.<p> " .
        "This is needed for all activities cross faction and instructions that affect more than one thing.");

  return 1;
}

function AffectActivities() {
  global $GAMEID,$ThingInstrs;
  $Facts = Get_Factions();
  $Systems = Get_SystemsById();
  $Homes = Get_ProjectHomes();

  GMLog("<H1>Affect ongoing Projects, Instructions and Operations</h1>");
  GMLog("Pause - will stop any progress this turn.<p>" .
    "Cancel - Will cancel the activity, Projects and Operations could be recovered by Richard, Instructions are lost permenantly.<p>");

  GMLog( "<form method=Post action=TurnActions.php?ACTION=DoStage2>" . fm_hidden('Stage','Affect Activities'));
  GMLog( "<h2>Projects</h2>");
  GMLog( "<table border><tr><td>Activity<td>Who<td>Where<td>Progress<td>Pause<td>Cancel<td>Reason");

  $Projects = Get_Projects_Cond("Status=1 AND GameId=$GAMEID");
  foreach ($Projects as $P) {
    $Pid = $P['id'];
    GMLog( "<tr><td><a href=ProjEdit.php?id=$Pid>" . $P['Name'] . "</a><td>" . $Facts[$P['FactionId']]['Name'] .
      "<td>" . ($Systems[($Homes[$P['Home']]['SystemId']??0)]['Ref']??'??') . "<td>" .
      $P['Progress'] . "/" . $P['ProgNeeded'] . "<td>" . fm_checkbox('',$_REQUEST,"Pause:Proj:$Pid") .
      "<td>" . fm_checkbox('',$_REQUEST,"Cancel:Proj:$Pid") . fm_text1('',$_REQUEST,"Reason:Proj:$Pid"));
  }
  GMLog( "</table><p>\n");

  GMLog( "<h2>Operations</h2>");

  $Ops = Gen_Get_Cond('Operations',"Status=1 AND GameId=$GAMEID");
  GMLog( "<table border><tr><td>Activity<td>Who<td>Where<td>Progress<td>Pause<td>Cancel<td>Reason");
  foreach ($Ops as $OP) {
    $Oid = $OP['id'];
    GMLog( "<tr><td><a href=OperEdit.php?id=$Oid>" . $OP['Name'] . "</a><td>" . $Facts[$OP['Whose']]['Name'] .
      "<td>" . ($Systems[$OP['SystemId']]['Ref']??'??') . "<td>" .
      $OP['Progress'] . "/" . $OP['ProgNeeded'] . "<td>" . fm_checkbox('',$_REQUEST,"Pause:Oper:$Oid") .
      "<td>" . fm_checkbox('',$_REQUEST,"Cancel:Oper:$Oid") . fm_text1('',$_REQUEST,"Reason:Oper:$Oid"));
  }
  GMLog( "</table><p>\n");

  GMLog( "<h2>Instructions</h2>");
  GMLog( "Only those that can be affected are listed, scans are handled seperately later.<p>\n");


  $Things = Get_Things_Cond(0,"Instruction!=0 AND ActionsNeeded!=0 AND GameId=$GAMEID AND (BuildState=" . BS_COMPLETE . " OR BuildState=" . BS_SERVICE . ")");
  if ($Things) {
    GMLog( "<table border><tr><td>Activity<td>Who<td>Where<td>Progress<td>Pause<td>Cancel<td>Reason");
    foreach ($Things as $T) {
      $Tid = $T['id'];
      GMLog( "<tr><td><a href=ThingEdit.php?id=$Tid>" . $T['Name'] . " a " . $T['Class'] . "</a> doing - " . $ThingInstrs[abs($T['Instruction'])] .
        "<td>" . $Facts[$T['Whose']]['Name'] .
        "<td>" . ($Systems[$T['SystemId']]['Ref']??'??') . "<td>");
      switch ($ThingInstrs[abs($T['Instruction'])]) {
        case 'Analyse Anomaly': // Analyse
          $Aid = $T['ProjectId'];
          $Anom = Get_Anomaly($Aid);
          $FA = Gen_Get_Cond1('FactionAnomaly',"FactionId=" . $T['Whose'] . " AND AnomalyId=$Aid");
          GMLog( $FA['Progress']. "/" . $Anom['AnomalyLevel'] . "<td>" . fm_checkbox('',$_REQUEST,"Pause:Inst:$Tid") .
          "<td>" . fm_text1('',$_REQUEST,"Reason:Oper:$Tid")); // No Cancel
          break;

        case 'Collaborative Space Construction': // Progress is elsewhere, only cancel is meaningful
        case 'Collaborative DSC':
        case 'Collaborative Planetary Construction':
          GMLog( "<td><td>" . fm_checkbox('',$_REQUEST,"Cancel:Inst:$Tid") . fm_text1('',$_REQUEST,"Reason:Inst:$Tid"));
          break;

        default:
          GMLog( $T['Progress'] . "/" . $T['ActionsNeeded'] . "<td>" . fm_checkbox('',$_REQUEST,"Pause:Inst:$Tid") .
            "<td>" . fm_checkbox('',$_REQUEST,"Cancel:Inst:$Tid") . fm_text1('',$_REQUEST,"Reason:Inst:$Tid"));
          break;
      }
    }
    GMLog("</table><p>\n");

  }


  GMLog( "<input type=submit name=Ignore value=Checked>\n");
  dotail();

}

function AffectActivitiesActions() {
  global $GAME,$Project_Statuses,$ThingInstrs;
  $mtch = [];
// var_dump($_REQUEST);
  foreach ($_REQUEST as $R=>$V) {
    if (preg_match('/(\w*):(\w*):(\d*)/', $R, $mtch)) {
      [$junk,$action,$area,$id] = $mtch;
      switch ($action) {
        case 'Pause':
          switch ($area) {
            case 'Proj':
              // Mark as done this turn
              $P = Get_Project($id);
              $P['LastUpdate'] = $GAME['Turn'];

              $Reason = ($_REQUEST["Reason:Proj:$id"]??'Unknown reason');
              TurnLog($P['FactionId'], "Project " . $P['Name'] . " had no progress because of: $Reason");
              Put_Project($P);
              break;

            case 'Oper':
              $O = Get_Operation($id);
              $O['TurnState'] = 1;

              $Reason = ($_REQUEST["Reason:Oper:$id"]??'Unknown reason');
              TurnLog($O['Whose'], "Operation " . $O['Name'] . " had no progress because of: $Reason");
              Put_Operation($O);
              break;

            case 'Inst':
              $T = Get_Thing($id);
              $T['CurInst'] = -abs($T['CurInst']);

              $Reason = ($_REQUEST["Reason:Inst:$id"]??'Unknown reason');
              TurnLog($T['Whose'], "Instruction: " . $ThingInstrs[abs($T['Instruction'])] . " had no progress because of: $Reason");
// var_dump($T);
              Put_Thing($T);

              break;
          }
          break;

        case 'Cancel':
          switch ($area) {
            case 'Proj':
              $P = Get_Project($id);
              $P['Status'] = $Project_Statuses['Cancelled'];
              $Cost = intdiv($P['Costs'],2);
              Spend_Credit($P['FactionId'],-$Cost,"Refund from Cancelled Project");

              $Reason = ($_REQUEST["Reason:Proj:$id"]??'Unknown reason');
              TurnLog($P['FactionId'], "Project " . $P['Name'] . " has been cancelled because of: $Reason.  $Cost " . credit() . " have recovered.");
              Put_Project($P);

              break;

            case 'Oper':
              $P = Get_Operation($id);
              $P['Status'] = $Project_Statuses['Cancelled'];

              $Reason = ($_REQUEST["Reason:Proj:$id"]??'Unknown reason');
              TurnLog($P['Whose'], "Operation " . $P['Name'] . " has been cancelled because of: $Reason");
              Put_Operation($P);
              break;

            case 'Inst':
              $T = Get_Thing($id);
              $T['CurInst'] = $T['Instruction'] = 0;

              $Reason = ($_REQUEST["Reason:Inst:$id"]??'Unknown reason');
              TurnLog($T['Whose'], "Instruction: " . $ThingInstrs[abs($T['Instruction'])] . " has been cancelled because of: $Reason");
              Put_Thing($T);
              break;
          }
          break;

        default: // pick up Reason as used
          break;
      }
    }
  }
}


function FinishShakedowns() {
  // Move anything in shakedown to completed

  if (!Feature('Shakedowns')) return;
  $Things = Get_Things_Cond(0," BuildState=" . BS_SERVICE);

  foreach($Things as $T) {
    $T['BuildState'] = BS_COMPLETE;
    TurnLog($T['Whose'],$T['Name'] . " has finished it's Shakedown and is now ready for operations.",$T);
    Put_Thing($T);
  }

  GMLog("Shakdowns finished<br>");
  return 1;
}



function MilitiaArmyRecovery() {
//  GMLog("Militia Recovery is currently Manual<p>");
//  GMLog("Also Self Repair Armour<p>");

  $Things = Get_Things_Cond(0,"(CurHealth>0 OR (CurHealth=0 AND BuildState=" . BS_COMPLETE . ")) AND (CurHealth<OrigHealth OR CurShield<ShieldPoints)");
  $TTypes = Get_ThingTypes();
  $MTypes = Get_ModuleTypes();
  $MTNs = Mod_Types_From_Names($MTypes);
  $LastHost = $LastId = $Rec = 0;
  $Facts = Get_Factions();
  foreach ($Facts as $Fid=>$Fact) {
    $Facts[$Fid]['Organic'] = Has_Trait($Fid,'Organic Units');
  }

  foreach ($Things as $T) {
    if ($TTypes[$T['Type']]['Prop2'] & THING_HAS_RECOVERY) {
      if ($T['Conflict']) continue;
      // if not in conflit, recovery some
      $Conflict = 0;
      $Conf = Gen_Select("SELECT W.* FROM ProjectHomes PH, Worlds W WHERE PH.SystemId=" . $T['SystemId'] . " AND W.Home=PH.id AND W.Conflict=1");
      if ($Conf) $Conflict = $Conf[0]['Conflict'];
      if ($Conflict) continue;  // No recovery allowed

      // Work out recovery
      if ($TTypes[$T['Type']]['Name'] == 'Militia') {
        $SaveRec = 1;
        if ($LastHost != $T['Dist1'] || $LastId != $T['Dist2']) {
          $Dists = Gen_Get_Cond('Districts',"HostType=" . $T['Dist1'] . " AND HostId=" . $T['Dist2']);
          $Dcount = 0;
          foreach($Dists as $D) $Dcount += $D['Number'];
          $SaveRec = floor($Dcount/2)+1;
          $LastHost = $T['Dist1'];
          $LastId = $T['Dist2'];
        }
        $Rec = $SaveRec;
      } else {
        $Rec = intdiv($T['OrigHealth'],4);
      }

// echo "Recovery of $Rec<br>";
      $T['CurHealth'] = max($T['OrigHealth'], $T['CurHealth']+$Rec);
      Put_Thing($T);
      if ($T['Whose']) TurnLog($T['Whose'],$T['Name'] . " recovered $Rec health",$T);
      GMLog("<a href=ThingEdit.php?id=" . $T['id'] . ">" . $T['Name'] . ' a ' . $TTypes[$T['Type']]['Name'] . "</a> recovered $Rec health");

    }

    if (isset($MTNs['Self-Repairing Armour']) && (($TTypes[$T['Type']]['Properties'] & THING_HAS_SHIPMODULES))) {
      $Self = Get_ModulesType($T['id'],$MTNs['Self-Repairing Armour']);
      if (isset($Self['Number'])) {
        $Rep = $Self['Number']*$Self['Level']*2;
        $T['CurHealth'] = min($T['OrigHealth'],$T['CurHealth']+$Rep);
        Put_Thing($T);
        if ($T['Whose']) TurnLog($T['Whose'],$T['Name'] . " recovered $Rep health",$T);
        GMLog("<a href=ThingEdit.php?id=" . $T['id'] . ">" . $T['Name'] . "</a> recovered $Rep health");
      }
    }

    if (isset($MTNs['Self Repairing Robot Armour']) && (($TTypes[$T['Type']]['Properties'] & THING_HAS_ARMYMODULES))) {
      $RepMods = ['Medical Corps', 'Self Repairing Robot Armour'];
      foreach ($RepMods as $Mt) {
        $Med = Get_ModulesType($T['id'],$MTNs[$Mt]);
        if (isset($Med[0])) {
          $Rep = $Med[0]['Number']*$Med[0]['Level']*2;
          if ($Mt == 'Self Repairing Robot Armour') $Rep *=2;
          $Conflict = 0;
          $Conf = Gen_Select("SELECT W.* FROM ProjectHomes PH, Worlds W WHERE PH.SystemId=" . $T['SystemId'] . " AND W.Home=PH.id AND W.Conflict=1");
          if ($Conf) $Conflict = $Conf[0]['Conflict'];
          if (!$Conflict) $Rep*=2;
          $T['CurHealth'] = min($T['OrigHealth'],$T['CurHealth']+$Rep);
          Put_Thing($T);
          if ($T['Whose']) TurnLog($T['Whose'],$T['Name'] . " recovered $Rep health",$T);
          GMLog("<a href=ThingEdit.php?id=" . $T['id'] . ">" . $T['Name'] . "</a> recovered $Rep health");
        }
      }
    }

    if ($T['Whose'] && ($Facts[$T['Whose']]['Organic']??0)) {
      if ($T['Conflict']) continue;
      $Conflict = 0;
      $Conf = Gen_Select("SELECT W.* FROM ProjectHomes PH, Worlds W WHERE PH.SystemId=" . $T['SystemId'] . " AND W.Home=PH.id AND W.Conflict=1");
      if ($Conf) $Conflict = $Conf[0]['Conflict'];
      if (!$Conflict) {
        $Rec = intdiv($T['OrigHealth'],10);
        $T['CurHealth'] = min($T['OrigHealth'], $T['CurHealth']+$Rec);
        Put_Thing($T);
        if ($T['Whose']) TurnLog($T['Whose'],$T['Name'] . " recovered $Rec health",$T);
        GMLog("<a href=ThingEdit.php?id=" . $T['id'] . ">" . $T['Name'] . ' a ' . $TTypes[$T['Type']]['Name'] . "</a> recovered $Rec health");
      }
    }

    if ($T['CurShield'] != $T['ShieldPoints']) {
      $T['CurShield'] = $T['ShieldPoints'];

      if ($T['Whose']) TurnLog($T['Whose'],$T['Name'] . " Shields Recovered",$T);
      GMLog("<a href=ThingEdit.php?id=" . $T['id'] . ">" . $T['Name'] . "</a> Shields Recovered");

      Put_Thing($T);
    }

  }

  return 1;
}

function GenerateTurns() {
  GMLog("Generate Turns is currently Manual<p>");
  return 1;
}

function FixFudge() { // Tempcode called to fix things - left in case needed again
  return 1;
  global $db,$GAMEID,$GAME;
  // Save What can I see data
  $Factions = Get_Factions();
  foreach($Factions as $F) {
    $Fid = $F['id'];
    $CouldC = WhatCanBeSeenBy($Fid,1);
    $CB = fopen("Turns/$GAMEID/" . ($GAME['Turn']-1) . "/CouldC$Fid.html", "w");
    fwrite($CB,$CouldC);
    fclose($CB);
    GMLog("Saved What could be seen for " . $F['Name']);
  }

}

function ActuallyDeleteStuff() {
  global $GAMEID,$GAME;

  $Delayed = Gen_Get_Cond('DelayedRemoval',"GameId=$GAMEID AND Turn<=" . $GAME['Turn']);

  if ($Delayed) {
    foreach($Delayed as $D) {
      Thing_Delete($D['ThingId'],1);
      db_delete('DelayedRemoval',$D['id']);
    }

//    $_REQUEST['TurnP'] = 1; // Makes FollowUps think its part of turn processing
//    include_once("FollowUp.php"); // Second call if needed
  }
  return 1;
}

function TidyUps() {
  global $db,$GAMEID,$GAME;
  $Systems = Get_Systems();
  $TTypes = Get_ThingTypes();
  $TNames = NamesList($TTypes);
  $ThingNames = array_flip($TNames);
  $WormStab = $ThingNames['Wormhole Stabiliser'];
  $res = $db->query("UPDATE Things SET LinkId=0, LinkPay=0, LinkCost=0, Retreat=0, CurInst=ABS(Instruction) " .
    "WHERE (LinkId>=0 OR LinkId=-5 OR LinkId=-6 OR LinkId=-7) AND GameId=$GAMEID");
  $res = $db->query("UPDATE Things SET Conflict=0 WHERE Conflict>0 AND GameId=$GAMEID");
  $res = $db->query("UPDATE Operations SET TurnState=0 WHERE GameId=$GAMEID");

  // Check for lid <-1...
  $NotFin = Get_Things_Cond(0,"(LinkId<-1 AND LinkId>-5)");
  if ($NotFin) {
    GMLog( "<h2 class=Err>These things have a broken load/unload still in place get Richard to fix</h2>");
    FollowUp(0,"Things have broken load/unload get Richard to fix");
    foreach ($NotFin as $T) {
      GMLog("<a href=ThingEdit.php?id=" . $T['id'] . ">" . $T['Name'] . " </a> has a lid of " . $T['LinkId']);
    }
  }

  // Tidy 1 turn carry options
  $FFs = Gen_Get_Cond('FactionFaction',"GameId=$GAMEID AND Props>0");
  foreach ($FFs as $F) {
    if (($F['Props']       &15) == 1) $F['Props'] = ($F['Props']&0xfffffff0);
    if ((($F['Props']>>4)  &15) == 1) $F['Props'] = ($F['Props']&0xffffff0f);
    if ((($F['Props']>>8)  &15) == 1) $F['Props'] = ($F['Props']&0xfffff0ff);
    if ((($F['Props']>>12) &15) == 1) $F['Props'] = ($F['Props']&0xffff0fff);
    if ((($F['Props']>>16) &15) == 1) $F['Props'] = ($F['Props']&0xfff0ffff);
    if ((($F['Props']>>20) &15) == 1) $F['Props'] = ($F['Props']&0xff0fffff);
    if ((($F['Props']>>24) &15) == 1) $F['Props'] = ($F['Props']&0xf0ffffff);
    if ((($F['Props']>>28) &15) == 1) $F['Props'] = ($F['Props']&0x0fffffff);
    Put_FactionFaction($F);
  }
  GMLog("Single Turn Player FF data Tidied Up<p>");

  $Delayed = Gen_Get_Cond('DelayedRemoval',"GameId=$GAMEID AND Turn<" . $GAME['Turn']);
  if ($Delayed) {
    foreach($Delayed as $D) {
      Thing_Delete($D['ThingId']);
      db_delete('DelayedRemoval',$D['id']);
    }
  }

  GMLog("Delayed Destruction Completed<p>");

  // Tidy up Scans due ?

  // Tidy up continuing Instructions

  $Things = Get_Things_Cond(0,"Instruction<0");
  foreach ($Things as $T) {
    $T['Instruction'] = abs($T['Instruction']);
    Put_Thing($T);
  }
  GMLog("Instructions fields reset for continued use<p>");

  $Links = Get_LinksGame();
  foreach ($Links as $Lid=>$L) {
    // Mods this/next
    $L['ThisTurnMod'] = $L['NextTurnMod'];
    $L['NextTurnMod'] = 0;
    $L['FluxCrystals'] = 0;

    $Sid1 = $Systems[$L['System1Ref']]['id'];
    $Sid2 = $Systems[$L['System2Ref']]['id'];

    $Stabs = Get_Things_Cond(0,"Type=$WormStab AND (SystemId=$Sid1 OR SystemId=$Sid2) AND Dist1=$Lid" );
    if ($Stabs) foreach($Stabs as $S) $L['ThisTurnMod']-=$S['Level'];
    Put_Link($L);
  }
  GMLog("Links: Wormhole Stabilisers and Cret-Chath tidied<p>");

  CheckBranches();
  GMLog("Branches: Data Cross Checked<p>");

  $Ts = Get_Things_Cond(0,"Type=23 AND Dist1!=0");
  foreach($Ts as $T) {
    $T['Dist1']++; // Incrementing ages of characters
    Put_Thing($T);
  }

  $Branches = Gen_Get('Branches', "GameId=$GAMEID AND Suppressed>0");
  if ($Branches) foreach ($Branches as $B) {
    $B['Suppressed']--;
    Gen_Put('Branches',$B);
  }


  $Facts = Get_Factions();
  foreach ($Facts as $F) {
    if ($F['NPC'] == 0 ) continue;
    $F['TurnState'] = 1;
    Put_Faction($F);
  }

  GMLog("<br>NPC Factions marked as Turn Planning<p>\n");


  return 1;
}

function ClearConflictFlags() {
  global $ForceNoFaction;
  GMLog("<h2>Please clear worlds of conflict where applicable</h2>Devastation is derived from conflict normally, only tweak if something special needs it");

  $_REQUEST['CONFLICT'] = 2; // Makes WorldList think its part of turn processing - 2 for clearing flags
  $ForceNoFaction = 1;
  include_once("WorldList.php");
  return 1;
}


function RecalcProjectHomes() {
  global $GAME,$GAMEID;
  // Proj Homes, Worlds
  include_once("HomesLib.php");
  include_once("MinedLib.php");
  Recalc_Project_Homes('SKLog'); // in ThingLib - this is to add new project homes that have been created by colonisation etc.
  Recalc_Worlds();

  // Dynamic Systems

  $DSys = Gen_Get_Cond('Systems',"GameId=$GAMEID AND Flags>0");
  foreach($DSys as $N) {
    Dynamic_Update($N,1);
  }

  Recalc_Mined_locs();
  Recalc_Prisoner_Counts();

  // check for worlds and colonies that can be autoscanned at a higher level
  $Facts = Get_Factions();
  foreach ($Facts as $Fid=>$Fact) {
    $Worlds = Get_Worlds($Fid);
    $Scanlvl = Has_Tech($Fid,'Sensors');
    foreach ($Worlds as $Wid=>$W) {
      switch ($W['ThingType']) {
        case 1: // Planet
          $P = Get_Planet($W['ThingId']);
          $Sys = $P['SystemId'];
          break;
        case 2: // MOon
          $M = Get_Moon($W['ThingId']);
          $P = Get_Planet($M['PlanetId']);
          $Sys = $P['SystemId'];
          break;
        case 3: // hing
          $P = Get_Thing($W['ThingId']);
          $Sys = $P['SystemId'];
          break;
      }
      $FS = Get_FactionSystemFS($Fid,$Sys);
      if (!isset($FS['id']) || ($FS['ScanLevel'] < $Scanlvl) || ($FS['SpaceScan'] < $Scanlvl) || ($FS['PlanetScan'] < $Scanlvl)) {
        $FS['ScanLevel'] = $FS['SpaceScan'] = $FS['PlanetScan'] = $Scanlvl;
        $FS['SpaceTurn'] = $FS['PlanetTurn'] = $GAME['Turn'];
        $N = Get_System($Sys);
        Put_FactionSystem($FS);
        Record_SpaceScan($FS);
        Record_PlanetScan($FS);

        TurnLog($Fid, "<h3>You are due an improved survey report for <a href=SurveyReport.php?N=$Sys>" . System_Name($N,$Fid) .
          "</a> (just click on the system on your map)</h3>");
      }
    }
  }
  Set_System_List(); // Lists of worlds in Systems
  GMLog("<br>Worlds Recalculated and indications of improved World and colony surveys given<p>\n");

  return 1;
}

function FinishTurnProcess() {
  global $GAME,$Sand;
  // Change faction state update turn number
  // TODO Send messages to discord ??
  return 1;
}

function CheckFollowUps() {
  // Tidy up 1 turn District Deltas
  $Ds = Gen_Get_Cond("Districts","Delta!=0");
  foreach($Ds as $D) {
    $D['Delta'] = 0;
    Put_District($D);
  }

  $_REQUEST['TurnP'] = 1; // Makes FollowUps think its part of turn processing
  include_once("FollowUp.php");
  return 1;
}

function EnableFactionsAccess() {
  global $GAME,$Sand;
  $Facts = Get_Factions();
  foreach ($Facts as $F) {
    $F['TurnState'] = 1;
    Put_Faction($F);
  }

  GMLog("<br>All Factions marked as Turn Planning<p>\n");

  $GAME['Turn'] ++;
  $Sand['DateCompleted'] = $GAME['DateCompleted'] = time();
  Put_Game($GAME);

  GMLog("Turn number incremented<p>\n");

  return 1;
}

function Finish_Phase($S,$Ret = 1) {
  global $TurnActions,$Sand;
  $Sand['Progress'] |= 1<<$S;
  if ($Ret > 1) {
    $Sand['Progress'] |= 2<<$S;
  }
//  var_dump($Sand,$S,$Ret);
  Put_Turn($Sand);
}

function Do_Phase($S) {
  global $TurnActions,$Sand;
  if (is_numeric($S)) {
    $act = $TurnActions[$S][2];
    $act = preg_replace('/ /','',$act);
  } else {
    $SName = $_REQUEST['S'];
    $SName = preg_replace('/ /','',$SName);
    $SName = preg_replace('/_/','',$SName);
    for($S =0; $S <64 ; $S++) {
      $act = $TurnActions[$S][2];
      $act = preg_replace('/ /','',$act);
      if ($SName == $act) return;
    }
    //       var_dump($S);
    if ($S > 63) {
      GMLog("Stage $SName not found");
      return;
    } else {
      $Sand['Progress'] |= 1<<$S;
    }
    SKLog("Completed " . $TurnActions[$S][2]);
    $S++;
    $act = $TurnActions[$S][2];
    $act = preg_replace('/ /','',$act);
    Put_Turn($Sand);
  }

  $Pfx = $TurnActions[$S][1];
  if ($Pfx) {
    include_once("TurnActs/$Pfx" . ".php");
  }
  GMLog("Would call $act<p>");
  if (!is_callable($act)) {
    GMLog("<class Err>$act not yet written</span><p>");
    return;
  }
  SKLog("Doing " . $TurnActions[$S][2]);
  $Result = $act();
  if ($Result) {
    Finish_Phase($S,$Result);
  } else {
    GMLog("Processing cancelled<p>\n");
  }
  Put_Turn($Sand);

}

function StageNumber() {
  global $TurnActions;
  $SName = $_REQUEST['Stage'];
  $SName = preg_replace('/ /','',$SName);
  for($S =0; $S <64 ; $S++) {
    $act = $TurnActions[$S][2];
    $act = preg_replace('/ /','',$act);
    if ($SName == $act) return $S;
  }
  return -1;
}

function Do_Turn() {
  global $Sand;  // If you need to add something, replace a spare if poss, then nothing breaks
  global $TurnActions;

  $Sand = Get_TurnNumber();
// var_dump($Sand);

  // Display progress chart
  // At each stage BEGIN TRANSACTION at end COMMIT TRANSACTION ??
  dostaffhead("Turn Processing");
  echo "<H1>Turn Processing</h1>";
//var_dump($_REQUEST);
  if (isset($_REQUEST['ACTION']) && ( isset($_REQUEST['S']) || isset($_REQUEST['Stage']))) {
    $S = (isset($_REQUEST['S'])? $_REQUEST['S'] : 0);
    switch ($_REQUEST['ACTION']) {
      case 'StagesDone': // Not used now
        $S = StageNumber();
        if ($S < 0 ) {
          GMLog("Stage " . $_REQUEST['Stage'] . " not found");
          break;
        }
        Finish_Phase($S,2);
        break;

      case 'Complete':
        $S = StageNumber();
        if ($S < 0 ) {
          GMLog("Stage " . $_REQUEST['Stage'] . " not found");
          break;
        }
        Finish_Phase($S,1);
        break;

      case 'DoStage2':
        $S = StageNumber();
        if ($S < 0 ) {
          GMLog("Stage " . $_REQUEST['Stage'] . " not found");
          break;
        }
        Finish_Phase($S,1);
        Do_Phase($S+1);
        break;

      case 'Process':
        Do_Phase($S);
        break;

      case 'Skip':
        if (isset($TurnActions[$S][2] )) {
          GMLog("<b>" . $TurnActions[$S][2] . " Skipped</b>");
          SKLog("Skipped " . $TurnActions[$S][2]);
          $Sand['Progress'] |= 1<<$S;
        } else {
          echo "Off the end of the turn";
        }
        break;

      case 'Revert':
        if (isset($TurnActions[$S][2] )) {
          GMLog("<b>" . $TurnActions[$S][2] . " Reverted</b>");
          SKLog("Reverted " . $TurnActions[$S][2]);
          $Sand['Progress'] &= ~(1<<$S);
        } else {
          GMLog("Off the end of the turn");
        }
        break;

      case 'Redo' : //Revert and Do Now
        if (isset($TurnActions[$S][2] )) {
          GMLog("<b>" . $TurnActions[$S][2] . " Reverted</b>");
          SKLog("Reverted " . $TurnActions[$S][2]);
          $Sand['Progress'] &= ~(1<<$S);

          Do_Phase($S);

        } else {
          GMLog("Off the end of the turn");
        }
        break;

      case 'RevertAll':
        if (isset($TurnActions[$S][2] )) {
          SKLog("Reverted All");
          $Sand['Progress'] = 0;
        } else {
          GMLog("Off the end of the turn");
        }
        break;

    }
  }

  echo "<table border>";
  echo "<tr><th>#" . (Access('God')?"<td>Mask<td>File":"") . "<td>Automation<td>Stage<th>State<th>Commands\n";
  $Stage = 0;
  $Prog = 1;
  $NextStage = -1;

  foreach ($TurnActions as $sta) {
    if ($sta[2] != 'Spare') {
      echo "<tr><td>$Stage<td>" .  (Access('God')?(dechex($Prog) . "<td>" . $sta[1] . "<td>"):"") . $sta[3] . "<td>" . $sta[2] . "<td>";

// var_dump($Sand['Progress'],$Prog);
      if ($Sand['Progress'] & $Prog) {
        echo "Completed<td><a href=TurnActions.php?ACTION=Revert&S=$Stage>Revert</a> &nbsp &nbsp; <a href=TurnActions.php?ACTION=Redo&S=$Stage>Redo</a>";
      } else if ($NextStage < 0) {
        $NextStage = $Stage;
        echo "Ready to do<td><a href=TurnActions.php?ACTION=Process&S=$Stage>Process</a> &nbsp &nbsp; <a href=TurnActions.php?ACTION=Skip&S=$Stage>Skip</a>";
      } else {
        echo "Not yet<td><a href=TurnActions.php?ACTION=Process&S=$Stage>Do now</a> ";
      }
    }
    $Stage ++;
    $Prog <<= 1;
  }
  echo "</table>";

  echo "<center><h2><a href=TurnActions.php?ACTION=Process&S=$NextStage>Do Next Stage</a></h2></center>\n";
  if (Access('God')) echo "<center><h2><a href=TurnActions.php?ACTION=RevertAll&S=$NextStage>Revert All</a></h2></center>\n";

  if (!empty($Sand['id'])) {
    echo "<br><p><br><p><h2>Turn Detail (for bug fixing)</h2>";
    echo "<table border><form method=post action=TurnActions.php?ACTION=FIX>";
    Register_AutoUpdate('Turn',$Sand['id']);
    echo fm_hidden('id',$Sand['id']);
    echo "<tr>" . fm_number("Turn Number",$Sand,'TurnNumber');
    echo "<tr>" . fm_hex("Progress",$Sand,'Progress');
    echo "<tr>" . fm_textarea("Log",$Sand,'ActivityLog',8,5);
    if (Access('God')) echo "</tbody><tfoot><tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea>";
    echo "</table></form><p>\n";
  }

// var_dump($Sand);
  Put_Turn($Sand);  // Probably redundant
}


// Start Here
  Do_Turn();

  dotail();

