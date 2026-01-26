<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("PlayerLib.php");
  include_once("SystemLib.php");
  include_once("ProjLib.php");
  include_once("HomesLib.php");
  include_once("BattleLib.php");
  include_once("TurnTools.php");
  include_once("vendor/erusev/parsedown/Parsedown.php");

  global $LinkStates,$GAME,$GAMEID;

  A_Check('GM');

  $LinkState = array_flip($LinkStates);
  dostaffhead("Special Turn Processing");
  echo "<h1>Special Turn Actions - Only needed once a Blue Moon</h1>\n";

// var_dump($_REQUEST);

  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
    case 'ExplodeLink':
      echo "<form method=post action=TurnSpecials.php?ACTION=ExplodeLink2>";
      echo fm_number('Link id #:',$_REQUEST,'LinkId');
      echo "</form>";
      dotail();

    case 'ExplodeLink2':
      $Lid = $_REQUEST['LinkId'];
      $L = Get_Link($Lid);

      $SR1 = Get_SystemR($L['System1Ref']);
      $SR2 = Get_SystemR($L['System2Ref']);

      $DamageDice = (abs($L['Level'])+1)*2;
      GMLog("<span class=Red>LINK EXPLOSION </span> on link $Lid from " . $L['System1Ref'] . " to " . $L['System2Ref'] );
      GMLog("Do ($DamageDice D10) x 10 to everything (Including Outposts, Space Stations etc) in " .
            "<a href=Meetings.php?ACTION=Check&R=" . $L['System1Ref'] . ">" . $L['System1Ref'] . "</a> And " .
            "<a href=Meetings.php?ACTION=Check&R=" . $L['System2Ref'] . ">" . $L['System2Ref'] . "</a>");
            // Emergency lockdown both ends

//      db_delete('Links',$L['id']);
      SetAllLinks($L['System1Ref'], $SR1['id'],$LinkState['In Safe Mode']);
      SetAllLinks($L['System2Ref'], $SR2['id'],$LinkState['In Safe Mode']);

      Report_Others(0, $SR1['id'], 31, "Link #$Lid Exploded.  All other links in " . $L['System1Ref'] . " have been put in Safe Mode");
      Report_Others(0, $SR2['id'], 31, "Link #$Lid Exploded.  All other links in " . $L['System2Ref'] . " have been put in Safe Mode");

            // Remove the link!

      $L['GameId'] = - $L['GameId'];
      Put_Link($L);
      echo "Link Exploded<p>";
      dotail();

    case 'RefitAll' :
      $Facts = Get_Factions();
      foreach ($Facts as $Fid=>$F) {
        echo "Starting " . $F['Name'] . "<p>";
        $Wid = $F['HomeWorld'];
        if ($Wid <= 0) {
          echo "No homeworld - missing out seting starting loc<p>";
          $StartSys = 0;
        } else {
          $World = Get_World($Wid);
          $Planet = Get_Planet($World['ThingId']);
          $StartSys = ($Planet['SystemId']??0);
        }
        $Things = Get_Things_Cond($Fid);
        foreach ($Things as $Tid=>$T) {
          if ($StartSys && ($T['SystemId'] == 0)) $T['SystemId'] = $StartSys;
          Put_Thing($T);
          if ($T['BuildState'] < BS_COMPLETE) $T['BuildState'] = BS_COMPLETE;
          RefitRepair($T);
          echo "Setup thing $Tid - " . $T['Name'] . "<br>";
        }
        echo "Finished " . $F['Name'] . "<p>";
        flush();
      }
      dotail();

    case 'FixFSdata':
      $Facts = Get_Factions();
      foreach ($Facts as $Fid=>$F) {
        $HW = $F['HomeWorld'];
        if ($HW <= 0) {
          echo "Skipping " . $F['Name'] . " no homeworld found.<p>";
          continue;
        }
        $World = Get_World($HW);
        switch ($World['ThingType']) {
          case 1: // Planet
            $Planet = Get_Planet($World['ThingId']);
            $Sys = $Planet['SystemId'];
            break;
          case 2:// Moon
            $Moon = Get_Moon($World['ThingId']);
            $Planet = Get_Planet($Moon['PlanetId']);
            $Sys = $Planet['SystemId'];
            break;
          case 3: // Thing
            $Thing = Get_Thing($World['ThingId']);
            $Sys = $Thing['SystemId'];
            break;
        }
        $FS = Get_FactionSystemFS($Fid,$Sys);
        $Sens = max(1,Has_Tech($Fid,'Sensors'));
        $FS['ScanLevel'] = max($FS['ScanLevel'],$Sens);
        $FS['SpaceScan'] = max($FS['SpaceScan'],$Sens);
        $FS['PlanetScan'] = max($FS['PlanetScan'],$Sens);

        Put_FactionSystem($FS);
        echo "Sorted " . $F['Name'] . "<p>";

      }

      dotail();

    case 'FixFSdata2':
      $FSs = Gen_Get_Cond('FactionSystem',"FactionId>=29"); // 29 for live
      foreach ($FSs as $FS) {
        $FS['PlanetTurn'] = $GAME['Turn'];
        Record_SpaceScan($FS);
        if ($FS['PlanetScan']>0) Record_PlanetScan($FS);
        if ($FS['SpaceScan']>0 || $FS['PlanetScan']>0) echo "Updated " . $FS['FactionId'] . ":" . $FS['SystemId'] . "<br>";
      }
      echo "All Done";
      dotail();

    case 'SetTeamDescs':
      $Orgs = Gen_Get_All_GameId('Organisations');
      foreach ($Orgs as $Oid=>$Org) {
        $Tid = $Org['Team'];
        if ($Tid == 0) continue;
        $Ops = Gen_Get_Cond1('Operations',"OrgId=$Oid AND Status=1");
        if (!$Ops) continue;
        $Team = Get_Thing($Tid);
        $Team['Description'] = $Ops['Name'] . ' ' . $Ops['Description'];
        Put_Thing($Team);
        echo "Set Team for " . $Org['Name'] . "<p>";
      }
      echo "All Done";
      dotail();

    case 'ChangeFighters':
      $ModTypes = Get_ModuleTypes();
      $MTN = NamesList($ModTypes);
      $NFM = array_flip($MTN);
      $Arm = $NFM['Ship Armour'];
      $Things = Get_Things(0,'Fighter');
      $Facts = Get_Factions();

      foreach($Things as $Tid=>$T) {
        $Ms = Get_Modules($Tid);
        if (isset($Ms[$Arm])) {
          db_delete('Modules',$Ms[$Arm]['id']);
          $T['OrigHealth'] = $T['CurHealth'] = 1;
          $T['Evasion'] += 25;
          $T['Level'] = 0;
          $T['MaxModules'] = 4;
//var_dump($T);
          Put_Thing($T);
          echo "Fixed <a href=ThingEdit.php?id=$Tid>" . $T['Name'] . " </a>of " . ($Facts[$T['Whose']]['Name']??'Unknown') . "<br>";
        }
      }
      echo "All Done";
      dotail();

    case 'ChangeDefFighters':
      $Things = Get_Things(0,'Fighter Defences');
      $Facts = Get_Factions();

      foreach($Things as $Tid=>$T) {
        $T['OrigHealth'] = $T['CurHealth'] = 1;
        $T['Evasion'] += 25;
        $T['Level'] = 0;
        Put_Thing($T);
        echo "Fixed <a href=ThingEdit.php?id=$Tid>" . $T['Name'] . " </a>of " . ($Facts[$T['Whose']]['Name']??'Unknown') . "<br>";
      }
      echo "All Done";
      dotail();

    case 'ChangePatrolShips':
      $Facts = Get_Factions();
      $Things = Get_Things_Cond_Ordered(0,"Blueprint=2391 AND GameId=$GAMEID");
      $ModTypes = Get_ModuleTypes();
      $MTN = NamesList($ModTypes);
      $NFM = array_flip($MTN);
      $Arm = $NFM['Ship Armour'];
      $Wep = $NFM['Ship Weapons'];

      foreach($Things as $Tid=>$T) {
        $Ms = Get_Modules($Tid);
        //var_dump($T,$Ms); echo "<p>";
        $Ms[$Arm]['Number'] +=1;
        $Ms[$Wep]['Number'] -=1;
        $ModHlth = $Ms[$Arm]['Level']*3+12;
        $T['OrigHealth'] += $ModHlth;
        $T['CurHealth'] += $ModHlth;
        //var_dump($T,$Ms); exit;
        Put_Module($Ms[$Arm]);
        Put_Module($Ms[$Wep]);
        Put_Thing($T);
        echo "Fixed <a href=ThingEdit.php?id=$Tid>" . $T['Name'] . " </a>of " . ($Facts[$T['Whose']]['Name']??'Unknown') . "<br>";
      }

      echo "All Done";
      dotail();

    case 'ChangePatrolShips2':
      $Facts = Get_Factions();
      $Things = Get_Things_Cond_Ordered(0,"Blueprint=2391 AND GameId=$GAMEID");
      $ModTypes = Get_ModuleTypes();
      $MTN = NamesList($ModTypes);
      $NFM = array_flip($MTN);
      $Arm = $NFM['Ship Armour'];
      $Wep = $NFM['Ship Weapons'];

      foreach($Things as $Tid=>$T) {
        $Ms = Get_Modules($Tid);
        $ModHlth = $Ms[$Arm]['Level']*3;
        $T['OrigHealth'] += $ModHlth;
        $T['CurHealth'] += $ModHlth;
        Put_Thing($T);
        echo "Fixed <a href=ThingEdit.php?id=$Tid>" . $T['Name'] . " </a>of " . ($Facts[$T['Whose']]['Name']??'Unknown') . "<br>";
      }

      echo "All Done";
      dotail();

    case 'ActuallyDelete':
      $Pending = Get_Things_Cond_Ordered(0,"GameId=$GAMEID AND BuildState=" . BS_DELETE);
      if ($Pending) {
        foreach($Pending as $Did=>$D) {
          Thing_Delete($Did,1);
        }
      }
      echo "All Done";
      dotail();

    case 'LinkMods':
      $Systems = Get_Systems();
      $Links = Get_LinksGame();
      $TTypes = Get_ThingTypes();
      $TNames = NamesList($TTypes);
      $ThingNames = array_flip($TNames);
      $WormStab = $ThingNames['Wormhole Stabiliser'];
      $DeWormStab = $ThingNames['Wormhole Destabiliser'];
      foreach ($Links as $Lid=>$L) {
        $Sid1 = $Systems[$L['System1Ref']]['id'];
        $Sid2 = $Systems[$L['System2Ref']]['id'];

        $Stabs = Get_Things_Cond(0,"Type=$WormStab AND (SystemId=$Sid1 OR SystemId=$Sid2) AND Dist1=$Lid AND BuildState=3" );
        $DeStabs = Get_Things_Cond(0,"Type=$DeWormStab AND (SystemId=$Sid1 OR SystemId=$Sid2) AND Dist1=$Lid AND BuildState=3" );
        if ($Stabs) foreach($Stabs as $S) $L['ThisTurnMod']-=$S['Level'];
        if ($DeStabs) foreach($DeStabs as $S) $L['ThisTurnMod']+=$S['Level']*2;
        Put_Link($L);
      }
      echo "All Done";
      dotail();

    case 'REDOSaveCur':
      $Factions = Get_Factions();
      foreach($Factions as $F) {
        $Fid = $F['id'];
        $CouldC = WhatCanBeSeenBy($Fid,1);
        $CB = fopen("Turns/$GAMEID/" . $GAME['Turn'] . "/CouldC$Fid.html", "w");
        fwrite($CB,$CouldC);
        fclose($CB);
      }
      echo "Saved What can I see Updated for CURRENT turn";
      dotail();

    case 'REDOSavePrev':
      $Factions = Get_Factions();
      foreach($Factions as $F) {
        $Fid = $F['id'];
        $CouldC = WhatCanBeSeenBy($Fid,1);
        $CB = fopen("Turns/$GAMEID/" . ($GAME['Turn']-1) . "/CouldC$Fid.html", "w");
        fwrite($CB,$CouldC);
        fclose($CB);
      }
      echo "Saved What can I see Updated for PREVIOUS turn";
      dotail();

    case 'RecoverSys' : // Recover Militia /HS for N turn recovery ignoring conflict flag
      $Systems = Get_SystemRefs();
      if (empty($_REQUEST['Turns'])) $_REQUEST['Turns'] = 1;

      echo "<form method=post>";

      echo fm_number('Turns',$_REQUEST,'Turns');
      echo fm_select($Systems,$_REQUEST,'Sid',);
      echo fm_submit('ACTION','Recover Militia');
      dotail();

    case 'Recover Militia':
      $Sid = $_REQUEST['Sid'];
      $Turns = $_REQUEST['Turns']??1;
      $Things = Get_Things_Cond(0,"BuildState=" . BS_COMPLETE . " AND (CurHealth<OrigHealth OR CurShield<ShieldPoints) AND SystemId=$Sid");
      $TTypes = Get_ThingTypes();

      foreach ($Things as $T) {
        if (($TTypes[$T['Type']]['Prop2']??0) & THING_HAS_RECOVERY) {
          if ($TTypes[$T['Type']]['Name'] == 'Militia') {
            $Dists = Gen_Get_Cond('Districts',"HostType=" . $T['Dist1'] . " AND HostId=" . $T['Dist2']);
            $Dcount = 0;
            foreach($Dists as $D) $Dcount += $D['Number'];
            $Rec = floor($Dcount/2)+1;
          } else {
            $Rec = intdiv($T['OrigHealth'],4);
          }
          $Rec*=$Turns;
          // echo "Recovery of $Rec<br>";
          $T['CurHealth'] = min($T['OrigHealth'], $T['CurHealth']+$Rec);
          Put_Thing($T);
          echo "<a href=ThingEdit.php?id=" . $T['id'] . ">" . $T['Name'] . ' a ' . $TTypes[$T['Type']]['Name'] . "</a> recovered $Rec health<p>";
        }
      }
      echo "Done";

      break;

    case 'SetHSSys':
      // Foreach mil branch

      $Branches = Gen_Get_Cond('Branches',"GameId=$GAMEID AND (OrgType=3 OR OrgType2=3)");

      foreach ($Branches as $B) {
        switch($B['HostType']) {
          case 1: // Planet
            $P = Get_Planet($B['HostId']);
            $Sid = $P['SystemId'];
            break;

          case 2: // Moon
            $M = Get_Moon($B['HostId']);
            $P = Get_Planet($M['PlantId']);
            $Sid = $P['SystemId'];
            break;

          case 3:// Thing
            $T = Get_Thing($B['HostId']);
            break;
        }
      } // NOT FINISHED - just using SQL
      // System for branch
      // Set all HS

      break;


    }
  }

  echo "</form>";
  echo "<h2>Actions: (Check with Richard before using any)</h2>";
  echo "<li><a href=TurnSpecials.php?ACTION=ExplodeLink>Explode Link</a><p>";
  echo "<li><a href=TurnSpecials.php?ACTION=RefitAll>Refit all and Complete all in planning</a><p>";
//  echo "<li><a href=TurnSpecials.php?ACTION=FixFSdata2>Fix FS Data (again)</a><p>";
  echo "<li><a href=TurnSpecials.php?ACTION=SetTeamDescs>Set Team Descriptions</a><p>";
//  echo "<li><a href=TurnSpecials.php?ACTION=ChangeFighters>Change Fighters</a><p>";
  echo "<li><a href=TurnSpecials.php?ACTION=ChangeDefFighters>Change Defense Fighters</a><p>";
  //  echo "<li><a href=TurnSpecials.php?ACTION=ChangePatrolShips>Change Patrol Ships</a><p>";
//  echo "<li><a href=TurnSpecials.php?ACTION=ChangePatrolShips2>Reccheck Change Patrol Ships</a><p>";

  echo "<li><a href=TurnSpecials.php?ACTION=ActuallyDelete>Delete all things Pending Deletion</a><p>";
  echo "<li><a href=TurnSpecials.php?ACTION=LinkMods>Recalc Link Mods</a><p>";
  echo "<li><a href=TurnSpecials.php?ACTION=REDOSaveCur>Redo What Can I See Save for Current Turn</a><p>";
  echo "<li><a href=TurnSpecials.php?ACTION=REDOSavePrev>Redo What Can I See Save for prev Turn</a><p>";

  echo "<li><a href=TurnSpecials.php?ACTION=RecoverSys>Recover Militia/Heavy Security for a turn</a><p>";
  echo "<li><a href=TurnSpecials.php?ACTION=SetHSSys>Set Heavy Security SystemId</a><p>";


  echo "<h2><a href=TurnActions.php>Back to Turn Processing</a></h2>";

  dotail();
?>
