<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  
  A_Check('GM');

  dostaffhead("List Things");

  global $db, $GAME,$BuildState,$ShipTypes;

  $Systems = Get_SystemRefs();
  $Factions = Get_Factions();
  
  if (isset($_REQUEST['AT'])) {
    $Sids = array_flip($Systems);
    if (isset($Sids[$_REQUEST['AT']])) {
      $Things = Get_AllThingsAt($Sids[$_REQUEST['AT']]);
      $xtra = " at " . $_REQUEST['AT'];
    } else {
      echo "<h2>No Location " .  $_REQUEST['AT'] . "</h2>";
      dotail();
    }
  } else {
    $Things = Get_AllThings();
    $xtra = '';
  }
  $ThingTypes = Thing_Type_Names();
  
  if (!$Things) {
    echo "<h2>No Things found</h2>";
    echo "<h2><a href=ThingEdit.php?ACTION=NEW>New Thing</a></h2>";
    dotail();
  }
  
  echo "<h1>Things $xtra</h1>";
  echo "Clicking on the id takes you to GM level edit access, clickinging on Planning takes you to player level Plan access<br>\n";
  
  $coln = 0;
  echo "<div class=tablecont><table id=indextable border width=100% style='min-width:1400px'>\n";
  echo "<thead><tr>";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Id</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Type</a>\n";
//  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>SubType</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Level</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>System</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Location</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Name</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Whose</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Project</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Build State</a>\n";

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
      $Loc = $Locs[$T['WithinSysLoc']];
    }
    
    echo "<tr><td><a href=ThingEdit.php?id=$tid>$tid</a>";

    echo "<td>" . $ThingTypes[$T['Type']] . /* "<td>" . $ShipTypes[$T['SubType']] .*/ "<td>" . $T['Level'];
    echo "<td><a href=ThingList.php?AT=$Ref>$Ref</a><td>$Loc";
    echo "<td><a href=ThingEdit.php?id=$tid>$Name</a>";
    echo "<td>" . ($who? $Factions[$T['Whose']]['Name'] : "");
    echo "<td>" . $T['ProjectId'];
    echo "<td>" . ($T['BuildState']? $BuildState[$T['BuildState']] : "<a href=ThingPlan.php?F=" . $T['Whose'] . "&id=$tid>Planning</a>" );

  }
      
  echo "</tbody></table></div>\n";
  
  echo "<h2><a href=ThingEdit.php?ACTION=NEW>New Thing</a></h2>";

  dotail();
?>
