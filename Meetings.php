<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("PlayerLib.php");
  include_once("SystemLib.php");
  include_once("ProjLib.php");
  include_once("HomesLib.php");
    
  A_Check('GM');

/* Get all systems and Factions

  Go through each Home - record systems 
  
  Go through each Things - record systems

  For each system with more than 1 faction - report (Embassy?) - simple list and details button - details == WWhatCanIC  
*/

  dostaffhead("Meetups");
  
  $Systems = Get_Systems();
  $Facts = Get_Factions();
  
  $Homes = Get_ProjectHomes();
  $TTypes = Get_ThingTypes();
  
  $TurnP = '';
  if (isset($_REQUEST['TurnP'])) $TurnP = "&TurnP=1";
  
  foreach($Homes as $H) {
    switch ($H['ThingType']) {
    case 1: // Planet
      $P = Get_Planet($H['ThingId']);
      $Sid = $P['SystemId'];
      break;
    case 2: // Moon
      $M = Get_Moon($H['ThingId']);
      $P = Get_Planet($M['PlanetId']);
      $Sid = $P['SystemId'];
      break;
    case 3:// Thing - do later
      continue 2;
    }
    $Sys[$Sid][$H['Whose']] = 1024;
  }
  
//  echo "Checked Homes<p>";
  
  $Things = Get_AllThings();
  foreach ($Things as $T){
    if ($T['BuildState'] <2 || $T['BuildState'] >3) continue; // Don't exist
    $Sid = $T['SystemId'];
    $Eyes = $TTypes[$T['Type']]['Eyes'];
    if (isset($Sys[$Sid][$T['Whose']])) {
      $Sys[$Sid][$T['Whose']] |= $Eyes;
    } else {
      $Sys[$Sid][$T['Whose']] = $Eyes;
    }
  }
  
  
  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
    case 'Check':
      $Sid = $_REQUEST['S'];
      $N = Get_System($Sid);
      SeeInSystem($Sid,31,1,0,-1);
      
 //     foreach($Facts as $Fid=>$F) {      
 //       if ($
      break;
    }
  }
  echo "<h1>Checking</h1>";

//  echo "Checked Things<p>";
  
  foreach ($Systems as $N) {
    $Sid = $N['id'];
    $NumF = 0;
    $Fs = [];
    foreach($Facts as $Fid=>$F) {
      if (isset($Sys[$Sid][$Fid])) {
        $NumF++;
        $Fs[] = $F;
      }
    }
    if ($NumF > 1) {
      echo "System: <a href=Meetings.php?ACTION=Check&S=$Sid$TurnP>" . $N['Ref'] . "</a> has ";
      foreach($Fs as $F) echo $F['Name'] . " , ";
      echo "<br>";
    }   
  }
  
  echo "<P>Scan Finished<p>";
  
  if ($TurnP) echo "<h2><a href=TurnActions.php?ACTION=StageDone&S=36>Back To Turn Processing</a></h2>";
  
  dotail();
  
?>
