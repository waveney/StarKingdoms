<?php

// Update festival system
// push database Skema changes
// Call Special Update if needed

  include_once("fest.php");
  global $FESTSYS,$VERSION,$db;

// ********************** START HERE ***************************************************************


  dostaffhead("Update System");

//  FixUpdate436();
//  echo "Done";
//  exit;

  $Match = '';
  preg_match('/(\d*)\.(\d*)/',$VERSION,$Match);
  $pfx = $Match[1];
  $Version = $Match[2];

  if (($FESTSYS['CurVersion'] ?? 0) == $Version) {
    echo "The System is up to date - no actions taken<p>";
    dotail();
  }
// Pre Database changes

  if (!isset($_REQUEST['MarkDone'])) {
    for ($Ver = ($FESTSYS['CurVersion'] ?? 0); $Ver <= $Version; $Ver++) {
      if (function_exists("PreUpdate$Ver")) {
        echo "Doing Pre update to Verion $pfx.$Ver<br>";
        ("PreUpdate$Ver")();
      }
    }


    chdir('Schema');
    $skema = system('skeema push 2>&1');
    $skedit = preg_replace('/\n/','<br>\n',$skema);
    echo $skedit . "\n\n";
    chdir('..');

    if (strstr('[ERROR]',$skedit)) {
      echo "<p>The Database structure failed to update.<p>Update cancelled<p>";
      dotail();
    }


// Post Database changes

    for ($Ver = ($FESTSYS['CurVersion'] ?? 0); $Ver <= $Version; $Ver++) {
      if (function_exists("PostUpdate$Ver")) {
        echo "Doing Post update to Verion $pfx.$Ver<br>";
        ("PostUpdate$Ver")();
      }
    }
  }
  echo "Updated to Version $VERSION<p>";
  $FESTSYS['CurVersion'] = $Version;
  $FESTSYS['VersionDate'] = time();
  Gen_Put('SystemData',$FESTSYS);
  dotail();
?>
