<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
    
  global $FACTION;
  
  if (Access('GM') ) {
    A_Check('GM');
  } else if (Access('Player')) {
    if (!$FACTION) {
      Error_Page("Sorry you need to be a GM or a Player to access this");
    }
  }

  dostaffhead("Technologies");
  $CTs = Get_CoreTechs();
  $CTNs = [];
  $CTNs[0] = '';
  foreach ($CTs as $TT) $CTNs[$TT['id']] = $TT['Name'];

    
  if (isset($_REQUEST['f'])) {
    $Fid = $_REQUEST['f'];
  } else   if (isset($_REQUEST['F'])) {
    $Fid = $_REQUEST['F'];
  } else {
    $Fid = 0;
  }
  
  if ($FACTION) $Fid = $FACTION['id'];

  $Techs = Get_Techs($Fid);
  $TechFacts = [];
  if ($Fid) {
    $TechFacts = Get_Faction_Techs($Fid);
  }
  
  echo "<h2>Technologies</h2>\n";
  echo "Click on name to expand definition\n<p>";
  
  foreach ($Techs as $T) {
    Show_Tech($T,$CTNs,$FACTION,$TechFacts,0);
  }
  
  echo "<p>";

    
