<?php

function Instructions() {
  global $ThingInstrs,$GAME;
  global $Currencies,$ValidMines;
  global $FACTION;

  $Things = Get_Things_Cond(0,"Instruction>0");
  $NeedColStage2 = 0;
  $Facts = Get_Factions();
  $Systems = Get_SystemRefs();
  $DTypes = Get_DistrictTypes();
  $DistNames = NamesList($DTypes);
  $NamesDists = array_flip($DistNames);

  $PTs = Get_ProjectTypes();
  $TTypes = Get_ThingTypes();
  $AAs = [];
  $Stage2Link = "<form method=post action=TurnActions.php?ACTION=DoStage2&Stage=Instructions>";

  foreach ($Things as $T) {
    if ( $T['BuildState'] != BS_COMPLETE) {
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
        if ($T['InstCost'] && !Spend_Credit($T['Whose'],$T['InstCost'],"Colonise in " . $N['Ref']) ) {
          $T['Progress'] = -1; // Stalled
          TurnLog($T['Whose'],"Could not afford to start Colony in " .$N['Ref'],$T);
          GMLog($Facts[$T['Whose']]['Name'] . " Could not afford to start Colony in " .$N['Ref']);
          break;
        }
        $P = Get_Planet($T['Spare1']);
        if ($N['Control'] > 0 && $N['Control'] != $T['Whose']) {  // Colonising system under control of others
          if ($NeedColStage2 == 0) {
            GMLog($Stage2Link);
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
              GMLog($Stage2Link);
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

      case 'Disband': // Dissasemble
      case 'Decommision': // Dissasemble
      case 'Retire': // Retire Agent
        switch ($ThingInstrs[$T['Instruction']]) {

          case 'Disband': // Dissasemble
            $Tec = Feature('MilTech');
            $Dist = 'Military';
            $txt = 'disbanded';
            break;
          case 'Decommision': // Dissasemble
            $Tec = Feature('Ship Construction');
            $Dist = 'Shipyard';
            $txt = 'decommisioned';
            break;
          case 'Retire': // Retire Agent
            $Tec = 'Intelligence Operations';
            $Dist = 'Intelligence';
            $txt = 'retired';
            break;

        }

        $Lvl = $T['Level'];
        $Loc = $T['SystemId'];
        $Fid = $T['Whose'];

        $Homes = Gen_Get_Cond('ProjectHomes', "SystemId=$Loc");

        if (!$Homes) {
          TurnLog($T['Whose'], "The " . $T['Name'] . " could not be $txt as no home found", $T);
          GMLog($Facts[$T['Whose']]['Name'] . " - " . $T['Name'] . " could not be $txt as no home found");
          break;
        }

        $Rating = -1;
        $SelectedH = [];

        foreach ($Homes as $H) {
          $Rate = 0;
          if ($H['Whose'] == $Fid) $Rate = 2;
          $LDists = Get_DistrictsH($H['id']);
          if ($LDists[$DistNames[$Dist]??0]??0) {
            $Rate += 1;
          }
          if ($Rate > $Rating) {
            $Rating = $Rate;
            $SelectedH = $H;
          }
        }

        $T['BuildState'] = BS_NOT;
        $T['SystemId'] = 0;
        $T['Instruction'] = 0;
        $cash = 10*$Lvl*Has_Tech($SelectedH['Whose'],'Ship Construction');
        TurnLog($T['Whose'], "The " . $T['Name'] . " has been $txt gaining you " . Credit() . $cash, $T);
        Spend_Credit($SelectedH['Whose'],-$cash,"$txt " . $T['Name']);
        GMLog($Facts[$T['Whose']]['Name'] . " - " . $T['Name'] . " has been $txt");

        $Have = Get_Things_Cond(0," (LinkId<0 AND SystemId=$Tid) ");
        $DWin = $DLid = 0;
        $DSys = $Loc;
        $N = Get_System($Loc);
        if ($Have) {
          switch ($SelectedH['ThingType']) {
          case 3: // Thing
            $DLid = -1;
            $DWin = 0;
            $Host = Get_Thing($Loc);
            break;

          case 1: // Planet
            $DWin = Within_Sys_Locs($N,$SelectedH['ThingType']);
            $Host = Get_Planet($SelectedH['ThingType']);
            break;

          case 2 : // Moon
            $DWin = Within_Sys_Locs($N,-$SelectedH['ThingType']);
            $Host = Get_Moon($SelectedH['ThingType']);
            break;
          }

          foreach ($Have as $H) {
            $H['SystemId'] = $DSys;
            $H['WithinSysLoc'] = $DLid;
            $H['LinkId'] = $DLid;
            TurnLog($T['Whose'],$H['Name'] . " has been offloaded on to " . $Host['Name'] . " in " . $N['Ref'],$H);

            if ($H['Whose'] != $Host['Whose']) TurnLog($H['Whose'],$H['Name'] . " has been offloaded on to " . $Host['Name'] . " in " . $N['Ref'],$H);
            Put_Thing($H);
          }
        }

        db_delete('Things',$Tid);
        continue 2;

/*      case 'Disband': // Dissasemble
        $Lvl = $T['Level'];
        $T['BuildState'] = BS_NOT;
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
        $T['BuildState'] = BS_NOT;
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
        continue 2;*/


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
              GMLog($Stage2Link);
              $NeedColStage2 = 1;
            }
            GMLog("<p><a href=ThingEdit.php?id=$Tid>" . $T['Name'] . "</a> is starting to analyse anomaly <a href=AnomalyEdit.php?id=$Aid>" . $A['Name'] .
              "</a> which has other requirements. <b>" . $A['OtherReq'] . "</b>.  Are they met? " . fm_YesNo("AA$Tid" ,1,$Rtxt="Why" ));
            GMLog("<p>");
            break;
          } else if ($FA['State'] >= 3) {
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
                  GMLog($Stage2Link);
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
          GMLog($Stage2Link);
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
        $PMines = Get_Things_Cond(0,"Type=" . TTName('Planet Mine') . " AND SystemId=" . $N['id'] . " AND BuildState=" . BS_COMPLETE);
        if (isset($PMines[0]) && $PMines[0]['Whose'] == $T['Whose']) break; // Yes fine
        // By others?
        if (empty($phs) && empty($PMines)) {
          TurnLog($T['Whose'],"Attempting to build an orbital repair yard in " . $N['Ref'] . " but there is nothing there to build it round. ",$T);
          $T['Progress'] = -1; // Stalled
          break;
        }
        if ($NeedColStage2 == 0) {
          GMLog($Stage2Link);
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
        $PMines = Get_Things_Cond(0,"Type=" . TTName('Planet Mine') . " AND SystemId=" . $N['id'] . " AND BuildState=" . BS_COMPLETE);
        if (isset($PMines[0]) && $PMines[0]['Whose'] == $T['Whose']) break; // Yes fine
        // By others?
        if (empty($phs) && empty($PMines)) {
          TurnLog($T['Whose'],"Attempting to build a Build Space Station in " . $N['Ref'] . " but there is nothing there to build it round. ",$T);
          $T['Progress'] = -1; // Stalled
          break;
        }
        if ($NeedColStage2 == 0) {
          GMLog($Stage2Link);
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

      default: // everything else

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

        $NT = ['GameId'=>$GAME['id'], 'Type'=> 17, 'Level'=> 1, 'SystemId'=>$T['SystemId'], 'WithinSysLoc'=>$H['WithinSysLoc'], 'Whose'=>$T['Whose'],
          'BuildState'=>BS_COMPLETE, 'TurnBuilt'=>$GAME['Turn'], 'OtherFaction'=>$T['OtherFaction'], 'Name'=>$T['MakeName']];
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

function InstructionsComplete() {
  global $ThingInstrs,$GAME,$ValidMines,$GAMEID;
  $Things = Get_Things_Cond(0,"Instruction!=0 AND Progress>=ActionsNeeded ");
  $NeedColStage2 = 0;
  $Facts = Get_Factions();
  $TTypes = Get_ThingTypes();
  $Systems = [];
  $DTypes = Get_DistrictTypes();
  $DTypeNames = NamesList($DTypes);

// var_dump(array_flip($DTypeNames)); exit;
  foreach ($Things as $T) {
    $N = Get_System($T['SystemId']);
    $Tid = $T['id'];
    $Instr = $ThingInstrs[abs($T['Instruction'])];
    $Who = $T['Whose'];

    switch ($Instr) {
      case 'Colonise':
        $P = Get_Planet($T['Spare1']);
        $P['Control'] = $Who;
        Put_Planet($P);

        $Control = System_Owner($P['SystemId']);
        if ($Control) {
          TurnLog($T['Whose'], $Control);
          GMLog($Control);
        }

        $D = ['HostType' =>1, 'HostId'=> $P['id'], 'Type'=> $T['Dist1'], 'Number'=>1, 'GameId'=>$GAME['id'], 'TurnStart'=>$GAME['Turn']];
        //      if ($D['Type'] == 0) $D['Type'] = 1;
        $D['Type'] = array_flip($DTypeNames)['Industrial'];
        Put_District($D);
        /*
         if (Get_ModulesType($Tid,'Self Repairing Robot Armour') && $T['Dist2']) {
         $D1 = ['HostType' =>1, 'HostId'=> $P['id'], 'Type'=> $T['Dist2'], 'Number'=>1, 'GameId'=>$GAME['id'], 'TurnStart'=>$GAME['Turn']];
         Put_District($D1);
         }*/


        TurnLog($Who,'<p>' . $P['Name'] . " on " . $N['Ref'] . " has been colonised");
        GMLog($P['Name'] . " on " . $N['Ref'] . " has been colonised by " . $Facts[$Who]['Name'],1);  // TODO Check for any named chars and offload
        FollowUp($Who,"Set Social Principles of new colony in " .$N['Ref'] . " - not yet automated");
        Report_Others($T['Whose'], $T['SystemId'],31,$P['Name'] . " on " . $N['Ref'] . " has been colonised by " . $N['Ref']);

        if (Feature('RemoveAfterColonise')){
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
          $T['BuildState'] = BS_NOT;
        }

        $NewHome = ['ThingType'=>1, 'ThingId'=>$P['id'], 'Whose'=>$Who, 'SystemId'=>$P['SystemId'], 'WithinSysLoc'=>$T['WithinSysLoc'],
          'EconomyFactor'=>100, 'GameId'=>$GAMEID ];
        Put_ProjectHome($NewHome);
        $NewWorld = ['FactionId'=>$Who, 'Home'=>$NewHome['id'], 'Minerals'=>$P['Minerals'],'ThingType'=>1, 'ThingId'=>$P['id'], 'GameId'=>$GAMEID ];
        Put_World($NewWorld);

        // Social Principles
        // Get list current worlds
        // If 1 - use that, if many find nearest, copy SPs to new colony

        $Worlds = Get_Worlds($Who);
        if (count($Worlds) == 1) {
          $World = array_pop($Worlds);

          $SocPs = Get_SocialPs($World['id']);
          foreach ($SocPs as $SP) {
            if ($SP['Value']>1) {
              $newSP = ['World'=>$NewWorld['id'],'Principle'=>$SP['Principle'],'Value'=>($SP['Value']-1)];
              Gen_Put('SocPsWorlds',$newSP);
            }
          }

        } else {
          $MinDist = 9;
          foreach ($Worlds as $Wid=>$W) {
            $Home = Get_ProjectHome($W['Home']);
            $Dist = DistanceBetween($Who,$P['SystemId'],$Home['SystemId'],$MinDist);
            if ($Dist < 0) continue;
            if ($Dist < $MinDist) $MinDist = $Dist;
            $Worlds[$Wid]['Distance'] = $Dist;
          }

          $Prins = [];
          foreach ($Worlds as $Wid=>$W) {
            if (($Worlds[$Wid]['Distance']??99) == $MinDist) {
              $SocPs = Get_SocialPs($Wid);
              foreach ($SocPs as $SP) {
                if (($Prins[$SP['Principle']]??0) < $SP['Value']) {
                  $SocPs[$SP['Principle']] = $SP['Value'];
                }
              }
            }
          }

          foreach ($Prins as $P=>$V) {
            if ($V < 2) continue;
            $newSP = ['World'=>$NewWorld['id'],'Principle'=>$P,'Value'=>$V-1];
            Gen_Put('SocPsWorlds',$newSP);
          }
        }

        break;


      case 'Analyse Anomaly':
        $Aid = $T['ProjectId'];
        if ($Aid) {
          $A = Get_Anomaly($Aid);
          $Fid = $T['Whose'];
          $FA = Gen_Get_Cond1('FactionAnomaly',"FactionId=$Fid AND AnomalyId=$Aid");
          if ($FA) {
            if (($FA['Progress'] >= $A['AnomalyLevel']) && ($FA['State'] < 3)) {
              $FA['State'] = 3;
              Gen_Put('FactionAnomaly',$FA);
              TurnLog($Fid ,'<p>' .  $T['Name'] . " Anomaly study on " . $A['Name'] .
                " has been completed - See sperate response from the GMs for what you get");
              if (!empty($A['Completion'])) {
                TurnLog($Fid ,"Completion Text: " . ParseText($A['Completion']) );
                $ctxt = '';
              } else {
                $ctxt = "  AND the completion text.";
              }
              GMLog($Facts[$Fid]['Name'] . " has completed anomaly study : <a href=AnomalyEdit.php?id=$Aid>" . $A['Name'] .
                "</a> has been completed - give them the reward.  $ctxt");
              FollowUp($Fid,$Facts[$Fid]['Name'] . " has completed anomaly study : <a href=AnomalyEdit.php?id=$Aid>" . $A['Name'] .
                "</a> has been completed - give them the reward. $ctxt");
              $T['ProjectId'] = 0;

              if ($A['Complete'] == 1) { // ONe Use
                $A['Complete'] = 2;
                Put_Anomaly($A);
              }

              for ($i=1; $i<=4; $i++) {
                if (!empty($A["ChainedOn$i"])) {
                  if (empty($Systems)) $Systems = Get_SystemRefs();

                  $Xid = $A["ChainedOn$i"];
                  $XA = Get_Anomaly($Xid);
                  $FS = Get_System($XA['SystemId']);
                  if (empty($FS['id'])) continue;

                  $FA = Gen_Get_Cond1('FactionAnomaly',"FactionId=$Fid AND AnomalyId=$Xid");
                  if (!$FA) {
                    $FA = ['FactionId'=>$Fid, 'AnomalyId'=>$Xid, 'State' =>2,  'Notes'=>''];
                  } else {
                    if ($FA['State'] >=2) continue;
                    $FA['State'] = 2;
                  }
                  Gen_Put('FactionAnomaly',$FA);

                  TurnLog($Fid , "Completing " . $A['Name'] . " has opened up another anomaly that could be studied: " . $XA['Name'] .
                    " in " . $Systems[$XA['SystemId']] . "\n" .  ParseText($XA['Description']) .
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
        $NT = ['GameId'=>$GAME['id'], 'Type'=> TTName('Outpost'), 'Level'=> 1, 'SystemId'=>$T['SystemId'], 'Whose'=>$T['Whose'],
        'BuildState'=>BS_COMPETE, 'TurnBuilt'=>$GAME['Turn'], 'Name'=>$T['MakeName']];
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
        if (($NT = Get_Things_Cond(0,"Type=" . TTName('Asteroid Mine') . " AND SystemId=" . $N['id'] . " AND BuildState=" . BS_COMPLETE))) {
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
        $Exist = Get_Things_Cond(0,"Type=" . TTName('Asteroid Mine') . " AND SystemId=" . $N['id'] . " AND BuildState=" . BS_COMPLETE);
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
        $NT = ['GameId'=>$GAME['id'], 'Type'=> TTName('Asteroid Mine'), 'Level'=> (($Instr == 'Make Asteroid Mine')?1:2),
          'SystemId'=>$T['SystemId'], 'Whose'=>$T['Whose'],
          'BuildState'=>BS_COMPLETE, 'TurnBuilt'=>$GAME['Turn'],
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
          $NT = ['GameId'=>$GAME['id'], 'Type'=> TTName('Minefield'), 'Level'=> (($Instr == 'Make Minefield') ?1:2),
            'SystemId'=>$T['SystemId'], 'WithinSysLoc'=> $WLoc, 'Whose'=>$T['Whose'],
            'BuildState'=>BS_COMPLETE, 'TurnBuilt'=>$GAME['Turn'], 'Name'=>$T['MakeName']];
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
        $NT = ['GameId'=>$GAME['id'], 'Type'=>  TTName('Orbital Repair Yards'), 'Level'=> 1, 'SystemId'=>$T['SystemId'], 'WithinSysLoc'=> $T['WithinSysLoc'],
        'Whose'=>$T['Whose'], 'BuildState'=>BS_COMPLETE, 'TurnBuilt'=>$GAME['Turn'], 'Name'=>$T['MakeName']];
        Put_Thing($NT);
        $N = Get_System($T['SystemId']);
        TurnLog($Who,"An Orbital Repair Yard has been made in " . $N['Ref']);
        GMLog("An Orbital Repair Yard has been made in " . $N['Ref']);
        Report_Others($T['Whose'], $T['SystemId'],2,"An Orbital Repair Yard has been made in " . $N['Ref']);
        break;

      case 'Build Space Station':
        $Fid = $T['Whose'];
        $NT = ['GameId'=>$GAME['id'], 'Type'=> TTName('Space Station'), 'Level'=> 1, 'SystemId'=>$T['SystemId'], 'WithinSysLoc'=> $T['WithinSysLoc'],
          'Whose'=>$T['Whose'], 'BuildState'=>BS_COMPLETE, 'TurnBuilt'=>$GAME['Turn'], 'MaxDistricts'=>$T['Dist1'], 'Name'=>$T['MakeName']];
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
        $SS = Get_Things_Cond($Fid,"Type=" . TTName('Space Station') . " AND SystemId=" . $T['SystemId'] . " AND BuildState=" . BS_COMPLETE);
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
        $NT = ['GameId'=>$GAME['id'], 'Type'=> TTName('Deep Space Sensor'), 'Level'=> 1, 'SystemId'=>$T['SystemId'], 'WithinSysLoc'=> $T['WithinSysLoc'],
        'Whose'=>$T['Whose'], 'BuildState'=>BS_COMPLETE, 'TurnBuilt'=>$GAME['Turn'], 'Name'=>$T['MakeName']];
        Put_Thing($NT);
        $N = Get_System($T['SystemId']);
        TurnLog($Who,"A Deep Space Sensor has been made in " . $N['Ref']);
        Report_Others($T['Whose'], $T['SystemId'],2,"A Deep Space Sensor has been made in " . $N['Ref']);
        break;

      case 'Make Advanced Deep Space Sensor':
        $NT = ['GameId'=>$GAME['id'], 'Type'=> TTName('Deep Space Sensor'), 'Level'=> 2, 'SystemId'=>$T['SystemId'], 'WithinSysLoc'=> $T['WithinSysLoc'],
        'Whose'=>$T['Whose'], 'BuildState'=>BS_COMPLETE, 'TurnBuilt'=>$GAME['Turn'], 'Name'=>$T['MakeName'], 'Level'=>2, 'NebSensors'=>2];
        Put_Thing($NT);
        $N = Get_System($T['SystemId']);
        TurnLog($Who,"An Advanced Deep Space Sensor has been made in " . $N['Ref']);
        Report_Others($T['Whose'], $T['SystemId'],2,"A Deep Space Sensor has been made in " . $N['Ref']);
        break;


      case 'Build Stargate':
        if (empty($Systems)) $Systems = Get_SystemRefs();
        $LinkLevels = Get_LinkLevels();
        $LL = $LinkLevels[$T['Dist1']];
        $L = $NewLink = ['GameId'=>$GAME['id'], 'System1Ref'=>$Systems[$T['SystemId']], 'System2Ref'=> $Systems[$T['Dist2']],
          'Level'=>$T['Dist1'], 'Name'=>$T['MakeName']];
        $Lid = Put_Link($NewLink);
        TurnLog($T['Whose'], "<span style=color:" . $LL['Colour'] . ">Link" . ($L['Name']?$L['Name']:"#$Lid").
          " </span>has been created between " . $Systems[$T['SystemId']] . " and " .
          $Systems[$T['Dist2']]);
        GMLog("A new " . $LL['Colour'] . " level " . $T['Dist1'] . " link " . ($L['Name']?$L['Name']:"#$Lid"). " </span> has been made between " .
          $Systems[$T['SystemId']] . " and " .
          $Systems[$T['Dist2']]);
        $FLK = ['FactionId' => $Fid,'LinkId'=>$Lid,'Used'=>1];
        Gen_Put('FactionLinkKnown',$FLK);
        Report_Others($T['Whose'], $T['SystemId'],2,"A new " . $LL['Colour'] . " level " . $T['Dist1'] . " link #$Lid </span> has been made in " . $N['Ref']);
        Report_Others($T['Whose'], $T['Dist2'],2,"A new " . $LL['Colour'] . " level " . $T['Dist1'] . " link #$Lid </span> has been made in " . $Systems[$T['Dist2']]);
        break;

      case 'Make Planet Mine':
        $NT = ['GameId'=>$GAME['id'], 'Type'=> TTName('Planet Mine'), 'Level'=> 1, 'SystemId'=>$T['SystemId'],
        'Whose'=>$T['Whose'], 'BuildState'=>BS_COMPLETE, 'TurnBuilt'=>$GAME['Turn'], 'Name'=>$T['MakeName']]; // TODO add withinsysloc
        Put_Thing($NT);
        $N = Get_System($T['SystemId']);
        TurnLog($T['Whose'],"A Planet Mine has been made in " . $N['Ref']);
        GMLog("A Planet Mine has been setup in " . $N['Ref'] . " by " . $Facts[$T['Whose']]['Name'] . ".  Tell Richard to set the right withinsysloc",1);
        FollowUp($T['Whose'],"A Planet Mine has been setup in " . $N['Ref'] . " by " . $Facts[$T['Whose']]['Name'] . ".  Tell Richard to set the right withinsysloc");
        break;

      case 'Construct Command Relay Station':
        $NT = ['GameId'=>$GAME['id'], 'Type'=> TTName('Command Relay Station'), 'Level'=> 1, 'SystemId'=>$T['SystemId'], 'WithinSysLoc'=> $T['WithinSysLoc'],
        'Whose'=>$T['Whose'], 'BuildState'=>BS_COMPLETE, 'TurnBuilt'=>$GAME['Turn'], 'Name'=>$T['MakeName']];
        Put_Thing($NT);
        $N = Get_System($T['SystemId']);
        TurnLog($Who,"A Command Relay Station has been made in " . $N['Ref']);
        Report_Others($T['Whose'], $T['SystemId'],2,"A Command Relay Station has been made in " . $N['Ref']);
        break;

      case 'Repair Command Node': // Not coded yet
        $NT = ['GameId'=>$GAME['id'], 'Type'=> TTName('Beta Node'), 'Level'=> 1, 'SystemId'=>$T['SystemId'], 'WithinSysLoc'=> $T['WithinSysLoc'],
        'Whose'=>$T['Whose'], 'BuildState'=>BS_COMPLETE, 'TurnBuilt'=>$GAME['Turn'], 'Name'=>$T['MakeName']];
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

        $T['BuildState'] = BS_EX;
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
        Gain_Currency($T['Whose'],$AdianNumber,$Recovery,"Dismantling Link " . ($L['Name']?$L['Name']:"#$Lid"));
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
        'BuildState'=>BS_COMPLETE, 'TurnBuilt'=>$GAME['Turn'], 'Name'=>($T['MakeName'] . " warp gate" )];
        Put_Thing($NT);
        TurnLog($Who,"A warp gate has been made in " . $N['Ref']);
        Report_Others($T['Whose'], $T['SystemId'],2,"A warp gate has been made in " . $N['Ref']);
        break;

      case 'Clear Minefield':
        $Mines = Get_Things_Cond(0,"Type=" . TTName('Minefield') . " AND SystemId=" . $N['id'] . " AND BuildState=" . BS_COMPLETE .
          " AND WithinSysLoc=" . $T['WithinSysLoc']);
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
        $Wrecks = Get_Things_Cond(0,"SystemId=" . $T['SystemId'] . " AND BuildState>" . BS_COMPLETE);
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

      case 'Space Survey':
        $Sys = $T['SystemId'];
        $Sens = $T['SensorLevel'];
        $Scan = ['FactionId'=>$Who,'Sys'=>$Sys, 'Type'=>1, 'ThingId'=>$Tid,'GameId'=>$GAMEID, 'Turn'=>$GAME['Turn'], 'Scan'=>$Sens];
        Gen_Put('ScansDue',$Scan);
        break;

      case 'Planetary Survey':
        $Sys = $T['SystemId'];
        $Sens = $T['SensorLevel'];
        $Scan = ['FactionId'=>$Who,'Sys'=>$Sys, 'Type'=>2, 'ThingId'=>$Tid,'GameId'=>$GAMEID, 'Turn'=>$GAME['Turn'], 'Scan'=>$Sens];
        Gen_Put('ScansDue',$Scan);
        break;

      case 'Collaborative Planetary Construction' :
      case 'Collaborative DSC':
        break; // All handled by the progress

      default:
        GMLog("Instruction: $Instr has completed, for " . $Facts[$Who]['Name'] . " by <a href=ThingEdit.php?id=$Tid>" . $T['Name'] .
        ".  No automation - probable bug",1);
        FollowUp($Who,"Instruction: $Instr has completed, for " . $Facts[$Who]['Name'] . " by <a href=ThingEdit.php?id=$Tid>" . $T['Name'] .
          ".  No automation - probable bug");

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
      $T['GameId'] = -$GAMEID;
      $T['Whose'] = -$Who;
      Put_Thing($T);
    }
  }

  return 1;
}

function InstructionsProgress() {
  global $ThingInstrs,$IntructProps;
  $Things = Get_Things_Cond(0,"Instruction!=0 AND CurInst>0");
  $Facts = Get_Factions();

  foreach ($Things as $T) {
    $N = Get_System($T['SystemId']);
    $Tid = $T['id'];
    switch ($ThingInstrs[abs($T['Instruction'])]) {
      case 'Colonise':
        $Prog = $T['Dist2'];
        if ($Prog == 0) {
          GMLog("Colonisation by <a href=ThingEdit.php?id=$Tid>" . $T['Name'] . "</a> Has zero progress - Tell Richard");
          FollowUp($T['Whose'],"Colonisation by <a href=ThingEdit.php?id=$Tid>" . $T['Name'] . "</a> Has zero progress - Tell Richard");
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
        $Mods = Get_ModulesType($Tid, 'Space Construction Gear');
        $ProgGain = $Mods['Level']*$Mods['Number'];
        GMLog("$ProgGain progress on " . $ThingInstrs[abs($T['Instruction'])] . " for " . $Facts[$T['Whose']]['Name'] . ":" . $T['Name']);

        $T['Progress'] = min($T['ActionsNeeded'],$T['Progress']+$ProgGain);
        Put_Thing($T);
        break;

      case 'Collaborative Planetary Construction' :
      case 'Collaborative DSC':
        break; // Now in second pass

      case 'Analyse Anomaly':
        $Aid = $T['ProjectId'];
        $Fid = $T['Whose'];
        if ($Aid) {
          $A = Get_Anomaly($Aid);
          $FA = Gen_Get_Cond1('FactionAnomaly',"FactionId=$Fid AND AnomalyId=$Aid");
          if ($FA) {
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
              $FA = Gen_Get_Cond1('FactionAnomaly',"AnomalyId=$Aid AND FactionId=$Fid");
              if (empty($FA['id'])) continue;
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
        //        $Prog = Has_Tech($T['Whose'],'Space Construction Gear');
        $Mods = Get_ModulesType($Tid, 'Space Construction Gear');
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
          } else {
            TurnLog($T['Whose'], $T['Name'] . " Was collaborating with " . $HT['Name'] . ", but " . $HT['Name'] . " is not doing any " .
              "Space Construction.");
          }
        }
        break;

      case 'Collaborative Planetary Construction' :
        $Mods = Get_ModulesType($Tid, 'Engineering Corps');
        $ProgGain = $Mods['Level']*$Mods['Number']; // For all except colonise
        $HT = Get_Thing($T['Dist1']);
        if ($HT) {
          if ($HT['Instruction'] && ($IntructProps[abs($HT['Instruction'])] & 2)) {
            if ($HT['Instruction'] == 1) { // Colonise
 /*             $Fact = $Facts[$HT['Whose']];
              $Plan = $HT['Spare1'];
              $P = Get_Planet($Plan);
              if (($P['Type'] == $Fact['Biosphere']) || ($P['Type'] == $Fact['Biosphere2']) || ($P['Type'] == $Fact['Biosphere3'])) {
                $LMod = 0;
              } else if ($P['Type'] == 4 ) {
                $LMod = -2;
              } else {
                $LMod = -1;
              }*/
              $ProgGain = max(0,$Mods['Level']*$Mods['Number']);
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
          } else {
            TurnLog($T['Whose'], $T['Name'] . " Was collaborating with " . $HT['Name'] . ", but " . $HT['Name'] .
              " is not doing any Planetary Construction.");
          }
        }
        break;




      default:
        break;
    }
  }

  return 1;
}
