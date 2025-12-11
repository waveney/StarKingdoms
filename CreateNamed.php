<?php

// Plan a thing type - Ship, Army, Agent, Other
//include_once("Profile.php");
//prof_flag("Start");

  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("PlayerLib.php");
  include_once("ProjLib.php");

  global $FACTION,$GAME,$ARMY,$GAMEID;
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
    if (isset( $_REQUEST['F'])) {
      $Fid = $_REQUEST['F'];
    } else if (isset( $_REQUEST['f'])) {
      $Fid = $_REQUEST['f'];
    }
    if (isset($Fid)) $FACTION = $Faction = Get_Faction($Fid);
  }
//  prof_flag("Fid");

  $ThingTypes = Get_ThingTypes();
  $NamedChar = ListNames($ThingTypes)['Named Character'];

//  var_dump($Fid,$FACTION);

  if (isset($_REQUEST['id'])) {
    $Tid = $_REQUEST['id'];
    $T = Get_Thing($Tid);
    $Exist = 1;
    if (empty($Fid) && Access('GM')) {
      $Fid = $T['Whose'];
      $FACTION = $Faction = Get_Faction($Fid);
    }
  } else {
    $T = ['Whose'=>$Fid, 'BuildState'=>BS_NOT, 'Type'=> $NamedChar, 'Level'=>1];
    $Tid = Put_Thing($T);
    $Exist = 0;
  }
// var_dump($_REQUEST);
  if (!$FACTION)  Error_Page("Sorry you need a Faction to access this");
  //  var_dump($Fid);
//  prof_flag("b4 action");

  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
    case 'Delete':

       dostaffhead("Delete Plan");
       $tid = $_REQUEST['id'];

       db_delete('Things',$tid);

       echo "<h1>Deleted</h1>";
       echo "<h2><a href=PThingList.php>Back to Thing list</a></h2>";
 //      if (Access('God')) prof_print();
       dotail();

    }
  }
//  prof_flag("b4head");

  dostaffhead("Create Named Characters",["js/ProjectTools.js", "js/dropzone.js","css/dropzone.css" ]);

 //var_dump($T);

  $ThingTypes = Get_ThingTypes();
  $NamedChar = ListNames($ThingTypes)['Named Character'];
  $ThingProps = Thing_Type_Props();
  $Valid = $CanMake = 1;
  $InValid = '';
  $ResC =0;

 // var_dump($ThingTypeNames);exit;
//  prof_flag('Heading');
  echo "<h1>Create a Named Character</h1>";

  $MaxOver = ($Faction['TurnState']?1:0);

  echo "<form method=post action=CreateNamed.php >";
  Register_Autoupdate('Thing',$Tid);
  echo fm_hidden('id',$Tid) . fm_hidden('F',$Fid);
  echo "<table border>";
  echo "<tr>" . fm_text('Name',$T,'Name') . "<td" . (empty($T['Name'])? " class=Err" : "") . ">This is needed";
  if (empty($T['Name'])) {
    $Valid = 0;
    $InValid .= "No Name, ";
  } else if (preg_match('/^Copy (\#\d* )?of /',$T['Name'])) {
    $Valid = 0;
    $InValid .= "No Original Name, ";
  }
  echo "<tr>" . fm_text('Class',$T,'Class') . "<td>This is optional";
  echo "<tr>" . fm_number('Priority',$T,'Priority') . "<td colspan=3>This is optional - higher numbers appear at top of thing lists";
  echo "<tr><td rowspan=4 colspan=3><table><tr>";
  echo fm_DragonDrop(1,'Image','Thing',$Tid,$T,1,'',1,'','Thing');
  echo "</table><td>This is optional";

  if (isset($T['DesignValid']) && $T['DesignValid'] != $Valid) {
    $T['DesignValid'] = $Valid;
  }

  if (Access('God')) echo "<tr><tr><tr><tr><tr><tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea>";
  echo "</table>";
  Put_Thing($T);
  echo "<h2><input type=submit formaction=ThingEdit.php?ACTION=CREATE&id=$Tid value=Create><br>";
  echo "<input formnovalidate type=submit name=ACTION value=Delete></h2>\n";
  echo "</form>";
  echo "<h2><a href=PThingList.php>Back to List of Things</a></h2>\n";
//  if (Access('God')) prof_print();
  dotail();
