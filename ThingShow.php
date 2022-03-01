<?php
// Lib of Thing related stuff
include_once("sk.php");
include_once("GetPut.php");
include_once("vendor/erusev/parsedown/Parsedown.php");
include_once("PlayerLib.php");  



function Show_Thing(&$t,$Force=0) {
  include_once("ProjLib.php");
  global $BuildState,$GAME,$GAMEID;
  global $Project_Status;
  global $ModuleCats,$ModFormulaes,$ModValues,$Fields,$Tech_Cats,$CivMil,$BuildState,$ThingInstrs,$ThingInclrs, $InstrMsg;
  $tid = $t['id'];
  
  if ($Force) {
    $GM = 0;
  } else {
    $GM = Access('GM');
  }

  $Fid = $t['Whose'];
  $ttn = Thing_Type_Names();
  $FactNames = Get_Faction_Names();
  $Fact_Colours = Get_Faction_Colours();
  $ThingProps = Thing_Type_Props();
  $tprops = $ThingProps[$t['Type']];
  $N = Get_System($t['SystemId']);
  $Syslocs = Within_Sys_Locs($N);
  $LinkTypes = Get_LinkLevels();
  
  if ($t['SystemId'] == $t['NewSystemId'] || $t['NewSystemId'] == 0) {
    $NewSyslocs = $Syslocs;
  } elseif ($t['NewSystemId']) {
    $NN = Get_System($t['NewSystemId']);
    $NewSyslocs = Within_Sys_Locs($NN);
  } else {
    $NewSyslocs = [];
  }
  $Systems = Get_SystemRefs();
  $t['MaxModules'] = Max_Modules($t);
  if  ($tprops & THING_HAS_MODULES) $t['OrigHealth'] = Calc_Health($t);

  if (($t['BuildState'] == 3) && ($tprops & THING_CAN_MOVE) && ($t['CurHealth'] > 0)) { // Complete Only
    $res =     Moves_4_Thing($t,$Force,$N);
//var_dump($res);exit;
    [$Links, $SelLinks, $SelCols ] = $res;

  } else {
    $Links = $SelLinks = $SelCols = [];
  }

//var_dump($SelLinks);exit;
  if ($Links && $t['LinkId'] && !isset($Links[$t['LinkId']]['Level'])) {
    var_dump($t); echo "<p>"; var_dump($Links); echo "<p>";
  }
  if ($Links && ($t['LinkId']) && ($ll = $Links[$t['LinkId']]['Level']) >1 && ($Who = GameFeature('LinkOwner',0)) && $Who != $t['Whose']) {
    $LOwner = Get_Faction($Who);
    echo "<h2>You are taking a <span style='color:" . $LinkTypes[$ll]['Colour'] . "'>" . $LinkTypes[$ll]['Colour'] .
         "</span> link do you need to pay " . $LOwner['Name'] . " for this?</h2>\n";
  }



  echo "Note Movement does not yet work for armies moving by ship.<p>\n";
  
  if ($t['BuildState'] == 0) echo "Note the Tech level of this will be recorded when it is built<br>";

  echo "<form method=post id=mainform enctype='multipart/form-data' action=ThingEdit.php>";
  echo "<div class=tablecont><table width=90% border class=SideTable>\n";
  Register_AutoUpdate('Thing',$tid);
  echo fm_hidden('id',$tid);
  
  if ($GM) {
    echo "<tr class=NotSide><td class=NotSide>Id:<td class=NotSide>$tid<td class=NotSide>Game<td class=NotSide>$GAMEID<td class=NotSide>" . $GAME['Name'];
    echo "<tr><td>Type:<td>" . fm_select($ttn,$t,'Type',1); 
    if ($tprops & THING_HAS_LEVELS) echo fm_number("Level",$t,'Level');
  } else {
    echo "<tr><td>Type: " . $ttn[$t['Type']];
    if ($tprops & THING_HAS_LEVELS) "<td>Level: " . $t['Level'];
  }
  echo "<tr>" . fm_text('Name',$t,'Name',2);

  echo "<td rowspan=4 colspan=4><table><tr>";
    echo fm_DragonDrop(1,'Image','Thing',$tid,$t,1,'',1,'','Thing');
  echo "</table>";
  echo "<tr>" . fm_text('Class',$t,'Class',2);
  if ($GM) {
    echo "<tr><td>Build State:" . fm_select($BuildState,$t,'BuildState'); 
    if (isset($t['BuildState']) && $t['BuildState'] <= 1) {
      echo fm_number('Build Project',$t,'ProjectId');
      if ($t['ProjectId']) {
        $Proj = Get_Project($t['ProjectId']);
//var_dump($Proj,"<br>", $Project_Status);
        echo "Status: " . $Project_Status[$Proj['Status']];
        if ($Proj['TurnStart']) echo " Start Turn: " . $Proj['TurnStart'];
        if ($Proj['TurnEnd']) echo " End Turn: " . $Proj['TurnEnd'];
      }
      
    } else {
      if (! empty($t['SystemId'])) {
        if (!isset($t['LinkId']) || !isset($SelCols[$t['LinkId']])) { 
          $t['LinkId'] = 0;
          $SelCols[0] = "white";
        }
        if (($tprops & THING_CAN_MOVE) && isset($SelCols[$t['LinkId']]) ) {
          echo "<td>Taking Link:<td>" . fm_select($SelLinks,$t,'LinkId',0," style=color:" . $SelCols[$t['LinkId']] ,'',0,$SelCols) . "Update this normally";
        }
        if ($tprops & THING_CAN_BETRANSPORTED) echo "<td>No mechanism<td>to move armies yet";  // TODO transport armies
      }
    }

    echo "<tr><td>System:<td>" . fm_select($Systems,$t,'SystemId',1);
    echo "<td>" . fm_select($Syslocs,$t,'WithinSysLoc');
    if ($tprops & THING_CAN_MOVE) {
      echo "<tr><td>New System:<td>" . fm_select($Systems,$t,'NewSystemId',1) . "This is derived data<td>" . fm_select($NewSyslocs,$t,'NewLocation');
      echo fm_number('Target Known',$t,'TargetKnown');
    }
    echo "<tr>" . fm_radio('Whose',$FactNames ,$t,'Whose','',1,'colspan=6','',$Fact_Colours,0); 
  } else {
    echo "<tr><td>Build State:<td>" . $BuildState[$t['BuildState']]; 
    if (isset($t['BuildState']) && $t['BuildState'] <= 1) {
      if (!empty($t['ProjectId'])) {
        $Proj = Get_Project($t['ProjectId']);
        echo "<tr><td>See <a href=ProjEdit.php?id=" . $t['ProjectId'] . ">Project</a>";
        echo "Status: " . $Project_Status[$Proj['Status']];
        if ($Proj['TurnStart']) echo " Start Turn: " . $Proj['TurnStart'];
        if ($Proj['TurnEnd']) echo " End Turn: " . $Proj['TurnEnd'];
      }
      
    } else {
      echo "<tr><td>Current System:<td>" . $N['Ref'] . "<td>" . $Syslocs[$t['WithinSysLoc']];

      if ($tprops & THING_CAN_MOVE) {
        switch ($t['BuildState']) {
        
        case 0: // Plan
        case 1: // Build
        case 4: // Ex thing
          break; // Can't move
        case 2: // Shakedown - In sys only
          echo "<tr><td>Where in the system should it go? " . fm_select($Syslocs,$t,'WithinSysLoc');
          break;
        case 3: //
//        var_dump($SelLinks);
//           if (empty($SelCols[$t['LinkId']])) $t['LinkId'] = 0;
           if (count($SelLinks) > 1) {
             echo "<tr><td>Taking Link:<td>" . fm_select($SelLinks,$t,'LinkId',0," style=color:" . $SelCols[$t['LinkId']] ,'',0,$SelCols);
             if ($t['LinkId'] && !strpos($SelLinks[$t['LinkId']],'?')) {
               echo "<td>To:" . fm_select($NewSyslocs,$t,'NewLocation');
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
        if (($t['BuildState'] == 0)) {
          echo "<tr><td>Where in the system is it to be built? " . fm_select($Syslocs,$t,'WithinSysLoc');
        }
      }
    }


//    if ($tprops & THING_CAN_MOVE) {
//      echo "<tr><td>New System:<td>" . fm_select($Systems,$t,'NewSystemId',1) . "This is derived data<td>" . fm_select($NewSyslocs,$t,'NewLocation');
//    }
  }

  if  ($tprops & THING_HAS_GADGETS) echo "<tr>" . fm_textarea("Gadgets",$t,'Gadgets',8,3);
  if  ($tprops & THING_HAS_LEVELS) echo "<tr>" . fm_text("Orders",$t,'Orders',2);
  echo "<tr>" . fm_textarea("Description\n(For others)",$t,'Description',8,2);
  echo "<tr>" . fm_textarea('Notes',$t,'Notes',8,2);
  echo "<tr>" . fm_textarea('Named Crew',$t,'NamedCrew',8,2);
  if ($GM) echo "<tr>" . fm_textarea('GM Notes',$t,'GM_Notes',8,2,'class=NotSide');
  echo "<tr>" . fm_textarea('History',$t,'History',8,2,'','','',($GM?'':'Readonly'));
  if ($tprops & THING_HAS_2_FACTIONS) echo "<tr>" . fm_radio('Other Faction',$FactNames ,$t,'OtherFaction','',1,'colspan=6','',$Fact_Colours,0); 
  if  ($tprops & (THING_HAS_MODULES | THING_HAS_ARMYMODULES)) {
    if ($GM) {
      echo "<tr>" . fm_number('Orig Health',$t,'OrigHealth') . fm_number('Cur Health',$t,'CurHealth');
    } else {
      echo "<tr><td>Original Health: " . $t['OrigHealth'] . ($t['BuildState']? "<td>Current Health: " . $t['CurHealth'] : "");
    }
    echo "<td>Basic Damage: " . Calc_Damage($t);
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
      echo fm_number("Max Districts",$t,'MaxDistricts');
      echo fm_number(($t['ProjHome']?"<a href=ProjHomes.php?id=" . $t['ProjHome'] . ">Project Home</a>":"Project Home"),$t,'ProjHome');

      if (!isset($t['MaxDistricts'])) $t['MaxDistricts'] = 0;
      if ($totdisc > $t['MaxDistricts']) echo "<td class=Err>TOO MANY DISTRICTS\n";
    } else {
      if ($NumDists) echo "<tr><td rowspan=" . ceil(($NumDists+4)/4) . ">Districts:";
      
      foreach ($Ds as $D) {
        $did = $D['id'];
        if (($dc++)%4 == 0)  echo "<tr>";
        echo "<td>" . $DTs[$D['Type']] . ": " . $D['Number'];
        $totdisc += $D['Number'];      
      };

      if (($dc++)%4 == 0)  echo "<tr>";
      echo "<td>Max Districts: " . $t['MaxDistricts'];
      if (($dc++)%4 == 0)  echo "<tr>"; 
      if ($t['ProjHome']) echo "<td><a href=ProjDisp.php?H=" . $t['ProjHome'] . ">Projects here</a>";
    }
  }
  if ($tprops & THING_HAS_MODULES) {
//    echo "<tr><td>Modules to be done\n";
    $MTs = Get_ModuleTypes();

//var_dump($MTs);
//    $MTNs = [];
//    foreach($DTs as $M) $MTNs[$M['id']] = $M['Name'];
    $MTNs = Get_Valid_Modules($t);
    $Ds = Get_Modules($tid);

    $NumMods = count($Ds);
    $dc=0;
    $totmodc = 0;
    $BadMods = 0;
//    $t['Sensors'] = $t['SensorLevel'] = $t['NebSensors'] = 0;
    
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
          $t['Sensors'] = $D['Number'];
          $t['SensorLevel'] = $D['Level'];
        } else if ($D['Type'] == 9) $D['NebSensors'] = $D['Number'];
      }
      echo "<tr><td>Add Module Type<td>" . fm_Select($MTNs, NULL , 'Number', 1,'',"ModuleTypeAdd-$tid");
      echo fm_number1("Max Modules",$t,'MaxModules');
      if ($tprops & THING_HAS_CIVSHIPMODS) {
        echo fm_number1("Deep Space",$t,'HasDeepSpace');
        echo fm_number1("Cargo Space",$t,'CargoSpace');
      }
      if ($totmodc > $t['MaxModules']) {
        echo "<td class=Err>TOO MANY MODULES\n";
      } elseif ($BadMods) {
        echo "<td class=Err>$BadMods INVALID MODULES\n";
      } else {
        echo "<td>Module space used: $totmodc";
      }
      echo "<td>Speed: " . sprintf('%0.3g',$t['Speed']);
    } else {
      if ($NumMods) echo "<tr><td rowspan=" . ceil(($NumMods+4)/4) . ">Modules:";
  
      $MTs = Get_ModuleTypes();
      foreach ($Ds as $D) {
//        if ($D['Number'] == 0) continue;
        $did = $D['id'];
        if (($dc++)%4 == 0)  echo "<tr>";
        echo "<td><b>" . $D['Number']. "</b> of " . (isset($MTNs[$D['Type']]) ?$MTNs[$D['Type']] : 'Unknown Modules')  . ($t['BuildState']? (" (Level " . $D['Level'] . ") ") :"") ;
                
        $CLvl = Calc_TechLevel($Fid,$D['Type']);
        if ($CLvl < $D['Level'] && $t['BuildState'] != 0 ) {
          echo ". <span class=Blue> Note you have Level: $CLvl </span>";
        }
        if (!isset($MTNs[$D['Type']])) $BadMods += $D['Number'];
        $totmodc += $D['Number'] * $MTs[$D['Type']]['SpaceUsed'];
        };

      if ($totmodc > $t['MaxModules']) {
        echo "<tr><td>Max Modules: " . $t['MaxModules'];
//      echo fm_number1("Deep Space",$t,'HasDeepSpace');
        if ($totmodc > $t['MaxModules']) {
          echo "<td class=Err>TOO MANY MODULES\n";
        } elseif ($BadMods) {
          echo "<td class=Err>$BadMods INVALID MODULES\n";
        } else {
          echo "<td>Module space used: $totmodc";
        }
      }
      echo "<td>Speed: " . sprintf('%0.3g',$t['Speed']);
    }

 // TODO 
 // Max modules, current count, what e
  }
  
  if ($GM && ($tprops & THING_HAS_CIVSHIPMODS)) {
    echo "<tr>" . fm_number('Sensors',$t,'Sensors') . fm_number('Sens Level',$t,'SensorLevel') . fm_number('Neb Sensors', $t,'NebSensors');
  }
  $SpecOrders = []; $SpecCount = 0;
  if ($t['BuildState'] == 2 || $t['BuildState'] == 3) foreach ($ThingInstrs as $i=>$Ins) {
    switch ($i) {
    case 1: // Colonise
      if ((($tprops & THING_HAS_CIVSHIPMODS) == 0 ) ) continue 2;
      if (!Get_ModulesType($tid,10)) continue 2;
      break;
    
    case 2: // Warp Home
      if ((($tprops & THING_HAS_SHIPMODULES) == 0 ) || ($t['CurHealth'] == 0) ) continue 2;
      break;
    
    case 3: // Dissasemble
      if (($tprops & THING_HAS_SHIPMODULES) == 0 ) continue 2;
      // Is there a Home here with a shipyard
      $Loc = $t['SystemId'];
      $Homes = Gen_Get_Cond('ProjectHomes', "SystemId=$Loc AND Whose=$Fid");
      foreach ($Homes as $H) {
        $Ds = Get_DistrictsH($H['id']);
        if (isset($Ds[3])) break 2; // FOund a Shipyard
      }
      continue 2;
    
    case 4: // Analyse
      continue 2; // NOt yet
      
    case 5: // Deep Space Construction
      if (!Get_ModulesType($t,3)) continue 2;
      break;
      
    case 6: // Establish Embassy
      if (!Get_ModulesType($t,22)) continue 2;
      break;
    
    default: 
      continue 2;
      
    }
    $SpecOrders[$i] = $Ins;
    $SpecCount++;
  }

  if ($SpecCount>1) { 

    
    echo "<tr>" . fm_radio('Special Instructions', $SpecOrders,$t,'Instruction','',1,' colspan=6 ','',$ThingInclrs);
    foreach($SpecOrders as $i=>$Order) {
      echo "<tr id=InstMsg$i " . ($i == $t['Instruction']?'': 'hidden') . "><td colspan=6>" . $InstrMsg[$i];
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
  Put_Thing($t);
}

?>
