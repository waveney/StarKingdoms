<?php

include_once("ThingLib.php");

function Recalc_Mined_locs() {
  $Mines = Get_Things_Cond(0,'Type=10');
  $Links = Gen_Get_Table('Links');
  $Planets = Gen_Get_Table('Planets', "ORDER BY OrbitalRadius");
  $Moons = Gen_Get_Table('Moons', "ORDER BY OrbitalRadius");
  $SystemRs = Get_SystemRefs();

  foreach ($Links as &$L) $L['FMinedA'] = $L['FMinedB'] = 0;
  foreach ($Planets as &$P) $P['FMined'] = 0;
  foreach ($Moons as &$Mn) $Mn['FMined'] = 0;


//var_dump($Links);exit;
  foreach ($Mines as $M) {
    $Mid = $M['id'];
    $WLoc = $M['WithinSysLoc'];
    $LocType = intdiv($WLoc,100);
         
    switch ($LocType) {
    case 1: //Orbiting Planet
      $Pi = 1;
      foreach($Planets as &$P) if ($P['SystemId'] == $M['SystemId']) if ( (100+($Pi++)) == $WLoc ) break;
           
      $P['FMined'] = $Mid;
      break;
         
    case 3: //Orbiting Moon
      $Mi = 1;
      foreach($Planets as &$P) {
        if ($P['SystemId'] != $M['SystemId']) continue;
        foreach($Moons as &$Mn) if ($Mn['PlanetId'] == $P['id']) if ((300+($Mi++)) == $WLoc ) break 2;
      }
      if (isset($Moons) && isset($Mn)) {
        $Mn['FMined'] = $Mid;
      } else {
        echo "Could not find home for Mine $Mid<P>";
      }
      break;
         
    case 5: //At Stargate
      $Li = 1;
      $Ref = $SystemRs[$M['SystemId']];
      foreach($Links as &$lk) if ($lk['System1Ref'] == $Ref || $lk['System2Ref'] == $Ref) if ( (500+($li++)) == $WLoc ) break;
           
      if ($lk['System1Ref'] == $Ref) {
        $lk['FMinedA'] = $Mid;
      } else {
        $lk['FMinedB'] = $Mid;
      }
      break;
         
    default:
           // Should not get here (yet)
    } 
  }
  
  echo "Scanned all Mines, now generating data<p>";
  
  foreach($Planets as &$P) {
    if ($P['Mined'] == 0 && $P['FMined'] == 0) continue;
    $P['Mined'] = $P['FMined'];
    Put_Planet($P);
  }

  echo "Done Planets<p>";
  
  foreach($Moons as &$M) {
    if ($M['Mined'] == 0 && $M['FMined'] == 0) continue;
    $M['Mined'] = $M['FMined'];
    Put_Moon($M);
  }

  echo "Done Moons<p>";
  
  foreach($Links as &$L) {
    if ($L['MinedA'] == 0 && $L['FMinedA'] == 0 && $L['MinedB'] == 0 && $L['FMinedB'] == 0) continue;
    $L['MinedA'] = $M['FMinedA'];    
    $L['MinedB'] = $M['FMinedB'];
    Put_Link($L);
  }
  echo "Done Links<p>";
  
}


?>
