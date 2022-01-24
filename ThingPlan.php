<?php

// Plan a thing type - Ship, Army, Agent, Other

  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("PlayerLib.php");
  include_once("ProjLib.php");
  
  global $FACTION,$GAME;
  
  if (Access('GM') ) {
    A_Check('GM');
    if (isset( $_REQUEST['F'])) {
      $Fid = $_REQUEST['F'];
    } else if (isset( $_REQUEST['f'])) {
      $Fid = $_REQUEST['f'];
    }
    if ($Fid) $Faction = Get_Faction($Fid);
  } else if (Access('Player')) {
    if (!$FACTION) {
      Error_Page("Sorry you need to be a GM or a Player to access this");
    }
    $Fid = $FACTION['id'];
    $Faction = &$FACTION;
  }

  if (isset($_REQUEST['id'])) {
    $Tid = $_REQUEST['id'];
    $T = Get_Thing($Tid);
    $Exist = 1;
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
      if ($OldT['BuildState'] == 0) {
        db_delete('Things',$Tid);
        $Tid = $T['id'];
      } else {
        $T['id'] = $Tid;
        $T['Name'] = "Copy of " . $T['Name'];
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
//  echo $TT['Name'] . " " . (eval("return " . $TT['Gate'] . ";" )?"True":"false") . "<br>";
    if ($TT['Name'] && ($T['Type'] == $Ti || eval("return " . $TT['Gate'] . ";" ))) {
      $ThingTypeNames[$Ti] = $TT['Name'];
    }
  }

// var_dump($ThingTypeNames);exit;  
  echo "<h1>Plan a thing</h1>";
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
    echo fm_select($FList,null,'Design',0,' onchange=this.form.submit()'); 
    echo "<br>If it is in planning, it will open, otherwise it will copy the design.<p>\n";
    
    echo "<h2>Or design from scratch</h2>";
  }

  echo "<form method=post action=ThingPlan.php>";
  Register_Autoupdate('Thing',$Tid);
  echo fm_hidden('id',$Tid) . fm_hidden('F',$Fid);

  // Sanitise thing type names
  echo "<table border>";
  echo "<tr><td>Planning a:<td>" . fm_select($ThingTypeNames, $T,'Type');
  echo "<tr>" . fm_number('Level',$T,'Level','','min=1 max=10');
  echo "<tr>" . fm_text('Name',$T,'Name') . "<td>This is needed";
  echo "<tr>" . fm_text('Class',$T,'Class') . "<td>This is optional";

  if (Access('God')) echo "<tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea>";    
  echo "</table></form>";
  
  // type, Level
  
  // Name and class 
  
  // Modules
  
  // totals, armout, basic damage
  
  // Save for later

