<?php

// Scan all plantets/moon's/Thing's for districts and deep space construction

  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("PlayerLib.php");
  include_once("SystemLib.php");
  include_once("ProjLib.php");
  
  global $FACTION;
 
// var_dump($_REQUEST);

  $HomeColours = ['#ff99ff', '#ccffff', '#ccffcc', '#ffffcc', '#ffcccc', '#e6ccff', '#cce6ff', '#ffd9b3', '#ecc6c6', '#ecc6d6', '#d6b3ff', '#d1e0e0', '#d6ff99' ];

  A_Check('Player');    
  $Fid = 0;
  $GM = Access('GM');  
  if (Access('Player')) {
    if (!$FACTION) {
      if (!Access('GM') ) Error_Page("Sorry you need to be a GM or a Player to access this");
    } else {
      $Fid = $FACTION['id'];
      $Faction = &$FACTION;
    }
  } else if (!$GM) {
    Error_Page("Sorry you need to be a GM or a Player to access this");
  }
  if ($GM && !$Fid) {
    if (isset( $_REQUEST['F'])) {
      $Fid = $_REQUEST['F'];
    } else if (isset( $_REQUEST['f'])) {
      $Fid = $_REQUEST['f'];
    } else if (isset( $_REQUEST['id'])) {
      $Fid = $_REQUEST['id'];
    } 
    CheckFaction('ProjDisp',$Fid);
 
    if (isset($Fid)) $Faction = Get_Faction($Fid);
  }

  if ($Fid == 0) {
    dostaffhead("Display Projects When no Faction selected");
    echo "<h1>Display Projects When no Faction selected</h1>";
    dotail();
  }
  
  dostaffhead("Display Projects for faction",["js/ProjectTools.js"]);
  
  $OpenHi = $OpenDi = -99;
  $ProjTypes = Get_ProjectTypes();
  $ThingTypes = Get_ThingTypes();
  
  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {  // TODO This code is DREADFUL needs redoing
      case 'NEW': 
        $Ptype = $_REQUEST['p'];
        $Turn = $_REQUEST['t'];
        $Hi = $_REQUEST['Hi'];
        $Di = $_REQUEST['Di'];
        $DT = (isset($_REQUEST['DT'])? $_REQUEST['DT'] : 0);
        $Valid = 1;
        
        switch ($ProjTypes[$Ptype]['Name']) {
        case 'Construction':
        case 'Grow District':
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
        case 'Construct Warp Gate':
        case 'Decipher Alien Language':
        case 'Rebuild and Repair':  
        case 'Grow Modules' :    
          if (isset($_REQUEST['Sel'])) $Sel = $_REQUEST['Sel'];
          $Level = $_REQUEST['L'];
          $Costs = $_REQUEST['C'];
          $ProgN = $_REQUEST['PN'];
          $Name = base64_decode($_REQUEST['Name']);
          break;        

        case 'Re-equip and Reinforce':    
          $Level = 1;
          $TthingId2 = 0;
          if (isset($_REQUEST['Sel'])) {
            $TthingId = $_REQUEST['Sel'];
          } else if (isset($_REQUEST['Sel2'])) {
            $Sels = $_REQUEST['Sel2'];
            $Count = count($Sels);
            if ($Count == 0 || $Count>2) {
              echo "<h2 class=Err>You must select 1 (or up to two level 1 armies)</h2>\n";
              $Valid = 0;
              break;
            }
            if ($Count == 1) {
              $TthingId = $Sels[0];
            } else {
              $TthingId =$Sels[0];
              $TthingId2 = $Sels[1];
              $T2 = Get_Thing($TthingId2);
            }
          } else {
            echo "<h2 class=Err>You must select an army</h2>\n";
            $Valid = 0;
            break;          
          }  
          $T1 = Get_Thing($TthingId);
          
          if (isset($_REQUEST['Name'])) {
            $Name = base64_decode($_REQUEST['Name']);
          } else {
            $T1 = Get_Thing($TthingId);
            $Name = "Re-equip and Reinforce " . $T1['Name'];
            if ($TthingId2) $Name .= " and " . $T2['Name'];
          }
          
          $pc = Proj_Costs(1);
          $Costs = $pc[1];
          $ProgN = $pc[0];          
          break;

        case 'Refit and Repair': 
          $Level = 1;
          $TthingId2 = 0;
          if (isset($_REQUEST['Sel'])) {
            $TthingId = $_REQUEST['Sel'];
          } else if (isset($_REQUEST['Sel2'])) {
            $Sels = $_REQUEST['Sel2'];
            $Count = count($Sels);
            if ($Count == 0 || $Count>2) {
              echo "<h2 class=Err>You must select 1 (or up to two level 1 ships)</h2>\n";
              $Valid = 0;
              break;
            }
            if ($Count == 1) {
              $TthingId = $Sels[0];
            } else {
              $TthingId =$Sels[0];
              $TthingId2 = $Sels[1];
              $T2 = Get_Thing($TthingId2);
            }
          } else {
            echo "<h2 class=Err>You must select a ship</h2>\n";
            $Valid = 0;
            break;          
          }  
          $T1 = Get_Thing($TthingId);
          
          if (isset($_REQUEST['Name'])) {
            $Name = base64_decode($_REQUEST['Name']);
          } else {
            $T1 = Get_Thing($TthingId);
            $Name = "Refit and Repair " . $T1['Name'];
            if ($TthingId2) $Name .= " and " . $T2['Name'];
          }
          
          $pc = Proj_Costs(1);
          $Costs = $pc[1];
          $ProgN = $pc[0];          
          break;

        case 'Construct Ship':
        case 'Train Army':
        case 'Train Agent':
          $T = Get_Thing($_REQUEST['ThingId']);
          if ($T['BuildState'] > 0) {
            $T = Thing_Duplicate($T['id']);
          }
          $Level = $T['Level'];
          $pc = Proj_Costs($Level);
          if ($ProjTypes[$Ptype]['Name'] == 'Train Army' && Has_Tech($Fid,'Efficient Robot Construction')) $pc[0] = max(1, $pc[0] - $T['Level']); 
          if ($ProjTypes[$Ptype]['Name'] == 'Construct Ship' && Has_Tech($Fid,'Space Elevator')) $pc[0] = max(1, $pc[0] - $T['Level']); 
          $Costs = $pc[1];
          $ProgN = $pc[0];
          $TthingId = $T['id'];
          $Name = "Build " . $T['Name'] . " (level $Level)"; 
          break;

        case 'Share Technology':
          $Tech2S = $_REQUEST['Tech2Share'];
          preg_match('/(\d*):(\d*)/',$Tech2S,$mtch);
          $With = $_REQUEST["ShareWith"];
          $Level = $mtch[2];
          $pc = Proj_Costs($Level-1);            
          $Costs = $pc[1];
          $ProgN = $pc[0];
          $Sel = $mtch[1];
          $Tech = Get_Tech($Sel);
          $Fact = Get_Faction($With);
          $Name = "Share " . $Tech['Name'] . " at level $Level with " . $Fact['Name'];
          break;

        case 'Analyse':
          $Level = $_REQUEST['Level'];
          $Name = $_REQUEST['AnalyseText'];
          if (empty($Name)) {
            echo "<h2 class=Err>No project name given</h2>";
            $Valid = 0;
            break;
          }
          $pc = Proj_Costs($Level);          
          $Costs = $pc[1];
          $ProgN = $pc[0];
          break;

        case 'Post It':
          $Level = $_REQUEST['Level'];
          $pc = Proj_Costs($Level);
          $Costs = $pc[1];
          $ProgN = $pc[0];
          $Name = $_REQUEST['PostItTxt'];          
          if (empty($Name)) {
            echo "<h2 class=Err>No message given</h2>";
            $Valid = 0;
            break;
          }
          break;
          
          
          /* These are treated as Instructions now */
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
          break;
        default:    
        }
        
/*
        if (isset($_REQUEST['ThingId'])) {
          $T = Get_Thing($_REQUEST['ThingId']);
          if ($T['BuildState'] > 0) {
            $T = Thing_Duplicate($T['id']);
          }
          $Level = $T['Level'];
          $pc = Proj_Costs($Level);
          $Costs = $pc[1];
          $ProgN = $pc[0];
          $TthingId = $T['id'];
          $Name = "Build " . $T['Name'] . " (level $Level)"; 
        } else if ($Ptype == 7) {
          $Tech2S = $_REQUEST['Tech2Share'];
          preg_match('/(\d*):(\d*)/',$Tech2S,$mtch);
          $With = $_REQUEST["ShareWith"];
          $Level = $mtch[2];
          $pc = Proj_Costs($Level-1);            
          $Costs = $pc[1];
          $ProgN = $pc[0];
          $Sel = $mtch[1];
          $Tech = Get_Tech($Sel);
          $Fact = Get_Faction($With);
          $Name = "Share " . $Tech['Name'] . " at level $Level with " . $Fact['Name'];
        } else if ($Ptype == 8) {
          $Level = $_REQUEST['Level'];
          $Name = $_REQUEST['AnalyseText'];
          if (empty($Name)) {
            echo "<h2 class=Err>No project name given</h2>";
            break;
          }
          $pc = Proj_Costs($Level);          
          $Costs = $pc[1];
          $ProgN = $pc[0];
        } else if ($Ptype == '11') {
          $Level = 1;
          if (empty($TthingId = $_REQUEST['Sel'])) {
            echo "<h2 class=Err>You must select a ship</h2>\n";
            break;
          }
          if (isset($_REQUEST['Name'])) {
            $Name = base64_decode($_REQUEST['Name']);
          } else {
            $T1 = Get_Thing($TthingId);
            $Name = "Refit and Repair " . $T1['Name'];
          }
          $pc = Proj_Costs(1);
          $Costs = $pc[1];
          $ProgN = $pc[0];          
        } else if ($Ptype == '11b') {
          if (empty($_REQUEST['Sel2'])) {
            echo "<h2 class=Err>You must select a ship</h2>\n";
            break;
          }
          $Level = 1;
          $Ptype = 11;
          $Sels = $_REQUEST['Sel2'];
          $Count = count($Sels);
          if ($Count == 0 || $Count>2) {
            echo "<h2 class=Err>You must select 1 (or up to two level 1 ships)</h2>\n";
            break;
          }
          if ($Count == 1) {
            $TthingId = $Sels[0];
            if (isset($_REQUEST['Name'])) {
              $Name = base64_decode($_REQUEST['Name']);
            } else {
              $T1 = Get_Thing($TthingId);
              $Name = "Refit and Repair " . $T1['Name'];
            }
          } else {
            $TthingId =$Sels[0];
            $TthingId2 = $Sels[1];
            $T1 = Get_Thing($TthingId);
            $T2 = Get_Thing($TthingId2);
            if ($T1['Level'] != 1 || $T2['Level'] != 1) {
              echo "<h2 class=Err>You can only do two level 1 ships at once</h2>\n";
              break;
            }
            $Name = "Refit and Repair " . $T1['Name'] . " and " . $T2['Name'];
          }

          $pc = Proj_Costs(1);          
          $Costs = $pc[1];
          $ProgN = $pc[0];                    
         } else if ($Ptype == '16') {
          $Level = 1;
          if (empty($TthingId = $_REQUEST['Sel'])) {
            echo "<h2 class=Err>You must select an army</h2>\n";
            break;
          }
          if (isset($_REQUEST['Name'])) {
            $Name = base64_decode($_REQUEST['Name']);
          } else {
            $T1 = Get_Thing($TthingId);
            $Name = "Re-equip and Reinforce " . $T1['Name'];
          }
          $pc = Proj_Costs(1);          
          $Costs = $pc[1];
          $ProgN = $pc[0];          
        } else if ($Ptype == '11b') {
          if (empty($_REQUEST['Sel2'])) {
            echo "<h2 class=Err>You must select an army</h2>\n";
            break;
          }
          $Level = 1;
          $Ptype = 11;
          $Sels = $_REQUEST['Sel2'];
          $Count = count($Sels);
          if ($Count == 0 || $Count>2) {
            echo "<h2 class=Err>You must select 1 (or up to two level 1 armies)</h2>\n";
            break;
          }
          if ($Count == 1) {
            $TthingId = $Sels[0];
            if (isset($_REQUEST['Name'])) {
              $Name = base64_decode($_REQUEST['Name']);
            } else {
              $T1 = Get_Thing($TthingId);
              $Name = "Re-equip and Reinforce " . $T1['Name'];
            }
          } else {
            $TthingId = $Sels[0];
            $TthingId2 = $Sels[1];
            $T1 = Get_Thing($TthingId);
            $T2 = Get_Thing($TthingId2);
            if ($T1['Level'] != 1 || $T2['Level'] != 1) {
              echo "<h2 class=Err>You can only do two level 1 armies at once</h2>\n";
              break;
            }
            $Name = "Re-equip and Reinforce " . $T1['Name'] . " and " . $T2['Name'];
          }

          $pc = Proj_Costs(1);          
          $Costs = $pc[1];
          $ProgN = $pc[0];                    
        } else {
          if (isset($_REQUEST['Sel'])) $Sel = $_REQUEST['Sel'];
          $Level = $_REQUEST['L'];
          $Costs = $_REQUEST['C'];
          $ProgN = $_REQUEST['PN'];
          $Name = base64_decode($_REQUEST['Name']);
        }
*/
        if ($Valid) {
          $OldPro = Get_ProjectAt($Hi, $DT, $Turn);
// var_dump($OldPro);
          $Pro = ['FactionId'=>$Fid, 'Type'=>$Ptype, 'Level'=> $Level, 'Home'=>$Hi, 'Progress'=>0, 'Status'=>0, 'TurnStart'=>$Turn, 'Name'=>$Name,
                  'Costs' => $Costs, 'ProgNeeded' => $ProgN, 'BuildState'=>0, 'DType' => $DT];
          if (isset($With)) $Pro['ThingId'] = $With;
          if (isset($Sel)) $Pro['ThingType'] = $Sel;
          if (isset($TthingId)) $Pro['ThingId'] = $TthingId;
          if (isset($TthingId2)) $Pro['ThingId2'] = $TthingId2;

          if (isset($OldPro['id'])) {
            $Pro['id'] = $OldPro['id'];          
          }

          $OpenHi = $Hi;
          $OpenDi = $Di;
          $Pid = Put_Project($Pro);
        }
      break;
      
    }
  }
  
  echo "<h1>Display Projects</h1>";
 
  echo "<div class=floatright style=text-align:right><div class=Bespoke>" .
       "Showing:<button class=BigSwitchSelected id=BespokeM onclick=Add_Bespoke()>10 Turns</button><br>" .  
       "Switch to: <button class=BigSwitch id=GenericM onclick=Add_Bespoke()>All Turns</button></div>" .
       "<div class=Bespoke hidden id=BespokeMess>" .
       "Showing:<button class=BigSwitchSelected id=GenericM1 onclick=Remove_Bespoke()>All Turns</button><br>" .  
       "Switch to: <button class=BigSwitch id=BespokeM1 onclick=Remove_Bespoke()>10 Turns</button></div>" .
       "</div>";

  echo "Click on Place to Expand/Contract<br>";
  echo "Click on Showing Options top right to see more than 2 turns back<br>\n";
  echo "Click on the World, Distict type or Construction to expand/contract that area<p><p>";
  echo "Click on <button type=submit class=PHStart id=StartExample formaction=''>+</button> buttons to start/change projects<br>\n";
  echo "Click up/down or write number to rush projects<br>\n";

  echo "Note the cost totals are on the far right<br>
  The credits left on current turn is a rough guide only - it does not take account of other expenditure other than for the current turn - or any additional income.<p>";
  
  echo "Note 2: The amount of progress before the end of the previous turn is at best a guess.  If the number of districts/planetary construction has changed they will be wrong.<p>\n";
  
  echo "Currently this display is for construction and district based projects only.<br>\n";
  
  $Homes = Get_ProjectHomes($Fid);
  $DistTypes = Get_DistrictTypes();

  $Income = Income_Estimate($Fid);
//var_dump($ProjTypes);exit;

  $Headline1 = '';
  $Headline2 = [];
  $SkipProgress = 0;
  
  if (Access('GM')) {
    $TurnData = Get_TurnNumber();
    if ($TurnData['Progress'] & 1<<47) {
      $SkipProgress = 1;
    } 
  }
//  $Turns = [][];
  
// Stage 1 Scan the data

// var_dump($Homes);exit;
  
  $BPlanCon = Has_Tech($Fid,3);
  $PHx = 1;
  $Dis = [];
  $FirstHome = 0;
  $NoC = 0;
  
  $Things = Get_Things_Cond($Fid," Instruction!=0 AND Progress=0 AND InstCost!=0 ");
  $DeepSpace = 0;
  foreach($Things as $T) $DeepSpace += $T['InstCost'];

  $Things = Get_Things_Cond($Fid,"LinkId>0 AND LinkCost>0 AND LinkPay>0");
  $LinkCosts = 0;
  foreach($Things as $T) {
    $LinkCosts += $T['LinkCost'];
  }  
  
  foreach ($Homes as &$H) {
    $PlanCon = $BPlanCon;
    $Hi = $H['id'];
    $NoC = 0;
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
// if($Hi==199) echo "Here";    
    if (($H['ThingType'] != 3) && ($PH['Type'] != $Faction['Biosphere']) && ($PH['Type'] != $Faction['Biosphere2']) && ($PH['Type'] != $Faction['Biosphere3'])) $PlanCon--;
    //TODO Construction and Districts... 

    if ($NoC != 1 ) $Dists[] = ['HostType'=>-1, 'HostId' => $PH['id'], 'Type'=> -1, 'Number'=>0, 'id'=>-$PH['id']];
    $back = "style='background:" . $HomeColours[$PHx-1] . ";'";
    $Headline1 .= "<th id=PHH$PHx $back><button type=button class=ProjHome id=PHome$Hi onclick=ToggleHome($Hi)>" . $PH['Name'] . "</button>";
// if($Hi==199) { echo " THere"; var_dump($Headline1); };
    if ($FirstHome == 0) $FirstHome=$Hi;
    
    $District_Type = [];
    foreach ($Dists as $D) {

      
      if ($D['Type'] > 0 && (($DistTypes[$D['Type']]['Props'] &2) == 0)) continue;
      if ($D['Type'] < 0 && $PlanCon<1 ) continue;
      $Di = $D['id'];
      $Hide = ($Hi == $OpenHi && $Di == $OpenDi? "" : "hidden");
//      if (!$Hide && $SaveDType) {
//        $Pro['DType'] = $D['Type'];
//        Put_Project($Pro);
//      }
      $HL = "<th class='PHStart Group$Di Home$Hi' id=PHDist$Hi:$Di $back $Hide><b>+</b>" .
        "<th $back class='PHName  Home$Hi'><button type=button onclick=Toggle('Group$Di')>";
        
      if ($D['Type'] <= 0) {
        $HL .= "Construction&nbsp;-&nbsp;$PlanCon";
      } else if ($DistTypes[$D['Type']]['Props'] & 8 ) {
        $HL .= $DistTypes[$D['Type']]['Name'];
      } else {   
        $HL .= $DistTypes[$D['Type']]['Name'] . "&nbsp;-&nbsp;" . $D['Number'];
      }
      
      $HL .= "</button><th $back class='PHLevel Group$Di Home$Hi'id=PHLevel$Hi:$Di $Hide>Lvl" .
        "<th $back class='PHCost Group$Di Home$Hi' id=PHCost$Hi:$Di $Hide>Cost" .
        "<th $back class='PHRush Group$Di Home$Hi' id=PHRush$Hi:$Di $Hide>Rush";
        
//        "<th $back class='PHBonus Group$Di Home$Hi' id=PHBonus$Hi:$Di $GMHide>B" . 
      $HL .= "<th $back class='PHProg Group$Di Home$Hi' id=PHProg$Hi:$Di $Hide>Prog" .
        "<th $back class='PHStatus Group$Di Home$Hi' id=PHStatus$Hi:$Di $Hide>Status";
     
      $Headline2[] = $HL;
      $District_Type[$D['Type']] = $D['Number'];
      $Dis[$Hi][] = $D;
      }

//var_dump($Dis);exit;
    
    $Projs = Get_Projects($Hi);
//var_dump($Projs); echo "<p>";

    foreach ($Projs as &$P) {
      if ($P['Status'] > 2 ) continue; // Cancelled, On Hold or Could not start
//var_dump($P); echo "<P>";
      $TurnStuff = Get_ProjectTurns($P['id']);
//
//var_dump($TurnStuff); echo "<br>";
      $Pro = [];
      $Pro['id'] = $P['id'];
      $Pro['Type'] = $P['Type'];
      $Pro['Name'] = $P['Name']; // $ProjTypes[$P['Type']]['Name'];
      $Pro['Level'] = $P['Level'];
      $Pro['Cost'] = $P['Costs'];
      $Pro['Acts'] = $P['ProgNeeded'];
//      $Pro['Prog'] = $P['Progress'];
      $Pro['GMOverride'] = $P['GMOverride'];
      
      $PPtype = $ProjTypes[$P['Type']];
      $PCat = $PPtype['Category'];
      
      if ($P['DType']) {
        $WantedDT = $P['DType'];
      } else {
        $WantedDT = -10;
        if ($PCat & 1) { $WantedDT = 5; }// Academic
        else if ($PCat & 2) { $WantedDT = 3; } // Shipyard
        else if ($PCat & 4) { $WantedDT = 2; } // Military      
        else if ($PCat & 8) { $WantedDT = 4; } // Intelligence
        else if ($PCat & 16) { $WantedDT = -1; } // Construction
        else if ($PCat & 32) { $WantedDT = 100; } // Deep Space       
        else if ($PCat & 64) { $WantedDT = 101; } // Intelligence
      }
      
      $Di = -10;
      foreach ($Dis[$Hi] as $Dix) {
        if ($Dix['Type'] == $WantedDT) {
          $Di = $Dix['id'];
          break;
        }
      }
      
      if ($Di == -10) {
        if ($P['Status'] > 1) continue; // Duff in past
        echo "Faulty project " . $P['id'];
      } else {
      
        $Pro['Status'] = 'Started';
        $TotProg = 0;
      
//var_dump($Pro, $ProjTypes[$P['Type']]);  echo "<p>";    
        $TSi = 0;
        // Is there an existing project where this is going?
        if (isset($Proj[$P['TurnStart']][$Hi][$Di]['id'])) {
          $What = $Proj[$P['TurnStart']][$Hi][$Di]['id'];
          for ($t = $P['TurnStart']; $t <= $P['TurnStart']+50; $t++) {
            if (isset($Proj[$t][$Hi][$Di]['id']) && $Proj[$t][$Hi][$Di]['id'] == $What) {
              $Proj[$t][$Hi][$Di] = [];
            } else {
              break;
            }
          }
        }   

        $TotProg = $P['Progress'];
        for ($t = $P['TurnStart']; $t <= ($P['TurnEnd']?$P['TurnEnd']:$P['TurnStart']+50); $t++) {

          $Pro['Rush'] = $Rush = $FreeRush = $Bonus = 0;
//          $Pro['MaxRush'] = ( ($ProjTypes[$P['Type']]['Category'] & 16) ? $PlanCon : $District_Type[$WantedDT]);
          $Pro['MaxRush'] = (( $WantedDT < 0) ? $PlanCon : $District_Type[$WantedDT]);
            
/*            $Pro['MaxRush'] =  (($ProjTypes[$P['Type']]['BasedOn'])? Has_Tech($Fid,$ProjTypes[$P['Type']]['BasedOn'],$t) : 
               (isset ($District_Type[5]) ?$District_Type[5]:0)); */
          if (preg_match('/Research/',$PT['Name'],$mtch) && Has_Trait($P['FactionId'],'Built for Construction and Logistics')) {
            $TechId = $P['ThingType'];
            $Tech = Get_Tech($TechId);
            if ($Tech['PreReqTech'] == 1 || $TechId == 1) {
              $FreeRush = min(1,$Acts,$P['ProgNeeded']-$P['Progress']-$Acts-$Bonus);
            } 
         }

          if (isset($TurnStuff[$TSi])) {
            if ($TurnStuff[$TSi]['TurnNumber'] == $t) {
              $Rush = $Pro['Rush'] = min($TurnStuff[$TSi]['Rush'], $Pro['MaxRush']);
              if (!empty($TurnStuff[$TSi]['Bonus'])) $Bonus = $Pro['Bonus'] = $TurnStuff[$TSi]['Bonus'];
              $TSi ++;
            }
          }

          if ($t == $P['TurnEnd']) {
            $Pro['Status'] = 'Complete';
            $Pro['Progress'] = $Pro['Acts'] . "/" . $Pro['Acts'];
          } else if ($t < $GAME['Turn'] -1) {
            $Pro['Progress'] = "? /" . $Pro['Acts'];
            $Pro['Status'] = (($t == $P['TurnStart'])?'Started' : 'Ongoing' );
          } else if ($t == $GAME['Turn'] -1) {
            $Pro['Progress'] = $P['Progress'] . "/" .  $Pro['Acts'];
            $Pro['Status'] = (($t == $P['TurnStart'])?'Started' : 'Ongoing' );
          } else {
            $Prog = min($Pro['Acts'] - $TotProg,$Pro['MaxRush'] + $Rush + $Bonus+ $FreeRush ); // Note Bonus can be negative
            if ($t == $GAME['Turn']) { 
              if ($SkipProgress) $Prog = 0;
              $TotProg = $P['Progress'] + $Prog;
            } else {
              $TotProg += $Prog;
            }
            $Pro['Progress'] = "$TotProg/" . $Pro['Acts'];
            $Pro['Status'] = (($TotProg >= $Pro['Acts'])? 'Complete' : (($t == $P['TurnStart'])?'Started' : 'Ongoing' ));
          }

/*          
          if ($TotProg || $Prog >= $Pro['Acts'] ) {
            if ($t != $GAME['Turn'] || $SkipProgress==0 ) $TotProg += $Prog;
            if ($TotProg >= $Pro['Acts']) {
              $Pro['Progress'] = $Pro['Acts'] . "/" . $Pro['Acts'];
              $Pro['Status'] = 'Complete';
// TODO Future              Project_Finished($P,$t);
            } else {
              $Pro['Progress'] = "? /" . $Pro['Acts'];
              $Pro['Status'] = 'Ongoing';
            }
          } else {
              if ($t == ($GAME['Turn']-1)) {
                $TotProg = $P['Progress'];
              } else {
                $TotProg += $Prog;
              }
              $Pro['Progress'] = "$TotProg/" . $Pro['Acts'];
              $Pro['Status'] = 'Started';
          }
*/          
        
          $Proj[$t][$Hi][$Di] = $Pro;
          $Pro['Cost'] = 0;
          if ($Pro['Status'] == 'Complete') break;
        }
        
      }
    }
  $PHx++;
  }
  


//var_dump($Homes);
//var_dump($Proj);
//var_dump($Dis);

/*    
    echo "<th id=PHH$PHx><button class=ProjHome id=PHome$PHx>" . $PH['Name'] . "</button>";
    
    $p=1;
    echo "<th id=PJCol$p:$PHx>[S]<th id=PJCol$p>Construction<th id=PJCol$p:$PHx>Rush<th id=PJCol$p:$PHx>Prog<th id=PJCol$p:$PHx>State";
        
    foreach ($Dists as &$D) {
      $DistType = $DistTypes[$D['Type']];
      if (!($DistType['Props'] & 2)) continue;
      $p++;
      $Dtname = $DistType['Name'];  // add cost
      echo "<th class=PJCol$p:$PHx>[S]<th class=PJCol$p>$Dtname<th class=PJCol$p:$PHx>Rush<th class=PJCol$p:$PHx<th class=class=PJCol$p:$PHx>State";
      }

    
    }
*/
  // That is just the nested headers  
  
  
  // Now Present the data
    
  echo "<form method=post action=ProjDisp.php>";
  echo fm_hidden('DistData', base64_encode(json_encode($Dis)));  
  Register_AutoUpdate('Projects',$Fid);
  echo fm_hidden('id',$Fid);

  echo "<input type=submit name=Ignore value=Ignore hidden>\n";
  echo "<table border style='width:auto;height:50px;'>";
  echo "<h2>Worlds:</h2> $Headline1";
  echo "</table>";
  echo "<h2>Projects:</h2>";
  echo "<table border>";
  


  echo "<tr><td>Turn";
  foreach ($Headline2 as $Hl) echo $Hl;
  echo "<th>Total Cost<th>Credits Left";
  for($Turn=0; $Turn<($GAME['Turn']+50); $Turn++) {
    $RowClass = "ProjHide";
    $hide = ' hidden';
    if ($Turn >= ($GAME['Turn']-1) && $Turn < ($GAME['Turn']+9)) {
        $RowClass = 'ProjShow';
        $hide = '';
    }
    
    $BG = " style='background:White;'";
    $tdclass= 'ProjWhite';
    if ($Turn < $GAME['Turn']) {
      $BG = " style='background:#F0F0F0;'";
    } else if ($Turn == $GAME['Turn']) {
      $BG = " style='background:#C0FFC0;'";
    }
      
    echo "<tr class=$RowClass $hide><td class=PHTurn >$Turn";
    $TotCost = 0;
    
    foreach ($Homes as &$H) {

      if (isset($H['Skip'])) continue;
      if (!isset($H['id'])) continue;

      $Hi = $H['id'];
      if (isset($Dis[$Hi])) foreach($Dis[$Hi] as $Dix) {
        $Di = $Dix['id'];
        $Hide = ($Hi == $OpenHi && $Di == $OpenDi? "" : "hidden");
//echo "Doing $Hi - $Di<br>";
        // TODO ids need setting...
      // TODO Warning
      
        echo "<td $BG id=ProjS$Turn:$Hi:$Di class='PHStart Group$Di Home$Hi' $Hide>\n";
        if ($Turn >= $GAME['Turn']) {
          $Warn = '';
          if (isset($Proj[$Turn - 1 ][$Hi][$Di]['Status']) && ($Proj[$Turn - 1 ][$Hi][$Di]['Status'] == 'Started' || $Proj[$Turn - 1][$Hi][$Di]['Status'] == 'Ongoing')) {
 //           $Warn = "onclick=\"return confirm('Do you want to abandon " . $Proj[$Turn-1][$Hi][$Di]['Name'] . '?\'"';
              $Action = "onclick='return NewProjectCheck($Turn,$Hi,$Di)' formaction=ProjNew.php?t=$Turn&Hi=$Hi&Di=$Di";
          } else {
            $Action = "formaction=ProjNew.php?t=$Turn&Hi=$Hi&Di=$Di";
          } 
          echo "<button type=submit class=PHStartButton id=Start:STurn:$Hi:$Di $Action ><b>+</b>\n"; 
        }
        if (isset($Proj[$Turn][$Hi][$Di]['Type'])) {
          $PN = $Proj[$Turn][$Hi][$Di]['id'];
          echo "\n<td $BG id=ProjN$Turn:$Hi:$Di class='PHName Home$Hi" . ($Proj[$Turn][$Hi][$Di]['Type'] == 38?" PHpostit ":"") . "'>" .
                "<a href=ProjEdit.php?id=" . $Proj[$Turn][$Hi][$Di]['id'] . ">" . 
                $Proj[$Turn][$Hi][$Di]['Name'] . "</a>";
          echo "\n<td $BG id=ProjL$Turn:$Hi:$Di class='PHLevel Group$Di Home$Hi' $Hide>" . $Proj[$Turn][$Hi][$Di]['Level'];
          echo "\n<td $BG id=ProjC$Turn:$Hi:$Di class='PHCost Group$Di Home$Hi' $Hide>" . $Proj[$Turn][$Hi][$Di]['Cost'];
          echo "\n<td $BG id=ProjR$Turn:$Hi:$Di class='PHRush Group$Di Home$Hi' $Hide>" . (($Turn < $GAME['Turn'])?$Proj[$Turn][$Hi][$Di]['Rush'] : 
               "<input type=number id=Rush$Turn:$PN name=Rush$Turn:$PN oninput=RushChange($Turn,$PN,$Hi,$Di," . 
               $Proj[$Turn][$Hi][$Di]['MaxRush'] . ") value=" . min($Proj[$Turn][$Hi][$Di]['Rush'],$Proj[$Turn][$Hi][$Di]['MaxRush'])  .
               " min=0 max=" . ($Proj[$Turn][$Hi][$Di]['GMOverride']?20:$Proj[$Turn][$Hi][$Di]['MaxRush']) .">" );
//          if (!empty($Proj[$Turn][$Hi][$Di]['Bonus'])) echo "<div id=ProjB$Turn:$Hi:$Di class='PHBonus hidden>" . $Proj[$Turn][$Hi][$Di]['Bonus'] . "</div>";
          echo "\n<td $BG id=ProjP$Turn:$Hi:$Di class='PHProg Group$Di Home$Hi' $Hide>" . $Proj[$Turn][$Hi][$Di]['Progress'];
          echo "\n<td $BG id=ProjT$Turn:$Hi:$Di class='PHStatus Group$Di Home$Hi' $Hide>" . $Proj[$Turn][$Hi][$Di]['Status'] . "";
          
          $TotCost += $Proj[$Turn][$Hi][$Di]['Cost'] + $Proj[$Turn][$Hi][$Di]['Rush']*Rush_Cost($Fid);
          
        } else {
          echo "<td $BG id=ProjN$Turn:$Hi:$Di class='PHName Home$Hi'>";
          echo "<td $BG id=ProjL$Turn:$Hi:$Di class='PHLevel Group$Di Home$Hi' $Hide>\n";
          echo "<td $BG id=ProjC$Turn:$Hi:$Di class='PHCost Group$Di Home$Hi' $Hide>\n";
          echo "<td $BG id=ProjR$Turn:$Hi:$Di class='PHRush Group$Di Home$Hi' $Hide>\n";
          echo "<td $BG id=ProjP$Turn:$Hi:$Di class='PHProg Group$Di Home$Hi' $Hide>\n";
          echo "<td $BG id=ProjT$Turn:$Hi:$Di class='PHStatus Group$Di Home$Hi' $Hide>\n";
        }
      }
//      break;
    }
    
    echo "<td id=TotCost$Turn class=PHTurn>$TotCost<td class=PHTurn>";

    $Spend = 0;  
    if ($Turn >= $GAME['Turn']) {
      $Bs = Get_BankingFT($Fid,$Turn);
      foreach($Bs as $B) $Spend += $B['Amount'];
    }
    
    if ($Turn == $GAME['Turn']) {    
      $Left = $FACTION['Credits'] - ($SkipProgress?0:$TotCost) - $DeepSpace -$LinkCosts -$Spend;
      if ($Left >=0 ) { 
        echo $Left;
      } else {
        echo "<span class=Red>$Left</span>";
      }
    } else if ($Turn < $GAME['Turn']-1) {
      echo "?";
    } else if ($Turn == $GAME['Turn']-1) {
      echo $FACTION['Credits'];
    } else { // Future
      $Left = $Left + $Income - $TotCost - $Spend;
      if ($Left >=0 ) { 
        echo $Left;
      } else {
        echo "<span class=Red>$Left</span>";
      }      
    }
  }
  echo "</table></form>";    
  
  echo "<table border>";
  if (Access('God')) echo "<tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea>";
  echo "</table>";
  
  //<script>ToggleAllBut($FirstHome)</script>";

  
  dotail();
        
  
  // button to show history
  
  // Header
  
  // List of places, click to expand, click to shrink
  
  // History is wanted (2 before, current next 7)
  // Each Turn colour X for old turns, white current, pale yellow future

  // Project state
  
  // Buttons to start/change/ Up/down for rushing - total costs shown also total money spend against available
  
  // Future plans - changeable
  
  // Not Deep space, Not Agents
  
  // [S] Start Project, where, when, who [warning if it canels existing project]
  
?>
