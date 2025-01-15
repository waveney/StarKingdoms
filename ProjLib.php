<?php

// Things far handling projects

function Proj_Costs($lvl) {
  if ($lvl<0) return [1E6,1E6]; // Should be never but...
  if ($lvl <=16) return [[1,50],[1,50],[4,200],[9,450],[16,800],[25,1250],[36,1800],[49,2450],[64,4300],[81,4050],[100,5000],[121,5550],
                        [144,6200],[169,6950],[196,7800],[225,8750],[256,9800]][$lvl];
  $Cst = 9800;
  for($l=16;$l<=$lvl;$l++) $Cst+= $l*100+50;
  return [$lvl*$lvl,$Cst];
}

function Rush_Cost($who,$PT=0,$Hi=0) {
  if (Has_Trait($who,'Bike-Shedders')) return 100;
  if (($PT == 1) && (Has_PTraitH($Hi,'High Tectonic Activity'))) return 100;
  return 75;
}

global $Project_Status,$Project_Statuses;
$Project_Status = [0=>'Planned',1=>'Started', 2=>'Finished',3=>'Cancelled', 4=>'On Hold', 5=>'Not Started', 6=>'Interupted'];
$Project_Statuses = array_flip($Project_Status);

function  Where_Is_Home($PH,$Set=0) {
  $Home = Get_ProjectHome($PH);
// var_dump("Home",$Home);echo "<p>";
  switch ($Home['ThingType']) {
  case '1': // Planet
    if (!$Set) return [$Home['SystemId'],$Home['WithinSysLoc']];
    $P = Get_Planet($Home['ThingId']);
    $N['id'] = $P['SystemId'];
    $loc = Within_Sys_Locs($N,$P['id']);
    $Home['SystemId'] = $P['SystemId'];
    $Home['WithinSysLoc'] = $loc;
    Put_ProjectHome($Home);
    return [$P['SystemId'],$loc];

  case '2': // Moon
    if (!$Set) return [$Home['SystemId'],$Home['WithinSysLoc']];
    $M = Get_Moon($Home['ThingId']);
    $P = Get_Planet($M['PlanetId']);
    $N['id'] = $P['SystemId'];
    $loc = Within_Sys_Locs($N,- $M['id']);
    $Home['SystemId'] = $P['SystemId'];
    $Home['WithinSysLoc'] = $loc;
    Put_ProjectHome($Home);
    return [$P['SystemId'],$loc];
  case '3': // Thing
    $T = Get_Thing($Home['ThingId']);
    if ($T['BuildState'] > 0 && $T['BuildState'] < 5) return [$T['SystemId'],$T['WithinSysLoc']];
    return 0;
  }
}

function Project_Finished(&$P,$Turn) {  // CODE ON HOLD
  global $GAME,$GAMEID,$ARMY;
  $ProjTypes = Get_ProjectTypes();
  switch ($ProjTypes[$P['Type']]['Name']) {
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
// TODO how to forward project these?
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
      $CTech = Get_Faction_TechFT($P['FactionId'],$Tid);
      $Tech = Get_Tech($Tid);
      if ($Tech['Cat'] == 0) { // Core
        if ($CTech['Level'] < $P['Level']) {
          $N = Get_FutureTech($P['FactionId'], $Tid, $P['Level']);
          $N['StartTurn'] = $Turn;
          Put_FutureTech($N);
          break;
        }
      } else if ($CTech['Level'] == 0) { // Supp
        $CTech['Level'] = 1;
        $CTech['StartTurn'] = $Turn;
        Put_Faction_Tech($CTech);
        break;
      }
      break;

    case "Train $ARMY":
    case 'Train Agent':
    case 'Construct Ship':
      $T = Get_Thing($P['ThingId']);
      if ($T['BuildState'] < 2) { // Planing/building
        $T['TurnBuilt'] = $Turn;
        Put_Thing($T);
      }
      break;

    case 'Construct Warp Gate':
    case 'Decommission Ship':
    case 'Build Outpost':
    case 'Build Asteroid Mining Facility':
    case 'Build Minefield':
    case 'Build Orbital Shipyard':
    case 'Build Space Station':
    case 'Extend Space Station':
    case 'Deep Space Sensors':
    case 'Build Advanced Asteroid Mining Facility':
      // TODO these are not coded for yet

    case 'Re-equip and Reinforce':
    case 'Share Technology':
    case 'Analyse':
    case 'Decipher Alien Language':
    case 'Rebuild and Repair':
    case 'Refit and Repair':
    default:
      break;  // No action


    }
}

function Abandon_Project(&$P) {
  global $Project_Status, $Project_Statuses;

  $P['Status'] = $Project_Statuses['Cancelled'];
  Put_Project($P);
}

?>
