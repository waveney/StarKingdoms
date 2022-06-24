<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("PlayerLib.php");
  include_once("SystemLib.php");
  include_once("ProjLib.php");
  include_once("HomesLib.php");
  include_once("BattleLib.php");
  include_once("vendor/erusev/parsedown/Parsedown.php");
  
  A_Check('GM');

// For logging turn processing events that need following up or long term record keeping, set e to echo to GM
function GMLog($text,$Bold=0) {
  global $GAME,$GAMEID;
  static $LF;
  if (!isset($LF)) {
     if (!file_exists("Turns/$GAMEID/" . $GAME['Turn'])) mkdir("Turns/" . $GAMEID . "/" . $GAME['Turn'],0777,true);
     $LF = fopen("Turns/$GAMEID/" . $GAME['Turn'] . "/0.txt", "a+");
  }
  if ($Bold) $text = "<b>" . $text . "</b>";
  fwrite($LF,"$text\n");
  echo "$text<br>";  
}

function SKLog($text,$e=0) {
  global $Sand,$USER;
  $Sand['ActivityLog'] .= date("Y-m-d H:i:s - ") . $USER['Login'] . " - " . $text . "\n";  // Who?
  if ($e) GMLog($text. "<br>\n");
}

// Log to the turn text, and optionally aa history record of T
function TurnLog($Fid,$text,&$T=0) {
  global $GAME,$GAMEID;
  static $LF = [];
  if ($Fid == 0) {
    echo "<h2 class=Err>Loging a turn action for Faction 0! - Call Richard</h2>\n";
    debug_print_backtrace();
    dotail();
  }
  if (!isset($LF[$Fid])) {
     if (!file_exists("Turns/$GAMEID/" . $GAME['Turn'])) mkdir("Turns/" . $GAMEID . "/" . $GAME['Turn'],0777,true);
     $LF[$Fid] = fopen("Turns/$GAMEID/" . $GAME['Turn'] . "/$Fid.txt", "a+");
  }
  fwrite($LF[$Fid],"$text\n");
  if ($T) $T['History'] .= "Turn#" . ($GAME['Turn']) . ": " . $text . "\n";
}


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
  $Facts[-1]['Name'] = "Other";
  $Bs = Get_BankingFT(0,$GAME['Turn']);
  AddCurrencies();
  
  foreach($Bs as $B) {
    if ($B['DoneTurn'] >= $GAME['Turn']) continue; // Already done
    if ($B['What'] == 0) {
      if (Spend_Credit($B['FactionId'],$B['Amount'],$B['YourRef'])) {
        TurnLog($B['FactionId'],"Transfered " . Credit() . $B['Amount'] . " for " . $B['YourRef'] . " to " . $Facts[$B['Recipient']]['Name']);

        if ($B['Recipient'] > 0) {
          Spend_Credit($B['Recipient'], - $B['Amount'],$B['YourRef']);
          TurnLog($B['Recipient'],  $Facts[$B['FactionId']]['Name'] . " transfered " . Credit() . $B['Amount'] . " to you for " . $B['YourRef'] );
        }
        SKLog('Cash transfer from ' . $Facts[$B['FactionId']]['Name']. ' to ' . $Facts[$B['Recipient']]['Name'] . ' of ' . $B['Amount'] . ' for ' . $B['YourRef'],1);     
      } else {
        TurnLog($B['FactionId'],"Failed to transfer " . Credit() . $B['Amount'] . " for " . $B['YourRef'] . " to " . $Facts[$B['Recipient']]['Name'] . 
                 " you only have " . $Facts[$B['FactionId']]['Credits']);
        if ($B['Recipient'] > 0) TurnLog($B['Recipient'],  $Facts[$B['FactionId']]['Name'] . " Failed to transfer " . Credit() . $B['Amount'] . " for " . $B['YourRef'] );
        SKLog('Cash transfer from ' . $Facts[$B['FactionId']]['Name']. ' to ' . $Facts[$B['Recipient']]['Name'] . 
              ' of ' . Credit() . $B['Amount'] . ' for ' . $B['YourRef'] .  ' Bounced',1);
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
          SKLog('Transfer from ' . $Facts[$B['FactionId']]['Name']. ' to ' . $Facts[$B['Recipient']]['Name'] . ' of ' . $B['Amount'] . $Currencies[$B['What']]
                 . ' for ' . $B['YourRef'],1);      
        } else {
          TurnLog($B['FactionId'],"Failed to transfer " . $B['Amount']  . " of " . $Currencies[$B['What']] . " for " . $B['YourRef'] . " to " . $Facts[$B['Recipient']]['Name']);
          if ($B['Recipient'] > 0) TurnLog($B['Recipient'],  $Facts[$B['FactionId']]['Name'] . " Failed to transfer " . $B['Amount']  . " of " . $Currencies[$B['What']]
              . " for " . $B['YourRef'] );
          SKLog('Transfer from ' . $Facts[$B['FactionId']]['Name']. ' to ' . $Facts[$B['Recipient']]['Name'] . 
                ' of ' . $B['Amount'] . " of " . $Currencies[$B['What']] . ' for ' . $B['YourRef'] .  ' Bounced',1);
        }
      } else {
        if ($B['Recipient'] > 0) {
          Gain_Currency($B['Recipient'], $B['What'], $B['Amount'],$B['YourRef']);
          TurnLog($B['Recipient'],  "Gained " . $B['Amount'] . " of " . $Currencies[$B['What']] . " for " . $B['YourRef'] );
        }      
      }
    }
    $B['DoneTurn'] = $GAME['Turn'];
    Put_Banking($B);
  }
  
  GMLog("All Cash trasfered complete.<br>\n");
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
    if (Spend_Credit($T['Whose'],$Cost,'Moving ' . $Ref)) {
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
//  GMLog("Pay For Stargates are currently Manual<p>");
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
        GMLog("Faction: " . $Fact['Name'] . " attempted to use science points to raise " . $Tech['Name'] . " to level " . $Br['Level'] . " already at level " . $CTech['Level']);
        TurnLog($Br['FactionId'],"You attempted to use science points to raise " . $Tech['Name'] . " to level " . $Br['Level'] . " it is already at level " . $CTech['Level']);
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
        if (($T['SystemId'] != 0 && $T['SystemId'] != $Where[0])) {
//var_dump($Where,$T);
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
        RefitRepair($T); //Note this saves it
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

/*
$ThingInstrs = ['None','Colonise','Voluntary Warp Home','Decommision','Analyse Anomaly','Establish Embassy','Make Outpost','Make Asteroid Mine','Make Minefield',
                'Make Orbital Repair Yard','Build Space Station','Expand Space Station','Make Deep Space Sensor','Make Advanced Asteroid Mine','Build Stargate',
                'DSC Special'];
*/

function Instuctions() { // And other Instructions
  global $ThingInstrs;
  $Things = Get_Things_Cond(0,"Instruction>0");
  $NeedColStage2 = 0;
  $Facts = Get_Factions();
  
  $PTs = Get_ProjectTypes();
  $AAs = [];
  
  foreach ($Things as $T) {
    $N = Get_System($T['SystemId']);
    $Tid = $T['id'];
    // Mark new Instructions
    if ($T['Instruction'] != $T['CurInst']) {
      $T['CurInst'] = $T['Instruction'];
      $T['Progress'] = 0;
    }
    
    if ($T['Progress']>0) continue;
    
    switch ($ThingInstrs[$T['Instruction']]) {
    case 'Colonise': // Colonise
    case 'Build Planetary Mine':

      $P = Get_Planet($T['Spare1']);
      if (empty($P)) { // Refind the planet we need
        $Fid = $T['Whose'];
        $PTs = Get_PlanetTypes();
        $Ps = Get_Planets($N['id']);
        $Hab_dome = Has_Tech($Fid,'Habitation Domes');
        $HabPs = [];
        foreach($Ps as $P) {
          if (!$PTs[$P['Type']]['Hospitable']) continue;
          if (Get_DistrictsP($P['id'])) continue; // Someone already there
          if (($P['Type'] == $FACTION['Biosphere']) || ($PH['Type'] != $FACTION['Biosphere2']) || ($PH['Type'] != $FACTION['Biosphere3'])) { 
            $HabPs[$P['id']] = [$P,3];
          }
          if ($P['Type'] == 4 ) {
            if (!$Hab_dome) continue;
            $HabPs[$P['id']] = [$P,10];
          } else {
            $HabPs[$P['id']] = [$P,6];
          }
        }
        if (empty($HabPs)) {
          GMLog("<h2>Planet not found - Tell Richard</h2>",1);
          var_dump($T);
          exit;
        }
        [$P,$Acts] = array_shift($HabPs);
        $T['Spare1'] = $P['id'];
        $T['ActionsNeeded'] = $Acts;
      }
      
      if (($ThingInstrs[$T['Instruction']]) == 'Colonise') {
        TurnLog($T['Whose'],"The " . $T['Name'] . " is colonising " . $P['Name'] . " in " . $N['Ref'] ,$T);
        SKlog($Facts[$T['Whose']]['Name'] . " is starting to colonise " . $P['Name'] . " in " . $N['Ref']);
      } else {
        TurnLog($T['Whose'],"The " . $T['Name'] . " is seting up planetary mining " . $P['Name'] . " in " . $N['Ref'] ,$T);
        SKlog($Facts[$T['Whose']]['Name'] . " is starting to planetary mine " . $P['Name'] . " in " . $N['Ref']);      
      }
      break;

    case 'Voluntary Warp Home': // Warp out
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
             echo "<form method=post action=TurnActions.php?ACTION=Process&S=16>";
             $NeedColStage2 = 1;
           }
           $_REQUEST['G'] = $FirstG;
           GMLog("<p><a href=ThingEdit.php?id=$Tid>" . $T['Name'] . "</a> is warping out - Please Choose which gate:");
           GMLog(fm_select($GLocs,$_REQUEST,'G',0,'',"G" . $T['id']));
           GMLog("<p>");
           break;
         } else {
           $T['SystemId'] = $Gates[0]['SystemId'];
           $T['WithinSysLoc'] = $Gates[0]['WithinSysLoc'];
           $T['CurHealth'] = $T['Link_id'] = 0;
           TurnLog($T['Whose']," The " . $T['Name'] . " has warped back.  It now needs repair before it can be used again",$T);         
           break;
         }
       } else {
         TurnLog($T['Whose']," The " . $T['Name'] . " tried to warp out, but you have no Warp Gates...",$T);
         SKLog(" The " . $T['Name'] . " tried to warp out, but there are no warp gates",1);
         $T['Instruction'] = 0;
       }
       break;
 
    case 'Decommision': // Dissasemble
      $Lvl = $T['Level'];
      $T['BuildState'] = -1;
      $T['SystemId'] = 0;
      $T['History'] .= "Decommissioned";
      $T['Instruction'] = 0;
      $cash = 10*$Lvl*Has_Tech($T['Whose'],'Ship Construction');
      TurnLog($T['Whose'], "The " . $T['Name'] . " has been decommisioned gaining you " . Credit() . $cash, $T);
      Spend_Credit($T['Whose'],-$cash,"Decommisioning " . $T['Name']);
      GMLog($Facts[$T['Whose']]['Name'] . " - " . $T['Name'] . " has been decommisioned"); 
      
      $Have = Get_Things_Cond(0," (LinkId<0 AND SystemId=$Tid ");
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
      $T['History'] .= "Disbanded";
      $T['Instruction'] = 0;
      $cash = 10*$Lvl*Has_Tech($T['Whose'],'Military Organisation');
      TurnLog($T['Whose'], "The " . $T['Name'] . " has been disbanded gaining you " . Credit() . $cash, $T);
      Spend_Credit($T['Whose'],-$cash,"Disbanded " . $T['Name']);
      GMLog($Facts[$T['Whose']]['Name'] . " - " . $T['Name'] . " has been disbanded"); 
      
      $Have = Get_Things_Cond(0," (LinkId<0 AND SystemId=$Tid ");
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
          GMLog($Facts[$T['Whose']]['Name'] . " - " . $T['Name'] . " is supposed to be analysing an anomaly - but you don't know of one at" . $Sys['Ref']);
          $T['Instruction'] = $T['ProjectId'] = 0;
          Put_Thing($T);
          break;
        } else if ( ($FA['State'] == 1) && $A['OtherReq']) {
           if ($NeedColStage2 == 0) {
             echo "<form method=post action=TurnActions.php?ACTION=Process&S=16>";
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
                echo "<form method=post action=TurnActions.php?ACTION=Process&S=16>";
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
        echo "<form method=post action=TurnActions.php?ACTION=Process&S=16>";
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
//      $T['ActionsNeeded'] = 4;
      if (!Spend_Credit($T['Whose'],$T['InstCost'],"Make Asteroid Mine in " . $N['Ref']) ) {
        $T['Progress'] = -1; // Stalled
        TurnLog($T['Whose'],"Could not afford to start an Asteroid Mine in " .$N['Ref'],$T);
      }
      break;
      
    case 'Make Orbital Repair Yard':
//      $T['ActionsNeeded'] = 10;
      if (!Spend_Credit($T['Whose'],$T['InstCost'],"Make Orbital Repair Yard in " . $N['Ref']) ) {
        $T['Progress'] = -1; // Stalled
        TurnLog($T['Whose'],"Could not afford to start an Orbital Repair Yard in " .$N['Ref'],$T);
      }
      break;
      
    case 'Make Minefield':
//      $T['ActionsNeeded'] = 4;
      if (!Spend_Credit($T['Whose'],$T['InstCost'],"Make Minefield in " . $N['Ref']) ) {
        $T['Progress'] = -1; // Stalled
        TurnLog($T['Whose'],"Could not afford to start a Minefield in " .$N['Ref'],$T);
      }
      break;
      
    case 'Build Space Station':
//      $T['ActionsNeeded'] = 5*$T['Dist1'];
      if (!Spend_Credit($T['Whose'],$T['InstCost'],"Build Space Station in " . $N['Ref']) ) {
        $T['Progress'] = -1; // Stalled
        TurnLog($T['Whose'],"Could not afford to start a Build Space Station in " .$N['Ref'],$T);
      }
      break;
    
    case 'Expand Space Station':
//      $T['ActionsNeeded'] = 10*$T['Dist1'];
      if (!Spend_Credit($T['Whose'],$T['InstCost'],"Expand Space Station in " . $N['Ref']) ) {
        $T['Progress'] = -1; // Stalled
        TurnLog($T['Whose'],"Could not afford to Expand Space Station in " .$N['Ref'],$T);
      }
      break;
    
    case 'Make Deep Space Sensor':
//      $T['ActionsNeeded'] = 1;
      if (!Spend_Credit($T['Whose'],$T['InstCost'],"Make Deep Space Sensor in " . $N['Ref']) ) {
        $T['Progress'] = -1; // Stalled
        TurnLog($T['Whose'],"Could not afford to Make Deep Space Sensor in " .$N['Ref'],$T);
      }
      break;
    
    case 'Make Advanced Asteroid Mine':
//      $T['ActionsNeeded'] = 8;
      if (!Spend_Credit($T['Whose'],$T['InstCost'],"Make Advanced Asteroid Mine in " . $N['Ref']) ) {
        $T['Progress'] = -1; // Stalled
        TurnLog($T['Whose'],"Could not afford to start an Advanced Asteroid Mine in " .$N['Ref'],$T);
      }
      break;

     case 'Build Stargate':
// TODO 
       break;

    case 'Make Planet Mine':
      if (!Spend_Credit($T['Whose'],$T['InstCost'],"Make Planet Mine in " . $N['Ref']) ) {
        $T['Progress'] = -1; // Stalled
        TurnLog($T['Whose'],"Could not afford to start a Planet Mine in " .$N['Ref'],$T);
      }
      break;

    case 'Construct Command Relay Station':
      if (!Spend_Credit($T['Whose'],$T['InstCost'],"Make Command Relay Statione in " . $N['Ref']) ) {
        $T['Progress'] = -1; // Stalled
        TurnLog($T['Whose'],"Could not afford to start a Command Relay Station in " .$N['Ref'],$T);
      }
      break;
      
    case 'Repair Command Node': 
      if (!Spend_Credit($T['Whose'],$T['InstCost'],"Repair Command Node " . $N['Ref']) ) {
        $T['Progress'] = -1; // Stalled
        TurnLog($T['Whose'],"Could not afford to Repair Command Node " .$N['Ref'],$T);
      }


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

function InstuctionsStage2() { // And other Instructions
  global $ThingInstrs,$GAME;
  $Things = Get_Things_Cond(0,"Instruction>0");
  $NeedColStage3 = 0;
  foreach ($Things as $T) {
    $Tid = $T['id'];
    switch ($ThingInstrs[$T['Instruction']]) {

    case 'Voluntary Warp Home': // Warp out
      if (isset($_REQUEST["G$Tid"])) {
        $Gate = Get_Thing($_REQUEST["G$Tid"]);
      } else {
        $Gates = Get_Things_Cond($T['Whose'],' Type=15'); // Warp Gates
        $Gate = $Gates[0];      
      }
      $T['SystemId'] = $Gate['SystemId'];
      $T['WithinSysLoc'] = $Gate['WithinSysLoc'];
      $T['CurHealth'] = $T['Link_id'] = 0;
      TurnLog($T['Whose']," The " . $T['Name'] . " has warped back.  It now needs repair before it can be used again",$T);   
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

      
    default:
    break;
    }
  }
  return 1;
}

function AgentsStartMissions() {
  GMLog("Agents Start Missions	are currently Manual<p>");
  return 1;
}

function Economy() {
  global $db,$GAMEID;
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
    $EconVal = 0;
    $EccTxt = "\nEconomy:\n";
    $OutPosts = $AstMines = $AstVal = $Embassies = $OtherEmbs = 0;
    foreach ($Worlds as $W) {
      $H = Get_ProjectHome($W['Home']);
      if (empty($H)) continue;
      $PH = Project_Home_Thing($H);
      $Name = $PH['Name'];
      $ECon = $H['Economy'] = Recalc_Economic_Rating($H,$W,$Fid);
      $ECon = ceil(($ECon - $H['Devastation'])*$H['EconomyFactor']/100);
      
      if ($ECon <0) {
        $EccTxt .= "$Name: 0\n";
      } else {
        $EccTxt .= "$Name: $ECon " . ($H['Devastation']? " after devastation effect of -" . $H['Devastation'] : "");
        if ($H['EconomyFactor'] < 100) {
          $EccTxt .= " - at " . $H['EconomyFactor'] . "%\n";
        } else if ($H['EconomyFactor'] > 100) {
          $EccTxt .= " - at " . $H['EconomyFactor'] . "%\n";
        }
        $EconVal += $ECon;
      }
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
    foreach($OtherTs as $OT) {
      $OtherEmbs++;
    }
    
    if ($OutPosts) {
      $EccTxt .= "Plus $OutPosts Outposts worth 2 each\n";
      $EconVal += $OutPosts*2;
    }
    if ($AstMines) {
      $AstVal *= Has_Tech($Fid,'Deep Space Construction');
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
        if ($H['Whose'] != $T['Whose']) TurnLog($H['Whose'],  $Facts[$T['Whose']]['Name'] . " tried to load " . $T['Name'] . " on to " . $H['Name'] . " there is not enough space");
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
  global $GAME,$GAMEID;
  $LinkLevels = Get_LinkLevels();
  $Things = Get_AllThings();
  $TTypes = Get_ThingTypes();
  $Facts = Get_Factions();
  
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
 var_dump($UsedLinks);

  GMLog("<table border><tr><td>Who<td>What<td>Level<td>From<td>Link<td>To<td>Paid<td>Stop<td>Why Stopping\n");  
  foreach ($Things as $T) {
    if ($T['BuildState'] <2 || $T['BuildState'] > 3 || $T['LinkId'] <= 0 || $T['Whose']==0) continue;
    if (( $Agents == 0 &&  ($TTypes[$T['Type']]['Properties'] & THING_MOVES_AFTER)) || ( $Agents &&  ($TTypes[$T['Type']]['Properties'] & THING_MOVES_AFTER) ==0 ) ) continue;
    
    $CheckNeeded = ( $Agents && ($TTypes[$T['Type']]['Properties'] & THING_MOVES_AFTER) );

    if ($T['LinkId']>0 && $T['NewSystemId'] != $T['SystemId'] ) {
      $Tid = $T['id'];
      $Lid = $T['LinkId']; 
            
      $L = Get_Link($Lid);

      $Fid = $T['Whose'];
      $SR1 = Get_SystemR($L['System1Ref']);
      $SR2 = Get_SystemR($L['System2Ref']);
      
      GMLog("<tr><td>" . $Facts[$Fid]['Name'] . "<td><a href=ThingEdit.php?id=$Tid>" . $T['Name']  . "<td>" . $T['Level'] . "<td>");
      if ($T['SystemId'] == $SR1['id']) {
         GMLog($L['System1Ref'] . "<td style=color:" . $LinkLevels[$L['Level']]['Colour'] . ";>#$Lid<td>" . $L['System2Ref']);
      } else {
         GMLog($L['System2Ref'] . "<td style=color:" . $LinkLevels[$L['Level']]['Colour'] . ";>#$Lid<td>" . $L['System1Ref']);      
      }

// var_dump($CheckNeeded,$T['LinkPay']);
      if ($L['Level'] ==1 || $T['LinkPay']<0) {
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
  global $GAME,$GAMEID;
  // Foreach thing, do moves, generate list of new survey reports & levels, update "knowns" 

  if (!file_exists("Turns/" . $GAMEID . "/" . $GAME['Turn'])) $LF = mkdir("Turns/" . $GAMEID . "/" . $GAME['Turn'],0777,true);  
  
//  $PotS = fopen("Turns/" . $GAMEID . "/" . $GAME['Turn'] . "/ScansDue", "a+");
  $LinkLevels = Get_LinkLevels();
  $Things = Get_AllThings();
  $TTypes = Get_ThingTypes();
  $Facts = Get_Factions();
  
  foreach ($Things as $T) {
    if ($T['BuildState'] <2 || $T['BuildState'] > 3 || $T['LinkId'] <= 0 || $T['Whose']==0) continue;
    if (( $Agents == 0 &&  ($TTypes[$T['Type']]['Properties'] & THING_MOVES_AFTER)) || ( $Agents &&  ($TTypes[$T['Type']]['Properties'] & THING_MOVES_AFTER) ==0 ) ) continue;

    $Tid = $T['id'];
    $Fid = $T['Whose'];
    
    if (isset($_REQUEST["Prevent$Tid"]) && $_REQUEST["Prevent$Tid"] ) {
      $Lid = $T['LinkId']; 
      $L = Get_Link($Lid);
      TurnLog($Fid,$T['Name'] . " was <b>unable to take link</b> <span style=color:" . $LinkLevels[$L['Level']]['Colour'] . ">#$Lid </span> beause of " . 
        (isset($_REQUEST["Reason$Tid"])? $_REQUEST["Reason$Tid"]:"Unknown reasons"), $T);       
      continue;
    }
    
    if ($T['LinkId']>0 && $T['NewSystemId'] != $T['SystemId'] ) {
      GMLog("Moving " . $T['Name'] . "<br>");

      $Lid = $T['LinkId']; 

      $ShipScanLevel = Scanners($T);
      $ShipNebScanLevel = NebScanners($T);
            
      $L = Get_Link($Lid);
      if ($Agents == 0) {
        $L['UseCount'] += $T['Level'];
        Put_Link($L);
      }

      $SR1 = Get_SystemR($L['System1Ref']);
      $SR2 = Get_SystemR($L['System2Ref']);
      
      if ($T['SystemId'] == $SR1['id']) {
        $Sid = $T['NewSystemId'] = $SR2['id'];
        $Ref = $SR2['Ref']; // Names...
        $N = $SR2;
        $OldSid = $SR1['id'];
        $OldN = $SR1;
      } else {
        $Sid = $T['NewSystemId'] = $SR1['id']; 
        $Ref = $SR1['Ref'];
        $N = $SR1;
        $OldSid = $SR2['id'];
        $OldN = $SR2;
      }

      if ($Fid) {
        $FS = Get_FactionSystemFS($Fid,$Sid);
        if (!isset($FS['id']) || $FS['Scan'] < ($ShipScanLevel?5:1)) { // TODO Nebula
          $SP = ['FactionId'=>$Fid, 'Sys'=> $Sid, 'Scan'=>($ShipScanLevel?5:1), 'Neb'=>$ShipNebScanLevel, 'Turn'=>$GAME['Turn']];
          Insert_db('ScansDue', $SP);
        } 
        
        $FL = Get_FactionLinkFL($Fid,$Lid);
        $FL['Known'] = 1;
        Put_FactionLink($FL);
      
        $pname = System_Name($N,$Fid);
      }
      
//      $N = Get_System($T['NewSystemId']);
      $EndLocs = Within_Sys_Locs($N);
      $T['SystemId'] = $T['NewSystemId'];
      $T['WithinSysLoc'] = $T['NewLocation'];
//      SKLog("Moved to $pname along " . $LinkLevels[$L['Level']]['Colour']. " link #$Lid to " . $EndLocs[$T['NewLocation']]); 
      if ($Fid) TurnLog($Fid,$T['Name'] . " has moved from " . System_Name($OldN,$Fid) . " along <span style='color:" . $LinkLevels[$L['Level']]['Colour'] . ";'>link #$Lid </span>to $pname " . 
        ($T['NewLocation'] > 2?( " to " . $EndLocs[$T['NewLocation']]): ""),$T); 
//    $T['LinkId'] = 0;
      $T['Instruction'] = 0;
      Put_Thing($T);
    } else if ( $T['WithinSysLoc'] != $T['NewLocation'] && $T['NewLocation']>1) {
      $T['WithinSysLoc'] = $T['NewLocation'];
      $N = Get_System($T['SystemId']);
      $Sid = $T['SystemId'];
      $Fid = $T['Whose'];

      $pname = System_Name($N,$Fid);

      $EndLocs = Within_Sys_Locs($N);
//      SKLog("Moved to " . $EndLocs[$T['NewLocation']] . " within $pname"); 
      if ($Fid) TurnLog($Fid,$T['Name'] . " moved to " . $EndLocs[$T['NewLocation']] .  " within $pname",$T); 
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
  $Things = Get_Things_Cond(0,"(BuildState=2 OR BuildState=3) AND LinkId=-3"); // -4 has been converted on load to -3
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

function GroundCombat() {
  GMLog("Ground Combat is currently Manual<p>");
  return 1;
}


function DevastationSelection() {
  GMLog("<h2>Please mark those worlds with combat on</h2>");
  $_REQUEST['CONFLICT'] = 1; // Makes WorldList think its part of turn processing
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
        TurnLog($Fid,$LogT);
        SKLog($LogT,1);      
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

function ProjectProgress() {
  // Mark progress on all things, if finished change state appropriately 
//  echo "Project Progress is currently Manual<p>";  
  global $GAME;

  $ProjTypes = Get_ProjectTypes();
  $Projects = Get_Projects_Cond("Status=1");
  $DistTypes = Get_DistrictTypes();

  foreach ($Projects as $P) {
    if ($P['LastUpdate'] >= $GAME['Turn']) continue;
    GMLog("Updating project " . $P['id'] . " " . $P['Name'] . "<br>");

    
    $PT = $ProjTypes[$P['Type']];
    if ($PT['Category'] & 16) { // Construction
      $Fact = Get_Faction($P['FactionId']);
      $MaxActs = Has_Tech($P['FactionId'],3);  

      $H = Get_ProjectHome($P['Home']);
      if (!isset($H['ThingType'])) {
        GMLog("<b>Confused state for project " . $P['id'] . "</b><p>");
        $H['Skip'] = 1;
        continue;  // Remove things without districts                
      }
      switch ($H['ThingType']) {
        case 1: // Planet
          $PH = Get_Planet($H['ThingId']);
          if (($PH['Type'] != $Fact['Biosphere']) && ($PH['Type'] != $Fact['Biosphere2']) && ($PH['Type'] != $Fact['Biosphere3'])) $MaxActs-=1;
          break;
        case 2: // Moon
          $PH = Get_Moon($H['ThingId']);
          if (($PH['Type'] != $Fact['Biosphere']) && ($PH['Type'] != $Fact['Biosphere2']) && ($PH['Type'] != $Fact['Biosphere3'])) $MaxActs-=1;
          break;
        case 3: // Thing
          $PH = Get_Thing($H['ThingId']);
          break;
      }
    } else if ($PT['Category'] & 32) { // Deep SPace - TODO
    
    } else if ($PT['Category'] > 32) { // Intelligence -TODO
    
    } else { // District based 
      $H = Get_ProjectHome($P['Home']);
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
            $ORY = 0;
            foreach($DistTypes as $DT) if ($DT['Name'] == 'Orbital Repair') $ORY = $DT['id'];
            $Dists = [$ORY=>['HostType'=>3,'HostId'=>$PH['id'],'Type'=>$ORY,'Number'=>1, 'id'=>-1]];
            $NoC = 1;
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
    $Acts = min($MaxActs,$P['ProgNeeded']-$P['Progress']);
    if (preg_match('/Research/',$PT['Name'],$mtch) && Has_Trait($P['FactionId'],'Built for Construction and Logistics')) {
      $TechId = $P['ThingType'];
      $Tech = Get_Tech($TechId);
      if ($Tech['PreReqTech'] == 1 || $TechId == 1) {
        $FreeRush = min(1,$Acts,$P['ProgNeeded']-$P['Progress']-$Acts-$Bonus);
        if ($FreeRush) {
          TurnLog($P['FactionId'],'Free Rush of ' . $P['Name'] . " by $FreeRush ");
        }
      } 
    }
    if (isset($TurnStuff['Rush'])) {
      $Rush = min($TurnStuff['Rush'],$Acts,$P['ProgNeeded']-$P['Progress']-$Acts-$Bonus-$FreeRush);
      if ($Rush) {
        if (Spend_Credit($P['FactionId'],$Rc = (Rush_Cost($P['FactionId'])*$Rush), 'Rushing ' . $P['Name'] . " By $Rush")) {
          TurnLog($P['FactionId'],'Rushing ' . $P['Name'] . " by $Rush  Cost: " . Credit() . " $Rc");
        } else {
          TurnLog($P['FactionId'],'Not enough Credits to Rush: ' . $P['Name']);
        }
      }
    }

//echo "Acts . $Acts<br>";        
    $P['Progress'] = min($P['ProgNeeded'], $P['Progress']+$Acts+$Rush+$Bonus+$FreeRush);
    TurnLog($P['FactionId'],"Progressing " . $P['Name'] . " by " . ($Acts+$Rush+$Bonus+$FreeRush));
    $P['LastUpdate'] = $GAME['Turn'];
    Put_Project($P); // Note completeion is handled later in the turn sequence
  }
  
  return 1;
}

function InstructionsProgress() {
  global $ThingInstrs;
  $Things = Get_Things_Cond(0,"Instruction>0");
  $NeedColStage2 = 0;
  $Facts = Get_Factions();
  
  foreach ($Things as $T) {
    $N = Get_System($T['SystemId']);
    $Tid = $T['id'];
    switch ($ThingInstrs[$T['Instruction']]) {
      case 'Colonise':
        $Prog = Has_Tech($T['Whose'], 'Planetary Construction');
        $Mods = Get_ModulesType($Tid, 10);
        if ($Prog*$Mods[0]['Number'] == 0) {
          GMLog("Colonisation by <a href=ThingEdit.php?id=$Tid>" . $T['Name'] . "Has zero progress - Tell Richard");
        }
        $T['Progress'] = min($T['ActionsNeeded'],($T['Progress']+$Prog*$Mods[0]['Number']));
        Put_Thing($T);
        break;
      case 'Make Outpost':
      case 'Make Asteroid Mine':
      case 'Make Minefield':
      case 'Make Orbital Repair Yard':
      case 'Build Space Station':
      case 'Expand Space Station' :
      case 'Make Deep Space Sensor':
      case 'Make Advanced Asteroid Mine':
      case 'Build Stargate':
      case 'Make Planet Mine':
      case 'Construct Command Relay Station':
      case 'Repair Command Node': // Not coded yet
        $Prog = Has_Tech($T['Whose'],'Deep Space Construction');
        $Mods = Get_ModulesType($Tid, 3);
//var_dump($T);exit;
//var_dump($T['Progress'], $Prog, $Mods[0], $T['ActionsNeeded']);
//echo "<br>" . $Prog*$Mods[0]['Number'] . "<p>";
        $T['Progress'] = min($T['ActionsNeeded'],($T['Progress']+$Prog*$Mods[0]['Number']));
//var_dump($T['Progress']); exit;
        Put_Thing($T);
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
            $FA['Progress'] = min($FA['Progress']+$Pro, $A['AnomalyLevel']);
            Gen_Put('FactionAnomaly',$FA);
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
                $FA['Progress'] = min($FA['Progress']+$Pro, $A['AnomalyLevel']);
                Gen_Put('FactionAnomaly',$FA);
                $T['ProjectId'] = $Aid;
                TurnLog($Fid,$T['Name'] . " did $Pro towards completing anomaly " . $A['Name'] . " now at " . $FA['Progress'] . " / " . $A['AnomalyLevel'],$T);
              }
              break 2;
            }
          }
          TurnLog($Fid,$T['Name'] . " is supposed to be analysing an anomaly - but there isn't one",$T);                    
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


function ProjectsComplete() {
//  echo "Projects Complete is currently Manual<p>";  

  global $GAME,$GAMEID;

  $ProjTypes = Get_ProjectTypes();
  $Projects = Get_Projects_Cond("Status=1 AND Progress=ProgNeeded");
  foreach ($Projects as $P) {
    GMLog("Completing project " . $P['id'] . " " . $P['Name'] . "<br>");
    $P['Status'] = 2;
    $P['TurnEnd'] = $GAME['Turn'];
    Put_Project($P);
    
    $Fid = $P['FactionId'];

    $PT = $ProjTypes[$P['Type']];
    switch ($PT['Name']) {
    
    case 'Construction':
    case 'Grow District':
      $H = Get_ProjectHome($P['Home']);
      if (!isset($H['ThingType'])) {
        GMLog("Project " . $P['id'] . " Does not have a valid home<br>");
        break;
      }
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
          TurnLog($P['FactionId'],'Project ' . $P['Name'] . " is complete");         
          break 2;
        }
      }
      $D = ['HostType'=>$H['ThingType'], 'HostId'=>$PH['id'], 'Type'=>$P['ThingType'], 'Number'=>1, 'GameId' => $GAMEID];
      Put_District($D);
      TurnLog($P['FactionId'],'Project ' . $P['Name'] . " is complete");
      break;
      
    case 'Research Planetary Construction':
    case 'Research Core Technology':
    case 'Research Supplemental Technology':
    case 'Research Ship Construction':
    case 'Research Supplemental ship Tech':
    case 'Research Military Organisation':
    case 'Research Supplemental Army Tech':
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
          TurnLog($P['FactionId'],'Project ' . $P['Name'] . " is complete");
          break;
        } else {
          SKLog( "Project to " . $P['Name'] . " already have level " . $CTech['Level'] . " See <a href=ProjEdit.php?id=" . $P['id'] . ">Project</a>", 1);
          TurnLog($P['FactionId'],'Project ' . $P['Name'] . " is complete");
          break;          
        }
      } else if ($CTech['Level'] == 0) { // Supp
        $CTech['Level'] = 1;
        Put_Faction_Tech($CTech);
        TurnLog($P['FactionId'],'Project ' . $P['Name'] . " is complete");
        break;
      } else {
        SKLog( "Project to " . $P['Name'] . " already have". " See <a href=ProjEdit.php?id=" . $P['id'] . ">Project</a>", 1);
        TurnLog($P['FactionId'],'Project ' . $P['Name'] . " is complete");
      }
      break;
      
    case 'Re-equip and Reinforce':    

    case 'Refit and Repair': 
      if ($P['ThingId']) {
        $T = Get_Thing($P['ThingId']);
        RefitRepair($T);
        TurnLog($P['FactionId'], $T['Name'] . " has been " . $PT['Name'] . "ed",$T);        
      }
      if ($P['ThingId2']) {
        $T = Get_Thing($P['ThingId2']);
        RefitRepair($T);
        TurnLog($P['FactionId'], $T['Name'] . " has been " . $PT['Name'] . "ed",$T);        
      }
      break;
    
    case 'Construct Ship':
      $T = Get_Thing($P['ThingId']);
      $T['BuildState'] = 2; // Shakedown
      TurnLog($P['FactionId'], $T['Name'] . " has been lanched and will now start its shakedown cruise",$T);              
      Calc_Scanners($T);
      $T['ProjectId'] = 0;
      Put_Thing($T);
      break;

    case 'Train Army':
    case 'Train Agent':
      $T = Get_Thing($P['ThingId']);
      $T['BuildState'] = 3; // Complete
      TurnLog($P['FactionId'], $T['Name'] . " has been completed",$T);              
      $T['ProjectId'] = 0;
      Put_Thing($T);
      break;
     
    case 'Share Technology':
      $FFact = Get_Faction($P['FactionId']);
      $Tech = Get_Tech($P['ThingType']);
      $Level = $P['Level'];
      $Xfr2 = $P['ThingId'];
      $XFact = Get_Faction($Xfr2);
      $Have = Has_Tech($Xfr2,$Tech['id']);
      if ($Tech['Cat'] == 0) {
        if ($Have >= $Level) {
          TurnLog($P['FactionId'], "You tried to share " . $Tech['Name'] . " at level $Level. with " . $XFact['Name'] . " They already know it.");                      
          TurnLog($Xfr2, $FFact['Name'] . " tried to share " . $Tech['Name'] . " at level $Level.  With you - you already have it at level $Have.");
        } else if ($Have == $Level-1) {
          $CTech = Get_Faction_TechFT($Xfr2 ,$Tech['id']);
          $CTech['Level'] = $Level;
          Put_Faction_Tech($CTech);
          TurnLog($P['FactionId'], "Your have shared " . $Tech['Name'] . " at level $Level.  with " . $XFact['Name']);                      
          TurnLog($Xfr2, $FFact['Name'] . " has shared " . $Tech['Name'] . " at level $Level.  With you.");
        } else if (0 ) { // Learn lower level option
          $CTech = Get_Faction_TechFT($Xfr2 ,$Tech['id']);
          $CTech['Level'] = $Have+1;
          Put_Faction_Tech($CTech);
          TurnLog($P['FactionId'], "You tried to share " . $Tech['Name'] . " at level $Level. with " . $XFact['Name'] . 
             " They only had it at level $Have.  They learnt level " . ($Have+1));                      
          TurnLog($Xfr2, $FFact['Name'] .  " tried to share " . $Tech['Name'] . " at level $Level with you. You only have it at level $Have  so learnt it at level " . ($Have+1));
        } else {
          TurnLog($P['FactionId'], "You tried to share " . $Tech['Name'] . " at level $Level. with " . $XFact['Name'] . 
             " They only had it at level $Have - they don't understand what you sent");
          TurnLog($Xfr2, $FFact['Name'] .  " tried to share " . $Tech['Name'] . " at level $Level with you. You only have it at level $Have so learnt nothing");        
        }
      } else { // Supp techs
        $PRHave = Has_Tech($Xfr2,$Tech['PreReqTech']);
        if ($Have) {
          TurnLog($P['FactionId'], "You tried to share " . $Tech['Name'] . " with " . $XFact['Name'] . " They already know it.");                      
          TurnLog($Xfr2, $FFact['Name'] . " tried to share " . $Tech['Name'] . " with you - you already have it.");
        } else if ($PRHave >= $Tech['PreReqLevel']) {
          $CTech = Get_Faction_TechFT($Xfr2 ,$Tech['id']);
          $CTech['Level'] = $Level;
          Put_Faction_Tech($CTech);
          TurnLog($P['FactionId'], "Your have shared " . $Tech['Name'] . " with " . $XFact['Name']);                      
          TurnLog($Xfr2, $FFact['Name'] . " has shared " . $Tech['Name'] . " with you.");
        } else {
          TurnLog($P['FactionId'], "You tried to share " . $Tech['Name'] . " with " . $XFact['Name'] . " They don't understand what you sent");
          TurnLog($Xfr2, $FFact['Name'] .  " tried to share " . $Tech['Name'] . " with you. You don't understand it");        
        }
      }
      break;
        
    case 'Analyse':
      $Fact = Get_Faction($Fid);
      TurnLog($Fid, "You have conpleted " . $P['Name'] . " look at your turn response from the GM to see what you learnt");
      SKLog($Fact['Name'] . " has completed a level " . $P['Level'] . " analyse project called " . $P['Name'] . ".  Please give the results in the player", 1);
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
      TurnLog($P['FactionId'],"A warp gate has been made for " . $PH['Name']);

      break;

    case 'Decipher Alien Language':
    case 'Rebuild and Repair':  
    case 'Grow Modules' :    
      SKLog("A project to " . $PT['Name'] . " has completed, this is not automated yet.  See <a href=ProjEdit.php?id=" . $P['id'] . ">Project</a>",1);
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
    case 'Build Advanced Asteroid Mining Facility':
    case 'Unknown' :

    default:
      SKLog("A project to " . $PT['Name'] . " has completed, this is not automated yet.  See <a href=ProjEdit.php?id=" . $P['id'] . ">Project</a>",1);
    }
  }
  
  return 1;
}

function InstructionsComplete() {
  global $ThingInstrs,$GAME;
  $Things = Get_Things_Cond(0,"Instruction>0 AND Progress>=ActionsNeeded ");
  $NeedColStage2 = 0;
  $Facts = Get_Factions();
  $TTNames = Thing_Types_From_Names();
  
  foreach ($Things as $T) {
    $N = Get_System($T['SystemId']);
    $Tid = $T['id'];
    switch ($ThingInstrs[$T['Instruction']]) {
    case 'Colonise':
      $P = Get_Planet($T['Spare1']);
      $Who = $T['Whose'];
      if ($N['Control'] != $Who) {
        if ($N['Control'] == 0) {
          $N['Control'] = $Who;
          Put_System($N);
        }
      }
       
      $D = ['HostType' =>1, 'HostId'=> $P['id'], 'Type'=> $T['Dist1'], 'Number'=>1, 'GameId'=>$GAME['id'], 'TurnStart'=>$GAME['Turn']];
      if ($D['Type'] == 0) $D['Type'] = 1;
      Put_District($D);
      if (Get_ModulesType($Tid,27) && $T['Dist2']) {
        $D1 = ['HostType' =>1, 'HostId'=> $P['id'], 'Type'=> $T['Dist2'], 'Number'=>1, 'GameId'=>$GAME['id'], 'TurnStart'=>$GAME['Turn']];
        Put_District($D1);
      }


      TurnLog($Who,$P['Name'] . " on " . $N['Ref'] . " has been colonised");
      SKLog($P['Name'] . " on " . $N['Ref'] . " has been colonised by " . $Facts[$Who]['Name'],1);  // TODO Check for any named chars and offload
       
      $Have = Get_Things_Cond(0," (LinkId<0 AND SystemId=$Tid ");
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
           if ($FA['Progress'] >= $A['AnomalyLevel']) {
             $FA['State'] = 2;
             Gen_Put('FactionAnomaly',$FA);          
             TurnLog($T['Whose'], $T['Name'] . " Anomaly study on " . $A['Name'] . " has been completed - See sperate response from the GMs for what you get");
             GMLog($Facts[$Fid]['Name'] . " has completed anomaly study : <a href=AnomalyEdit.php?id=$Aid>" . $A['Name'] . "</a> has been completed - give them the reward.");
             $T['ProjectId'] = 0;
           }
         } else {
           continue 2;
         }
       }
     
       break;
       
     case 'Make Outpost':
       $NT = ['GameId'=>$GAME['id'], 'Type'=> $TTNames['Outpost'], 'Level'=> 1, 'SystemId'=>$T['SystemId'], 'Whose'=>$T['Whose'], 'BuildState'=>3, 'TurnBuilt'=>$GAME['Turn'],
              'Name'=>$T['MakeName']];
       Put_Thing($NT);
       $N = Get_System($T['SystemId']);
       $Who = $T['Whose'];
       if ($N['Control'] != $Who) {
         $N['Control'] = $Who;
         Put_System($N);
       }
       TurnLog($Who,"An outpost has been made in " . $N['Ref']);
       SKLog($N['Ref'] . " is now controlled by " . $Facts[$Who]['Name'],1);
       break;
       
     case 'Make Asteroid Mine':
       $NT = ['GameId'=>$GAME['id'], 'Type'=> $TTNames['Asteroid Mine'], 'Level'=> 1, 'SystemId'=>$T['SystemId'], 'Whose'=>$T['Whose'], 'BuildState'=>3, 'TurnBuilt'=>$GAME['Turn'],
              'Name'=>$T['MakeName']];
       Put_Thing($NT);
       $N = Get_System($T['SystemId']);
       $Who = $T['Whose'];
       TurnLog($Who,"An Asteroid Mine has been made in " . $N['Ref']);
      break;

     case 'Make Minefield':
       $NT = ['GameId'=>$GAME['id'], 'Type'=> $TTNames['Minefield'], 'Level'=> 1, 'SystemId'=>$T['SystemId'], 'WithinSysLoc'=> $T['WithinSysLoc'], 'Whose'=>$T['Whose'], 
              'BuildState'=>3, 'TurnBuilt'=>$GAME['Turn'], 'Name'=>$T['MakeName']];
       Put_Thing($NT);
       $N = Get_System($T['SystemId']);
       $Who = $T['Whose'];
       TurnLog($Who,"An Asteroid Mine has been made in " . $N['Ref']);
       break;

     case 'Make Orbital Repair Yard':
       $NT = ['GameId'=>$GAME['id'], 'Type'=>  $TTNames['Orbital Repair Yards'], 'Level'=> 1, 'SystemId'=>$T['SystemId'], 'WithinSysLoc'=> $T['WithinSysLoc'], 
              'Whose'=>$T['Whose'], 'BuildState'=>3, 'TurnBuilt'=>$GAME['Turn'], 'Name'=>$T['MakeName']];
       Put_Thing($NT);
       $N = Get_System($T['SystemId']);
       $Who = $T['Whose'];
       TurnLog($Who,"An Orbital Repair Yard has been made in " . $N['Ref']);
       break;

     case 'Build Space Station':
       $NT = ['GameId'=>$GAME['id'], 'Type'=> $TTNames['Space Station'], 'Level'=> 1, 'SystemId'=>$T['SystemId'], 'WithinSysLoc'=> $T['WithinSysLoc'], 
              'Whose'=>$T['Whose'], 'BuildState'=>3, 'TurnBuilt'=>$GAME['Turn'], 'MaxDistricts'=>$T['Dist1'], 'Name'=>$T['MakeName']];
       $Sid = Put_Thing($NT);
       $D = ['HostType' =>3, 'HostId'=> $Sid, 'Type'=> $T['Dist2'], 'Number'=>1, 'GameId'=>$GAME['id'], 'TurnStart'=>$GAME['Turn']];
       Put_District($D);
       
       $N = Get_System($T['SystemId']);
       $Who = $T['Whose'];
       TurnLog($Who,"A Space Station has been made in " . $N['Ref']);
       SKLog("A space station has been made in " . $N['Ref'] . " by " . $Facts[$Who]['Name'],1);
       break;

     case 'Expand Space Station' :
       $SS = Get_Things_Cond($Fid,"Type=" . $TTNames['Space Station'] . " OR Type=AND SystemId=" . $T['Systemid'] . " AND BuildState=3");
       if (empty($SS)) {
         TurnLog($Who,"There is not a Space Station here to expand in " . $N['Ref']);
         SKLog("There is not a space station to extend in " . $N['Ref'] . " by " . $Facts[$Who]['Name'],1);
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
       $Who = $T['Whose'];
       TurnLog($Who,"A Deep Space Sensor has been made in " . $N['Ref']);
       break;

     case 'Make Advanced Asteroid Mine':
       $NT = ['GameId'=>$GAME['id'], 'Type'=> $TTNames['Asteroid Mine'], 'Level'=> 2, 'SystemId'=>$T['SystemId'], 
              'Whose'=>$T['Whose'], 'BuildState'=>3, 'TurnBuilt'=>$GAME['Turn'], 'Name'=>$T['MakeName']];
       Put_Thing($NT);
       $N = Get_System($T['SystemId']);
       TurnLog($Who,"An Advanced Asteroid Mine has been made in " . $N['Ref']);
       break;

     case 'Build Stargate':
       SKLog("A stargate has been made in " . $N['Ref'] . " by " . $Facts[$Who]['Name'] . "Not Automated...",1);     
       break;

     case 'Make Planet Mine':
       $NT = ['GameId'=>$GAME['id'], 'Type'=> $TTNames['Planet Mine'], 'Level'=> 1, 'SystemId'=>$T['SystemId'], 
              'Whose'=>$T['Whose'], 'BuildState'=>3, 'TurnBuilt'=>$GAME['Turn'], 'Name'=>$T['MakeName']]; // TODO add withinsysloc
       Put_Thing($NT);
       $N = Get_System($T['SystemId']);
       TurnLog($Who,"A Planet Mine has been made in " . $N['Ref']);
       SKLog("A Planet Mine has been setup in " . $N['Ref'] . " by " . $Facts[$Who]['Name'] . ".  Tell Richard to set the right withinsysloc",1);
       break;

     case 'Construct Command Relay Station':
       $NT = ['GameId'=>$GAME['id'], 'Type'=> $TTNames['Command Relay Station'], 'Level'=> 1, 'SystemId'=>$T['SystemId'], 'WithinSysLoc'=> $T['WithinSysLoc'], 
              'Whose'=>$T['Whose'], 'BuildState'=>3, 'TurnBuilt'=>$GAME['Turn'], 'Name'=>$T['MakeName']];
       Put_Thing($NT);
       $N = Get_System($T['SystemId']);
       $Who = $T['Whose'];
       TurnLog($Who,"A Command Relay Station has been made in " . $N['Ref']);
       break;
      
     case 'Repair Command Node': // Not coded yet
       $NT = ['GameId'=>$GAME['id'], 'Type'=> $TTNames['Beta Node'], 'Level'=> 1, 'SystemId'=>$T['SystemId'], 'WithinSysLoc'=> $T['WithinSysLoc'], 
              'Whose'=>$T['Whose'], 'BuildState'=>3, 'TurnBuilt'=>$GAME['Turn'], 'Name'=>$T['MakeName']];
       Put_Thing($NT);
       
       $D = ['HostType' =>3, 'HostId'=> $NT['id'], 'Type'=> $T['Dist1'], 'Number'=>1, 'GameId'=>$GAME['id'], 'TurnStart'=>$GAME['Turn']];
       if ($D['Type'] == 0) $D['Type'] = 1;
       Put_District($D);

       TurnLog($Who,"A Command Node has been repaired " . $N['Ref']);
       SKLog("A Command Node has been repaired in " . $N['Ref'] . " by " . $Facts[$Who]['Name'] . ".  Tell Richard to set the right stuff up",1);
       break;

     case 'Build Planetary Mine':
       $P = Get_Planet($T['Spare1']);
       $Who = $T['Whose'];
       
       $D = ['HostType' =>1, 'HostId'=> $P['id'], 'Type'=> 9, 'Number'=>1, 'GameId'=>$GAME['id'], 'TurnStart'=>$GAME['Turn']];
       if ($D['Type'] == 0) $D['Type'] = 1;
       Put_District($D);

       $T['BuildState'] = 4;      
       TurnLog($Who,$P['Name'] . " on " . $N['Ref'] . " has plantary mining established");
       SKLog($P['Name'] . " on " . $N['Ref'] . " has plantary mining by " . $Facts[$Who]['Name'],1);
       break; // The making homes and worlds in a later stage completes the colonisation I hope
       
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
  $Scans = Gen_Get_Cond('ScansDue'," Turn=" . $GAME['Turn'] . " ORDER BY FactionId,Sys,Scan DESC");
  
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
      GMLog("<table border><tr><td>Faction<td>Where<td>Scan Level<td>Control\n");
      if (Access('God')) echo "<tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea>";  
      Register_AutoUpdate('ScansDue',0);
      $Started = 1;
    }

    $N = Get_System($S['Sys']);
    GMLog("<tr><td>" . $Facts[$Fid]['Name'] . "<td>" . $N['Ref'] . "<td>" . fm_radio('',$SurveyLevels,$S,'Scan','',0,'',"Scan:$SSid") . 
      ($N['Control'] ? ( "<td style='background:" . $Facts[$N['Control']]['MapColour'] . ";'>" . $Facts[$N['Control']]['Name'])  : '<td>None') );
    $LastSys = $Sid;
  }
  if ($Started) {
    GMLog("</table>\n");
    GMLog("<h2><a href=TurnActions.php?ACTION=StageDone&Stage=CheckSurveyReports&=55>When Happy click here</a></h2>");
    dotail();
  }
  return 1;
}


function GiveSurveyReports() {
  global $GAME;
  $Scans = Gen_Get_Cond('ScansDue'," Turn=" . $GAME['Turn'] . " ORDER BY FactionId,Sys");
  
  $Facts = Get_Factions();
  $Started = 0;
  foreach($Scans as $S) {

    $Fid = $S['FactionId'];
    $FS = Get_FactionSystemFS($Fid,$S['Sys']);

    
    if ($FS['ScanLevel'] >= $S['Scan']) continue;
    $Sys = $S['Sys'];
    $N = Get_System($Sys);
    $New = !isset($FS['id']);
    $FS['ScanLevel'] = $S['Scan'];
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
          if ( GameFeature('MissedAnomalies',0)) GMLog($Facts[$T['Whose']]['Name'] . " Just missed spotting an anomaly in " . $Systems[$Sid] . " by one sensor level on the " . $T['Name']);
          $LastAn = $Aid;
          continue;
        }
*/
        
        $Tid = $T['id'];
        GMLog("<tr><Td>" . $Facts[$T['Whose']]['Name'] . "<td>" . $Systems[$Sid] . "<td><a href=AnomalyEdit.php?id=$Aid>" . $A['Name'] . "</a><td>" .  fm_checkbox('',$_REQUEST,"Prevent$Tid"));
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
  
  $Anoms = Gen_Get_Cond('Anomalies',"GameId=$GAMEID ORDER BY SystemId, ScanLevel");
  foreach($Anoms as $A) {
    $Aid = $A['id'];
    $Sid = $A['SystemId'];
    $ScanNeed = $A['ScanLevel'];
    
    $Things = Get_Things_Cond(0,"SystemId=$Sid AND SensorLevel>=$ScanNeed ORDER BY Whose, SensorLevel DESC");
    $LastWho = 0;
    $LastAn = 0;
    foreach($Things as $T) {
// if ($Aid == 45) echo "Checking " . $T['id'] . "<br>";
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
                
          GMLog($Facts[$T['Whose']]['Name'] . " have just spotted anomaly: <a href=AnomalyEdit.php?id=$Aid>" . $A['Name'] . "</a> in " . $Systems[$Sid] . " from the " . $T['Name'] );
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

  GMLog("All Anomalies checked<p>");
  return 1;
}


function MilitiaArmyRecovery() {
//  GMLog("Militia Recovery is currently Manual<p>");
//  GMLog("Also Self Repair Armour<p>");
  
  $Things = Get_Things_Cond(0,"CurHealth>0 AND CurHealth!=OrigHealth");
  $TTypes = Get_ThingTypes();
  $MTypes = Get_ModuleTypes();
  $MTNs = Mod_Types_From_Names($MTypes);
  
  foreach ($Things as $T) {
    if ($TTypes[$T['Type']]['Name'] == 'Militia') {
      // if not in conflit, recovery some
      $Conflict = 0; 
      $Conf = Gen_Select("SELECT W.* FROM ProjectHomes PH, Worlds W WHERE PH.SystemId=" . $T['SystemId'] . " AND W.Home=PH.id AND W.Conflict=1");
      if ($Conf) $Conflict = $Conf[0]['Conflict'];
      if ($Conflict) continue;  // No recovery allowed
      
      $Dists = Gen_Get_Cond('Districts',"HostType=" . $T['Dist1'] . " AND HostId=" . $T['Dist2']);
      $Dcount = 0;
      foreach($Dists as $D) $Dcount += $D['Number'];
      $Rec = floor($Dcount/2)+1;
      $T['CurHealth'] = max($T['OrigHealth'], $T['CurHealth']+$Rec);
      Put_Thing($T);
      if ($T['Whose']) TurnLog($T['Whose'],$T['Name'] . " recovered $Rec health",$T);
      GMLog("<a href=ThingEdit.php?id=" . $T['id'] . ">" . $T['Name'] . "</a> recovered $Rec health",1);
      
    }
    
    if ($TTypes[$T['Type']]['Properties'] & THING_HAS_SHIPMODULES) {
      $Self = Get_ModulesType($T['id'],$MTNs['Self-Repairing Armour']);
      if (isset($Self[0])) {
        $Rep = $Self[0]['Number']*$Self[0]['Level']*2;
        $T['CurHealth'] = min($T['OrigHealth'],$T['CurHealth']+$Rep);
        Put_Thing($T);
        if ($T['Whose']) TurnLog($T['Whose'],$T['Name'] . " recovered $Rep health",$T);
        GMLog("<a href=ThingEdit.php?id=" . $T['id'] . ">" . $T['Name'] . "</a> recovered $Rep health",1);
      }
    }  
    
    if ($TTypes[$T['Type']]['Properties'] & THING_HAS_ARMYMODULES) {
      $Med = Get_ModulesType($T['id'],$MTNs['Medical Corps']);
      if (isset($Med[0])) {
        $Rep = $Self[0]['Number']*$Self[0]['Level']*2;
        $Conflict = 0; 
        $Conf = Gen_Select("SELECT W.* FROM ProjectHomes PH, Worlds W WHERE PH.SystemId=" . $T['SystemId'] . " AND W.Home=PH.id AND W.Conflict=1");
        if ($Conf) $Conflict = $Conf[0]['Conflict'];
        if (!$Conflict) $Rep*=2;
        $T['CurHealth'] = min($T['OrigHealth'],$T['CurHealth']+$Rep);
        Put_Thing($T);
        if ($T['Whose']) TurnLog($T['Whose'],$T['Name'] . " recovered $Rep health",$T);
        GMLog("<a href=ThingEdit.php?id=" . $T['id'] . ">" . $T['Name'] . "</a> recovered $Rep health",1);
      }
    }  
     
  }
  
  return 1;
}

function GenerateTurns() {
  GMLog("Generate Turns is currently Manual<p>");
  return 1;
}

function TidyUpMovements() {
  global $db,$GAMEID;
  
  $res = $db->query("UPDATE Things SET LinkId=0, LinkPay=0, LinkCost=0 WHERE LinkId>0 AND GameId=$GAMEID"); 
  
  // Check for lid <-1...
  $NotFin = Get_Things(0,"LinkId<-1");
  if ($NotFin) {
    echo "<h2 class=Err>These things have a broken load/unload still in place get Richard to fix</h2>";
    foreach ($NotFin as $T) {
      GMLog("<a href=ThingEdit.php?id=" . $T['id'] . ">" . $T['Name'] . " has a lid of " . $T['LinkId']);
    }
  }
  
  // Tidy 1 turn carry options
  $FFs = Gen_Get_Cond('FactionFaction','Props>0');
  foreach ($FFs as $F) {
    if (($F['Props'] &15) == 2) $F['Props'] = ($F['Props']&0xf0)+1;
    if ((($F['Props']>>4) &15) == 2) $F['Props'] = ($F['Props']&0x0f)+16;  
    Put_FactionFaction($F);  
  }
  
  // Tidy up 1 turn District Deltas
  $Ds = Gen_Get_Cond("Districts","Delta!=0");
  foreach($Ds as $D) {
    $D['Delta'] = 0;
    Put_District($D);
  }
  
  GMLog("Movements, 1 turn carry, district deltas Tidied Up<p>");  
  return 1;
}

function RecalcProjectHomes() {
  // Proj Homes, Worlds
  include_once("HomesLib.php");
  Recalc_Project_Homes('SKLog'); // in ThingLib - this is to add new project homes that have been created by colonisation etc.
  Recalc_Worlds();
  
  // Dynamic Systems
  
  $DSys = Gen_Get_Cond('Systems',"Flags>0");
  foreach($DSys as $N) {
    Dynamic_Update($N,1);
  }
  return 1;
}

function FinishTurnProcess() {
  global $GAME,$Sand;
  // Change faction state update turn number

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
  

  // TODO Send messages to discord ??
  return 1;
}

function Do_Turn() {
  global $Sand;  // If you need to add something, replace a spare if poss, then nothing breaks
  $Stages = ['Check Turns Ready', 'Spare', 'Spare','Start Turn Process', 'Save All Locations', 'Spare', 'Cash Transfers', 'Spare',
             'Spare', 'Pay For Stargates', 'Spare', 'Scientific Breakthroughs', 'Start Projects', 'Spare', 'Spare', 'Instuctions', 
             'Instuctions Stage 2', 'Spare', 'Spare', 'Spare', 'Spare', 'Spare', 'Spare', 'Spare', 
             'Agents Start Missions', 'Spare', 'Spare', 'Economy', 'Spare', 'Direct Moves', 'Load Troops', 'Spare', 
             
             'Ship Move Check','Ship Movements', 'Agents Move Check', 'Agents Movements', 
             'Meetups', 'Spare', 'Space Combat', 'Unload Troops', 
             'Orbital Bombardment', 'Spare', 'Ground Combat', 'Devastation Selection', 
             'Devastation', 'Project Progress', 'Instructions Progress', 'Spare', 
             'Espionage Missions Complete', 'Counter Espionage', 'Spare', 'Finish Shakedowns', 
             'Projects Complete', 'Instructions Complete', 'Spare', 'Check Survey Reports', 
             'Give Survey Reports', 'Check Spot Anomalies', 'Spot Anomalies', 'Militia Army Recovery', 
             'Generate Turns', 'Tidy Up Movements', 'Recalc Project Homes', 'Finish Turn Process'];

  $Coded =  ['Coded','No','No','Coded','Coded','No','Coded', 'No',
             'No','Coded','No','Coded','Partial','No','No','Coded',
             'Coded','No','No','No','No','No','No','No',
             'No','No','No','Coded','No','Coded','Coded','No',
             
             'Coded','Coded','Coded','Coded',
             'Coded','No', 'No','Coded',
             'No','No','No','Coded',
             'Coded','Coded', 'Coded', 'No',
             'No', 'No', 'No','Coded',
             'Partial','Coded', 'No','Partial (not nebula)',
             'Coded', 'Coded', 'Coded', 'Coded',
             'No','Coded','Coded','Coded?'];
  $Sand = Get_TurnNumber();
// var_dump($Sand);

  // Display progress chart
  // At each stage BEGIN TRANSACTION at end COMMIT TRANSACTION ??
  dostaffhead("Turn Processing");
  echo "<H1>Turn Processing</h1>";
// var_dump($_REQUEST);  
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
      $act = $Stages[$S];
      $act = preg_replace('/ /','',$act);
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
    $Prog*=2;
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
    if (Access('God')) echo "<tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea>";
    echo "</table></form><p>\n";
  }

// var_dump($Sand);
  Put_Turn($Sand);  // Probably redundant
}


// Start Here
  Do_Turn();

  dotail();

?>
