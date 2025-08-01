<?php

// Scan all plantets/moon's/Thing's for districts and deep space construction

  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("PlayerLib.php");
  include_once("SystemLib.php");
  include_once("ProjLib.php");

  global $FACTION,$ADDALL,$GAME,$ARMY, $ARMIES, $GAMEID;

// var_dump($_REQUEST);

  $HomeColours = ['#ff99ff', '#ccffff', '#ccffcc', '#ffffcc', '#ffcccc', '#e6ccff', '#cce6ff', '#ffd9b3', '#ecc6c6', '#ecc6d6', '#d6b3ff', '#d1e0e0', '#d6ff99',
     '#ffb3ff', '#b3b3ff', '#b3ffff', '#b3ffb3', '#ffffb3', '#ffb3b3', '#ecc6c6', '#ffb3cc', '#ffb3d9', '#ecc6d9', '#ffb3ff', '#ecc6ec', '#ecb3ff', '#e0b3ff',
     '#d9b3ff', '#d1d1e0', '#c6c6ec', '#b3b3ff', '#b3ccff', '#c2d1f0', '#c6d9ec', '#b3d9ff', '#d1e0e0', '#c6ecd9', '#d9ffb3', '#e5e5cc', '#e0e0d1', '#ecd9c6',
     '#ffd9b3', '#ffe6b3', '#ffc6b3', '#ffccb3',

   ];

  A_Check('Player');
  $mtch = [];

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

  if (!$GM && $Faction['TurnState'] > 2) Player_Page();
  dostaffhead("Display Projects for faction",["js/ProjectTools.js"]);

  $OpenHi = $OpenDi = -99;
  $ProjTypes = Get_ProjectTypes();
  $PostItType = 0;
  $ExtraCosts = [0,0,0,0];

  foreach($ProjTypes as $Pti=>$Pt) if ($Pt['Name'] == 'Post It') { $PostItType=$Pti; break;}

  $ThingTypes = Get_ThingTypes();

  if (isset($_REQUEST['ACTION'])) {
    $Name = base64_decode($_REQUEST['Name']??'');
    $OrgName = $OrgDest = '';
    $OrgSP = $TType = 0;

    switch ($_REQUEST['ACTION']) {  // TODO This code is DREADFUL needs redoing
      case 'NEWORG':
        $OrgName = $_REQUEST['NewOrgName'];
        $OrgDesc = $_REQUEST['NewOrgDescription'];
        $_REQUEST['Sel'] = $_REQUEST['NewOrgType'];
        $_REQUEST['Sel2'] = $_REQUEST['NewOrgType2'];
        $OrgSP = $_REQUEST['NewOrgSocialPrinciple'];
        $Sel = 0;

        /*
        $NOrg = ['Whose'=>$Fid, 'OrgType' => $_REQUEST['NewOrgType'], 'Name'=> $_REQUEST['NewOrgName'], 'Description'=>$_REQUEST['NewOrgDescription'],
                 'SocialPrinciple' => $_REQUEST['NewOrgSocialPrinciple'], 'OfficeCount'=>0, 'GameId'=>$GAMEID];
        $Orgid = Gen_Put('Organisations',$NOrg);
        $_REQUEST['Sel'] = -$Orgid;*/

        if (str_contains($Name,'NNEEWW')) {
          $Name = preg_replace('/NNEEWW/',$_REQUEST['NewOrgName'],$Name);
        }
        // Drop through

      case 'NEW':
        $Ptype = $_REQUEST['p'];
        $Turn = $_REQUEST['t'];
        $Hi = $_REQUEST['Hi'];
        $Di = $_REQUEST['Di'];
        $DT = (isset($_REQUEST['DT'])? $_REQUEST['DT'] : 0);
        $Valid = 1;
        $FreeRush = 0;
        $TType = $TthingId = $_REQUEST['Sel']??0;
        $TthingId2 = $_REQUEST['Sel2']??0;
        switch ($ProjTypes[$Ptype]['Name']) {
        case 'Construct Ship':
        case "Train $ARMY":
        case 'Train Agent':
          $T = Get_Thing($_REQUEST['ThingId']);
          if ($T['BuildState'] > 0) {
            $T = Thing_Duplicate($T['id']);
          }
          $Level = $T['Level'];
          $pc = Proj_Costs($Level);
          if ($ProjTypes[$Ptype]['Name'] == "Train $ARMY" && Has_Tech($Fid,'Efficient Robot Construction')) $pc[0] = max(1, $pc[0] - $T['Level']);
          if ($ProjTypes[$Ptype]['Name'] == 'Construct Ship' && Has_Tech($Fid,'Space Elevator')) $pc[0] = max(1, $pc[0] - $T['Level']);
          $Costs = $pc[1];
          $ProgN = $pc[0];
          $TthingId = $T['id'];
          $TType = $T['Type'];
          $PH = Get_ProjectHome($Hi);
          switch ($PH['ThingType']) {
          case 1:
            $Place = Get_Planet($PH['ThingId']);
            break;
          case 2:
            $Place = Get_Moon($PH['ThingId']);
            break;
          case 3:
            $Place = Get_Thing($PH['ThingId']);
            break;
          }
          $ExtraCosts = OtherCosts($TthingId);
          $Name = "Build " . $T['Name'] . " (level $Level) on " . $Place['Name'] ;
          break;

        case 'Share Technology':
          $Tech2S = $_REQUEST['Tech2Share'];
          preg_match('/(\d*):(\d*)/',$Tech2S,$mtch);
          $With = $_REQUEST["ShareWith"];
          $Level = $mtch[2];
          $pc = Proj_Costs($Level-1);
          $Costs = $pc[1];
          $ProgN = $pc[0];
          $TType = $Sel = $mtch[1];
          $Tech = Get_Tech($Sel);
          $Fact = Get_Faction($With);
          $Name = "Share " . $Tech['Name'] . " at level $Level with " . $Fact['Name'];
          if (Get_Things_Cond($Fid, " Type=17 AND OtherFaction=$With ")) {
            $FreeRush=1;
          }
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
            echo "<h2 class=Err>No Postit Message given</h2>";
            $Valid = 0;
            break;
          }
          break;

        case 'Seek Enemy Agents' :
          $Level = $_REQUEST['Level'];
          $pc = Proj_Costs($Level);
          $Costs = $pc[1];
          $ProgN = $pc[0];
          $Name = 'Seek Enemy Agents';
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


        case 'Construction':
        case 'Build Office':
        case 'Build Head Office':
          // Mostly default apart from Lewis...
          if (Has_PTraitH($Hi,'New Home')) {
            $FreeRush = 99;  // Will be reduced to actual limit
          }
          // Drop through

        case 'Grow District':
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
        case 'Construct Warp Gate':
        case 'Decipher Alien Language':
        case 'Rebuild and Repair':
        case 'Grow Modules' :
        case 'Produce Adianite' :
        default:

          if (isset($_REQUEST['Sel'])) $Sel = $_REQUEST['Sel'];
          $Level = $_REQUEST['L'];
          $Costs = $_REQUEST['C'];
          $ProgN = $_REQUEST['PN'];
          break;

        }

        if ($Valid) {
          $OldPro = Get_ProjectAt($Hi, $DT, $Turn);
          $Pro = ['FactionId'=>$Fid, 'Type'=>$Ptype, 'Level'=> $Level, 'Home'=>$Hi, 'Progress'=>0, 'Status'=>0, 'TurnStart'=>$Turn, 'Name'=>$Name,
                  'Costs' => $Costs + $ExtraCosts[0], 'ProgNeeded' => $ProgN, 'BuildState'=>0, 'DType' => $DT, 'FreeRushes'=>$FreeRush,
                  'ThingType'=>($TType??0),'CostCur1'=>$ExtraCosts[1],'CostCur2'=>$ExtraCosts[2],'CostCur3'=>$ExtraCosts[3]
          ];
          if (isset($With)) $Pro['ThingId'] = $With;
          if (isset($TthingId)) $Pro['ThingId'] = $TthingId;
          if (isset($TthingId2)) $Pro['ThingId2'] = $TthingId2;
          if ($OrgName) $Pro['OrgName'] = $OrgName;
          if (isset($OrgDesc)) $Pro['OrgDesc'] = $OrgDesc;
          if ($OrgSP) $Pro['OrgSP'] = $OrgSP;

          if (isset($OldPro['id'])) {
            $Pro['id'] = $OldPro['id'];
          }

          $OpenHi = $Hi;
          $OpenDi = $Di;
//          var_dump($Pro);
          Put_Project($Pro);
          $Pid = $Pro['id'];

 //         var_dump($Pro,$TthingId,$TthingId2,$ProjTypes[$Ptype]);

          if ($TthingId??0) {
            $T1 = Get_Thing(($TthingId));
            $T1['ProjectId'] = $Pid;
            if ($ProjTypes[$Ptype]['Props'] & 0x400) $T1['BuildState'] = BS_SERVICE;
            Put_Thing($T1);
            if ($TthingId2??0) {
              $T1 = Get_Thing(($TthingId2));
              $T1['ProjectId'] = $Pid;
              if ($ProjTypes[$Ptype]['Props'] & 0x400) $T1['BuildState'] = BS_SERVICE;
              Put_Thing($T1);
            }
          }
        }
      break;

    }
  }

  echo "<h1>Display Projects</h1>";
  $LookForward = Faction_Feature('Projects_Forward',10);
  $LookBack = Faction_Feature('Projects_Back',-1);

  echo "<div class=floatright style=text-align:right><div class=Bespoke>" .
       "Showing:<button class=BigSwitchSelected id=BespokeM onclick=Add_Bespoke()>$LookForward Turns</button><br>" .
       "Switch to: <button class=BigSwitch id=GenericM onclick=Add_Bespoke()>All Turns</button></div>" .
       "<div class=Bespoke hidden id=BespokeMess>" .
       "Showing:<button class=BigSwitchSelected id=GenericM1 onclick=Remove_Bespoke()>All Turns</button><br>" .
       "Switch to: <button class=BigSwitch id=BespokeM1 onclick=Remove_Bespoke()>$LookForward Turns</button></div>" .
       "<h2><a href=ProjDisp.php>Refresh</a></h2>" .
       "</div>";

  echo "Click on Place to Expand/Contract<br>";
  echo "Click on Showing Options top right to see more than 2 turns back<br>\n";
  echo "Click on the World, Distict type or Construction to expand/contract that area<p><p>";
  echo "Click on <button type=submit class=PHStart id=StartExample formaction=''>+</button> buttons to start/change projects<br>\n";
  echo "Click up/down or write number to rush projects<br>\n";

  echo "Note the cost totals are on the far right<br>" .
       "The credits left on current turn is a rough guide only - it does not take account of other expenditure other than for the current turn " .
       "- or any additional income.<p>";

  echo "Note 2: The amount of progress before the end of the previous turn is at best a guess.  " .
       "If the number of districts/planetary construction has changed they will be wrong.<p>\n";

//  echo "Currently this display is for district based projects only.<br>\n";

  $Homes = Get_ProjectHomes($Fid);
  $DistTypes = Get_DistrictTypes();

//  $Income = Income_Estimate($Fid);
  [$EcVal,$txt] = Income_Calc($Fid);
  $Income = $EcVal*10;
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

  foreach($Homes as &$H) $H['RelOrder'] = 0;

  // Reoder Homes based on Worlds Importance
  $Worlds = Get_Worlds();
  foreach ($Worlds as $W) {
    if (isset($Homes[$W['Home']])) $Homes[$W['Home']]['RelOrder'] = $W['RelOrder'];
  }

  usort($Homes, function ($a, $b) {
    return $b['RelOrder'] - $a['RelOrder'];
  });

  $BPlanCon = Has_Tech($Fid,3);
  $HWorld = ($Faction['HomeWorld']> 0?Get_World($Faction['HomeWorld']):0);
  $HWorldHome = ($HWorld['Home']??0);

  $PHx = 1;
  $Dis = [];
  $FirstHome = 0;
  $NoC = 0;
  $proj = [];

  $Things = Get_Things_Cond($Fid," Instruction!=0 AND Progress=0 AND InstCost!=0 ");
  $DeepSpace = 0;
  foreach($Things as $T) $DeepSpace += $T['InstCost'];

  $AllLinks = Get_LinksGame();
  $LLevels = Get_LinkLevels();
  $Things = Get_Things_Cond($Fid,"LinkId>0 AND LinkCost>0 AND LinkPay>0");
  $LinkCosts = 0;
  foreach($Things as $T) {
    if ($LLevels[$AllLinks[$T['LinkId']]['Level']]['Cost'] == 0) continue;
    $LinkCosts += $T['LinkCost'];
  }

  $BonusRushes = Has_Trait($Fid,'Built for Construction and Logistics');
  $ShowOtherCat = 0;

  foreach ($Homes as &$H) {
    $ButAdd = '';
    $PlanCon = $BPlanCon;
    // Homeworld +1, bio =, desolate -2, other -1
    $Hi = $H['id'];

    if ($Hi == $HWorldHome) $PlanCon++;
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
        foreach($DistTypes as $DT) {
          if ($DT['Name'] == 'Orbital Repair') {
            $ORY = $DT['id'];
            $N = Get_System($PH['SystemId']);
            $ButAdd = " (" . $N['Ref'] . ")";
          }
          if (($DT['Props'] & 3) == 2) $ShowOtherCat = 1;
        }
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
    if ($PH['Type'] == 4) {
      $PlanCon-=2;
    } else if (($H['ThingType'] != 3) && ($PH['Type'] != $Faction['Biosphere']) &&
       ($PH['Type'] != $Faction['Biosphere2']) && ($PH['Type'] != $Faction['Biosphere3'])) $PlanCon--;
    //TODO Construction and Districts...

    if ($NoC != 1 ) $Dists[] = ['HostType'=>-1, 'HostId' => $PH['id'], 'Type'=> -1, 'Number'=>0, 'id'=>-$PH['id']];
    $back = "style='background:" . $HomeColours[$PHx-1] . ";'";
    $Headline1 .= "<th id=PHH$PHx $back><button type=button class=ProjHome id=PHome$Hi onclick=ToggleHome($Hi)>" . $PH['Name'] . "$ButAdd</button>";
// if($Hi==199) { echo " THere"; var_dump($Headline1); };
    if ($FirstHome == 0) $FirstHome=$Hi;

    $District_Type = [];
    foreach ($Dists as $D) {


      if ($D['Type'] > 0 && (($DistTypes[$D['Type']]['Props'] &2) == 0)) continue;
      if ($D['Type'] < 0 ) continue;
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
        $HL .= $DistTypes[$D['Type']]['Name'] . "&nbsp;" . $D['Number'];
      }

      $HL .= "$ButAdd</button><th $back class='PHLevel Group$Di Home$Hi'id=PHLevel$Hi:$Di $Hide>Lvl" .
        "<th $back class='PHCost Group$Di Home$Hi' id=PHCost$Hi:$Di $Hide>Cost" .
        "<th $back class='PHRush Group$Di Home$Hi' id=PHRush$Hi:$Di $Hide>Rush";

//        "<th $back class='PHBonus Group$Di Home$Hi' id=PHBonus$Hi:$Di $GMHide>B" .
      $HL .= "<th $back class='PHProg Group$Di Home$Hi' id=PHProg$Hi:$Di $Hide>Prog" .
        "<th $back class='PHStatus Group$Di Home$Hi' id=PHStatus$Hi:$Di $Hide>Status";

      $Headline2[] = $HL;
      $District_Type[$D['Type']] = $D['Number'];
      $Dis[$Hi][] = $D;
      }


    $Projs = Get_Projects($Hi);

//    var_dump($Projs);echo "BEFORE<p>";

    foreach ($Projs as &$P) {
//      var_dump($P);
      if (!isset($P['Status']) || ($P['Status'] > 2 )) continue; // Cancelled, On Hold or Could not start
      $TurnStuff = Get_ProjectTurns($P['id']);
      $TSi = 0;
      while (isset($TurnStuff[$TSi]['TurnNumber']) && ($TurnStuff[$TSi]['TurnNumber'] < $P['TurnStart'])) $TSi++;
//
      $Pro = [];
      $Pro['id'] = $P['id'];
      $Pro['Type'] = $P['Type'];
      $Pro['Name'] = $P['Name']; // $ProjTypes[$P['Type']]['Name'];
      $Pro['Level'] = $P['Level'];
      $Pro['Cost'] = $P['Costs'];
      $Pro['Acts'] = $P['ProgNeeded'];
      $Pro['GMOverride'] = $P['GMOverride'];
      $Pro['FreeRushes'] = $P['FreeRushes'];

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
        // Is there an existing project where this is going?
        if (isset($proj[$P['TurnStart']][$Hi][$Di]['id'])) {
          $What = $proj[$P['TurnStart']][$Hi][$Di]['id'];
          for ($t = $P['TurnStart']; $t <= $P['TurnStart']+50; $t++) {
            if (isset($proj[$t][$Hi][$Di]['id']) && $proj[$t][$Hi][$Di]['id'] == $What) {
              $proj[$t][$Hi][$Di] = [];
            } else {
              break;
            }
          }
        }

        $TotProg = $P['Progress'];
        for ($t = $P['TurnStart']; $t <= ($P['TurnEnd']?$P['TurnEnd']:$P['TurnStart']+50); $t++) {

          $Pro['Rush'] = $Rush = $BonusRush = $Bonus = 0;
//          $Pro['MaxRush'] = ( ($ProjTypes[$P['Type']]['Category'] & 16) ? $PlanCon : $District_Type[$WantedDT]);
          $Pro['MaxRush'] = (( $WantedDT < 0) ? $PlanCon : $District_Type[$WantedDT]);
          if (Has_Trait($Fid,'Masters of Energy Manipulation'))
            if (strstr($PPtype['Name'],'Research')) {
              $Tid = $P['ThingType'];
              $Tech = Get_Tech($Tid);
              if (($Tech['Field']??0) == 1) $Pro['MaxRush']++;
            }

          if ($P['FreeRushes']) $Pro['Rush'] = $Rush = $Pro['MaxRush'];

/*            $Pro['MaxRush'] =  (($ProjTypes[$P['Type']]['BasedOn'])? Has_Tech($Fid,$ProjTypes[$P['Type']]['BasedOn'],$t) :
               (isset ($District_Type[5]) ?$District_Type[5]:0)); */
          if ($BonusRushes && preg_match('/Research/',$ProjTypes[$P['Type']]['Name'],$mtch) ) {
            $TechId = $P['ThingType'];
            $Tech = Get_Tech($TechId);
            if ($Tech['PreReqTech'] == 1 || $TechId == 1) {
              $BonusRush = 1; // min(1,$Acts,$P['ProgNeeded']-$P['Progress']-$Acts-$Bonus);
            }
         }

          if (isset($TurnStuff[$TSi])) {
            if ($TurnStuff[$TSi]['TurnNumber'] == $t) {
              $Rush = $Pro['Rush'] = min($TurnStuff[$TSi]['Rush'], $Pro['MaxRush']);
              if (!empty($TurnStuff[$TSi]['Bonus'])) $Bonus = $Pro['Bonus'] = $TurnStuff[$TSi]['Bonus'];
              $TSi ++;
            }
          }

          if (Has_Trait($Fid,"I Don't Want To Die")) {
            $PNam = $ProjTypes[$P['Type']]['Name'];
            if ($PNam == 'Train Detachment' || $PNam == 'Reinforce Detachment' || $PNam == 'Refit Detachment') $Bonus = -1;
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
            $Prog = min($Pro['Acts'] - $TotProg,$Pro['MaxRush'] + $Rush + $Bonus+ $BonusRush ); // Note Bonus can be negative
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
              $Pro['Status'] = 'Started' ;
          }
*/

          $proj[$t][$Hi][$Di] = $Pro;
          if ($t == $P['TurnStart'] && isset($proj[$t-1][$Hi][$Di]) && isset($proj[$t-1][$Hi][$Di]['Status']) &&
            ($proj[$t-1][$Hi][$Di]['Status']!='Complete')) {
              $proj[$t-1][$Hi][$Di]['Status'] = "<b class=Err>Not Complete</b>";
          }
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

  /*
  echo "<H2>Category:</h2>";
  echo "<table border style='width:auto;height:50px;'><tr>";

  $DTypes = Get_DistrictTypes();
  foreach ($DTypes as $DCat => $Dty) {
    if ($Dty['Props'] &2 ) echo "<th id=TCat$DCat><button type=button class=ProjCat id=PCat$DCat onclick=ToggleCat($DCat)>" . $Dty['Name'] . "</button>";
  }
  echo "<th id=TCat-1 ><button type=button class=ProjCat id=PCat-1 onclick=ToggleCat(-1)>Construction</button>";
  if ($ShowOtherCat) echo "<th id=TCat-2 ><button type=button class=ProjCat id=PCat-2 onclick=ToggleCat(-2)>Other</button>";

  echo "</table>";  */
  echo "<h2>Projects:</h2>";
  echo "<table border>";



  echo "<tr><td>Turn";
  foreach ($Headline2 as $Hl) echo $Hl;
  echo "<th>Total Cost";
  if (Feature('ProjMiscCosts',0)) echo "<th>Misc Costs";
  echo "<th>Credits Left";
  for($Turn=0; $Turn<($GAME['Turn']+50); $Turn++) {
    $RowClass = "ProjHide";
    $hide = ' hidden';
    if ($Turn >= ($GAME['Turn']+$LookBack) && $Turn < ($GAME['Turn']+$LookForward-1)) {
      $RowClass = 'projhow';
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

        echo "<td $BG id=proj$Turn:$Hi:$Di class='PHStart Group$Di Home$Hi' $Hide>\n";
        if ($Turn >= $GAME['Turn'] && $ADDALL!='readonly') {
          $Warn = '';
          if (isset($proj[$Turn - 1 ][$Hi][$Di]['Status']) &&
              ($proj[$Turn - 1 ][$Hi][$Di]['Status'] == 'Started' || $proj[$Turn - 1][$Hi][$Di]['Status'] == 'Ongoing')) {
              $Action = "onclick='return NewProjectCheck($Turn,$Hi,$Di)' formaction=ProjNew.php?t=$Turn&Hi=$Hi&Di=$Di";
          } else {
            $Action = "formaction=ProjNew.php?t=$Turn&Hi=$Hi&Di=$Di";
          }
          echo "<button type=submit class=PHStartButton id=Start:STurn:$Hi:$Di $Action ><b>+</b>\n";
        }
        if (isset($proj[$Turn][$Hi][$Di]['Type'])) {
          $PN = $proj[$Turn][$Hi][$Di]['id'];
          echo "\n<td $BG id=ProjN$Turn:$Hi:$Di class='PHName Home$Hi" . ($proj[$Turn][$Hi][$Di]['Type'] == $PostItType?" PHpostit ":"") . "'>" .
                "<a href=ProjEdit.php?id=" . $proj[$Turn][$Hi][$Di]['id'] . ">";
          if ($proj[$Turn][$Hi][$Di]['id'] != ($proj[$Turn-1][$Hi][$Di]['id']??0)) echo "<b>";
          echo $proj[$Turn][$Hi][$Di]['Name'] . "</b></a>";
          echo "\n<td $BG id=ProjL$Turn:$Hi:$Di class='PHLevel Group$Di Home$Hi' $Hide>" . $proj[$Turn][$Hi][$Di]['Level'];
          echo "\n<td $BG id=ProjC$Turn:$Hi:$Di class='PHCost Group$Di Home$Hi' $Hide>" . $proj[$Turn][$Hi][$Di]['Cost'];
          echo "\n<td $BG id=ProjR$Turn:$Hi:$Di class='PHRush Group$Di Home$Hi' $Hide>" . (($Turn < $GAME['Turn'])?$proj[$Turn][$Hi][$Di]['Rush'] :
               "<input type=number id=Rush$Turn:$PN name=Rush$Turn:$PN oninput=RushChange($Turn,$PN,$Hi,$Di," .
               $proj[$Turn][$Hi][$Di]['MaxRush'] . ",'Projects') value=" . min($proj[$Turn][$Hi][$Di]['Rush'],$proj[$Turn][$Hi][$Di]['MaxRush'])  .
               " min=0 max=" . ($proj[$Turn][$Hi][$Di]['GMOverride']?20:$proj[$Turn][$Hi][$Di]['MaxRush']) .">" );
//          if (!empty($Proj[$Turn][$Hi][$Di]['Bonus'])) echo "<div id=ProjB$Turn:$Hi:$Di class='PHBonus hidden>" . $Proj[$Turn][$Hi][$Di]['Bonus'] . "</div>";
          echo "\n<td $BG id=ProjP$Turn:$Hi:$Di class='PHProg Group$Di Home$Hi' $Hide>" . $proj[$Turn][$Hi][$Di]['Progress'];
          echo "\n<td $BG id=ProjT$Turn:$Hi:$Di class='PHStatus Group$Di Home$Hi' $Hide>" . $proj[$Turn][$Hi][$Di]['Status'] . "";

          $TotCost += $proj[$Turn][$Hi][$Di]['Cost'] +
          ( $proj[$Turn][$Hi][$Di]['FreeRushes']?0:($proj[$Turn][$Hi][$Di]['Rush']*Rush_Cost($Fid,$proj[$Turn][$Hi][$Di]['Type'],$Hi)));

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
//var_dump($Bs);
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
//var_dump($Left,$Income,$TotCost,$Spend);\
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
  if (Access('God')) echo "</tbody><tfoot><tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea>";
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
