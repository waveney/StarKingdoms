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
      $Sid = $PH['SystemId'];
      break;
    case 2: // Moon
      $PH = Get_Moon($H['ThingId']);
      $Dists = Get_DistrictsM($H['ThingId']);
      $Plan = Get_Planet($PH['PlanetId']);
      $Sid = $Plan['SystemId'];
      break;
    case 3: // Thing
      $PH = Get_Thing($H['ThingId']);
      $Dists = Get_DistrictsT($H['ThingId']);
      if (!$Dists) {
        $H['Skip'] = 1;
        continue 2;  // Remove things without districts
      }
      $Sid = $PH['SystemId'];
      break;
    }
    //TODO Construction and Districts... 

    $Dists[] = ['HostType'=>-1, 'HostId' => $PH['id'], 'Type'=> -1, 'Number'=>0, 'id'=>-1];  
    foreach ($Dists as &$D) {
      if ($D['Type'] > 0 && (($DistTypes[$D['Type']]['Props'] &2) == 0)) continue;
      if ($D['Type'] < 0 && $PH['Type'] != $Faction['Biosphere'] && Has_Tech($Fid,3)<2 ) continue;
      $Dix = $D['id'];
     
      if ($Hi == $Hix && $Di == $Dix) {
        $HDists[$Hix] = $Dists;      
        break 2;  // $D is now the relevant one
      }
      $Dis[$Hix][] = $Dix;
      }

    $HDists[$Hix] = $Dists;
    }
    
  $Turn = $_REQUEST['t'];

  $Stage = (isset($_REQUEST['STAGE'])? $_REQUEST['STAGE'] : 0);
  
  $Where =  ($D['Type'] > 0 ? $DistTypes[$D['Type']]['Name'] : 'Construction');


// echo "ZZ: $Where<p>";    
    
// var_dump($HDists);

  echo "<form method=post action=ProjNew.php>";
  echo fm_hidden('t',$Turn) . fm_hidden('Hi',$Hi) . fm_hidden('Di',$Di);
  
// echo "Doinfg $Where<p>";
  switch ($Where) {

 
  case 'Construction':
    echo "<h2>Select Construction Project:</h2><p>";
      $DTs = Get_DistrictTypes();
      $DNames = [];
      foreach ($DTs as $DT) {
        if ($DT['BasedOn'] == 0 || Has_tech($Fid,$DT['BasedOn'])) {
          $DNames[$DT['id']] = $DT['Name'];
          
          $Lvl = 0;
          
          foreach ($HDists[$Hi] as $D) if ($D['Type'] == $DT['id']) {
            $Lvl = $D['Number'];
            break;
          }
          
          $Lvl++;
          $pc = Proj_Costs($Lvl);
          echo "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=1&t=$Turn&Hi=$Hi&Di=$Di&Sel=" . $DT['id'] . 
                "&Name=" . base64_encode("Build " . $DT['Name'] . " District $Lvl") . "&L=$Lvl&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .
                "Build " . $DT['Name'] . " District $Lvl; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";        
        
        }
      }
      
    echo "<h2>Rebuild and reapir</h2>Not yet selectable<p>";
      
    echo "<h2>Construct Warp Gate</h2>";
      $pc = Proj_Costs(4);
      echo "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=3&t=$Turn&Hi=$Hi&Di=$Di&Sel=0" .
                "&Name=" . base64_encode("Build Warp Gate"). "&L=$Lvl&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .
                "Build Warp Gate; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";
    
    echo "<h2>Research Planetary Construction</h2><p>";
      $OldPc = Has_Tech($Fid,3);
      $Lvl = $OldPc+1;
      $pc = Proj_Costs($Lvl);
      echo "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=4&t=$Turn&Hi=$Hi&Di=$Di&Sel=3" .
                "&Name=" . base64_encode("Research Planetary Construction $Lvl"). "&L=$Lvl&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .      
                "Research Planetary Construction $Lvl; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";  
    
    break;
      
  case 'Academic':
  
    echo "<h2>Research Core Technology</h2>";
      $FactTechs = Get_Faction_Techs($Fid);
      $CTs = Get_CoreTechsByName();
      foreach ($CTs as $TT) {
        $Lvl = $FactTechs[$TT['id']]['Level']+1;
        $pc = Proj_Costs($Lvl);
        echo "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=5&t=$Turn&Hi=$Hi&Di=$Di&Sel=" . $TT['id'] .
                "&Name=" . base64_encode("Research " . $TT['Name'] . " $Lvl"). "&L=$Lvl&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .      
                "Research " . $TT['Name'] . " $Lvl; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";  
        }
 
    echo "<h2>Research Supplimental Technology</h2>";
      $Techs = Get_TechsByCore($Fid);
      foreach ($Techs as $T) {
        if ($T['Cat'] == 0 || isset($FactTechs[$T['id']]) ) continue;
        if (!isset($FactTechs[$T['PreReqTech']]) ) continue;
        if ( ($FactTechs[$T['PreReqTech']]['Level']<$T['PreReqLevel'] ) ) continue;
        $Lvl = $T['PreReqLevel'];
        echo "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=6&t=$Turn&Hi=$Hi&Di=$Di&Sel=" . $T['id'] .
                "&Name=" . base64_encode("Research " . $T['Name']) . "&L=$Lvl&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .
                "Research " . $T['Name'] . "; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";
      }
   
    echo "<h2>Share Technology</h2>";
      echo "This is manual at present<p>";
    
    echo "<h2>Analyse</h2>";
      echo "These projects will be defined by a GM<p>";
      
    
    echo "<h2>Decipher Alien Language</h2>"; 
    echo "Not in this game<p>";     
      break;
      
      
  case 'Shipyard':
      echo "<h2>Build a Ship</h2>";
      echo "Not yet<p>";
      echo "<button class=projtype type=submit formaction='ProjSetup.php?t=Ship&ACTION=NEW&id=$Fid&p=10&t=$Turn&Hi=$Hi&Di=$Di'>Build a new ship</button><p>";
    
    
      echo "<h2>Refit, Repair and Decommision Ships</h2>";
      echo "Not yet";
      $Ships = Get_ThingsSys($Sid,$type=1,$Fid);
      if ($Ships) {
    
      }
    
    
      echo "<h2>Research Ship Construction</h2><p>";
        $OldPc = Has_Tech($Fid,7);
        $Lvl = $OldPc+1;
        $pc = Proj_Costs($Lvl);
        echo "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=13&t=$Turn&Hi=$Hi&Di=$Di&Sel=7" .
                "&Name=" . base64_encode("Research Ship Construction $Lvl"). "&L=$Lvl&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .  
                "Research Ship Construction $Lvl; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";  
    
      $Rbuts = [];
      $FactTechs = Get_Faction_Techs($Fid);
      $Techs = Get_TechsByCore($Fid);
      foreach ($Techs as $T) {
        if ($T['Cat'] == 0 || isset($FactTechs[$T['id']]) ) continue;
        if (!isset($FactTechs[$T['PreReqTech']])) continue;
        if ($T['PreReqTech']!=7) continue;
        if ( ($FactTechs[$T['PreReqTech']]['Level']<$T['PreReqLevel'] ) ) continue;

        $Lvl = $T['PreReqLevel'];
        $Rbuts[] = "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=14&t=$Turn&Hi=$Hi&Di=$Di&Sel=" . $T['id'] .
                "&Name=" . base64_encode("Research " . $T['Name']) . "&L=$Lvl&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .
                "Research " . $T['Name'] . "; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";
      }
   
     if ($Rbuts) {
       echo "<h2>Research Supplimental Ship Technology</h2>";
       foreach ($Rbuts as $rb) echo $rb;
     }
 

    
      break;
      
  case 'Military':
      echo "<h2>Train an Army</h2>";
      echo "Not yet<p>";
      echo "<button class=projtype type=submit formaction='ProjArmy.php?ACTION=NEW&id=$Fid&p=15&t=$Turn&Hi=$Hi&Di=$Di'>Train a new army</button><p>";
    
    
      echo "<h2>Re-equip and Reinforce Army</h2>";
      echo "Not yet";
      $Armies = Get_ThingsSys($Sid,$type=2,$Fid);
      if ($Armies) {
    
      }
    
    
      echo "<h2>Research Military Organisation</h2><p>";
        $OldPc = Has_Tech($Fid,8);
        $Lvl = $OldPc+1;
        $pc = Proj_Costs($Lvl);
        echo "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=17&t=$Turn&Hi=$Hi&Di=$Di&Sel=8" .
                "&Name=" . base64_encode("Research Military Organisation $Lvl"). "&L=$Lvl&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .
                "Research Military Organisation $Lvl; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";  
    
      $Rbuts = [];
      $FactTechs = Get_Faction_Techs($Fid);
      $Techs = Get_TechsByCore($Fid);
      foreach ($Techs as $T) {
        if ($T['Cat'] == 0 || isset($FactTechs[$T['id']]) ) continue;
        if (!isset($FactTechs[$T['PreReqTech']])) continue;
        if ($T['PreReqTech']!=8) continue;
        if ( ($FactTechs[$T['PreReqTech']]['Level']<$T['PreReqLevel'] ) ) continue;

        $Lvl = $T['PreReqLevel'];
        $Rbuts[] = "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=18&t=$Turn&Hi=$Hi&Di=$Di&Sel=" . $T['id'] .
                "&Name=" . base64_encode("Research " . $T['Name']) . "&L=$Lvl'&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .
                "Research " . $T['Name'] . "; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";
      }
   
     if ($Rbuts) {
       echo "<h2>Research Supplimental Army Technology</h2>";
       foreach ($Rbuts as $rb) echo $rb;
     }
 


    
      break;
      
  case 'Intelligence':
      echo "<h2>Train an Agent</h2>";
      echo "Not yet<p>";
      echo "<button class=projtype type=submit formaction='ProjAgent.php?ACTION=NEW&id=$Fid&p=19&t=$Turn&Hi=$Hi&Di=$Di'>Train an agent</button><p>";
    
    
   
      echo "<h2>Research Intelligence Operations</h2><p>";
        $OldPc = Has_Tech($Fid,4);
        $Lvl = $OldPc+1;
        $pc = Proj_Costs($Lvl);
        echo "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=20&t=$Turn&Hi=$Hi&Di=$Di&Sel=4" .
                "&Name=" . base64_encode("Research Intelligence Operations $Lvl"). "&L=$Lvl&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .
                "Research Intelligence Operations $Lvl; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";  
    
      $Rbuts = [];
      $FactTechs = Get_Faction_Techs($Fid);
      $Techs = Get_TechsByCore($Fid);
      foreach ($Techs as $T) {
        if ($T['Cat'] == 0 || isset($FactTechs[$T['id']]) ) continue;
        if (!isset($FactTechs[$T['PreReqTech']])) continue;
        if ($T['PreReqTech']!=4) continue;
        if ( ($FactTechs[$T['PreReqTech']]['Level']<$T['PreReqLevel'] ) ) continue;

        $Lvl = $T['PreReqLevel'];
        $Rbuts[] = "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=21&t=$Turn&Hi=$Hi&Di=$Di&Sel=" . $T['id'] .
                "&Name=" . base64_encode("Research " . $T['Name']) . "&L=$Lvl&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .
                "Research " . $T['Name'] . "; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";
      }
   
     if ($Rbuts) {
       echo "<h2>Research Supplimental Intelligence Technology</h2>";
       foreach ($Rbuts as $rb) echo $rb;
     }
 


    
      break;
    }
  echo "</form>";  

  dotail();
        
  

?>
