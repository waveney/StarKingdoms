<?php

// Scan all plantets/moon's/Thing's for districts and deep space construction

  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("PlayerLib.php");
  include_once("SystemLib.php");
  include_once("ProjLib.php");
  
  global $FACTION;
  
  if (Access('GM') ) {
    A_Check('GM');
    $Fid = $_REQUEST['id'];
    $Faction = Get_Faction($Fid);
  } else if (Access('Player')) {
    if (!$FACTION) {
      Error_Page("Sorry you need to be a GM or a Player to access this");
    }
    $Fid = $FACTION['id'];
    $Faction = &$FACTION;
  }

  dostaffhead("New Projects for faction");
  
  echo "<h1>New Project</h1>";
 
  $Homes = Get_ProjectHomes($Fid);
  $DistTypes = Get_DistrictTypes();
  $ProjTypes = Get_ProjectTypes();
    
  $PHx = 1;
  $Dis = [];
  foreach ($Homes as &$H) {
    $Hi = $H['id'];
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
    
    //TODO Construction and Districts... 

    $Dists[] = ['HostType'=>-1, 'HostId' => $PH['id'], 'Type'=> -1, 'Number'=>0, 'id'=>-1];  

    foreach ($Dists as &$D) {
      if ($D['Type'] > 0 && (($DistTypes[$D['Type']]['Props'] &2) == 0)) continue;
      if ($D['Type'] < 0 && $PH['Type'] != $Faction['Biosphere'] && Has_Tech($Fid,3)<2 ) continue;
      $Di = $D['id'];
      $Headline2[] = "<th class=PHStart id=PHDist$Hi:$Di>[S]<th class=PHName>" . ($D['Type'] > 0 ? $DistTypes[$D['Type']]['Name'] : 'Construction') .
        "<th class=PHLevel id=PHLevel$Hi:$Di>Level<th class=PHCost id=PHCost$Hi:$Di>Cost<th class=PHRush id=PHRush$Hi:$Di>Rush" .
        "<th class=PHProg id=PHProg$Hi:$Di>Prog<th class=PHStatus id=PHStatus$Hi:$Di>Status";
     
      $Dis[$Hi][] = $Di;
      }
      
    $HDists[$Hi] = $Dists;
    }
    
  $Turn = $_REQUEST['t'];
  $Hi = $_REQUEST['Hi'];
  $Di = $_REQUEST['Di'];
    
  $Stage = (isset($_REQUEST['STAGE'])? $_REQUEST['STAGE'] : 0);
  $Where =  ($D['Type'] > 0 ? $DistTypes[$D['Type']]['Name'] : 'Construction');
  $CDists = $HDists[$Hi];
    
    
// var_dump($CDists);

  echo "<form method=post action=ProjNew.php>";
  echo fm_hidden('t',$Turn) . fm_hidden('Hi',$Hi) . fm_hidden('Di',$Di);
  
  switch ($Where) {
  case 'Construction':
    echo "<h2>Select Construction Project:</h2><p>";
      $DTs = Get_DistrictTypes();
      $DNames = [];
      foreach ($DTs as $DT) {
        if ($DT['BasedOn'] == 0 || Has_tech($Fid,$DT['BasedOn'])) {
          $DNames[$DT['id']] = $DT['Name'];
          
          $Lvl = 0;
          
          foreach ($CDists as $D) if ($D['Type'] == $DT['id']) {
            $Lvl = $D['Number'];
            break;
          }
          
          $Lvl++;
          $pc = Proj_Costs($Lvl);
          echo "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=1&t=$Turn&Hi=$Hi&Di=$Di&Sel=" . $DT['id'] . 
                "&Name=" . base64_encode("Build " . $DT['Name'] . " District $Lvl"). "&L=$Lvl'>" .
                "Build " . $DT['Name'] . " District $Lvl; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";        
        
        }
      }
      
    echo "<h2>Rebuild and reapir</h2>Not yet selectable<p>";
      
    echo "<h2>Construct Warp Gate</h2>";
      $pc = Proj_Costs(4);
      echo "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=3&t=$Turn&Hi=$Hi&Di=$Di&Sel=0" .
                "&Name=" . base64_encode("Build Warp Gate"). "&L=$Lvl'>" .
                "Build Warp Gate; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";
    
    echo "<h2>Research Planetary Construction</h2><p>";
      $OldPc = Has_Tech($Fid,3);
      $Lvl = $OldPc+1;
      $pc = Proj_Costs($Lvl);
      echo "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=4&t=$Turn&Hi=$Hi&Di=$Di&Sel=0" .
                "&Name=" . base64_encode("Research Planetary Construction $Lvl"). "&L=$Lvl'>" .      
                "Research Planetary Construction $Lvl; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";  
    
    break;
      
  case 'Academic':
    
      break;
      
      
  case 'Shipyard':
    
      break;
      
  case 'Military':
    
      break;
      
  case 'Inteligence':
    
      break;
    }
  echo "</form>";  

  dotail();
        
  

?>
