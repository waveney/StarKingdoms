<?php
  include_once("sk.php");
  global $FACTION,$GAMEID,$USER,$GAME;
  include_once("GetPut.php");
  include_once("SystemLib.php");

  /* Do Space or Planetary survey - record result for later viewing */

  function SFile($Type,$id) {
    global $FACTION,$GAMEID;
    $fname = "Turns/$GAMEID/" . ($FACTION['id']??0) . "/$Type/$id";
  }

  function SpaceSurvey($Sid,$Level,$Neb) {
    global $GAME,$FACTION;
    $Fid = ($FACTION['id']??0);
    $N = Get_System[$Sid];
    $pname = System_Name($N,$Fid);

    $S = "<h2>On Turn " . $GAME['Turn'] . " System $pname Was surveyed at Level $Level</h2>\n";

    if ($N['Nebulae']> $Neb) {
      $S .= "Unforunately it is in a nebula and you didn't have any Nebula sensors...<p>";
    } else {
      // Space Anomalies

      // Mineral Ratings

      // Worm holes
    }

    $F = SFile('Space',$Sid);
    fwrite($F,$S);
    fclose($F);
  }

  function PlanetSurvey($Pid,$Level,$body='Planet') {
    global $GAME,$FACTION;
    $Fid = ($FACTION['id']??0);
    if ($body == 'Planet') {
      $P = Get_Planet($Pid);
      $N = Get_System($P['SystemId']);

      $pname = System_Name($N,$Fid);

      $S = "<h2>On Turn " . $GAME['Turn'] . " Planet " . $P['Name'] . " in System $pname Was surveyed at Level $Level</h2>\n";
      $F = SFile('Planet',$Pid);

    } else {
      $Mid = $Pid;
      $M = Get_Moon($Mid);
      $Pid = $M['PlanetId'];
      $N = Get_System($P['SystemId']);

      $pname = System_Name($N,$Fid);

      $S = "<h2>On Turn " . $GAME['Turn'] . " Moon " . $M['Name'] . " around Planet " . $P['Name'] .
           " in System $pname Was surveyed at Level $Level</h2>\n";
      $F = SFile('Moon',$Mid);
    }

    // Mineral Rating

    // Traits/Quirks if Conceal<= Level

    // Anomaliesif Conceal<= Level

    // Districts

    fwrite($F,$S);
    fclose($F);
  }

