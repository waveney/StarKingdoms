<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("PlayerLib.php");
  include_once("SystemLib.php");
  include_once("ProjLib.php");
  include_once("HomesLib.php");
    
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
  $Facts = Get_Factions();
  $AllOK = 1;
  foreach ($Facts as $F) {
    if ($F['TurnState'] != 2) {
      echo "<a href=FactionEdit.php?id=" . $F['id'] . ">" . $F['Name'] . "</a> has not submitted a turn.<br>\n";
      $AllOK = 0;
    }
  }
  if ($AllOK) return;
  echo "To proceed you must mark them as submitted<p>\n";
  return false;

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
  $LF = mkdir("Turns/$GAMEID/" . $GAME['Turn'],0777,true);
  return true;
}

function SaveAllLocations() {  
  // Copy location of everything
  $Things = Get_AllThings();
  foreach ($Things as $T) {
    if ($T['BuildState'] == 0) continue;
    $S = ['ThingId'=>$T['id'], 'SystemId'=>$T['SystemId'], 'Turn' => $GAME['Turn'],'BuildState'=> $T['BuildState'], 'CurHealth'=>$T['CurHealth'], 'Game'=>$GAMEID];
    Insert_db('LocationSave',$S);
  }
  
  return true;
}

function CashTransfers() {
  global $GAME;
  
  $Facts = Get_Factions();
  $Facts[-1]['Name'] = "Other";
  $Bs = Get_BankingFT(0,$GAME['Turn']);
  
  foreach($Bs as $B) {
    if (Spend_Credit($B['FactionId'],$B['Amount'],$B['What'])) {
      TurnLog($B['FactionId'],"Transfered  &#8373;" . $B['Amount'] . " for " . $B['What'] . " to " . $Facts[$B['Recipient']]['Name']);

      if ($B['Recipient'] > 0) {
        Spend_Credit($B['Recipient'], - $B['Amount'],$B['What']);
        TurnLog($B['Recipient'],  $Facts[$B['FactionId']]['Name'] . " transfered  &#8373;" . $B['Amount'] . " to you for " . $B['What'] );
      }
      SKLog('Cash transfer from ' . $Facts[$B['FactionId']]['Name']. ' to ' . $Facts[$B['Recipient']]['Name'] . ' of ' . $B['Amount'] . ' for ' . $B['What'],1);     
    } else {
      TurnLog($B['FactionId'],"Failed to transfer  &#8373;" . $B['Amount'] . " for " . $B['What'] . " to " . $Facts[$B['Recipient']]['Name'] . 
               " you only have " . $Facts[$B['FactionId']]['Credits']);
      if ($B['Recipient'] > 0) TurnLog($B['Recipient'],  $Facts[$B['FactionId']]['Name'] . " Failed to transfer  &#8373;" . $B['Amount'] . " for " . $B['What'] );
      SKLog('Cash transfer from ' . $Facts[$B['FactionId']]['Name']. ' to ' . $Facts[$B['Recipient']]['Name'] . 
            ' of  &#8373;' . $B['Amount'] . ' for ' . $B['What'] .  ' Bounced',1);
    }
  }
  
  echo "All Cash trasfered complete.<br>\n";
  return true;
}

function PayForStargates() {
  echo "Pay For Stargates are currently Manual<p>";
  return true;
}

function ScientificBreakthroughs() {
  echo "Scientific Breakthroughs is currently Manual<p>";
  return true;

}

function StartProjects() {
  global $GAME;
//  echo "Start Projects are currently Manual<p>";
// Find all projects with 0 progress due to start this turn
// Pay costs, Status = Started  

  $ProjTypes = Get_ProjectTypes();
  $Projects = Get_Projects_Cond("Progress=0 AND Status=0 AND Costs!=0 AND TurnStart=" . $GAME['Turn']);
//var_dump("Projects",$Projects);

  foreach ($Projects as $P) {
  var_dump("Project",$P);
    $PT = $ProjTypes[$P['Type']];
    $Cost = $P['Costs'];
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
        if ($T['SystemId'] != 0 && $T['SystemId'] != $Where[0]) {
//var_dump($Where,$T);
          $P['Status'] = 5; // Not Started
          TurnLog($P['FactionId'],'Not starting as not in same system: ' . $P['Name']);
          Put_Project($P);
          continue;     
        }
      } // TODO 2nd thing for repair - Checks are wrong
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
        }
      } 
    }
    if (Spend_Credit($P['FactionId'],$Cost,'Starting: ' . $P['Name'])) {
      $P['Status'] = 1; // Started
      TurnLog($P['FactionId'],'Starting ' . $P['Name'] . " Cost: &#8373;$Cost");
      if ($ProjTypes[$P['Type']]['Props'] & 2) { // Has a thing      
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
        RefitRepair($T); //Not this saves it
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
  return true;  
}


function Colonisation() {
  echo "Colonisation are currently Manual<p>";
  return true;
}


function DeepSpaceConstruction() {
  echo "Deep Space Construction	 are currently Manual<p>";
  return true;
}

function StartAnomaly() {
  echo "Anomaly Studies are currently Manual<p>";
  return true;
}

function AgentsStartMissions() {
  echo "Agents Start Missions	are currently Manual<p>";
  return true;
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
    $OutPosts = $AstMines = $Embassies = $OtherEmbs = 0;
    foreach ($Worlds as $W) {
      $H = Get_ProjectHome($W['Home']);
      $PH = Project_Home_Thing($H);
      $Name = $PH['Name'];
      $ECon = $H['Economy'] = Recalc_Economic_Rating($H,$W,$Fid);
      $ECon = ceil(($ECon - $H['Devastation'])*$H['EconomyFactor']/100);
      
      $EccTxt .= "$Name: $ECon " . ($H['Devastation']? " after devastation effect of -" . $H['Devastation'] : "");
      if ($H['EconomyFactor'] < 100) {
        $EccTxt .= " - at " . $H['EconomyFactor'] . "%\n";
      } else if ($H['EconomyFactor'] > 100) {
        $EccTxt .= " - at " . $H['EconomyFactor'] . "%\n";
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
      $EccTxt .= "Plus $OutPosts Outposts worth 1 each\n";
      $EconVal += $OutPosts;
    }
    if ($AstMines) {
      $AstVal = Has_Tech($Fid,'Deep Space Construction');
      if (Has_Tech($Fid,'Advanced Asteroid Mining')) $AstVal*=2;
      $EccTxt .= "Plus $AstMines Asteroid Mines worth $AstVal each\n";
      $EconVal += $AstMines*$AstVal;
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

  
    $LogAvail = LogsticalSupport($Fid);
    $LogCats = ['Ships','Armies','Agents'];
    
    foreach ($LogCats as $i => $n) {
      if ($Logistics[$i]) {
        $pen = min(0,$LogAvail[0]-$Logistics[0]);
        if ($pen < 0) {
          $EconVal += $pen;
          $EccTxt .= "Logistical penalty of $pen for $n\n";
        }
      }
    }
    
    $EccTxt .= "Total Economy is $EconVal worth " . $EconVal*10;
    Spend_Credit($Fid, -$EconVal*10, "Turn Income");
    TurnLog($Fid,$EccTxt);
    echo "Done Economy for " . $F['Name'] . "<br>";
    
  }
  return true;
}

function LoadTroops() {
  echo "Load Troops	is currently Manual<p>";
  return true;
}

function Movements() {
  global $GAME,$GAMEID;
  // Foreach thing, do moves, generate list of new survey reports & levels, update "knowns" 

  if (!file_exists("Turns/" . $GAMEID . "/" . $GAME['Turn'])) $LF = mkdir("Turns/" . $GAMEID . "/" . $GAME['Turn'],0777,true);  
  $LinkLevels = Get_LinkLevels();
  $Things = Get_AllThings();
  foreach ($Things as $T) {
    if ($T['LinkId'] && $T['NewSystemId'] != $T['SystemId'] ) {
echo "Moving " . $T['Name'] . "<br>";
      $Lid = $T['LinkId']; 

      $ShipScanLevel = Scanners($T);
      $ShipNebScanLevel = NebScanners($T);
            
      $L = Get_Link($Lid);

      $Fid = $T['Whose'];
      $SR1 = Get_SystemR($L['System1Ref']);
      $SR2 = Get_SystemR($L['System2Ref']);
      
      $FL = Get_FactionLinkFL($Fid,$Lid);
      if (!isset($FL['Known']) || !$FL['Known']) {
        $FL['Known'] = 1;
        Put_FactionLink($FL);
      }
      
      $FS1 = Get_FactionSystemFS($Fid,$SR1['id']);

      if ($SR1['Nebulae'] ) { // TODO RUBBISH - Need to sort out the two different scan scales - Scanners/NebScan tell us scanning capability
        $ScanLevel = ($NebShipScanLevel>=($SR1['Nebulae']*2)?$NebShipScanLevel/2:0);
      } else {
        $ScanLevel = ($ShipScanLevel>0?2:0);
      }

      if (isset($FS1['ScanLevel'])) { 
        echo "Already seen system " . $L['System1Ref'] . " at level " . $FS1['ScanLevel'];
      } else {
        $FS1['ScanLevel'] = $ScanLevel;
        echo "System " . $L['System1Ref'] . " is new give a survey report";
        Put_FactionSystem($FS1);
        $add = ['FactionId'=>$Fid, 'TurnNumber'=>$GAME['Turn'], 'SystemRef'=>$L['System1Ref'], 'ScanLevel'=> $ScanLevel ];
        Insert_db('ScansDue', $add);
      }
      echo "<p>";
        
      $FS2 = Get_FactionSystemFS($Fid,$SR2['id']);
      if (isset($FS2['ScanLevel'])) { 
        echo "Already seen system " . $L['System2Ref'] . " at level " . $FS2['ScanLevel'];
      } else {
        $FS2['ScanLevel'] = $ScanLevel;
        echo "System " . $L['System2Ref'] . " is new give a survey report";
        Put_FactionSystem($FS2);
        $add = ['FactionId'=>$Fid, 'TurnNumber'=>$GAME['Turn'], 'SystemRef'=>$L['System2Ref'], 'ScanLevel'=> $ScanLevel ];
        Insert_db('ScansDue', $add);
      }
      echo "<p>";
      
      if ($T['SystemId'] == $SR1['id']) {
        $Sid = $T['NewSystemId'] = $SR2['id'];
        $Ref = $SR2['Ref']; // Names...
        $N = $SR2;
      } else {
        $Sid = $T['NewSystemId'] = $SR1['id']; 
        $Ref = $SR1['Ref'];
        $N = $SR1;
      }

      $pname = NameFind($N);
      if ($Fid) {
        $FS = Get_FactionSystemFS($Fid, $Sid);
        if (!empty($FS['Name'])) {
          $Fname = NameFind($FS);
          if ($pname != $Fname) {
            if (strlen($pname) > 1) {
              $pname = $Fname . " ( $pname | $Ref ) ";
            } else {
              $pname = $Fname . " ( $Ref ) ";        
            }
          } else {
            $pname .= " ( $Ref ) ";   
          }
        } else if ($pname) {
          $pname .= " ( $Ref ) ";
        } else {
          $pname = $Ref;
        }
      } else if ($pname) {
        $pname .= " ( $Ref ) ";
      } else {
        $pname = $Ref;
      }

//      $N = Get_System($T['NewSystemId']);
      $EndLocs = Within_Sys_Locs($N);
      $T['SystemId'] = $T['NewSystemId'];
      $T['WithinSysLoc'] = $T['NewLocation'];
//      SKLog("Moved to $pname along " . $LinkLevels[$L['Level']]['Colour']. " link #$Lid to " . $EndLocs[$T['NewLocation']]); 
      TurnLog($Fid,$T['Name'] . " moved to $pname along " . $LinkLevels[$L['Level']]['Colour']. " link #$Lid to " . $EndLocs[$T['NewLocation']],$T); 
      $T['LinkId'] = 0;
      Put_Thing($T);
    } else if ( $T['WithinSysLoc'] != $T['NewLocation'] ) {
      $T['WithinSysLoc'] = $T['NewLocation'];
      $N = Get_System($T['SystemId']);
      $Sid = $T['SystemId'];
      $Fid = $T['Whose'];

      $pname = NameFind($N);
      if ($Fid) {
        $FS = Get_FactionSystemFS($Fid, $Sid);
        if (!empty(strlen($FS['Name']))) {
          $Fname = NameFind($FS);
      
          if ($pname != $Fname) {
            if (strlen($pname) > 1) {
              $pname = $Fname . " ( $pname | $Ref ) ";
            } else {
              $pname = $Fname . " ( $Ref ) ";        
            }
          } else {
            $pname .= " ( $Ref ) ";   
          }
        } else if ($pname) {
          $pname .= " ( $Ref ) ";
        } else {
          $pname = $Ref;
        }

      } else if ($pname) {
        $pname .= " ( $Ref ) ";
      } else {
        $pname = $Ref;
      }

      $EndLocs = Within_Sys_Locs($N);
//      SKLog("Moved to " . $EndLocs[$T['NewLocation']] . " within $pname"); 
      TurnLog($Fid,$T['Name'] . " moved to " . $EndLocs[$T['NewLocation']] .  " within $pname",$T); 
      Put_Thing($T);
    }        
    
  }
  return true;
}


function Meetups() {
  // For each system get all things.  If more than 1 faction, note location flag for that system
  // if (more than 1 has "control" then there is a bundle
  // if only 1 and has control and system controlled then there is a bundle
  // ships, agents ...
  echo "Meetups is currently Manual<p>";
  return true;
}

function SpaceCombat() {
  echo "Space Combat is currently Manual<p>";
  return true;
}

function OrbitalBombardment() {
  echo "Orbital Bombardment is currently Manual<p>";
  return true;
}

function GroundCombat() {
  echo "Ground Combat is currently Manual<p>";
  return true;
}


function Devastation() {
  echo "Devastation is currently Manual<p>";
  return true;

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
          TurnLog($P['FactionId'],'Rushing ' . $P['Name'] . " by $Rush  Cost: &#8373; $Rc");
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
  
  return true;
}

function EspionageMissionsComplete() {
  echo "Espionage Missions Complete	is currently Manual<p>";
}

function CounterEspionage() {
  echo "Counter Espionage is currently Manual<p>";
  return true;
}

function FinishShakedowns() {
  // Move anything in shakedown to completed
  
  $Things = Get_Things_Cond("AND BuildState=2");
  foreach($Things as $T) {
    $T['BuildState'] = 3;
    TurnLog($T['FactionId'],$T['Name'] . " has finished it's Shakedown and is now ready for operations.");
    Put_Thing($T);
  }
  
  echo "Shakdowns finished<br>";
  return true;
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
      
    case 'Rebuild and Repair':  
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
      $T['BuildState'] = 2; // Shakkedown
      TurnLog($P['FactionId'], $T['Name'] . " has been lanched");              
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
     
        
    case 'Construct Warp Gate':
    case 'Share Technology':
    case 'Analyse':
    case 'Decipher Alien Language':
    case 'Decommission Ship':
    case 'Re-equip and Reinforce':
    case 'Build Outpost':
    case 'Build Asteroid Mining Facility':
    case 'Build Minefield':
    case 'Build Orbital Shipyard':
    case 'Build Space Station':
    case 'Extend Space Station':
    case 'Deep Space Sensors':
    case 'Build Advanced Asteroid Mining Facility':
    default:
      SKLog("A project to " . $PT['Name'] . " has completed, this is not automated yet.  See <a href=ProjEdit.php?id=" . $P['id'] . ">Project</a>",1);
    }
  }
  
  return true;
}

function SurveyReports() {
  echo "Survey Reports is currently Manual<p>";
  return true;
}

function GenerateTurns() {
  echo "Generate Turns is currently Manual<p>";
  return true;
}

function TidyUpMovements() {
  global $db,$GAMEID;
  
  $res = $db->query("UPDATE Things SET LinkId=0 WHERE LinkId>0 AND GameId=$GAMEID"); 
  
  echo "Movements Tidied Up<p>";  
  return true;
}

function RecalcProjectHomes() {
  include_once("HomesLib.php");
  Recalc_Project_Homes('SKLog'); // in ThingLib - this is to add new project homes that have been created by colonisation etc.
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
  return true;
}

function Do_Turn() {
  global $Sand;  // If you need to add something, replace a spare if poss, then nothing breaks
  $Stages = ['Check Turns Ready', 'Spare', 'Spare','Start Turn Process', 'Save All Locations', 'Spare', 'Cash Transfers', 'Spare',
             'Spare', 'Pay For Stargates?', 'Spare', 'Scientific Breakthroughs', 'Start Projects', 'Spare', 'Spare', 'Colonisation', 
             'Spare', 'Spare', 'Deep Space Construction', 'Spare', 'Spare', 'Start Anomaly', 'Spare', 'Spare', 
             'Agents Start Missions', 'Spare', 'Spare', 'Economy', 'Spare', 'Spare', 'Load Troops', 'Spare', 
             
             'Spare','Movements', 'Spare', 'Spare', 'Meetups', 'Spare', 'Spare', 'Spare', 
             'Space Combat', 'Spare', 'Orbital Bombardment', 'Spare', 'Ground Combat', 'Devastation', 'Spare', 'Project Progress', 
             'Spare','Espionage Missions Complete', 'Spare', 'Counter Espionage','Spare', 'Finish Shakedowns', 'Spare', 'Projects Complete', 
             'Survey Reports', 'Spare', 'Spare', 'Generate Turns', 'Spare', 'Tidy Up Movements', 'Recalc Project Homes', 'Finish Turn Process'];
  $Coded =  ['Coded','No','No','Coded','Coded','No','Coded', 'No',
             'No','No','No','No','Partial','No','No','No',
             'No','No','No','No','No','No','No','No',
             'No','No','No','Coded','No','No','No','No',
             
             'No','Coded for Ships only','No','No','No','No','No','No',
             'No','No','No','No','No','No','No','Coded',
             'No','No','No','No','No','Coded','No','Partial',
             'No','No','No','No','No','Coded','Coded','Coded?'];
  $Sand = Get_TurnNumber();
// var_dump($Sand);

  // Display progress chart
  // At each stage BEGIN TRANSACTION at end COMMIT TRANSACTION ??
  dostaffhead("Turn Processing");
  echo "<H1>Turn Processing</h1>";
  
  if (isset($_REQUEST['ACTION']) && isset($_REQUEST['S'])) {
    $S = $_REQUEST['S'];
    switch ($_REQUEST['ACTION']) {
    case 'Process':
      $act = $Stages[$S];
      $act = preg_replace('/ /','',$act);
      echo "Would call $act<p>";
      if (!is_callable($act)) {
        echo "$act not yet written<p>";
        break;
      }
      SKLog("Doing " . $Stages[$S]);      
      $Result = $act(); 
      if ($Result) {
        $Sand['Progress'] |= 1<<$S;
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
      
      break;
    }  
  }
  
  echo "<table border>";
  echo "<tr><th>#<td>Mask<td>Automation<td>Stage<th>State<th>Commands\n";
  $Stage = 0;
  $Prog = 1;
  $NextStage = -1;
  
  foreach ($Stages as $sta) {
    if ($sta != 'Spare') {
      echo "<tr><td>$Stage<td>" . dechex($Prog) . "<td>" . $Coded[$Stage] . "<td>" . $sta . "<td>";
      
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
