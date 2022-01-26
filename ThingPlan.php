<?php

// Plan a thing type - Ship, Army, Agent, Other

  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("PlayerLib.php");
  include_once("ProjLib.php");
  
  global $FACTION,$GAME;
  
  if (Access('Player')) {
    if (!$FACTION) {
      Error_Page("Sorry you need to be a GM or a Player to access this");
    }
    $Fid = $FACTION['id'];
    $Faction = &$FACTION;
  } else if (Access('GM') ) {
    A_Check('GM');
    if (isset( $_REQUEST['F'])) {
      $Fid = $_REQUEST['F'];
    } else if (isset( $_REQUEST['f'])) {
      $Fid = $_REQUEST['f'];
    }
    if (isset($Fid)) $Faction = Get_Faction($Fid);
  }

//var_dump($FACTION); exit;

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
    case 'COPY':
      $OldT = Get_Thing($_REQUEST['Design']);
      $T = $OldT;      
      if (empty($OldT['BuildState'])) {
        db_delete('Things',$Tid);
        $Tid = $T['id'];
      } else {
        $T['id'] = $Tid;
        $T['Name'] = "Copy of " . $T['Name'];
        $T['BuildState'] = $T['NewSystemId'] = $T['SystemId'] = 0;
        Put_Thing($T);
      }
      $Exist = 1; 
    }
  }
  
  dostaffhead("Plan a thing",["js/ProjectTools.js"]);
 
// var_dump($T);  
 
  $ThingTypes = Get_ThingTypes();
  $ThingTypeNames = [];
  foreach ($ThingTypes as $Ti=>$TT) {
    if ($TT['Name'] && ($T['Type'] == $Ti || eval("return " . $TT['Gate'] . ";" ))) {
      $ThingTypeNames[$Ti] = $TT['Name'];
    }
  }
  $ThingProps = Thing_Type_Props();
  $tprops = (empty($T['Type'])? 0: $ThingProps[$T['Type']]);

// var_dump($ThingTypeNames);exit;  
  echo "<h1>Plan a Thing</h1>";
  echo "This is to design or modify a ship/army/agent etc to later build if you want to.";
  
  if ($Exist == 0) {
    echo "<form method=post action=ThingPlan.php?ACTION=COPY>";
    echo fm_hidden('id',$Tid) . fm_hidden('F',$Fid);
    echo "<h2>Either start with:</h2>";
    $Things = Get_Things($Fid);
    $FList = [];
    foreach ($Things as $TT) {
      if (!empty($ThingTypeNames[$TT['Type']])) $FList[$TT['id']] = $TT['Name'] . " a level " . $TT['Level'] . " " . $ThingTypeNames[$TT['Type']];
    }
    echo fm_select($FList,null,'Design',1,' onchange=this.form.submit()'); 
    echo "<br>If it is in planning, it will open, otherwise it will copy the design.<p>\n";
    
    echo "<h2>Or design from scratch</h2>";
  }

  echo "<form method=post action=ThingPlan.php>";
  Register_Autoupdate('Thing',$Tid); //'ModuleCheck');
  echo fm_hidden('id',$Tid) . fm_hidden('F',$Fid);
  $T['MaxModules'] = Max_Modules($T);
  // Sanitise thing type names
  echo "<table border>";
  echo "<tr><td>Planning a:<td>" . fm_select($ThingTypeNames, $T,'Type');
  echo "<tr>" . fm_number('Level',$T,'Level','','min=1 max=10');
  echo "<tr>" . fm_text('Name',$T,'Name') . "<td>This is needed";
  echo "<tr>" . fm_text('Class',$T,'Class') . "<td>This is optional";  
  if ($tprops & THING_HAS_MODULES) {
  echo "<tr><td>Maximum Modules<td id=MaxModules>" . $T['MaxModules'];
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
    foreach ($MTs as $Mti=>$Mtype) {
      if (isset($MTNs[$Mti])) {
        $MT = $MTNs[$Mti];
//    foreach ($MTNs as $Mti=> $MT) {
        if (isset($MMs[$Mti])) {
          echo "<tr>" . fm_number($MT,$MMs[$Mti],'Number','','',"ModuleNumber-" . $MMs[$Mti]['id']);
          $totmodc += $MMs[$Mti]['Number'] * $MTs[$Mti]['SpaceUsed'];
        } else  {
          echo "<tr>" . fm_number($MT,$ZZnull,'Number','','',"ModuleAddType-$Mti");
        }
      } else if (isset($MMs[$Mti])) {
        echo "<tr>" . fm_number($Mtype['Name'],$MMs[$Mti],'Number','','',"ModuleNumber-" . $MMs[$Mti]['id']);
        echo "<td class=Err>This module is not allowed here\n";
        $totmodc += $MMs[$Mti]['Number'] * $MTs[$Mti]['SpaceUsed'];
      }
    }

    echo "<tr><td>Total Modules:";
    fm_hidden('HighestModule',$Mti);
    echo "<td id=CurrentModules" . ($totmodc > $T['MaxModules'] ? " class=ERR":"") . ">$totmodc\n";
    $T['OrigHealth'] = Calc_Health($T);
    echo "<tr><td>Health/Hull<td>" . $T['OrigHealth'];
    $BaseDam = Calc_Damage($T);
    if ($tprops & (THING_HAS_ARMYMODULES | THING_HAS_MILSHIPMODS )) echo "<tr><td>Basic Damage<td>$BaseDam<td>Before special weapons etc";
  }

  if (Access('God')) echo "<tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea>";    
  echo "</table></form>";
  
  echo "<h2>Once planned, go to where you want to make it and select an appropriate project</h2>";
  echo "You can customise by adding crew names, notes and gadgets by selecting the thing while it is being made or used later.<p>\n"
  
  // type, Level
  
  // Name and class 
  
  // Modules
  
  // totals, armout, basic damage
  
  // Save for later

?>
