<?php
// Lib of Thing related stuff
include_once("sk.php");
include_once("GetPut.php");
include_once("vendor/erusev/parsedown/Parsedown.php");
include_once("PlayerLib.php");  




function Show_Thing(&$T,$Force=0) {
  include_once("ProjLib.php");
  global $BuildState,$GAME,$GAMEID,$FACTION;
  global $Project_Status;
  global $ModuleCats,$ModFormulaes,$ModValues,$Fields,$Tech_Cats,$CivMil,$BuildState,$ThingInstrs,$ThingInclrs, $InstrMsg;
  
  $ThingInclrs = ['white','lightgreen','lightpink','lightblue','cream','bisque','#99ffcc','#b3b3ff',
                 'lightgreen','lightpink','lightblue','cream','bisque','#99ffcc','#b3b3ff',
                 'lightgreen','lightpink','lightblue','cream','bisque','#99ffcc','#b3b3ff'];

  $tid = $T['id'];
  
  if ($Force) {
    $GM = 0;
  } else {
    $GM = Access('GM');
  }

  $Fid = $T['Whose'];
  $ttn = Thing_Type_Names();
  $FactNames = Get_Faction_Names();
  $Fact_Colours = Get_Faction_Colours();
  $ThingProps = Thing_Type_Props();
  $tprops = $ThingProps[$T['Type']];
  $N = Get_System($T['SystemId']);
  $Syslocs = Within_Sys_Locs($N);
  $LinkTypes = Get_LinkLevels();
  
  if ($T['SystemId'] == $T['NewSystemId'] || $T['NewSystemId'] == 0) {
    $NewSyslocs = $Syslocs;
  } elseif ($T['NewSystemId']) {
    $NN = Get_System($T['NewSystemId']);
    $NewSyslocs = Within_Sys_Locs($NN);
  } else {
    $NewSyslocs = [];
  }
  $Systems = Get_SystemRefs();
  $T['MaxModules'] = Max_Modules($T);
  if  ($tprops & THING_HAS_MODULES) $T['OrigHealth'] = Calc_Health($T);

  if (($T['BuildState'] == 3) && ($tprops & THING_CAN_MOVE) && ($T['CurHealth'] > 0)) { // Complete Only
    $res =     Moves_4_Thing($T,$Force,$N);
//var_dump($res);exit;
    [$Links, $SelLinks, $SelCols ] = $res;

  } else {
    $Links = $SelLinks = $SelCols = [];
  }

//var_dump($SelLinks);exit;
  if ($Links && $T['LinkId'] && !isset($Links[$T['LinkId']]['Level'])) {
    var_dump($T); echo "<p>"; var_dump($Links); echo "<p>";
  }
  if ($Links && ($T['LinkId']) && ($ll = $Links[$T['LinkId']]['Level']) >1 && ($Who = GameFeature('LinkOwner',0)) && $Who != $T['Whose']) {
    $LOwner = Get_Faction($Who);
    echo "<h2>You are taking a <span style='color:" . $LinkTypes[$ll]['Colour'] . "'>" . $LinkTypes[$ll]['Colour'] .
         "</span> link do you need to pay " . $LOwner['Name'] . " for this?</h2>\n";
  }



  echo "Note Movement does not yet work for armies moving by ship.<p>\n";
  
  if ($T['BuildState'] == 0) echo "Note the Tech level of this will be recorded when it is built<br>";

  echo "<form method=post id=mainform enctype='multipart/form-data' action=ThingEdit.php>";
  echo "<div class=tablecont><table width=90% border class=SideTable>\n";
  Register_AutoUpdate('Thing',$tid);
  echo fm_hidden('id',$tid);
  
  if ($GM) {
    echo "<tr class=NotSide><td class=NotSide>Id:<td class=NotSide>$tid<td class=NotSide>Game<td class=NotSide>$GAMEID<td class=NotSide>" . $GAME['Name'];
    echo "<tr><td>Type:<td>" . fm_select($ttn,$T,'Type',1); 
    if ($tprops & THING_HAS_LEVELS) echo fm_number("Level",$T,'Level');
  } else {
    echo "<tr><td>Type: " . $ttn[$T['Type']];
    if ($tprops & THING_HAS_LEVELS) "<td>Level: " . $T['Level'];
  }
  echo "<tr>" . fm_text('Name',$T,'Name',2);

  echo "<td rowspan=4 colspan=4><table><tr>";
    echo fm_DragonDrop(1,'Image','Thing',$tid,$T,1,'',1,'','Thing');
  echo "</table>";
  echo "<tr>" . fm_text('Class',$T,'Class',2);
  if ($GM) {
    echo "<tr><td>Build State:" . fm_select($BuildState,$T,'BuildState'); 
    if (isset($T['BuildState']) && $T['BuildState'] <= 1) {
      echo fm_number('Build Project',$T,'ProjectId');
      if ($T['ProjectId']) {
        $Proj = Get_Project($T['ProjectId']);
//var_dump($Proj,"<br>", $Project_Status);
        echo "Status: " . $Project_Status[$Proj['Status']];
        if ($Proj['TurnStart']) echo " Start Turn: " . $Proj['TurnStart'];
        if ($Proj['TurnEnd']) echo " End Turn: " . $Proj['TurnEnd'];
      }
      
    } else {
      if (! empty($T['SystemId'])) {
        if (!isset($T['LinkId']) || !isset($SelCols[$T['LinkId']])) { 
          $T['LinkId'] = 0;
          $SelCols[0] = "white";
        }
        if (($tprops & THING_CAN_MOVE) && isset($SelCols[$T['LinkId']]) ) {
          echo "<td>Taking Link:<td>" . fm_select($SelLinks,$T,'LinkId',0," style=color:" . $SelCols[$T['LinkId']] ,'',0,$SelCols) . "Update this normally";
        }
        if ($tprops & THING_CAN_BETRANSPORTED) echo "<td>No mechanism<td>to move armies yet";  // TODO transport armies
      }
    }

    echo "<tr><td>System:<td>" . fm_select($Systems,$T,'SystemId',1);
    echo "<td>" . fm_select($Syslocs,$T,'WithinSysLoc');
    if ($tprops & THING_CAN_MOVE) {
      echo "<tr><td>New System:<td>" . fm_select($Systems,$T,'NewSystemId',1) . "This is derived data<td>" . fm_select($NewSyslocs,$T,'NewLocation');
      echo fm_number('Target Known',$T,'TargetKnown');
    }
    echo "<tr>" . fm_radio('Whose',$FactNames ,$T,'Whose','',1,'colspan=6','',$Fact_Colours,0); 
  } else {
    echo "<tr><td>Build State:<td>" . $BuildState[$T['BuildState']]; 
    if (isset($T['BuildState']) && $T['BuildState'] <= 1) {
      if (!empty($T['ProjectId'])) {
        $Proj = Get_Project($T['ProjectId']);
        echo "<tr><td>See <a href=ProjEdit.php?id=" . $T['ProjectId'] . ">Project</a>";
        echo "Status: " . $Project_Status[$Proj['Status']];
        if ($Proj['TurnStart']) echo " Start Turn: " . $Proj['TurnStart'];
        if ($Proj['TurnEnd']) echo " End Turn: " . $Proj['TurnEnd'];
      }
      
    } else {
      echo "<tr><td>Current System:<td>" . $N['Ref'] . "<td>" . $Syslocs[$T['WithinSysLoc']];

      if ($tprops & THING_CAN_MOVE) {
        switch ($T['BuildState']) {
        
        case 0: // Plan
        case 1: // Build
        case 4: // Ex thing
          break; // Can't move
        case 2: // Shakedown - In sys only
          echo "<tr><td>This is unable to use links, it can move within the system.<br>Where in the system should it go? " . fm_select($Syslocs,$T,'WithinSysLoc');
          break;
        case 3: //
//        var_dump($SelLinks);
//           if (empty($SelCols[$T['LinkId']])) $T['LinkId'] = 0;
           if ($T['CurHealth'] == 0 && !empty(Syslocs)) {
             echo "<tr><td>This is unable to use links, it can move within the system.<br>Where in the system should it go? " . fm_select($Syslocs,$T,'WithinSysLoc');           
           } else if (count($SelLinks) > 1) {
             echo "<tr><td>Taking Link:<td>" . fm_select($SelLinks,$T,'LinkId',0," style=color:" . $SelCols[$T['LinkId']] ,'',0,$SelCols);
             if ($T['LinkId'] && !strpos($SelLinks[$T['LinkId']],'?')) {
               echo "<td>To:" . fm_select($NewSyslocs,$T,'NewLocation');
             }
           } else {
             echo "<tr><td>Row Not Used\n";
           }
           break;
        default:
          break;
        }
      } else if ($tprops & THING_CAN_BETRANSPORTED) {
        echo "<tr><td>Transport of armies<td>not yet written\n"; // TODO move armies
      } else { //Static, can specify where before start
        if (($T['BuildState'] == 0)) {
          echo "<tr><td>Where in the system is it to be built? " . fm_select($Syslocs,$T,'WithinSysLoc');
        }
      }
    }


//    if ($tprops & THING_CAN_MOVE) {
//      echo "<tr><td>New System:<td>" . fm_select($Systems,$T,'NewSystemId',1) . "This is derived data<td>" . fm_select($NewSyslocs,$T,'NewLocation');
//    }
  }

  if  ($tprops & THING_HAS_GADGETS) echo "<tr>" . fm_textarea("Gadgets",$T,'Gadgets',8,3);
  if  ($tprops & THING_HAS_LEVELS) echo "<tr>" . fm_text("Orders",$T,'Orders',2);
  echo "<tr>" . fm_textarea("Description\n(For others)",$T,'Description',8,2);
  echo "<tr>" . fm_textarea('Notes',$T,'Notes',8,2);
  echo "<tr>" . fm_textarea('Named Crew',$T,'NamedCrew',8,2);
  if ($GM) echo "<tr>" . fm_textarea('GM Notes',$T,'GM_Notes',8,2,'class=NotSide');
  echo "<tr>" . fm_textarea('History',$T,'History',8,2,'','','',($GM?'':'Readonly'));
  if ($tprops & THING_HAS_2_FACTIONS) echo "<tr>" . fm_radio('Other Faction',$FactNames ,$T,'OtherFaction','',1,'colspan=6','',$Fact_Colours,0); 
  if  ($tprops & (THING_HAS_MODULES | THING_HAS_ARMYMODULES)) {
    if ($GM) {
      echo "<tr>" . fm_number('Orig Health',$T,'OrigHealth') . fm_number('Cur Health',$T,'CurHealth');
    } else {
      echo "<tr><td>Original Health: " . $T['OrigHealth'] . ($T['BuildState']? "<td>Current Health: " . $T['CurHealth'] : "");
    }
    echo "<td>Basic Damage: " . Calc_Damage($T);
  }
  if ($tprops & THING_HAS_DISTRICTS) {
    $DTs = Get_DistrictTypeNames();
    $Ds = Get_DistrictsT($tid);

    $NumDists = count($Ds);
    $dc=0;
    $totdisc = 0;

    if ($GM) {
      if ($NumDists) echo "<tr><td rowspan=" . ceil(($NumDists+2)/2) . ">Districts:";
      
      foreach ($Ds as $D) {
        $did = $D['id'];
        if (($dc++)%2 == 0)  echo "<tr>";
        echo "<td>" . fm_Select($DTs, $D , 'Type', 1,'',"DistrictType-$did") . fm_number1('', $D,'Number', '','',"DistrictNumber-$did");
        $totdisc += $D['Number'];      
      };


      echo "<tr><td>Add District Type<td>" . fm_Select($DTs, NULL , 'Number', 1,'',"DistrictTypeAdd-$tid");
      echo fm_number("Max Districts",$T,'MaxDistricts');
      echo fm_number(($T['ProjHome']?"<a href=ProjHomes.php?id=" . $T['ProjHome'] . ">Project Home</a>":"Project Home"),$T,'ProjHome');

      if (!isset($T['MaxDistricts'])) $T['MaxDistricts'] = 0;
      if ($totdisc > $T['MaxDistricts']) echo "<td class=Err>TOO MANY DISTRICTS\n";
    } else {
      if ($NumDists) echo "<tr><td rowspan=" . ceil(($NumDists+4)/4) . ">Districts:";
      
      foreach ($Ds as $D) {
        $did = $D['id'];
        if (($dc++)%4 == 0)  echo "<tr>";
        echo "<td>" . $DTs[$D['Type']] . ": " . $D['Number'];
        $totdisc += $D['Number'];      
      };

      if (($dc++)%4 == 0)  echo "<tr>";
      echo "<td>Max Districts: " . $T['MaxDistricts'];
      if (($dc++)%4 == 0)  echo "<tr>"; 
      if ($T['ProjHome']) echo "<td><a href=ProjDisp.php?H=" . $T['ProjHome'] . ">Projects here</a>";
    }
  }
  if ($tprops & THING_HAS_MODULES) {
//    echo "<tr><td>Modules to be done\n";
    $MTs = Get_ModuleTypes();

//var_dump($MTs);
//    $MTNs = [];
//    foreach($DTs as $M) $MTNs[$M['id']] = $M['Name'];
    $MTNs = Get_Valid_Modules($T);
    $Ds = Get_Modules($tid);

    $NumMods = count($Ds);
    $dc=0;
    $totmodc = 0;
    $BadMods = 0;
//    $T['Sensors'] = $T['SensorLevel'] = $T['NebSensors'] = 0;
    
    if ($GM) { // TODO Allow for setting module levels 
      if ($NumMods) echo "<tr><td rowspan=" . ceil(($NumMods+2)/2) . ">Modules:";
  
      foreach ($Ds as $D) {
        $did = $D['id'];
        if (($dc++)%2 == 0)  echo "<tr>";
        echo "<td>" . fm_Select($MTNs, $D , 'Type', 1,'',"ModuleType-$did")
                    . fm_number1('Level', $D,'Level', '', '',"ModuleLevel-$did") 
                    . fm_number0('', $D,'Number', '','',"ModuleNumber-$did") 
                    . "<button id=ModuleRemove-$did onclick=AutoInput('ModuleRemove-$did')>R</button>";
        if (!isset($MTNs[$D['Type']])) $BadMods += $D['Number'];
        $totmodc += $D['Number'] * $MTs[$D['Type']]['SpaceUsed'];
        
        if ($D['Type'] == 4) { 
          $T['Sensors'] = $D['Number'];
          $T['SensorLevel'] = $D['Level'];
        } else if ($D['Type'] == 9) $D['NebSensors'] = $D['Number'];
      }
      echo "<tr><td>Add Module Type<td>" . fm_Select($MTNs, NULL , 'Number', 1,'',"ModuleTypeAdd-$tid");
      echo fm_number1("Max Modules",$T,'MaxModules');
      if ($tprops & THING_HAS_CIVSHIPMODS) {
        echo fm_number1("Deep Space",$T,'HasDeepSpace');
        echo fm_number1("Cargo Space",$T,'CargoSpace');
      }
      if ($totmodc > $T['MaxModules']) {
        echo "<td class=Err>TOO MANY MODULES\n";
      } elseif ($BadMods) {
        echo "<td class=Err>$BadMods INVALID MODULES\n";
      } else {
        echo "<td>Module space used: $totmodc";
      }
      echo "<td>Speed: " . sprintf('%0.3g',$T['Speed']);
    } else {
      if ($NumMods) echo "<tr><td rowspan=" . ceil(($NumMods+4)/4) . ">Modules:";
  
      $MTs = Get_ModuleTypes();
      foreach ($Ds as $D) {
//        if ($D['Number'] == 0) continue;
        $did = $D['id'];
        if (($dc++)%4 == 0)  echo "<tr>";
        echo "<td><b>" . $D['Number']. "</b> of " . (isset($MTNs[$D['Type']]) ?$MTNs[$D['Type']] : 'Unknown Modules')  . ($T['BuildState']? (" (Level " . $D['Level'] . ") ") :"") ;
                
        $CLvl = Calc_TechLevel($Fid,$D['Type']);
        if ($CLvl < $D['Level'] && $T['BuildState'] != 0 ) {
          echo ". <span class=Blue> Note you have Level: $CLvl </span>";
        }
        if (!isset($MTNs[$D['Type']])) $BadMods += $D['Number'];
        $totmodc += $D['Number'] * $MTs[$D['Type']]['SpaceUsed'];
        };

      if ($totmodc > $T['MaxModules']) {
        echo "<tr><td>Max Modules: " . $T['MaxModules'];
//      echo fm_number1("Deep Space",$T,'HasDeepSpace');
        if ($totmodc > $T['MaxModules']) {
          echo "<td class=Err>TOO MANY MODULES\n";
        } elseif ($BadMods) {
          echo "<td class=Err>$BadMods INVALID MODULES\n";
        } else {
          echo "<td>Module space used: $totmodc";
        }
      }
      echo "<td>Speed: " . sprintf('%0.3g',$T['Speed']);
    }

 // TODO 
 // Max modules, current count, what e
  }
  
  if ($GM && ($tprops & THING_HAS_CIVSHIPMODS)) {
    echo "<tr>" . fm_number('Sensors',$T,'Sensors') . fm_number('Sens Level',$T,'SensorLevel') . fm_number('Neb Sensors', $T,'NebSensors');
  }
  $SpecOrders = []; $SpecCount = 0;
  if ($T['BuildState'] == 2 || $T['BuildState'] == 3) foreach ($ThingInstrs as $i=>$Ins) {
    switch ($ThingInstrs[$i]) {
    case 'None': // None
      break;
    

    case 'Colonise': // Colonise
      if ((($tprops & THING_HAS_CIVSHIPMODS) == 0 ) ) continue 2;
      if (!Get_ModulesType($tid,10)) continue 2;
      break;
    
    case 'Voluntary Warp Home': // Warp Home
      if ((($tprops & THING_HAS_SHIPMODULES) == 0 ) || ($T['CurHealth'] == 0) ) continue 2;
      break;
    
    case 'Decommision': // Dissasemble
      if (($tprops & THING_HAS_SHIPMODULES) == 0 ) continue 2;
      // Is there a Home here with a shipyard
      $Loc = $T['SystemId'];
      $Homes = Gen_Get_Cond('ProjectHomes', "SystemId=$Loc AND Whose=$Fid");
      foreach ($Homes as $H) {
        $Ds = Get_DistrictsH($H['id']);
        if (isset($Ds[3])) break 2; // FOund a Shipyard
      }
      if (isset($N['id']) && Get_Things_Cond($Fid,"Type=11 AND SystemId=" . $N['id'] . " AND BuildState=3")) break 2; // Orbital Shipyard     
      continue 2;
    
    case 'Analyse Anomoly': // Analyse
      continue 2; // NOt yet
      
    case 'Establish Embassy': // Establish Embassy
      if (!Get_ModulesType($tid,22)) continue 2;  // Check if have Embassy & at homeworld
      if (Get_Things_Cond($Fid,"Type=17 AND SystemId=" . $N['id'] . " AND BuildState=3")) continue 2; // Already have one
      $Facts = Get_Factions();
      foreach ($Facts as $F) {
        if ($F['id'] == $T['Whose'] || $F['HomeWorld'] == 0) continue;
        $W = Get_World($F['HomeWorld']);
        $H = Get_ProjectHome($W['Home']);
        if ($H['SystemId'] == $T['SystemId']) {
          $T['OtherFaction'] = $F['id'];
          break 2;
        }
      }
      continue 2;
    
    case 'Make Outpost': // Make Outpost
      if ((!Get_ModulesType($tid,3) && ($N['Control'] != $Fid))) continue 2;
      if (Get_Things_Cond($Fid,"Type=6 AND SystemId=" . $N['id'] . " AND BuildState=3")) continue 2; // Already have one
      break;
      
    case 'Make Asteroid Mine':
    case 'Make Advanced Asteroid Mine':
      if (!Get_ModulesType($tid,3) || !Has_Tech($Fid,'Asteroid Mining')) continue 2;
      if (Get_Things_Cond(0,"Type=8 AND SystemId=" . $N['id'] . " AND BuildState=3")) continue 2; // Already have one
      $Ps = Get_Planets($N['id']);
      foreach ($Ps as $P) if ($P['Type'] == 3) break;
      continue 2; // No field 
    
    case 'Make Minefield':
      if (!Get_ModulesType($tid,3) || !Has_Tech($Fid,'Mine Layers')) continue 2;
      break;
      
    case 'Make Orbital Repair Yard':
      if (!Get_ModulesType($tid,3) || !Has_Tech($Fid,'Orbital Repair Yards')) continue 2;
      if (Get_Things_Cond(0,"Type=11 OR Type=AND SystemId=" . $N['id'] . " AND BuildState=3")) continue 2; // Already have one
      break;

    case 'Build Space Station':
      if (!Get_ModulesType($tid,3) || !Has_Tech($Fid,'Space Stations')) continue 2;
      if (Get_Things_Cond(0,"Type=7 OR Type=AND SystemId=" . $N['id'] . " AND BuildState=3")) continue 2; // Already have one
      break;

    case 'Expand Space Station':
      if (!Get_ModulesType($tid,3) || !Has_Tech($Fid,'Space Stations')) continue 2;
      if (!(Get_Things_Cond($Fid,"Type=7 OR Type=AND SystemId=" . $N['id'] . " AND BuildState=3"))) continue 2; // Don't have one
      break;

    case 'Make Deep Space Sensor':
      if (!Get_ModulesType($tid,3) || !Has_Tech($Fid,'Deep Space Sensors')) continue 2;
      break;

    case 'Build Stargate':
      if (!Get_ModulesType($tid,3) || !Has_Tech($Fid,'Stargate Construction')) continue 2;
      break;

     default: 
      continue 2;
      
    }
    $SpecOrders[$i] = $Ins;
    $SpecCount++;
  }

/*
$ThingInstrs = ['None','Colonise','Voluntary Warp Home','Decommision','Analyse Anomoly','Establish Embassy','Make Outpost','Make Asteroid Mine','Make Minefield',
                'Make Orbital Repair Yard','Build Space Station','Expand Space Station','Make Deep Space Sensor','Make Advanced Asteroid Mine','Build Stargate',
                'DSC Special'];
*/
  if ($SpecCount>1) { 
    $PTs=Get_ProjectTypes();
    $ProgShow = $Cost = $Acts = 0;
    echo "<tr>" . fm_radio('Special Instructions', $SpecOrders,$T,'Instruction','',1,' colspan=6 ','',$ThingInclrs);// . " under development don't use yet";
    switch ($ThingInstrs[$T['Instruction']]) {
    case 'None': // None
      break;

    case 'Colonise': // Colonise
      $PTs = Get_PlanetTypes();
      $Ps = Get_Planets($N['id']);
      $Hab_dome = Has_Tech($Fid,'Habitation Domes');
      $HabPs = [];
      foreach($Ps as $P) {
        if (!$PTs[$P['Type']]['Hospitable']) continue;
        if (Get_DistrictsP($P['id'])) continue; // Someone already there
        if ($P['Type'] == $FACTION['Biosphere']) {
          $HabPs[$P['id']] = [$P['Name'],$P['Type'],3];
        }
        if ($P['Type'] == 4 ) {
          if (!$Hab_dome) continue;
          $HabPs[$P['id']] = [$P['Name'],$P['Type'],10];
        } else {
          $HabPs[$P['id']] = [$P['Name'],$P['Type'],6];
        }
      }
      
      if (empty($HabPs)) {
        echo "<tr><td><td colspan=6 class=Err>There are no colonisable planets here\n";
        break;
      }
      $NumPs = count($HabPs);
      if ($NumPs == 1) {
        foreach ($HabPs as $Plid=>$data) {
          $P = $Ps[$Plid];
          $Acts = $data[2];
          echo "<tr><td><td colspan=6>Colonising: " . $P['Name'] . " a " . $PTs[$P['Type']]['Name'] . ($PTs[$P['Type']]['Append']?' Planet':'') .
             " will take " . $data[2] . " actions"; // TODO Moons
          $T['Spare1'] = $Plid;

          break;
        }
      } else {
        echo "<tr><td><td colspan=6>";
        $Cols = $Plans = [];
        $i = 1;
        foreach ($HabPs as $Plid=>$data) {
          $P = $Ps[$Plid];
          $Plans[$Plid] = $P['Name'] . " a " . $PTs[$P['Type']]['Name'] . ($PTs[$P['Type']]['Append']?'Planet':'') . " will take " . $data[2] . " actions"; // TODO Moons
          $Cols[$Plid] = $ThingInclrs[$i++];
          
        }
//    echo "<tr>" . fm_radio('Special Instructions', $SpecOrders,$T,'Instruction','',1,' colspan=6 ','',$ThingInclrs) . " under development don't use yet";
        echo fm_radio('Colonising',$Plans,$T,'Spare1','',0,'','',$Cols);
      }
      if ($T['Spare1']) $Acts = $HabPs[$T['Spare1']][2];
      $PrimeMods = [];
      $DTs = Get_DistrictTypes();
      foreach ($DTs as $D) if ($D['Props'] &1) $PrimeMods[$D['id']] = $D['Name'];
      echo "<br>District to Establish:" . fm_select($PrimeMods,$T,'Dist1');
      if (Get_ModulesType($tid,27)) echo "<br>Second District (must be different):" .fm_select($PrimeMods,$T,'Dist2');
      $ProgShow = 1;
      break;
    
    case 'Voluntary Warp Home': // Warp Home
    case 'Decommision': // Dissasemble
      break;
      
    case 'Establish Embassy': // Establish Embassy
      echo "<br>" . fm_text0("Name of Embassy",$T,'MakeName') . " with " . $FactNames[$T['OtherFaction']];
      break;
      
    case 'Make Outpost': // Make Outpost
      echo "<br>" . fm_text0("Name of Outpost",$T,'MakeName');
      $Acts = $PTs[22]['CompTarget'];
      $ProgShow = 2;      
      break;

    case 'Analyse Anomoly': // Analyse
      break;
      
    case 'Make Asteroid Mine':
      echo "<br>" . fm_text0("Name of Asteroid Mine",$T,'MakeName');
      $Acts = $PTs[23]['CompTarget'];
      $ProgShow = 2;
      break;

    case 'Make Advanced Asteroid Mine':
      echo "<br>" . fm_text0("Name of Advanced Asteroid Mine",$T,'MakeName');
      $Acts = $PTs[29]['CompTarget'];
      $ProgShow = 2;
      break;

    case 'Make Orbital Repair Yard':
      echo "<br>" . fm_text0("Name of Orbital Repair Yard",$T,'MakeName');
      $Acts = $PTs[25]['CompTarget'];
      $ProgShow = 2;
      break;

    case 'Make Minefield':
      echo "<tr><td><td colspan=6>Make sure you move to the location within the system you want to mine";
      echo "<br>" . fm_text0("Name of Minefield",$T,'MakeName');
      $Acts = $PTs[24]['CompTarget'];
      $ProgShow = 1;
      break;

    case 'Build Space Station':
      $PrimeMods = [];
      $DTs = Get_DistrictTypes();
      foreach ($DTs as $D) if ($D['Props'] &1) $PrimeMods[$D['id']] = $D['Name'];
      echo "<tr><td><td colspan=6>" . (($GM || $T['Progress'] == 0)? fm_number0('What size:',$T,'Dist1'): ("<td>Size: " . $T['Dist1']));
      echo " First District:" . fm_select($PrimeMods,$T,'Dist2',1);
      echo "<br>" . fm_text0("Name of Space Station",$T,'MakeName');
      $Acts = - $PTs[26]['CompTarget']*$T['Dist1'];
      $ProgShow = 1;      
      break;
 
    case 'Expand Space Station':
      echo "<tr><td clospan=6>" . (($GM || $T['Progress'] == 0)? fm_number0('By how much:',$T,'Dist1'): ("<td>Adding Size: " . $T['Dist1']));
      $ProgShow = 1;
      if ($T['Dist1']) {
        $Acts = - $PTs[27]['CompTarget']*$T['Dist1'];
      }
      break;

    case 'Make Deep Space Sensor':
      echo "<br>" . fm_text0("Name of Deep Space Sensor",$T,'MakeName');
      $Acts = $PTs[28]['CompTarget'];
      break;

    case 'Build Stargate':
      $ProgShow = 1;
      $Acts = $PTs[33]['CompTarget'];

      // Needs a lot of work
      break;

    default: 
      break;
    }
    if ($ProgShow) {
      $T['ActionsNeeded'] = $Acts;
      $T['InstCost'] = $Cost = $Acts*10;
      if (Has_Trait($Fid,'Built for Construction and Logistics') && ($Cost>200)) {
        $T['InstCost'] = $Cost = (200+($Cost-200)/2);
      }
      if ($ProgShow == 2) echo "<tr><td><td colspan=6>";
      if ($GM) {
        echo fm_number0('Progress',$T,'Progress') . " / " . fm_number0('Actions Needed',$T,'ActionsNeeded');
      } else {
        echo " Progress " . $T['Progress'] . ' / ' . $T['ActionsNeeded'];
      }
      if ($Cost && ($T['Progress'] == 0)) echo " - Will cost " . Credit() . fm_number0('',$T,'InstCost') . " this turn";
      if ($T['Instruction'] != $T['CurInst']) {
        $T['CurInst'] = $T['Instruction'];
        $T['Progress'] = 0;
      }
    }

  }
  if (Access('God')) echo "<tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea>";  
  echo "</table></div>\n";
  if ($GM) echo "<input type=submit name=ACTION value='GM Refit'> <input type=submit name=ACTION value='Destroy Thing (Leave debris)'>" .
       " <input type=submit name=ACTION value='Remove Thing (No debris)'>  <input type=submit name=ACTION value='Warp Out'>\n";
  if ($GM || empty($Fid)) {
    if (Access('God')) echo "<h2><a href=ThingList.php>Back to Thing list</a> &nbsp; <input type=submit name=ACTION value=Duplicate></h2>";
  } else {
    echo "<h2><a href=PThingList.php?id=$Fid>Back to Thing list</a></h2>";
  }
  Put_Thing($T);
}

?>
