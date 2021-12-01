<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("SystemLib.php");
  include_once("vendor/erusev/parsedown/Parsedown.php");
  
  A_Check('GM'); // For now, will be player version

  dostaffhead("Survey Report",["js/dropzone.js","css/dropzone.css" ]);

  global $db, $GAME;

// START HERE
//  var_dump($_REQUEST);
  if (isset($_REQUEST['N'])) {
    $Sid = $_REQUEST['N'];
  } else if (isset($_REQUEST['id'])) {
    $Sid = $_REQUEST['id'];
  } else { 

    echo "<h2>No Systems Requested</h2>";
    dotail();
  }

  $Parsedown = new Parsedown();
  
  $N=Get_System($Sid);
  $Fs= Get_Factions();
  
  $pname = NameFind($N); // Need diff logic for player
  if (!$pname) $pname = $N['Ref'];
  
  echo "<div class=SReport style='width:800;'><h1>Survey Report - $pname</h1>\n";
  echo "UniqueRef is: " . UniqueRef($Sid) . "<p>";
  
  if ($N['Description']) echo $Parsedown->text($N['Description']) . "<p>";
  
  if ($N['Control']) echo "Controlled by: " . "<span style='background:" . $Fs[$N['Control']]['MapColour'] . "; padding=2;'>" . $Fs[$N['Control']]['Name'] . "</span><p>";
  if ($N['HistoricalControl']) echo "Historically controlled by: " . "<span style='background:" . $Fs[$N['HistoricalControl']]['MapColour'] . "; padding=2;'>" 
      . $Fs[$N['HistoricalControl']]['Name'] . "</span><p>"; // GM only
  
  // Star (s)
  
 
  echo "The " . ($N['Type2']?"star":"principle star") . " is a " . $N['Type'] . ", with a radius of " . 
        sprintf("%0.2g Km = ",$N['Radius'])  . RealWorld($N,'Radius') . ", a mass of " .
        sprintf("%0.2g Kg = ",$N['Mass'])  . RealWorld($N,'Mass') . ", a temperature of " .
        sprintf("%0.0f K = ",$N['Temperature'])  . " and a luminosity of " .
        sprintf("%0.2g Km = ",$N['Luminosity'])  . RealWorld($N,'Luminosity') . ".<p>";
  if ($N['Type2']) {
    echo "The companion star is a " . $N['Type2'] . ", with a radius of " . 
        sprintf("%0.2g Km = ",$N['Radius2'])  . RealWorld($N,'Radius2') . ", a mass of " .
        sprintf("%0.2g Kg = ",$N['Mass2'])  . RealWorld($N,'Mass2') . ", a temperature of " .
        sprintf("%0.0f K = ",$N['Temperature2'])  . " and a luminosity of " .
        sprintf("%0.2g Km = ",$N['Luminosity2'])  . RealWorld($N,'Luminosity2') . " which orbits at " .
        sprintf("%0.2g Km = ",$N['Distance'])  . RealWorld($N,'Distance') . ", with a periodicity of " .
        sprintf("%0.2g Hr = ",$N['Period'])  . RealWorld($N,'Period') . ".<p>";
  }      
            
  $PTNs = Get_PlanetTypeNames();
  $PTD = Get_PlanetTypes();

  $Ps = Get_Planets($Sid);
  $Planets = $Asteroids = 0;
  foreach ($Ps as $P) {
    if ($PTNs[$P['Type']] == 'Asteroid Belt') {
      $Asteroids++;
    } else { 
      $Planets++;
    }
  }

  if ($Planets) {
  
    if ($Planets>1) {
      echo "It has $Planets planets";
    } else {
      echo "It has a planet";
    }
    
    if ($Asteroids) {
      if ($Asteroids > 1) {
        echo " and $Asteroids asteroid belts.";
      } else {
        echo " and an asteroid belt.";
      }
    }
  } elseif ($Asteroids) {
      if ($Asteroids > 1) {
        echo "It has $Asteroids asteroid belts.";
      } else {
        echo "It has an asteroid belt.";
      }
  
  } else {
    echo "No planets or asteroids in the system";
  }
  
  echo "<p>";
  
  foreach ($Ps as $P) {
    $Mns = [];
    if ($P['Moons']) $Mns = Get_Moons($P['id']);
    echo NameFind($P) . " is " . ($PTNs[$P['Type']] == 'Asteroid Belt'?" an ":($PTD[$P['Type']]['Hospitable']?" a <b>habitable ":" an uninhabitable ")) . 
         PM_Type($PTD[$P['Type']],"Planet") . "</b>.  ";
    
    if ($PTD[$P['Type']]['Hospitable'] && $P['Minerals']) echo "It has a minerals rating of <b>" . $P['Minerals'] . "</b>.  ";
    echo "It's orbital radius is " . sprintf('%0.2g', $P['OrbitalRadius']) . " Km = " .  RealWorld($P,'OrbitalRadius') . 
         ($P['Radius']?" ,":" and") . " a period of " . sprintf('%0.2g', $P['Period']) . " Hr = " .  RealWorld($P,'Period');
    if ($P['Radius']) echo ", it has a radius of " . sprintf('%0.2g', $P['Radius']) . " Km = " .  RealWorld($P,'Radius') .
                           " and gravity at " . sprintf('%0.2g', $P['Gravity']) . " m/s<sup>2</sup> = " .  RealWorld($P,'Gravity');
    
    if ($P['Moons']) echo ".  It has " . Plural($P['Moons'],'',"a moon.", $P['Moons'] . " moons.");
    
    if ($P['Description']) echo "<p>" . $Parsedown->text($P['Description']) . "<p>";
    
    // Districts

    if ($Mns) {
      echo Plural($Mns,'',"  The moon of note is:", "  The moons of note are: ") . "<p>";
      foreach ($Mns as $M) {
        echo NameFind($M) . " is " . ($PTNs[$M['Type']] == 'Asteroid Belt'?" an ":($PTD[$P['Type']]['Hospitable']?" a <b>habitable ":" an uninhabitable ")) . 
             PM_Type($PTD[$M['Type']],"Moon") . "</b>.  ";
    
        if ($PTD[$M['Type']]['Hospitable'] && $M['Minerals']) echo "It has a minerals rating of <b>" . $M['Minerals'] . "</b>.  ";
        echo "It's orbital radius is " . sprintf('%0.2g', $M['OrbitalRadius']) . " Km = " .  RealWorld($M,'OrbitalRadius') . 
             ($M['Radius']?" ,":" and") . " a period of " . sprintf('%0.2g', $M['Period']) . " Hr = " .  RealWorld($M,'Period');
        if ($P['Radius']) echo ", it has a radius of " . sprintf('%0.2g', $M['Radius']) . " Km = " .  RealWorld($M,'Radius') .
                               " and gravity at " . sprintf('%0.2g', $M['Gravity']) . " m/s<sup>2</sup> = " .  RealWorld($M,'Gravity');
                               
        if ($M['Description']) echo "<p>" . $Parsedown->text($M['Description']) . "<p>";
        
        // Districts
      }
      
    }    
    echo "<p>";
  }

  // Planets
  
  // Moons
  
  
  
  echo "</div>";
  
  if (Access('GM')) echo "<h2><a href=SysEdit.php?id=$Sid>Edit System</s></h2>";
  
  dotail();
  

    
  
/* Name, Control, Star(s), Planet(s), Jump Link(s), Anomalies, ships present, Planets: Districts, armies - other names */
/* Player based - Not in control
   Name, Control, Star(s), Planet(s), Jump Link(s), Anomalies?, ships */
/* In control + Planets contents - other names*/

/* Name rules if GM
    If ShortName Use
    if Name Use
    if Control Faction then
      use Factions short name|name if avail  
    else use Refcode
    
  If Not control by faction then
  
  If control by otheer faction then
          use Factions short name|name if avail  
          use sysname if avail
          use randow ref - unique to faction systemm SK#GsFyFsF

  echo "<div class=SReport><h1>Survey Report - " . ((isset($N['ShortName']) && $N['ShortName'])?$N['ShortName']:isset($N['Name']) && $N['Name'])?$N['Name':(Access('GM')?$N['Ref']:
  
*/

?>
