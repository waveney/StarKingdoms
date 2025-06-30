<?php



function CheckSurveyReports() {
  global $GAME, $GAMEID, $SurveyLevels, $SurveyTypes;

  //  Go throuh Scans file - sort by Fid - each link, is it higher scan level than currently scanned?  If so find highest scan level attempted and show menu
  //  GMs can de-tune to allow for conflict etc  Enable scan data - save scans to log?
  $Scans = Gen_Get_Cond('ScansDue'," GameId=$GAMEID AND Turn=" . ($GAME['Turn']) . " ORDER BY FactionId,Sys,Type,Scan DESC");

  // var_dump($Scans);
  $Facts = Get_Factions();
  $Started = 0;
  $LastSys = 0;
  $LastFid = $LastType = -1;

  foreach($Scans as $spid=>$S) {

    $Fid = $S['FactionId'];
    $Sid = $S['Sys'];
    $SSid = $S['id'];
    if (($LastFid == $Fid) && ($LastSys == $Sid) && ($LastType == $S['Type'])) continue;
    $FS = Get_FactionSystemFS($Fid,$Sid);

    //var_dump($FS);
    switch ($S['Type']) {
      case 0: // Passive
        if ($FS['ScanLevel'] >= $S['Scan']) continue 2;
        if (!Feature('CheckPassive')) continue 2;
        break;
      case 1: // Space
        if ($FS['SpaceScan'] >= $S['Scan']) continue 2;
        break;
      case 2: // Planet
        if ($FS['PlanetScan'] >= $S['Scan']) continue 2;
        break;
    }

    $N = Get_System($S['Sys']);
    if ($Fid == $N['Control']) continue;

    if (!$Started) {
      GMLog("<h2>Please review these scans, Stop as needed</h2>Only those scans that could be stopped are listed<p>\n");
      GMLog("<form method=post action=TurnActions.php?ACTION=DoStage2>" . fm_hidden('Stage','Check Survey Reports'));
      GMLog("<table border><tr><td>Faction<td>Where<td>Scan Level<td>Type<td>Control<td>Stop<td>Reason\n");
      if (Access('God')) echo "</tbody><tfoot><tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea>";
      //      Register_AutoUpdate('ScansDue',0);
      $Started = 1;
    }

    //   if ($N['Nebulae'] > $S['Neb']) $S['Scan'] = 0; // Blind - in nebula wo neb scanners
    GMLog("<tr><td>" . $Facts[$Fid]['Name'] . "<td>" . $N['Ref'] . "<td>" . $S['Scan'] . "<td>" . $SurveyTypes[$S['Type']] .
      ($N['Control'] ? ( "<td style='background:" . $Facts[$N['Control']]['MapColour'] . ";'>" . $Facts[$N['Control']]['Name'])  : '<td>None') .
      "<td>Allow? " . fm_YesNo("Scan$spid",1, "Reason to reject") . "\n<br>");
      $LastSys = $Sid;
      $LastType = $S['Type'];
      $LastFid = $Fid;
  }
  if ($Started) {
    GMLog("</table>\n");
    GMLog( "<input type=submit name=Ignore value=Chosen>\n");
    dotail();
  } else {
    return 2;
  }
}


function GiveSurveyReports() {
  global $GAME, $GAMEID, $SurveyTypes;
  $Scans = Gen_Get_Cond('ScansDue'," GameId=$GAMEID AND Turn=" . ($GAME['Turn']) . " ORDER BY FactionId,Sys,Type,Scan DESC");

  $Facts = Get_Factions();
  $LastFid = $Started = 0;
  $LastSys = -1;

  //  var_dump($_REQUEST,$Scans);

  foreach($Scans as $spid=>$S) {

    $Fid = $S['FactionId'];
    if ( (!isset($_REQUEST["Scan$spid"]) &&  (!isset($_REQUEST["Scan$spid"])))  ||
      (isset($_REQUEST["Scan$spid"]) &&  ($_REQUEST["Scan$spid"] == "on"))) {
        $FS = Get_FactionSystemFS($Fid,$S['Sys']);
        $New = !isset($FS['id']);
        $Changed = $New;
        $Scan = max(1,$S['Scan']);
        switch ($S['Type']) {
          case 0: // Passive
            if ($FS['ScanLevel'] < $S['Scan']) {
              $Changed = 1;
              $FS['ScanLevel'] = $S['Scan'];
            }
            break;
          case 1: // Space
            if (($FS['SpaceScan'] < $S['Scan']) || ($FS['SpaceTurn'] < $GAME['Turn'])) {
              $Changed = 1;
              $FS['SpaceScan'] = $S['Scan'];
              $FS['SpaceTurn'] = $GAME['Turn'];
              Record_SpaceScan($FS);
            }
            break;
          case 2: // Planet
            if (($FS['PlanetScan'] < $S['Scan']) || ($FS['PlanetTurn'] < $GAME['Turn'])) {
              $Changed = 1;
              $FS['PlanetScan'] = $S['Scan'];
              $FS['PlanetTurn'] = $GAME['Turn'];
              Record_PlanetScan($FS);
            }
            break;
        }
        if ($Changed) {
          Put_FactionSystem($FS);
          //     if (($Fid != $LastFid) || ($LastSys != $S['Sys'])) {
          $N = Get_System($S['Sys']);
          if (!$New) {
            TurnLog($Fid, "<h3>You are due an improved survey report for <a href=SurveyReport.php?N=" . $S['Sys'] . ">" . System_Name($N,$Fid) . "</a>" .
              " (Just click on the system on your map)</h3>");
          } else {
            TurnLog($Fid, "<h3>You are have new survey report for <a href=SurveyReport.php?N=" . $S['Sys'] . ">" . System_Name($N,$Fid) . "</a>" .
              " (Just click on the system on your map)</h3>");
          }
          //   }
        }
      } else if (isset($_REQUEST["ReasonScan$spid"])) {
        $N = Get_System($S['Sys']);
        TurnLog($Fid,"Your " . $SurveyTypes[$S['Type']] . " survey in " . System_Name($N)." could not be performed because " .
          $_REQUEST["ReasonScan$spid"] . "\n<br>");
      }
      $LastFid = $Fid;
      $LastSys = $S['Sys'];

  }
  //  echo "Give Survey Reports is currently Manual<p>";
  return 1;
}

// TODO GM check, and use the scan results to modify

function CheckSpotAnomalies() {
  global $GAME;
  // Get all Anomalies - order by locn and scan level asc
  // foreach get all ships with sensors in each locn
  // if fact already knows skip
  // if sensors < scan level needed report failure and margin
  // if sensors >= scan level report found
  // Do we need to consider multiple ships from a faction?

  $Facts = Get_Factions();
  $Systems = Get_SystemRefs();
  $Scans = Gen_Get_Cond('ScansDue'," Turn=" . $GAME['Turn'] . " ORDER BY FactionId,Sys");
  $Anoms = Gen_Get_Cond('Anomalies',"GameId=" . $GAME['id'] . " ORDER BY SystemId, ScanLevel");
  $SysAs = [];
  $Started = 0;

  foreach ($Anoms as $Aid=>$A) {
    $Sid = $A['SystemId'];
    if (isset($SysAs[$Sid])) {
      $SysAs[$Sid][] = $Aid;
    } else {
      $SysAs[$Sid] = [$Aid];
    }
  }

  if ($Scans) {
    foreach($Scans as $Scid=>$Sc) {
      $Fid = $Sc['FactionId'];

      $Sys = $Sc['Sys'];
      if (!empty($SysAs[$Sys])) {
        foreach ($SysAs[$Sys] as $Aid) {
          $FA = Gen_Get_Cond1('FactionAnomaly',"FactionId=$Fid AND AnomalyId=$Aid");
          if (isset($FA['id'])) continue; // Already found
          $FS = Get_FactionSystemFS($Fid, $Sys);

          $Loc = 0; // Space
          $LocCat = intdiv($A['WithinSysLoc'],100);
          if ($LocCat ==2 || $LocCat == 4) $Loc=1; // Ground;
          if (($Loc == 1) && $A['VisFromSpace']) $Loc=3; // Vis From Space

          if ((($Loc == 0) && ($A['ScanLevel']<=$FS['SpaceScan'])) ||
            (($Loc == 1) && ($A['ScanLevel']<=$FS['PlanetScan'])) ||
            (($Loc == 2) && ($A['ScanLevel']<=max($FS['SpaceScan'],$FS['PlanetScan'])))) {  // Found...

              if (empty($A['OtherReq'])) {
                $N = Get_System($Sys);
                $Syslocs = Within_Sys_Locs($N);

                TurnLog($Fid,"You have spotted an anomaly: " . $A['Name'] . " in " . $Systems[$Sid] . "\n" .
                  " location: " . ($Syslocs[$A['WithinSysLoc']]? $Syslocs[$A['WithinSysLoc']]: "Space") . "<p>" .
                  Parsetext($A['Description']) .
                  "\nIt will take " . $A['AnomalyLevel'] . " scan level actions to complete.\n\n");

                  $FA = ['FactionId' => $Fid, 'State'=>1, 'AnomalyId'=>$Aid];
                  Gen_Put('FactionAnomaly',$FA);

                  continue;
              } else {
                if (!$Started){
                  GMLog("<form method=Post action=TurnActions.php?ACTION=DoStage2>" . fm_hidden('Stage','CheckSpotAnomalies'));
                  GMLog("<h1>Please check these anomalies should be spotted</h1>");
                  GMLog("<table border><tr><td>Who<td>Where<td>what<td>Reqs<td>Enable\n");
                  $Started=1;
                }
                GMLog("<tr><Td>" . $Facts[$Fid]['Name'] . "<td>" . $Systems[$Sys] . "<td><a href=AnomalyEdit.php?id=$Aid>" . $A['Name'] . "</a><td>" .
                  $A['OtherReq'] . "<td>" . fm_checkbox('',$_REQUEST,"Enable$Fid:$Aid"));

              }
          }
        }
      }
    }
  }

  if ($Started) {
    GMLog("</table><input type=submit value='Click to Proceed'></form>\n");
    dotail();
  } else {
    return 2;
  }

}


function SpotAnomalies() {
  global $GAMEID;
  // Get all Anomalies - order by locn and scan level asc
  // foreach get all ships with sensors in each locn
  // if fact already knows skip
  // if sensors < scan level needed report failure and margin
  // if sensors >= scan level report found
  // Do we need to consider multiple ships from a faction?

  $Facts = Get_Factions();
  $Systems = Get_SystemRefs();
  $mtch = [];

  foreach($_REQUEST as $R=>$V) {
    if (preg_match('/Enable(\d*):(\d*)/',$R,$mtch) && ($V != 'on')){
      $Fid = $mtch[1];
      $Aid = $mtch[2];
      $A = Get_Anomaly($Aid);
      $Sys = $A['SystemId'];

      $FA = Gen_Get_Cond1('FactionAnomaly',"FactionId=$Fid AND AnomalyId=$Aid");
      $N = Get_System($Sys);
      $Syslocs = Within_Sys_Locs($N);

      TurnLog($Fid,"You have spotted an anomaly: " . $A['Name'] . " in " . $N['Ref'] . "\n" .
        " location: " . ($Syslocs[$A['WithinSysLoc']]? $Syslocs[$A['WithinSysLoc']]: "Space") . "<p>" .
        ParseText($A['Description']) .
        "\nIt will take " . $A['AnomalyLevel'] . " scan level actions to complete.\n\n");
        Gen_Put('FactionAnomaly',$FA);
    }
  }

  GMLog("All Anomalies checked<p>");
  return 1;
}

