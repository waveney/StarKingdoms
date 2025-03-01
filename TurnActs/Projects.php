<?php

function StartProjects() {
  global $GAME,$GAMEID;
  //  echo "Start Projects are currently Manual<p>";
  // Find all projects with 0 progress due to start this turn
  // Pay costs, Status = Started

  $Facts = Get_Factions();
  $ProjTypes = Get_ProjectTypes();
  $Projects = Get_Projects_Cond("GameId=$GAMEID AND Status=0 AND TurnStart=" . $GAME['Turn']);
  $TTypes = Get_ThingTypes();
  //var_dump("Projects",$Projects);


  foreach ($Projects as $P) {
    //  var_dump("Project",$P);
    $PT = $ProjTypes[$P['Type']];
    $Cost = $P['Costs'];
    $Fid = $P['FactionId'];
    $T = 0;

    if ($ProjTypes[$P['Type']]['Props'] & 2) { // Has a thing
      $Where = Where_Is_Home($P['Home']);
      $T = 0;

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

    if ($T && ($T['BuildFlags'] & BUILD_FLAG1 )) {
      if ($Facts[$Fid]['Currency3'] < $T['Level']) {

//        $P['Status'] = 5; // Not Started
        TurnLog($P['FactionId'],'Not enough ' . Feature('Currency3','Unknown') . ': ' . $P['Name']) . " building of " . $T['Name'] . " proceding without it.";
        GMLog($Facts[$P['FactionId']]['Name'] . ' Not enough ' . Feature('Currency3','Unknown') . ': ' . $P['Name']  . " building of " . $T['Name'] .
           " proceding without it.",1);
        $T['BuildFlags'] = ($T['BuildFlags'] & (~BUILD_FLAG1));
        Put_Thing($T);
      } else {
        $Facts[$Fid]['Currency3'] -= $T['Level'];
        Put_Faction($Facts[$Fid]);
        TurnLog($P['FactionId'],'Spent ' . $T['Level'] . ' ' . Feature('Currency3','Unknown') . ' starting ' . $T['Name']);
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
    $OPs = Get_Projects_Cond("GameId=$GAMEID AND Home=$home AND Status=1 ");
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

function ProjectProgressActions($Pay4=0) {
  // Mark progress on all things, if finished change state appropriately
  //  echo "Project Progress is currently Manual<p>";
  // Note the rushes have already been paid for in the almost identicalcode PayForRushes above.
  global $GAME,$GAMEID;

  $ProjTypes = Get_ProjectTypes();
  $Projects = Get_Projects_Cond("GameId=$GAMEID AND Status=1");
  $DistTypes = Get_DistrictTypes();
  $ThingTypes = Get_ThingTypes();
  $Factions = Get_Factions();
  $Worlds = Get_Worlds();
  $Homes = Get_ProjectHomes();
  $DTNames = NamesList($DistTypes);
  $NameDTs = array_flip($DTNames);

  foreach ($Projects as $P) {
    // var_dump($P);
    if ($P['LastUpdate'] >= $GAME['Turn']) continue;
    GMLog("Updating project " . $P['id'] . " " . $P['Name']);

    $H = $Homes[$P['Home']];
    $Wid = 0;
    $Fid = $P['FactionId'];
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
      else if ($P['DType'] == $NameDTs['Military']) { $PT['Category'] = 4; }
      else if ($P['DType'] == $NameDTs['Shipyard']) { $PT['Category'] = 2; }
//      else if ($P['DType'] == $NameDTs['Intelligence']) { $PT['Category'] = 8; }
      else if ($P['DType'] == $NameDTs['Academic']) { $PT['Category'] = 1; }
      else if ($P['DType'] == $NameDTs['Orbital Repair']) { $PT['Category'] = 2; }
      // else default actions
    }

    //    var_dump($P,$PT);

    /*
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
    */
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
        case 1:
          $MaxActs = $Dists[$NameDTs['Academic']]['Number'];
          if (Has_Trait($Fid,'Masters of Energy Manipulation'))
            if (strstr($PT['Name'],'Research')) {
              $Tid = $P['ThingType'];
              $Tech = Get_Tech($Tid);
              if (($Tech['Field']??0) == 1) $MaxActs++;
            }
          break;
        case 2: $MaxActs = $Dists[$NameDTs['Shipyard']]['Number']; break;
        case 4: $MaxActs = $Dists[$NameDTs['Military']]['Number']; break;
        case 8: $MaxActs = $Dists[$NameDTs['Intelligence']]['Number']; break;
        case 16: $MaxActs = $Dists[$NameDTs['Industrial']]['Number']; break;
        default:
          GMLog("<b>Confused state for project " . $P['id'] . "</b><p>");
      }
 //   }

    // echo "Maxacts . $MaxActs<br>";

    // Find Project Home
    // Find Dists or Skill to base on
    // Set maxact from num dists or skill


    $TurnStuff = Get_ProjectTurnPT($P['id'],$GAME['Turn']);

    $Rush = 0;
    $FreeRush = $P['FreeRushes'];
    $Bonus = 0;
    if (!empty($TurnStuff['Bonus'])) $Bonus = $TurnStuff['Bonus'];

    if (Has_Trait($Fid,"I Don't Want To Die")) {
      $PNam = $ProjTypes[$P['Type']]['Name'];
      if ($PNam == 'Train Detachment' || $PNam == 'Reinforce Detachment' || $PNam == 'Refit Detachment') $Bonus -= 1;
    }

    $PActs = $Acts = min($MaxActs,$P['ProgNeeded']-$P['Progress']-$Bonus);

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
          if (Spend_Credit($P['FactionId'],$Rc = (Rush_Cost($P['FactionId'],$P['Type'],$P['Home'])*$Rush), 'Rushing ' . $P['Name'] . " By $Rush")) {
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
      GMLog("Updating project " . $P['id'] . " " . $P['Name'] . " with ". ($Acts+$Rush+$Bonus+$FreeRush) . " progress");
      $P['LastUpdate'] = $GAME['Turn'];
      Put_Project($P); // Note completeion is handled later in the turn sequence
    }
  }

  return 1;
}




function ProjectProgress() {
  ProjectProgressActions(0); // Progress, payment already made
  GMLog("All Projects Progressed<p>");
  return 1;
}

function PayForRushes() {
  ProjectProgressActions(1); // Pay 4 rushes only
  GMLog("Rushes paid for<p>");
  return 1;
}

function ProjectsCompleted($Pass) {
  //  echo "Projects Complete is currently Manual<p>";
  // Pass 0 = Refits only, 1 = rest

  global $GAME,$GAMEID,$Currencies,$Facts,$ARMY;

  $ProjTypes = Get_ProjectTypes();
  $Projects = Get_Projects_Cond("GameId=$GAMEID AND Status=1 AND Progress>=ProgNeeded");
  $OTypes = Get_OrgTypes();
  $TTypes = Get_ThingTypes();
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
            $Off = ['Organisation' => -$P['ThingType'], 'OrgType'=>$P['ThingId'], 'World'=>$World['id'], 'Whose'=>$P['FactionId'], 'Number'=>1];
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
          Calc_Scanners($T);
          $T['ProjectId'] = 0;
          TurnLog($Fid, $T['Name'] . " has been launched" . (Feature('Shakedowns')?" and will now start its shakedown cruise":''),$T);
          Put_Thing($T);

          if ($P['Level'] > 1 && $TTypes[$T['Type']]['Name'] == 'Fighter' && Has_Tech($Fid,'Advanced Fighter Construction') ) {
            $Number = ([1,1,3,6,10,15,21,28,36][$P['Level']]??1);
            $OrigName = $T['Name'];
//            var_dump($Number);
            for ($Dup = 2;$Dup<=$Number;$Dup++) {
              $DT = Thing_Duplicate($T['id']);
              $DT['Name'] = "$OrigName $Dup";
              $DT['SystemId'] = $T['SystemId'];
              $DT['WithinSysLoc'] = $T['WithinSysLoc'];
              Put_Thing($DT);
              TurnLog($Fid, $DT['Name'] . " has been launched" . (Feature('Shakedowns')?" and will now start its shakedown cruise":''),$DT);
            }
          }

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

        case 'Academic Contemplation':
          Gain_Science($Fid, rand(1,3), 30, 'Academic Contemplation');

          TurnLog($Fid,"Gained 30 Science points from Academic Contemplation");
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


