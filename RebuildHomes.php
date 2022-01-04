<?php

// Scan all plantets/moon's/Thing's for districts and deep space construction

  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("PlayerLib.php");
  include_once("SystemLib.php");
  
  A_Check('GM');
  
  dostaffhead("Rebuild Project homes database");
  
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
          if ($P['ProjHome']) {
            $PHi = $P['ProjHome'];
//echo "PHI is $PHi<p>";
            foreach ($KnownHomes as &$H) {
//var_dump($H);
//if (!isset($H['id'])) { echo "<P>"; var_dump($H);exit;}
              if ($H['id'] == $PHi) {
                $H['Inuse'] = 1;
                if ($H['ThingType'] != 1 || $H['ThingId'] != $P['id']) {
                  $H['ThingType'] = 1;
                  $H['ThingId'] = $P['id'];
                  Put_ProjectHome($H);
                }
                $doneP = 1;
                break;
              }
            }
            
            if (!$doneP) {
              $H = ['ThingType'=> 1, 'ThingId'=> $P['id'], 'Inuse'=>1, 'Whose'=>$N['Control']];
              $H['id'] = Put_ProjectHome($H);
              $KnownHomes[$H['id']] = $H;
              $P['ProjHome'] = $H['id'];
              Put_Planet($P);
            }
          } else {
            $Dists = Get_DistrictsP($P['id']);
            $Homeless = 1;
            if ($Dists) {
              foreach ($KnownHomes as &$H) {
                if ($H['ThingType'] == 1 && $H['ThingId'] == $P['id']) {
                  $Homeless = 0;
                  if ($H['Whose'] == $N['Control']) {
                    $H['Inuse'] = 1;
                  } else {
                    echo "GM question - <a href=ProjHome.php?id=" . $H['id'] . " who should control project home " . $H['id'] . " is it a planet?</a><P>";
                  }
                  break;
                }
              }
              if ($Homeless) {
                $H = ['ThingType'=>1, 'ThingId'=>$P['id'], 'Whose'=>$N['Control'], 'Inuse'=>1];
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
              if ($M['ProjHome']) {
                $MHi = $M['ProjHome'];
                foreach ($KnownHomes as &$H) {
                  if ($H['id'] == $MHi) {
                    $H['Inuse'] = 1;
                    if ($H['ThingType'] != 2 || $H['ThingId'] != $M['id']) {
                      $H['ThingType'] = 1;
                      $H['ThingId'] = $M['id'];
                      Put_ProjectHome($H);
                    }
                    continue 2;
                  }
                }
                $H = ['ThingType'=> 2, 'ThingId'=> $M['id'], 'Inuse'=>1, 'Whose'=>$N['Control']];
                $H['id'] = Put_ProjectHome($H);
                $KnownHomes[] = $H;
                $M['ProjHome'] = $H['id'];
                Put_Moon($M);
                
              } else {
                $Dists = Get_DistrictsM($M['id']);
var_dump($Dists);
                $Homeless = 1;
                if ($Dists) {
                  foreach ($KnownHomes as &$H) {
                    if ($H['ThingType'] == 2 && $H['ThingId'] == $M['id']) {
                      $Homeless = 0;
                      if ($H['Whose'] == $N['Control']) {
                        $H['Inuse'] = 1;
                      } else {
                        echo "GM question - <a href=ProjHome.php?id=" . $H['id'] . " who should control project home " . $H['id'] . " is it a moon?</a><P>";
                      }
                      break;
                    }
                  }
                  if ($Homeless) {
                    $H = ['ThingType'=>2, 'ThingId'=>$M['id'], 'Whose'=>$N['Control'], 'Inuse'=>1];
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
    if ($T['HasDeepSpace'] || ($ThingTypes[$T['Type']]['Properties'] & 1) || ($ThingTypes[$T['Type']]['Properties'] & 16) ) {
      $THi = $T['ProjHome'];
      if ($THi) {
        foreach ($KnownHomes as &$H) {
          if ($H['id'] == $THi) {
            $H['Inuse'] = 1;
            if ($H['ThingType'] != 3 || $H['ThingId'] != $T['id']) {
              $H['ThingType'] = 3;
              $H['ThingId'] = $T['id'];
              Put_ProjectHome($H);
            }
            continue 2;
          }
        }
        $H = ['ThingType'=> 3, 'ThingId'=> $T['id'], 'Inuse'=>1, 'Whose'=>$T['Whose']];
        $H['id'] = Put_ProjectHome($H);
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
              echo "GM question - <a href=ProjHome.php?id=" . $H['id'] . " who should control project home " . $H['id'] . " is it a thing?</a><P>";
            }
            break;
          }
        }
        if ($Homeless) {
          $H = ['ThingType'=>3, 'ThingId'=>$T['id'], 'Whose'=>$T['Whose'], 'Inuse'=>1];
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
  dotail();
  
                  // Need to add control of planet I think
   // Things with districts and deep spae construction
   // Agents do projects as well!!!
          
?>
