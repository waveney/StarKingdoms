<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("ProjLib.php");
  include_once("HomesLib.php");
   
  $Fid = 0;
  $xtra = '';
  if (Access('Player')) {
    if (!$FACTION) {
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
  
  dostaffhead("List of Worlds and Colonies",["js/ProjectTools.js"]);
  
  $Worlds = Get_Worlds($Fid);
  $PlanetTypes = Get_PlanetTypes();
  $TTypes = Get_ThingTypes();
  $Facts = Get_Factions();
  
  echo "<h1>Worlds $xtra</h1>";
  echo "Click on the name to see districts, change the relative importance<p>\n";
  
  $coln = 0;
  echo "<div class=tablecont><table id=indextable border width=100% style='min-width:1400px'>\n";
  echo "<thead><tr>";
  if ($GM) echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Id</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Type</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Name</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Minerals</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Current<br>Economy</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Devastation</a>\n";
  if ($GM) echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Economy<br>Modifier</a>\n";
  if ($GM) echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Whose</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Relative<br>Importance</a>\n";

  echo "</thead><tbody>";

  foreach ($Worlds as $W) {
    echo "<tr>";
    if ($GM) echo "<td><a href=WorldEdit.php?id=" . $W['id'] . ">" . $W['id'] . "</a>";
    switch ($W['ThingType']) {
      case 1: //Planet
        $P = Get_Planet($W['ThingId']);
        $type = $PlanetTypes[$P['Type']]['Name'];
        if ($PlanetTypes[$P['Type']]['Append']) $type .= " Planet";
        $Name = $P['Name'];
        break;
        
      case 2: /// Moon
        $M = Get_Moon($W['ThingId']);
        $type = $PlanetTypes[$M['Type']]['Name'];
        if ($PlanetTypes[$M['Type']]['Append']) $type .= " Moon";
        $Name = $M['Name'];
        break;
    
      case 3: // Thing
        $T = Get_Thing($W['ThingId']);
        $type = $TTypes[$T['Type']]['Name'];
        $Name = $T['Name'];
        break;
    }
    
    $H = Get_ProjectHome($W['Home']);
    
    
    echo "<td>$type<td><a href=WorldEdit.php?id=" . $W['id'] . ">$Name</a><td>" . $W['Minerals'] . "<td>" . $H['Economy'] . "<td>" . $H['Devastation'];
    if ($GM) echo "<td>" . $H['EconomyFactor'] . "<td>" . $Facts[$W['FactionId']]['Name'];
    echo "<td>" . $W['RelOrder'] . "\n";
  }
  echo "</table></div>\n";
  
  dotail();
?>

