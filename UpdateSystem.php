<?php

// Update festival system
// push database Skema changes
// Call Special Update if needed

  include_once("sk.php");
  global $GAMESYS,$VERSION,$db;

 // function PreUpdate


// ********************** START HERE ***************************************************************


  dostaffhead("Update System");

//  FixUpdate436();
//  echo "Done";
//  exit;

  $Match = '';
  preg_match('/(\d*)\.(\d*)/',$VERSION,$Match);
  $pfx = $Match[1];
  $Version = $Match[2];

  if (($GAMESYS['CurVersion'] ?? 0) == $Version) {
    echo "The System is up to date - no actions taken<p>";
    dotail();
  }
// Pre Database changes

  global $FACTION,$USER,$GAMEID;
  $req_dump = date("D d H:i:s ");
  if (isset($USER['Login'])) $req_dump .= "\nWho: " . $USER['Login'] . "\n";

  $fp = fopen('cache/' . ($GAMEID??'0') . '/Update.log', 'a');
  fwrite($fp, $req_dump);


  if (!isset($_REQUEST['MarkDone'])) {
    for ($Ver = ($GAMESYS['CurVersion'] ?? 0); $Ver <= $Version; $Ver++) {
      if (function_exists("PreUpdate$Ver")) {
        echo "Doing Pre update to Verion $pfx.$Ver<br>";
        ("PreUpdate$Ver")($fp);
        fwrite($fp,"Doing Pre update to Verion $pfx.$Ver\n");
      }
    }


    chdir('Schema');
    $skema = shell_exec('skeema push');
    $skedit = preg_replace("/\n/","<br>\n",$skema);
    echo $skedit . "\n\n";
    fwrite($fp,"Skeema Update:\n$skedit\n\n");
    chdir('..');

    if (strstr('ERROR',$skedit)) {
      echo "<p>The Database structure failed to update.<p>Update cancelled<p>";
      dotail();
    }


// Post Database changes

    for ($Ver = ($GAMESYS['CurVersion'] ?? 0); $Ver <= $Version; $Ver++) {
      if (function_exists("PostUpdate$Ver")) {
        echo "Doing Post update to Verion $pfx.$Ver<br>";
        ("PostUpdate$Ver")($fp);
        fwrite($fp,"Doing Post update to Verion $pfx.$Ver\n");
      }
    }
  }
  echo "Updated to Version $VERSION<p>";
  fwrite($fp,"Updated to Version $VERSION\n\n");

  $GAMESYS['CurVersion'] = $Version;
  $GAMESYS['VersionDate'] = time();
  Gen_Put('MasterData',$GAMESYS);
  dotail();
?>
