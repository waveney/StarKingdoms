<?php

// Plan a thing type - Ship, Army, Agent, Other
//include_once("Profile.php");
//prof_flag("Start");

  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("PlayerLib.php");
  include_once("ProjLib.php");

  global $FACTION,$GAME,$ARMY;
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


  $Blue = Feature('BluePrints');
  $Slots = Feature('ModuleSlots');
  $SPad = ($Slots?'<td>':'');

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
    $T = ['Whose'=>$Fid, 'BuildState'=>BS_PLANNING, 'Type'=> 0, 'Level'=>1];
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
 //      if (Access('God')) prof_print();
       dotail();


    case 'COPY':
      if (isset($_REQUEST['Create'])) { // Named chars only (I think)
        $T = ['Whose' => $Fid, 'Type'=>$_REQUEST['Create']];
        $Tid = Put_Thing($T);
      } else {
        $Design = '';
        $mtch = [];
        $Copy = 1;
        if (!empty($_REQUEST['Design'])) {
          $Design = $_REQUEST['Design'];
        } else {
          foreach ($_REQUEST as $L=>$R) {
            if (!$R) continue;
            if (preg_match('/^(.*)Design\d*/',$L,$mtch) && $R) {
              $Design = $R;
              $DesCat = $mtch[1];
              if ($DesCat == 'Plan') $Copy = 0;
              break;
            }
          }
          if (empty($Design)) {
            echo "<h1 class=Err>Design not found - Tell Richard what you did</h1>";
            dotail();
          }
        }
        $OldT = Get_Thing($Design);
  //var_dump($OldT);

        $T = $OldT;
        if ((empty($OldT['BuildState'])) && ($OldT['BluePrint'] >= 0) && ($Copy==0)) {
          db_delete('Things',$Tid);
          $Tid = $T['id'];
        } else {
          $T = Thing_Duplicate($Design);
          $T['Whose'] = $Fid;
          $Tid = $T['id'];
          $T['BuildState'] = $T['NewSystemId'] = $T['SystemId'] = 0;
          Put_Thing($T);
        }
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
//  prof_flag("b4head");

  dostaffhead("Plan a thing",["js/ProjectTools.js", "js/dropzone.js","css/dropzone.css" ]);

 //var_dump($T);

  $ThingTypes = Get_ThingTypes();
  $ThingTypeNames = [];
  foreach ($ThingTypes as $Ti=>$TT) {
    if ($TT['Name'] && ($T['Type'] == $Ti ||
         (is_numeric($TT['Gate'])? ($TT['Gate'] >1? (($res = Has_Tech($Fid,$TT['Gate']))): ($TT['Gate'] ==1)) :
           eval("return " . $TT['Gate'] . ";" )))) {
      $ThingTypeNames[$Ti] = $TT['Name'];
    }
  }
  $ThingProps = Thing_Type_Props();
  $tprops = (empty($T['Type'])? 0: $ThingProps[$T['Type']]??0);
  $tprops2 = ($ThingTypes[$T['Type']]['Prop2']??0);
  $Valid = $CanMake = 1;
  $ResC =0;
  if ($T['Whose'] && $T['Whose'] == $FACTION['id']) {
    RefitRepair($T,0);
    Calc_Health($T);
    Calc_Damage($T,$ResC);
    Calc_Scanners($T);
  }
// var_dump($ThingTypeNames);exit;
//  prof_flag('Heading');
  echo "<h1>Plan a Thing</h1>";
  echo "This is to design or modify a ship/$ARMY etc to later build if you want to.";

  if (($GM = Access('GM'))) echo "<h2><a href=ThingEdit.php?id=$Tid>GM: Thing Editing</a></h2>\n";

  $MaxOver = ($Faction['TurnState']?1:0);

  if ($Exist == 0) {
    echo "<form method=post action=ThingPlan.php?ACTION=COPY>";
    echo fm_hidden('id',$Tid) . fm_hidden('F',$Fid);
    $Things = Get_Things($Fid);
    $FList = [];
    $PlanList = [];
    $CopyList = [];
    $NeedOr = 0;

    if ($Things) {
      foreach ($Things as $Tid=>$TT) {
//        var_dump($TT);
        $Type = $TT['Type'];
        if (empty($ThingTypeNames[$Type])) continue;
        $Name = $TT['Name'] . " a level " . $TT['Level'] . " " . $ThingTypeNames[$Type];
        if ($TT['BuildState']==0) {
          $PlanList[$Type][$Tid] = $Name;
        }
        if ($ThingProps[$Type] & (THING_HAS_ARMYMODULES | THING_HAS_SHIPMODULES)) $CopyList[$Type][$Tid] = $Name;
      }

      if ($PlanList) {
        echo "<h2>Continue Planning these:</h2>";
        echo "<table>";
        foreach ($PlanList as $Typ=>$List) {
          echo "<tr><td width=150>" . $ThingTypeNames[$Typ] . "<td>" , fm_select($List,null,"PlanDesign$Typ",1,' onchange=this.form.submit()') . "<p>";
        }
        echo "</table><p>";
        $NeedOr = 1;
      }

      if ($CopyList) {
        echo "<h2>" . ($NeedOr?'OR ':'') . "A copy of one of these:</h2>";
        echo "<table>";
        foreach ($CopyList as $Typ=>$List) {
          echo "<tr><td width=150>" . $ThingTypeNames[$Typ] . "<td>" , fm_select($List,null,"CopyDesign$Typ",1,' onchange=this.form.submit()') . "<p>";
        }
        echo "</table><p>";
        $NeedOr = 1;
      }
    }

    if ($Blue) {
      echo "<h2>" . ($NeedOr?'OR ':'') . "Start with a Blue Print:</h2>";
      $NeedOr = 1;
      $LimA = Has_Tech($Fid,'Military Theory');
      $LimS = Has_Tech($Fid,'Ship Construction');
      $LimSat = Has_Tech($Fid,"Satellite Defences");
      $BPs = BluePrintList(max($LimA,$LimS+$LimSat)+$MaxOver,'',0);
//var_dump($BPs);
      $Direct = [];
      echo "<table>";
      foreach($ThingTypeNames as $TT=>$Name) {
        if (!empty($BPs[$TT])) {
          echo "<tr><td width=150>A $Name: <td>" . fm_select($BPs[$TT],null,"BlueDesign$TT",1,' onchange=this.form.submit()') . "<p>";
        } else if (($ThingTypes[$TT]['Properties'] & THING_HAS_BLUEPRINTS) ==0) {
          $Direct[] = $TT;
        }
      }
      echo "</table><p>";
    } else {
      echo "<h2>" . ($NeedOr?'OR ':'') . "Start with Classes:</h2>";
      $NeedOr = 1;

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
      echo fm_select($CList,null,'ClassDesign',1,' onchange=this.form.submit()');

      echo "<h2>OR design from scratch</h2>";
    }
    if ($Direct) {
      echo "<h2>" . ($NeedOr?'OR ':'') . " Plan A:</h2>";
      $NeedOr = 1;
      foreach ($Direct as $di) {
          echo "<button type=submit name=Create value=$di>" .  $ThingTypeNames[$di] . "</button><br>";
        }
      }
    dotail();

  }

  echo "<form method=post action=ThingPlan.php>";
  Register_Autoupdate('Thing',$Tid); //'ModuleCheck');
  echo fm_hidden('id',$Tid) . fm_hidden('F',$Fid);
  $T['MaxModules'] = Max_Modules($T);
  // Sanitise thing type names
  echo "<table border>";
  echo "<tr><td>Planning a:<td>" . (Feature('BluePrints')?($ThingTypeNames[ $T['Type']]??'Unknown') :fm_select($ThingTypeNames, $T,'Type'));
  if ($T['Type']) {

    $Limit = 0;
    if ($tprops & THING_HAS_LEVELS) {
      if ($tprops & THING_HAS_SHIPMODULES) {
        $Limit = Has_Tech($Fid,'Ship Construction');
      } else if ($tprops & THING_HAS_ARMYMODULES) {
        $Limit = Has_Tech($Fid,Feature('MilTech'));
      } else if ($tprops & THING_HAS_GADGETS) {
        $Limit = Has_Tech($Fid,'Intelligence Operations');
      }
    }

    if ($tprops & THING_HAS_LEVELS) {
      $MaxLvl = $GM?1000000:(Feature('MaxLevel',$ThingTypes[$T['Type']]['MaxLvl']));
      if ($tprops & THING_HAS_BLUEPRINTS ) {
        echo "<tr><td>Level:<td>" . $T['Level'];
      } else {
        echo "<tr>" . fm_number('Level',$T,'Level','','min=1 max=$MaxLvl');
      }
      if ($T['Level'] > $Limit) {
        $CanMake = 0;
        echo "<td><td class=Err>Note this is higher level than you can currently make";
      }
    }
    echo "<tr>" . fm_text('Name',$T,'Name') . "$SPad<td" . (empty($T['Name'])? " class=Err" : "") . ">This is needed";
    if (empty($T['Name'])) $Valid = 0;
    if ($tprops & THING_HAS_BLUEPRINTS ) {
      echo "<tr><td>Class:<td>" . $T['Class'];
    } else {
      echo "<tr>" . fm_text('Class',$T,'Class') . "$SPad<td>This is optional";
    }
    echo "<tr>" . fm_number('Priority',$T,'Priority') . "$SPad<td colspan=3>This is optional - higher numbers appear at top of thing lists";
    echo "<tr><td rowspan=4 colspan=" . ($Slots?3:2) . "><table><tr>";
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
      $MMs = Get_Modules($Tid);

      $dc=0;
      $totmodc = 0;
      $BadMods = 0;
      $FlexM = 0;
      $IsBlue =  ($T['BluePrint']>0);
      if ($IsBlue) {
        $BP = Get_Thing($T['BluePrint']);
        $BMs = Get_Modules($T['BluePrint']);
        $Flex = Get_ModulesType($T['BluePrint'],'Flexible');
        $FlexM = $Flex['Number']??0;
      }

// var_dump($MTNs, $Ms, $MMs);
      $ZZnull = [];
      echo "<tr><th><b>Module type</b>" .($IsBlue?'<th><b>Orig Number</b>':'') . ($FlexM?"<th><b>Number</b>":'') .
           ($Slots?"<th><b>Slots per Module</b>":'') . "<th><b>Comments</b>";
      $Elvl = $Mlvl = $Flvl = 1;
      $Engines = 0;
      $Weapons = 0;
      $Mobile = 0;
      $FluxStab = 0;

      // Count Used slots
      $TotUsed = 0;
      foreach ($MTs as $Mti=>$Mtype) {
        if (isset($MMs[$Mti])) {
          if ($MTs[$Mti]['Name'] == 'Flexible') continue;
          $TotUsed += $MMs[$Mti]['Number'];
        }
      }

      $SpareFlex = $T['MaxModules'] - $TotUsed;
      echo fm_hidden('MaxSlots',$T['MaxModules']);

      foreach ($MTs as $Mti=>$Mtype) {
        if (isset($MTNs[$Mti])) {
          $MT = $MTNs[$Mti];
//    foreach ($MTNs as $Mti=> $MT) {
          echo "<tr><td>$MT:";

          if ($MT == 'Flexible') {
            echo "<td id=FlexSpace>$FlexM<td>Space left: <span id=UnusedFlex " . ($SpareFlex<0?'class=Err':'') . ">$SpareFlex</span>";
            if ($SpareFlex != 0) $Valid = 0;
            continue;
          }

          if (isset($MMs[$Mti])) {
            $min=0;
            if ($IsBlue) {
              $min = ($BMs[$Mti]['Number']??0);
              $max = $min+$FlexM;
              echo "<td>$min";
            }
            if ($FlexM) echo fm_number1('',$MMs[$Mti],'Number','',"min=$min max=$max onchange=CheckModSpace()","ModuleNumber-" . $MMs[$Mti]['id']);
            $totmodc += $MMs[$Mti]['Number'] * ($Slots?$MTs[$Mti]['SpaceUsed']:1);
          } else  {
            if ($IsBlue) echo '<td>0';
            if ($FlexM) {
              echo fm_number1('',$ZZnull,'Number','',"min=0 max=$FlexM  onchange=CheckModSpace()","ModuleAddType-$Mti");
            } else {
              echo "<td>" . ($MMs[$Mti]['Number']??0);
            }
          }
          if ($Slots) echo "<td>" . $Mtype['SpaceUsed'];
        } else if (isset($MMs[$Mti]) && $MMs[$Mti]['Number']>0) {
          echo "<tr><td>" . $Mtype['Name'];
          if ($FlexM) echo "<td>0";
          echo fm_number1('',$MMs[$Mti],'Number','','min=0',"ModuleNumber-" . $MMs[$Mti]['id']);
          if ($Slots) echo "<td>" . $Mtype['SpaceUsed'];
          echo "<td><span class=Err>This module is not allowed on this type of thing</span>\n";
          echo fm_hidden('Mid',$MMs[$Mti]['id']);
          echo "<input type=submit name=ACTION value='Remove Module'>";
          $Valid = 0;
          $totmodc += $MMs[$Mti]['Number'] * ($Slots?$MTs[$Mti]['SpaceUsed']:1);
        }

      }
      if (Has_Tech($Fid,'Cret-Chath Engineering') && ($tprops & (THING_HAS_SHIPMODULES | THING_HAS_ARMYMODULES ))) {
        echo "<tr><td>" . fm_checkflagbox('Use Cret-Chath',$T,'BuildFlags',BUILD_FLAG1,'','',1) . "<td>Needed: " . $T['Level'] .
             "<td>Construction will fail if not available at start.";
      }

      $ModNames = NamesList($MTs);
      $NamesMod = array_flip($ModNames);

      $LvlMod = 0;
      if (Has_Trait($T['Whose'],'Ebonsteel Manufacturing')) $LvlMod = 1;

      if ($MMs[$Mti = $NamesMod['Sublight Engines']]??0) {
        $Elvl = Calc_TechLevel($T['Whose'],$Mti) +$LvlMod;
        $Engines = $MMs[$Mti]['Number'];
      }
      if ($MMs[$Mti = $NamesMod['Flux Stabilisers']]??0) {
        $Flvl = Calc_TechLevel($T['Whose'],$Mti) +$LvlMod;
        $FluxStab = $MMs[$Mti]['Number'];
      }
      if ($MMs[$Mti = $NamesMod['Suborbital Transports']]??0) {
        $Mlvl = Calc_TechLevel($T['Whose'],$Mti) +$LvlMod;
        $Mobile = $MMs[$Mti]['Number'];
      }

      if (($tprops & THING_HAS_ARMYMODULES) && Has_Tech($Fid,'Giant Kaiju')) {
        $Elvl = $Flvl = $Mlvl = $T['Level'];
      }


      echo "<tr><td><td><td><tr><td>Modules slots used:";
      echo fm_hidden('HighestModule',$Mti);

      echo "<td id=CurrentModules" . ($totmodc > $T['MaxModules'] ? " class=Err":"") . ">$totmodc\n";
      if ($totmodc > $T['MaxModules'] ) $Valid = 0;
      [$T['OrigHealth'],$T['ShieldPoints']] = Calc_Health($T);
      echo "<tr><td>Health/Hull<td>" . $T['OrigHealth'] . "$SPad<td colspan=3>At current Tech Levels";
      if ($T['ShieldPoints']) echo "<tr><td>Shields<td>" . $T['ShieldPoints'] . "$SPad<td colspan=3>At current Tech Levels";
      $ResC = 0;
      [$BaseDam,$ToHit] = Calc_Damage($T, $ResC);
      if ($tprops & (THING_HAS_ARMYMODULES | THING_HAS_MILSHIPMODS )) {
         echo "<tr><td>Basic Damage<td>$BaseDam$SPad<td colspan=3>At current Tech Levels.  Before special weapons etc";
      }
      if (((($tprops & THING_CAN_MOVE) != 0) && (($tprops & THING_HAS_SHIPMODULES) != 0)) || ($tprops2 & THING_HAS_SPEED)) {
        $T['Speed'] = ceil($Engines*$Elvl/$T['Level']);
        echo "<tr><td>Speed:<td>" . sprintf('%0.3g',ceil($T['Speed'])) . "$SPad<td colspan=3>At current Tech Levels";
        $T['Stability'] = ceil($FluxStab*$Flvl/$T['Level']);
        echo "<tr><td>Stability:<td>" . sprintf('%0.3g',ceil($T['Stability'])) . "$SPad<td colspan=3>At current Tech Levels";
      } else if ((($tprops & THING_CAN_MOVE) != 0) && (($tprops & THING_HAS_ARMYMODULES) != 0)) {
        $T['Mobility'] = ceil($Mobile*$Mlvl/$T['Level']);
        echo "<tr><td>Mobility:<td>" . sprintf('%0.3g',ceil($T['Mobility'])) . "$SPad<td colspan=3>At current Tech Levels";
        $T['Stability'] = 1;
        echo "<tr><td>Stability:<td>" . sprintf('%0.3g',ceil($T['Stability'])) . "$SPad<td colspan=3>At current Tech Levels";
      }
      if (($CS = ($MMs[$NamesMod['Cargo Space']]['Number']??0))) echo "<tr><td>Cargo Capacity:<td>$CS$SPad<td colspan=3>At current Tech Levels";

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
//    if (Access('God')) prof_print();
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
  echo "<h2><a href=PThingList.php>Back to List of Things</a></h2>\n";
//  if (Access('God')) prof_print();
  dotail();
  // type, Level

  // Name and class

  // Modules

  // totals, armout, basic damage

  // Save for later

