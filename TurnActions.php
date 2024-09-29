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
  include_once("vendor/erusev/parsedown/Parsedown.php");

  A_Check('GM');

  global $Stages,$Coded;

  $Stages = ['Check Turns Ready', 'Spare', 'Start Turn Process',
    'Save All Locations', 'Spare' /*'Remove Unsupported Minefields'*/, 'Spare' /*'Cash Transfers'*/, 'Spare',
             'Follow' /*'Pay For Stargates'*/, 'Spare', 'Scientific Breakthroughs',
             'Start Projects', 'Start Operations', 'Start Operations Stage 2', 'Instructions',
             'Instructions Stage 2', 'Clear Paid For', 'Spare'/*'Agents Start Missions'*/, 'Pay For Rushes',
             'Spare', 'Economy', 'Spare', 'Direct Moves',
             'Load Troops', 'Spare', 'Ship Move Check', 'Ship Movements',
             'Spare', 'Spare' /*'Agents Move Check', 'Agents Movements'*/, 'See After Move', 'Meetups',

             'Space Combat', 'Unload Troops', 'Planetary Defence', 'Orbital Bombardment',
             'Ground Combat', 'Devastation Selection', 'Devastation', 'Ownership Change',
             'Project Progress', 'Operations Progress', 'Instructions Progress','Collaborative Progress',
    'Spare', 'Spare', /*'Espionage Missions Complete', 'Counter Espionage',*/ 'Handle Co Op Projects', 'Spare' /*'Finish Shakedowns'*/,
             'Refit Projects Complete', 'Projects Complete','Operations Complete',  'Instructions Complete',
             'Check Survey Reports', 'Give Survey Reports', 'Check Spot Anomalies', 'Spot Anomalies',
             'Militia Army Recovery', 'Generate Turns', 'Tidy Ups', 'Clear Conflict Flags',
             'Save What Can Be Seen', 'Recalc Project Homes', 'Finish Turn Process',
             'Check Follow Ups', 'Enable Factions Access'];

  $Coded =  ['Coded','No','Coded',
             'Coded','Coded','Coded', 'No',
             'Coded','Coded','No','Coded',
             'Partial,M','No','No','Coded,M',
             'Coded,M','Coded', 'No','Coded',
             'No','Coded','No','Coded',
             'Coded','No','Coded,M','Coded',
             'Coded,M','Coded','Coded', 'Coded,M',

             'Help','Coded','No',
             'Coded,M','Help','Coded,M',
             'Coded', 'No', 'Coded','No','Coded',
             'Coded', 'No','No','No',
             'Coded','Coded','Coded,M','Coded,M',
             'Partial,M','Coded', 'Coded,M','No', 'Coded',
             'Coded','No','Coded','Coded',
             'Coded','Coded','Coded',
             'Coded','Coded'];

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
    GMLog( "<td style='background:" . $F['MapColour'] . ";'>"
         . (isset($F['LastActive']) && $F['LastActive']? date('d/m/y H:i:s',$F['LastActive']) :"Never") );
    GMLog( "<td <span style='background:" . $PlayerStateColours[$F['TurnState']] . "'>"  . $PlayerState[$F['TurnState']]);
    if ($F['TurnState'] != 2) {
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

  GMLog("Started Turn Processing");
  return 1;
}

function SaveAllLocations() {
  global $GAME,$GAMEID;
  // Copy location of everything
  $Things = Get_AllThings();
  foreach ($Things as $T) {
    if ($T['BuildState'] == 0) continue;
    $S = ['ThingId'=>$T['id'], 'SystemId'=>$T['SystemId'], 'Turn' => $GAME['Turn'],'BuildState'=> $T['BuildState'], 'CurHealth'=>$T['CurHealth'], 'Game'=>$GAMEID];
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

function Follow() {
  // find everything with a follow order
  // Do follows if possible, if not set again needed
  // If again needed and depth < Limit repeat
  $Factions = Get_Factions();
  $Depth = $Again = 0;
  do {
    $Again = 0;
    $Things = Get_Things_Cond(0," LinkId=" . LINK_FOLLOW);
    foreach ($Things as $T) {
      $Folid = $T['NewSystemId'];
      $Fol = Get_Thing($Folid);
      if ($Fol) {
        if ($Fol['LinkId'] >= 0) {
          $T['LinkId'] = $Fol['LinkId'];
          $T['LinkCost'] = $Fol['LinkCost'];
          $T['LinkPay'] = 1;
          $T['NewSystemId'] = $Fol['NewSystemId'];
          Put_Thing($T);
          GMLog($T['Name'] . " ( " . $Factions[$T['Whose']]['Name'] . " ) is following " . $Fol['Name'] . " ( " . $Factions[$Fol['Whose']]['Name'] . " )" );
        } else {
          $Again = 1;
        }
      } else {
          $T['LinkId'] = 0;
      }
    }
  } while ($Again && $Depth++ < 10);

  if ($Again) {
    GMLog("Follows aborted after $Depth");
  }
  GMLog("Follows evaluated.<br>\n");
  return 1;
}

function PayForStargates() {

//  echo "Called Pay4SGates<p>";
  $LOWho = GameFeature('LinkOwner',0);
  if ($LOWho == 0) return 1; // Not 4 this gaame

// echo "HERE!";
  $Facts = Get_Factions();

  $TotalPay = 0;
  $Things = Get_Things_Cond(0," LinkId>0 AND LinkPay=1 AND LinkCost>0 ORDER BY Whose");
// var_dump($Things);
  foreach($Things as $T) {
    $Cost = $T['LinkCost'];
    $Ref = $T['Name'] . " along link " . $T['LinkId'];
    if (Spend_Credit($T['Whose'],$Cost,'Moving ' . $Ref, $LOWho)) {
      TurnLog($T['Whose'],"Transfered " . Credit() . $Cost . " for $Ref to " . $Facts[$LOWho]['Name']);
      $TotalPay += $Cost;
      $T['LinkPay'] = 2;
      GMLog("Payed for $Ref");
    } else {
      TurnLog($T['Whose'],"Failed to transfer " . Credit() . $Cost . " for $Ref to " . $Facts[$LOWho]['Name']);
      GMLog("Failed to pay for $Ref",1);
      $T['LinkPay'] = 0;
    }
    Put_Thing($T);
  }

  if ($TotalPay) {
    Spend_Credit($LOWho,-$TotalPay,'Link Receipts');
    TurnLog($LOWho,"Recieved " . Credit() . $TotalPay  . " for Link use");
    GMLog("Transfered " . Credit() . $TotalPay  . " for Link use");
  }

  GMLog("Finished paying for stargates");
  return 1;
}

function ScientificBreakthroughs() {
  global $GAME,$GAMEID;
//  GMLog("Scientific Breakthroughs is currently Manual<p>");

  $TechCats = [['Engineering','EngineeringSP'],['Physics','PhysicsSP'],['Xenology','XenologySP']];
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
        TurnLog($Br['FactionId'],'Using ' . $Br['Cost'] . $TechCats[$Br['Field']][0] . " science points " . $Tech['Name'] . " has been raised to level " . $Br['Level']);
        $Fact[$TechCats[$Br['Field']][1]] = max(0, $Fact[$TechCats[$Br['Field']][1]] - $Br['Cost']);
        Put_Faction($Fact);
      } else {
        GMLog("Faction: " . $Fact['Name'] . " attempted to use science points to raise " . $Tech['Name'] . " to level " . $Br['Level'] .
              " already at level " . $CTech['Level']);
        TurnLog($Br['FactionId'],"You attempted to use science points to raise " . $Tech['Name'] . " to level " . $Br['Level'] .
                " it is already at level " . $CTech['Level']);
      }
    } else if ($CTech['Level'] == 0) { // Supp
      $CTech['Level'] = 1;
      Put_Faction_Tech($CTech);
      TurnLog($Br['FactionId'],'Using ' . $Br['Cost'] . $TechCats[$Br['Field']][0] . " science points " . $Tech['Name'] . " has been reserched");
      $Fact[$TechCats[$Br['Field']][1]] = max(0, $Fact[$TechCats[$Br['Field']][1]] - $Br['Cost']);
      Put_Faction($Fact);
    } else {
      GMLog("Faction: " . $Fact['Name'] . " attempted to use science points to research " . $Tech['Name'] . " it is already known");
      TurnLog($Br['FactionId'],"You attempted to use science points to research " . $Tech['Name'] . " it is already known");
    }
    $Br['DoneTurn'] = $GAME['Turn'];
    Gen_Put('Breakthroughs',$Br);
  }

  return 1;
}

function StartProjects() {
  global $GAME;
//  echo "Start Projects are currently Manual<p>";
// Find all projects with 0 progress due to start this turn
// Pay costs, Status = Started

  $Facts = Get_Factions();
  $ProjTypes = Get_ProjectTypes();
  $Projects = Get_Projects_Cond("Status=0 AND TurnStart=" . $GAME['Turn']);
  $TTypes = Get_ThingTypes();
//var_dump("Projects",$Projects);


  foreach ($Projects as $P) {
//  var_dump("Project",$P);
    $PT = $ProjTypes[$P['Type']];
    $Cost = $P['Costs'];
    $Fid = $P['FactionId'];
    if ($ProjTypes[$P['Type']]['Props'] & 2) { // Has a thing
      $Where = Where_Is_Home($P['Home']);

      $Tid = $P['ThingId'];
      if ($Tid) {
        $T = Get_Thing($Tid);
        if (!$T['DesignValid']) {
          $P['Status'] = 5; // Not Started
          TurnLog($P['FactionId'],'Not starting as design invalid: ' . $P['Name']);
          GMLog($Facts[$P['FactionId']]['Name'] . ' Not starting as design invalid: ' . $P['Name'],1);
          Put_Project($P);
          continue;
        }

        if (($ProjTypes[$P['Type']]['Props'] & 256) ==0) { // Has a thing
          if ($T['BuildState'] > 0 ) {
            $T = Thing_Duplicate($Tid);
            $Tid = $T['id'];
            $P['ThingId'] = $Tid;
            Put_Project($P);
          }
        }

        if (($T['SystemId'] != 0 && $T['SystemId'] != $Where[0])) {
//var_dump($Where,$T);
          $P['Status'] = 5; // Not Started
          TurnLog($P['FactionId'],'Not starting as not in same system: ' . $P['Name']);
          GMLog($Facts[$P['FactionId']]['Name'] . ' Not starting as not in same system: ' . $P['Name'],1);
          Put_Project($P);
          continue;
        }if ($ProjTypes[$P['Type']]['Props'] & 16) { // Tight Location check
          if (($T['WithinSysLoc'] == $Where[1] || $T['WithinSysLoc'] == $Where[1]-100)) {
            // OK
          } else {
            $P['Status'] = 5; // Not Started
            TurnLog($P['FactionId'],'Not starting as not at the same planet: ' . $P['Name']);
            GMLog($Facts[$P['FactionId']]['Name'] . ' Not starting as not at the same planet: ' . $P['Name'],1);
            Put_Project($P);
            continue;
          }
        }
        if (($ProjTypes[$P['Type']]['Props'] & 64) == 0) { // No Level Check
          if ($T['Level'] != $P['Level']) {
            if ($T['Level'] > $P['Level']) {
              $P['Status'] = 5; // Not Started

              TurnLog($P['FactionId'],'Not starting as project level less than Thing Level: ' . $P['Name']);
              GMLog($Facts[$P['FactionId']]['Name'] . ' Not starting as project level less than Thing Level: ' . $P['Name'],1);
              Put_Project($P);
              continue;
            }
          }
          // Warning?
        }
      } // TODO 2nd thing for repair
      if ($ProjTypes[$P['Type']]['Props'] & 4) { // Has can have a 2nd thing
        $Tid2 = $P['ThingId2'];
        if ($Tid2) {
          $T2 = Get_Thing($Tid2);
          if ($T2['SystemId'] != 0 && $T2['SystemId'] != $Where[0]) {
            $P['Status'] = 5; // Not Started
            TurnLog($P['FactionId'],'Not starting as not in same system: ' . $P['Name']);
            GMLog($Facts[$P['FactionId']]['Name'] . ' Not starting as not in same system: ' . $P['Name'],1);
            Put_Project($P);
            continue;
          }
          if ($ProjTypes[$P['Type']]['Props'] & 16) { // Tight Location check
            if (($T['WithinSysLoc'] == $Where[1] || $T['WithinSysLoc'] == $Where[1]-100)) {
              // OK
            } else {
              $P['Status'] = 5; // Not Started
              TurnLog($P['FactionId'],'Not starting as not at the same planet: ' . $P['Name']);
              GMLog($Facts[$P['FactionId']]['Name'] . ' Not starting as not at the same planet: ' . $P['Name'],1);
              Put_Project($P);
              continue;
            }
          }
        }
      }
    }
    if ($Cost == 0 || Spend_Credit($P['FactionId'],$Cost,'Starting: ' . $P['Name'])) {
      $P['Status'] = 1; // Started
      TurnLog($P['FactionId'],'Starting ' . $P['Name'] . " Cost: " . Credit() . " $Cost");
      GMLog($Facts[$P['FactionId']]['Name'] . ' Starting ' . $P['Name'] . " Cost: " . Credit() . " $Cost");
      if (($ProjTypes[$P['Type']]['Props'] & 2) && (($ProjTypes[$P['Type']]['Props'] & 20) ==0 )) { // Has ONE thing - 2nd test elimiates repair and construction
        if ($Tid) {
          if ($T['BuildState'] !=0) {
            $T = Thing_Duplicate($Tid);
            $Tid = $T['id'];
            $P['ThingId'] = $Tid;
            Put_Project($P);
          }
          $T['BuildState'] = 1; // Building
          $T['SystemId'] = $Where[0];
          $T['WithinSysLoc'] = $Where[1];
          $T['CurHealth'] = $T['OrigHealth'];
        } else {
          $T = ['Whose'=>$Fid, 'Type'=>$P['ThingType'], 'BuildState'=>1, 'SystemId' => $Where[0],  'WithinSysLoc' => $Where[1]];
        }
        $T['ProjectId'] = $P['id'];
        Calc_Scanners($T);
//        Put_Thing($T);

        // Level of modules
        RefitRepair($T,1); //Note this saves it and the CURRENT levels of techs
        if ($TTypes[$T['Type']]['Properties'] & THING_HAS_MODULES ) {
          $Mods = Get_Modules($Tid);
          foreach($Mods as $M) {
            if ($M['Number'] == 0) {
              db_delete('Modules',$M['id']);
            }
          }
        }
      }
    } else {
      $P['Status'] = 5; // Not Started
      TurnLog($P['FactionId'],'Not starting as not enough Credits: ' . $P['Name']);
      GMLog($Facts[$P['FactionId']]['Name'] . ' Not starting as not enough Credits: ' . $P['Name'],1);
      Put_Project($P);
      continue;
    }
    // Is there a project already running there?  If so put it on hold
    $home = $P['Home'];
    $OPs = Get_Projects_Cond(" Home=$home AND Status=1 ");
    foreach ($OPs as $OP) {
      if ($OP['id'] == $P['id']) continue;
      if ($ProjTypes[$OP['Type']]['Category'] == $ProjTypes[$P['Type']]['Category']) { // Put old project on hold
        $OP['Status'] = 5;  //5 = Cancelled , 4= On Hold
        Put_Project($OP);
        $OTid = $OP['ThingId'];
        if ($OTid) {
          $OT = Get_Thing($OTid);
          if ($OT['BuildState'] == 1) { // Building
            $OT['BuildState'] = 5; // Abandoned
            Put_Thing($OT);
          }
        }
        TurnLog($P['FactionId'],'Project ' . $OP['Name'] . " has been put on hold, having made " . $OP['Progress'] . "/" . $OP['ProgNeeded'] . "progress");
//        GMLog($Facts[$P['FactionId']]['Name'] . ' Not starting as not enough Credits: ' . $P['Name']);
      }
    }
    Put_Project($P);
  }
  return 1;
}

function StartOperations() {
  global $GAME,$GAMEID;

  $Facts = Get_Factions();
  $OpTypes = Get_OpTypes();
  $Operations = Gen_Get_Cond('Operations', "GameId=$GAMEID AND Status=0 AND TurnStart=" . $GAME['Turn']);
  $OrgTypes = Get_OrgTypes();
  $NeedColStage2 = 0;
  $TTYpes = Get_ThingTypes();
  $TTNames = NamesList($TTYpes);
  $Orgs = Gen_Get_Cond('Organisations',"GameId=$GAMEID");

  foreach ($Operations as $Oid=>$O) {
    $Fid = $O['Whose'];
    $Otp = $OpTypes[$O['Type']]['Props'];
    $Wh = $O['SystemId'];
    $Sys = Get_System($Wh);
    $TWho = $Sys['Control'];
    $OrgId = $O['OrgId'];

    if ($Otp & OPER_OUTPOST) {
      $OutPs = Get_Things_Cond($Fid,"Type=" . $TTNames['Outpost'] . " AND SystemId=$Wh AND BuildState=3");
      if ($OutPs) {
        if (count($OutPs >1)) {
          GMLog("There are multiple Outposts in " . $Sys['Ref'] . " - Tell Richard");
          exit;
        }
        if (($Otp & OPER_CREATE_OUTPOST)) {
          $Tid = $OutPs[0]['id'];
          $EBs = Gen_Get_Cond('Branches', " HostType=3 AND HostId=$Tid");

          $MaxB = HasTech($OutPs[0]['Whose'],'Offworld Construction');
          foreach ($EBs as $B) if ($B['Props'] & BRANCH_NOSPACE) $MaxB--;

          if ($MaxB >= $EBs) {
            $O['Status'] = 5; // Not Started
            TurnLog($Fid,'Not Starting ' . $O['Name'] . " as the Outpost is full");
            GMLog('Not Starting ' . $O['Name'] . " as the <a href=ThingEdit.php?id=$Tid>Outpost</a> is full");
            Put_Operation($O);
            break;
          }
        }

        if ($Otp & OPER_BRANCH) {
          $AllReady = Gen_Get_Cond('Branches'," HostType=3 AND HostId=$Tid AND OrgId=$OrgId" );
          if ($AllReady) {
            $O['Status'] = 5; // Not Started
            TurnLog($Fid,'Not Starting ' . $O['Name'] . " as there is already a branch there");
            Put_Operation($O);
            break;
          }
        }
      } else if (!($Otp & OPER_CREATE_OUTPOST)) { // No out post and can't create
        $O['Status'] = 5; // Not Started
        TurnLog($Fid,'Not Starting ' . $O['Name'] . " There is not currently an Outpost there, this operation can't create one");
        Put_Operation($O);
        break;
      }
    } else if ($Otp & OPER_BRANCH) {
      $Plan = HabPlanetFromSystem($Wh);
      if ($Plan) {
        $AllReady = Gen_Get_Cond('Branches'," HostType=1 AND HostId=$Plan AND OrgId=$OrgId" );
        if ($AllReady) {
          $P = Get_Planet($Plan);
          $O['Status'] = 5; // Not Started
          TurnLog($Fid,'Not Starting ' . $O['Name'] . " There is already a branch of " . $Orgs[$OrgId]['Name'] . " on " . $P['Name'] . " in " .
                  System_Name($Sys,$Fid) );
          Put_Operation($O);
          break;
        }
      } else {
        $O['Status'] = 5; // Not Started
        TurnLog($Fid,"There is no planet in " . System_Name($Sys,$Fid) . " that can support a Branch" );
        Put_Operation($O);
        break;
      }
    }

    if (($Otp & OPER_BRANCH) && !($Otp & OPER_HIDDEN ) ){
      if ($NeedColStage2 == 0) {
        GMLog("<form method=post action=TurnActions.php?ACTION=Process&S=Instructions>");
        $NeedColStage2 = 1;
      }
      GMLog($Facts[$Fid]['Name'] . " is seting up a branch of  " . $Orgs[$O['OrgId']]['Name'] .
           " (" . $OrgTypes[$OrgId]['Name'] . " ) it is controlled by " . ($Facts[$Sys['Control']]['Name']??'Nobody') .
           " - Allow? " . fm_YesNo("Org$Oid",1, "Reason to reject") . "\n<br>");
    }

    $Level = 0;
    if ($Otp & OPER_TECH ) {

      $Tech = Get_Tech($O['Para1']);
      $Got = Has_Tech($TWho,$O['Para1']);
      if ($Got && $Got>= $O['Para2']) {
        // Target already has tech
        $O['Status'] = 5; // Finished
        TurnLog($Fid,'Not Starting sharing ' . $Tech['Name'] . " as it is already known by " . $Facts[$TWho]['Name']);
        GMLog($Facts[$Fid]['Name'] . ' Not Starting sharing ' . $$Tech['Name'] . " as it is already known by " . $Facts[$TWho]['Name']);
        Put_Operation($O);
      }
      $Level = $O['Para2'];
    }

    if ($Otp & OPER_SOCPTARGET) {
      $SocP = Get_SocialP($O['Para1']);
      $Level = $SocP['Value'];
    }


    $Mod = ($Otp & OPER_LEVEL);
    if ($Mod >4) {
      if ($Mod &4) $Mod = $Level;
      if ($Mod &8) $Mod = $Level*2;
    }

    $BaseLevel = Op_Level($OrgId,$Wh) + $Mod;

    if ($BaseLevel != $O['Level']) {
      $ProgNeed = Proj_Costs($BaseLevel)[0];

      TurnLog($Fid,'WARNING operation ' . $O['Name'] . " with the " . $Orgs[$O['OrgId']]['Name'] . " is actually level $BaseLevel not " .
        $O['Level'] . " and now needs " . $O['ProgNeeded'] . " progress.");

    }
    $O['Status'] = 1;// Started
    TurnLog($Fid,"Operation " . $O['Name'] . " has started for organisation " . $Orgs[$O['OrgId']]['Name']);
    Put_Operation($O);
  }
  if ($NeedColStage2) {
    echo "<input type=submit name=Ignore value=Checked>\n";

    dotail();
  }
  return 2;
}

function StartOperationsStage2() {  // Making branches is checked
  global $GAME,$GAMEID;

  $Facts = Get_Factions();
  $OpTypes = Get_OpTypes();
  $Operations = Gen_Get_Cond('Operations', "GameId=$GAMEID AND Status=0 AND TurnStart=" . $GAME['Turn']);
  $OrgTypes = Get_OrgTypes();
  $NeedColStage2 = 0;
  $TTYpes = Get_ThingTypes();
  $TTNames = NamesList($TTYpes);
  $Orgs = Gen_Get_Cond('Organisations',"GameId=$GAMEID");

  foreach ($Operations as $Oid=>$O) {

    if (!isset($_REQUEST["Org$Oid"])) continue;
    $Ans = $_REQUEST["Org$Oid"];

    $Fid = $O['Whose'];
    $Otp = $OpTypes[$O['Type']]['Props'];
    $Wh = $O['SystemId'];
    $Sys = Get_System($Wh);
    $TWho = $Sys['Control'];

    if ($Ans == "on") {
      $O['State'] = 1;
      TurnLog($Fid,"Operation " . $O['Name'] . " has started for organisation " . $Orgs[$O['OrgId']]['Name']);
    } else {
      TurnLog($Fid, "Operation " . $O['Name'] . "was not started because " . $_REQUEST["ReasonOrg$Oid"]??"Unknown");
      $O['State'] = 5;
    }
    Put_Operation($O);
  }
  GMLog("<br>All Operations started<p>");
  return 1;
}


/*
$ThingInstrs = ['None','Colonise','Voluntary Warp Home','Decommision','Analyse Anomaly','Establish Embassy','Make Outpost','Make Asteroid Mine','Make Minefield',
                'Make Orbital Repair Yard','Build Space Station','Expand Space Station','Make Deep Space Sensor','Make Advanced Asteroid Mine','Build Stargate',
                'DSC Special'];
*/

function Instructions() { // And other Instructions
  global $ThingInstrs,$GAME;
  global $Currencies,$ValidMines;
  global $FACTION;

  $Things = Get_Things_Cond(0,"Instruction>0");
  $NeedColStage2 = 0;
  $Facts = Get_Factions();
  $Systems = Get_SystemRefs();
  $TTNames = Thing_Types_From_Names();

  $PTs = Get_ProjectTypes();
  $TTypes = Get_ThingTypes();
  $AAs = [];

  foreach ($Things as $T) {
    if ( $T['BuildState'] < 2 || $T['BuildState'] > 3) {
      $T['Instruction'] = $T['Progress'] = 0;
      Put_Thing($T);
      continue;
    }
    $N = Get_System($T['SystemId']);
    $Tid = $T['id'];
    // Mark new Instructions
    if (abs($T['Instruction']) != $T['CurInst']) {
      $T['CurInst'] = $T['Instruction'];
      $T['Progress'] = 0;
    }

    if ($T['Progress']>0 || $T['Instruction']<0) continue;

    switch ($ThingInstrs[$T['Instruction']]) {

    case 'Colonise': // Colonise
    case 'Build Planetary Mine':
      $P = Get_Planet($T['Spare1']);
      if ($N['Control'] > 0 && $N['Control'] != $T['Whose']) {  // Colonising system under control of others
        if ($NeedColStage2 == 0) {
          GMLog("<form method=post action=TurnActions.php?ACTION=Process&S=Instructions>");
          $NeedColStage2 = 1;
        }
        GMLog($Facts[$T['Whose']]['Name'] . " colonising " . $N['Ref'] . " it is controlled by " . $Facts[$N['Control']]['Name'] .
               " - Allow? " . fm_YesNo("Col$Tid",1, "Reason to reject") . "\n<br>");
        break;
      }

      if (($ThingInstrs[$T['Instruction']]) == 'Colonise') {
        TurnLog($T['Whose'],"The " . $T['Name'] . " is colonising " . $P['Name'] . " in " . $N['Ref'] ,$T);
        GMlog($Facts[$T['Whose']]['Name'] . " is starting to colonise " . $P['Name'] . " in " . $N['Ref']);
      } else {
        TurnLog($T['Whose'],"The " . $T['Name'] . " is seting up planetary mining " . $P['Name'] . " in " . $N['Ref'] ,$T);
        GMlog($Facts[$T['Whose']]['Name'] . " is starting to planetary mine " . $P['Name'] . " in " . $N['Ref']);
      }
      break;

    case 'Voluntary Warp Home': // Warp out
      if ($T['Dist1']) {
        $Gate = Get_Thing($T['Dist1']);
        if (isset($Gate['Type']) && $Gate['Type'] == 15) {
          $T['SystemId'] = $Gate['SystemId'];
          $T['WithinSysLoc'] = $Gate['WithinSysLoc'];
          $T['CurHealth'] = $T['LinkId'] = 0;
          TurnLog($T['Whose']," The " . $T['Name'] . " has warped back.  It now needs repair before it can be used again",$T);
          GMlog("The " . $Facts[$T['Whose']]['Name'] . " - " . $T['Name'] . " has warped back to: " . $Systems[$T['SystemId']] );
          break;
        }
      }
      $Gates = Get_Things_Cond($T['Whose'],' Type=15'); // Warp Gates
      if ($Gates) {
        if (isset($Gates[1])) { // Multiple Gates
          $GLocs = [];
           $FirstG = 0;
           foreach ($Gates as $G) {
             $N = Get_System($G['SystemId']);
             $GLocs[$G['id']] = $N['Ref'];
             if ($FirstG == 0) $FirstG = $G['id'];
           }
           if ($NeedColStage2 == 0) {
             GMLog("<form method=post action=TurnActions.php?ACTION=Process&S=Instructions>");
             $NeedColStage2 = 1;
           }
           $_REQUEST['G'] = $FirstG;
           GMLog("<p><a href=ThingEdit.php?id=$Tid>" . $T['Name'] . "</a> is warping out - Please Choose which gate:");
           GMLog(fm_select($GLocs,$_REQUEST,'G',0,'',"G" . $T['id']));
           GMLog("<p>");
           db_delete_cond('ScansDue',"ThingId=$Tid AND Turn=" . $GAME['Turn']);
           break;
         } else {
           $T['SystemId'] = $Gates[0]['SystemId'];
           $T['WithinSysLoc'] = $Gates[0]['WithinSysLoc'];
           $T['CurHealth'] = $T['LinkId'] = 0;
           TurnLog($T['Whose']," The " . $T['Name'] . " has warped back.  It now needs repair before it can be used again",$T);
           break;
         }
       } else {
         TurnLog($T['Whose']," The " . $T['Name'] . " tried to warp out, but you have no Warp Gates...",$T);
         GMLog(" The " . $T['Name'] . " tried to warp out, but there are no warp gates",1);
         $T['Instruction'] = 0;
       }
       break;

    case 'Decommision': // Dissasemble
      $Lvl = $T['Level'];
      $T['BuildState'] = -1;
      $T['SystemId'] = 0;
//      $T['History'] .= "Decommissioned";
      $T['Instruction'] = 0;
      $cash = 10*$Lvl*Has_Tech($T['Whose'],'Ship Construction');
      TurnLog($T['Whose'], "The " . $T['Name'] . " has been decommisioned gaining you " . Credit() . $cash, $T);
      Spend_Credit($T['Whose'],-$cash,"Decommisioning " . $T['Name']);
      GMLog($Facts[$T['Whose']]['Name'] . " - " . $T['Name'] . " has been decommisioned");

      $Have = Get_Things_Cond(0," (LinkId<0 AND SystemId=$Tid) ");
      if ($Have) {
        foreach ($Have as $H) {
          $H['SystemId'] = $T['SystemId'];
          $H['WithinSysLoc'] = 0;
          TurnLog($T['Whose'],$H['Name'] . " has been offloaded on to " . $P['Name'] . " in " . $N['Ref'],$H);

          if ($H['Whose'] != $T['Whose']) TurnLog($H['Whose'],$H['Name'] . " has been offloaded on to " . $P['Name'] . " in " . $N['Ref'],$H);
          Put_Thing($H);
        }
      }

      db_delete('Things',$Tid);
      continue 2;

    case 'Disband': // Dissasemble
      $Lvl = $T['Level'];
      $T['BuildState'] = -1;
      $T['SystemId'] = 0;
//      $T['History'] .= "Disbanded";
      $T['Instruction'] = 0;
      $cash = 10*$Lvl*Has_Tech($T['Whose'],Feature('MilTech'));
      TurnLog($T['Whose'], "The " . $T['Name'] . " has been disbanded gaining you " . Credit() . $cash, $T);
      Spend_Credit($T['Whose'],-$cash,"Disbanded " . $T['Name']);
      GMLog($Facts[$T['Whose']]['Name'] . " - " . $T['Name'] . " has been disbanded");

      $Have = Get_Things_Cond(0," (LinkId<0 AND SystemId=$Tid) ");
      if ($Have) {
        foreach ($Have as $H) {
          $H['SystemId'] = $T['SystemId'];
          $H['WithinSysLoc'] = 0;
          TurnLog($T['Whose'],$H['Name'] . " has been offloaded on to " . $P['Name'] . " in " . $N['Ref'],$H);

          if ($H['Whose'] != $T['Whose']) TurnLog($H['Whose'],$H['Name'] . " has been offloaded on to " . $P['Name'] . " in " . $N['Ref'],$H);
          Put_Thing($H);
        }
      }

      db_delete('Things',$Tid);
      continue 2;

    case 'Retire': // Retire Agent
      $Fid = $T['Whose'];
      $IntDist = 0;
      $Loc = $T['SystemId'];
      $Homes = Gen_Get_Cond('ProjectHomes', "SystemId=$Loc AND Whose=$Fid");
      foreach ($Homes as $H) {
        $Ds = Get_DistrictsH($H['id']);
        if (isset($Ds[4])) {
          $IntDist =1;
          break ; // FOund a Inteligence District
        }
      }

      $Lvl = $T['Level'];
      $T['BuildState'] = -1;
      $T['SystemId'] = 0;
//      $T['History'] .= "Retired";
      $T['Instruction'] = 0;
      if ($IntDist) {
        $cash = 10*$Lvl*Has_Tech($T['Whose'],'Intelligence Operations');
        TurnLog($Fid, $T['Name'] . " has retired gaining you " . Credit() . $cash, $T);
        Spend_Credit($Fid,-$cash,"Retired " . $T['Name']);
      } else {
        TurnLog($Fid, $T['Name'] . " has retired", $T);
      }
      GMLog($Facts[$Fid]['Name'] . " - " . $T['Name'] . " has been Retired");

      $Have = Get_Things_Cond(0," (LinkId<0 AND SystemId=$Tid) ");
      if ($Have) {
        foreach ($Have as $H) {
          $H['SystemId'] = $T['SystemId'];
          $H['WithinSysLoc'] = 0;
          TurnLog($T['Whose'],$H['Name'] . " has been offloaded on to " . $P['Name'] . " in " . $N['Ref'],$H);

          if ($H['Whose'] != $T['Whose']) TurnLog($H['Whose'],$H['Name'] . " has been offloaded on to " . $P['Name'] . " in " . $N['Ref'],$H);
          Put_Thing($H);
        }
      }

      db_delete('Things',$Tid);
      continue 2;


    case 'Analyse Anomaly': // Anomaly
      $Aid = $T['ProjectId'];
      $Fid = $T['Whose'];
      if ($Aid) {
        $A = Get_Anomaly($Aid);
        $FA = Gen_Get_Cond1('FactionAnomaly',"AnomalyId=$Aid AND FactionId=$Fid");
        if (!$FA || ($FA['State'] == 0) ) {
          $Sys = Get_System($T['SystemId']);
          TurnLog($T['Whose'], $T['Name'] . " is supposed to be analysing an anomaly - but you don't know of one here",$T);
          GMLog($Facts[$T['Whose']]['Name'] . " - " . $T['Name'] . " is supposed to be analysing an anomaly - but you don't know of one at " . $Sys['Ref']);
          $T['Instruction'] = $T['ProjectId'] = 0;
          Put_Thing($T);
          break;
        } else if ( ($FA['State'] == 1) && $A['OtherReq']) {
           if ($NeedColStage2 == 0) {
             echo "<form method=post action=TurnActions.php?ACTION=Process&S=Instructions>";
             $NeedColStage2 = 1;
           }
           GMLog("<p><a href=ThingEdit.php?id=$Tid>" . $T['Name'] . "</a> is starting to analyse anomaly <a href=AnomalyEdit.php?id=$Aid>" . $A['Name'] .
                 "</a> which has other requirements. <b>" . $A['OtherReq'] . "</b>.  Are they met? " . fm_YesNo("AA$Tid" ,1,$Rtxt="Why" ));
           GMLog("<p>");
           break;
        } else if ($FA['State'] == 3) {
          TurnLog($T['Whose'], $T['Name'] . " is trying to analyse a completed Anomaly: " , $T['Name']);
          $T['Instruction'] = $T['ProjectId'] = 0;
          Put_Thing($T);
          break;
        } else { // No action needed

        }
      } else {
        $Anoms = Gen_Get_Cond('Anomalies',"SystemId=" . $T['SystemId'] . " ORDER BY AnomalyLevel");
        if ($Anoms) {
          foreach($Anoms as $A) {
            $Aid = $A['id'];
            $FA = Gen_Get_Cond1('FactionAnomaly',"AnomalyId=$Aid AND FactionId=$Fid");
            if (empty($FA['id'])) continue;

            if ($FA['State'] == 0 || $FA['State'] == 3) continue;
            if ($FA['Progress'] > 0 || $FA['State'] == 2 || ($FA['State'] == 1 && empty($A['OtherReq']))) {
              $T['ProjectId'] = $Aid;
              Put_Thing($T);
              break 2;
            } else if ($FA['State'] == 1 && !empty($A['OtherReq'])) {
              if ($NeedColStage2 == 0) {
                echo "<form method=post action=TurnActions.php?ACTION=Process&S=Instructions>";
                $NeedColStage2 = 1;
              }
              $T['ProjectId'] = $Aid;
              Put_Thing($T);

              GMLog("<p><a href=ThingEdit.php?id=$Tid>" . $T['Name'] . "</a> is starting to analyse anomaly <a href=AnomalyEdit.php?id=$Aid>" . $A['Name'] .
                    "</a> which has other requirements. <b>" . $A['OtherReq'] . "</b>.  Are they met? " . fm_YesNo("AA$Tid" ,1,$Rtxt="Why" ));
              GMLog("<p>");
              break 2;
            }
          }
        }
        TurnLog($T['Name'] . " is supposed to be analysing an anomaly - but there isn't a known one");
      }
      break;

    case 'Establish Embassy':
      if ($NeedColStage2 == 0) {
        echo "<form method=post action=TurnActions.php?ACTION=Process&S=Instructions>";
        $NeedColStage2 = 1;
      }
      GMLog($Facts[$T['Whose']]['Name'] . " setting up an Embassy in " . $N['Ref'] . " - Allow? " . fm_YesNo("Emb$Tid",1, "Reason to reject") . "\n<br>");
      break;

    case 'Make Outpost':
      $T['ActionsNeeded'] = 1;
      if (!Spend_Credit($T['Whose'],$T['InstCost'],"Make Outpost in " . $N['Ref']) ) {
        $T['Progress'] = -1; // Stalled
        TurnLog($T['Whose'],"Could not afford to start an outpost in " .$N['Ref'],$T);
      }
      break;

    case 'Make Asteroid Mine':
      if (!Spend_Credit($T['Whose'],$T['InstCost'],"Make Asteroid Mine in " . $N['Ref']) ) {
        $T['Progress'] = -1; // Stalled
        TurnLog($T['Whose'],"Could not afford to start an Asteroid Mine in " .$N['Ref'],$T);
      }
      $T['Instruction'] = -$T['Instruction'];
      break;

    case 'Make Orbital Repair Yard':
      if (!Spend_Credit($T['Whose'],$T['InstCost'],"Make Orbital Repair Yard in " . $N['Ref']) ) {
        $T['Progress'] = -1; // Stalled
        TurnLog($T['Whose'],"Could not afford to start an Orbital Repair Yard in " .$N['Ref'],$T);
      }
      $T['Instruction'] = -$T['Instruction'];

      // Is there a world there controlled by the faction?
      $phs = Gen_Get_Cond('ProjectHomes',"SystemId=" . $N['id']);
      foreach ($phs as $ph) {
        if ($ph['Whose'] == $T['Whose']) break 2; // Yes fine
      }
      // or a planetary mine
      $PMines = Get_Things_Cond(0,"Type=" . $TTNames['Planet Mine'] . " AND SystemId=" . $N['id'] . " AND BuildState=3");
      if (isset($PMines[0]) && $PMines[0]['Whose'] == $T['Whose']) break; // Yes fine
      // By others?
      if (empty($phs) && empty($PMines)) {
        TurnLog($T['Whose'],"Attempting to build an orbital repair yard in " . $N['Ref'] . " but there is nothing there to build it round. ",$T);
        $T['Progress'] = -1; // Stalled
        break;
      }
      if ($NeedColStage2 == 0) {
        GMLog("<form method=post action=TurnActions.php?ACTION=Process&S=Instructions>");
        $NeedColStage2 = 1;
      }

      $List = [];
      foreach ($phs as $ph) {
        switch ($ph['ThingType']) {
          case 1: // Planet
            $Plan = Get_Planet($ph['ThingId']);
            $Control = ($Plan['Control'] != 0? $Plan['Control'] : $N['Control']);
            if ($Control) {
              $List[]= $Plan['Name']  . " a planet controlled by " . $Facts[$Control]['Name'];
            } else {
              $List[]= $Plan['Name']  . " an uncontrolled planet";
            }
            break;
          case 2: // Moon
            $Moon = Get_Moon($ph['ThingId']);
            $Plan = Get_Planet($Moon['PlanetId']);
            $Cont = ($Moon['Control'] != 0 ? $Moon['Control'] : ($Plan['Control'] != 0 ? $Plan['Control'] : $N['Control']));
            if ($Control) {
              $List[]= $Moon['Name']  . " a moon of " . $Plan['Name'] . " controlled by " . $Facts[$Control]['Name'];
            } else {
              $List[]= $Moon['Name']  . " an uncontrolled moon";
            }
            break;
          case 3: // Thing???
            break;  // Not permitted I think
        }
      }
      GMLog($Facts[$T['Whose']]['Name'] . " making an orbital repair yard in " . $N['Ref'] . " there is " . implode($List) .
               " - Allow? " . fm_YesNo("SpaceStn$Tid",1, "Reason to reject") . "\n<br>");
      break;

    case 'Make Advanced Minefield':
    case 'Make Minefield':
      $Who = $T['Whose'];
      $Loc = Within_Sys_Locs($N);
      $WSL = $T['WithinSysLoc'];
      if ($T['NewSystemId'] == 0 && $T['NewLocation'] != 0) $WSL = $T['NewLocation'];
      $LocT = intdiv($WSL,100);
      if (!Spend_Credit($Who,$T['InstCost'],"Make Minefield in " . $N['Ref']) ) {
        $T['Progress'] = -1; // Stalled
        TurnLog($Who,"Could not afford to start a Minefield in " .$N['Ref'],$T);
      }

      if ($ValidMines[$LocT] == 0 ) {
        $LocText = $Loc[$T['WithinSysLoc']];

        $T['Progress'] = -1; // Stalled
        TurnLog($Who,"A Minefield could not be made in " . $N['Ref'] . " " . $LocText);
      }

      $T['Instruction'] = -$T['Instruction'];
      break;

    case 'Build Space Station':
      if (!Spend_Credit($T['Whose'],$T['InstCost'],"Build Space Station in " . $N['Ref']) ) {
        $T['Progress'] = -1; // Stalled
        TurnLog($T['Whose'],"Could not afford to start a Build Space Station in " .$N['Ref'],$T);
      }
      $T['Instruction'] = -$T['Instruction'];

      // Is there a world there controlled by the faction?
      $phs = Gen_Get_Cond('ProjectHomes',"SystemId=" . $N['id']);
      foreach ($phs as $ph) {
        if ($ph['Whose'] == $T['Whose']) break 2; // Yes fine
      }
      // or a planetary mine
      $PMines = Get_Things_Cond(0,"Type=" . $TTNames['Planet Mine'] . " AND SystemId=" . $N['id'] . " AND BuildState=3");
      if (isset($PMines[0]) && $PMines[0]['Whose'] == $T['Whose']) break; // Yes fine
      // By others?
      if (empty($phs) && empty($PMines)) {
        TurnLog($T['Whose'],"Attempting to build a Build Space Station in " . $N['Ref'] . " but there is nothing there to build it round. ",$T);
        $T['Progress'] = -1; // Stalled
        break;
      }
      if ($NeedColStage2 == 0) {
        GMLog("<form method=post action=TurnActions.php?ACTION=Process&S=Instructions>");
        $NeedColStage2 = 1;
      }

      $List = [];
      foreach ($phs as $ph) {
        switch ($ph['ThingType']) {
          case 1: // Planet
            $Plan = Get_Planet($ph['ThingId']);
            $Control = ($Plan['Control'] != 0? $Plan['Control'] : $N['Control']);
            if ($Control) {
              $List[]= $Plan['Name']  . " a planet controlled by " . $Facts[$Control]['Name'];
            } else {
              $List[]= $Plan['Name']  . " an uncontrolled planet";
            }
            break;
          case 2: // Moon
            $Moon = Get_Moon($ph['ThingId']);
            $Plan = Get_Planet($Moon['PlanetId']);
            $Control = ($Moon['Control'] != 0 ? $Moon['Control'] : ($Plan['Control'] != 0 ? $Plan['Control'] : $N['Control']));
            if ($Control) {
              $List[]= $Moon['Name']  . " a moon of " . $Plan['Name'] . " controlled by " . $Facts[$Control]['Name'];
            } else {
              $List[]= $Moon['Name']  . " an uncontrolled moon";
            }
            break;
          case 3: // Thing???
            break;  // Not permitted I think
        }
      }
      GMLog($Facts[$T['Whose']]['Name'] . " making a space station in " . $N['Ref'] . " there is " . implode($List) .
               " - Allow? " . fm_YesNo("SpaceStn$Tid",1, "Reason to reject") . "\n<br>");
      break;

    case 'Expand Space Station':
      if (!Spend_Credit($T['Whose'],$T['InstCost'],"Expand Space Station in " . $N['Ref']) ) {
        $T['Progress'] = -1; // Stalled
        TurnLog($T['Whose'],"Could not afford to Expand Space Station in " .$N['Ref'],$T);
      }
      $T['Instruction'] = -$T['Instruction'];
      break;

    case 'Make Advanced Deep Space Sensor':
    case 'Make Deep Space Sensor':
      if (!Spend_Credit($T['Whose'],$T['InstCost'],"Make Deep Space Sensor in " . $N['Ref']) ) {
        $T['Progress'] = -1; // Stalled
        TurnLog($T['Whose'],"Could not afford to Make Deep Space Sensor in " .$N['Ref'],$T);
      }
      $T['Instruction'] = -$T['Instruction'];
      break;

    case 'Make Advanced Asteroid Mine':
      if (!Spend_Credit($T['Whose'],$T['InstCost'],"Make Advanced Asteroid Mine in " . $N['Ref']) ) {
        $T['Progress'] = -1; // Stalled
        TurnLog($T['Whose'],"Could not afford to start an Advanced Asteroid Mine in " .$N['Ref'],$T);
      }
      $T['Instruction'] = -$T['Instruction'];
      break;

    case 'Dismantle Stargate':
      if (!Spend_Credit($T['Whose'],$T['InstCost'],"Dismantle Stargate #" . $T['Dist1']) ) {
        $T['Progress'] = -1; // Stalled
        TurnLog($T['Whose'],"Could not afford to start to dismantle stargate #" .$T['Dist1'],$T);
      }
      $T['Instruction'] = -$T['Instruction'];
      break;

    case 'Build Stargate':
      if (!Spend_Credit($T['Whose'],$T['InstCost'],"Build Stargate Level " . $T['Dist1']) ) {
        $T['Progress'] = -1; // Stalled
        TurnLog($T['Whose'],"Could not afford to start to dismantle stargate #" .$T['Dist1'],$T);
      }
      $LinkLevels = Get_LinkLevels();

      $LL = $LinkLevels[$T['Dist1']];
      $LinkRes = GameFeature('LinkResource',0);
      if ($LinkRes) {
        AddCurrencies();
        $Cur = 0;
        foreach ($Currencies as $Ci => $C) if ( $C == $LinkRes) $Cur = $Ci;
        if (!Gain_Currency($T['Whose'],$Cur,-$LL['MakeCost'],"Build new stargate level " . $T['Dist1'] . " from " . $Systems[$T['SystemId']] .
            " to " . $Systems[$T['Dist2']])) {
          TurnLog($T['Whose'],"Could not aford the $LinkRes to build new stargate level " . $T['Dist1'] . " from " . $Systems[$T['SystemId']] .
                  " to " . $Systems[$T['Dist2']]);
          $T['Progress'] = -1; // Stalled
        }
      }
      $T['Instruction'] = -$T['Instruction'];
      break;

    case 'Make Planet Mine':
      if (!Spend_Credit($T['Whose'],$T['InstCost'],"Make Planet Mine in " . $N['Ref']) ) {
        $T['Progress'] = -1; // Stalled
        TurnLog($T['Whose'],"Could not afford to start a Planet Mine in " .$N['Ref'],$T);
      }
      $T['Instruction'] = -$T['Instruction'];
      break;

    case 'Construct Command Relay Station':
      if (!Spend_Credit($T['Whose'],$T['InstCost'],"Make Command Relay Statione in " . $N['Ref']) ) {
        $T['Progress'] = -1; // Stalled
        TurnLog($T['Whose'],"Could not afford to start a Command Relay Station in " .$N['Ref'],$T);
      }
      $T['Instruction'] = -$T['Instruction'];
      break;

    case 'Repair Command Node':
      if (!Spend_Credit($T['Whose'],$T['InstCost'],"Repair Command Node " . $N['Ref']) ) {
        $T['Progress'] = -1; // Stalled
        TurnLog($T['Whose'],"Could not afford to Repair Command Node " .$N['Ref'],$T);
      }
      $T['Instruction'] = -$T['Instruction'];
      break;

    case 'Transfer':
      $OldWho = $T['Whose'];
      $T['Whose'] = $T['Dist1'];
      if ($OldWho && $T['Whose']) {
        TurnLog($OldWho,$T['Name'] . " has been transfered to: " . $Facts[$T['Whose']]['Name'],$T);
        TurnLog($T['Whose'],$T['Name'] . " has been transfered to you from the : " .$Facts[$OldWho]['Name'],$T);
        GMLog("The " . $T['Name'] . " has been transfered from the " . $Facts[$OldWho]['Name'] . " to the " . $Facts[$T['Whose']]['Name']);
      }
      $T['Instruction'] = 0;
      $T['LinkId'] = 0;
      $T['LinkPay'] = 0;
      $T['LinkCost'] = 0;
      $T['Progress'] = 0;
      $T['ActionsNeeded'] = 0;
      $T['Dist1'] = 0;
      $T['Dist2'] = 0;
      $T['CurInst'] = 0;
      $T['MakeName'] ='';

      if ($TTypes[$T['Type']]['Properties'] & THING_HAS_CONTROL) {
        Control_Propogate($T['SystemId'],$T['Whose']);
      }
      break;

    case 'Make Something':
      if (empty($T['MakeName']) || $T['ActionsNeeded']==0) {
        $T['Progress'] = -1; // Stalled
        TurnLog($T['Whose'],"Attempted to make an ill defned thing in " . $N['Ref'],$T);
        GMLog($Facts[$T['Whose']]['Name'] . " attempting to make ill defined thing in " . $N['Ref'] . " with " . $T['Name']);
      } else if (!Spend_Credit($T['Whose'],$T['InstCost'],"Make Something" . $N['Ref']) ) {
        $T['Progress'] = -1; // Stalled
        TurnLog($T['Whose'],"Could not afford to Make something in " . $N['Ref'],$T);
      }
      $T['Instruction'] = -$T['Instruction'];
      break;

    case 'Make Warpgate':
      if (!Spend_Credit($T['Whose'],$T['InstCost'],"Make Warpgate" . $N['Ref']) ) {
        $T['Progress'] = -1; // Stalled
        TurnLog($T['Whose'],"Could not afford to Make Warpgate in " . $N['Ref'],$T);
      }
      $T['Instruction'] = -$T['Instruction'];
      break;

    case 'Clear Minefield':
      if (!Spend_Credit($T['Whose'],$T['InstCost'],"Clear Minefield" . $N['Ref']) ) {
        $T['Progress'] = -1; // Stalled
        TurnLog($T['Whose'],"Could not afford to Clear Minefield in " . $N['Ref'],$T);
      }
      $T['Instruction'] = -$T['Instruction'];
      break;

    case 'Salvage':
      $T['Instruction'] = -$T['Instruction'];
      break;

    case 'Link Repair':
// var_dump($T);
      if (!Spend_Credit($T['Whose'],$T['InstCost'],"Link Repair #" . $T['Dist1']) ) {
        $T['Progress'] = -1; // Stalled
        TurnLog($T['Whose'],"Could not afford to start Link Repair in " .$N['Ref'],$T);
        break;
      }
      $Link = Get_Link($T['Dist1']);
      if ($Link['Weight'] < $T['Dist2']) { // Need to spend aidianite
        $LinkLevels = Get_LinkLevels();

        $LL = $LinkLevels[$Link['Level']];
        $LinkRes = GameFeature('LinkResource',0);
        if ($LinkRes) {
          AddCurrencies();
          $Cur = 0;
          foreach ($Currencies as $Ci => $C) if ( $C == $LinkRes) $Cur = $Ci;
          if (!Gain_Currency($T['Whose'],$Cur,-$LL['MakeCost'],"Strengthening " . $T['Dist1'] . " from " . $Systems[$T['SystemId']] .
              " to " . $Systems[$T['Dist2']] . " at Strength " . $T['Dist2'])) {
            TurnLog($T['Whose'],"Could not aford the $LinkRes to Strengthen " . $T['Dist1'] . " from " . $Systems[$T['SystemId']] .
                    " to " . $Systems[$T['Dist2'] ]. " at Strength " . $T['Dist2']);
            $T['Progress'] = -1; // Stalled
          }
        }
      }

      $T['Instruction'] = -$T['Instruction'];
      $Link['Status'] = 1;
//var_dump($Link);
      Put_Link($Link);
      break;

    case 'Collaborative DSC': // Dist1 has Thing number being helped
      $HT = Get_Thing($T['Dist1']);
      if (empty($HT)) {
        TurnLog($T['Whose'],$T['Name'] . " Could not do Collaborative DSC with unknown ship.",$T);
      } else if ($T['SystemId'] != $HT['SystemId']) {
        TurnLog($T['Whose'],$T['Name'] . " Could not do Collaborative DSC with " . $HT['Name'] . " as not in the same system.",$T);
        $T['Instruction'] = 0;
      } else {
        // Should work
      }
      break;

    default:

    }
    Put_Thing($T);
  }

  if ($NeedColStage2) {
    echo "<input type=submit name=Ignore value=Chosen>\n";


    dotail();
  }
  return 2;
}

function InstructionsStage2() { // And other Instructions
  global $ThingInstrs,$GAME;
  global $FACTION;

  $Things = Get_Things_Cond(0,"Instruction!=0");
  $TTNames = Thing_Types_From_Names();
  $Systems = Get_SystemRefs();
  $Facts = Get_Factions();
  $NeedColStage3 = 0;
//echo "HERE";
  foreach ($Things as $T) {
    $Tid = $T['id'];
    $Fid = $T['Whose'];
    $N = Get_System($T['SystemId']);
    switch ($ThingInstrs[abs($T['Instruction'])]) {

    case 'Voluntary Warp Home': // Warp out
      if (isset($_REQUEST["G$Tid"])) {
        $Gate = Get_Thing($_REQUEST["G$Tid"]);
      } else {
        $Gates = Get_Things_Cond($T['Whose'],' Type=15'); // Warp Gates
        $Gate = $Gates[0];
      }
      $T['SystemId'] = $Gate['SystemId'];
      $T['WithinSysLoc'] = $Gate['WithinSysLoc'];
      $T['CurHealth'] = $T['LinkId'] = 0;
      TurnLog($T['Whose']," The " . $T['Name'] . " has warped back.  It now needs repair before it can be used again",$T);
      GMlog("The " . $Facts[$T['Whose']]['Name'] . " - " . $T['Name'] . " has warped back to: " . $Systems[$T['SystemId']] );
      $T['Instruction'] = 0;
      Put_Thing($T);
      break;

    case 'Establish Embassy':
      $N = Get_System($T['SystemId']);
      if (isset($_REQUEST["Emb$Tid"]) &&  $_REQUEST["Emb$Tid"] != "on") {
        TurnLog($T['Whose'],"The embassy was not created in " . $N['Ref'] . " because " . $_REQUEST["ReasonEmb$Tid"] . "\n<br>",$T);
        $T['Instruction'] = 0;
        Put_Thing($T);
        break;
      }

      $Facts = Get_Factions();
      foreach ($Facts as $F) {
        if ($F['id'] == $T['Whose'] || $F['HomeWorld'] == 0) continue;
        $W = Get_World($F['HomeWorld']);
        $H = Get_ProjectHome($W['Home']);
        if ($H['SystemId'] == $T['SystemId']) break;
      }

      $NT = ['GameId'=>$GAME['id'], 'Type'=> 17, 'Level'=> 1, 'SystemId'=>$T['SystemId'], 'WithinSysLoc'=>$H['WithinSysLoc'], 'Whose'=>$T['Whose'], 'BuildState'=>3,
               'TurnBuilt'=>$GAME['Turn'], 'OtherFaction'=>$T['OtherFaction'], 'Name'=>$T['MakeName']];
      Put_Thing($NT);
      $OF = $Facts[$T['OtherFaction']];
      TurnLog($T['Whose'],"You have created an Embassy in " . $N['Ref'] . " with the " . $OF['Name'],$T);
      TurnLog($T['OtherFaction'],"An embassy to you has been setup by " . $Facts[$T['Whose']]['Name']);
      GMLog($T['OtherFaction'],"An embassy to "  . $Facts[$T['Whose']]['Name'] . " has been setup in " . $N['Ref'] . " with the " . $OF['Name']);
      $T['Instruction'] = 0;
      $T['CurInst'] = 0;
      $T['Progress'] = 0;
      $T['MakeName'] = '';
      Put_Thing($T);
      break;

    case 'Analyse Anomaly': // Anomaly
      $Aid = $T['ProjectId'];
      if ($Aid) {
        $A = Get_Anomaly($Aid);
        $FA = Gen_Get_Cond1('FactionAnomaly',"AnomalyId=$Aid AND FactionId=$Fid");
        if ( ($FA['State'] == 1) && $A['OtherReq']) {
          if (isset($_REQUEST["AA$Tid"]) &&  $_REQUEST["AA$Tid"] == "on") {
            $FA['State'] = 2;
            Gen_Put('FactionAnomaly',$FA);
            break;
          }
          TurnLog($T['Whose'], $T['Name'] . " Could not start to analyse: " . $A['Name'] . " because " . $_REQUEST["ReasonAA$Tid"] . "\n<br>",$T);
          $T['Instruction'] = $T['ProjectId'] = 0;
          Put_Thing($T);
        }
      }
      break;

    case 'Colonise': // Colonise
    case 'Build Planetary Mine':
// var_dump($_REQUEST);

      $P = Get_Planet($T['Spare1']);

      if ($N['Control'] > 0 && $N['Control'] != $T['Whose']) {  // Colonising system under control of others
        if (!isset($_REQUEST["Col$Tid"]) ||  $_REQUEST["Col$Tid"] != "on") {
          TurnLog($T['Whose'],"The colony was not created in " . $N['Ref'] . " because " . $_REQUEST["ReasonCol$Tid"] . "\n<br>",$T);
          $T['Instruction'] = 0;
          Put_Thing($T);
          break;
        }
      }

      if (($ThingInstrs[$T['Instruction']]) == 'Colonise') {
        TurnLog($T['Whose'],"The " . $T['Name'] . " is colonising " . $P['Name'] . " in " . $N['Ref'] ,$T);
        GMlog($Facts[$T['Whose']]['Name'] . " is starting to colonise " . $P['Name'] . " in " . $N['Ref']);
      } else {
        TurnLog($T['Whose'],"The " . $T['Name'] . " is seting up planetary mining " . $P['Name'] . " in " . $N['Ref'] ,$T);
        GMlog($Facts[$T['Whose']]['Name'] . " is starting to planetary mine " . $P['Name'] . " in " . $N['Ref']);
      }
      break;

    case 'Build Space Station':
      $N = Get_System($T['SystemId']);
      if (isset($_REQUEST["SpaceStn$Tid"]) &&  $_REQUEST["SpaceStn$Tid"] != "on") {
        TurnLog($T['Whose'],"The space station was not created in " . $N['Ref'] . " because " . $_REQUEST["ReasonSpaceStn$Tid"] . "\n<br>",$T);
        $T['Instruction'] = 0;
        Put_Thing($T);
      }
      break;

    case 'Make Orbital Repair Yard':
      $N = Get_System($T['SystemId']);
      if (isset($_REQUEST["SpaceStn$Tid"]) &&  $_REQUEST["SpaceStn$Tid"] != "on") {
        TurnLog($T['Whose'],"The Orbital Repair Yard was not created in " . $N['Ref'] . " because " . $_REQUEST["ReasonSpaceStn$Tid"] . "\n<br>",$T);
        $T['Instruction'] = 0;
        Put_Thing($T);
      }
      break;


    default:
    break;
    }
  }

  Done_Stage("Instructions");
  Done_Stage("Instructions Stage 2");
//global $Sand;
//var_dump($Sand);
  return 1;
}

function ClearPaidFor() {
  $Things = Get_Things_Cond(0,"Instruction<0");
  foreach ($Things as $T) {
    $T['Instruction'] = -$T['Instruction'];
    Put_Thing($T);
  }
  return 1;
}

function AgentsStartMissions() {
  GMLog("Agents Start Missions	are currently Manual<p>");
  return 1;
}

function ProjectProgressActions($Pay4=0) {
  // Mark progress on all things, if finished change state appropriately
//  echo "Project Progress is currently Manual<p>";
// Note the rushes have already been paid for in the almost identicalcode PayForRushes above.
  global $GAME;

  $ProjTypes = Get_ProjectTypes();
  $Projects = Get_Projects_Cond("Status=1");
  $DistTypes = Get_DistrictTypes();
  $ThingTypes = Get_ThingTypes();
  $Factions = Get_Factions();
  $Worlds = Get_Worlds();
  $Homes = Get_ProjectHomes();

  foreach ($Projects as $P) {
    if ($P['LastUpdate'] >= $GAME['Turn']) continue;
    GMLog("Updating project " . $P['id'] . " " . $P['Name']);

    $H = $Homes[$P['Home']];
    $Wid = 0;
    $Fid = $P['Whose'];
    foreach($Worlds as $W) {
      if ($W['Home'] == $P['Home']) {
        $Wid = $W['id'];
        break;
      }
    }
    if ($Wid == 0) {
      echo "<h2 class=Err>Something went wrong - can't find world for project " . $P['id'] . " call Richard...</h2>";
      exit;
    }
    $W = $Worlds[$Wid];

    if (empty($H)) {
          GMLog("<b>Project " . $P['id'] . " fails as the world does not exist.  </b><p>");
          TurnLog($P['FactionId'], " <b>Project " . $P['Name'] . " fails as " . $P['Name'] . " is no longer controlled by you.");

          Abandon_Project($P);
          continue;
    }
    $Revolt = $W['Revolt'];

    if (isset($H['ThingType'])) switch ($H['ThingType']) {
      case 1: // Planet
        $PH = Get_Planet($H['ThingId']);
        if ($PH['Control'] != 0) {
          $C = $PH['Control'];
        } else {
          $N = Get_System($PH['SystemId']);
          $C = $N['Control'];
        }
        if ($C != $P['FactionId']) {
          GMLog("<b>Project " . $P['id'] . " fails as <a href=PlanEdit.php?id=" . $H['ThingId']. ">" . $PH['Name'] . "</a>" .
                " is no longer controlled by " . $Factions[$P['FactionId']]['Name'] . "</b><p>");
          TurnLog($P['FactionId'], " <b>Project " . $P['Name'] . " fails as " . $PH['Name'] . " is no longer controlled by you.");
          Abandon_Project($P);
          continue 2;
        }
        break;
      case 2: // Moon
        $PH = Get_Moon($H['ThingId']);
        if ($PH['Control'] != 0) {
          $C = $PH['Control'];
        } else {
          $PL = Get_Planet($PH['PlanetId']);
          if ($PL['Control'] != 0) {
            $C = $PL['Control'];
          } else {
            $N = Get_System($PL['SystemId']);
            $C = $N['Control'];
          }
        }
        if ($C != $P['FactionId']) {
          GMLog("<b>Project " . $P['id'] . " fails as <a href=MoonEdit.php?id=" . $H['ThingId']. ">" . $PH['Name'] . "</a>" .
                " is no longer controlled by " . $Factions[$P['FactionId']]['Name'] . "</b><p>");
          TurnLog($P['FactionId'], " <b>Project " . $P['Name'] . " fails as " . $PH['Name'] . " is no longer controlled by you.");
          Abandon_Project($P);
          continue 2;
        }
        break;
      case 3: // Thing
        $PH = Get_Thing($H['ThingId']);
        if ($PH['Whose'] != $P['FactionId']) {
          GMLog("<b>Project " . $P['id'] . " fails as <a href=ThingEdit.php?id=" . $H['ThingId']. ">" . $PH['Name'] . "</a>" .
                " is no longer controlled by " . $Factions[$P['FactionId']]['Name'] . "</b><p>");
          TurnLog($P['FactionId'], " <b>Project " . $P['Name'] . " fails as " . $PH['Name'] . " is no longer controlled by you.");
          Abandon_Project($P);
          continue 2;
        }
        break;
    }

    $PT = $ProjTypes[$P['Type']];

    if (($PT['Category'] & 255) == 255) { // Post-it
      if ($P['DType'] == -1) { $PT['Category'] = 16; }
      else if ($P['DType'] == 2) { $PT['Category'] = 4; }
      else if ($P['DType'] == 3) { $PT['Category'] = 2; }
      else if ($P['DType'] == 4) { $PT['Category'] = 8; }
      else if ($P['DType'] == 5) { $PT['Category'] = 1; }
      else if ($P['DType'] == 10) { $PT['Category'] = 2; }
      // else default actions
    }

    if ($PT['Category'] & 16) { // Construction

      $Fact = Get_Faction($P['FactionId']);
      $MaxActs = PlanConst($Fid,$Wid);

      if (!isset($H['ThingType'])) {
        GMLog("<b>Confused state for project " . $P['id'] . "</b><p>");
        $H['Skip'] = 1;
        continue;  // Remove things without districts
      }

      switch ($H['ThingType']) {
        case 1: // Planet
          $PH = Get_Planet($H['ThingId']);
          break;
        case 2: // Moon
          $PH = Get_Moon($H['ThingId']);
          break;
        case 3: // Thing
          $PH = Get_Thing($H['ThingId']);
          break;
      }
    } else if ($PT['Category'] & 32) { // Deep SPace - TODO

    } else if ($PT['Category'] > 32) { // Intelligence -TODO

    } else { // District based
      if (!isset($H['ThingType'])) {
        GMLog("<b>Confused state for project " . $P['id'] . "</b><p>");
        $H['Skip'] = 1;
        continue;  // Remove things without districts
      }
      switch ($H['ThingType']) {
        case 1: // Planet
          $PH = Get_Planet($H['ThingId']);
          $Dists = Get_DistrictsP($H['ThingId']);
          break;
        case 2: // Moon
          $PH = Get_Moon($H['ThingId']);
          $Dists = Get_DistrictsM($H['ThingId']);
          break;
        case 3: // Thing
          $PH = Get_Thing($H['ThingId']);

          if ($ThingTypes[$PH['Type']]['Properties'] & THING_CAN_DO_PROJECTS) {

/*            $ORY = 0;
            foreach($DistTypes as $DT) if ($DT['Name'] == 'Orbital Repair') $ORY = $DT['id'];
            $Dists = [$ORY=>['HostType'=>3,'HostId'=>$PH['id'],'Type'=>$ORY,'Number'=>1, 'id'=>-1]];*/
            $Dists = [3=>['HostType'=>3,'HostId'=>$PH['id'],'Type'=>3,'Number'=>1, 'id'=>-1]];
            $MaxActs = $NoC = 1;
            break;
          }

          $Dists = Get_DistrictsT($H['ThingId']);
          if (!$Dists) {
            $H['Skip'] = 1;
            continue 2;  // Remove things without districts
          }
          break;
      }

//var_dump($Dists);
      switch ($PT['Category']) {
      case 1: $MaxActs = $Dists[5]['Number']; break;
      case 2: $MaxActs = $Dists[3]['Number']; break;
      case 4: $MaxActs = $Dists[2]['Number']; break;
      case 8: $MaxActs = $Dists[4]['Number']; break;
      default:
        GMLog("<b>Confused state for project " . $P['id'] . "</b><p>");
      }
    }

//echo "Maxacts . $MaxActs<br>";

  // Find Project Home
  // Find Dists or Skill to base on
  // Set maxact from num dists or skill


    $TurnStuff = Get_ProjectTurnPT($P['id'],$GAME['Turn']);

    $Rush = $FreeRush = 0;
    $Bonus = 0;
    if (!empty($TurnStuff['Bonus'])) $Bonus = $TurnStuff['Bonus'];

    $PActs = $Acts = min($MaxActs,$P['ProgNeeded']-$P['Progress']);
    if ($Revolt) {
      $Acts = 0;
      TurnLog($P['FactionId'],'No normal progress on ' . $P['Name'] . " by because of <b>Revolt</b> ");
    }
    $mtch = [];
    if (preg_match('/Research/',$PT['Name'],$mtch) && Has_Trait($P['FactionId'],'Built for Construction and Logistics')) {
      $TechId = $P['ThingType'];
      $Tech = Get_Tech($TechId);
      if ($Tech['PreReqTech'] == 1 || $TechId == 1) {
        $FreeRush = min(1,$PActs,$P['ProgNeeded']-$P['Progress']-$Acts-$Bonus);
        if ($FreeRush && $Pay4 == 0) {
          TurnLog($P['FactionId'],'Free Rush of ' . $P['Name'] . " by $FreeRush ");
        }
      }
    }
    if (isset($TurnStuff['Rush'])) {
      $Rush = min($TurnStuff['Rush'],$PActs,$P['ProgNeeded']-$P['Progress']-$Acts-$Bonus-$FreeRush);
      if ($Rush) {
        if (isset($P['FreeRushes']) && $P['FreeRushes']>0) continue;
        if ($Pay4) {
          if (Spend_Credit($P['FactionId'],$Rc = (Rush_Cost($P['FactionId'])*$Rush), 'Rushing ' . $P['Name'] . " By $Rush")) {
            TurnLog($P['FactionId'],'Rushing ' . $P['Name'] . " by $Rush  Cost: " . Credit() . " $Rc");
          } else {
            TurnLog($P['FactionId'],'Not enough Credits to Rush: ' . $P['Name']);
            $TurnStuff['Rush'] = 0;
            Put_ProjectTurn($TurnStuff);
            $Rush = 0;
          }
        }
      }
    }

//echo "Acts . $Acts<br>";
    if ($Pay4 == 0) {
      $P['Progress'] = min($P['ProgNeeded'], $P['Progress']+$Acts+$Rush+$Bonus+$FreeRush);
      TurnLog($P['FactionId'],"Progressing " . $P['Name'] . " by " . ($Acts+$Rush+$Bonus+$FreeRush));
      $P['LastUpdate'] = $GAME['Turn'];
      Put_Project($P); // Note completeion is handled later in the turn sequence
    }
  }

  return 1;
}


function PayForRushes() {
  ProjectProgressActions(1); // Pay 4 rushes only
  GMLog("Rushes paid for<p>");
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
    $Worlds = Get_Worlds($Fid);

    $LinkTypes = Get_LinkLevels();
    $HasHomeLogistics = Has_Tech($Fid,'Simplified Home Logistics');
    $FactionHome = 0;
    if ($HasHomeLogistics) {
      $Faction = Get_Faction($Fid);
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


    $EconVal = 0;
    $EccTxt = "\nEconomy:\n";
    $OutPosts = $AstMines = $AstVal = $Embassies = $OtherEmbs = $MineFields = 0;
    foreach ($Worlds as $W) {
      $H = Get_ProjectHome($W['Home']);
      if (empty($H)) continue;
      $PH = Project_Home_Thing($H);
      if (!$PH) continue;
      $Name = $PH['Name'];
      $ECon = $H['Economy'] = Recalc_Economic_Rating($H,$W,$Fid);
      if ($W['Revolt']) {
        $ECon = 0;
        echo "It is in <b>Revolt</b> no income<br>\n";
      } else {
        if ($H['Devastation']) {
          $ECon = $ECon - $H['Devastation'];
          $EccTxt .= " It has devastation reducing it to: $ECon <br>\n";
        }

        if ($W['Blockade'] ) { //&& $Fid != 9) {
          $ECon /= 2;
          $EccTxt .=  "It is blockaded income is halved  to $ECon\n";
        } else {
          $ECon = ceil(($ECon - $H['Devastation'])*$H['EconomyFactor']/100);
        }
      }
      if ($ECon <=0 && $Name) {
          $EccTxt .= "$Name: None\n";
      } else {
        $EccTxt .= "$Name: $ECon " . ($H['Devastation']? " after devastation effect of -" . $H['Devastation'] : "");
        if ($H['EconomyFactor'] < 100) {
          $EccTxt .= " - at " . $H['EconomyFactor'] . "%\n";
        } else if ($H['EconomyFactor'] > 100) {
          $EccTxt .= " - at " . $H['EconomyFactor'] . "%\n";
        }
        $EconVal += "$ECon\n";
      }
      echo "\n";
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
          $AstVal += $Plan['Minerals']*$T['Level'];
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

    $OtherTs = Get_Things_Cond(0,"Type=17 AND OtherFaction=$Fid AND BuildState=3");
    foreach($OtherTs as $OT) {
      $OtherEmbs++;
    }

    if ($OutPosts) {
      $EccTxt .= "Plus $OutPosts Outposts worth 2 each\n";
      $EconVal += $OutPosts*2;
    }
    if ($AstMines) {
      if (Feature('AstmineDSC')) $AstVal *= Has_Tech($Fid,'Deep Space Construction');
      $EccTxt .= "Plus $AstMines Asteroid Mines worth a total of $AstVal\n";
      $EconVal += $AstVal;
    }
    if ($Embassies) {
      $EccTxt .= "Plus $Embassies of your Embassies worth 1 each\n";
      $EconVal += $Embassies;
    }
    if ($OtherEmbs) {
      $EccTxt .= "Plus $OtherEmbs of other Embassies worth 1 each\n";
      $EconVal += $OtherEmbs;
    }

    if ($MineFields) {
      $EccTxt .= "Less $MineFields of Minefields worth 1 each\n";
      $EconVal -= $MineFields;
    }

    $Logistics = [0,0,0]; // Ship, Army, Intelligence
    foreach ($Things as $T) {
      if (empty($T['Type'])) continue;
      $Props = $TTypes[$T['Type']]['Properties'];
      if ($T['BuildState'] == 2 || $T['BuildState'] == 3) {
        if ($HasHomeLogistics && ($T['SystemId'] == $Facts[$T['Whose']]['HomeWorld'])) $T['Level'] /=2;
        if ($Props & THING_HAS_ARMYMODULES) $Logistics[1] += $LogistCost[$T['Level']];
        if ($Props & THING_HAS_GADGETS) $Logistics[2] += $LogistCost[$T['Level']];
        if ($Props & ( THING_HAS_MILSHIPMODS | THING_HAS_CIVSHIPMODS)) $Logistics[0] += $LogistCost[$T['Level']];
      };
    }

    foreach($Logistics as &$Log) $Log = floor($Log);
    $LogAvail = LogisticalSupport($Fid);
    $LogCats = ['Ships',$ARMIES,'Agents'];

    foreach ($LogCats as $i => $n) {
      if ($Logistics[$i]) {
        $pen = min(0,$LogAvail[$i]-$Logistics[$i]);
        if ($pen < 0) {
          $EconVal += $pen;
          $EccTxt .= "Logistical penalty of $pen for $n\n";
        }
      }
    }

    $EccTxt .= "Total Economy is $EconVal worth " . $EconVal*10 . "\n\n";
    Spend_Credit($Fid, -$EconVal*10, "Turn Income");
    TurnLog($Fid,$EccTxt);
    GMLog("Done Economy for " . $F['Name'] . "<br>");

  }
  return 1;
}

function DirectMoves() {
  // EG for sharks
  $Things = Get_Things_Cond(0,'LinkId=-6 AND NewSystemId!=0 ');
  $TTypes = Get_ThingTypes();
  $SysRefs = Get_SystemRefs();
  foreach($Things as $T) {
    $Tid = $T['id'];
    $T['SystemId'] = $T['NewSystemId'];
    $T['WithinSysLoc'] = $T['NewLocation'];
    $T['NewSystemId'] = $T['LinkId'] = 0;
    Put_Thing($T);
    GMLog("Directly moved " . (empty($T['Name'])?"Nameless $Tid": $T['Name']) . " a " . $TTypes[$T['Type']]['Name'] . " to " . $SysRefs[$T['SystemId']]);
  }
  return 1;
}

function LoadTroops() {
  $Things = Get_Things_Cond(0,"(BuildState=2 OR BuildState=3) AND (LinkId=-2 OR LinkId=-4)");
  $TTypes = Get_ThingTypes();
  $Facts = Get_Factions();

  foreach($Things as $T) {
    $HostId = $T['NewSystemId'];
    $H = Get_Thing($HostId);
    $NeedCargo = ($TTypes[$T['Type']]['Properties'] & THING_NEEDS_CARGOSPACE);
    if ($H['Whose'] != $T['Whose']) {
      $FF = Get_FactionFactionsCarry($T['Whose']);
      $Carry = (empty($FF[$H['Whose']])? 0 : $FF[$H['Whose']]);
      if (!$NeedCargo) $Carry >>= 4;
      if (($Carry&15) <2) {
        TurnLog($T['Whose'],"You tried to load " . $T['Name'] . " on to " . $H['Name'] . " access was denied by " . $Facts[$H['Whose']]['Name'],$T);
        TurnLog($H['Whose'],  $Facts[$T['Whose']]['Name'] . " tried to load " . $T['Name'] . " on to " . $H['Name'] . " you denied access",$T);
        GMLog($Facts[$T['Whose']]['Name'] . " tried to load " . $T['Name'] . " on to " . $H['Name'] . " access was denied by " . $Facts[$H['Whose']]['Name'],1);
        $T['LinkId'] = 0;
        Put_Thing($T);
        continue;
      }
    }

    if ($NeedCargo) {
      $OnBoard = Get_Things_Cond(0,"(LinkId=-1 OR LinkId=-3) AND SystemId=" . $H['id']);
      $Used = 0;
      foreach($OnBoard as $OB) if ($TTypes[$OB['Type']]['Properties'] & THING_NEEDS_CARGOSPACE) $Used += $OB['Level'];
      if ($H['CargoSpace'] < $Used + $T['Level']) {
        TurnLog($T['Whose'],"You tried to load " . $T['Name'] . " on to " . $H['Name'] . " there is not enough space",$T);
        if ($H['Whose'] != $T['Whose'])
          TurnLog($H['Whose'],  $Facts[$T['Whose']]['Name'] . " tried to load " . $T['Name'] . " on to " . $H['Name'] . " there is not enough space");
        GMLog($Facts[$T['Whose']]['Name'] . " tried to load " . $T['Name'] . " on to " . $H['Name'] . " there is not enough space",1);
        $T['LinkId'] = 0;
        Put_Thing($T);
        continue;
      }
    }

    TurnLog($T['Whose'],"Loaded " . $T['Name'] . " on to " . $H['Name'], $T);
    if ($H['Whose'] != $T['Whose']) TurnLog($H['Whose'],  $Facts[$T['Whose']]['Name'] . " loaded " . $T['Name'] . " on to " . $H['Name'] );
    GMLog($Facts[$T['Whose']]['Name'] . " loaded " . $T['Name'] . " on to " . $H['Name']);


    $T['SystemId'] = $HostId;
    if ($T['LinkId'] == -2) {
      $T['LinkId'] = -1;
    } else {
      $T['LinkId'] = -3;
    }
// var_dump($T); echo "<P>";

    Put_Thing($T);
  }


  GMLog("<br>Load Troops	is Complete<p>");
  return 1;
}

function ShipMoveCheck($Agents=0) {  // Show all movements to allow for blocking
  global $GAME,$GAMEID,$LinkStates;
  $LinkLevels = Get_LinkLevels();
  $Things = Get_AllThings();
  $TTypes = Get_ThingTypes();
  $Facts = Get_Factions();
  $LOWho = GameFeature('LinkOwner',0);

  GMLog("<h2>These movements are planned - to stop one, tick the stop box and say why</h2>");
//  GMLog("<form method=Post action=TurnActions.php?ACTION=Complete>" . fm_hidden('S',($Agents?34:32)));
  GMLog("<form method=Post action=TurnActions.php?ACTION=StageDone>" . fm_hidden('Stage',($Agents?'Agents Move Check':'Ship Move Check')));


  $UsedLinks = [];
  if ($Agents) {
    foreach ($Things as $T) {
      if ($T['BuildState'] <2 || $T['BuildState'] > 3 || $T['LinkId'] <= 0 || $T['Whose']==0) continue;
      if ($TTypes[$T['Type']]['Properties'] & THING_MOVES_AFTER) {
      } else {
        $UsedLinks[$T['LinkId']][$T['Whose']] = ($T['SystemId'] == $T['NewSystemId']);
      }
    }
  }
// var_dump($UsedLinks);

  GMLog("<table border><tr><td>Who<td>What<td>Level<td>From<td>Link<td>To<td>Paid<td>Stop<td>Why Stopping\n");
  foreach ($Things as $T) {
    if ($T['BuildState'] <2 || $T['BuildState'] > 3 || $T['LinkId'] <= 0 || $T['Whose']==0 || $T['CurHealth']==0) continue;
    if (( $Agents == 0 &&  ($TTypes[$T['Type']]['Properties'] & THING_MOVES_AFTER)) ||
        ( $Agents &&  ($TTypes[$T['Type']]['Properties'] & THING_MOVES_AFTER) ==0 ) ) continue;

    $CheckNeeded = ( $Agents && ($TTypes[$T['Type']]['Properties'] & THING_MOVES_AFTER) );

    if ($T['LinkId']>0 && $T['NewSystemId'] != $T['SystemId'] ) {
      $Tid = $T['id'];
      $Lid = $T['LinkId'];

      $L = Get_Link($Lid);

      $Fid = $T['Whose'];
      $SR1 = Get_SystemR($L['System1Ref']);
      $SR2 = Get_SystemR($L['System2Ref']);

      GMLog("<tr><td>" . $Facts[$Fid]['Name'] . "<td><a href=ThingEdit.php?id=$Tid>" . $T['Name']  . "<td>" . $T['Level']);
      if ($T['SystemId'] == $SR1['id']) {
         GMLog("<td>" . $L['System1Ref'] . "<td style=color:" . $LinkLevels[abs($L['Level'])]['Colour'] . ";>#$Lid<td>" . $L['System2Ref']);
      } else {
         GMLog("<td>" . $L['System2Ref'] . "<td style=color:" . $LinkLevels[abs($L['Level'])]['Colour'] . ";>#$Lid<td>" . $L['System1Ref']);
      }

// var_dump($CheckNeeded,$T['LinkPay']);

      if ($L['Status'] != 0) {
        GMLog("<td class=Err>" . $LinkStates[$L['Status']]);
      } else if ($L['Level'] ==1 || $T['LinkPay']<0 || ($LOWho>0 && $Fid == $LOWho)) {
        GMLog("<td>Free");
      } elseif ($T['LinkPay'] > 0) {
        GMLog("<td>Yes");
      } elseif ($CheckNeeded && isset($UsedLinks[$Lid][$T['Whose']]) && $UsedLinks[$Lid][$T['Whose']]) {
        GMLog("<td>Following");
      } else {
        GMLog("<td><b>No</b");
      }


//      GMLog("<td>" . (($L['Level'] ==1 || $T['LinkPay']<0)?'Free':($T['LinkPay'] > 0?'Yes':
//            ($CheckNeeded && isset($UsedLinks[$Lid][$T['Whose']]) && $UsedLinks[$Lid][$T['Whose']])?'Following':'<b>No</b>')));
      GMLog("<td>" . fm_checkbox('',$_REQUEST,"Prevent$Tid") . fm_text1('', $_REQUEST,"Reason$Tid"));
    }
  }
  GMLog("</table><input type=submit value='Click to Proceed'></form>\n");
  dotail();

}

function ShipMovements($Agents=0) {
  global $GAME,$GAMEID,$LinkStates;
  // Foreach thing, do moves, generate list of new survey reports & levels, update "knowns"

  if (!file_exists("Turns/" . $GAMEID . "/" . $GAME['Turn'])) $LF = mkdir("Turns/" . $GAMEID . "/" . $GAME['Turn'],0777,true);

  $LinkLevels = Get_LinkLevels();
  $Things = ($Agents ? Get_AllThings() : Gen_Get_Table('Things',"ORDER BY RAND()"));
  $TTypes = Get_ThingTypes();
  $Facts = Get_Factions();
  $LOwner = GameFeature('LinkOwner',0);
  $LinkState = array_flip($LinkStates);

  foreach ($Things as $T) {
    if ($T['BuildState'] <2 || $T['BuildState'] > 3 || $T['LinkId'] <= 0 || $T['Whose']==0 || $T['CurHealth']==0) continue;
/*    if ($Agents == 0 && ($T['LinkId'] <= 0) {
      if (($TTypes[$T['Type']]['Properties'] & THING_CAN_BETRANSPORTED)) {

      }
      continue;
    }*/
    if (( $Agents == 0 &&  ($TTypes[$T['Type']]['Properties'] & THING_MOVES_AFTER)) ||
        ( $Agents &&  ($TTypes[$T['Type']]['Properties'] & THING_MOVES_AFTER) ==0 ) ) continue;

    if ($T['LastMoved'] == $GAME['Turn']) continue; // Already done

    $Tid = $T['id'];
    $Fid = $T['Whose'];
    $Lid = $T['LinkId'];
    if ($Lid > 0) $L = Get_Link($Lid);

    if (isset($_REQUEST["Prevent$Tid"]) && $_REQUEST["Prevent$Tid"] ) {
      TurnLog($Fid,$T['Name'] . " was <b>unable to take link</b> <span style=color:" . $LinkLevels[abs($L['Level'])]['Colour'] . ">#$Lid </span> beause of " .
        (isset($_REQUEST["Reason$Tid"])? $_REQUEST["Reason$Tid"]:"Unknown reasons"), $T);
      $T['LastMoved'] = $GAME['Turn'];
      Put_Thing($T);
      continue;
    }

    if ($T['LinkId']>0 && $T['NewSystemId'] != $T['SystemId'] ) {
      // if link out & not spider - cant move

      if ($L && ($L['Status'] > 0) && ($Fid != $LOwner) && ($L['GameId']>0)) {
        TurnLog($Fid,$T['Name'] . " was <b>unable to take link</b> <span style=color:" . $LinkLevels[abs($L['Level'])]['Colour'] . ">#$Lid </span> because it is " .
          $LinkStates[$L['Status']],$T);
        $T['LastMoved'] = $GAME['Turn'];
        Put_Thing($T);
        continue;
      } else if (!$L || ($L['GameId'] < 0)) {
        GMLog("Not Moving " . $T['Name']);
        TurnLog($Fid,$T['Name'] . " was <b>unable to take link</b>#$Lid because it no longer exists",$T);
        $T['LastMoved'] = $GAME['Turn'];
        Put_Thing($T);
        continue;
      }
      GMLog("Moving " . $T['Name']);

      $ShipScanLevel = Scanners($T);
      $ShipNebScanLevel = NebScanners($T);

      $SR1 = Get_SystemR($L['System1Ref']);
      $SR2 = Get_SystemR($L['System2Ref']);



      if (($Agents == 0) && ($T['Whose'] != $LOwner)) {
        $L['UseCount'] += $T['Level'];
        if (Feature('LinksExplode') && ($L['UseCount'] > 100*$L['Weight'])) {// something breaks
          $BadProb = $T['Level']*($L['UseCount'] - 100*$L['Weight']);
          if (rand(0,100*$L['Weight']) > $BadProb) { // There she blows!!!!
            $DamageDice = (abs($L['Level'])+1)*2;
            GMLog("<span class=Red>LINK EXPLOSION</span> on link " . $L['id'] . " from " . $L['System1Ref'] . " to " . $L['System2Ref'] );
            GMLog("Movement will be paused.  Do ($DamageDice D10) x 10 to everything (Including Outposts, Space Stations etc) in " .
                  "<a href=Meetings.php?ACTION=Check&R=" . $L['System1Ref'] . ">" . $L['System1Ref'] . "</a> And " .
                  "<a href=Meetings.php?ACTION=Check&R=" . $L['System2Ref'] . ">" . $L['System2Ref'] . "</a>");
            // Destroy Link
//            db_delete('Links',$L['id']);

            // Emergency lockdown both ends

            SetAllLinks($L['System1Ref'], $SR1['id'],$LinkState['In Safe Mode']);
            SetAllLinks($L['System2Ref'], $SR2['id'],$LinkState['In Safe Mode']);

            Report_Others(0, $SR1['id'], 31, "Link #$Lid Exploded.  All other links in " . $L['System1Ref'] . " have been put in Safe Mode");
            Report_Others(0, $SR2['id'], 31, "Link #$Lid Exploded.  All other links in " . $L['System2Ref'] . " have been put in Safe Mode");

            // Remove the link!

            $L['GameId'] = - $L['GameId'];
            Put_Link($L);
            $SetBreak = 1;
          }
        }
        Put_Link($L);
      }

      if ($T['SystemId'] == $SR1['id']) {
        $Sid = $T['NewSystemId'] = $SR2['id'];
        $Ref = $SR2['Ref']; // Names...
        $N = $SR2;
        $OldSid = $SR1['id'];
        $OldN = $SR1;
        $GFrom = 'A';
        $GTo = 'B';
      } else {
        $Sid = $T['NewSystemId'] = $SR1['id'];
        $Ref = $SR1['Ref'];
        $N = $SR1;
        $OldSid = $SR2['id'];
        $OldN = $SR2;
        $GFrom = 'B';
        $GTo = 'A';
      }

      $MineChecks = ['From'=>$GFrom, 'To'=>$GTo];
      foreach ($MineChecks as $Dir=>$MC) {
//      var_dump($L);
        if ($L["Mined$MC"]) {
          $Mine = Get_Thing($L["Mined$MC"]);
          if ($Mine) {
// echo "Mine Damage "; var_dump($T,$N,$OldN,$Dir); echo "<P>";
            if ($Dir == 'To' ) {
              Do_Mine_Damage($T,$Mine, $N,1);
            } else {
              Do_Mine_Damage($T,$Mine, $OldN,1);
            }
          }
          if ($T['BuildState'] != 3) continue 2;
        }
      }

      if ($Fid) {
        $FS = Get_FactionSystemFS($Fid,$Sid);
        if (!isset($FS['id']) || ($FS['ScanLevel'] < $ShipScanLevel) || ($FS['NebScanned']<$ShipNebScanLevel)) {
          $SP = ['FactionId'=>$Fid, 'Sys'=> $Sid, 'Scan'=>$ShipScanLevel, 'Neb'=>$ShipNebScanLevel,
                 'Turn'=>$GAME['Turn'], 'ThingId'=>$T['id']];

          Insert_db('ScansDue', $SP);
        }

        $FL = Get_FactionLinkFL($Fid,$Lid);
        $FL['Known'] = 1;
        Put_FactionLink($FL);

        $pname = System_Name($N,$Fid);
      } else {
        $pname = $N['Ref'];
      }

      // Leaving Minefields
      if (!$Agents) Move_Thing_Within_Sys($T,1,1);
//      $N = Get_System($T['NewSystemId']);
      $EndLocs = Within_Sys_Locs($N);
      $T['SystemId'] = $T['NewSystemId'];
      $T['WithinSysLoc'] = $T['NewLocation'];
      if (!$Agents) Move_Thing_Within_Sys($T,$T['NewLocation'],1);
//      SKLog("Moved to $pname along " . $LinkLevels[$L['Level']]['Colour']. " link #$Lid to " . $EndLocs[$T['NewLocation']]);
      if ($Fid) {
        if (isset($SetBreak)) {
          TurnLog($Fid,$T['Name'] . " attempted to move from " . System_Name($OldN,$Fid) . " along <span style='color:" . $LinkLevels[abs($L['Level'])]['Colour'] .
                  ";'>link #$Lid </span>to $pname " . ($T['NewLocation'] > 2?( " to " . $EndLocs[$T['NewLocation']]): "") . " unfortunately the link was " .
                  "overloaded and exploded - see turn report" ,$T);
          $T['CurHealth'] = 0;
          $T['SystemId'] = 0;
        } else {
          TurnLog($Fid,$T['Name'] . " has moved from " . System_Name($OldN,$Fid) . " along <span style='color:" . $LinkLevels[abs($L['Level'])]['Colour'] .
                  ";'>link #$Lid </span>to $pname " .         ($T['NewLocation'] > 2?( " to " . $EndLocs[$T['NewLocation']]): ""),$T);
        }
      }
//    $T['LinkId'] = 0;
      if ($T['Instruction'] != 0 && !Has_Tech($Fid,'Stargate Construction') ) $T['Instruction'] = 0;
      $T['LastMoved'] = $GAME['Turn'];
      Put_Thing($T);
      if (isset($SetBreak)) return 0; // Will need to come back in to finish movements after damage
    } else if ( $T['WithinSysLoc'] != $T['NewLocation'] && $T['NewLocation']>1) {
      if (!$Agents) {
        Move_Thing_Within_Sys($T,1,1);
        Move_Thing_Within_Sys($T,$T['NewLocation'],1);
      } else {
        $T['WithinSysLoc'] = $T['NewLocation'];
      };
      $N = Get_System($T['SystemId']);
      $Sid = $T['SystemId'];
      $Fid = $T['Whose'];

      $pname = System_Name($N,$Fid);

      $EndLocs = Within_Sys_Locs($N);
//      SKLog("Moved to " . $EndLocs[$T['NewLocation']] . " within $pname");
      if ($Fid) TurnLog($Fid,$T['Name'] . " moved to " . $EndLocs[$T['NewLocation']] .  " within $pname",$T);
      $T['LastMoved'] = $GAME['Turn'];
      Put_Thing($T);
    }

  }
  return 1;
}

function AgentsMoveCheck() {
  ShipMoveCheck(1);
  return 1;
}

function AgentsMovements() {
  ShipMovements(1);
  return 1;
}


function SeeAfterMove() {
  global $db,$GAMEID,$GAME;
  // Save What can I see data
  $Factions = Get_Factions();
  foreach($Factions as $F) {
    $Fid = $F['id'];
    $CouldC = WhatCanBeSeenBy($Fid);
    $CB = fopen("Turns/$GAMEID/" . $GAME['Turn'] . "/CouldM$Fid.html", "w");
    fwrite($CB,$CouldC);
    fclose($CB);
    GMLog("Saved What could be seen for " . $F['Name']);
  }
  return 1;

}

function Meetups() {
  // For each system get all things.  If more than 1 faction, note location flag for that system
  // if (more than 1 has "control" then there is a bundle
  // if only 1 and has control and system controlled then there is a bundle
  // ships, agents ...
//  echo "Meetups is currently Manual<p>";
  $_REQUEST['TurnP'] = 1; // Makes Meetings think its part of turn processing
  include_once("Meetings.php");

  return 1;
}

function SpaceCombat() {
  GMLog("Space Combat is currently Manual<p>");
  return 1;
}


function UnloadTroops() {
  $Things = Get_Things_Cond(0,"(BuildState=2 OR BuildState=3) AND ( LinkId=-3 OR LinkId=-4)"); // -4 should have been converted on load to -3 (in theory)
  $TTypes = Get_ThingTypes();
  $Facts = Get_Factions();

  foreach($Things as $T) {
    $HostId = $T['SystemId'];
    $H = Get_Thing($HostId);

    $N = Get_System($H['SystemId']);
    $Syslocs = Within_Sys_Locs($N,0,0,0,1);

    $T['SystemId'] = $H['SystemId'];
    if (isset($Syslocs[$T['NewLocation']])) {
      $T['WithinSysLoc'] = $T['NewLocation'];
    } else {
      $T['WithinSysLoc'] = 1;
    }
    $T['LinkId'] = 0;

    TurnLog($T['Whose'], $T['Name'] . " has been unloaded from " . $H['Name'] . " in " . $N['Ref'] . " to " . $Syslocs[$T['WithinSysLoc']], $T);
    if ($T['Whose'] != $H['Whose']) TurnLog($H['Whose'], $T['Name'] . " has been unloded from " . $H['Name'] . " in " . $N['Ref'] . " to " . $Syslocs[$T['WithinSysLoc']]);
    GMLog($T['Name'] . " has been unloaded from " . $H['Name'] . " in " . $N['Ref'] . " to " . $Syslocs[$T['WithinSysLoc']]);
    Put_Thing($T);
  }

  GMLog("Unload Troops is complete<p>");
  return 1;
}

function OrbitalBombardment() {
  GMLog("Orbital Bombardment is currently Manual<p>");
  return 1;
}

function PlanetaryDefence() {
  GMLog("Planetary Defence is currently Manual<p>");
  return 1;
}

function GroundCombat() {
  GMLog("Ground Combat is currently Manual<p>");
  return 1;
}


function DevastationSelection() {
  GMLog("<h2>Please mark those worlds with combat on</h2>");

  $_REQUEST['CONFLICT'] = 1; // Makes WorldList think its part of turn processing - 1 for setting flags
  include_once("WorldList.php");
  return 1;

}

function Devastation() {
  $Worlds = Get_Worlds();
  foreach ($Worlds as $W) {
    $H = Get_ProjectHome($W['Home']);
    if ($W['Conflict']) {
      $H['EconomyFactor'] = 50;
      $H['Devastation']++;

      $Dists = Get_DistrictsH($H['id']);
      $Dcount = 0;
      foreach ($Dists as $D) $Dcount += $D['Number'];

      if ($H['Devastation'] > $Dcount) { // Lose districts...
        $LogT = Devastate($H,$W,$Dists,1);
        TurnLog($H['Whose'],$LogT);
        GMLog($LogT,1);
      }
      Put_ProjectHome($H);
    } else { // Recovery?
      if ($H['EconomyFactor'] != 100) {
        $H['EconomyFactor'] = 100;
        Put_ProjectHome($H);
      }
//      $Things = Get_Things_Cond(0,"CurHealth != OrigHealth AND SystemId=$Sys AND WithinSysLoc=$Loc)
    }
  }

// Look for Militia to recover

  GMLog("Devastation is Complete<p>");
  return 1;

}

function OwnershipChange() {
  GMLog("<h2>If worlds have changed hands after conflict, do that NOW</h2>");
  return 1;
}

function ProjectProgress() {
  ProjectProgressActions(0); // Progress, payment already made
  GMLog("All Projects Progressed<p>");
  return 1;
}

function OperationsProgress() {
  global $GAME,$GAMEID;

  $OpTypes = Get_OpTypes();
  $Operations = Gen_Get_Cond('Operations', "GameId=$GAMEID AND Status=1" );
  $Orgs = Gen_Get_Cond('Organisations',"GameId=$GAMEID");

  foreach ($Operations as $Oid=>$O) {
    $O['Progress'] += $Orgs[$O['OrgId']]['OfficeCount'];
    Put_Operation($O);
  }
  GMLog("All Operations Progressed<p>");
  return 1;
}

function InstructionsProgress() {
  global $ThingInstrs,$IntructProps;
  $Things = Get_Things_Cond(0,"Instruction!=0");
  $Facts = Get_Factions();

  foreach ($Things as $T) {
    $N = Get_System($T['SystemId']);
    $Tid = $T['id'];
    switch ($ThingInstrs[abs($T['Instruction'])]) {
      case 'Colonise':
        $Prog = $T['Dist2'];
        if ($Prog == 0) {
          GMLog("Colonisation by <a href=ThingEdit.php?id=$Tid>" . $T['Name'] . "Has zero progress - Tell Richard");
          FollowUp($T['Whose'],"Colonisation by <a href=ThingEdit.php?id=$Tid>" . $T['Name'] . "Has zero progress - Tell Richard");
        }
        $T['Progress'] = min($T['ActionsNeeded'],($T['Progress']+$Prog)); // Progress stored in Dist2
        Put_Thing($T);
        break;
      case 'Make Outpost':
      case 'Make Asteroid Mine':
      case 'Make Minefield':
      case 'Make Orbital Repair Yard':
      case 'Build Space Station':
      case 'Expand Space Station' :
      case 'Make Adbanced Deep Space Sensor':
      case 'Make Deep Space Sensor':
      case 'Make Advanced Asteroid Mine':
      case 'Dismantle Stargate':
      case 'Build Stargate':
      case 'Make Planet Mine':
      case 'Construct Command Relay Station':
      case 'Repair Command Node': // Not coded yet
      case 'Make Something':
      case 'Make Warpgate':
      case 'Link Repair':
//        $Prog = Has_Tech($T['Whose'],'Deep Space Construction');
        $Mods = Get_ModulesType($Tid, 'Deep Space Construction');
        $ProgGain = $Mods['Level']*$Mods['Number'];
        GMLog("$ProgGain progress on " . $ThingInstrs[abs($T['Instruction'])] . " for " . $Facts[$T['Whose']]['Name'] . ":" . $T['Name']);

        $T['Progress'] = min($T['ActionsNeeded'],$T['Progress']+$ProgGain);
        Put_Thing($T);
        break;

      case 'Collaborative DSC': // Dist1 has Thing number being helped
        break; // Now in second pass
//        $Prog = Has_Tech($T['Whose'],'Deep Space Construction');
        $Mods = Get_ModulesType($Tid, 'Deep Space Construction');
        $ProgGain = $Mods['Level']*$Mods['Number'];
        $HT = Get_Thing($T['Dist1']);
        if ($HT) {
          if ($HT['Instruction'] && ($IntructProps[abs($HT['Instruction'])] & 1)) {
            $HT['Progress'] = min($HT['ActionsNeeded'],$HT['Progress']+$ProgGain);
            GMLog("$ProgGain progress on " . $ThingInstrs[abs($HT['Instruction'])] . " for " . $Facts[$HT['Whose']]['Name'] . ":" . $HT['Name'] . " Now at " .
            $HT['Progress'] . " / " . $HT['ActionsNeeded']);

            TurnLog($HT['Whose'],$T['Name'] . " did $ProgGain towards completing " . $ThingInstrs[abs($HT['Instruction'])] . " by " . $HT['Name'] . " Now at " .
            $HT['Progress'] . " / " . $HT['ActionsNeeded']);
            if ($HT['Whose'] != $T['Whose']) {
              TurnLog($T['Whose'],$T['Name'] . " did $ProgGain towards completing " . $ThingInstrs[abs($HT['Instruction'])] . " by " . $HT['Name']);
            }
            Put_Thing($HT);
          }
          else {
            TurnLog($T['Whose'], $T['Name'] . " Was collacorating with " . $HT['Name'] . ", but " . $HT['Name'] . " is not doing any DSC.");
          }
        }
        break;

      case 'Analyse Anomaly':
        $Aid = $T['ProjectId'];
        $Fid = $T['Whose'];
        if ($Aid) {
          $A = Get_Anomaly($Aid);
          $FAs = Gen_Get_Cond('FactionAnomaly',"FactionId=$Fid AND AnomalyId=$Aid");
          if ($FAs) {
            $FA = $FAs[0];
            $Pro = $T['Sensors']*$T['SensorLevel'];
            $T['Progress'] = $FA['Progress'] = min($FA['Progress']+$Pro, $A['AnomalyLevel']);
            Gen_Put('FactionAnomaly',$FA);
            Put_Thing($T);
            TurnLog($Fid,$T['Name'] . " did $Pro towards completing anomaly " . $A['Name'] . " now at " . $FA['Progress'] . " / " . $A['AnomalyLevel'],$T);
          } else {
            TurnLog($Fid, $T['Name'] . " is supposed to be analysing an anomaly - but there isn't one selected",$T);
          }
        } else { // No anomaly is there one here?
          $Anoms = Gen_Get_Cond('Anomalies',"SystemId=" . $T['SystemId']);
          if ($Anoms) {
            foreach($Anoms as $A) {
              $Aid = $A['id'];
              $FAs = Gen_Get_Cond('FactionAnomaly',"AnomalyId=$Aid AND FactionId=$Fid");
              if (empty($FAs[0]['id'])) continue;
              $FA = $FAs[0];
              if ($FA['Progress'] < $A['AnomalyLevel']) {
                $Pro = $T['Sensors']*$T['SensorLevel'];
                $T['Progress'] = $FA['Progress'] = min($FA['Progress']+$Pro, $A['AnomalyLevel']);
                Gen_Put('FactionAnomaly',$FA);
                $T['ProjectId'] = $Aid;
                Put_Thing($T);
                TurnLog($Fid,$T['Name'] . " did $Pro towards completing anomaly " . $A['Name'] . " now at " . $FA['Progress'] . " / " . $A['AnomalyLevel'],$T);
              }
              break 2;
            }
          }
          TurnLog($Fid,$T['Name'] . " is supposed to be analysing an anomaly - but there isn't one",$T);
        }
        break;

      case 'Salvage':
        $Prog = Has_Tech($T['Whose'],'Salvage Rigs');
        GMLog("$Prog progress on " . $ThingInstrs[abs($T['Instruction'])] . " for " . $Facts[$T['Whose']]['Name'] . ":" . $T['Name']);
        $T['Progress'] = min($T['ActionsNeeded'],$T['Progress']+$Prog);
        Put_Thing($T);
        break;

      default:
        break;
     }
   }

  return 1;
}

function CollaborativeProgress() {
  global $ThingInstrs,$IntructProps;
  $Things = Get_Things_Cond(0,"Instruction!=0");
  $Facts = Get_Factions();

  foreach ($Things as $T) {
    $Tid = $T['id'];
    switch ($ThingInstrs[abs($T['Instruction'])]) {
      case 'Collaborative Space Construction' :
      case 'Collaborative DSC': // Dist1 has Thing number being helped
//        $Prog = Has_Tech($T['Whose'],'Deep Space Construction');
        $Mods = Get_ModulesType($Tid, 'Deep Space Construction');
        $ProgGain = $Mods['Level']*$Mods['Number'];
        $HT = Get_Thing($T['Dist1']);
        if ($HT) {
          if ($HT['Instruction'] && ($IntructProps[abs($HT['Instruction'])] & 1)) {
            $HT['Progress'] = min($HT['ActionsNeeded'],$HT['Progress']+$ProgGain);
            GMLog("$ProgGain progress on " . $ThingInstrs[abs($HT['Instruction'])] . " for " . $Facts[$HT['Whose']]['Name'] . ":" . $HT['Name'] . " Now at " .
            $HT['Progress'] . " / " . $HT['ActionsNeeded']);

            TurnLog($HT['Whose'],$T['Name'] . " did $ProgGain towards completing " . $ThingInstrs[abs($HT['Instruction'])] . " by " . $HT['Name'] . " Now at " .
            $HT['Progress'] . " / " . $HT['ActionsNeeded']);
            if ($HT['Whose'] != $T['Whose']) {
              TurnLog($T['Whose'],$T['Name'] . " did $ProgGain towards completing " . $ThingInstrs[abs($HT['Instruction'])] . " by " . $HT['Name']);
            }
            Put_Thing($HT);
          }
          else {
            TurnLog($T['Whose'], $T['Name'] . " Was collacorating with " . $HT['Name'] . ", but " . $HT['Name'] . " is not doing any DSC.");
          }
        }
        break;

      case 'Collaborative Planetary Construction' :
        $Mods = Get_ModulesType($Tid, 'Engineering Corps');
        $ProgGain = $Mods['Level']*$Mods['Number']; // For all except colonise
        $HT = Get_Thing($T['Dist1']);
        if ($HT) {
          if ($HT['Instruction'] && ($IntructProps[abs($HT['Instruction'])] & 1)) {
            if ($HT['Instruction'] == 1) { // Colonise
              $Fact = $Facts[$HT['Whose']];
              $Plan = $HT['Spare1'];
              $P = Get_Planet($Plan);
              if (($P['Type'] == $Fact['Biosphere']) || ($P['Type'] == $Fact['Biosphere2']) || ($P['Type'] == $Fact['Biosphere3'])) {
                $LMod = 0;
              } else if ($P['Type'] == 4 ) {
                $LMod = -2;
              } else {
                $LMod = -1;
              }
              $ProgGain = max(0,$Mods['Level']+$LMod)*$Mods['Number'];
            }
            $HT['Progress'] = min($HT['ActionsNeeded'],$HT['Progress']+$ProgGain);
            GMLog("$ProgGain progress on " . $ThingInstrs[abs($HT['Instruction'])] . " for " . $Facts[$HT['Whose']]['Name'] . ":" . $HT['Name'] . " Now at " .
              $HT['Progress'] . " / " . $HT['ActionsNeeded']);

            TurnLog($HT['Whose'],$T['Name'] . " did $ProgGain towards completing " . $ThingInstrs[abs($HT['Instruction'])] . " by " . $HT['Name'] . " Now at " .
              $HT['Progress'] . " / " . $HT['ActionsNeeded']);
            if ($HT['Whose'] != $T['Whose']) {
              TurnLog($T['Whose'],$T['Name'] . " did $ProgGain towards completing " . $ThingInstrs[abs($HT['Instruction'])] . " by " . $HT['Name']);
            }
            Put_Thing($HT);
          }
          else {
            TurnLog($T['Whose'], $T['Name'] . " Was collacorating with " . $HT['Name'] . ", but " . $HT['Name'] . " is not doing any DSC.");
          }
        }
        break;




      default:
        break;
     }
   }

  return 1;
}



function EspionageMissionsComplete() {
  GMLog("Espionage Missions Complete	is currently Manual<p>");
  return 1;
}

function CounterEspionage() {
  GMLog("Counter Espionage is currently Manual<p>");
  return 1;
}

function HandleCoOpProjects() {
  GMLog("Now handle any co-op projects or instructions adding progress where needed.<p> " .
        "This is needed for all activities cross faction and instructions that affect more than one thing.");

  return 1;
}

function FinishShakedowns() {
  // Move anything in shakedown to completed

  $Things = Get_Things_Cond(0," BuildState=2 ");

  foreach($Things as $T) {
    $T['BuildState'] = 3;
    TurnLog($T['Whose'],$T['Name'] . " has finished it's Shakedown and is now ready for operations.",$T);
    Put_Thing($T);
  }

  GMLog("Shakdowns finished<br>");
  return 1;
}


function ProjectsCompleted($Pass) {
//  echo "Projects Complete is currently Manual<p>";
// Pass 0 = Refits only, 1 = rest

  global $GAME,$GAMEID,$Currencies,$Facts,$ARMY;

  $ProjTypes = Get_ProjectTypes();
  $Projects = Get_Projects_Cond("Status=1 AND Progress>=ProgNeeded");
  $OTypes = Get_OrgTypes();
  foreach ($Projects as $P) {
    $PT = $ProjTypes[$P['Type']];

    if (($Pass==0) && (($PT['Props']&512) ==0) ||
        ($Pass==1) && (($PT['Props']&512) !=0)) continue;

    GMLog("Completing project " . $P['id'] . " " . $P['Name'] . "<br>");
    $P['Status'] = 2;
    $P['TurnEnd'] = $GAME['Turn'];
    Put_Project($P);

    $Fid = $P['FactionId'];


    switch ($PT['Name']) {

    case 'Construction':
    case 'Grow District':
      $H = Get_ProjectHome($P['Home']);
      if (!isset($H['ThingType'])) {
        GMLog("Project " . $P['id'] . " Does not have a valid home<br>");
        FollowUp($Fid, "Construction Project " . $P['id'] . " Does not have a valid home");
        break;
      }

      if ($P['ThingType']> 0){ // District
        switch ($H['ThingType']) {
          case 1: // Planet
            $PH = Get_Planet($H['ThingId']);
            $Dists = Get_DistrictsP($H['ThingId'],1);
            break;
          case 2: // Moon
            $PH = Get_Moon($H['ThingId']);
            $Dists = Get_DistrictsM($H['ThingId'],1);
            break;
          case 3: // Thing
            $PH = Get_Thing($H['ThingId']);
            $Dists = Get_DistrictsT($H['ThingId'],1);
            break;
          }
        if ($Dists) foreach($Dists as $D) {
          if ($D['Type'] == $P['ThingType']) {
            $D['Number']++;
            Put_District($D);
            TurnLog($Fid,'Project ' . $P['Name'] . " is complete");
            break 2;
          }
        }
        $D = ['HostType'=>$H['ThingType'], 'HostId'=>$PH['id'], 'Type'=>$P['ThingType'], 'Number'=>1, 'GameId' => $GAMEID];
        Put_District($D);
      } else { // Office
        $World = Gen_Get_Cond1('Worlds',"Home=" . $P['Home']);
        $Off = ['Type' => -$P['ThingType'], 'World'=>$World['id'], 'Whose'=>$P['Whose'], 'Number'=>1];
        Put_Office($Off);
      }
      TurnLog($P['FactionId'],'Project ' . $P['Name'] . " is complete");
      break;

    case 'Research Planetary Construction':
    case 'Research Core Technology':
    case 'Research Supplemental Technology':
    case 'Research Ship Construction':
    case 'Research Supplemental ship Tech':
    case ('Research ' . Feature('MilTech')):
    case "Research Supplemental $ARMY Tech":
    case 'Research Intelligence Operations':
    case 'Research Supplemental Intelligence Tech':
    case 'Research Supplemental Planetary Construction Tech':
      $Tid = $P['ThingType'];
      $CTech = Get_Faction_TechFT($Fid,$Tid);
      $Tech = Get_Tech($Tid);
      if (empty($Tech)) {
        GMLog($Facts[$Fid]['Name'] . " has completed Researching an unknown tech: $Tid",1);
      }
      if ($Tech['Cat'] == 0) { // Core
        if ($CTech['Level'] < $P['Level']) {
          $CTech['Level'] = $P['Level'];
          Put_Faction_Tech($CTech);
          TurnLog($Fid,'Project ' . $P['Name'] . " is complete");
          break;
        } else {
          GMLog( "Project to " . $P['Name'] . " already have level " . $CTech['Level'] . " See <a href=ProjEdit.php?id=" . $P['id'] . ">Project</a>", 1);
          TurnLog($Fid,'Project ' . $P['Name'] . " is complete");
          break;
        }
      } else if ($CTech['Level'] == 0) { // Supp
        $CTech['Level'] = 1;
        Put_Faction_Tech($CTech);
        TurnLog($Fid,'Project ' . $P['Name'] . " is complete");
        break;
      } else {
        GMLog( "Project to " . $P['Name'] . " already have". " See <a href=ProjEdit.php?id=" . $P['id'] . ">Project</a>", 1);
        TurnLog($Fid,'Project ' . $P['Name'] . " is complete");
      }
      break;

    case 'Re-equip and Reinforce':
    case 'Refit and Repair':
      $Where = Where_Is_Home($P['Home']);

      $Tid = $P['ThingId'];
      if ($Tid) {
        $T = Get_Thing($Tid);
        if (($T['SystemId'] != 0 && $T['SystemId'] != $Where[0])) {
          TurnLog($Fid,"Not performing " . $PT['Name'] . " to " . $T['Name'] . " as not in same system");
          GMLog($Facts[$Fid]['Name'] . "Not performing " . $PT['Name'] . " to " . $T['Name'] . " as not in same system",1);
        } else if ($P['ThingId']) {
          $T = Get_Thing($P['ThingId']);
          RefitRepair($T,1,0,$P['FactionId']);
          TurnLog($P['FactionId'], $T['Name'] . " has been " . $PT['Name'] . "ed",$T);
        }
      }
      if ($P['ThingId2']) {
        $Tid = $P['ThingId2'];
        if (($T['SystemId'] != 0 && $T['SystemId'] != $Where[0])) {
          TurnLog($Fid,"Not performing " . $PT['Name'] . " to " . $T['Name'] . " as not in same system");
          GMLog($Facts[$Fid]['Name'] . "Not performing " . $PT['Name'] . " to " . $T['Name'] . " as not in same system",1);
        } else {
          $T = Get_Thing($Tid);
          RefitRepair($T,1,0,$P['FactionId']);
          TurnLog($Fid, $T['Name'] . " has been " . $PT['Name'] . "ed",$T);
        }
      }
      break;

    case 'Construct Ship':
      $T = Get_Thing($P['ThingId']);
      $T['BuildState'] = (Feature('Shakedowns')?2:3); // Shakedown
      if (empty($T['SystemId'])) {
//        $Where = Where_Is_Home($P['Home']);
//        $T['SystemId'] = $Where[0];
//        if (empty($T['SystemId'])) {
          GMLog("Ship: <a href=ThingEdit.php?id=" . $P['ThingId'] . ">" . $T['Name'] . "</a> has no System Setup - Call Richard...");
          FollowUp($Fid,"Ship: <a href=ThingEdit.php?id=" . $P['ThingId'] . ">" . $T['Name'] . "</a> has no System Setup - Call Richard...");
//        }
      }

      $WSL = ConstructLoc($P['Home'],0);
      $T['WithinSysLoc'] = 1;
      Move_Thing_Within_Sys($T,$WSL,1);
      TurnLog($Fid, $T['Name'] . " has been launched" . (Feature('Shakedowns')?" and will now start its shakedown cruise":''),$T);
      Calc_Scanners($T);
      $T['ProjectId'] = 0;
      Put_Thing($T);
      break;

    case "Train $ARMY":
    case 'Train Agent':
      $T = Get_Thing($P['ThingId']);
      $T['BuildState'] = 3; // Complete
      $T['WithinSysLoc'] = ConstructLoc($P['Home'],1);
      TurnLog($Fid, $T['Name'] . " has been completed",$T);
      $T['ProjectId'] = 0;
      Put_Thing($T);
      break;

    case 'Share Technology':
      $FFact = Get_Faction($Fid);
      $Tech = Get_Tech($P['ThingType']);
      $Level = $P['Level'];
      $Xfr2 = $P['ThingId'];
      $XFact = Get_Faction($Xfr2);
      $Have = Has_Tech($Xfr2,$Tech['id']);
//      var_dump($Tech,$XFact);
      if ($Tech['Cat'] == 0) {
        if ($Have >= $Level) {
          TurnLog($Fid, "You tried to share " . $Tech['Name'] . " at level $Level. with " . $XFact['Name'] . " They already know it.");
          TurnLog($Xfr2, $FFact['Name'] . " tried to share " . $Tech['Name'] . " at level $Level.  With you - you already have it at level $Have.");
        } else if ($Have == $Level-1) {
          $CTech = Get_Faction_TechFT($Xfr2 ,$Tech['id']);
          $CTech['Level'] = $Level;
          Put_Faction_Tech($CTech);
          TurnLog($Fid, "Your have shared " . $Tech['Name'] . " at level $Level.  with " . $XFact['Name']);
          TurnLog($Xfr2, $FFact['Name'] . " has shared " . $Tech['Name'] . " at level $Level.  With you.");
        } else if (0 ) { // Learn lower level option
          $CTech = Get_Faction_TechFT($Xfr2 ,$Tech['id']);
          $CTech['Level'] = $Have+1;
          Put_Faction_Tech($CTech);
          TurnLog($Fid, "You tried to share " . $Tech['Name'] . " at level $Level. with " . $XFact['Name'] .
             " They only had it at level $Have.  They learnt level " . ($Have+1));
          TurnLog($Xfr2, $FFact['Name'] .  " tried to share " . $Tech['Name'] . " at level $Level with you. You only have it at level $Have  so learnt it at level " .
                  ($Have+1));
        } else {
          TurnLog($Fid, "You tried to share " . $Tech['Name'] . " at level $Level. with " . $XFact['Name'] .
             " They only had it at level $Have - they don't understand what you sent");
          TurnLog($Xfr2, $FFact['Name'] .  " tried to share " . $Tech['Name'] . " at level $Level with you. You only have it at level $Have so learnt nothing");
        }
      } else { // Supp techs
        $PRHave = Has_Tech($Xfr2,$Tech['PreReqTech']);
        if ($Have) {
          TurnLog($Fid, "You tried to share " . $Tech['Name'] . " with " . $XFact['Name'] . " They already know it.");
          TurnLog($Xfr2, $FFact['Name'] . " tried to share " . $Tech['Name'] . " with you - you already have it.");
        } else if ($PRHave >= $Tech['PreReqLevel']) {
          $CTech = Get_Faction_TechFT($Xfr2 ,$Tech['id']);
          $CTech['Level'] = $Level;
          Put_Faction_Tech($CTech);
          TurnLog($Fid, "Your have shared " . $Tech['Name'] . " with " . $XFact['Name']);
          TurnLog($Xfr2, $FFact['Name'] . " has shared " . $Tech['Name'] . " with you.");
        } else {
          TurnLog($Fid, "You tried to share " . $Tech['Name'] . " with " . $XFact['Name'] . " They don't understand what you sent");
          TurnLog($Xfr2, $FFact['Name'] .  " tried to share " . $Tech['Name'] . " with you. You don't understand it");
        }
      }
      break;

    case 'Analyse':
      $Fact = Get_Faction($Fid);
      TurnLog($Fid, "You have conpleted " . $P['Name'] . " look at your turn response from the GM to see what you learnt");
      GMLog($Fact['Name'] . " has completed a level " . $P['Level'] . " analyse project called " . $P['Name'] . ".  Please give the results in the player", 1);
      FollowUp($Fid,$Fact['Name'] . " has completed a level " . $P['Level'] . " analyse project called " . $P['Name'] . ".  Please give the results in the player");
      break;

    case 'Construct Warp Gate':
      $H = Get_ProjectHome($P['Home']);
      switch ($H['ThingType']) {
        case 1: // Planet
          $PH = Get_Planet($H['ThingId']);
          break;
        case 2: // Moon
          $PH = Get_Moon($H['ThingId']);
          break;
        case 3: // Thing
          $PH = Get_Thing($H['ThingId']);
          break;
        }
      $NT = ['GameId'=>$GAME['id'], 'Type'=> 15, 'Level'=> 1, 'SystemId'=>$H['SystemId'], 'WithinSysLoc' => $H['WithinSysLoc'], 'Whose'=>$P['FactionId'],
              'BuildState'=>3, 'TurnBuilt'=>$GAME['Turn'], 'Name'=>($PH['Name'] . " warp gate" )];
      Put_Thing($NT);
      TurnLog($Fid,"A warp gate has been made for " . $PH['Name']);

      break;

    case 'Rebuild and Repair':
      $H = Get_ProjectHome($P['Home']);
      switch ($H['ThingType']) {
        case 1: // Planet
          $PH = Get_Planet($H['ThingId']);
          break;
        case 2: // Moon
          $PH = Get_Moon($H['ThingId']);
          break;
        case 3: // Thing
          $PH = Get_Thing($H['ThingId']);
          break;
        }

      $H['Devastation'] = max(0,$H['Devastation']-2);
      Put_ProjectHome($H);
      TurnLog($Fid,"The has been Rebuilding and Repair on " . $PH['Name']);
      break;

    case 'Decipher Alien Language':
    case 'Grow Modules' :
      GMLog("A project to " . $PT['Name'] . " has completed (" . $P['Name'] . "), this is not automated yet.  See <a href=ProjEdit.php?id=" . $P['id'] . ">Project</a>",1);
      FollowUp($Fid,"A project to " . $PT['Name'] . " has completed, this is not automated yet.");
      break;

    case 'Produce Adianite':
      $LinkRes = GameFeature('LinkResource',0);
      if ($LinkRes) {
        AddCurrencies();
        $Cur = 0;
        foreach ($Currencies as $Ci => $C) if ( $C == $LinkRes) $Cur = $Ci;

        $Ad = Proj_Costs($P['Level'])[0];
        Gain_Currency($Fid,$Cur,$Ad,'Producing Adianite');

        $H = Get_ProjectHome($P['Home']);
        switch ($H['ThingType']) {
          case 1: // Planet
            $PH = Get_Planet($H['ThingId']);
            break;
          case 2: // Moon
            $PH = Get_Moon($H['ThingId']);
            break;
          case 3: // Thing
            $PH = Get_Thing($H['ThingId']);
            break;
        }
        TurnLog($Fid,"Produced $Ad $LinkRes on " . $PH['Name']);
      }
    break;


    // These all now handled as instructions - not projects at the moment
    case 'Decommision':
    case 'Disband':
    case 'Build Outpost':
    case 'Build Asteroid Mining Facility':
    case 'Build Minefield':
    case 'Build Orbital Shipyard':
    case 'Build Space Station':
    case 'Extend Space Station':
    case 'Deep Space Sensors':
    case 'Advanced Deep Space Sensors':
    case 'Build Advanced Asteroid Mining Facility':
    case 'Unknown' :
    case 'Seek Enemy Agents' :

    default:
      GMLog("A project to " . $PT['Name'] . " at level " . $P['Level'] .
            " has completed, this is not automated yet.  See <a href=ProjEdit.php?id=" . $P['id'] . ">Project</a>",1);
      FollowUp($Fid,"A project to " . $PT['Name'] . " at level " . $P['Level'] .
            " has completed, this is not automated yet.  See <a href=ProjEdit.php?id=" . $P['id'] . ">Project</a>");
    }
  }

  return 1;
}

function RefitProjectsComplete() {
//  echo "Projects Complete is currently Manual<p>";
  return ProjectsCompleted(0);
}

function ProjectsComplete() {
//  echo "Projects Complete is currently Manual<p>";
  return ProjectsCompleted(1);
}

function OperationsComplete() {
  global $GAME,$GAMEID;

  $Facts = Get_Factions();
  $OpTypes = Get_OpTypes();
  $Operations = Gen_Get_Cond('Operations', "GameId=$GAMEID AND Status=1 AND Progress>=ProgNeeded");
  $OrgTypes = Get_OrgTypes();
  $TTYpes = Get_ThingTypes();
  $TTNames = NamesList($TTYpes);
  $Orgs = Gen_Get_Cond('Organisations',"GameId=$GAMEID");

  foreach ($Operations as $Oid=>$O) {
    $Fid = $O['Whose'];
    $Otp = $OpTypes[$O['Type']]['Props'];
    $Wh = $O['SystemId'];
    $Sys = Get_System($Wh);
    $TWho = $Sys['Control'];

    switch ($O['Type']) {
    default:
      GMLog("Operation " . $O['Name'] . " has completed, this is not automated yet.  See <a href=OperEdit.php?id=$Oid>Operation</a>",1);
      FollowUp($Fid,"Operation " . $O['Name'] . " has completed, this is not automated yet.  See <a href=OperEdit.php?id=$Oid>Operation</a>");
    }
  }
}

function InstructionsComplete() {
  global $ThingInstrs,$GAME,$ValidMines;
  $Things = Get_Things_Cond(0,"Instruction!=0 AND Progress>=ActionsNeeded ");
  $NeedColStage2 = 0;
  $Facts = Get_Factions();
  $TTNames = Thing_Types_From_Names();
  $TTypes = Get_ThingTypes();
  $Parsedown = new Parsedown();
  $Systems = [];
  $DTypes = Get_DistrictTypes();
  $DTypeNames = NameList($DTypes);


  foreach ($Things as $T) {
    $N = Get_System($T['SystemId']);
    $Tid = $T['id'];
    $Instr = $ThingInstrs[abs($T['Instruction'])];
    $Who = $T['Whose'];

    switch ($Instr) {
    case 'Colonise':
      $P = Get_Planet($T['Spare1']);
      if ($N['Control'] != $Who) {
        if ($N['Control'] == 0) {
          $N['Control'] = $Who;
          Put_System($N);
        }
      }
      $P['Control'] = $Who;
      Put_Planet($P);

      $D = ['HostType' =>1, 'HostId'=> $P['id'], 'Type'=> $T['Dist1'], 'Number'=>1, 'GameId'=>$GAME['id'], 'TurnStart'=>$GAME['Turn']];
//      if ($D['Type'] == 0) $D['Type'] = 1;
      $D['Type'] = array_flip($DTypeNames)['industrial'];
      Put_District($D);
      /*
      if (Get_ModulesType($Tid,'Self Repairing Robot Armour') && $T['Dist2']) {
        $D1 = ['HostType' =>1, 'HostId'=> $P['id'], 'Type'=> $T['Dist2'], 'Number'=>1, 'GameId'=>$GAME['id'], 'TurnStart'=>$GAME['Turn']];
        Put_District($D1);
      }*/


      TurnLog($Who,$P['Name'] . " on " . $N['Ref'] . " has been colonised");
      GMLog($P['Name'] . " on " . $N['Ref'] . " has been colonised by " . $Facts[$Who]['Name'],1);  // TODO Check for any named chars and offload
      Report_Others($T['Whose'], $T['SystemId'],31,$P['Name'] . " on " . $N['Ref'] . " has been colonised by " . $N['Ref']);

      $Have = Get_Things_Cond(0," (LinkId<0 AND SystemId=$Tid) ");
      if ($Have) {
        $Loc = Within_Sys_Locs($N,$T['Spare1']);
        foreach ($Have as $H) {
          $H['SystemId'] = $T['SystemId'];
          $H['WithinSysLoc'] = $Loc;
          TurnLog($Who,$H['Name'] . " has been offloaded on to " . $P['Name'] . " in " . $N['Ref'],$H);
          if ($H['Whose'] != $Who) TurnLog($H['Whose'],$H['Name'] . " has been offloaded on to " . $P['Name'] . " in " . $N['Ref'],$H);
          Put_Thing($H);
        }
      }
      $T['BuildState'] = -1;
      break; // The making homes and worlds in a later stage completes the colonisation I hope

    case 'Analyse Anomaly':
       $Aid = $T['ProjectId'];
       if ($Aid) {
         $A = Get_Anomaly($Aid);
         $Fid = $T['Whose'];
         $FAs = Gen_Get_Cond('FactionAnomaly',"FactionId=$Fid AND AnomalyId=$Aid");
         if ($FAs) {
           $FA = $FAs[0];
           if ($FA['Progress'] >= $A['AnomalyLevel'] && $FA['State'] != 3) {
             $FA['State'] = 3;
             Gen_Put('FactionAnomaly',$FA);
             TurnLog($Fid , $T['Name'] . " Anomaly study on " . $A['Name'] .
               " has been completed - See sperate response from the GMs for what you get");
             if (!empty($A['Completion'])) {
               TurnLog($Fid ,"Completion Text: " . $Parsedown -> text($A['Completion']) );
               $ctxt = '';
             } else {
               $ctxt = "  AND the completion text.";
             }
             GMLog($Facts[$Fid]['Name'] . " has completed anomaly study : <a href=AnomalyEdit.php?id=$Aid>" . $A['Name'] .
                   "</a> has been completed - give them the reward.  $ctxt");
             FollowUp($Fid,$Facts[$Fid]['Name'] . " has completed anomaly study : <a href=AnomalyEdit.php?id=$Aid>" . $A['Name'] .
                   "</a> has been completed - give them the reward. $ctxt");
             $T['ProjectId'] = 0;

             if ($A['Complete'] == 1) {
               $A['Complete'] = 2;
               Put_Anomaly($A);
             }

             for ($i=1; $i<=4; $i++) {
               if (!empty($A["ChainedOn$i"])) {
                 if (empty($Systems)) $Systems = Get_SystemRefs();

                 $Xid = $A["ChainedOn$i"];
                 $FA = Gen_Get_Cond1('FactionAnomaly',"FactionId=$Fid AND AnomalyId=$Xid");
                 if (!$FA) {
                   $FA = ['FactionId'=>$Fid, 'AnomalyId'=>$Xid, 'State' =>2,  'Notes'=>''];
                 } else {
                   if ($FA['State'] >=2) continue;
                   $FA['State'] = 2;
                 }
                 Gen_Put('FactionAnomaly',$FA);
                 $XA = Get_Anomaly($Xid);

                 TurnLog($Fid , "Completing " . $A['Name'] . " has opened up another anomaly that could be studied: " . $XA['Name'] .
                   " in " . $Systems[$XA['SystemId']] . "\n" .  $Parsedown->text($XA['Description']) .
                   "\nIt will take " . $XA['AnomalyLevel'] . " scan level actions to complete.\n\n");
                 GMLog($Facts[$Fid]['Name'], "Have been told about anomaly " . $XA['Name']);
               }
             }
           }
         } else {
           continue 2; // elsewhere
         }
       }
       break;

     case 'Make Outpost':
       $NT = ['GameId'=>$GAME['id'], 'Type'=> $TTNames['Outpost'], 'Level'=> 1, 'SystemId'=>$T['SystemId'], 'Whose'=>$T['Whose'],
              'BuildState'=>3, 'TurnBuilt'=>$GAME['Turn'], 'Name'=>$T['MakeName']];
       Put_Thing($NT);
       $N = Get_System($T['SystemId']);
       if ($N['Control'] != $Who) {
         $N['Control'] = $Who;
         Put_System($N);
       }
       TurnLog($Who,"An outpost has been made in " . $N['Ref']);
       GMLog($N['Ref'] . " is now controlled by " . $Facts[$Who]['Name'],1);
       Report_Others($T['Whose'], $T['SystemId'],2,"An outpost has been made in " . $N['Ref']);
       break;

     case 'Make Advanced Asteroid Mine':
       if (($NT = Get_Things_Cond(0,"Type=" . $TTNames['Asteroid Mine'] . " AND SystemId=" . $N['id'] . " AND BuildState=3 "))) {
         $NT = array_shift($NT);
         $NT['Level'] = 2;  // Generalise how?
         Put_Thing($NT);
         $N = Get_System($T['SystemId']);
         TurnLog($Who,"An Asteroid Mine has been upgraded in " . $N['Ref']);
         break;
       }
         // else Drop through
     case 'Make Asteroid Mine':
       $Asts = [];
       $Ps = Get_Planets($N['id']);
       foreach ($Ps as $P) if ($P['Type'] == 3) $Asts[$P['id']]= $P;
       if (empty($Asts)) {
         TurnLog($Who,"Can't find an asteroid belt to mine in " . $N['Ref']);
         GMLog($Facts[$Who]['Name'] . " Can't find an asteroid belt to mine in " . $N['Ref']);
         break;
       }
       $Exist = Get_Things_Cond(0,"Type=" . $TTNames['Asteroid Mine'] . " AND SystemId=" . $N['id'] . " AND BuildState=3");
       if ($Exist || count($Asts) <= count($Exist))  {
         TurnLog($Who,"The asteroid " . Plural($Asts,'','belt','belts') . " in " . $N['Ref'] . " are already mined");
         GMLog($Facts[$Who]['Name'] . " The asteroid " . Plural($Asts,'','belt','belts') . " in " . $N['Ref'] . " are already mined");
         break;
       }
       if ($Exist) foreach ($Exist as $E) unset($Asts[$E['Dist1']]);
       $Best = $Mine = 0;
       foreach ($Asts as $A) if ($A['Minerals'] > $Best) {
         $Best = $A['Minerals'];
         $Mine = $A['id'];
       }
       if ($Best == 0) {
         TurnLog($Who,"The asteroid belt in " . $N['Ref'] . " has no minerals");
         GMLog($Facts[$Who]['Name'] . " The asteroid belt in " . $N['Ref'] . " has no minerals");
         break;
       }
       $NT = ['GameId'=>$GAME['id'], 'Type'=> $TTNames['Asteroid Mine'], 'Level'=> (($Instr == 'Make Asteroid Mine')?1:2),
         'SystemId'=>$T['SystemId'], 'Whose'=>$T['Whose'],
         'BuildState'=>3, 'TurnBuilt'=>$GAME['Turn'],
         'Name'=>$T['MakeName'],'Dist1'=>$Mine];


       Put_Thing($NT);
       $N = Get_System($T['SystemId']);
       TurnLog($Who,"An Asteroid Mine has been made in " . $N['Ref']);
       Report_Others($T['Whose'], $T['SystemId'],2,"An Asteroid Mine has been made in " . $N['Ref']);
      break;

     case 'Make Advanced Minefield':
     case 'Make Minefield':
       $N = Get_System($T['SystemId']);
       $Loc = Within_Sys_Locs($N);
       $WLoc = $T['WithinSysLoc'];
       $LocText = $Loc[$WLoc];
       $LocType = intdiv($WLoc,100);
       if ($ValidMines[$LocType] == 1 ) {
         $NT = ['GameId'=>$GAME['id'], 'Type'=> $TTNames['Minefield'], 'Level'=> (($Instr == 'Make Minefield') ?1:2),
                'SystemId'=>$T['SystemId'], 'WithinSysLoc'=> $WLoc, 'Whose'=>$T['Whose'],
                'BuildState'=>3, 'TurnBuilt'=>$GAME['Turn'], 'Name'=>$T['MakeName']];
         Put_Thing($NT);
         TurnLog($Who,"A Minefield has been made in " . $N['Ref'] . " " . $LocText);
         Report_Others($T['Whose'], $T['SystemId'],2,"A Minefield has been made in " . $N['Ref'] . " " . $LocText);

         switch ($LocType) {
         case 1: //Orbiting Planet
           $Ps = Get_Planets($N['id']);
           $Pi = 1;
           foreach($Ps as $P) if ( (100+($Pi++)) == $WLoc ) break;

           $P['Mined'] = $NT['id'];
           Put_Planet($P);
           break;

         case 3: //Orbiting Moon
           $Ps = Get_Planets($N['id']);
           $Pi = $Mi = 1;
           foreach($Ps as $P) {
             $Ms = Get_Moons($P['PlanetId']);
             if ($Ms) {
               foreach($Ms as $M) if ((300+($Mi++)) == $WLoc ) break 2;
             }
           }
           if (isset($Ms) && isset($M)) {
             $M['Mined'] = $NT['id'];
             Put_Moon($P);
           }
           break;

         case 5: //At Link
           $LKs = Get_Links($N['Ref']);
           $li = 1;
           foreach($LKs as $lk) if ( (500+($li++)) == $WLoc ) break;

           if ($lk['System1Ref'] == $N['Ref']) {
             $lk['MinedA'] = $NT['id'];
           } else {
             $lk['MinedB'] = $NT['id'];
           }
           Put_Link($lk);
           break;

         default:
           // Should not get here (yet)
         }


       } else {
         TurnLog($Who,"A Minefield could not be made in " . $N['Ref'] . " " . $LocText);
       }
       break;

     case 'Make Orbital Repair Yard':
       $NT = ['GameId'=>$GAME['id'], 'Type'=>  $TTNames['Orbital Repair Yards'], 'Level'=> 1, 'SystemId'=>$T['SystemId'], 'WithinSysLoc'=> $T['WithinSysLoc'],
              'Whose'=>$T['Whose'], 'BuildState'=>3, 'TurnBuilt'=>$GAME['Turn'], 'Name'=>$T['MakeName']];
       Put_Thing($NT);
       $N = Get_System($T['SystemId']);
       TurnLog($Who,"An Orbital Repair Yard has been made in " . $N['Ref']);
       Report_Others($T['Whose'], $T['SystemId'],2,"An Orbital Repair Yard has been made in " . $N['Ref']);
       break;

     case 'Build Space Station':
       $Fid = $T['Whose'];
       $NT = ['GameId'=>$GAME['id'], 'Type'=> $TTNames['Space Station'], 'Level'=> 1, 'SystemId'=>$T['SystemId'], 'WithinSysLoc'=> $T['WithinSysLoc'],
              'Whose'=>$T['Whose'], 'BuildState'=>3, 'TurnBuilt'=>$GAME['Turn'], 'MaxDistricts'=>$T['Dist1'], 'Name'=>$T['MakeName']];
       $Sid = Put_Thing($NT);
       $D = ['HostType' =>3, 'HostId'=> $Sid, 'Type'=> $T['Dist2'], 'Number'=>1, 'GameId'=>$GAME['id'], 'TurnStart'=>$GAME['Turn']];
       Put_District($D);

       $N = Get_System($T['SystemId']);
       TurnLog($Who,"A Space Station has been made in " . $N['Ref']);
       GMLog("A space station has been made in " . $N['Ref'] . " by " . $Facts[$Who]['Name'],1);
       Report_Others($T['Whose'], $T['SystemId'],2,"A Space Station has been made in " . $N['Ref']);
       break;

     case 'Expand Space Station' :
       $Fid = $T['Whose'];
       $SS = Get_Things_Cond($Fid,"Type=" . $TTNames['Space Station'] . " AND SystemId=" . $T['SystemId'] . " AND BuildState=3");
       if (empty($SS)) {
         TurnLog($Fid,"There is not a Space Station here to expand in " . $N['Ref']);
         GMLog("There is not a space station to extend in " . $N['Ref'] . " by " . $Facts[$Fid]['Name'],1);
       } else {
         $S = $SS[0];
         $S['MaxDistricts'] += $T['Dist1'];
         Put_Thing($S);
       }
       break;

     case 'Make Deep Space Sensor':
       $NT = ['GameId'=>$GAME['id'], 'Type'=> $TTNames['Deep Space Sensor'], 'Level'=> 1, 'SystemId'=>$T['SystemId'], 'WithinSysLoc'=> $T['WithinSysLoc'],
              'Whose'=>$T['Whose'], 'BuildState'=>3, 'TurnBuilt'=>$GAME['Turn'], 'Name'=>$T['MakeName']];
       Put_Thing($NT);
       $N = Get_System($T['SystemId']);
       TurnLog($Who,"A Deep Space Sensor has been made in " . $N['Ref']);
       Report_Others($T['Whose'], $T['SystemId'],2,"A Deep Space Sensor has been made in " . $N['Ref']);
       break;

     case 'Make Advanced Deep Space Sensor':
       $NT = ['GameId'=>$GAME['id'], 'Type'=> $TTNames['Deep Space Sensor'], 'Level'=> 2, 'SystemId'=>$T['SystemId'], 'WithinSysLoc'=> $T['WithinSysLoc'],
              'Whose'=>$T['Whose'], 'BuildState'=>3, 'TurnBuilt'=>$GAME['Turn'], 'Name'=>$T['MakeName'], 'Level'=>2, 'NebSensors'=>2];
       Put_Thing($NT);
       $N = Get_System($T['SystemId']);
       TurnLog($Who,"An Advanced Deep Space Sensor has been made in " . $N['Ref']);
       Report_Others($T['Whose'], $T['SystemId'],2,"A Deep Space Sensor has been made in " . $N['Ref']);
       break;


     case 'Build Stargate':
       if (empty($Systems)) $Systems = Get_SystemRefs();
       $LinkLevels = Get_LinkLevels();
       $LL = $LinkLevels[$T['Dist1']];
       $NewLink = ['GameId'=>$GAME['id'], 'System1Ref'=>$Systems[$T['SystemId']], 'System2Ref'=> $Systems[$T['Dist2']], 'Level'=>$T['Dist1']];
       $Lid = Put_Link($NewLink);
       TurnLog($T['Whose'], "<span style=color:" . $LL['Colour'] . ">Link#$Lid </span>has been created between " . $Systems[$T['SystemId']] . " and " .
               $Systems[$T['Dist2']]);
       GMLog("A new " . $LL['Colour'] . " level " . $T['Dist1'] . " link #$Lid </span> has been made between " . $Systems[$T['SystemId']] . " and " .
              $Systems[$T['Dist2']]);
       $FL = ['LinkId'=>$Lid, 'FactionId'=>$T['Whose'],'Known'=>1];
       Put_FactionLink($FL);
       Report_Others($T['Whose'], $T['SystemId'],2,"A new " . $LL['Colour'] . " level " . $T['Dist1'] . " link #$Lid </span> has been made in " . $N['Ref']);
       Report_Others($T['Whose'], $T['Dist2'],2,"A new " . $LL['Colour'] . " level " . $T['Dist1'] . " link #$Lid </span> has been made in " . $Systems[$T['Dist2']]);
       break;

     case 'Make Planet Mine':
       $NT = ['GameId'=>$GAME['id'], 'Type'=> $TTNames['Planet Mine'], 'Level'=> 1, 'SystemId'=>$T['SystemId'],
              'Whose'=>$T['Whose'], 'BuildState'=>3, 'TurnBuilt'=>$GAME['Turn'], 'Name'=>$T['MakeName']]; // TODO add withinsysloc
       Put_Thing($NT);
       $N = Get_System($T['SystemId']);
       TurnLog($T['Whose'],"A Planet Mine has been made in " . $N['Ref']);
       GMLog("A Planet Mine has been setup in " . $N['Ref'] . " by " . $Facts[$T['Whose']]['Name'] . ".  Tell Richard to set the right withinsysloc",1);
       FollowUp($T['Whose'],"A Planet Mine has been setup in " . $N['Ref'] . " by " . $Facts[$T['Whose']]['Name'] . ".  Tell Richard to set the right withinsysloc");
       break;

     case 'Construct Command Relay Station':
       $NT = ['GameId'=>$GAME['id'], 'Type'=> $TTNames['Command Relay Station'], 'Level'=> 1, 'SystemId'=>$T['SystemId'], 'WithinSysLoc'=> $T['WithinSysLoc'],
              'Whose'=>$T['Whose'], 'BuildState'=>3, 'TurnBuilt'=>$GAME['Turn'], 'Name'=>$T['MakeName']];
       Put_Thing($NT);
       $N = Get_System($T['SystemId']);
       TurnLog($Who,"A Command Relay Station has been made in " . $N['Ref']);
       Report_Others($T['Whose'], $T['SystemId'],2,"A Command Relay Station has been made in " . $N['Ref']);
       break;

     case 'Repair Command Node': // Not coded yet
       $NT = ['GameId'=>$GAME['id'], 'Type'=> $TTNames['Beta Node'], 'Level'=> 1, 'SystemId'=>$T['SystemId'], 'WithinSysLoc'=> $T['WithinSysLoc'],
              'Whose'=>$T['Whose'], 'BuildState'=>3, 'TurnBuilt'=>$GAME['Turn'], 'Name'=>$T['MakeName']];
       Put_Thing($NT);

       $D = ['HostType' =>3, 'HostId'=> $NT['id'], 'Type'=> $T['Dist1'], 'Number'=>1, 'GameId'=>$GAME['id'], 'TurnStart'=>$GAME['Turn']];
       if ($D['Type'] == 0) $D['Type'] = 1;
       Put_District($D);

       TurnLog($Who,"A Command Node has been repaired " . $N['Ref']);
       GMLog("A Command Node has been repaired in " . $N['Ref'] . " by " . $Facts[$Who]['Name'] . ".  Tell Richard to set the right stuff up",1);
       FollowUp($Who,"A Command Node has been repaired in " . $N['Ref'] . " by " . $Facts[$Who]['Name'] . ".  Tell Richard to set the right stuff up");
       break;

     case 'Build Planetary Mine':
       $P = Get_Planet($T['Spare1']);

       $D = ['HostType' =>1, 'HostId'=> $P['id'], 'Type'=> 9, 'Number'=>1, 'GameId'=>$GAME['id'], 'TurnStart'=>$GAME['Turn']];
       if ($D['Type'] == 0) $D['Type'] = 1;
       Put_District($D);

       $T['BuildState'] = 4;
       TurnLog($Who,$P['Name'] . " on " . $N['Ref'] . " has plantary mining established");
       GMLog($P['Name'] . " on " . $N['Ref'] . " has plantary mining by " . $Facts[$Who]['Name'],1);
       break; // The making homes and worlds in a later stage completes the colonisation I hope

     case 'Dismantle Stargate' :
       if (empty($Systems)) $Systems = Get_SystemRefs();
       global $Currencies;
       AddCurrencies();
       $LLs = Get_LinkLevels();
       $Lid = $T['Dist1'];
       $SaveL = $L = Get_Link($Lid);
       $Lvl =$L['Level'];
       $L['System1Ref'] = $L['System2Ref'] = '';
       Put_Link($L);
       $Adianite = $LLs[$Lvl]['MakeCost'];
       $Recovery = $Adianite * ((Has_Trait($T['Whose'],'Grow Modules'))?0.9:0.5);

       $AdianName = GameFeature('LinkResource');
       $AdianNumber = -1;
       foreach($Currencies as $CN=>$C) if ($C == $AdianName) { $AdianNumber=$CN; break;}
       if ($AdianNumber < 0) {
         GMLog("Dismantling Stargate: $Lid Could not find currency name");
         break;
       }
       Gain_Currency($T['Whose'],$AdianNumber,$Recovery,"Dismantling Link #$Lid");
       GMLog("Link $Lid has been dismantled by " . $Facts[$T['Whose']]['Name'] . " recovered $Recovery $AdianName ");
       TurnLog($Who,"Link $Lid has been dismantled you recovered $Recovery $AdianName ");
       Report_Others($T['Whose'], $Systems[$SaveL['System1Ref']]['id'],2,"Link $Lid has been dismantled in " . $SaveL['System1Ref']);
       Report_Others($T['Whose'], $Systems[$SaveL['System2Ref']]['id'],2,"Link $Lid has been dismantled in " . $SaveL['System2Ref']);
       break;

     case 'Make Something':
       $Name = $Facts[$Who]['Name'];
       GMLog("$Name - <a href=ThingEdit.php?id=$Tid>" . $T['Name'] . "</a> has done a DSC make something of '" . $T['MakeName'] . "' in " . $N['Ref'] .
             " it took " . $T['Progress'] . " actions this needs to be handled by the GMs.",1);
       FollowUp($Who,"<a href=ThingEdit.php?id=$Tid>" . $T['Name'] . "</a> Has done a DSC make something of '" . $T['MakeName'] . "' in " . $N['Ref'] .
             " it took " . $T['Progress'] . " actions this needs to be handled by the GMs.");
       break;

     case 'Make Warpgate':
       $NT = ['GameId'=>$GAME['id'], 'Type'=> 15, 'Level'=> 1, 'SystemId'=>$T['SystemId'], 'WithinSysLoc' => $T['WithinSysLoc'], 'Whose'=>$Who,
              'BuildState'=>3, 'TurnBuilt'=>$GAME['Turn'], 'Name'=>($T['MakeName'] . " warp gate" )];
       Put_Thing($NT);
       TurnLog($Who,"A warp gate has been made in " . $N['Ref']);
       Report_Others($T['Whose'], $T['SystemId'],2,"A warp gate has been made in " . $N['Ref']);
       break;

     case 'Clear Minefield':
       $Mines = Get_Things_Cond(0,"Type=" . $TTNames['Minefield'] . " AND SystemId=" . $N['id'] . " AND BuildState=3 AND WithinSysLoc=" . $T['WithinSysLoc']);
       $N = Get_System($T['SystemId']);
       $Loc = Within_Sys_Locs($N);
       $WLoc = $T['WithinSysLoc'];
       $LocText = $Loc[$WLoc];
       $LocType = intdiv($WLoc,100);

       if ($Mines) {
         foreach($Mines as $Mine) {
           $Mid = $Mine['id'];
           db_delete('Things',$Mid);

           switch ($LocType) {
           case 1: // Planet
             $P = Gen_Get_Cond1('Planets'," Mined=$Mid ");
             if ($P) {
               if ($P['Mined'] == $Mid) $P['Mined'] = 0;
               Put_Planet($P);
             }
             break;

           case 3: // Moon
             $M = Gen_Get_Cond1('Moons'," Mined=$Mid ");
             if ($M) {
               if ($M['Mined'] == $Mid) $M['Mined'] = 0;
               Put_Moon($M);
             }
             break;

           case 5: // Link
             $Link = Gen_Get_Cond1('Links'," MinedA=$Mid OR MinedB=$Mid ");
             if ($Link) {
               if ($Link['MinedA'] == $Mid) $Link['MinedA'] = 0;
               if ($Link['MinedB'] == $Mid) $Link['MinedB'] = 0;
               Put_Link($Link);
             }
             break;

           default:
           }

         TurnLog($Who,"A Minefield in " . $N['Ref'] . " $LocText has been cleared.");
         Report_Others($Who, $T['SystemId'],2,"A Minefield in " . $N['Ref'] . " $LocText has been cleared by " . $Facts[$Who]['Name']);
         GMLog("A Minefield in " . $N['Ref'] . " $LocText has been cleared by " . $Facts[$Who]['Name']);
         }
       } else {
         TurnLog($$T['Whose'],"There is no Minefield to remove in " . $N['Ref'] . " $LocText");
       }
       break;

     case 'Salvage':  // Find all Ex things in space, go through each work out money, if have Wrecage analysis give that report as well
       $Wrecks = Get_Things_Cond(0,"SystemId=" . $T['SystemId'] . " AND BuildState>3");
       $SalvageLevel = Has_Tech($Who,'Salvage Rigs');
       $HasWreck = Has_Tech($Who,'Wreckage Analysis');
       $TotMoney = 0;
       $ModTypes = Get_ModuleTypes();
       $DistTypes = Get_DistrictTypes();

       $N = Get_System($T['SystemId']);

       foreach ($Wrecks as $W) {
         if (($TTypes[$W['Type']]['Properties'] & (THING_HAS_DISTRICTS + THING_HAS_SHIPMODULES)) != 0) {
           $Money = 0;
           $Wreck = [];
           switch ($TTypes[$W['Type']]['Name']) {

           case 'Military Ship':
           case 'Support Ship' :
           case 'Civilian Ship' :
           case 'Satellite Defences' :
             $Money = min(10*$W['Level']*$SalvageLevel,Proj_Costs($W['Level'])[1]*0.9);
             if ($HasWreck) {
               $Modules = Get_Modules($W['id']);
               foreach ($Modules as $Mod) {
                 $L = Has_Tech($W['Whose'],$ModTypes[$Mod['Type']]['BasedOn']);
                 if ($L) {
                   $Wreck[]= $Mod['Number'] . " " . $ModTypes[$Mod['Type']]['Name'] . " L$L";
                 } else {
                   $Wreck[]= $Mod['Number'] . " Unknown modules.";
                 }
               }
            }

             Thing_Delete($W['id']);
             break;

           case 'Space Station':
             $Money = min(10*$W['MaxDistricts']*$SalvageLevel,Proj_Costs($W['MaxDistricts'])[1]*0.9);
             if ($HasWreck) {
               $Districts = Get_DistrictsT($W['id']);
               foreach ($Districts as $D) {
                 $Wreck[]= $D['Number'] . " " . $DistTypes[$D['Type']]['Name'];
               }
             }
             Thing_Delete($W['id']);
             break;

           default:
             break;
           }
           if ($Money) {
             $TotMoney += $Money;
             TurnLog($Who,"The wreckage of the " . (empty($W['Name'])? ("Unknown Thing #" . $W['id']) : $W['Name']) .
                " has been salvaged.  in " . $N['Ref'] . " Gaining " . Credit() . $Money );
             if ($Wreck) TurnLog($Who, "It had: " . implode(', ', $Wreck));
           }
         }
       }
       if ($TotMoney) {
         Spend_Credit($Who,- $TotMoney, "Salvage from " . $N['Ref']);
       } else {
         TurnLog($Who,"Salvage was attempted in " .  $N['Ref'] . " but there are no wrecks currently present.");
         GMLog("Salvage was attempted in " .  $N['Ref'] . " by " . $Facts[$Who]['Name'] . " but there are no wrecks currently present.");
       }
       break;

     case 'Link Repair':
       $Link = Get_Link($T['Dist1']);
       $Link['Status'] = 0;
       $Link['UseCount'] = 0;
       $Link['Weight'] = $T['Dist2'];
       Put_Link($Link);
       TurnLog($T['Whose'],"Link " . $T['Dist1'] . " has been repaired.");
       GMLog("Link " . $T['Dist1'] . " has been repaired at Strength " . $T['Dist2']);
       break;

     default:
       break;
     }
     if ($T['BuildState']>=0) {
       $T['Instruction'] = 0;
       $T['Progress'] = 0;
       $T['CurInst'] = 0;
       $T['MakeName'] = '';
       $T['ProjectId'] = 0;
       $T['Spare1'] = 0;
       Put_Thing($T);
     } else {
       db_delete('Things',$Tid);
     }
   }

  return 1;
}

function CheckSurveyReports() {
  global $GAME, $SurveyLevels;

//  Go throuh Scans file - sort by Fid - each link, is it higher scan level than currently scanned?  If so find highest scan level attempted and show menu
//  GMs can de-tune to allow for conflict etc  Enable scan data - save scans to log?
  $Scans = Gen_Get_Cond('ScansDue'," Turn=" . ($GAME['Turn']) . " ORDER BY FactionId,Sys,Scan DESC");

// var_dump($Scans);
  $Facts = Get_Factions();
  $Started = 0;
  $LastSys = 0;
  foreach($Scans as $S) {

    $Fid = $S['FactionId'];
    $Sid = $S['Sys'];
    $SSid = $S['id'];
    if ($LastSys == $Sid) continue;
    $FS = Get_FactionSystemFS($Fid,$Sid);

//var_dump($FS);
    if ($FS['ScanLevel'] >= $S['Scan']) continue;
    if (!$Started) {
      GMLog("<h2>Please review these scans, mark lower as needed</h2>\n");
      GMLog("<table border><tr><td>Faction<td>Where<td>Scan Level<td>Type<td>Control\n");
      if (Access('God')) echo "</tbody><tfoot><tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea>";
      Register_AutoUpdate('ScansDue',0);
      $Started = 1;
    }

    $N = Get_System($S['Sys']);
    if ($N['Nebulae'] > $S['Neb']) $S['Scan'] = 0; // Blind - in nebula wo neb scanners
    GMLog("<tr><td>" . $Facts[$Fid]['Name'] . "<td>" . $N['Ref'] . "<td>" . fm_radio('',$SurveyLevels,$S,'Scan','',0,'',"Scan:$SSid") .
      ($N['Control'] ? ( "<td style='background:" . $Facts[$N['Control']]['MapColour'] . ";'>" . $Facts[$N['Control']]['Name'])  : '<td>None') );
    $LastSys = $Sid;
  }
  if ($Started) {
    GMLog("</table>\n");
    GMLog("<h2><a href=TurnActions.php?ACTION=StageDone&Stage=CheckSurveyReports>When Happy click here</a></h2>");
    dotail();
  }
  return 1;
}


function GiveSurveyReports() {
  global $GAME;
  $Scans = Gen_Get_Cond('ScansDue'," Turn=" . ($GAME['Turn']) . " ORDER BY FactionId,Sys,Scan DESC");

  $Facts = Get_Factions();
  $Started = 0;
  foreach($Scans as $S) {

    $Fid = $S['FactionId'];
    $FS = Get_FactionSystemFS($Fid,$S['Sys']);
//echo "<p>";
// if ($Fid == 2) var_dump($Fid, $S, $FS);
    if ($FS['ScanLevel'] >= $S['Scan'] && $FS['NebScanned'] >= $S['Neb']) continue;
    $Sys = $S['Sys'];
    $N = Get_System($Sys);
    $Neb = $N['Nebulae'];
    $New = !isset($FS['id']);
    if ($FS['ScanLevel'] < $S['Scan']) $FS['ScanLevel'] = $S['Scan'];
    if ($FS['NebScanned'] < $S['Neb']) $FS['NebScanned'] = $S['Neb'];
// if ($Fid == 2) var_dump($FS);
    Put_FactionSystem($FS);

    if (!$New) {
      TurnLog($Fid, "<h3>You are due an improved survey report for <a href=SurveyReport.php?N=$Sys>" . System_Name($N,$Fid) . "</a></h3>");
    } else {
      TurnLog($Fid, "<h3>You are have new survey report for <a href=SurveyReport.php?N=$Sys>" . System_Name($N,$Fid) . "</a></h3>");
    }
  }


//  echo "Give Survey Reports is currently Manual<p>";
  return 1;
}

// TODO GM check, and use the scan results to modify

function CheckSpotAnomalies() {
  global $GAME;
  // Get all Anomalies - order by locn and scan level asc
  // foreach get all ships with sensors in each locn
  // if fact already knows skip
  // if sensors < scan level needed report failure and margin
  // if sensors >= scan level report found
  // Do we need to consider multiple ships from a faction?

  $Facts = Get_Factions();
  $Systems = Get_SystemRefs();
  $Scans = Gen_Get_Cond('ScansDue'," Turn=" . $GAME['Turn'] . " ORDER BY FactionId,Sys");

  GMLog("<form method=Post action=TurnActions.php?ACTION=StageDone>" . fm_hidden('Stage','CheckSpotAnomalies'));
  GMLog("<h1>Please check these anomalies should be spotted</h1>");
  GMLog("<table border><tr><td>Who<td>Where<td>what<td>Stop\n");
  $Anoms = Gen_Get_Cond('Anomalies',"GameId=" . $GAME['id'] . " ORDER BY SystemId, ScanLevel");
  foreach($Anoms as $A) {
    $Aid = $A['id'];
//if ($Aid == 37) var_dump($A);
    $Sid = $A['SystemId'];
    $ScanNeed = $A['ScanLevel'];

    $Things = Get_Things_Cond(0,"SystemId=$Sid AND SensorLevel>=$ScanNeed ORDER BY Whose, SensorLevel DESC");
// if ($Aid == 37) var_dump($Things);
    $LastWho = 0;
    $LastAn = 0;
    foreach($Things as $T) {
//if ($Aid == 45) echo "Checking " . $T['id'] . "<br>";
//if ($Aid == 37) echo "Checking " . $T['Name'] . "<br>";
      foreach($Scans as $S) {
        if (($S['Sys'] == $Sid) && $S['FactionId'] == $T['Whose'] && $S['Scan']<5) continue 2; // Next thing
      }

//if ($Aid == 45) echo "Checked " . $T['Name'] . "<br>";
      if ($T['Whose'] != $LastWho) {
        $LastWho = $T['Whose'];
        $LastAn = 0;
        $FA = Gen_Get_Cond('FactionAnomaly',"FactionId=$LastWho AND AnomalyId=$Aid");
        if ($FA) {
          $FA = $FA[0];
        } else {
          $FA = ['FactionId'=>$LastWho, 'AnomalyId'=>$Aid, 'State'=>0, 'Notes'=>''];
        }
      }
      if ((($A['Properties'] == 0) && (($FA['State'] == 0) && ($LastAn != $Aid))) ||
          (($A['Properties'] == 1) && (($FA['State'] == -1) && ($LastAn != $Aid)))) {
/*
        if ($T['SensorLevel'] < $A['ScanLevel'] ) {
          if ( GameFeature('MissedAnomalies',0)) GMLog($Facts[$T['Whose']]['Name'] . " Just missed spotting an anomaly in " . $Systems[$Sid] .
              " by one sensor level on the " . $T['Name']);
          $LastAn = $Aid;
          continue;
        }
*/

        $Tid = $T['id'];
        GMLog("<tr><Td>" . $Facts[$T['Whose']]['Name'] . "<td>" . $Systems[$Sid] . "<td><a href=AnomalyEdit.php?id=$Aid>" . $A['Name'] . "</a><td>" .
              fm_checkbox('',$_REQUEST,"Prevent$Tid"));
        $LastAn = $Aid;
      }
    }
  }

  GMLog("</table><input type=submit value='Click to Proceed'></form>\n");
  dotail();
}


function SpotAnomalies() {
  global $GAMEID;
  // Get all Anomalies - order by locn and scan level asc
  // foreach get all ships with sensors in each locn
  // if fact already knows skip
  // if sensors < scan level needed report failure and margin
  // if sensors >= scan level report found
  // Do we need to consider multiple ships from a faction?

  $Facts = Get_Factions();
  $Systems = Get_SystemRefs();
  $Parsedown = new Parsedown();
  $TTypes = Get_ThingTypes();

  $Anoms = Gen_Get_Cond('Anomalies',"GameId=$GAMEID AND ScanLevel>=0 ORDER BY SystemId, ScanLevel");
  foreach($Anoms as $A) {
    $Aid = $A['id'];
    $Sid = $A['SystemId'];
    $ScanNeed = $A['ScanLevel'];
    $OnPlanet = (($A['WithinSysLoc']%100 ==2) || ($A['WithinSysLoc']%100 ==4));

    $Things = Get_Things_Cond(0,"SystemId=$Sid AND SensorLevel>=$ScanNeed ORDER BY Whose, SensorLevel DESC");
    $LastWho = 0;
    $LastAn = 0;
    foreach($Things as $T) {
      if (( $OnPlanet && (($TTypes[$T['Type']]['Props'] & THING_HAS_ARMYMODULES) > 0)) ||
          (!$OnPlanet && (($TTypes[$T['Type']]['Props'] & THING_HAS_SHIPMODULES) > 0 ))) {
// if ($Aid == 45) echo "Checking " . $T['id'] . "<br>";
        if ($Facts[$T['Whose']]['NoAnomalies']) continue;
        if ($T['Whose'] != $LastWho) {
          $LastWho = $T['Whose'];
          $LastAn = 0;
          $FA = Gen_Get_Cond('FactionAnomaly',"FactionId=$LastWho AND AnomalyId=$Aid");
          if ($FA) {
            $FA = $FA[0];
          } else {
            $FA = ['FactionId'=>$LastWho, 'AnomalyId'=>$Aid, 'State'=>0, 'Notes'=>''];
          }
        }
        $Tid = $T['id'];
        if ((($A['Properties'] == 0) && (($FA['State'] == 0) && ($LastAn != $Aid))) ||
            (($A['Properties'] == 1) && (($FA['State'] == -1) && ($LastAn != $Aid)))) {

          if ( !isset($_REQUEST["Prevent$Tid"]) || $_REQUEST["Prevent$Tid"]!='on')  {

            TurnLog($LastWho,"You have spotted an anomaly: " . $A['Name'] . " in " . $Systems[$Sid] . "\n" .  $Parsedown->text($A['Description']) .
                  "\nIt will take " . $A['AnomalyLevel'] . " scan level actions to complete.\n\n");

            GMLog($Facts[$T['Whose']]['Name'] . " have just spotted anomaly: <a href=AnomalyEdit.php?id=$Aid>" . $A['Name'] . "</a> in " .
                  $Systems[$Sid] . " from the " . $T['Name'] );
            $FA['State'] = 1;
  // var_dump($FA);
            Gen_Put('FactionAnomaly',$FA);
          }
          $LastAn = $Aid;
          continue;
        } else { // Already known

        }
      }
    }
  }

  GMLog("All Anomalies checked<p>");
  return 1;
}


function MilitiaArmyRecovery() {
//  GMLog("Militia Recovery is currently Manual<p>");
//  GMLog("Also Self Repair Armour<p>");

  $Things = Get_Things_Cond(0,"(CurHealth>0 OR (CurHealth=0 AND Type=20)) AND (CurHealth<OrigHealth OR CurShield<ShieldPoints)");
  $TTypes = Get_ThingTypes();
  $MTypes = Get_ModuleTypes();
  $MTNs = Mod_Types_From_Names($MTypes);
  $LastHost = $LastId = $Rec = 0;

  foreach ($Things as $T) {
    if ($TTypes[$T['Type']]['Name'] == 'Militia') {

      // if not in conflit, recovery some
      $Conflict = 0;
      $Conf = Gen_Select("SELECT W.* FROM ProjectHomes PH, Worlds W WHERE PH.SystemId=" . $T['SystemId'] . " AND W.Home=PH.id AND W.Conflict=1");
      if ($Conf) $Conflict = $Conf[0]['Conflict'];
      if ($Conflict) continue;  // No recovery allowed

      if ($LastHost != $T['Dist1'] || $LastId != $T['Dist2']) {
        $Dists = Gen_Get_Cond('Districts',"HostType=" . $T['Dist1'] . " AND HostId=" . $T['Dist2']);
        $Dcount = 0;
        foreach($Dists as $D) $Dcount += $D['Number'];
        $Rec = floor($Dcount/2)+1;
        $LastHost = $T['Dist1'];
        $LastId = $T['Dist2'];
      }

// echo "Recovery of $Rec<br>";
      $T['CurHealth'] = max($T['OrigHealth'], $T['CurHealth']+$Rec);
      Put_Thing($T);
      if ($T['Whose']) TurnLog($T['Whose'],$T['Name'] . " recovered $Rec health",$T);
      GMLog("<a href=ThingEdit.php?id=" . $T['id'] . ">" . $T['Name'] . ' a ' . $TTypes[$T['Type']]['Name'] . "</a> recovered $Rec health");

    }

    if ($TTypes[$T['Type']]['Properties'] & THING_HAS_SHIPMODULES) {
      $Self = Get_ModulesType($T['id'],$MTNs['Self-Repairing Armour']);
      if (isset($Self['Number'])) {
        $Rep = $Self['Number']*$Self['Level']*2;
        $T['CurHealth'] = min($T['OrigHealth'],$T['CurHealth']+$Rep);
        Put_Thing($T);
        if ($T['Whose']) TurnLog($T['Whose'],$T['Name'] . " recovered $Rep health",$T);
        GMLog("<a href=ThingEdit.php?id=" . $T['id'] . ">" . $T['Name'] . "</a> recovered $Rep health");
      }
    }

    if ($TTypes[$T['Type']]['Properties'] & THING_HAS_ARMYMODULES) {
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

function FixFudge() { // Tempcode called to fix thi9ngs
  return 1;
  global $db,$GAMEID,$GAME;
  // Save What can I see data
  $Factions = Get_Factions();
  foreach($Factions as $F) {
    $Fid = $F['id'];
    $CouldC = WhatCanBeSeenBy($Fid);
    $CB = fopen("Turns/$GAMEID/" . ($GAME['Turn']-1) . "/CouldC$Fid.html", "w");
    fwrite($CB,$CouldC);
    fclose($CB);
    GMLog("Saved What could be seen for " . $F['Name']);
  }

}

function TidyUps() {
  global $db,$GAMEID,$GAME;

  $res = $db->query("UPDATE Things SET LinkId=0, LinkPay=0, LinkCost=0 WHERE LinkId>0 AND GameId=$GAMEID");

  // Check for lid <-1...
  $NotFin = Get_Things(0,"LinkId<-1");
  if ($NotFin) {
    GMLog( "<h2 class=Err>These things have a broken load/unload still in place get Richard to fix</h2>");
    FollowUp(0,"Things have broken load/unload get Richard to fix");
    foreach ($NotFin as $T) {
      GMLog("<a href=ThingEdit.php?id=" . $T['id'] . ">" . $T['Name'] . " has a lid of " . $T['LinkId']);
    }
  }

  // Tidy 1 turn carry options
  $FFs = Gen_Get_Cond('FactionFaction',"GameId=$GAMEID AND Props>0");
  foreach ($FFs as $F) {
    if (($F['Props'] &15) == 1) $F['Props'] = ($F['Props']&0xfffffff0);
    if ((($F['Props']>>4) &15) == 1) $F['Props'] = ($F['Props']&0xffffff0f);
    if ((($F['Props']>>8) &15) == 1) $F['Props'] = ($F['Props']&0xfffff0ff);
    if ((($F['Props']>>12) &15) == 1) $F['Props'] = ($F['Props']&0xffff0fff);
    if ((($F['Props']>>16) &15) == 1) $F['Props'] = ($F['Props']&0xfff0ffff);
    if ((($F['Props']>>20) &15) == 1) $F['Props'] = ($F['Props']&0xff0fffff);
    if ((($F['Props']>>24) &15) == 1) $F['Props'] = ($F['Props']&0xf0ffffff);
    if ((($F['Props']>>28) &15) == 1) $F['Props'] = ($F['Props']&0x0fffffff);
    Put_FactionFaction($F);
  }

  // Tidy up Scans due ?

  // Tidy up continuing Instructions

  $Things = Get_Things_Cond(0,"Instruction<0");
  foreach ($Things as $T) {
    $T['Instruction'] = abs($T['Instruction']);
    Put_Thing($T);
  }
  GMLog("Instructions fields reset for continued use<p>");


  GMLog("Movements, 1 turn carry, district deltas Tidied Up<p>");
  return 1;
}

function ClearConflictFlags() {
  $_REQUEST['CONFLICT'] = 2; // Makes WorldList think its part of turn processing - 2 for clearing flags
  include_once("WorldList.php");
  return 1;
}


function SaveWhatCanBeSeen() {
  global $db,$GAMEID,$GAME;
  // Save What can I see data
  $Factions = Get_Factions();
  foreach($Factions as $F) {
    $Fid = $F['id'];
    $CouldC = WhatCanBeSeenBy($Fid);
    $CB = fopen("Turns/$GAMEID/" . $GAME['Turn'] . "/CouldC$Fid.html", "w");
    fwrite($CB,$CouldC);
    fclose($CB);
    GMLog("Saved What could be seen for " . $F['Name']);
  }
  return 1;
}

function RecalcProjectHomes() {
  // Proj Homes, Worlds
  include_once("HomesLib.php");
  include_once("MinedLib.php");
  Recalc_Project_Homes('SKLog'); // in ThingLib - this is to add new project homes that have been created by colonisation etc.
  Recalc_Worlds();

  // Dynamic Systems

  $DSys = Gen_Get_Cond('Systems',"Flags>0");
  foreach($DSys as $N) {
    Dynamic_Update($N,1);
  }

  Recalc_Mined_locs();
  Recalc_Prisoner_Counts();
  return 1;
}

function FinishTurnProcess() {
  global $GAME,$Sand;
  // Change faction state update turn number

  $Facts = Get_Factions();
  foreach ($Facts as $F) {
    if ($F['NPC'] == 0 ) continue;
    $F['TurnState'] = 1;
    Put_Faction($F);
  }

  GMLog("<br>NPC Factions marked as Turn Planning<p>\n");
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

function Do_Turn() {
  global $Sand;  // If you need to add something, replace a spare if poss, then nothing breaks
  global $Stages,$Coded;

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
    case 'StageDone':
//      $S = $_REQUEST['S'];
      $SName = $_REQUEST['Stage'];
      $SName = preg_replace('/ /','',$SName);
      for($S =0; $S <64 ; $S++) {
        $act = $Stages[$S];
        $act = preg_replace('/ /','',$act);
        if ($SName == $act) break;
      }
      if ($S > 63) {
        GMLog("Stage $SName not found");
        break;
      } else {
        $Sand['Progress'] |= 1<<$S;
      }// Deliberate drop through

    case 'Complete': // Should be now no longer used - See StageDone lower down.  (Uses name of stge not number - thus allows for renumbering)
      SKLog("Completed " . $Stages[$S]);
      $Sand['Progress'] |= 1<<$S;
      $S++; // Deliberate drop through

    case 'Process':
      if (is_numeric($S)) {
        $act = $Stages[$S];
        $act = preg_replace('/ /','',$act);
      } else {
        $SName = $_REQUEST['S'];
        $SName = preg_replace('/ /','',$SName);
        for($S =0; $S <64 ; $S++) {
          $act = $Stages[$S];
          $act = preg_replace('/ /','',$act);
          if ($SName == $act) break;
        }
        var_dump($S);
        if ($S > 63) {
          GMLog("Stage $SName not found");
          break;
        } else {
          $Sand['Progress'] |= 1<<$S;
        }
        SKLog("Completed " . $Stages[$S]);
        $S++;
        $act = $Stages[$S];
        $act = preg_replace('/ /','',$act);
      }
      GMLog("Would call $act<p>");
      if (!is_callable($act)) {
        GMLog("<class Err>$act not yet written</span><p>");
        break;
      }
      SKLog("Doing " . $Stages[$S]);
      $Result = $act();
      if ($Result) {
        $Sand['Progress'] |= 1<<$S;
        if ($Result > 1) {
          $Sand['Progress'] |= 2<<$S;
        }
      } else {
        GMLog("Processing cancelled<p>\n");
      }
      break;

    case 'Skip':
      if (isset($Stages[$S] )) {
        GMLog("<b>" . $Stages[$S] . " Skipped<b>");
        SKLog("Skipped " . $Stages[$S]);
        $Sand['Progress'] |= 1<<$S;
      } else {
        echo "Off the end of the turn";
      }
      break;

    case 'Revert':
      if (isset($Stages[$S] )) {
        GMLog("<b>" . $Stages[$S] . " Reverted<b>");
        SKLog("Reverted " . $Stages[$S]);
        $Sand['Progress'] &= ~(1<<$S);
      } else {
        GMLog("Off the end of the turn");
      }
      break;

    case 'RevertAll':
      if (isset($Stages[$S] )) {
        SKLog("Reverted All");
        $Sand['Progress'] = 0;
      } else {
        GMLog("Off the end of the turn");
      }
      break;

    }
  }

  echo "<table border>";
  echo "<tr><th>#" . (Access('God')?"<td>Mask":"") . "<td>Automation<td>Stage<th>State<th>Commands\n";
  $Stage = 0;
  $Prog = 1;
  $NextStage = -1;

  foreach ($Stages as $sta) {
    if ($sta != 'Spare') {
      echo "<tr><td>$Stage<td>" .  (Access('God')?(dechex($Prog) . "<td>"):"") . $Coded[$Stage] . "<td>" . $sta . "<td>";

// var_dump($Sand['Progress'],$Prog);
      if ($Sand['Progress'] & $Prog) {
        echo "Completed<td><a href=TurnActions.php?ACTION=Revert&S=$Stage>Revert</a>";
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
  $Parsedown = new Parsedown(); // To hande logs in time

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

?>
