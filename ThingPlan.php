<?php

// Plan a thing type - Ship, Army, Agent, Other

  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("PlayerLib.php");
  include_once("ProjLib.php");
  
  global $FACTION,$GAME;
  A_Check('Player');    
  $Fid = 0;
  if (Access('Player')) {
    if (!$FACTION) {
      if (!Access('GM')) {
        Error_Page("Sorry you need to be a GM or a Player to access this");
      } else {
      }
    } else {
      $Fid = $FACTION['id'];
      $Faction = &$FACTION;
    }
  }
  if ($Fid == 0 && Access('GM') ) {
    A_Check('GM');
    if (isset( $_REQUEST['F'])) {
      $Fid = $_REQUEST['F'];
    } else if (isset( $_REQUEST['f'])) {
      $Fid = $_REQUEST['f'];
    }
    if (isset($Fid)) $Faction = Get_Faction($Fid);
  }

//var_dump($_REQUEST);

  if (isset($_REQUEST['id'])) {
    $Tid = $_REQUEST['id'];
    $T = Get_Thing($Tid);
    $Exist = 1;
    if (empty($Fid)) {
      $Fid = $T['Whose'];
      $Faction = Get_Faction($Fid);
    }
  } else {
    $T = ['Whose'=>$Fid, 'BuildState'=>0, 'Type'=> 0, 'Level'=>1];
    $Tid = Put_Thing($T);
    $Exist = 0;
  }
  
  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
    case 'Delete':
    
       dostaffhead("Delete Plan");
       $tid = $_REQUEST['id'];
       $Discs = Get_DistrictsT($tid);
       if ($Discs) {
         foreach ($Discs as $D) {
           db_delete('Districts',$D['id']);
         }
       }
       $Mods = Get_DistrictsT($tid);
       if ($Mods) {
         foreach ($Mods as $M) {
           db_delete('Modules',$M['id']);
         }
       }

       db_delete('Things',$tid);

       echo "<h1>Deleted</h1>";
       echo "<h2><a href=PThingList.php>Back to Thing list</a></h2>";
       dotail();
    
    
    case 'COPY':
      if (empty($_REQUEST['Design'])) {
        if (empty($_REQUEST['CDesign'])) {
          echo "<h1 class=Err>Design not found - Tell Richard what you did</h1>";
          dotail();
        }
        $_REQUEST['Design'] = $_REQUEST['CDesign'];
      }
      $OldT = Get_Thing($_REQUEST['Design']);
//var_dump($OldT);
      $T = $OldT;      
      if (empty($OldT['BuildState'])) {
        db_delete('Things',$Tid);
        $Tid = $T['id'];
      } else {
        $T = Thing_Duplicate($_REQUEST['Design']);
        $Tid = $T['id'];
        $T['BuildState'] = $T['NewSystemId'] = $T['SystemId'] = 0;
        Put_Thing($T);
      }
      $Exist = 1; 
      break;
      
    case 'Remove Module' :
      $Tid = $_REQUEST['id'];
      $Mid = $_REQUEST['Mid'];
      db_delete('Modules',$Mid);     
      break;
    }
  }
  
  dostaffhead("Plan a thing",["js/ProjectTools.js", "js/dropzone.js","css/dropzone.css" ]);
 
 //var_dump($T);  
 
  $ThingTypes = Get_ThingTypes();
  $ThingTypeNames = [''];
  foreach ($ThingTypes as $Ti=>$TT) {
    if ($TT['Name'] && ($T['Type'] == $Ti || (is_numeric($TT['Gate'])? ($TT['Gate'] >0? (Has_Tech($Fid,$TT['Gate'])): false) : eval("return " . $TT['Gate'] . ";" )))) {
      $ThingTypeNames[$Ti] = $TT['Name'];
    }
  }
  $ThingProps = Thing_Type_Props();
  $tprops = (empty($T['Type'])? 0: $ThingProps[$T['Type']]);
  $Valid = $CanMake = 1;
  $ResC =0;
  RefitRepair($T,0);
  Calc_Health($T);
  Calc_Damage($T,$ResC);
  Calc_Scanners($T);

// var_dump($ThingTypeNames);exit;  
  echo "<h1>Plan a Thing</h1>";
  echo "This is to design or modify a ship/army/agent etc to later build if you want to.";

  if (($GM = Access('GM'))) echo "<h2><a href=ThingEdit.php?id=$Tid>GM: Thing Editing</a></h2>\n";

  if ($Exist == 0) {
    echo "<form method=post action=ThingPlan.php?ACTION=COPY>";
    echo fm_hidden('id',$Tid) . fm_hidden('F',$Fid);
    echo "<h2>Either start with Things:</h2>";
    $Things = Get_Things($Fid);
    $FList = [];
    foreach ($Things as $TT) {
      if (!empty($ThingTypeNames[$TT['Type']])) $FList[$TT['id']] = $TT['Name'] . " a level " . $TT['Level'] . " " . $ThingTypeNames[$TT['Type']];
    }
    echo fm_select($FList,null,'Design',1,' onchange=this.form.submit()'); 
    echo "<br>If it is in planning, it will be selected, otherwise it will copy the design.<p>\n";

    echo "<h2>OR start with Classes:</h2>";

    $CList = [];
    $Classes = [];
    foreach ($Things as $TT) {
      if (!empty($ThingTypeNames[$TT['Type']]) && !empty($TT['Class'])) {
        if (empty($Classes[$TT['Class']])) {
          $CList[$TT['id']] = $TT['Class'] . " a level " . $TT['Level'] . " class of " . $ThingTypeNames[$TT['Type']];
          $Classes[$TT['Class']] = 1;
        }
      }
    }
    echo fm_select($CList,null,'CDesign',1,' onchange=this.form.submit()'); 
    echo "<br>If it is in planning, it will be selected, otherwise it will copy the design.<p>\n";
    
    echo "<h2>OR design from scratch</h2>";
  }

  echo "<form method=post action=ThingPlan.php>";
  Register_Autoupdate('Thing',$Tid); //'ModuleCheck');
  echo fm_hidden('id',$Tid) . fm_hidden('F',$Fid);
  $T['MaxModules'] = Max_Modules($T);
  // Sanitise thing type names
  echo "<table border>";
  echo "<tr><td>Planning a:<td>" . fm_select($ThingTypeNames, $T,'Type');
  if ($T['Type']) {
  
    $Limit = 0;
    if ($tprops & THING_HAS_LEVELS) {
      if ($tprops & THING_HAS_SHIPMODULES) {
        $Limit = Has_Tech($Fid,'Ship Construction');
      } else if ($tprops & THING_HAS_ARMYMODULES) {
        $Limit = Has_Tech($Fid,'Military Organisation');
      } else if ($tprops & THING_HAS_GADGETS) {
        $Limit = Has_Tech($Fid,'Intelligence Operations');
      }
    }
  
    if ($tprops & THING_HAS_LEVELS) {
      $MaxLvl = ($GM?1000000:$ThingTypes[$T['Type']]['MaxLvl']);
      echo "<tr>" . fm_number('Level',$T,'Level','','min=1 max=$MaxLvl');
      if ($T['Level'] > $Limit) {
        $CanMake = 0;
        echo "<td><td class=Err>Note this is higher level than you can currently make";
      }
    }
    echo "<tr>" . fm_text('Name',$T,'Name') . "<td><td" . (empty($T['Name'])? " class=Err" : "") . ">This is needed";
    if (empty($T['Name'])) $Valid = 0;
    echo "<tr>" . fm_text('Class',$T,'Class') . "<td><td>This is optional";
    echo "<tr>" . fm_number('Priority',$T,'Priority') . "<td><td>This is optional - higher numbers appear at top of thing lists";
    echo "<tr><td rowspan=4 colspan=3><table><tr>";
    echo fm_DragonDrop(1,'Image','Thing',$Tid,$T,1,'',1,'','Thing');
    echo "</table><td>This is optional";
    echo "<tr><tr><tr>";   
    
    if ($tprops & THING_HAS_MODULES) {
    echo "<tr><td>Maximum Module Slots<td id=MaxModules>" . $T['MaxModules'];
      $MTs = Get_ModuleTypes();

//var_dump($MTs);
//    $MTNs = [];
//    foreach($DTs as $M) $MTNs[$M['id']] = $M['Name'];
      $MTNs = Get_Valid_Modules($T);
      $Ms = Get_Modules($Tid);
    
      $MMs = [];
      foreach ($Ms as $MM) $MMs[$MM['Type']] = $MM;

      $NumMods = count($Ms);
      $dc=0;
      $totmodc = 0;
      $BadMods = 0;

// var_dump($MTNs, $Ms, $MMs);
      $ZZnull = []; 
      $MTs = Get_ModuleTypes();
      echo "<tr><th><b>Module type</b><th><b>Number</b><th><b>Slots per Module</b><th><b>Comments</b>";
      $Elvl = 1;
      $Engines = 0;
      $Weapons = 0;

      foreach ($MTs as $Mti=>$Mtype) {
        if (isset($MTNs[$Mti])) {
          $MT = $MTNs[$Mti];
//    foreach ($MTNs as $Mti=> $MT) {
          if (isset($MMs[$Mti])) {
            echo "<tr>" . fm_number($MT,$MMs[$Mti],'Number','','min=0',"ModuleNumber-" . $MMs[$Mti]['id']);
            $totmodc += $MMs[$Mti]['Number'] * $MTs[$Mti]['SpaceUsed'];
          } else  {
            echo "<tr>" . fm_number($MT,$ZZnull,'Number','','min=0',"ModuleAddType-$Mti");
          }
          echo "<td>" . $Mtype['SpaceUsed'];
        } else if (isset($MMs[$Mti]) && $MMs[$Mti]['Number']>0) {
          echo "<tr>" . fm_number($Mtype['Name'],$MMs[$Mti],'Number','','min=0',"ModuleNumber-" . $MMs[$Mti]['id']);
          echo "<td>" . $Mtype['SpaceUsed'];
          echo "<td><span class=Err>This module is not allowed on this type of thing</span>\n";
          echo fm_hidden('Mid',$MMs[$Mti]['id']);
          echo "<input type=submit name=ACTION value='Remove Module'>";
          $Valid = 0;
          $totmodc += $MMs[$Mti]['Number'] * $MTs[$Mti]['SpaceUsed'];
        }
        
         if ($Mti == 5) { // Engines
           $Elvl = Calc_TechLevel($T['Whose'],$Mti);
           if (isset($MMs[$Mti]['Number'])) $Engines = $MMs[$Mti]['Number'];
         }
      }

      echo "<tr><td><td><td><tr><td>Modules slots used:";
      fm_hidden('HighestModule',$Mti);
      echo "<td id=CurrentModules" . ($totmodc > $T['MaxModules'] ? " class=ERR":"") . ">$totmodc\n";
      if ($totmodc > $T['MaxModules'] ) $Valid = 0;
      [$T['OrigHealth'],$T['ShieldPoints']] = Calc_Health($T);
      echo "<tr><td>Health/Hull<td>" . $T['OrigHealth'] . "<td><td>At current Tech Levels";
      if ($T['ShieldPoints']) echo "<tr><td>Shields<td>" . $T['ShieldPoints'] . "<td><td>At current Tech Levels";
      $ResC = 0;
      $BaseDam = Calc_Damage($T, $ResC);
      if ($tprops & (THING_HAS_ARMYMODULES | THING_HAS_MILSHIPMODS )) echo "<tr><td>Basic Damage<td>$BaseDam<td><td>At current Tech Levels.  Before special weapons etc";
      if ((($tprops & THING_CAN_MOVE) != 0) && (($tprops & THING_HAS_SHIPMODULES) != 0)) {
        $T['Speed'] = $Engines*$Elvl/$T['Level'] +1;
        echo "<tr><td>Speed:<td>" . sprintf('%0.3g',$T['Speed']) . "<td><td>At current Tech Levels";
      if ($T['CargoSpace']) echo "<tr><td>Cargo Capacity:<td>" . $T['CargoSpace'] . "<td><td>At current Tech Levels";
      } 
    }
    
    if ($tprops & THING_CAN_BE_CREATED) {
      // Offer location based on Sys/within  OR onboard thing Y
    }

//  if (!$Valid) echo "<tr><td class=Err>Warning:<td class=Err>This is not yet valid\n";
    if (isset($T['DesignValid']) && $T['DesignValid'] != $Valid) {
      $T['DesignValid'] = $Valid;
    }
  } else {
    echo "</table></form>";
    dotail();
  }

  if (Access('God')) echo "<tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea>";    
  echo "</table>";
  Put_Thing($T);
  if ($Valid) {
    if ($tprops & THING_CAN_BE_CREATED) {
      echo "<h2><a href=ThingEdit.php?ACTION=CREATE&id=$Tid>Create</a></h2>";
    } else {
      if (isset($_REQUEST['Validate'])) {
        if ($CanMake) {
          echo "<h2 class=Green>Design is valid</h2>";
        } else {
          echo "<h2 class=Green>Design is valid, but can't currently be made</h2>";       
        }
        if (($tprops & THING_HAS_MODULES) && ($totmodc < $T['MaxModules'] )) {
          echo "<h2>Though it can have " . ( $T['MaxModules'] - $totmodc). " more modules</h2>\n";
        }
      }
      echo "<input type=submit name=Validate value=Validate>\n";
      echo "<input type=submit name=ACTION value=Delete>\n";
      if ($CanMake) echo "<h2>Once planned, go to where you want to make it and select an appropriate project</h2>";
      echo "You can customise by adding crew names, an images, notes and gadgets by selecting the thing while it is being made or used later.<p>\n";
    }
  } else {
    echo "<input type=submit name=Validate value=Validate>\n";
    echo "<input type=submit name=ACTION value=Delete>\n";
    echo "<h2 class=Err>Warning.  If you try and make an Invalid thing. The action will fail</h2>\n";
  }
  echo "</form>";
  dotail();
  // type, Level
  
  // Name and class 
  
  // Modules
  
  // totals, armout, basic damage
  
  // Save for later

?>
