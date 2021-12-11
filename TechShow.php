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
  $CTs = Get_CoreTechsByName();
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
  
  if ($FACTION) {
    $Fid = $FACTION['id'];
  } else {
    $FACTION = Get_Faction($Fid);
  }

  $Techs = Get_TechsByCore($Fid);
//var_dump($Techs); exit;

  $TechFacts = [];
  if ($Fid) {
    $TechFacts = Get_Faction_Techs($Fid);
  }
  
  $Setup = isset($_REQUEST['SETUP']);
//var_dump ($TechFacts);
//var_dump($FACTION);
 
  echo "<h2>Technologies</h2>\n";
  echo "Click on name to Toggle showing the definition and examples\n<p>";
  
//  if (Access('God')) echo "<table><tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea></table>";  
  if ($Setup) {
    Register_AutoUpdate('FactTech',$Fid);
  }
  foreach ($CTs as $CT) {
    Show_Tech($CT,$CTNs,$FACTION,$TechFacts,0,$Setup);
    
    foreach ($Techs as $T) {
//      echo "Checking "  . $T['Name'] . " Against " . $CT['Name']
      if ($T['Cat']>0 && ($T['PreReqTech'] == $CT['id'])) {
//        echo "Found " . $T['Name'] . "<br>";
        Show_Tech($T,$CTNs,$FACTION,$TechFacts,0,$Setup);
      }
    }
  }
  
  echo "<p>";

    
