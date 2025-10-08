<?php

$HomeTypes = ['','Planet','Moon','Thing'];
global $HomeTypes;

include_once("ProjLib.php");

function Recalc_Project_Homes($Logf=0, $Silent=0) {
  global $GAME;
  echo "<h1>Rebuild Project homes database</h1>";
  $Facts = Get_Factions();

  $KnownHomes = [];

 // var_dump($Facts);
  foreach($Facts as $F) {
    $Homes = Get_ProjectHomes($F['id']);
    if ($Homes) $KnownHomes = array_merge($KnownHomes,$Homes);
  }

  $AllDists = Get_DistrictsAll();
////var_dump($AllDists); exit;
  $PWithDists = $MWithDists = $TWithDists = [];
  foreach($AllDists as $D) {
    switch ($D['HostType']) {
    case 0:
    case 1:
      $PWithDists[$D['HostId']] = 1;
      break;
    case 2:
      $MWithDists[$D['HostId']] = 1;
      break;
    case 3:
      $TWithDists[$D['HostId']] = 1;
      break;
    default:
    }
  }

//var_dump($KnownHomes); exit;
//var_dump($PWithDists);exit;
//var_dump($MWithDists);exit;
  $Systems = Get_Systems();
  foreach ($Systems as &$N) {
    if ($N['Control'] || $N['HistoricalControl']) {
      if (!$Silent) echo "Checking " . $N['Ref'] . "<br>\n";

      $Planets = Get_Planets($N['id']);
      if ($Planets) {
        foreach ($Planets as $Pid=>&$P) {
          $doneP = 0;

          $Cont = $P['Control'];
          if ($Cont == 0) $Cont = $N['Control'];

          if (isset($PWithDists[$P['id']])) {
            if ($P['ProjHome']) {
              $PHi = $P['ProjHome'];

              foreach ($KnownHomes as &$H) {

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
              // Has home defined but not found
                if (!$Silent) echo "Would Delete $PHi<p>";
//              db_delete('ProjectHomes',$PHi);
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
         }
/*
          } else {
            if (isset($PWithDists[$P['id']])) {
              $Dists = $PWithDists[$P['id']];
            } else {
              $Dists = 0;
            }
            $Homeless = 1;
            if ($Dists || $P['Control']) {
              foreach ($KnownHomes as &$H) {
                if (($H['ThingType'] == 1) && ($H['ThingId'] == $P['id'])) {
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
*/

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

              if (isset($MWithDists[$M['id']])) {
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
                }
              }
              $H = ['ThingType'=> 2, 'ThingId'=> $M['id'], 'Inuse'=>1, 'Whose'=>$Cont];
              $H['id'] = Put_ProjectHome($H);
              $KnownHomes[] = $H;
              $M['ProjHome'] = $H['id'];
              Put_Moon($M);
              continue;
/*
                else { // No dists
                echo "Would delete Moon home $MHi<p>";// WRONG
//                  db_delete('ProjectHomes',$MHi);
                }

              } else {
                if (isset($MWithDists[$M['id']])) {
                  $Dists = $MWithDists[$M['id']];
                } else {
                  $Dists = 0;
                }

                $Dists = $MWithDists[$M['id']];
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
*/
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
      if (!$Silent) db_delete('Things',$T['id']);
      if (!$Silent) echo "Deleted entry Null Thing " . $T['id'] . "<br>";
      continue;
    }

    if ($T['BuildState'] < BS_SERVICE || $T['BuildState'] > BS_COMPLETE ) continue;

    if ((($ThingTypes[$T['Type']]['Properties']??0) & (THING_HAS_DISTRICTS + THING_CAN_DO_PROJECTS)) != 0 ) {
      $THi = $T['ProjHome'];
      if ($THi) {
        foreach ($KnownHomes as &$H) {
          if ($H['id'] == $THi) {
            $H['Inuse'] = 1;
            if ($H['ThingType'] != 3 || $H['ThingId'] != $T['id'] || $T['Whose'] != $H['Whose']) {
              $H['ThingType'] = 3;
              $H['ThingId'] = $T['id'];
              $H['SystemId'] = $T['SystemId'];
              $H['WithinSysLoc'] = $T['WithinSysLoc'];
              $H['Whose'] = $T['Whose'];
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
              if (!$Silent) echo "GM question - <a href=ProjHomes.php?id=" . $H['id'] . " who should control project home " . $H['id'] . " is it a thing?</a><P>";
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
    // Check for branches
    // get world, check branches in world
    $World = Gen_Get_Cond1('Worlds',"ThingType=" . $H['ThingType'] . " AND ThingId=" . $H['ThingType']);
    if ($World) {
      $Branches = Gen_Get_Cond('Branches',"World=" . $World['id']);
      if ($Branches) continue;
    }
    if (!$Silent) echo "Would delete Home " . $H['id'] . "<br>";
    db_delete('ProjectHomes',$H['id']);
  }

  if (!$Silent) echo "Project Homes Rebuilt<p>";
}

function Show_Home($Hid) {
  global $HomeTypes;
  $H = Get_ProjectHome($Hid);
  echo "Properties: 1=No Projects<p>";
  echo "<form method=post action=ProjHomes.php>";
  Register_Autoupdate("ProjectHomes",$Hid);
  fm_hidden('id',$Hid);
  echo "<table border>\n";
  echo "<tr><td>Id: $Hid";
  echo "<tr><td>Home type:<td>" . fm_select($HomeTypes,$H,'ThingType');
  echo "<tr>" . fm_number('Id of thing', $H,'ThingId');
  echo "<tr>" . fm_number('Economy',$H,'Economy');
  echo "<tr>" . fm_number('Devastation',$H,'Devastation');
  echo "<tr>" . fm_number('Economy Modifier',$H,'EconomyMod') . "<td>Amount to be added/subtracted before other effects\n";
  echo "<tr>" . fm_number('Economy % Factor',$H,'EconomyFactor') . "<td>For example during an invasion - normally 100(%)\n";
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
  echo "<tr>" . fm_number('Properties ',$H,'Props');
  if (Access('God')) echo "</tbody><tfoot><tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea>";
  echo "</table><p>";
}

function Recalc_Worlds($Silent=0) {
  include_once("ThingLib.php");
  // Get all districts
  // Work out list of things that have them
  // Go through each world
    // does it exist?  If so check minerals level and check its home numbber
    // If not create it
  // Any worldd without districts is removed - TODO

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
          $Fid = ($P['Control'] ? $P['Control'] : $Sys['Control']);
          $Minerals = $P['Minerals'];
          $ThisBio = $P['Type'];
          break;
        case 2: // Moon
          $M = Get_Moon($H['ThingId']);
          $P = Get_Planet($M['PlanetId']);
          $Sys = Get_System($P['SystemId']);
          $Fid = ($M['Control'] ? $M['Control'] : ($P['Control'] ? $P['Control'] : $Sys['Control']));
          $Minerals = $M['Minerals'];
          $ThisBio = $M['Type'];
          break;
        case 3: // Thing
          $T = Get_Thing($H['ThingId']);
          if (($TTypes[$T['Type']]['Properties'] & THING_HAS_DISTRICTS) == 0) continue 2;
          $Fid = $T['Whose'];
          $Minerals = (($TTypes[$T['Type']]['Properties'] & THING_HAS_MINERALS)?100: 0);
          $ThisBio = -1;
          break;
        }
        $Bio = ($Facts[$Fid]['Biosphere']??0);
    // Find Project Home

        $W['Home'] = $H['id'];
        $W['Minerals'] = $Minerals;
        $W['FactionId'] = $Fid;

        Put_World($W);

        $Worlds[$Wi]['Done'] = 1;
        [$Recalc,$Rtxt] = Recalc_Economic_Rating($H,$W,$Fid);
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
        $Fid = ($P['Control'] ? $P['Control'] : $Sys['Control']);
        $Minerals = $P['Minerals'];
        $ThisBio = $P['Type'];
        break;
      case 2: // Moon
        $M = Get_Moon($H['ThingId']);
        $P = Get_Planet($M['PlanetId']);
        $Sys = Get_System($P['SystemId']);
        $Fid = ($M['Control'] ? $M['Control'] : ($P['Control'] ? $P['Control'] : $Sys['Control']));
        $Minerals = $M['Minerals'];
        $ThisBio = $M['Type'];
        break;
      case 3: // Thing
        $T = Get_Thing($H['ThingId']);
        if (empty($T)) {
          if (!$Silent) echo "Home " . $H['id'] . " is faulty!";
          break 2;
        }
        if (($TTypes[$T['Type']]['Properties'] & THING_HAS_DISTRICTS) == 0) continue 2;
        $Fid = $T['Whose'];
        $Minerals = (($TTypes[$T['Type']]['Properties'] & THING_HAS_MINERALS)?100: 0);
        $ThisBio = -1;
        break;
    }
    $Bio = $Facts[$Fid]['Biosphere'];
    // Find Project Home

    $W = ['FactionId' => $Fid, 'Home' => $H['id'], 'Minerals' => $Minerals, 'RelOrder' => ($Bio == $ThisBio ? 100:80),
          'ThingType' => $H['ThingType'], 'ThingId'=>$H['ThingId'], 'Done'=>1 ];
    Put_World($W);
    $Worlds[$W['id']] = $W;

    [$Recalc,$Rtxt] = Recalc_Economic_Rating($H,$W,$Fid);
    if ($H['Economy'] != $Recalc) {
      $H['Economy'] = $Recalc;
      Put_ProjectHome($H);
    }
  }

  foreach ($Worlds as $W) {
    if (empty($W['Done'])) {
      if (!$Silent) echo "World " . $W['id'] . " is not used - Delete?<br>\n";
    }
  }

  foreach ($Facts as $F) {
    if (isset($F['HomeWorld']) && $F['HomeWorld']>0 && isset($Worlds[$F['HomeWorld']])) {
      if (!$Silent) echo "Faction " . $F['Name'] . " has a homeworld<br>\n";
      if (empty($Worlds[$F['HomeWorld']]['Done'])) if (!$Silent) echo "Faction " . $F['Name'] . " Homeworld Not USED!<br>\n";
    } else if ($F['HomeWorld']==0)  {
      if (!$Silent) echo "Faction " . $F['Name'] . " does <b>NOT</b> have a homeworld<br>\n";
      $HW = 0;
      $Ord = 0;
      foreach ($Worlds as $W) {
        if ($W['FactionId'] == $F['id'] && $W['RelOrder'] > $Ord) {
          $HW = $W['id'];
          $Ord = $W['RelOrder'];
        }
      }
      if ($HW) {
        $F['HomeWorld'] = $HW;
        Put_Faction($F);
        if (!$Silent) echo "Now setup<p>";
      } else {
       if (!$Silent)  echo "No Worlds for faction<p>";
      }
    } else {
      if (!$Silent && ($F['HomeWorld']==0)) echo "Faction " . $F['Name'] . " does not have a homeworld<br>\n";

    }
  }


  if (!$Silent) echo "Worlds recalculted<p>\n";

  // Checking All Branches
  $Orgs = Gen_Get_All_GameId('Organisations');

  $Branches = Gen_Get_All_GameId('Branches');

  foreach ($Branches as $B) {
    $Up = 0;
    $OrgId = $B['Organisation'];
    if ($Orgs[$OrgId]['OrgType'] != $B['OrgType']) {
      $B['OrgType'] = $Orgs[$OrgId]['OrgType'];
      $Up = 1;
    }

    if ($Orgs[$OrgId]['OrgType2'] != $B['OrgType2']) {
      $B['OrgType2'] = $Orgs[$OrgId]['OrgType2'];
      $Up = 1;
    }

    if ($Orgs[$OrgId]['Whose'] != $B['Whose']) {
      $B['Whose'] = $Orgs[$OrgId]['Whose'];
      $Up = 1;
    }

    if ($Up) Gen_Put('Branches',$B);
  }
}

function Recalc_Economic_Rating(&$H,&$W,$Fid,$Turn=0) {
  if (!isset($H['id'])) return 0;
  $Dists = Get_DistrictsH($H['id'],$Turn);
  $DTs = Get_DistrictTypes();

  $ERate = 0;
  $EText = '';
  $NumPrime = $Mines = 0;
  $NumCom = 0; $NumInd = 0;
  if (!$Dists) return 0;
  foreach ($Dists as $D) {
    if ($D['Type'] == 0) {
      echo "District " . $D['id'] . " is illegal...<br>";
      continue;
    }

    if ($D['Type'] == 1) $NumCom = $D['Number'];
    if (isset($DTs[$D['Type']])) {
      if ($DTs[$D['Type']]['Props'] & 1) $NumPrime += $D['Number'];
      if ($DTs[$D['Type']]['Props'] & 4) $Mines += $D['Number'];
      if ($DTs[$D['Type']]['Props'] & 32) $NumInd += $D['Number'];
    } else {
      GMLog4Later("District " . $D['id'] . " is invalid - tell Richard");
    }
  }
//var_dump($NumPrime);
  $MinFact = (Has_Tech($Fid,'Improved Mining')?1.5:1);
  if (Feature('Industrial')) {
    $PrimeMult = Feature('PrimeMult',4);
    if (Has_PTraitW($W['id'],'Friendly Wildlife')) $PrimeMult = 6;
    $ERate = $NumPrime*$PrimeMult;
    $EText = $NumPrime*$PrimeMult . " from districts<br>\n";
    $Min = $W['Minerals']*$NumInd;
    if ($Min) {
      $ERate += $Min;
      $EText .= "Plus $Min from minerals<br>\n";
    }
    if (Has_PTraitW($W['id'],'Rare Mineral Deposits') && Has_Tech($Fid,'Advanced Mineral Extraction')) {
      $ERate += $MinFact*3*$NumInd;
      $EText .= "Plus " . $MinFact*3*$NumInd . " from Rare Mineral Deposits<br>\n";
    }
    if (Has_PTraitW($W['id'],'Automated Farming Robots')) {
      $ERate += 4;
      $EText .= "Plus 4 from Automated Farming Robots<br>\n";
    }

    if ($H['EconomyMod']) {
      $ERate += $H['EconomyMod'];
      $EText .= ($H['EconomyMod'] > 0?"Plus " . $H['EconomyMod'] : "Less " . -$H['EconomyMod']) . " from other reasons<br>\n";
    }
 //   var_dump($NumPrime,$NumInd,$MinFact, $W);
    return [$ERate,$EText];
  } else {
    return (Has_Trait($Fid,'No customers')?($NumPrime - $NumCom):$NumPrime)*$NumCom*2
         + min($W['Minerals'] * $MinFact,$NumPrime) + min($W['Minerals'] * $MinFact,$Mines*2* $MinFact);
  }
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

function Control_Propogate($Sid,$Who) {
  // System Control & Name
  $N = Get_System($Sid);
  $N['Control'] = $Who;

  $FS = Get_FactionSystemFS($Who,$Sid);
  if (!empty($FS['Name'])) {
    $N['Name'] = $FS['Name'];
    $N['ShortName'] = $FS['ShortName'];
  }
  Put_System($N);

  $Plans = Get_Planets($Sid);
  foreach ($Plans as $Pid=>$P) {
    $PS = Get_FactionPlanetFS($Who,$Pid);
    if (!empty($PS['Name'])) {
      $P['Name'] = $PS['Name'];
      $P['ShortName'] = $PS['ShortName'];
      Put_Planet($P);
    }
    $Moons = Get_Moons($Pid);
    foreach ($Moons as $Mid=>$M) {
      $MS = Get_FactionMoonFS($Who,$Mid);
      if (!empty($MS['Name'])) {
        $M['Name'] = $MS['Name'];
        $M['ShortName'] = $MS['ShortName'];
        Put_Moon($M);
      }
    }
  }
}

function ConstructLoc($Hid,$Posn=0) {
  $H = Get_ProjectHome($Hid);
  switch ($H['ThingType']) {
    case 1: // Planet
      $Planet = Get_Planet($H['ThingId']);
      $Planets = Get_Planets($Planet['SystemId']);
      $Pi = 1;
      foreach ($Planets as $P) {
        if ($P['id'] == $Planet['id']) return $Pi + 100 + ($Posn?100:0);
        $Pi++;
      }
      return 0;

    case 2: // Moon
      $Moon = Get_Moon($H['ThingId']);
      $Plan = Get_Planet($Moon['PlanetId']);
      $Planets = Get_Planets($Plan['SystemId']);
      $mi = 1;
      foreach ($Planets as $P) {
        $Moons = Get_Moons($P['id']);
        foreach($Moons as $M) {
          if ($M['id'] == $Moon['id']) return 300+$mi + ($Posn?100:0);
          $mi++;
        }
      }
      return 0;

    case 3: // Thing
      $T = Get_Thing($H['ThingId']);
      return $T['WithinSysLoc']; // Ignores Posn
  }

}

function WorldFlags(&$W) {
  $Stat = [];
  if ($W['Conflict']) $Stat[]= 'Conflict';
  if ($W['Blockade']) $Stat[]= 'Blockade:' . $W['Blockade'];
  if ($W['Revolt'])   $Stat[]= 'Revolt';
  if ($Stat) {
    echo "<td class=Red>" . implode(', ',$Stat);
  } else {
    echo "<td>";
  }
}

function World_Name_Short(&$W) {
  switch ($W['ThingType']) {
    case 1: //Planet
      $P = Get_Planet($W['ThingId']);
      return $P['Name'];

    case 2: /// Moon
      $M = Get_Moon($W['ThingId']);
      return $M['Name'];

    case 3: // Thing
      $T = Get_Thing($W['ThingId']);
      return $T['Name'];
  }
}

function World_Name_Long($Wid,$Fid=0) {
  $World = Get_World($Wid);
  switch ($World['ThingType']) {
    case 1: // Planet
      $P = Get_Planet($World['ThingId']);
      $Sys = $P['SystemId'];
      $FP = Get_FactionPlanetFS($Fid,$P['id']);
      $N = Get_System($Sys);
      $Name = (!empty($FP['Name'])?$FP['Name']:$P['Name']) . " in " . System_Name($N,$Fid);
      return $Name;

    case 2 : // Moon
      $M = Get_Moon($World['ThingId']);
      $FM = Get_FactionMoonFS($Fid,$M['id']);
      $P = Get_Planet($M['PlanetId']);
      $Sys = $P['SystemId'];
      $FP = Get_FactionPlanetFS($Fid,$P['id']);
      $N = Get_System($Sys);
      $Name = ($FM['Name']?$FM['Name']:$M['Name']) . " a moon of " . ($FP['Name']?$FP['Name']:$P['Name']) . " in " . System_Name($N,$Fid);
      return $Name;

    case 3: // Thing
      $T = Get_Thing($World['ThingId']);
      $Sys = $T['SystemId'];
      $N = Get_System($Sys);
      $Name = $T['Name'] . " currently in " . System_Name($N,$Fid);
      return $Name;
  }
}

function Set_System_List() {// sets list of Planets/Moons for each system that are colonised
  $Systems = Get_Systems();
  foreach ($Systems as $N) {
    $Sid = $N['id'];
    $WL = [];
    $Planets = Get_Planets($Sid);
    foreach ($Planets as $Pid=>$P) {
      if ($P['Control']) {
        if ($Pid == 0) var_dump($P);
        $WL[] = $Pid;
      }
      $Moons = Get_Moons($Pid);
      if ($Moons) foreach($Moons as $M) if ($M['Control']) {
        $WL[] = -$M['id'];
      }
    }

    if ($WL) {
//var_dump($N['Ref'],$WL);
       $N['WorldList'] = implode(',',$WL);
      Put_System($N);
    }
  }
}

function ShowWorld(&$W,$Mode=0,$NeedDelta=0) { // Mode 0 = View, 1=Owner, 2 = GM
  include_once("SystemLib.php");
  global $GAMEID;
  $Wid = $W['id'];

  $TTypes = Get_ThingTypes();
  $PlanetTypes = Get_PlanetTypes();
  $Fid = $W['FactionId'];
  $DTs = Get_DistrictTypes();
  $DistTypes = 0;
  $SysId = 0;
  $Facts = Get_Factions();
  $SocPs = [];

  $H = Get_ProjectHome($W['Home']);
  if (isset($H['id'])) {
    if ($H['ThingType'] == 0 || $H['ThingId'] == 0 ) {
      echo "<h2 class=Err>There is a fault with Project Home " . $H['id'] . " Tell Richard</h2>";
    }

    $Dists = Get_DistrictsH($H['id']);
    $SocPs = Get_SocialPs($W['id']);

    // var_dump($Dists);
    switch ($W['ThingType']) {
      case 1: //Planet
        $WH = $P = Get_Planet($W['ThingId']);
        $type = $PlanetTypes[$P['Type']]['Name'];
        if ($PlanetTypes[$P['Type']]['Append']) $type .= " Planet";
        $Name = $P['Name'];
        $SysId = $P['SystemId'];
        $EditP = "PlanEdit.php";
        $Who = $P['Control'];
        break;

      case 2: /// Moon
        $WH = $M = Get_Moon($W['ThingId']);
        $type = $PlanetTypes[$M['Type']]['Name'];
        if ($PlanetTypes[$M['Type']]['Append']) $type .= " Moon";
        $Name = $M['Name'];
        $P = Get_Planet($M['PlanetId']);
        $SysId = $P['SystemId'];
        $EditP = "MoonEdit.php";
        $Who = $M['Control'];
        break;

      case 3: // Thing
        $WH = $T = Get_Thing($W['ThingId']);
        $type = $TTypes[$T['Type']]['Name'];
        $Name = $T['Name'];
        $SysId = $T['SystemId'];
        $EditP = "ThingEdit.php";
        $Who = $M['Whose'];
        break;
      default: // Error
        echo "<h2 class=Err>There is a fault with World " . $W['id'] . " Tell Richard</h2>";
        break;
    }

    $FS = Get_FactionSystemFS($Fid,$SysId);
    $PlanetLevel = max(1,($FS['PlanetScan']??1));
    $NumDists = 0;
    foreach ($Dists as $DT) {
      $NumDists += $DT['Number'];
      $DistTypes++;
    }
    $System = Get_System($SysId);
  } else {
    $NumDists = 0;
  }

  $dc=0;
  $totdisc = 0;
  $MaxDists = $WH['MaxDistricts']??0;
  $MaxOff = $WH['MaxOffices']??0;

//  var_dump($Mode,$NeedDelta);
  echo "<h2>" . $WH['Name'] . " in " . System_Name($System,$Fid) . "</h2>";
  echo "<table border>";
  if ($Mode) {
    echo "The only things you can change (currently) is the name, description and relative importance - higher numbers appear first when planning projects.<p>\n";
    Register_AutoUpdate("Worlds",$Wid);
    echo fm_hidden('id', $Wid);
  }
  if ($Mode ==2) echo "<tr><td>Id:<td>$Wid\n";
  if (Access('God')) {
    echo "<tr>" . fm_number('God: ThingType',$W,'ThingType') . fm_number('ThingId',$W,'ThingId');
  }
  echo "<tr>" . ($Mode? fm_text('Name',$WH,'Name',3,'','',"Name:" . $W['ThingType'] . ":" . $W['ThingId']) : "<td>Name:<td>" . $WH['Name']);
  WorldFlags($W);
  echo "<tr>" . ($Mode? fm_textarea('Description',$WH,'Description',8,3,'','', "Description:" . $W['ThingType'] . ":" . $W['ThingId']) :
     "<td colspan=4>" . ParseText($WH['Description']));
  echo "<tr><td>Controlled by:<td " . FactColours($Who) . ">" . ($Facts[$Who]['Name']??'No One');
  if ($Mode ==2) {
    echo "<tr>" . fm_number("Minerals", $W, 'Minerals');
  } else {
    echo "<tr><td>Minerals<td>" . $W['Minerals'];
  }
  if ($Mode) {
    echo "<tr>" . fm_number("Relative Importance", $W, 'RelOrder');
  }
  if ($MaxDists == 0) {
    if ($Mode >0) echo "<tr><td>No limit to number of Districts";
  } else {
    echo "<tr><td>Max Districts:<td>$MaxDists";
    if ($MaxOff > 0) {
      echo "<td>Max Offices:<td>$MaxOff";
    } else if ($MaxOff<0) {
      echo "<td>And Offices";
    }
  }
  if ($Mode ==2) echo "<td><a href=$EditP?i=" . $W['ThingId'] . ">Edit</a>";

  /*
   if ($GM) {
   echo "<tr>" . fm_number('Devastation',$W,'Devastation');
   echo "<tr>" . fm_number('Economy Factor
   */
  $NumCom = 0;
  $NumPrime = $Mines = 0; $DeltaSum = 0;
  if ($NumDists) {
    if ($NumDists) echo "<tr><td rowspan=" . ($DistTypes+1) . ">Districts:";
    foreach ($Dists as $D) {
      if ($D['Number'] == 0) continue;
      //      var_dump($D);
      echo "<tr><td>" . ($DTs[$D['Type']]['Name'] ?? 'Illegal') . ": " . $D['Number'];
      if ($NeedDelta) {
        echo fm_number1("Delta",$D,'Delta',''," min=-$NeedDelta max=$NeedDelta ","Dist:Delta:" . $D['id']);
        $DeltaSum += $D['Delta'];
      }
      if ( (($DTs[$D['Type']]['Name']??0) == 'Intelligence') && Has_Tech($Fid,'Defensive Intelligence' )) {
        $Agents = Get_Things_Cond($Fid," Type=5 AND Class='Military' AND SystemId=$SysId ORDER BY Level DESC");
        if ($Agents) {
          $Bi = ($Agents[0]['Level']/2);
          echo " ( +$Bi From Defensive Intelligence)";
        }

      }
    }
    if ($NeedDelta && $DeltaSum != 0) {
      echo "<tr><td colspan=3 class=Err>The Deltas do not sum to zero";
    }
  } else {
    echo "<tr><td>No Districts currently\n";
  }

  $OrgTypes = Get_OrgTypes();
  $Orgs = Gen_Get_Cond('Organisations',"GameId=$GAMEID");

  // Offices
  $Offs = Gen_Get_Cond('Offices',"World=$Wid");
  if ($Offs) {
    $Clean = [];
    foreach ($Offs as $i=>$Of) {
      if ($Orgs[$Of['Organisation']]??0) {
        $Clean[$i] = $Of;
      } else {
        db_delete('Offices',$Of['id']);
      }
    }

    if ($Clean) {
      echo "<tr><td rowspan=" . count($Clean) . ">Offices:";
      $Show = 0;
      foreach ($Clean as $i=>$Of) {
        if ($Show++) echo "<tr>";
        echo "<td colspan=4>" . ($Orgs[$Of['Organisation']]['Name']??'Unknown') .
        " ( " . ($OrgTypes[$Orgs[$Of['Organisation']]['OrgType']]['Name']??'Unknown') .
        ($Orgs[$Of['Organisation']]['OrgType2']? '/' . ($OrgTypes[$Orgs[$Of['Organisation']]['OrgType']]['Name']??'Unknown'):'') . " )";
      }
    }
  }

  if ($SocPs) {
    echo "<tr><td rowspan=" . count($SocPs)*2 . ">Social\nPrinciples:";
    $NumSp = 0;
    foreach ($SocPs as $si=>$SP) {
      $Prin = Get_SocialP($SP['Principle']);
      if (!$Prin) {
        echo "<tr><td>Error - Tell Richard";
        continue;
      }
      //var_dump($Prin,$SP);
      if ($NumSp++) echo "<tr>";
      if ($Mode ==2) {
        echo "<td><b>" . $Prin['Principle'] . "</b><td>Adherence: " . $SP['Value'];
        echo "<td " . FactColours($Prin['Whose']) . ">" . ($Facts[$Prin['Whose']]['Name']??'Unknown');
        echo "<td><a href=SocialEdit.php?Action=Edit&id=" . $SP['Principle'] . ">Change</a>";
        echo "<tr><td colspan=6>" . ParseText($Prin['Description']);

      } else { // Player
        echo "<td><b>" . $Prin['Principle'] . "</b><td>Adherence: " . $SP['Value'];
        echo "<tr><td colspan=6>" . ParseText($Prin['Description']);
      }
    }
  } else {
    echo "<tr><td colsan=2>No Social Principles Currently\n";
  }

  $Branches = Gen_Get_Cond('Branches', "HostType=" . $W['ThingType'] . " AND HostId=" . $W['ThingId'] );
  $BTypes = Get_BranchTypes();

  if ($Branches) {
    $Show = 0;
    if ($Mode ==2) {
      $Show = 1;
    } else {
      foreach ($Branches as $bi=>$B) if (($B['Whose']== $Fid) || (($BTypes[$B['Type']]['Props']&1)==0)) { $Show = 1; break;}
    }
    if ($Show) {
      echo "<tr><td>Branches:<td colspan=5>";
      foreach ($Branches as $bi=>$B) {
        //       var_dump($B);
        if ($Mode ==2 || ($B['Whose']== $Fid) || (($BTypes[$B['Type']]['Props']&1)==0)) {
          echo "A <b>" . $BTypes[$B['Type']]['Name'] . "</b> of the <b>" . $Orgs[$B['Organisation']]['Name'] .
          "</b> ( " . $OrgTypes[$Orgs[$B['Organisation']]['OrgType']]['Name'] . " ) - " .
          "<span " . FactColours($B['Whose']) . ">" . $Facts[$B['Whose']]['Name'] . "</span>";
          if (($BTypes[$B['Type']]['Props']&1) != 0) echo " [Hidden]";
          if ($B['Suppressed']) echo " - Suppresed " . $B['Suppressed'] . Plural($B['Suppressed'],'','turn',' turns');
          echo "<br>";
        }
      }
    }
  }

  // Traits - need planet survey level

  if ($WH) {
    for($i=1;$i<4;$i++) {
      if ($WH["Trait$i"] && ($WH["Trait$i" . "Conceal"] <= $PlanetLevel)) {
        echo "<tr><td>Trait:<td><b>" . $P["Trait$i"] . "</b><td colspan=4>" . ParseText($P["Trait$i" . "Desc"]);
      }
    }
  }
  [$H['Economy'],$Rtxt] = Recalc_Economic_Rating($H,$W,$Fid);
  if (isset($H['id'])) Put_ProjectHome($H);

  if (!empty($W['MaxDistricts'])) echo "<td>Max Districts: " . $WH['MaxDistricts'];
  echo "<tr><td>Economy:<td>" . $H['Economy'];
  if ($W['Devastation']??0) {
    if ($W['Devastation'] <= $NumDists) {
      echo "<tr><td>Devastation:<td>" . $H['Devastation'] . " If this ever goes higher than the number of districts ($NumDists), districts will be lost.";
    } else {
      echo "<tr><td class=Err>Devastation:<td class=Err>" . $W['Devastation'] . "  This is higher than the number of districts ($NumDists), districts will be lost.";
    }
  }
  if (Access('God')) echo "</tbody><tfoot><tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea>";
  echo "</table>";

}

function ShowOutpost($Tid,$Fid,$GM=0) {
  $Branches = Gen_Get_Cond('Branches',"HostType=3 AND HostId=$Tid");
  $Facts = Get_Factions();
  $OutP = Get_Thing($Tid);
  $Found = 0;
  foreach ($Branches as $B) if ($GM || $B['Whose']==$Fid) $Found = 1;
  if (!$Found) {
    echo "<h1>No branches of yours at that Outpost</h1>";
  } else {
    $System = Get_System($OutP['SystemId']);
    echo "<h1>Details of " . ($OutP['Name']??'') . " Outpost in " . System_Name($System,$Fid) . "<h1>";
    echo "<table border>";
    echo "<tr><td>Controlled by:<td " . FactColours($OutP['Whose']) . ">" . ($Facts[$OutP['Whose']]['Name']??'No One');
    echo "<tr><td>Branches:<td>";
    foreach ($Branches as $B) {
      $BT = Gen_Get('BranchTypes',$B['Type']);
      if (($B['Whose'] != $Fid) && ($BT['Props'] & BRANCH_HIDDEN) && !$GM) continue;
      $Org = Gen_Get('Organisations',$B['Organisation']);
      $OrgType = Gen_Get('OfficeTypes', $B['OrgType']);
      if ($B['OrgType2'] && ($GM || $B['Whose']==$Fid)) $OrgType2 = Gen_Get('OfficeTypes', $B['OrgType2']);
      echo $Org['Name'] . " (" . $OrgType['Name'] . (($OrgType2??0)? "/" . $OrgType2['Name']:'') . ") ";
      echo "<span " . FactColours($B['Whose']) . ">" . ($Facts[$B['Whose']]['Name']??'No One') . "</span><br>";
    }
    echo "</table>";
  }
}
