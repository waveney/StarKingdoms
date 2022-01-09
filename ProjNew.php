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

  $Di = $_REQUEST['Di'];
  $Hi = $_REQUEST['Hi'];
      
  $PHx = 1;
  $Dis = [];
  foreach ($Homes as &$H) {
    $Hix = $H['id'];
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
      $Dix = $D['id'];
     
      if ($Hi == $Hix && $Di == $Dix) break 2;  // $D is now the relevant one
      $Dis[$Hix][] = $Dix;
      }
      
    $HDists[$Hix] = $Dists;
    }
    
  $Turn = $_REQUEST['t'];

  $Stage = (isset($_REQUEST['STAGE'])? $_REQUEST['STAGE'] : 0);
  
  $Where =  ($D['Type'] > 0 ? $DistTypes[$D['Type']]['Name'] : 'Construction');


// echo "ZZ: $Where<p>";    
    
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
  
    echo "<h2>Research Core Technology</h2>";
      $FactTechs = Get_Faction_Techs($Fid);
      $CTs = Get_CoreTechsByName();
      foreach ($CTs as $TT) {
        $Lvl = $FactTechs[$TT['id']]['Level']+1;
        $pc = Proj_Costs($Lvl);
        echo "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=5&t=$Turn&Hi=$Hi&Di=$Di&Sel=0" .
                "&Name=" . base64_encode("Research " . $TT['Name'] . " $Lvl"). "&L=$Lvl'>" .      
                "Research " . $TT['Name'] . " $Lvl; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";  
        }
 
    echo "<h2>Research Supplimental Technology</h2>";
      $Techs = Get_TechsByCore($Fid);
      foreach ($Techs as $T) {
        if ($T['Cat'] == 0 || isset($FactTechs[$T['id']]) ) continue;
        if (!isset($FactTechs[$T['PreReqTech']]) ) continue;
        if ( ($FactTechs[$T['PreReqTech']]['Level']<$T['PreReqLevel'] ) ) continue;
        $Lvl = $T['PreReqLevel'];
        echo "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=6&t=$Turn&Hi=$Hi&Di=$Di&Sel=0" .
                "&Name=" . base64_encode("Research " . $T['Name'] . " $Lvl"). "&L=$Lvl'>" .      
                "Research " . $T['Name'] . " $Lvl; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";
      }
   
    echo "<h2>Share Technology</h2>";
      echo "This is manual at present<p>";
    
    echo "<h2>Analyse</h2>";
      echo "These projects will be defined by a GM<p>";
      
    
    echo "<h2>Decipher Alien Language</h2>";      
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
