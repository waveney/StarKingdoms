<?php

// Scan all plantets/moon's/Thing's for districts and deep space construction

  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("PlayerLib.php");
  include_once("SystemLib.php");
  include_once("ProjLib.php");
  
  global $FACTION;
  
  if (Access('Player')) {
    if (!$FACTION) {
      if (!Access('GM') ) Error_Page("Sorry you need to be a GM or a Player to access this");
    } else {
      $Fid = $FACTION['id'];
      $Faction = &$FACTION;
    }
  } 
  if (Access('GM') ) {
    A_Check('GM');
    if (isset( $_REQUEST['F'])) {
      $Fid = $_REQUEST['F'];
    } else if (isset( $_REQUEST['f'])) {
      $Fid = $_REQUEST['f'];
    } else if (isset( $_REQUEST['id'])) {
      $Fid = $_REQUEST['id'];
    }
    if (isset($Fid)) $Faction = Get_Faction($Fid);
  }

  dostaffhead("New Projects for faction");

  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
      case 'NEWSHIP': 
        $Limit = Has_Tech($Fid,'Ship Construction');
      case 'NEWARMY': 
        if ($_REQUEST['ACTION'] == 'NEWARMY') $Limit = Has_Tech($Fid,'Military Organisation');
      case 'NEWAGENT': 
         if ($_REQUEST['ACTION'] == 'NEWAGENT') $Limit = Has_Tech($Fid,'Intelligence Operations');
        $Ptype = $_REQUEST['p'];
        $Turn = $_REQUEST['t'];
        $Hi = $_REQUEST['Hi'];
        $Di = $_REQUEST['Di'];
        $Place = base64_decode($_REQUEST['pl']);
        
        if ($_REQUEST['ACTION'] == 'NEWSHIP') {
          $Things = Get_Things_Cond($Fid, 'DesignValid=1 AND ( Type=1 OR Type=2 OR Type=3 )');
        } else if ($_REQUEST['ACTION'] == 'NEWARMY') {
          $Things = Get_Things_Cond($Fid, 'DesignValid=1 AND Type=4');
        } else if ($_REQUEST['ACTION'] == 'NEWAGENT') {
          $Things = Get_Things_Cond($Fid, 'DesignValid=1 AND Type=5');
        } 
        
        if (!$Things) {
          echo "<h2>Sorry you do not have any plans for these - go to <a href=ThingPlan.php?F=$Fid>Thing Planning</a> first</h2>";
          break;
        }
        
        $NameList = [];
        foreach ($Things as $T) {
          if ($T['Level'] > $Limit) continue;
          $pc = Proj_Costs($T['Level']);        
          $NameList[$T['id']] = $T['Name'] . (empty($T['Class'])?'': ", a " . $T['Class']) . " ( Level " . $T['Level'] . " ); $Place;" .
            "Cost: " . $pc[1] .  " Needs " . $pc[0] . " progress'>";
          }
        
        if ($NameList) {
          echo "<h2>Select a deign to make</h2>";
          echo "If it is in planning, you are build that, if already built then it will be a copy.<br>";
        
          echo "<form method=post action=ProjDisp.php?ACTION=NEW&id=$Fid&p=$Ptype&t=$Turn&Hi=$Hi&Di=$Di>";
          echo fm_select($NameList,$_REQUEST,'ThingId',1," onchange=this.form.submit()") . "\n<br>";
        } else {
          echo "<h2>Sorry you do not have any plans for these - go to <a href=ThingPlan.php?F=$Fid>Thing Planning</a> first</h2>";
        }
        echo "<h2><a href=ProjDisp.php?id=$Fid>Cancel</a></h2>\n";
        dotail();
        
      break;
      
    }
  }
  


  
  echo "<h1>New Project</h1>";
 
  $Homes = Get_ProjectHomes($Fid);
  $DistTypes = Get_DistrictTypes();
  $ProjTypes = Get_ProjectTypes();

  $DiCall = $_REQUEST['Di'];
  $Di = abs($DiCall);
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

    $Dists[] = ['HostType'=>-1, 'HostId' => $PH['id'], 'Type'=> -1, 'Number'=>0, 'id'=>-$PH['id']];
    foreach ($Dists as &$D) {
      if ($D['Type'] > 0 && (($DistTypes[$D['Type']]['Props'] &2) == 0)) continue;
      if ($D['Type'] < 0 && $PH['Type'] != $Faction['Biosphere'] && Has_Tech($Fid,3)<2 ) continue;
      $Dix = $D['id'];
     
      if (($Hi == $Hix) && ($DiCall == $Dix)) {
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
  
  $Place = " on " . $PH['Name'];
  $pl = "&pl=" . base64_encode($Place);

// echo "ZZ: $Where<p>";    
    
//var_dump($HDists);

  echo "<form method=post action=ProjNew.php>";
  echo fm_hidden('t',$Turn) . fm_hidden('Hi',$Hi) . fm_hidden('Di',$Di);
  
// echo "Doinfg $Where<p>";
  switch ($Where) {

 
  case 'Construction':
    echo "<h2>Select Construction Project:</h2><p>";
      $DTs = Get_DistrictTypes();
      $DNames = [];
//var_dump($HDists[$Hi]);
      foreach ($DTs as $DT) {
        if (eval("return " . $DT['Gate'] . ";" )) { // ($DT['BasedOn'] == 0 || Has_tech($Fid,$DT['BasedOn'], $Turn)) {
          $DNames[$DT['id']] = $DT['Name'];
          
          $Lvl = 0;
          
          foreach ($HDists[$Hi] as $D) {
//echo "<br>Checking "; var_dump($D);
            if ($D['Type'] == $DT['id']) {
              $Lvl = $D['Number'];
              break;
            }
          }
          
 //echo "Have $Lvl of " . $DT['Name'] . "<P>";
// TODO bug if you already have that level in the pipeline - Add check to Turns Ready          
          $Lvl++;
          $pc = Proj_Costs($Lvl);
          if (Has_Trait($Fid,"On the Shoulders of Giants") && $Lvl>1 ) {
            $pc = Proj_Costs($Lvl-1);
            if ($DT['Type'] == 9) $pc[1] = 0;
          }
          echo "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=1&t=$Turn&Hi=$Hi&Di=$Di&Sel=" . $DT['id'] . 
                "&Name=" . base64_encode("Build " . $DT['Name'] . " District $Lvl$Place") . "&L=$Lvl&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .
                "Build " . $DT['Name'] . " District $Lvl; $Place; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";        
        
        }
      }
      
    echo "<h2>Rebuild and repair</h2>Manual at present. Please put this in your turn orders<p>";
      
      
    echo "<h2>Construct Warp Gate</h2>";
      $pc = Proj_Costs(4);
      echo "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=3&t=$Turn&Hi=$Hi&Di=$Di&Sel=0" .
                "&Name=" . base64_encode("Build Warp Gate$Place"). "&L=$Lvl&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .
                "Build Warp Gate $Place; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";
    
    echo "<h2>Research Planetary Construction</h2><p>";
      $OldPc = Has_Tech($Fid,3, $Turn);
      $Lvl = $OldPc+1;
      $pc = Proj_Costs($Lvl);
      echo "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=4&t=$Turn&Hi=$Hi&Di=$Di&Sel=3" .
                "&Name=" . base64_encode("Research Planetary Construction $Lvl$Place"). "&L=$Lvl&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .      
                "Research Planetary Construction $Lvl; $Place; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";  
    
    break;
      
  case 'Academic':
  
    echo "<h2>Research Core Technology</h2>";
      $FactTechs = Get_Faction_Techs($Fid, $Turn);
      $CTs = Get_CoreTechsByName();
      foreach ($CTs as $TT) {
        if (isset($FactTechs[$TT['id']]['Level'])) {
          $Lvl = $FactTechs[$TT['id']]['Level']+1;
        } else {
          $Lvl = 1;
        }
        $pc = Proj_Costs($Lvl);
        echo "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=5&t=$Turn&Hi=$Hi&Di=$Di&Sel=" . $TT['id'] .
                "&Name=" . base64_encode("Research " . $TT['Name'] . " $Lvl$Place"). "&L=$Lvl&C=" . $pc[1] . "&PN=" . $pc[0] ."'>" .      
                "Research " . $TT['Name'] . " $Lvl; $Place; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";  
        }
 
    echo "<h2>Research Supplimental Technology</h2>";
      $Techs = Get_TechsByCore($Fid);
      foreach ($Techs as $T) {
        if ($T['Cat'] == 0 || isset($FactTechs[$T['id']]) ) continue;
        if (!isset($FactTechs[$T['PreReqTech']]) ) continue;
        if ( ($FactTechs[$T['PreReqTech']]['Level']<$T['PreReqLevel'] ) ) continue;
        $Lvl = $T['PreReqLevel'];
        $pc = Proj_Costs($Lvl);
        echo "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=6&t=$Turn&Hi=$Hi&Di=$Di&Sel=" . $T['id'] .
                "&Name=" . base64_encode("Research " . $T['Name'] . $Place) . "&L=$Lvl&C=" . $pc[1] . "&PN=" . $pc[0] ."'>" .
                "Research " . $T['Name'] . "; $Place; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";
      }
   
    echo "<h2>Share Technology</h2>";
//      echo "This is manual at present. Please put this in your turn orders<p>";
      echo "</form>";  
      echo "<form method=post action='ProjDisp.php?ACTION=NEW&id=$Fid&p=7&t=$Turn&Hi=$Hi&Di=$Di'>";

      $Shares = [];
      foreach ($CTs as $TT) {
        $Tid = $TT['id'];
        for($Lvl = 1; $Lvl <= $FactTechs[$Tid]['Level']; $Lvl++) {
          $pc = Proj_Costs($Lvl-1);
          $Shares["$Tid:$Lvl"] = $TT['Name'] . " at level $Lvl; Cost " . $pc[1] . " Needs " . $pc[0] . " progress";
        }
      }
      
      foreach ($Techs as $T) {
        if ($T['Cat'] == 0 || !isset($FactTechs[$T['id']]) ) continue;
        if (!isset($FactTechs[$T['PreReqTech']]) ) continue;
        $Tid = $T['id'];
        $Lvl = $T['PreReqLevel'];
        if ($Lvl < 1) continue;
        $pc = Proj_Costs($Lvl-1);
        $Shares["$Tid:$Lvl"] = $T['Name'] . " at level $Lvl; Cost " . $pc[1] . " Needs " . $pc[0] . " progress";
      }
      
      $Factions = Get_Factions(); 
      $Facts = Get_FactionFactions($Fid);
      $FactList = [];
      foreach ($Facts as $Fi=>$F) {
        $FactList[$Fi] = $Factions[$Fi]['Name'];
      }
      echo "Tecnology: " . fm_select($Shares,$_REQUEST,"Tech2Share") . " share with: " .fm_select($FactList,$_REQUEST,"ShareWith",1);
      echo "<button class=projtype type=submit>Share</button>";
      echo "</form><p>"; 
    
    echo "<h2>Analyse</h2>";
      echo "These projects will be defined by a GM<p>";
      echo "<form method=post action='ProjDisp.php?ACTION=NEW&id=$Fid&p=8&t=$Turn&Hi=$Hi&Di=$Di'>";
      echo "Name of project - meaningfull to you and the GM:" . fm_text0('',$_REQUEST,'AnalyseText'). fm_number0(' Level ',$_REQUEST,'Level');
      echo "<button class=projtype type=submit>Analyse</button>";
      echo "</form><p>"; 
      
    
//    echo "<h2>Decipher Alien Language</h2>"; 
//    echo "Not in this game<p>";     
      break;
      
      
  case 'Shipyard':
      echo "<h2>Build a Ship</h2>";

      echo "This action is to build an already designed ship.  If you want a new design please go to <a href=ThingPlan.php>The Thing Planning Tool</a> first.<p>\n";
      echo "<button class=projtype type=submit formaction='ProjNew.php?ACTION=NEWSHIP&id=$Fid&p=10&t=$Turn&Hi=$Hi&Di=$Di$pl'>Build a new ship$Place</button><p>";
    
    
    
      echo "<h2>Research Ship Construction</h2><p>";
        $OldPc = Has_Tech($Fid,7);
        $Lvl = $OldPc+1;
        $pc = Proj_Costs($Lvl);
        echo "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=13&t=$Turn&Hi=$Hi&Di=$Di&Sel=7" .
                "&Name=" . base64_encode("Research Ship Construction $Lvl$Place"). "&L=$Lvl&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .  
                "Research Ship Construction $Lvl; $Place; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";  
    
      $Rbuts = [];
      $FactTechs = Get_Faction_Techs($Fid, $Turn);
      $Techs = Get_TechsByCore($Fid);
      foreach ($Techs as $T) {
        if ($T['Cat'] == 0 || isset($FactTechs[$T['id']]) ) continue;
        if (!isset($FactTechs[$T['PreReqTech']])) continue;
        if ($T['PreReqTech']!=7) continue;
        if ( ($FactTechs[$T['PreReqTech']]['Level']<$T['PreReqLevel'] ) ) continue;

        $Lvl = $T['PreReqLevel'];
        $pc = Proj_Costs($Lvl);
        $Rbuts[] = "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=14&t=$Turn&Hi=$Hi&Di=$Di&Sel=" . $T['id'] .
                "&Name=" . base64_encode("Research " . $T['Name'] . $Place) . "&L=$Lvl&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .
                "Research " . $T['Name'] . "; $Place; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";
      }
   
      if ($Rbuts) {
        echo "<h2>Research Supplimental Ship Technology</h2>";
        foreach ($Rbuts as $rb) echo $rb;
      }

      if (Has_Trait($Fid,'Grow Modules')) {
        echo "<h2>Grow Modules</h2>";    
        $Grow = Has_Tech($Fid,'Ship Construction');
        $pc = Proj_Costs(1);
        echo "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=31&t=$Turn&Hi=$Hi&Di=$Di" .
                "&Name=" . base64_encode("Grow $Grow Modules" . $Place) . "&L=1&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .
                "Grow $Grow Modules " . "; $Place; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";

        echo "<h2>Grow Districts</h2>";
          $DTs = Get_DistrictTypes();
          foreach([3,5] as $DT) {
            $Lvl = 0;
          
            foreach ($HDists[$Hi] as $D) {
              if ($D['Type'] == $DT) {
                $Lvl = $D['Number'];
                break;
              }
            }
          
            $Lvl++;
            $pc = Proj_Costs($Lvl);
            echo "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=37&t=$Turn&Hi=$Hi&Di=$Di&Sel=$DT" . 
                  "&Name=" . base64_encode("Build " . $DTs[$DT]['Name'] . " District $Lvl$Place") . "&L=$Lvl&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .
                  "Build " . $DTs[$DT]['Name'] . " District $Lvl; $Place; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";        
        
          } 
      }

      echo "<h2>Refit and Repair</h2>"; // THIS MUST be AFTER the simple buttons as the form gets lost

      echo "You can only do this to ships at the same planet.<p>";
      $HSys = $Homes[$Hi]['SystemId'];
      $HLoc = $Homes[$Hi]['WithinSysLoc'];
      $TTs = Get_ThingTypes();
      $Things = Get_Things_Cond($Fid," SystemId=$HSys "); // Get all things at xx then filter  by type == Ship & WithinSysLoc
      $RepShips = [];
      $Level1 = 0;
      $Count = 0;
      foreach ($Things as $T) {
        if ($TTs[$T['Type']]['Properties'] & THING_HAS_SHIPMODULES) {
          if ($T['WithinSysLoc'] == $HLoc || $T['WithinSysLoc'] == ($HLoc-100)) {
            $RepShips[$T['id']] = $T['Name'] . " - level " . $T['Level'];
            if ($T['Level'] == 1) $Level1++;
            $Count++;
          }
        }
      }
      
      $pc = Proj_Costs(1);
      if ($Count) {
        if ($Count == 1) {
          foreach ($RepShips as $tid=>$Name) { 
            echo "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=11&t=$Turn&Hi=$Hi&Di=$Di&Sel=$tid" .
                "&Name=" . base64_encode("Refit and Repair " . $Name ) . "&L=1&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .  
                "Refit and Repair $Name $Place; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";
          }
        } else if ($Level1 < 2) {
          echo "</form><form method=post action='ProjDisp.php?ACTION=NEW&id=$Fid&p=11&t=$Turn&Hi=$Hi&Di=$Di'>";
          echo "Select the ship to refit and repair: " . fm_select($RepShips, $_REQUEST, 'Sel', 0) ;
          echo "<button class=projtype type=submit>Refit and Repair</button></form>";
        } else {
          echo "</form><form method=post action='ProjDisp.php?ACTION=NEW&id=$Fid&p=11&t=$Turn&Hi=$Hi&Di=$Di'>";
          echo "You may select <b>2</b> level 1 ships or 1 ship of level 2 or more. (For PCs and Linux hold down Ctrl to select 2nd ship)<p>";
          echo "Select the ship to refit and repair: " . fm_select($RepShips, $_REQUEST, 'Sel', 0, ' multiple ','Sel2[]') ;
          echo "<button class=projtype type=submit>Refit and Repair</button><form>";
        }
          
      } else {
        echo "No ships are currently near the yard<p>";
      }

      break;
      
  case 'Military':
      echo "<h2>Train an Army</h2>";
//      echo "Not yet<p>";
      echo "This action is to build an already designed Army.  If you want a new design please go to <a href=ThingPlan.php>The Thing Planning Tool</a> first.<p>\n";
      echo "<button class=projtype type=submit formaction='ProjNew.php?ACTION=NEWARMY&id=$Fid&p=15&t=$Turn&Hi=$Hi&Di=$Di$pl'>Train a new army$Place</button><p>";
    
    
      echo "<h2>Research Military Organisation</h2><p>";
        $OldPc = Has_Tech($Fid,8, $Turn);
        $Lvl = $OldPc+1;
        $pc = Proj_Costs($Lvl);
        echo "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=17&t=$Turn&Hi=$Hi&Di=$Di&Sel=8" .
                "&Name=" . base64_encode("Research Military Organisation $Lvl$Place") . "&L=$Lvl&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .
                "Research Military Organisation $Lvl; $Place; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";  
    
      $Rbuts = [];
      $FactTechs = Get_Faction_Techs($Fid);
      $Techs = Get_TechsByCore($Fid);
      foreach ($Techs as $T) {
        if ($T['Cat'] == 0 || isset($FactTechs[$T['id']]) ) continue;
        if (!isset($FactTechs[$T['PreReqTech']])) continue;
        if ($T['PreReqTech']!=8) continue;
        if ( ($FactTechs[$T['PreReqTech']]['Level']<$T['PreReqLevel'] ) ) continue;

        $Lvl = $T['PreReqLevel'];
        $pc = Proj_Costs($Lvl);
        $Rbuts[] = "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=18&t=$Turn&Hi=$Hi&Di=$Di&Sel=" . $T['id'] .
                "&Name=" . base64_encode("Research " . $T['Name'] .  $Place) . "&L=$Lvl&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .
                "Research " . $T['Name'] . "; $Place; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";
      }
   
     if ($Rbuts) {
       echo "<h2>Research Supplimental Army Technology</h2>";
       foreach ($Rbuts as $rb) echo $rb;
     }

    
      echo "<h2>Re-equip and Reinforce Army</h2>"; // THIS MUST be AFTER the simple buttons as the form gets lost
      echo "You can only do this to armies on the same planet.<p>";
      $HSys = $Homes[$Hi]['SystemId'];
      $HLoc = $Homes[$Hi]['WithinSysLoc'];
      $TTs = Get_ThingTypes();
      $Things = Get_Things_Cond($Fid," SystemId=$HSys "); // Get all things at xx then filter  by type == Ship & WithinSysLoc
      $RepShips = [];
      $Level1 = 0;
      $Count = 0;
      foreach ($Things as $T) {
        if ($TTs[$T['Type']]['Properties'] & THING_HAS_ARMYMODULES) {
          if ($T['WithinSysLoc'] == $HLoc ) {
            $RepShips[$T['id']] = $T['Name'] . " - level " . $T['Level'];
            if ($T['Level'] == 1) $Level1++;
            $Count++;
          }
        }
      }
      
      $pc = Proj_Costs(1);
      if ($Count) {
        if ($Count == 1) {
          foreach ($RepShips as $tid=>$Name) { 
            echo "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=16&t=$Turn&Hi=$Hi&Di=$Di&Sel=$tid" .
                "&Name=" . base64_encode("Re-equip and Reinforce " . $Name ) . "&L=1&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .  
                "Refit and Repair $Name $Place; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";
          }
        } else if ($Level1 < 2) {
          echo "</form><form method=post action='ProjDisp.php?ACTION=NEW&id=$Fid&p=16&t=$Turn&Hi=$Hi&Di=$Di'>";
          echo "Select the army to Re-equip and Reinforce: " . fm_select($RepShips, $_REQUEST, 'Sel', 0) ;
          echo "<button class=projtype type=submit>Re-equip and Reinforce</button></form>";
        } else {
          echo "</form><form method=post action='ProjDisp.php?ACTION=NEW&id=$Fid&p=16&t=$Turn&Hi=$Hi&Di=$Di'>";
          echo "You may select <b>2</b> level 1 Army or 1 Army of level 2 or more. (For PCs and Linux hold down Ctrl to select 2nd army)<p>";
          echo "Select the ship to Re-equip and Reinforce: " . fm_select($RepShips, $_REQUEST, 'Sel', 0, ' multiple ','Sel2[]') ;
          echo "<button class=projtype type=submit>Re-equip and Reinforce</button><form>";
        }
          
      } else {
        echo "No armies are currently there<p>";
      }


      break;
      
  case 'Intelligence':
      echo "<h2>Train an Agent</h2>";
//      echo "Not yet<p>";
      echo "This action is to build an already designed Agent.  If you want a new design please go to <a href=ThingPlan.php>The Thing Planning Tool</a> first.<p>\n";
      echo "<button class=projtype type=submit formaction='ProjNew.php?ACTION=NEWAGENT&id=$Fid&p=19&t=$Turn&Hi=$Hi&Di=$Di$pl'>Train an agent$Place</button><p>";
    
    
   
      echo "<h2>Research Intelligence Operations</h2><p>";
        $OldPc = Has_Tech($Fid,4, $Turn);
        $Lvl = $OldPc+1;
        $pc = Proj_Costs($Lvl);
        echo "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=20&t=$Turn&Hi=$Hi&Di=$Di&Sel=4" .
                "&Name=" . base64_encode("Research Intelligence Operations $Lvl$Place"). "&L=$Lvl&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .
                "Research Intelligence Operations $Lvl; $Place; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";  
    
      $Rbuts = [];
      $FactTechs = Get_Faction_Techs($Fid, $Turn);
      $Techs = Get_TechsByCore($Fid);
      foreach ($Techs as $T) {
        if ($T['Cat'] == 0 || isset($FactTechs[$T['id']]) ) continue;
        if (!isset($FactTechs[$T['PreReqTech']])) continue;
        if ($T['PreReqTech']!=4) continue;
        if ( ($FactTechs[$T['PreReqTech']]['Level']<$T['PreReqLevel'] ) ) continue;

        $Lvl = $T['PreReqLevel'];
        $pc = Proj_Costs($Lvl);
        $Rbuts[] = "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=21&t=$Turn&Hi=$Hi&Di=$Di&Sel=" . $T['id'] .
                "&Name=" . base64_encode("Research " . $T['Name'] . $Place) . "&L=$Lvl&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .
                "Research " . $T['Name'] . "; $Place; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";
      }
   
     if ($Rbuts) {
       echo "<h2>Research Supplimental Intelligence Technology</h2>";
       foreach ($Rbuts as $rb) echo $rb;
     }
    
      break;
    }
  echo "<h2><a href=ProjDisp.php?id=$Fid>Cancel</a></h2>\n";
  echo "</form>";  

  dotail();
?>
