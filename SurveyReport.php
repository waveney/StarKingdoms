<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("SystemLib.php");
  include_once("ThingLib.php");
  include_once("vendor/erusev/parsedown/Parsedown.php");

  dostaffhead("Survey Report",["js/dropzone.js","css/dropzone.css" ]);

  global $db, $GAME, $FACTION, $LinkStates, $FAnomalyStates, $GAMEID;

// START HERE
  $GM = Access('GM');
//var_dump($_REQUEST);
  if (isset($_REQUEST['N'])) {
    $Sid = $_REQUEST['N'];
    $N=Get_System($Sid);
  } else if (isset($_REQUEST['id'])) {
    $Sid = $_REQUEST['id'];
    $N=Get_System($Sid);
  } else if (isset($_REQUEST['R'])) {
    $N=Get_SystemR($_REQUEST['R']);
    if (!$N) {
      echo "<h2>System " . $_REQUEST['R'] . " Not Known</h2>";
      dotail();
    }
    $Sid = $N['id'];
  } else {
    echo "<h2>No Systems Requested</h2>";
    dotail();
  }
  if ($GM) {
    if (isset($_REQUEST['FORCE'])) {
      $GM = 0;
    } else {
      echo "<h2><a href=SurveyReport.php?N=$Sid>This page in Player Mode</a></h2>\n";
    }
  }

  $SurveyLevel = 100; // Switch off
  $PlanetLevel = $SpaceLevel = $ScanLevel = 0;
  $Syslocs = Within_Sys_Locs($N);

  if (!empty($FACTION)) { // Player mode
//    $ScanSurveyXlate = [0=>0, 1=>1, 2=>3, 3=>5];
    $Fid = $FACTION['id'];
    $FS = Get_FactionSystemFS($Fid,$Sid);
    if (empty($FS['id'])) {
      echo "<h1>Unknown system</h1>\n";
      dotail();
    }
    $ScanLevel = max(0,$FS['ScanLevel']);
    $PlanetLevel = max(0,$FS['PlanetScan']);
    $SpaceLevel = max(0,$FS['SpaceScan']);
  } else { // GM access
    if (isset($_REQUEST['V'])) {
      $SurveyLevel = $_REQUEST['V'];
    } else if (Access('GM')) {
      $SurveyLevel = $PlanetLevel = $SpaceLevel = 100;
    }

    if (isset($_REQUEST['F'])) {
      $Fid = $_REQUEST['F'];
      $FACTION = Get_faction($Fid);
    } else {
      $Fid = 0;
    }

    /*
    if (isset($_REQUEST['M']) && $Fid ) {  // DUFF OLD  Needs changes if ever reused
      $FS = Get_FactionSystemFS($Fid,$Sid);
      $FS['ScanLevel'] = ($_REQUEST['L']?? $SurveyLevel);
      if (isset($_REQUEST['N'])) $FS['NebScanned'] = ($_REQUEST['N']?? $SurveyLevel);
      Put_FactionSystem($FS);
    }*/
  }


  $Parsedown = new Parsedown();
  $PTNs = Get_PlanetTypeNames();
  $PTD = Get_PlanetTypes();
  $DistTypes = Get_DistrictTypes();
  $LinkLevels = Get_LinkLevels();

  $N=Get_System($Sid);
  $Ref = $N['Ref'];
  $Fs= Get_Factions();

  if ($N['Flags'] & 1) Dynamic_Update($N);

  $pname = System_Name($N,$Fid);
/*
  $pname = NameFind($N);
  if ($Fid) {
    $FS = Get_FactionSystemFS($Fid, $Sid);
    if (!empty($FS['Name']) > 1) {
      $Fname = NameFind($FS);

      if ($pname != $Fname) {
        if (strlen($pname) > 1) {
          $pname = $Fname . " ( $pname | $Ref ) ";
        } else {
          $pname = $Fname . " ( $Ref ) ";
        }
      } else {
        $pname .= " ( $Ref ) ";
      }
    } else if ($pname) {
      $pname .= " ( $Ref ) ";
    } else {
      $pname = $Ref;
    }
  } else if ($pname) {
    $pname .= " ( $Ref ) ";
  } else {
    $pname = $Ref;
  }
*/
  echo "<div class=SReport><h1>Survey Report - $pname</h1>\n";
  if (Feature('UniqueRefs') && $GM && $SurveyLevel >= 10) echo "UniqueRef is: " . UniqueRef($Sid) . "<p>";

  if ($Fid) echo "This system has been passivly scanned. " .
     (($SpaceLevel>0)?"Has been space scanned at level $SpaceLevel. ":"No Space Survey has been Made. ") .
     (($PlanetLevel>0)?"Has been planetary scanned at level $PlanetLevel<p>":"No Planetary Survey has been made.<p>");

  if (($ScanLevel>=0) && ($SurveyLevel > 2)) {

    if ($N['Description']) echo $Parsedown->text($N['Description']) . "<p>";

    if ($N['Control']) echo "Controlled by: " . "<span style='background:" . $Fs[$N['Control']]['MapColour'] . "; padding=2;'>" . $Fs[$N['Control']]['Name'] . "</span><p>";
    if ($GM && $SurveyLevel >= 10 && $N['HistoricalControl']) echo "Historically controlled by: " . "<span style='background:" . $Fs[$N['HistoricalControl']]['MapColour'] .
        "; padding=2;'>" . $Fs[$N['HistoricalControl']]['Name'] . "</span><p>"; // GM only

    // Star (s)

    if ($N['Image']) echo "<img src=" . $N['Image'] . ">";
    $Acc = "%0.2g";
    if (isset($N['Flags']) && ($N['Flags'] &1)) $Acc="%0.8g";

    echo "The " . ($N['Type2']?"principle star":"star");
    if ($N['StarName']) echo " ( " . $N['StarName'] . " ) " ;
    echo " is a " . $N['Type'] . ".<br>";

    if ($SurveyLevel >= 3) echo "It has a radius of " .
          sprintf("$Acc Km = ",$N['Radius'])  . RealWorld($N,'Radius') .
          ", a mass of " . sprintf("$Acc Kg = ",$N['Mass'])  . RealWorld($N,'Mass') .
          ", a temperature of " . sprintf("$Acc K = ",$N['Temperature'])  .
          " and a luminosity of " . sprintf("$Acc W = ",$N['Luminosity'])  . RealWorld($N,'Luminosity') . ".<p>";

    if ($N['Type2']) {

      if ($N['Image2']) echo "<br clear=all><img src=" . $N['Image2'] . ">";
      echo "The companion star ";
      if ($N['StarName2']) echo " ( " . $N['StarName2'] . " ) " ;
      echo " is a " . $N['Type2']  . ".<br>";

      if ($SurveyLevel >= 3) echo "It has a radius of " . sprintf("$Acc Km = ",$N['Radius2'])  . RealWorld($N,'Radius2') .
          ", a mass of " . sprintf("$Acc Kg = ",$N['Mass2'])  . RealWorld($N,'Mass2') .
          ",<br>A temperature of " . sprintf("$Acc K = ",$N['Temperature2'])  .
          " and a luminosity of " . sprintf("$Acc W = ",$N['Luminosity2'])  . RealWorld($N,'Luminosity2') .
          ",<br>Which orbits at " . sprintf("$Acc Km = ",$N['Distance'])  . RealWorld($N,'Distance') .
          ", with a periodicity of " .
          ($N['Period']>1? sprintf("$Acc Hr = ",$N['Period']) : sprintf("$Acc Seconds = ",$N['Period']*3600) )  . RealWorld($N,'Period') . ".<p>";
    }
    echo "<br>";
    if ($SurveyLevel >= 0) {
      $Ps = Get_Planets($Sid);
      $Planets = $Asteroids = 0;
      foreach ($Ps as $Pi=>$P) {
        if ($P['Attributes'] & 1) {
          if ($GM || $P['Control'] == $Fid) {
            $Ps[$Pi]['Hidden'] = 1;
          } else {
            unset($Ps[$Pi]);
            continue;
          }
        }
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

    }
    echo "<br clear=all><ul>";

    if ($SurveyLevel >= 3) {
      foreach ($Ps as $P) {
        $Pid = $P['id'];
        $Mns = [];
        if ($P['Moons']) $Mns = Get_Moons($Pid);

        foreach ($Mns as $Mip=>$MM) {
          if ($MM['Attributes'] & 1) {
            if ($GM || $MM['Control'] == $Fid) {
              $Mns[$Mip]['Hidden'] = 1;
            } else {
              unset($Mns[$Mip]);
              continue;
            }
          }
        }

        $pname = NameFind($P); // Need diff logic for player
        if ($Fid) {
          $FP = Get_FactionPlanetFS($Fid, $Pid);
          if (isset($FP['Name']) && strlen($FP['Name']) > 1) {
            $Fname = NameFind($FP);

            if ($pname != $Fname) {
              if (strlen($pname) > 0) {
                $pname = $Fname . " ( $pname ) ";
              } else {
                $pname = $Fname;
              }
            }
          }
        }

        echo "<li><span class=SRName>" . $pname . "</span>";
  //var_dump($P);
        if ($P['Image']) echo "<img src=" . $P['Image'] . ">";
        if ($SurveyLevel >= 4) {
          echo " Is " . (isset($P['Hidden'])?' (hidden) ':'') .
               ($PTNs[$P['Type']] == 'Asteroid Belt'?" an ":($PTD[$P['Type']]['Hospitable']?" a <b>habitable ":" an uninhabitable "));
        } else {
          echo "<br>";
        }
        echo PM_Type($PTD[$P['Type']],"Planet") . "</b>.  ";

        if ($SurveyLevel >= 4 && $P['Control']>0 && $P['Control'] != $N['Control']) {
          echo "Controlled by: " . "<span style='background:" . $Fs[$P['Control']]['MapColour'] . "; padding=2;'>" . $Fs[$P['Control']]['Name'] . "</span><p>";
        }

        if ($P['Minerals']) {
          if (( $PTD[$P['Type']]['Hospitable'] && ($PlanetLevel>0)) ||
            (!$PTD[$P['Type']]['Hospitable'] && ($SpaceLevel>0))) {
              echo "It has a minerals rating of <b>" . $P['Minerals'] . "</b>.  ";
            }
        }
        if ($SurveyLevel >= 4) {
          echo "It's orbital radius is " . sprintf('%0.2g', $P['OrbitalRadius']) . " Km = " .  RealWorld($P,'OrbitalRadius');

          if ($P['Period']) echo ($P['Radius']?" ,":" and") . " a period of " . sprintf('%0.2g', $P['Period']) . " Hr = " .  RealWorld($P,'Period');
          if ($P['Radius']) echo ", it has a radius of " . sprintf('%0.2g', $P['Radius']) . " Km = " .  RealWorld($P,'Radius') .
                                 " and gravity at " . sprintf('%0.2g', $P['Gravity']) . " m/s<sup>2</sup> = " .  RealWorld($P,'Gravity');

          if ($P['Moons']) echo ".  It has " . Plural($P['Moons'],'',"a moon.", $P['Moons'] . " moons.");
        }
        if ($SurveyLevel > 4 && $P['Description']) echo "<p>" . $Parsedown->text($P['Description']) ;

        echo "<p>";
    // Districts

        if (($PlanetLevel>0) && ($SurveyLevel > 5)) { // Now planet Survey
          $Ds = Get_DistrictsP($Pid);
          if ($Ds) { // &&
            echo "<p>Districts: ";
            $dc = 0;
            foreach ($Ds as $DD) {
              if (!isset($DistTypes[$DD['Type']])) continue;
              if ($dc++) echo ", ";
              echo $DistTypes[$DD['Type']]['Name'] . ": " . $DD['Number'];
            }
            echo "<p>";
          }
        }

        if ($SurveyLevel >= 4 && $Mns) {
          echo Plural($Mns,'',"  The moon of note is:", "  The moons of note are: ") . "<p><ul>";
          foreach ($Mns as $M) {
            $Mid = $M['id'];

            $pname = NameFind($M); // Need diff logic for player
            if ($Fid) {
              $FP = Get_FactionMoonFS($Fid, $Mid);
              if (isset($FP['Name']) && strlen($FP['Name']) > 1) {
                $Fname = NameFind($FP);

                if ($pname != $Fname) {
                  if (strlen($pname) > 1) {
                    $pname = $Fname . " ( $pname ) ";
                  } else {
                    $pname = $Fname;
                  }
                }
              }
            }

            echo "<li><span class=SRName>" . $pname . "</span>";
  //var_dump($M);
            if ($M['Image']) echo "<img src=" . $M['Image'] . ">";
            if ($SurveyLevel >= 4) {
              echo " Is " . (isset($M['Hidden'])?' (hidden) ':'') .
                   ($PTNs[$M['Type']] == 'Asteroid Belt'?" an ":($PTD[$M['Type']]['Hospitable']?" a <b>habitable ":" an uninhabitable "));
            } else {
              echo "<br>";
            }

            echo PM_Type($PTD[$M['Type']],"Moon") . "</b>.  ";

            if ($SurveyLevel >= 4 && $M['Control']>0 && $M['Control'] != $P['Control']) {
              echo "Controlled by: " . "<span style='background:" . $Fs[$M['Control']]['MapColour'] . "; padding=2;'>" . $Fs[$M['Control']]['Name'] . "</span><p>";
            }

            if ($M['Minerals']) {
              if (( $PTD[$M['Type']]['Hospitable'] && ($PlanetLevel>0)) ||
                (!$PTD[$M['Type']]['Hospitable'] && ($SpaceLevel>0))) {
                  echo "It has a minerals rating of <b>" . $M['Minerals'] . "</b>.  ";
                }
            }

            if ($SurveyLevel >= 4) {
              echo "It's orbital radius is " . sprintf('%0.2g', $M['OrbitalRadius']) . " Km = " .  RealWorld($M,'OrbitalRadius') .
                   ($M['Radius']?" ,":" and") . " a period of " . sprintf('%0.2g', $M['Period']) . " Hr = " .  RealWorld($M,'Period');
              if ($M['Radius']) echo ", it has a radius of " . sprintf('%0.2g', $M['Radius']) . " Km = " .  RealWorld($M,'Radius') .
                                   " and gravity at " . sprintf('%0.2g', $M['Gravity']) . " m/s<sup>2</sup> = " .  RealWorld($M,'Gravity');
            }

            if ($SurveyLevel > 4 && $M['Description']) echo "<p>" . $Parsedown->text($M['Description']);

            // Districts
            if (($PlanetLevel >0) && ($SurveyLevel > 5)) { // Now Planet Survey
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
    }


    echo "</ul>";
  }

  if ($SurveyLevel > 1) {
    $Ls = Get_Links($Ref);
    echo "<BR CLEAR=ALL><h2>There are " . Feature('LinkRefText','Stargate') . "s to:</h2><ul>\n";
//    $GM = Access('GM');

    foreach ($Ls as $L) {
      $OSysRef = ($L['System1Ref']==$Ref? $L['System2Ref']:$L['System1Ref']);
      $ON = Get_SystemR($OSysRef);
      $LinkKnow = Get_FactionLinkFL($Fid,$L['id']);
 //     var_dump($LinkKnow,$L,$SpaceLevel);
      if (!(isset($LinkKnow['id']))) {
        if ($L['Concealment']<=max(0,$SpaceLevel)) {
          $LinkKnow = ['Known'=>1];
        } else {
          continue;
        }
      }
      /*
      if ($SurveyLevel >= 10) {
        $LinkKnow = ['Known'=>1];
      } else if ($FACTION) {
        $LinkKnow = Get_FactionLinkFL($Fid,$L['id']);
      } else {
        $LinkKnow = ['Known'=>0];
      }*/
      echo "<li>Link " . ($L['Name']?$L['Name']:"#" . $L['id']) . " ";

      if ($LinkKnow['Known']) {
//        $name = NameFind($L);
//        if ($name) echo " ( $name ) ";
        echo " to " . ReportEnd($ON) . " Instability: " . $L['Instability'] . " Concealment: " . $L['Concealment'];
        // " level " . $LinkLevels[abs($L['Level'])]['Colour'];
      } else {
        echo " to an unknown location." . " Instability: " . $L['Instability'] . " Concealment: " . $L['Concealment'];
        //Level " .  $LinkLevels[abs($L['Level'])]['Colour'];
      }
      if ($L['Status'] != 0) echo " <span class=Red>" . $LinkStates[$L['Status']] . "</span>";

    }
    echo "</ul><p>\n";

    // Known Space ANOMALIES
    $Anoms = Gen_Get_Cond('Anomalies',"GameId=$GAMEID AND SystemId=$Sid");
    $Shown = 0;
    $AnStateCols = ['White','Lightgreen','Yellow','Pink','Green'];

    if ($Anoms) {
      foreach($Anoms as $Aid=>$A) {
        $Loc = 0; // Space
        $LocCat = $A['WithinSysLoc']%100;
        if ($LocCat ==2 || $LocCat == 4) $Loc=1; // Ground;
        if (($Loc == 1) && $A['VisFromSpace']) $Loc=3; // Vis From Space

        if ((($Loc == 0) && ($A['ScanLevel']<=$SpaceLevel)) ||
            (($Loc == 1) && ($A['ScanLevel']<=$PlanetLevel)) ||
            (($Loc == 2) && ($A['ScanLevel']<=max($SpaceLevel,$PlanetLevel)))) {
          if (!$Shown) {
            echo "<h2>Anomalies</h2>";
            $Shown = 1;
          }
          echo "Anomaly: " . $A['Name'] . " location: " . ($Syslocs[$A['WithinSysLoc']]? $Syslocs[$A['WithinSysLoc']]: "Space") . "<p>";
          echo "Description: " . $Parsedown->text($A['Description']) . "<p>";
          $FA = Gen_Get_Cond('FactionAnomaly',"AnomalyId=$Aid AND FactionId=$Fid");
          if (($FA['State']??0) == 0) {
            $FA['State'] = 1;
            Gen_Put('FactionAnomaly',$FA);
          }
          echo "<span style='Background:" . $AnStateCols[$FA['State']] . ";'>" . $FAnomalyStates[$FA['State']];
          echo "<br>Progress: " . ($FA['Progress']??0) . " / " . $A['AnomalyLevel'];

          if (($FA['State'] >= 3) && $A['Completion']) {
            echo "Complete: " . $Parsedown->text($A['Completion']) . "<p>";
          }
        }
      }
    }

    if ($SpaceLevel>0) {
      $SysTrait = 0;
      for($i=1;$i<4;$i++) {
        if ($N["Trait$i"] && ($N["Trait$i" . "Conceal"] <= $SpaceLevel)) {
          if ($SysTrait++ == 0) {
            echo "<h2>System Traits:</h2>";
          }
          echo "System has the trait: " . $N["Trait$i"] . "<br>" . $Parsedown->text($N["Trait$i" . "Desc"]) . "<p>";
        }
      }
    }

    if ($PlanetLevel > 0) {
      $PlanTrait = 0;
      foreach($Ps as $P) {

        for($i=1;$i<4;$i++) {
          if ($P["Trait$i"] && ($P["Trait$i" . "Conceal"] <= $PlanetLevel)) {
            if ($PlanTrait++ == 0) {
              echo "<h2>Planet Traits:</h2>";
            }
            var_dump($P,$PlanetLevel);
            echo "Planet " . $P['Name'] . " has the trait: " . $P["Trait$i"] . "<br>" . $Parsedown->text($P["Trait$i" . "Desc"]) . "<p>";
          }
        }
        $Mns = [];
        if ($P['Moons']) $Mns = Get_Moons($Pid);
        if ($Mns) {
          foreach($Mns as $M) {
            for($i=1;$i<4;$i++) {
              if ($M["Trait$i"] && ($M["Trait$i" . "Conceal"] <= $PlanetLevel)) {
                if ($PlanTrait++ == 0) {
                  echo "<h2>Planet/Moon Traits:</h2>";
                }
                echo "Moon " . $M['Name'] . " of Planet " . $P['Name'] . " has the trait: " . $M["Trait$i"] . "<br>" .
                     $Parsedown->text($M["Trait$i" . "Desc"]) . "<p>";
              }
            }

          }
        }
      }
    }

  } else { // BLIND
    if ($N['Nebulae']) {
      echo "You are caught in a Nebula and have no idea what is here.<p>";
    } else {
      echo "You have arrived somewhere new without sensors.  You have no idea what is here.  This should be impossible...  Tell Richard<p>";
    }

    $Ls = Get_Links($Ref);
    $GM = Access('GM');

    foreach ($Ls as $L) {
      $OSysRef = ($L['System1Ref']==$Ref? $L['System2Ref']:$L['System1Ref']);
      $ON = Get_SystemR($OSysRef);
      if ($SurveyLevel >= 10) {  // WRONG
      } else if ($FACTION) {
        $LinkKnow = Get_FactionLinkFL($Fid,$L['id']);
        if (!$LinkKnow['Known']) continue;
      } else {
        continue;
      }
      echo "You know of Link " . ($L['Name']?$L['Name']:"#" . $L['id']) . " ";

//      $name = NameFind($L);
//      if ($name) echo " ( $name ) ";
      echo " to " . ReportEnd($ON) .  " level " . $LinkLevels[$L['Level']]['Colour'];
    }
  }

  // Links
  // Images

  echo "</div>";


//  if ($GM) $Sid,$Eyes,$heading=0,$Images=1,$Fid=0,$Mode=0)
  echo SeeInSystem($Sid,EyesInSystem($Fid,$Sid),0,1,($FACTION['id']??0),$GM);


  if (Access('GM')) echo "<p><h2><a href=SysEdit.php?id=$Sid>Edit System</s></h2>";

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
