<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("SystemLib.php");
  include_once("vendor/erusev/parsedown/Parsedown.php");
  
  A_Check('GM'); // For now, will be player version

  dostaffhead("Survey Report",["js/dropzone.js","css/dropzone.css" ]);

  global $db, $GAME, $FACTION;

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
  
  $SurveyLevel = 0;
  if (Access('GM')) {
    $SurveyLevel = 10;
  } else {
    if (isset($_REQUEST['F'])) {
       $Fid = $_REQUEST['F'];
       $FACTION = Get_faction($Fid);
    }   
    $SurveyLevel = 2; // Test
  }
  if (isset($_REQUEST['F'])) {
    $Fid = $_REQUEST['F'];
    $FACTION = Get_faction($Fid);
  }

//  $SurveyLevel = 2; // Test purposes

  $Parsedown = new Parsedown();
  $PTNs = Get_PlanetTypeNames();
  $PTD = Get_PlanetTypes();
  $DistTypes = Get_DistrictTypes();
  $LinkLevels = Get_LinkLevels();
  
  $N=Get_System($Sid);
  $Ref = $N['Ref'];
  $Fs= Get_Factions();
  
  $pname = NameFind($N); // Need diff logic for player
  if (!$pname) $pname = $Ref;
  
  echo "<div class=SReport><h1>Survey Report - $pname</h1>\n";
  echo "UniqueRef is: " . UniqueRef($Sid) . "<p>";
  
  if ($N['Description']) echo $Parsedown->text($N['Description']) . "<p>";
  
  if ($N['Control']) echo "Controlled by: " . "<span style='background:" . $Fs[$N['Control']]['MapColour'] . "; padding=2;'>" . $Fs[$N['Control']]['Name'] . "</span><p>";
  if ($SurveyLevel >= 10 && $N['HistoricalControl']) echo "Historically controlled by: " . "<span style='background:" . $Fs[$N['HistoricalControl']]['MapColour'] . "; padding=2;'>" 
      . $Fs[$N['HistoricalControl']]['Name'] . "</span><p>"; // GM only
  
  // Star (s)
  
  if ($N['Image']) echo "<img src=" . $N['Image'] . ">";
  
  echo "The " . ($N['Type2']?"star":"principle star") . " is a " . $N['Type'] . ", with a radius of " . 
        sprintf("%0.2g Km = ",$N['Radius'])  . RealWorld($N,'Radius') . ", a mass of " .
        sprintf("%0.2g Kg = ",$N['Mass'])  . RealWorld($N,'Mass') . ", a temperature of " .
        sprintf("%0.0f K = ",$N['Temperature'])  . " and a luminosity of " .
        sprintf("%0.2g Km = ",$N['Luminosity'])  . RealWorld($N,'Luminosity') . ".<p>";
  if ($N['Type2']) {
    if ($N['Image2']) echo "<br clear=all><img src=" . $N['Image2'] . ">";
    echo "The companion star is a " . $N['Type2'] . ", with a radius of " . 
        sprintf("%0.2g Km = ",$N['Radius2'])  . RealWorld($N,'Radius2') . ", a mass of " .
        sprintf("%0.2g Kg = ",$N['Mass2'])  . RealWorld($N,'Mass2') . ", a temperature of " .
        sprintf("%0.0f K = ",$N['Temperature2'])  . " and a luminosity of " .
        sprintf("%0.2g Km = ",$N['Luminosity2'])  . RealWorld($N,'Luminosity2') . " which orbits at " .
        sprintf("%0.2g Km = ",$N['Distance'])  . RealWorld($N,'Distance') . ", with a periodicity of " .
        sprintf("%0.2g Hr = ",$N['Period'])  . RealWorld($N,'Period') . ".<p>";
  }      
            
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
      echo "The system has $Planets planets";
    } else {
      echo "The system has a planet";
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
        echo "The system has $Asteroids asteroid belts.";
      } else {
        echo "The system has an asteroid belt.";
      }
  
  } else {
    echo "No planets or asteroids in the system";
  }
  
  echo "<br clear=all><ul>";
  
  foreach ($Ps as $P) {
    $Pid = $P['id'];
    $Mns = [];
    if ($P['Moons']) $Mns = Get_Moons($Pid);

    echo "<li><span class=SRName>" . NameFind($P) . "</span>";
    if ($P['Image']) echo "<img src=" . $P['Image'] . ">";
    echo " Is " . ($PTNs[$P['Type']] == 'Asteroid Belt'?" an ":($PTD[$P['Type']]['Hospitable']?" a <b>habitable ":" an uninhabitable ")) . 
         PM_Type($PTD[$P['Type']],"Planet") . "</b>.  ";
    
    if ( $SurveyLevel > 2 && $PTD[$P['Type']]['Hospitable'] && $P['Minerals']) echo "It has a minerals rating of <b>" . $P['Minerals'] . "</b>.  ";
    echo "It's orbital radius is " . sprintf('%0.2g', $P['OrbitalRadius']) . " Km = " .  RealWorld($P,'OrbitalRadius');
    if ($P['Period']) echo ($P['Radius']?" ,":" and") . " a period of " . sprintf('%0.2g', $P['Period']) . " Hr = " .  RealWorld($P,'Period');
    if ($P['Radius']) echo ", it has a radius of " . sprintf('%0.2g', $P['Radius']) . " Km = " .  RealWorld($P,'Radius') .
                           " and gravity at " . sprintf('%0.2g', $P['Gravity']) . " m/s<sup>2</sup> = " .  RealWorld($P,'Gravity');
    
    if ($P['Moons']) echo ".  It has " . Plural($P['Moons'],'',"a moon.", $P['Moons'] . " moons.");
    
    if ($SurveyLevel > 2 && $P['Description']) echo "<p>" . $Parsedown->text($P['Description']) ;
    
    echo "<p>";
    // Districts
    
    if ($SurveyLevel > 5) {
      $Ds = Get_DistrictsP($Pid);
      if ($Ds) { // && 
        echo "<p>Districts: ";
        $dc = 0;
        foreach ($Ds as $D) {
          if ($dc++) echo ", ";
          echo $DistTypes[$D['Type']]['Name'] . ": " . $D['Number'];
        }
        echo "<p>";
      }
    }

    if ($Mns) {
      echo Plural($Mns,'',"  The moon of note is:", "  The moons of note are: ") . "<p><ul>";
      foreach ($Mns as $M) {
        $Mid = $M['id'];
        echo "<li><span class=SRName>" . NameFind($M) . "</span>";
        if ($M['Image']) echo "<img src=" . $M['Image'] . ">";        
        echo " Is " . ($PTNs[$M['Type']] == 'Asteroid Belt'?" an ":($PTD[$P['Type']]['Hospitable']?" a <b>habitable ":" an uninhabitable ")) . 
             PM_Type($PTD[$M['Type']],"Moon") . "</b>.  ";
    
        if ($SurveyLevel > 2 && $PTD[$M['Type']]['Hospitable'] && $M['Minerals']) echo "It has a minerals rating of <b>" . $M['Minerals'] . "</b>.  ";
        echo "It's orbital radius is " . sprintf('%0.2g', $M['OrbitalRadius']) . " Km = " .  RealWorld($M,'OrbitalRadius') . 
             ($M['Radius']?" ,":" and") . " a period of " . sprintf('%0.2g', $M['Period']) . " Hr = " .  RealWorld($M,'Period');
        if ($P['Radius']) echo ", it has a radius of " . sprintf('%0.2g', $M['Radius']) . " Km = " .  RealWorld($M,'Radius') .
                               " and gravity at " . sprintf('%0.2g', $M['Gravity']) . " m/s<sup>2</sup> = " .  RealWorld($M,'Gravity');
                               
        if ($SurveyLevel > 2 && $M['Description']) echo "<p>" . $Parsedown->text($M['Description']);
        
        // Districts
        if ($SurveyLevel > 5) {
          $Ds = Get_DistrictsM($Mid);
            
          if ($Ds) { // && 
            echo "<p>Districts: ";
            $dc = 0;
            foreach ($Ds as $D) {
              if ($dc++) echo ", ";
              echo $DistTypes[$D['Type']]['Name'] . ": " . $D['Number'];
            }
            echo "<p>";
          }
        }

      }
      echo "</ul><p>";
    }    
    echo "<p>";
  }


  
  echo "</ul>";
  
  $Ls = Get_Links($Ref);
  echo "<BR CLEAR=ALL><h2>There are Stargates to:</h2>\n";
  $GM = Access('GM');
  
  foreach ($Ls as $L) {
    $OSysRef = ($L['System1Ref']==$Ref? $L['System2Ref']:$L['System1Ref']);
    $ON = Get_SystemR($OSysRef); 
    if ($SurveyLevel >= 10) {
      $LinkKnow = ['Known'=>1];
    } else if ($FACTION) {
      $LinkKnow = Get_FactionLinkFL($Fid,$L['id']);
    } else {
      $LinkKnow = ['Known'=>0];
    }
    echo "<li>Link #" . $L['id'] . " ";

//var_dump($LinkKnow);    
    if (1 ||$LinkKnow['Known']) {
      $name = NameFind($L);
      if ($name) echo " ( $name ) ";
      echo " to " . ReportEnd($ON) .  " level " . $LinkLevels[$L['Level']]['Colour'];
    } else {
      echo " to unknown location.  Level " .  $LinkLevels[$L['Level']]['Colour'];
    }
    
  }
 
  
  // Links
  // Images
  
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
