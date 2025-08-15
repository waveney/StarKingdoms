<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("SystemLib.php");
  include_once("ThingLib.php");
  include_once("PlayerLib.php");
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

  $DEBUG = $REDO = 0;

  if ($GM) {
    if (isset($_REQUEST['FORCE'])) {
      $GM = 0;
    } else {
      echo "<h2><a href=SurveyReport.php?N=$Sid&FORCE>This page in Player Mode</a></h2>\n";
      if (Access('God')) {
        echo "<h2><a href=SurveyReport.php?N=$Sid&FORCE&DEBUG>This page in Diagnostic Mode</a></h2>\n";
        echo "<h2><a href=SurveyReport.php?N=$Sid&FORCE&REDO>Redo The Cached data for this</a></h2>\n";
      }
    }
    if (isset($_REQUEST['DEBUG'])) {
      $DEBUG = 1;
    }
    if (isset($_REQUEST['REDO'])) {
      $REDO = 1;
    }

  }


  $SurveyLevel = 100; // Switch off
  $PlanetLevel = $SpaceLevel = $ScanLevel = 0;
  $Syslocs = Within_Sys_Locs($N);

  if (!$GM && !empty($FACTION) && !$DEBUG ) { // Player mode
//    $ScanSurveyXlate = [0=>0, 1=>1, 2=>3, 3=>5];
    $Fid = $FACTION['id'];
    $FS = Get_FactionSystemFS($Fid,$Sid);
    if (empty($FS['id'])) {
      echo "<h1>Unknown system</h1>\n";
      dotail();
    }
    $ScanLevel = $FS['ScanLevel'];
    $PlanetLevel = max(0,$FS['PlanetScan']);
    $SpaceLevel = max(0,$FS['SpaceScan']);

    $SpaceBlob = $FS['SpaceSurvey'];
    $PlanBlob = $FS['PlanetSurvey'];

    if ($REDO || $DEBUG || (empty($SpaceBlob) && $SpaceLevel>=0)) {
      $SpaceBlob = Record_SpaceScan($FS,$DEBUG);
    }

    if ($REDO || $DEBUG || (empty($PlanBlob) && $PlanetLevel>0)) {
      $PlanBlob = Record_PlanetScan($FS,$DEBUG);
    }

  } else { // GM access
    if (isset($_REQUEST['F'])) {
      $Fid = $_REQUEST['F'];
      $FACTION = Get_faction($Fid);
    } else if ($FACTION){
      $Fid = $FACTION['id'];
    } else {
      $Fid = 0;
    }

    $FS = Get_FactionSystemFS($Fid,$Sid);

    if (isset($_REQUEST['V'])) {
      $ScanLevel = $SurveyLevel = $PlanetLevel = $SpaceLevel = $_REQUEST['V'];
    } else if ($DEBUG) {
      $ScanLevel = $FS['ScanLevel'];
      $PlanetLevel = max(0,$FS['PlanetScan']);
      $SpaceLevel = max(0,$FS['SpaceScan']);
    } else if (Access('GM')) {
      $ScanLevel = $SurveyLevel = $PlanetLevel = $SpaceLevel = 100;
    }


    $SpaceBlob =  SpaceScanBlob($Sid,$Fid,$SpaceLevel,$PlanetLevel,$Syslocs,$GM,$DEBUG);
    $PlanBlob = PlanetScanBlob($Sid,$Fid,$SpaceLevel,$PlanetLevel,$Syslocs,$GM,$DEBUG);
  }
  $RawBlobs = explode('::::',$PlanBlob);
  $Blobs = [];
  $K = 'None';
  foreach($RawBlobs as $i=>$V) {
    if ($i&1) {
      $Blobs[$K] = $V;
    } else {
      $K = $V;
    }
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

  echo "<div class=SReport><h1>Survey Report - $pname</h1>\n";
  if (Feature('UniqueRefs') && $GM && $SurveyLevel >= 10) echo "UniqueRef is: " . UniqueRef($Sid) . "<p>";

  if ($Fid) {
    if ($ScanLevel>=0) {
      echo "This system has been passively scanned. " .
        (($SpaceLevel>0)?("Has been space scanned at level " . $FS['SpaceScan'] . ($FS['SpaceTurn']?(" on Turn " . $FS['SpaceTurn']):'') .".  ")
          :"No Space Survey has been Made. ") .
        (($PlanetLevel>0)?("Has been planetary scanned at level " . $FS['PlanetScan'] . ($FS['PlanetTurn']?(" on Turn " . $FS['PlanetTurn']):''))
          :"No Planetary Survey has been made.")  . "<p>";
    } else if ($N['Nebulae']) {
      echo "This system is in a Nebula, you have not scanned it with nebula sensors<p>";
    } else {
 //     echo "You have very limited information about this system<p>";

    }

  }

  if (($ScanLevel>=0) && ($SurveyLevel > 2)) {

    if ($N['Description']) echo $Parsedown->text(stripslashes($N['Description'])) . "<p>";

    if ($N['Control']) echo "Controlled by: " . "<span " . FactColours($N['Control'],'white','padding=2') . ">" . $Fs[$N['Control']]['Name'] . "</span><p>";
    if ($GM && $SurveyLevel >= 10 && $N['HistoricalControl']) echo "Historically controlled by: " .
        "<span " . FactColours($N['Control'],'white','padding=2') . ">" . $Fs[$N['HistoricalControl']]['Name'] . "</span><p>"; // GM only

    // Star (s)

    if ($N['Image']) echo "<img class=SeeSImage src=" . $N['Image'] . ">";
    $Acc = "%0.2g";
    if (isset($N['Flags']) && ($N['Flags'] &1)) $Acc="%0.8g";

    echo "The " . ($N['Type2']?"principle star":"star");
    if (($N['StarName']??'') || ($FS && ($FS['Star1Name']??''))) {
      echo " ( " . (($FS && ($FS['Star1Name']??''))?$FS['Star1Name']:$N['StarName']) . " ) " ;
    }
    echo " is a " . $N['Type'] . ".<br>";

    if ($SurveyLevel >= 3) echo "It has a radius of " .
          sprintf("$Acc Km = ",$N['Radius'])  . RealWorld($N,'Radius') .
          ", a mass of " . sprintf("$Acc Kg = ",$N['Mass'])  . RealWorld($N,'Mass') .
          ", a temperature of " . sprintf("$Acc K = ",$N['Temperature'])  .
          " and a luminosity of " . sprintf("$Acc W = ",$N['Luminosity'])  . RealWorld($N,'Luminosity') . ".<p>";

    if ($N['Type2']) {

      if ($N['Image2']) echo "<br clear=all><img class=SeeSImage src=" . $N['Image2'] . ">";
      echo "The companion star ";

      if (($N['StarName2']??'') || ($FS && ($FS['Star2Name']??''))) {
        echo " ( " . (($FS && ($FS['Star2Name']??''))?$FS['Star2Name']:$N['StarName2']) . " ) " ;
      }

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
        if (($P['Attributes'] & 1) || ($P['Concealment']>$SpaceLevel)) {
          if ($GM || $P['Control'] == $Fid) {
            $Ps[$Pi]['Hidden'] = 1;
          } else {
            unset($Ps[$Pi]);
            continue;
          }
        }
        if ($P['Concealment']) echo "It has a conealment rating of: " . $P['Concealment'] . "<br>";
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
          if (($MM['Attributes'] & 1) || ($MM['Concealment']>$PlanetLevel)) {
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
        if ($P['Image']) echo "<img class=SeeImage src=" . $P['Image'] . ">";
        if ($SurveyLevel >= 4) {
          echo " Is " . (isset($P['Hidden'])?' (hidden) ':'') .
               ($PTNs[$P['Type']] == 'Asteroid Belt'?" an ":($PTD[$P['Type']]['Hospitable']?" a <b>habitable ":" an uninhabitable "));
        } else {
          echo "<br>";
        }
        echo PM_Type($PTD[$P['Type']],"Planet") . "</b>.  ";

        if ($SurveyLevel >= 4 && $P['Control']>0) { // && $P['Control'] != $N['Control']) {
          echo "Colonised by: " . "<span " . FactColours($P['Control'],'white',"padding=2") . ">" . $Fs[$P['Control']]['Name'] . "</span><p>";
        }

        echo $Blobs["A$Pid"]??'';

        if ($SurveyLevel >= 4) {
          echo "It's orbital radius is " . sprintf('%0.2g', $P['OrbitalRadius']) . " Km = " .  RealWorld($P,'OrbitalRadius');

          if ($P['Period']) echo ($P['Radius']?" ,":" and") . " a period of " . sprintf('%0.2g', $P['Period']) . " Hr = " .  RealWorld($P,'Period');
          if ($P['Radius']) echo ", it has a radius of " . sprintf('%0.2g', $P['Radius']) . " Km = " .  RealWorld($P,'Radius') .
                                 " and gravity at " . sprintf('%0.2g', $P['Gravity']) . " m/s<sup>2</sup> = " .  RealWorld($P,'Gravity');

          if ($P['Moons']) echo ".  It has " . Plural($P['Moons'],'',"a moon.", $P['Moons'] . " moons.");
        }
        if ($SurveyLevel > 4 && $P['Description']) echo "<p>" . $Parsedown->text(stripslashes($P['Description'])) ;

        echo "<p>";

        echo $Blobs["B$Pid"]??'';
        /*
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

            $World = Gen_Get_Cond1('Worlds',"ThingType=1 AND ThingId=$Pid");
            if ($World) {
              $Offs = Gen_Get_Cond('Offices',"World=" . $World['id']);
              if ($Offs) {
                $Clean = [];
                foreach ($Offs as $i=>$Of) {
                  $Org = Gen_Get('Organisations',$Of['Organisation']);
                  $OrgType = Gen_Get('OfficeTypes',$Org['OrgType']);
                  if ($OrgType['Props']&1) continue; // Hidden
                  $Clean[]= $Org['Name'] . " (" . $OrgType['Name'] . ") ";

                }

                if ($Clean) {
                  echo "<p>Offices: " . implode(', ',$Clean) . "<p>";
                }
              }
            }

          }
        }*/

        if ($SurveyLevel >= 4 && $Mns) { // NOte this does not yet handle multiple embeded moons or some of each
          if ((count($Mns) == 1) && ($Mns[array_key_first($Mns)]['OrbitalRadius'] < $P['Radius'])) {
            echo "Embeded within " . $P['Name'] . " is:<p> ";
          } else {
            echo Plural($Mns,'',"  The moon of note is:", "  The moons of note are: ") . "<p><ul>";
          }
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

            if ( ($SurveyLevel >= 4) && ($M['Control']>0)) { // && $M['Control'] != $P['Control']) {
              echo "Colonised by: " . "<span " . FactColours($M['Control'],'white','padding=2') . ">" . $Fs[$M['Control']]['Name'] . "</span><p>";
            }

            echo $Blobs["C$Mid"]??'';

            if ($SurveyLevel >= 4) {
              echo "It's orbital radius is " . sprintf('%0.2g', $M['OrbitalRadius']) . " Km = " .  RealWorld($M,'OrbitalRadius') .
                   ($M['Radius']?" ,":" and") . " a period of " . sprintf('%0.2g', $M['Period']) . " Hr = " .  RealWorld($M,'Period');
              if ($M['Radius']) echo ", it has a radius of " . sprintf('%0.2g', $M['Radius']) . " Km = " .  RealWorld($M,'Radius') .
                                   " and gravity at " . sprintf('%0.2g', $M['Gravity']) . " m/s<sup>2</sup> = " .  RealWorld($M,'Gravity');
            }
            if ($M['Concealment']) echo "It has a conealment rating of: " . $M['Concealment'] . "<br>";

            if ($SurveyLevel > 4 && $M['Description']) echo "<p>" . $Parsedown->text(stripslashes($M['Description']));

            echo $Blobs["D$Mid"]??'';

          }
          echo "</ul><p>";
        }
        echo "<p>";
      }
    }


    echo "</ul>";
  }

  if ($SpaceBlob) echo $SpaceBlob;

  echo $Blobs['Z0']??'';

  echo "</div>";


//  if ($GM) $Sid,$Eyes,$heading=0,$Images=1,$Fid=0,$Mode=0)
 // echo SeeInSystem($Sid,EyesInSystem($Fid,$Sid),0,1,($FACTION['id']??0),$GM);
// TODO Reinstate that when saving result

  if ($GM) echo "<p><h2><a href=SysEdit.php?id=$Sid>Edit System</s></h2>";

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
