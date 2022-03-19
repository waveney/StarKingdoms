<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("PlayerLib.php");
  include_once("SystemLib.php");
  include_once("ProjLib.php");
  include_once("HomesLib.php");
  include_once("BattleLib.php");
      
  A_Check('GM');

// For logging turn processing events that need following up or long term record keeping, set e to echo to GM
function SKLog($text,$e=0) {
  global $Sand,$USER;
  $Sand['ActivityLog'] .= date("Y-m-d H:i:s - ") . $USER['Login'] . " - " . $text . "\n";  // Who?
  if ($e) echo $text . "<br>\n";
}

// Log to the turn text, and optionally aa history record of T
function TurnLog($Fid,$text,&$T=0) {
  global $GAME,$GAMEID;
  static $LF = [];
  if (!isset($LF[$Fid])) {
     if (!file_exists("Turns/$GAMEID/" . $GAME['Turn'])) mkdir("Turns/" . $GAMEID . "/" . $GAME['Turn'],0777,true);
     $LF[$Fid] = fopen("Turns/$GAMEID/" . $GAME['Turn'] . "/$Fid.txt", "a+");
  }
  fwrite($LF[$Fid],"$text\n");
  if ($T) $T['History'] .= "Turn#" . $GAME['Turn'] . ": " . $text . "\n";
}

function CheckTurnsReady() {
  global $PlayerStates,$PlayerState, $PlayerStateColours;
  $Factions = Get_Factions();
  $AllOK = 1;
  $coln = 0;
  
  echo "<div class=tablecont><table id=indextable border width=100% style='min-width:1400px'>\n";
  echo "<thead><tr>";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Faction</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Who</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Last Active</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>State</a>\n";

  echo "</thead><tbody>";

  foreach($Factions as $F) {
    $Fid = $F['id'];
    echo "<tr><td><a href=FactionEdit.php?F=$Fid>" . $F['Name'] . "</a>";
    echo "<td>" . $F['Player'];
    echo "<td style='background:" . $F['MapColour'] . ";'>"
         . (isset($F['LastActive']) && $F['LastActive']? date('d/m/y H:i:s',$F['LastActive']) :"Never");
    echo "<td <span style='background:" . $PlayerStateColours[$F['TurnState']] . "'>"  . $PlayerState[$F['TurnState']];
    if ($F['TurnState'] != 2) {
      $AllOK = 0;
    }
  }
  echo "</table></div>\n";
  if ($AllOK) return 1;
  echo "To proceed you must mark them as submitted<p>\n";
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
  echo "<br>All Factions marked as Turn Processing<p>\n";
  if (!file_exists("Turns/$GAMEID/" . $GAME['Turn'])) {
    $LF = mkdir("Turns/$GAMEID/" . $GAME['Turn'],0777,true);
  }
  
  echo "Started Turn Processing<p>";
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

  echo "Saved all Location data<p>";  
  return 1;
}

function CashTransfers() {
  global $GAME;
  
  $Facts = Get_Factions();
  $Facts[-1]['Name'] = "Other";
  $Bs = Get_BankingFT(0,$GAME['Turn']);
  
  foreach($Bs as $B) {
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
      if (Gain_Currency($Fid,$B['What'],-$B['Amount'],$B['YourRef'])) {
        TurnLog($B['FactionId'], "Transfered " . $B['Amount'] . " of " . $Currencies[$B['What']] . " for " . $B['YourRef'] . " to " . $Factions[$B['Recipient']]['Name']);
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
    }
  }
  
  echo "All Cash trasfered complete.<br>\n";
  return 1;
}

function PayForStargates() {
  echo "Pay For Stargates are currently Manual<p>";
  return 1;
}

function ScientificBreakthroughs() {
  echo "Scientific Breakthroughs is currently Manual<p>";
  return 1;

}

function StartProjects() {
  global $GAME;
//  echo "Start Projects are currently Manual<p>";
// Find all projects with 0 progress due to start this turn
// Pay costs, Status = Started  

  $ProjTypes = Get_ProjectTypes();
  $Projects = Get_Projects_Cond("Status=0 AND Costs!=0 AND TurnStart=" . $GAME['Turn']);
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
          Put_Project($P);
          continue;      
        }
        if (($T['SystemId'] != 0 && $T['SystemId'] != $Where[0])) {
//var_dump($Where,$T);
          $P['Status'] = 5; // Not Started
          TurnLog($P['FactionId'],'Not starting as not in same system: ' . $P['Name']);
          Put_Project($P);
          continue;     
        }
        if ($ProjTypes[$P['Type']]['Props'] & 16) { // Tight Location check
          if (($T['WithinSysLoc'] == $Where[1] || $T['WithinSysLoc'] == $Where[1]-100)) {       
            // OK
          } else {
            $P['Status'] = 5; // Not Started
            TurnLog($P['FactionId'],'Not starting as not at the same planet: ' . $P['Name']);
            Put_Project($P);
            continue;
          }
        }
      } // TODO 2nd thing for repair 
      if ($ProjTypes[$P['Type']]['Props'] & 4) { // Has can have a 2nd thing
        $Tid2 = $P['ThingId2'];
        if ($Tid2) {
          $T2 = Get_Thing($Tid2);
          if ($T2['SystemId'] != 0 && $T2['SystemId'] != $Where[0]) {
            $P['Status'] = 5; // Not Started
            TurnLog($P['FactionId'],'Not starting as not in same system: ' . $P['Name']);
            Put_Project($P);
            continue;     
          }
          if ($ProjTypes[$P['Type']]['Props'] & 16) { // Tight Location check
            if (($T['WithinSysLoc'] == $Where[1] || $T['WithinSysLoc'] == $Where[1]-100)) {       
              // OK
            } else {
              $P['Status'] = 5; // Not Started
              TurnLog($P['FactionId'],'Not starting as not at the same planet: ' . $P['Name']);
              Put_Project($P);
              continue;             
            }
          }
        }
      } 
    }
    if (Spend_Credit($P['FactionId'],$Cost,'Starting: ' . $P['Name'])) {
      $P['Status'] = 1; // Started
      TurnLog($P['FactionId'],'Starting ' . $P['Name'] . " Cost: " . Credit() . " $Cost");
      if (($ProjTypes[$P['Type']]['Props'] & 2) && (($ProjTypes[$P['Type']]['Props'] &16) ==0 )) { // Has a thing      
        if ($Tid) {
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
      }
    }
    Put_Project($P);
  }
  return 1;  
}

/*
$ThingInstrs = ['None','Colonise','Voluntary Warp Home','Decommision','Analyse Anomoly','Establish Embassy','Make Outpost','Make Asteroid Mine','Make Minefield',
                'Make Orbital Repair Yard','Build Space Station','Expand Space Station','Make Deep Space Sensor','Make Advanced Asteroid Mine','Build Stargate',
                'DSC Special'];
*/

function ColonisationInstuctions() { // And other Instructions
  global $ThingInstrs;
  $Things = Get_Things_Cond(0,"Instruction>0");
  $NeedColStage2 = 0;
  $Facts = Get_Factions();
  
  $PTs = Get_ProjectTypes();
  
  foreach ($Things as $T) {
    $N = Get_System($T['SystemId']);
    $Tid = $T['id'];
    // Mark new Instructions
    if ($T['Instruction'] != $T['CurInst']) {
      $T['CurInst'] = $T['Instruction'];
      $T['Progress'] = 0;
    }
    
    
    switch ($ThingInstrs[$T['Instruction']]) {
    case 'Colonise': // Colonise

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
          if ($P['Type'] == $FACTION['Biosphere']) {
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
          echo "<h2>Planet not found - Tell Richard</h2>";
          var_dump($T);
          exit;
        }
        [$P,$Acts] = array_shift($HabPs);
        $T['Spare1'] = $P['id'];
        $T['ActionsNeeded'] = $Acts;
      }
      
      TurnLog($T['Whose'],"The " . $T['Name'] . " is colonising " . $P['Name'] . " in " . $N['Ref'] ,$T);
      SKlog($Facts[$T['Whose']]['Name'] . " is starting to colonise " . $P['Name'] . " in " . $N['Ref']);
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
           echo "<p><a href=ThingEdit.php?id=$Tid>" . $T['Name'] . "</a> is warping out - Please Choose which gate:";
           echo fm_select($GLocs,$_REQUEST,'G',0,'',"G" . $T['id']);
           echo "<p>";
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
      $T['BuildState'] = 4;
      $T['SystemId'] = 0;
      $T['History'] .= "Decommissioned";
      $T['Instruction'] = 0;
      $cash = 10*$Lvl*Has_Tech($T['Whose'],'Ship Construction');
      TurnLog($T['Whose'], "The " . $T['Name'] . " has been decommisioned gaining you " . Credit() . $cash, $T);
      Spend_Credit($T['Whose'],-$cash,"Decommisioning " . $T['Name']);
      break;

    case 'Analyse Anomoly': // Anomoly
      break; // TODO
      
    case 'Establish Embassy': 
      if ($NeedColStage2 == 0) {
        echo "<form method=post action=TurnActions.php?ACTION=Process&S=16>";
        $NeedColStage2 = 1;
      }
      echo $Facts[$T['Whose']]['Name'] . " setting up an Embassy in " . $N['Ref'] . " - Allow? " . fm_YesNo("Emb$Tid",1, "Reason to reject") . "\n<br>";
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
      
    case 'Repair Command Node': // Not coded yet
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

function ColonisationInstuctionsStage2() { // And other Instructions
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
      TurnLog($T['Whose'],"You have created an Embassy in " . $N['Ref'] . " with the " . $OF['Name']);
      TurnLog($T['OtherFaction'],"An embassy to you has been setup by " . $Facts[$T['Whose']]['Name']);
      $T['Instruction'] = 0;
      $T['CurInst'] = 0;
      $T['Progress'] = 0;
      $T['MakeName'] = '';
      Put_Thing($T);
      break;
      
    default:
    break;
    }
  }
  return 1;
}

function StartAnomaly() {
  echo "Anomaly Studies are currently Manual<p>";
  return 1;
}

function AgentsStartMissions() {
  echo "Agents Start Missions	are currently Manual<p>";
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
    echo "Done Economy for " . $F['Name'] . "<br>";
    
  }
  return 1;
}

function LoadTroops() {
  echo "Load Troops	is currently Manual<p>";
  return 1;
}

function ShipMoveCheck($Agents=0) {  // Show all movements to allow for blocking
  global $GAME,$GAMEID;
  $LinkLevels = Get_LinkLevels();
  $Things = Get_AllThings();
  $Facts = Get_Factions();
  
  echo "<h2>These movements are planned - to stop one, tick the stop box and say why</h2>";
  echo "<form method=Post action=TurnActions.php?ACTION=Complete>" . fm_hidden('S',($Agents?34:32));
  echo "<table border><tr><td>Who<td>What<td>From<td>Link<td>To<td>Stop<td>Why Stopping\n";
  foreach ($Things as $T) {
    if (($T['Type'] == 5 && $Agents == 0) || ($T['Type'] != 5 && $Agents == 1) || $T['BuildState'] <2 || $T['BuildState'] > 3) continue;
    if ($T['LinkId'] && $T['NewSystemId'] != $T['SystemId'] ) {
      $Tid = $T['id'];
      $Lid = $T['LinkId']; 
            
      $L = Get_Link($Lid);

      $Fid = $T['Whose'];
      $SR1 = Get_SystemR($L['System1Ref']);
      $SR2 = Get_SystemR($L['System2Ref']);
      
      echo "<tr><td>" . $Facts[$Fid]['Name'] . "<td><a href=ThingEdit.php?id=$Tid>" . $T['Name']  . "<td>";
      if ($T['SystemId'] == $SR1['id']) {
         echo $L['System1Ref'] . "<td style=color:" . $LinkLevels[$L['Level']]['Colour'] . ";>#$Lid<td>" . $L['System2Ref'];
      } else {
         echo $L['System2Ref'] . "<td style=color:" . $LinkLevels[$L['Level']]['Colour'] . ";>#$Lid<td>" . $L['System1Ref'];      
      }
      echo "<td>" . fm_checkbox('',$_REQUEST,"Prevent$Tid") . fm_text1('', $_REQUEST,"Reason$Tid");
    }
  }
  echo "</table><input type=submit value='Click to Proceed'></form>\n";
  dotail();

}


function ShipMovements($Agents=0) {
  global $GAME,$GAMEID;
  // Foreach thing, do moves, generate list of new survey reports & levels, update "knowns" 

  if (!file_exists("Turns/" . $GAMEID . "/" . $GAME['Turn'])) $LF = mkdir("Turns/" . $GAMEID . "/" . $GAME['Turn'],0777,true);  
  
//  $PotS = fopen("Turns/" . $GAMEID . "/" . $GAME['Turn'] . "/ScansDue", "a+");
  $LinkLevels = Get_LinkLevels();
  $Things = Get_AllThings();
  $Facts = Get_Factions();
  
  foreach ($Things as $T) {
    if (($T['Type'] == 5 && $Agents == 0) || ($T['Type'] != 5 && $Agents == 1) || $T['BuildState'] <2 || $T['BuildState'] > 3) continue;
    $Tid = $T['id'];
    $Fid = $T['Whose'];
    
    if (isset($_REQUEST["Prevent$Tid"]) && $_REQUEST["Prevent$Tid"] ) {
      $Lid = $T['LinkId']; 
      $L = Get_Link($Lid);
      TurnLog($Fid,$T['Name'] . " was <b>unable to take link</b> <span style=color:" . $LinkLevels[$L['Level']]['Colour'] . ">#$Lid </span> beause of " . 
        (isset($_REQUEST["Reason$Tid"])? $_REQUEST["Reason$Tid"]:"Unknown reasons"), $T);       
      continue;
    }
    
    if ($T['LinkId'] && $T['NewSystemId'] != $T['SystemId'] ) {
      echo "Moving " . $T['Name'] . "<br>";

      $Lid = $T['LinkId']; 

      $ShipScanLevel = Scanners($T);
      $ShipNebScanLevel = NebScanners($T);
            
      $L = Get_Link($Lid);

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

      $FS = Get_FactionSystemFS($Fid,$Sid);
      if (!isset($FS['id']) || $FS['Scan'] < ($ShipScanLevel?5:1)) { // TODO Nebula
        $SP = ['FactionId'=>$Fid, 'Sys'=> $Sid, 'Scan'=>($ShipScanLevel?5:1), 'Neb'=>$ShipNebScanLevel, 'Turn'=>$GAME['Turn']];
        Insert_db('ScansDue', $SP);
      } 
      
      $pname = System_Name($N,$Fid);
      
//      $N = Get_System($T['NewSystemId']);
      $EndLocs = Within_Sys_Locs($N);
      $T['SystemId'] = $T['NewSystemId'];
      $T['WithinSysLoc'] = $T['NewLocation'];
//      SKLog("Moved to $pname along " . $LinkLevels[$L['Level']]['Colour']. " link #$Lid to " . $EndLocs[$T['NewLocation']]); 
      TurnLog($Fid,$T['Name'] . " has moved from " . System_Name($OldN,$Fid) . " along <span style='color:" . $LinkLevels[$L['Level']]['Colour'] . ";'>link #$Lid </span>to $pname " . 
        ($T['NewLocation'] > 2?( " to " . $EndLocs[$T['NewLocation']]): ""),$T); 
      $T['LinkId'] = 0;
      Put_Thing($T);
    } else if ( $T['WithinSysLoc'] != $T['NewLocation'] && $T['NewLocation']>1) {
      $T['WithinSysLoc'] = $T['NewLocation'];
      $N = Get_System($T['SystemId']);
      $Sid = $T['SystemId'];
      $Fid = $T['Whose'];

      $pname = System_Name($N,$Fid);

      $EndLocs = Within_Sys_Locs($N);
//      SKLog("Moved to " . $EndLocs[$T['NewLocation']] . " within $pname"); 
      TurnLog($Fid,$T['Name'] . " moved to " . $EndLocs[$T['NewLocation']] .  " within $pname",$T); 
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
  echo "Space Combat is currently Manual<p>";
  return 1;
}

function OrbitalBombardment() {
  echo "Orbital Bombardment is currently Manual<p>";
  return 1;
}

function GroundCombat() {
  echo "Ground Combat is currently Manual<p>";
  return 1;
}


function DevastationSelection() {
  echo "<h2>Please mark those worlds with combat on</h2>";
  $_REQUEST['CONFLICT'] = 1; // Makes WorldList think its part of turn processing
  include_once("WorldList.php");
//  echo "Devastation Selectionis currently Manual<p>";
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

  echo "Devastation is Complete<p>";
  return 1;

}

function ProjectProgress() {
  // Mark progress on all things, if finished change state appropriately 
//  echo "Project Progress is currently Manual<p>";  
  global $GAME;

  $ProjTypes = Get_ProjectTypes();
  $Projects = Get_Projects_Cond("Status=1");
  foreach ($Projects as $P) {
    if ($P['LastUpdate'] >= $GAME['Turn']) continue;
    echo "Updating project " . $P['id'] . " " . $P['Name'] . "<br>";

    
    $PT = $ProjTypes[$P['Type']];
    if ($PT['Category'] & 16) { // Construction
      $Fact = Get_Faction($P['FactionId']);
      $MaxActs = Has_Tech($P['FactionId'],3);  

      $H = Get_ProjectHome($P['Home']);
      switch ($H['ThingType']) {
        case 1: // Planet
          $PH = Get_Planet($H['ThingId']);
          if ($PH['Type'] != $Fact['Biosphere']) $MaxActs-=1;
          break;
        case 2: // Moon
          $PH = Get_Moon($H['ThingId']);
          if ($PH['Type'] != $Fact['Biosphere']) $MaxActs-=1; 
          break;
        case 3: // Thing
          $PH = Get_Thing($H['ThingId']);
          break;
      }
    } else if ($PT['Category'] & 32) { // Deep SPace - TODO
    
    } else if ($PT['Category'] > 32) { // Intelligence -TODO
    
    } else { // District based 
      $H = Get_ProjectHome($P['Home']);
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
        echo "Confused state for project " . $P['id'] . "<p>";
      }
    }

//echo "Maxacts . $MaxActs<br>";    

  // Find Project Home
  // Find Dists or Skill to base on
  // Set maxact from num dists or skill
    
    
    $TurnStuff = Get_ProjectTurnPT($P['id'],$GAME['Turn']);
    
    $Rush = 0;
    $Acts = min($MaxActs,$P['ProgNeeded']-$P['Progress']);
    if (isset($TurnStuff['Rush'])) {
      $Rush = min($TurnStuff['Rush'],$Acts,$P['ProgNeeded']-$P['Progress']-$Acts);
      if ($Rush) {
        if (Spend_Credit($P['FactionId'],$Rc = (Rush_Cost($P['FactionId'])*$Rush), 'Rushing ' . $P['Name'] . " By $Rush")) {
          TurnLog($P['FactionId'],'Rushing ' . $P['Name'] . " by $Rush  Cost: " . Credit() . " $Rc");
        } else {
          TurnLog($P['FactionId'],'Not enough Credits to Rush: ' . $P['Name']);
        }
      }
    }

//echo "Acts . $Acts<br>";        
    $P['Progress'] = min($P['ProgNeeded'], $P['Progress']+$Acts+$Rush);
    TurnLog($P['FactionId'],"Progressing " . $P['Name'] . " by " . ($Acts+$Rush));
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
      default: 
        break;
     }
   }

  return 1;
}

function EspionageMissionsComplete() {
  echo "Espionage Missions Complete	is currently Manual<p>";
  return 1;
}

function CounterEspionage() {
  echo "Counter Espionage is currently Manual<p>";
  return 1;
}

function FinishShakedowns() {
  // Move anything in shakedown to completed
  
  $Things = Get_Things_Cond(0," BuildState=2 ");

  foreach($Things as $T) {
    $T['BuildState'] = 3;
    TurnLog($T['FactionId'],$T['Name'] . " has finished it's Shakedown and is now ready for operations.");
    Put_Thing($T);
  }
  
  echo "Shakdowns finished<br>";
  return 1;
}


function ProjectsComplete() {
//  echo "Projects Complete is currently Manual<p>";  

  global $GAME,$GAMEID;

  $ProjTypes = Get_ProjectTypes();
  $Projects = Get_Projects_Cond("Status=1 AND Progress=ProgNeeded");
  foreach ($Projects as $P) {
    echo "Completing project " . $P['id'] . " " . $P['Name'] . "<br>";
    $P['Status'] = 2;
    $P['TurnEnd'] = $GAME['Turn'];
    Put_Project($P);
    
    $Fid = $P['FactionId'];

    $PT = $ProjTypes[$P['Type']];
    switch ($PT['Name']) {
    
    case 'Construction':
    case 'Grow District':
      $H = Get_ProjectHome($P['Home']);
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
          $Dists = Get_DistrictsT($H['ThingId']);
          break;
        }
      foreach($Dists as $D) {
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
      $Tid = $P['ThingType'];
      $CTech = Get_Faction_TechFT($Fid,$Tid);
      $Tech = Get_Tech($Tid);
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
        TurnLog($P['FactionId'], $T['Name'] . " has been " . $PT['Name'] . "ed");        
      }
      if ($P['ThingId2']) {
        $T = Get_Thing($P['ThingId2']);
        RefitRepair($T);
        TurnLog($P['FactionId'], $T['Name'] . " has been " . $PT['Name'] . "ed");        
      }
      break;
    
    case 'Construct Ship':
      $T = Get_Thing($P['ThingId']);
      $T['BuildState'] = 2; // Shakedown
      TurnLog($P['FactionId'], $T['Name'] . " has been lanched and will now start its shakedown cruise");              
      Calc_Scanners($T);
      Put_Thing($T);
      break;

    case 'Train Army':
    case 'Train Agent':
      $T = Get_Thing($P['ThingId']);
      $T['BuildState'] = 3; // Complete
      TurnLog($P['FactionId'], $T['Name'] . " has been completed");              
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
    case 'Decommission Ship':
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
  $Things = Get_Things_Cond(0,"Instruction>0 AND Progress>=ActionsNeeded");
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

       $T['BuildState'] = 4;      
       TurnLog($Who,$P['Name'] . " on " . $N['Ref'] . " has been colonised");
       SKLog($P['Name'] . " on " . $N['Ref'] . " has been colonised by " . $Facts[$Who]['Name'],1);
       break; // The making homes and worlds in a later stage completes the colonisation I hope
       
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

     case 'Make Orbital Repair Yards':
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


     default: 
       break;
     }
     $T['Instruction'] = 0;
     $T['Progress'] = 0;
     $T['CurInst'] = 0;
     $T['MakeName'] = '';
     Put_Thing($T);   
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
    if ($LastSys == $Sid) continue;
    $FS = Get_FactionSystemFS($Fid,$Sid);
    
//var_dump($FS);
    if ($FS['ScanLevel'] >= $S['Scan']) continue;
    if (!$Started) {
      echo "<h2>Please review these scans, mark lower as needed</h2>\n";
      echo "<table border><tr><td>Faction<td>Where<td>Scan Level\n";
      if (Access('God')) echo "<tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea>";  
      Register_AutoUpdate('ScansDue',0);
      $Started = 1;
    }

    $N = Get_System($S['Sys']);
    echo "<tr><td>" . $Facts[$Fid]['Name'] . "<td>" . $N['Ref'] . "<td>" . fm_radio('',$SurveyLevels,$S,'Scan','',0,'',"Scan:$Sid");
    $LastSys = $Sid;
  }
  if ($Started) {
    echo "</table>\n";
    echo "<h2><a href=TurnActions.php?ACTION=StageDone&S=56>When Happy click here</a></h2>";
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

function MilitiaArmyRecovery() {
  echo "Militia Recovery is currently Manual<p>";
  echo "Also Self Repair Armour<p>";
  return 1;
}

function GenerateTurns() {
  echo "Generate Turns is currently Manual<p>";
  return 1;
}

function TidyUpMovements() {
  global $db,$GAMEID;
  
  $res = $db->query("UPDATE Things SET LinkId=0 WHERE LinkId>0 AND GameId=$GAMEID"); 
  
  echo "Movements Tidied Up<p>";  
  return 1;
}

function RecalcProjectHomes() {
  include_once("HomesLib.php");
  Recalc_Project_Homes('SKLog'); // in ThingLib - this is to add new project homes that have been created by colonisation etc.
  Recalc_Worlds();
  return 1;
}

function FinishTurnProcess() {
  global $GAME;
  // Change faction state update turn number

  $Facts = Get_Factions();
  foreach ($Facts as $F) {
    $F['TurnState'] = 1;
    Put_Faction($F);
  }
  
  
  echo "<br>All Factions marked as Turn Planning<p>\n";

  $GAME['Turn'] ++;
  Put_Game($GAME);
  
  echo "Turn number incremented<p>\n";
  

  // TODO Send messages to discord ??
  return 1;
}

function Do_Turn() {
  global $Sand;  // If you need to add something, replace a spare if poss, then nothing breaks
  $Stages = ['Check Turns Ready', 'Spare', 'Spare','Start Turn Process', 'Save All Locations', 'Spare', 'Cash Transfers', 'Spare',
             'Spare', 'Pay For Stargates', 'Spare', 'Scientific Breakthroughs', 'Start Projects', 'Spare', 'Spare', 'Colonisation Instuctions', 
             'Colonisation Instuctions Stage 2', 'Spare', 'Spare', 'Spare', 'Spare', 'Start Anomaly', 'Spare', 'Spare', 
             'Agents Start Missions', 'Spare', 'Spare', 'Economy', 'Spare', 'Spare', 'Load Troops', 'Spare', 
             
             'Ship Move Check','Ship Movements', 'Agents Move Check', 'Agents Movements', 'Meetups', 'Spare', 'Spare', 'Spare', 
             'Space Combat', 'Spare', 'Orbital Bombardment', 'Spare', 'Ground Combat', 'Devastation Selection', 'Devastation', 'Project Progress', 
             'Instructions Progress','Espionage Missions Complete', 'Spare', 'Counter Espionage','Spare', 'Finish Shakedowns', 'Projects Complete', 'Instructions Complete', 
             'Check Survey Reports', 'Give Survey Reports', 'Militia Army Recovery', 'Generate Turns', 'Spare', 'Tidy Up Movements', 'Recalc Project Homes', 'Finish Turn Process'];

  $Coded =  ['Coded','No','No','Coded','Coded','No','Coded', 'No',
             'No','No','No','No','Partial','No','No','Coded',
             'Coded','No','No','No','No','No','No','No',
             'No','No','No','Coded','No','No','No','No',
             
             'Coded','Coded','Coded','Coded','Coded','No','No', 'No',
             'No','No','No','No','No','Coded','Coded','Coded',
             'Coded','No','No','No','No','Coded','Partial','Coded',
             'Partial (not nebula)','Coded','No','No','No','Coded','Coded','Coded?'];
  $Sand = Get_TurnNumber();
// var_dump($Sand);

  // Display progress chart
  // At each stage BEGIN TRANSACTION at end COMMIT TRANSACTION ??
  dostaffhead("Turn Processing");
  echo "<H1>Turn Processing</h1>";
  
  if (isset($_REQUEST['ACTION']) && isset($_REQUEST['S'])) {
    $S = $_REQUEST['S'];
    switch ($_REQUEST['ACTION']) {
    case 'Complete':
      SKLog("Completed " . $Stages[$S]);
      $Sand['Progress'] |= 1<<$S;    
      $S++; // Deliberate drop through    
    case 'Process':
      $act = $Stages[$S];
      $act = preg_replace('/ /','',$act);
      echo "Would call $act<p>";
      if (!is_callable($act)) {
        echo "<class Err>$act not yet written</span><p>";
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
        echo "Processing cancelled<p>\n";
      }
      break;
    case 'Skip':
      if (isset($Stages[$S] )) {
        echo "<b>" . $Stages[$S] . " Skipped<b>";
        SKLog("Skipped " . $Stages[$S]);
        $Sand['Progress'] |= 1<<$S;
      } else {
        echo "Off the end of the turn";
      }
      break;
    case 'Revert':
      if (isset($Stages[$S] )) {
        echo "<b>" . $Stages[$S] . " Reverted<b>";
        SKLog("Reverted " . $Stages[$S]);
        $Sand['Progress'] &= ~(1<<$S);
      } else {
        echo "Off the end of the turn";
      }
      break;    
    case 'RevertAll':
      if (isset($Stages[$S] )) {
        SKLog("Reverted All");
        $Sand['Progress'] = 0;
      } else {
        echo "Off the end of the turn";
      }
      break;
    case 'StageDone':
      $S = $_REQUEST['S'];
      $Sand['Progress'] |= 1<<$S;
      
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
