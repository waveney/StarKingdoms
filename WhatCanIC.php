<?php
  include_once("sk.php");
  global $FACTION,$GAMEID,$USER,$GAME;
  include_once("GetPut.php");
  include_once("PlayerLib.php");
  include_once("ThingLib.php");

  A_Check('Player');
  
  $GM = Access('GM');

  dostaffhead("What can I see");
  if (isset($FACTION)) {
    $Fid = $FACTION['id'];
  } else if ($GM) {
    if (isset($_REQUEST['id'])) {
      $Fid = $_REQUEST['id'];
    } else {
      $Fid = 0;
      echo "<h2>Note you are here without a faction...</h2>\n";
    }
  }
  

  
  echo "<div class=floatright><h2>Older Turns =&gt;";
  for($turn=1; $turn <= $GAME['Turn']; $turn++) {
    if (file_exists("Turns/$GAMEID/$turn/CouldC$Fid.html")) echo " <a href=WhatCanIC.php?Turn=$turn>$turn</a>";
  }
  echo "</h2></div>";
  
  if (isset($_REQUEST['Turn'])) {
    $Turn = $_REQUEST['Turn'];
    echo "<h1>What Could I See on Turn $Turn?</h1>";
    $html = file_get_contents("Turns/$GAMEID/$Turn/CouldC$Fid.html");
    echo $html;
  } else {  
    echo "<h1>What Can I See Now?</h1>";
    echo WhatCanBeSeenBy($Fid);
  }
  
/*  
  
  $MyThings = Get_Things($Fid);
  $MyHomes = Get_ProjectHomes($Fid);
  $ThingTypes = Get_ThingTypes();
  $SRefs = Get_SystemRefs();
  
  $Factions = Get_Factions();
  
  $Places = [];
  $Hosts = [];
  
  foreach ($MyThings as $T) {
    if ($T['BuildState'] < 2 || $T['BuildState']> 3) continue; // Ignore things not in use
    $Sid = $T['SystemId'];
    if ($T['LinkId'] == -1 || $T['LinkId'] == -3) {
      $Hosts[$T['SystemId']][] = $T['id']; // On board something
      continue;
 //    if ($Sid <= 0 || $T['LinkId'] <0 ) continue;
    } else if ($Sid == 0) continue; // Not Anywhere

 //echo "Adding " .$T['Name'] . " " .$T['id'] . " " . $T['SystemId'] . "<br>";
    $Eyes = EyesInSystem($Fid,$Sid);
    $Places[$Sid] = (empty($Places[$Sid])? $Eyes : ($Places[$Sid] | $Eyes));
  }

  echo "<h1>What Can I see?</h1>";
  echo "Everything of yours and what they can see.<p>";
//  echo "Note: Things on board other things (e.g. Named Characters) do not show up in this display - currently<p>";
// var_dump ($Places);  

  foreach ($MyHomes as $H) {
    switch ($H['ThingType']) {
    case 1: 
      $P = Get_Planet($H['ThingId']);
      $Sid = $P['SystemId'];
      break;
    case 2:
      $M = Get_Moon($H['ThingId']);
      if ($M) $P = Get_Planet($M['PlanetId']);
      if (!empty($P)) $Sid = $P['SystemId'];
      break;
    case 3: // Thing - already done
      continue 2;
    }
    if (!$Sid) continue;
    $Places[$Sid] = (empty($Places[$Sid])? 8 : ($Places[$Sid] | 8));
  }

//var_dump ($Places);
//var_dump ($SRefs);
// Now have list of places to search

  foreach ($SRefs as $Sid=>$Ref) {
    if (!isset($Places[$Sid])) continue;
    $Eyes = $Places[$Sid];
//  foreach ($Places as $Sid=>$Eyes ) {
    echo SeeInSystem($Sid,$Eyes,1,1,$Fid);
/*
    $Things = Get_AllThingsAt($Sid);
// var_dump ($Things); echo "XX<p>";   
    $N = Get_System($Sid);
    echo "<h2>System " . $N['Ref'] . "</h2>"; // TODO Add name...
    $LastWhose = 0;
    foreach ($Things as $T) {
//var_dump($T); echo "<p>";
      if (($ThingTypes[$T['Type']]['SeenBy'] & $Eyes) == 0 ) continue;
      if ($LastWhose && $LastWhose!= $T['Whose']) echo "<P>";
      echo ($GM?( "<a href=ThingEdit.php?id=" . $T['id'] . ">" . $T['Name'] . "</a>") : $T['Name'] ) . " a ";
      if ($ThingTypes[$T['Type']]['Properties'] & THING_HAS_LEVELS) echo " level " . $T['Level'];
      if ($T['Class']) echo " " . $T['Class'] . " class ";
      if ($T['Whose']) echo " " . $Factions[$T['Whose']]['Name'];
      echo " " . $ThingTypes[$T['Type']]['Name'];
      if (!empty($T['Image'])) echo " <img valign=top src=" . $T['Image'] . " height=100> ";
      echo "<br clear=all>\n";
      $LastWhose = $T['Whose'];
    };
//
  }
 
  $LastWhose = 0;
  
  if (!empty($Hosts)) {
    foreach($Hosts as $Hid=>$H) {
      if (empty($H)) continue;
      $HostT = isset($MyThings[$Hid]) ? $MyThings[$Hid] : Get_Thing($Hid);
      echo "<h2>On Board " . $HostT['Name'] . " is:</h2>";
      foreach($H as $Tid) {
        $T = Get_Thing($Tid);
        echo SeeThing($T,$LastWhose,15,$T['Whose'],1);
      }
    }
  }
*/
  dotail();  
?>
