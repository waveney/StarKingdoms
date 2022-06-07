<?php

$HomeTypes = ['','Planet','Moon','Thing'];
global $HomeTypes;

include_once("ProjLib.php");

function Recalc_Project_Homes($Logf=0) {
  global $GAME;
  echo "<h1>Rebuild Project homes database</h1>";
  $Facts = Get_Factions();

  $KnownHomes = [];
    
  foreach($Facts as $F) {
    $Homes = Get_ProjectHomes($F['id']);
    if ($Homes) $KnownHomes = array_merge($KnownHomes,$Homes);
  }

//var_dump($KnownHomes); exit;  
  $Systems = Get_Systems();
  foreach ($Systems as &$N) {
    if ($N['Control']) {
      echo "Checking " . $N['Ref'] . "<br>\n";
      
      $Planets = Get_Planets($N['id']);
      if ($Planets) {
        foreach ($Planets as &$P) {
          $doneP = 0;

          $Cont = $P['Control'];
          if ($Cont == 0) $Cont = $N['Control'];

          if ($P['ProjHome']) {
            $PHi = $P['ProjHome'];
//echo "PHI is $PHi<p>";
            foreach ($KnownHomes as &$H) {
//var_dump($H);
//if (!isset($H['id'])) { echo "<P>"; var_dump($H);exit;}
              if ($H['id'] == $PHi) {
                $H['Inuse'] = 1;
                if ($H['ThingType'] != 1 || $H['ThingId'] != $P['id'] || $H['Whose'] != $Cont) {
                  $H['ThingType'] = 1;
                  $H['ThingId'] = $P['id'];
                  $loc = Within_Sys_Locs($N,$P['id']);
                  $H['SystemId'] = $P['SystemId'];
                  $H['WithinSysLoc'] = $loc;
                  $H['Whose'] = $Cont;
                  Put_ProjectHome($H);
                } else {
                  Where_Is_Home($PHi,1);
                }
                $doneP = 1;
                break;
              }
            }
            
            if (!$doneP) {
              $H = ['ThingType'=> 1, 'ThingId'=> $P['id'], 'Inuse'=>1, 'Whose'=>$Cont];
              $loc = Within_Sys_Locs($N,$P['id']);
              $H['SystemId'] = $P['SystemId'];
              $H['WithinSysLoc'] = $loc;
              $H['id'] = Put_ProjectHome($H);
              $KnownHomes[$H['id']] = $H;
              $P['ProjHome'] = $H['id'];
              Put_Planet($P);
            }
          } else {
            $Dists = Get_DistrictsP($P['id']);
            $Homeless = 1;
            if ($Dists || $P['Control']) {
              foreach ($KnownHomes as &$H) {
                if ($H['ThingType'] == 1 && $H['ThingId'] == $P['id']) {
                  $Homeless = 0;
                  if ($H['Whose'] == $Cont) {
                    $H['Inuse'] = 1;
                  } else {
                    echo "GM question - <a href=ProjHomes.php?id=" . $H['id'] . " who should control project home " . $H['id'] . " is it a planet?</a><P>";
                  }
                  break;
                }
              }
              if ($Homeless) {
                $H = ['ThingType'=>1, 'ThingId'=>$P['id'], 'Whose'=>$Cont, 'Inuse'=>1];
                $loc = Within_Sys_Locs($N,$P['id']);
                $H['SystemId'] = $P['SystemId'];
                $H['WithinSysLoc'] = $loc;
                $H['id'] = Put_ProjectHome($H);
                $KnownHomes[] = $H;
                $P['ProjHome'] = $H['id'];
                Put_Planet($P);
              }
            }
          }
//echo "Getting Moons of " . $P['Name'] . $P['id'] . "<p>";
          $Mns = Get_Moons($P['id']);
//var_dump($Mns);
          if ($Mns) {
//echo "Checking moons of " . $P['Name'] . "<p>";
            foreach ($Mns as &$M) {
//echo "Q";
              $Cont = $M['Control'];
              if ($Cont == 0) $Cont = $P['Control'];
              if ($Cont == 0) $Cont = $N['Control'];

              if ($M['ProjHome']) {
                $MHi = $M['ProjHome'];
                foreach ($KnownHomes as &$H) {
                  if ($H['id'] == $MHi) {
                    $H['Inuse'] = 1;
                    if ($H['ThingType'] != 2 || $H['ThingId'] != $M['id'] || $H['Whose'] != $Cont) {
                      $H['ThingType'] = 2;
                      $H['ThingId'] = $M['id'];
                      $loc = Within_Sys_Locs($N,- $M['id']);
                      $H['SystemId'] = $P['SystemId'];
                      $H['WithinSysLoc'] = $loc;
                      $H['Whose'] = $Cont;
                      Put_ProjectHome($H);
                    } else {
                      Where_Is_Home($H['id'],1);
                    }
                    continue 2;
                  }
                }
                $H = ['ThingType'=> 2, 'ThingId'=> $M['id'], 'Inuse'=>1, 'Whose'=>$Cont];
                $H['id'] = Put_ProjectHome($H);
                $KnownHomes[] = $H;
                $M['ProjHome'] = $H['id'];
                Put_Moon($M);
                
              } else {
                $Dists = Get_DistrictsM($M['id']);
//var_dump($Dists);
                $Homeless = 1;
                if ($Dists || $M['Control']) {
                  foreach ($KnownHomes as &$H) {
                    if ($H['ThingType'] == 2 && $H['ThingId'] == $M['id']) {
                      $Homeless = 0;
                      if ($H['Whose'] == $Cont) {
                        $H['Inuse'] = 1;
                      } else {
                        echo "GM question - <a href=ProjHomes.php?id=" . $H['id'] . " who should control project home " . $H['id'] . " is it a moon?</a><P>";
                      }
                      break;
                    }
                  }
                  if ($Homeless) {
                    $H = ['ThingType'=>2, 'ThingId'=>$M['id'], 'Whose'=>$Cont, 'Inuse'=>1];
                    $loc = Within_Sys_Locs($N,- $M['id']);
                    $H['SystemId'] = $P['SystemId'];
                    $H['WithinSysLoc'] = $loc;
                    $H['id'] = Put_ProjectHome($H);
                    $KnownHomes[] = $H;
                    $M['ProjHome'] = $H['id'];
                    Put_Moon($M);
                  }
                }
              }
            }
          }
        }
      }
    }
  }
  
  $ThingTypes = Get_ThingTypes();
  
  $Things = Get_AllThings();
  foreach ($Things as &$T) {
    if ($T['Type'] == 0) {
      db_delete('Things',$T['id']);
      echo "Deleted entry Null Thing " . $T['id'] . "<br>";
      continue;
    }
 
    if ($T['BuildState'] < 2 || $T['BuildState'] > 3 ) continue;
//    if ($T['HasDeepSpace'] || ($ThingTypes[$T['Type']]['Properties'] & THING_HAS_DISTRICTS) || ($ThingTypes[$T['Type']]['Properties'] & THING_HAS_GADGETS ) ) {
    if (($ThingTypes[$T['Type']]['Properties'] & (THING_HAS_DISTRICTS | THING_CAN_DO_PROJECTS)) ) {
      $THi = $T['ProjHome'];
      if ($THi) {
        foreach ($KnownHomes as &$H) {
          if ($H['id'] == $THi) {
            $H['Inuse'] = 1;
            if ($H['ThingType'] != 3 || $H['ThingId'] != $T['id']) {
              $H['ThingType'] = 3;
              $H['ThingId'] = $T['id'];
              $H['SystemId'] = $T['SystemId'];
              $H['WithinSysLoc'] = $T['WithinSysLoc'];
              Put_ProjectHome($H);
            }
            continue 2;
          }
        }
        $H = ['ThingType'=> 3, 'ThingId'=> $T['id'], 'Inuse'=>1, 'Whose'=>$T['Whose']];
        Put_ProjectHome($H);
        $KnownHomes[$H['id']] = $H;
        $T['ProjHome'] = $H['id'];
        Put_Thing($T);
      } else {
        $Homeless = 1;
        foreach ($KnownHomes as &$H) {
          if ($H['ThingType'] == 3 && $H['ThingId'] == $P['id']) {
            $Homeless = 0;
            if ($H['Whose'] == $N['Control']) {
              $H['Inuse'] = 1;
            } else {
              echo "GM question - <a href=ProjHomes.php?id=" . $H['id'] . " who should control project home " . $H['id'] . " is it a thing?</a><P>";
            }
            break;
          }
        }
        if ($Homeless) {
          $H = ['ThingType'=>3, 'ThingId'=>$T['id'], 'Whose'=>$T['Whose'], 'Inuse'=>1, 'SystemId' => $T['SystemId'], 'WithinSysLoc' => $T['WithinSysLoc']];
          $H['id'] = Put_ProjectHome($H);
          $KnownHomes[] = $H;
          $T['ProjHome'] = $H['id'];
          Put_Thing($T);
          }
        }
      }
    }  

 
  // Remove unused entries
  foreach ($KnownHomes as &$H) {
    if (isset($H['Inuse']) && $H['Inuse']) continue;
    db_delete('ProjectHomes',$H['id']);
  }
  
  echo "Project Homes Rebuilt<p>";
}

function Show_Home($Hid) {
  global $HomeTypes;
  $H = Get_ProjectHome($Hid);
  echo "<form method=post action=ProjHomes.php>";
  Register_Autoupdate("ProjectHome",$Hid);
  fm_hidden('id',$Hid);
  echo "<table border>\n";
  echo "<tr><td>Id: $Hid";
  echo "<tr><td>Home type:<td>" . fm_select($HomeTypes,$H,'ThingType');
  echo "<tr>" . fm_number('Id of thing', $H,'ThingId');
  echo "<tr>" . fm_number('Economy',$H,'Economy');
  echo "<tr>" . fm_number('Devastation',$H,'Devastation');
  echo "<tr>" . fm_number('Economy % Modifier',$H,'EconomyFactor') . "<td>For example during an invasion\n";
  if ($H['ThingType'] != 3) {
    $Systems = Get_Systems();
    $SysNames = [0=>''];
    foreach($Systems as $N) {
      $SysNames[$N['id']] = $N['Ref'];
      if ($N['id'] == $H['SystemId']) $SysLocs = Within_Sys_Locs($N);
    }
    echo "<tr><td>System:<td>" . fm_select($SysNames,$H,'SystemId') . "<td>Refresh after changing to get within sys locs right\n";
    echo "<tr><td>Where:<td>" . fm_select($SysLocs, $H,'WithinSysLoc');
  }
  if (Access('God')) echo "<tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea>";  
  echo "</table><p>";
}

function Recalc_Worlds() {
  include_once("ThingLib.php");
  // Get all districts
  // Work out list of things that have them
  // Go through each world 
    // does it exist?  If so check minerals level and check its home numbber
    // If not create it
  // Any worldd without districts is removed
  $Dists = Get_DistrictsAll();
  $Worlds = Get_Worlds();
  $Facts = Get_Factions();
  $Homes = Get_ProjectHomes();
  $TTypes = Get_ThingTypes();
  
  foreach ($Homes as $H) {
    foreach ($Worlds as $Wi=>$W) {
      if ($W['ThingType'] == $H['ThingType'] && $W['ThingId'] == $H['ThingId']) {
      switch ($H['ThingType']) {
        case 1: // Planet
          $P = Get_Planet($H['ThingId']);
          $Sys = Get_System($P['SystemId']);
          $Fid = $Sys['Control'];
          $Minerals = $P['Minerals'];
          $ThisBio = $P['Type'];
          break;
        case 2: // Moon
          $M = Get_Moon($H['ThingId']);
          $P = Get_Planet($M['PlanetId']);
          $Sys = Get_System($P['SystemId']);
          $Fid = $Sys['Control'];
          $Minerals = $M['Minerals'];
          $ThisBio = $M['Type'];
          break;
        case 3: // Thing 
          $T = Get_Thing($H['ThingId']);
          if (($TTypes[$T['Type']]['Properties'] & THING_HAS_DISTRICTS) == 0) continue 2;
          $Fid = $T['Whose'];
          $Minerals = (($TTypes[$T['Type']]['Properties'] & THING_HAS_MINERALS)?10: 0);
          $ThisBio = -1;
          break;
        }
        $Bio = $Facts[$Fid]['Biosphere'];      
    // Find Project Home
    
        $W['Home'] = $H['id'];
        $W['Minerals'] = $Minerals;
 
        Put_World($W);

        $Worlds[$Wi]['Done'] = 1;
        $Recalc = Recalc_Economic_Rating($H,$W,$Fid);
        if ($H['Economy'] != $Recalc) {
          $H['Economy'] = $Recalc;
          Put_ProjectHome($H);
        }

        continue 2;
      }
    }
    // New World
    switch ($H['ThingType']) {
      case 1: // Planet
        $P = Get_Planet($H['ThingId']);
        $Sys = Get_System($P['SystemId']);
        $Fid = $Sys['Control'];
        $Minerals = $P['Minerals'];
        $ThisBio = $P['Type'];
        break;
      case 2: // Moon
        $M = Get_Moon($H['ThingId']);
        $P = Get_Planet($M['PlanetId']);
        $Sys = Get_System($P['SystemId']);
        $Fid = $Sys['Control'];
        $Minerals = $M['Minerals'];
        $ThisBio = $M['Type'];
        break;
      case 3: // Thing 
        $T = Get_Thing($H['ThingId']);
        if (empty($T)) {
          echo "Home " . $H['id'] . " is faulty!";
          break 2;
        }
        if (($TTypes[$T['Type']]['Properties'] & THING_HAS_DISTRICTS) == 0) continue 2;
        $Fid = $T['Whose'];
        $Minerals = (($TTypes[$T['Type']]['Properties'] & THING_HAS_MINERALS)?10: 0);
        $ThisBio = -1;
        break;
    }
    $Bio = $Facts[$Fid]['Biosphere'];      
    // Find Project Home
    
    $W = ['FactionId' => $Fid, 'Home' => $H['id'], 'Minerals' => $Minerals, 'RelOrder' => ($Bio == $ThisBio ? 100:80), 
          'ThingType' => $H['ThingType'], 'ThingId'=>$H['ThingId'], 'Done'=>1 ];
    Put_World($W);
    
    $Recalc = Recalc_Economic_Rating($H,$W,$Fid);
    if ($H['Economy'] != $Recalc) {
      $H['Economy'] = $Recalc;
      Put_ProjectHome($H);
    }
  }
  
  foreach ($Worlds as $W) {
    if (empty($W['Done'])) {
      echo "World " . $W['id'] . " is not used - Delete?<br>\n";
    }
  }
  
  echo "Worlds recalculted<p>\n";
}

function Recalc_Economic_Rating(&$H,&$W,$Fid,$Turn=0) {
  $Dists = Get_DistrictsH($H['id'],$Turn);
  $DTs = Get_DistrictTypes();

  $NumPrime = $Mines = 0;
  $NumCom = 0;
  if (!$Dists) return 0;
  foreach ($Dists as $D) {
    if ($D['Type'] == 0) {
      echo "District " . $D['id'] . " is illegal...<br>";
      continue;
    }

    if ($D['Type'] == 1) $NumCom = $D['Number'];
    if ($DTs[$D['Type']]['Props'] & 1) $NumPrime += $D['Number'];
    if ($DTs[$D['Type']]['Props'] & 4) $Mines += $D['Number'];
  }
  return (Has_Trait($Fid,'No customers')?($NumPrime - $NumCom):$NumPrime)*$NumCom*2  + min($W['Minerals'],$NumPrime) + min($W['Minerals'],$Mines*2);
}

function Project_Home_Thing(&$H) {
  if (empty($H['ThingType']) || empty($H['ThingId'])) return 0;
  switch ($H['ThingType']) {
    case 1: // Planet
      return Get_Planet($H['ThingId']);
          
    case 2: // Moon
      return Get_Moon($H['ThingId']);
    
    case 3: // Thing
      return Get_Thing($H['ThingId']);
    
  }
}


?>
