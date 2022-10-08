<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("ProjLib.php");
  include_once("HomesLib.php");
   
  $Fid = 0;
  $xtra = '';
  if (Access('Player')) {
    if (!isset($FACTION)) {
      if (!Access('GM') ) Error_Page("Sorry you need to be a GM or a Player to access this");
    } else {
      $Fid = $FACTION['id'];
      $Faction = &$FACTION;
    }
  } 
  if ($GM = Access('GM') ) {
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
  A_Check('Player');  
//  CheckFaction('WorldList',$Fid);
    
  dostaffhead("List of Worlds and Colonies",["js/ProjectTools.js"]);
  
  $Worlds = Get_Worlds($Fid);
  $PlanetTypes = Get_PlanetTypes();
  $TTypes = Get_ThingTypes();
  $Facts = Get_Factions();
  
  $Conflict = '';
  if (isset($_REQUEST['CONFLICT'])) $Conflict = 1;
  echo "<h1>Worlds $xtra</h1>";
  Register_AutoUpdate('Worlds',0);
  echo "Click on the name to see districts, change the relative importance<p>\n";
  
  $coln = 0;
  echo "<div class=tablecont><table id=indextable border width=100% style='min-width:1400px'>\n";
  echo "<thead><tr>";
  if ($GM) echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Id</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Type</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Name</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Where</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Minerals</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Current<br>Economy</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Devastation</a>\n";
  if ($GM) echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Economy<br>Modifier</a>\n";
  if ($GM) echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Whose</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Priority<br>Importance</a>\n";
  if ($GM) echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Conflict?</a>\n";
  if ($GM) echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Blockade?</a>\n";
  echo "</thead><tbody>";
  
  foreach ($Facts as $F) {
    if (!empty($F['HomeWorld'])) {
      if (isset($Worlds[$F['HomeWorld']])) {
        $Worlds[$F['HomeWorld']]['HomeOf'] = $F['id'];
      }
    }
  }

  foreach ($Worlds as $W) {

    echo "<tr>";
    if ($GM) echo "<td><a href=WorldEdit.php?id=" . $W['id'] . ">" . $W['id'] . "</a>";
    switch ($W['ThingType']) {
      case 1: //Planet
        $P = Get_Planet($W['ThingId']);
        $type = $PlanetTypes[$P['Type']]['Name'];
        if ($PlanetTypes[$P['Type']]['Append']) $type .= " Planet";
        $Name = $P['Name'];
        $Sys = Get_System($P['SystemId']);
        $Fid = ($P['Control'] ? $P['Control'] : $Sys['Control']);
        break;
        
      case 2: /// Moon
        $M = Get_Moon($W['ThingId']);
        $type = $PlanetTypes[$M['Type']]['Name'];
        if ($PlanetTypes[$M['Type']]['Append']) $type .= " Moon";
        $Name = $M['Name'];
        $P = Get_Planet($M['PlanetId']);
        $Sys = Get_System($P['SystemId']);
        $Fid = ($M['Control'] ? $M['Control'] : ($P['Control']? $P['Control'] :$Sys['Control']));
        break;
    
      case 3: // Thing
        $T = Get_Thing($W['ThingId']);
        $type = $TTypes[$T['Type']]['Name'];
        $Name = $T['Name'];
        $Fid = $T['Whose'];
        $Sys = Get_System($T['SystemId']);
        break;
    }
    
    $H = Get_ProjectHome($W['Home']);
    if (!isset($H['Economy'])) {
      echo "<h2 clss=Err>World #" . $W['id'] . " has no project home</h2>";
      continue;
    }
    
    $Recalc = Recalc_Economic_Rating($H,$W,$Fid);
    
    if ($Recalc != $H['Economy']) {
      echo "ECONOMY ERROR - SHOULD BE $Recalc <br>";
      var_dump($H,$W,$Fid);
    }
        
    echo "<td>$type<td><a href=WorldEdit.php?id=" . $W['id'] . ">" . (empty($Name)? "Nameless": $Name) . "</a><td>" . (empty($Sys['Ref'])?"":$Sys['Ref'])  .
         "<td>" . $W['Minerals'] . "<td>" . $H['Economy'] . "<td>" . $H['Devastation'];
    if ($GM) { 
      echo "<td>" . $H['EconomyFactor'] . "<td>" . $Facts[$W['FactionId']]['Name'];
      if (isset($W['HomeOf'])) echo " <b>(Home)</b>";
    }
    echo "<td>" . $W['RelOrder'] . "\n";
    if ($GM) echo "<td>" . fm_checkbox("Conflict?", $W, 'Conflict','',"Conflict:99:" . $W['id']);
    if ($GM) echo "<td>" . fm_checkbox("Blockade?", $W, 'Blockade','',"Blockade:99:" . $W['id']);
  }
  echo "</table></div>\n";
  
  
  if ($Conflict) echo "<h2><a href=TurnActions.php?ACTION=StageDone&Stage=DevastationSelection&S=45>Back To Turn Processing</a></h2>";
  
  dotail();
?>

