<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");

  A_Check('GM');

  dostaffhead("List Things",["js/ProjectTools.js"]," onload=ListThingSetup(0,1,0,0)");

  global $db, $GAME,$BuildState,$ThingInstrs,$GAMEID,$ARMY,$ARMIES,$MoveNames,$MoveProps;;
  $Blue = isset($_REQUEST['Blue']);

  $Systems = Get_SystemRefs();
  $Factions = Get_Factions();
  $xtra = '';
  $Varies = Gen_Get_All_Game('Variants');
  $DesLink = (Feature('Designs')?'PlanDesign.php':'ThingPlan.php');
  $DesName = (Feature('Designs')?'Design':'Planning');


  if (isset($_REQUEST['AT'])) {
    $Sids = array_flip($Systems);
    if (isset($Sids[$_REQUEST['AT']])) {
      $Things = Get_AllThingsAt($Sids[$_REQUEST['AT']]);
      $xtra = " at " . $_REQUEST['AT'];
    } else {
      echo "<h2>No Location " .  $_REQUEST['AT'] . "</h2>";
      dotail();
    }
  } else if ($Blue) {
      $Things = Gen_Get_Cond('Things',"BluePrint<0 AND GameId=$GAMEID");
  } else {
      $Things = Get_AllThings();
  }
//  $ThingTypes = Thing_Type_Names();
  $ThingTypes = Get_ThingTypes();

  if (!$Things) {
    echo "<h2>No Things found</h2>";
    echo "<h2><a href=ThingEdit.php?ACTION=NEW>New Thing</a></h2>";
    dotail();
  }

  $ShowCats = ['All','Ships',$ARMIES,'','Chars', 'Other'];
  $Show['ThingShow'] = 0;
  $BuildCats = ['All','Plan','Building','Shakedown','Complete','Other',(BS_DELETE+1) =>'Pending'];
  $Build['BuildShow'] = 0;

  echo "<div class=floatright ><b>" . fm_radio("Show",$ShowCats,$Show,'ThingShow',' onchange=ThingListFilter()') . "<br><br>";
  echo fm_radio("Build State",$BuildCats,$Build,'BuildShow',' onchange=ThingListFilter()') . "</b></div>";

  echo "<h1>Things $xtra</h1>";
  echo "Clicking on the id takes you to GM level edit access, clickinging on Planning takes you to player level Plan access<br>\n";

  $coln = 0;
  echo "<div class=tablecont><table id=indextable border width=100% style='min-width:1400px'>\n";
  echo "<thead><tr>";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Id</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Type</a>\n";
//  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>SubType</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Level</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Health</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>System</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Location</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Name</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Whose</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Instruction</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Build State</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Speed</a>\n";
  echo "</thead><tbody>";

  foreach($Things as $T) {
    if ($T['Type'] == 0) continue;
    $tid = $T['id'];
    $Name = $T['Name'];
    if (!$Name) $Name = "Unknown Thing $tid";
    $who = $T['Whose'];
    $Ref = empty($Systems[$T['SystemId']]) ? "" : $Systems[$T['SystemId']];
    if ($T['WithinSysLoc'] == 1) {
      $Loc = "Deep Space";
    } else if (empty($T['SystemId'])) {
      $Ref = '';
      $Loc = '';
    } else {
      $N = Get_System($T['SystemId']);
      $Locs = Within_Sys_Locs($N);
      if (!isset($Locs[$T['WithinSysLoc']])) {
        if ($T['WithinSysLoc'] > 2) {
          $Loc = $T['WithinSysLoc'] . " <span class=red>CONFUSED</span>";
        } else {
          $Loc = "";
        }
      } else {
        $Loc = $Locs[$T['WithinSysLoc']];
      }
    }
//var_dump($T);
    $Props = ($ThingTypes[$T['Type']]['Properties']??0);
    if ($Props & THING_HAS_SHIPMODULES) {
      $RowClass = 'Ship';
    } else  if ($Props & THING_HAS_ARMYMODULES) {
      $RowClass = 'Army';
    } else  if ($Props & THING_HAS_GADGETS) {
      $RowClass = 'Agent';
    } else  if (($ThingTypes[$T['Type']]['Name']??'') == 'Named Character') {
      $RowClass = 'Chars';
    } else {
      $RowClass = 'Other';
    }


    $BuildClass = ($T['BuildState']<BS_EX ? $T['BuildState'] : (($T['BuildState'] == BS_DELETE)?BS_DELETE: BS_EX));

    echo "<tr class='ThingList Thing_$RowClass Thing_Build$BuildClass'><td><a href=ThingEdit.php?id=$tid>$tid</a>";

    echo "<td>" . ($ThingTypes[$T['Type']]['Name']??'Unknown');
    if ($T['Variant']) echo " ( " . $Varies[$T['Variant']]['Name'] . " )";
    echo "<td>" . $T['Level'];
    echo "<td><center>" . (($Props & THING_HAS_HEALTH)? $T['CurHealth'] . ' / ' . $T['OrigHealth'] : "-");
    if ($T['LinkId'] >= 0 || $T['LinkId'] == -2 || $T['LinkId'] == -4 || ($T['LinkId'] == -6)) {
      echo "<td><a href=ThingList.php?AT=$Ref>$Ref</a><td>$Loc";
    } else if ($T['LinkId'] == -1 || $T['LinkId'] == -3) {
      $Host = Get_Thing($T['SystemId']);
      echo "<td>On:<td>" . ($Host['Name']??'Unknown');
    } else if ($T['LinkId'] == -6) {
      echo "<td><a href=ThingList.php?AT=$Ref>$Ref</a><td>$Loc";
    } else {
      echo "<td><a href=ThingList.php?AT=$Ref>$Ref</a><td>" . $MoveNames[$T['LinkId']];
    }

    echo "<td><a href=ThingEdit.php?id=$tid>$Name</a>";
    echo "<td>" . ($T['BluePrint']<0?'Blue Print':($who? $Factions[$T['Whose']]['Name'] : ""));
    if ($BuildClass == BS_DELETE) {
      echo "<td><a href='ThingEdit.php?ACTION=DeleteNOW&id=$tid&GMList'>Delete NOW!</a>";
    } else {
      echo "<td>" . $ThingInstrs[$T['Instruction']];
    }
    echo "<td>" . ($T['BuildState']? $BuildState[$T['BuildState']] : "<a href=$DesLink?F=" . $T['Whose'] . "&id=$tid>$DesName</a>" );
    echo "<td>" . sprintf('%0.3g',$T['Speed']);

  }

  echo "</tbody></table></div onload=ListThingSetup(0,1,0,0)>\n";

  if (!$Blue) {
    echo "<h2><a href=ThingEdit.php?ACTION=NEW>New Thing</a></h2>";
  } else {
    echo "<h2><a href=ThingEdit.php?ACTION=NEWBLUE>New Blue Print</a></h2>";
  }

  dotail();
?>
