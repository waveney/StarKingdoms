<?php
  include_once("sk.php");
  global $FACTION,$GAMEID,$USER,$GAME;
  include_once("GetPut.php");
  include_once("PlayerLib.php");
  include_once("ThingLib.php");
  global $PlayerState,$PlayerStates;

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
  
  $MyThings = Get_Things($Fid);
  $MyHomes = Get_ProjectHomes($Fid);
  $ThingTypes = Get_ThingTypes();
  
  $Factions = Get_Factions();
  
  $Places = [];
  
  foreach ($MyThings as $T) {
    if ($T['BuildState'] < 2 || $T['BuildState']> 3) continue; // Ignore things not in use
    $Sid = $T['SystemId'];
    if (!$Sid) continue;
// echo "Adding " .$T['Name'] . " " .$T['id'] . " " . $T['SystemId'] . "<br>";
    $Eyes = EyesInSystem($Fid,$Sid);
    $Places[$Sid] = (empty($Places[$Sid])? $Eyes : ($Places[$Sid] | $Eyes));
  }

// var_dump ($Places);  

  foreach ($MyHomes as $H) {
    switch ($H['ThingType']) {
    case 0: 
      $P = Get_Planet($H['ThingId']);
      $Sid = $P['SystemId'];
      break;
    case 1:
      $M = Get_Moon($H['ThingId']);
      if ($M) $P = Get_Planet($M['PlanetId']);
      if (!empty($P)) $Sid = $P['SystemId'];
      break;
    case 2: // Thing - already done
      continue 2;
    }
    if (!$Sid) continue;
    $Places[$Sid] = (empty($Places[$Sid])? 8 : ($Places[$Sid] | 8));
  }

//var_dump ($Places);
// Now have list of places to search

  foreach ($Places as $Sid=>$Eyes ) {
    SeeInSystem($Sid,$Eyes,1);
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
*/
  }
      
  // List of all things and places
  
  // Sort by location - alphabetically?
  
  // Anything here?
  
  

  dotail();  
?>
