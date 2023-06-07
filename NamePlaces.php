<?php
  include_once("sk.php");
  global $FACTION,$GAMEID,$USER;
  include_once("GetPut.php");
  include_once("PlayerLib.php");
  include_once("SystemLib.php");

// START HERE
// var_dump($_REQUEST);

  dostaffhead('Faction Name Places');
  
  if (isset($_REQUEST['f'])) {
    $Fid = $_REQUEST['f'];
  } else if (isset($_REQUEST['F'])) {
    $Fid = $_REQUEST['F'];
  } else if (isset($_REQUEST['id'])) {
    $Fid = $_REQUEST['id'];
  } else if (isset($FACTION)) {
    $Fid = $FACTION['id'];
  } else {
    echo "<h2>No Faction Requested</h2>";
    dotail();
  }

  if (isset($FACTION)) {
    $F = $FACTION;
  } else {
    $F = Get_Faction($Fid);
  }

  A_Check('Player');

  $GM = Access('GM');
  if (!$GM && $F['TurnState'] > 2) Player_Page();    
  
  echo "<h1>Name places you are aware of</h1>";
  echo "You can't name places you haven't been to.<p>You can have your own name for a System or Planet even if you don't control the system.<p>";
  echo "If you control the system/planet, that name becomes a public name for other factions<p>";

  $Force = 0;
  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
    case 'Remove Label':
      $sysref = $_REQUEST['sysref'];
      $N = Get_SystemR($sysref);
      if (!$N) {
        echo "<h2 class=Err>System $sysref not known to you</h2>";
        break;
      }
      $Sid = $N['id'];
      $FS = Get_FactionSystemFS($Fid,$Sid);
      $FS['Xlabel'] = '';
      Put_FactionSystem($FS);
      echo "<h2>Label has been removed from $sysref</h2>";
      break;
    
    case 'Assign Name' :
    case 'Assign Label' :
    case 'Name' :
      if (!empty($_REQUEST['Name'])) {
        echo "<p>";
        $Name = $_REQUEST['Name'];
        if (! isset($_REQUEST['sysref']) || strlen($_REQUEST['sysref']) < 3 ) {
          echo "<h2 class=Err>System $sysref not known to you</h2>";
          break;      
        }
        $sysref = $_REQUEST['sysref'];
        $N = Get_SystemR($sysref);
        if (!$N) {
          echo "<h2 class=Err>System $sysref not known to you</h2>";
          break;
        }
        $Sid = $N['id'];
        $FS = Get_FactionSystemFS($Fid,$Sid);
        if (!isset($FS['id'])) {
          echo "<h2 class=Err>System $sysref not known to you</h2>";
          break;
        }
        $Control = ($N['Control'] == $Fid);

      
        for($i=1; $i<2; $i++) { // Just to allow a break 3
      
        if (isset($_REQUEST['pname']) && strlen($_REQUEST['pname']) > 0) {
          $pcurname = $_REQUEST['pname'];
          $Ps = Get_Planets($Sid);
// var_dump($pcurname,$Ps);
          foreach ($Ps as $P) {
            if (trim($P['Name']) == $pcurname) { 
              if (isset($_REQUEST['mname'])&& strlen($_REQUEST['mname']) > 0) { // Setting a Moon
                $mcurname = $_REQUEST['mname'];
                $Ms = Get_Moons($P['id']);
                foreach ($Ms as $M) {
                  if (trim($M['Name']) == $mcurname) {
                    $FM = Get_FactionMoonFS($Fid, $M['id']);
                    $FM['Name'] = $Name;
                    Put_FactionMoon($FM);
                    if ($Control && ($M['Control'] == 0 || $M['Control'] == $Control)) {
                      $M['Name'] = $Name;
                      Put_Moon($M);
                    }
                    echo "<h2>Moon $mcurname has been renamed $Name</h2>";
                    break 3;
                  }
                }
                echo "<h2 class=Err>Moon $mcurname not found</h2>";
                break 2;
              } else { // Planet
                $FP = Get_FactionPlanetFS($Fid, $P['id']);
//var_dump("BB",$FP);
                $FP['Name'] = $Name;
                Put_FactionPlanet($FP);
                if ($Control && ($P['Control'] == 0 || $P['Control'] == $Control)) {
                  $P['Name'] = $Name;
                  Put_Planet($P);
                }
                echo "<h2>Planet $pcurname has been renamed $Name</h2>";
              }
              break 2;
            } else {
              $FP = Get_FactionPlanetFS($Fid, $P['id']);
//var_dump("AA",$FP);
              if (isset($FP['Name']) && trim($FP['Name']) == $pcurname) { 
                if (isset($_REQUEST['mname'])&& strlen($_REQUEST['mname']) > 0) { // Setting a Moon
                  $mcurname = $_REQUEST['mname'];
                  $Ms = Get_Moons($P['id']);
                  foreach ($Ms as $M) {
                    if ($M['Name'] == $mcurname) {
                      $FM = Get_FactionMoonFS($Fid, $M['id']);
                      $FM['Name'] = $Name;
                      Put_FactionMoon($FM);
                      if ($Control) {
                        $M['Name'] = $Name;
                        Put_Moon($M);
                      }
                      echo "<h2>Moon $mcurname has been renamed $Name</h2>";
                      break 3;
                    }
                  }
                  echo "<h2 class=Err>Moon $mcurname not found</h2>";
                  break 2;
                } else { // Planet
                  $FP['Name'] = $Name;
                  Put_FactionPlanet($FP);
                  if ($Control) {
                    $P['Name'] = $Name;
                    Put_Planet($P);
                  }
                  echo "<h2>Planet $pcurname has been renamed $Name</h2>";
                }
              break 2;
              }
            }
          }
          echo "<h2 class=Err>Planet $pcurname not found</h2>";
        } else { // Setting System Name
        
          $FS['Name'] = $Name;
          Put_FactionSystem($FS);
          
          if ($Control) {
            $N['Name'] = $Name;
            Put_System($N);
          }
          
          echo "<h2>System $sysref has been named $Name</h2>";
        }
      }
      } else if (isset($_REQUEST['Label']))  {
        echo "<p>";
        $Label = $_REQUEST['Label'];
        if (! isset($_REQUEST['sysref']) || strlen($_REQUEST['sysref']) < 3 ) {
          echo "<h2 class=Err>System not known to you</h2>";
          break;      
        }
        $sysref = $_REQUEST['sysref'];
        $N = Get_SystemR($sysref);
        if (!$N) {
          echo "<h2 class=Err>System $sysref not known to you</h2>";
          break;
        }
        $Sid = $N['id'];
        $FS = Get_FactionSystemFS($Fid,$Sid);
        if (!isset($FS['id']) && $N['Control'] != $Fid) {
          echo "<h2 class=Err>System $sysref not known to you</h2>";
          break;
        }
        $FS['Xlabel'] = $Label;
        Put_FactionSystem($FS); 
        echo "<h2>Label $Label has been assigned to $sysref</h2><p>";
      }    
      break;
           
    default: 
      break;
    }
  }
  
  $dat = ($Force? $_REQUEST : []);
  echo "<form method=post action=NamePlaces.php?ACTION=Name>" . fm_hidden('F',$Fid);
  
  echo "<b>Instructions: Naming:</b> Give System Ref, existing Planet name (To name Planet), existing Moon name (To name a Moon) THEN the name<p>";
  
  echo "<b>Private Label:</b> For a SYSTEM ONLY, you can give a private label.<br>Keep this SHORT a few characters or symbols otherwise your map will be unreadable.<br>" .
       "This will appear Outside the system node on your maps ONLY. (Hope to add emoji support)<p>\n";
  
  echo "Note most moons can't be named<p>";
  
  echo "<table><tr>" . fm_text('System Ref',$dat,'sysref');
  if ($Force) echo fm_hidden('FORCE',1);
  echo "<tr>" . fm_text('Current Planet Name',$dat,'pname') . "- so it can be found";
  echo "<tr>" . fm_text('Current Moon Name',$dat,'mname') . "- so it can be found";
  $val = (empty($dat['Name'])? '' : "value='" . $dat['Name'] . "'" );
  echo "<tr><td>Name to assign:<td><input type=text name=Name $val onchange=this.form.submit()>";
  echo "<input type=submit Name=ACTION Value='Assign Name'>";

  echo "<tr><td>Private Label assign:<td><input type=text name=Label $val onchange=this.form.submit()>";
  echo "<input type=submit Name=ACTION Value='Assign Label'>";  
  echo "<input type=submit name=ACTION value='Remove Label'>";
  echo "</form></table><p>";
  
  if ($Force) echo "<input type=submit value=SET>";
    
  dotail();

?>
