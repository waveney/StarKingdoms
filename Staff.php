<?php
  include_once("sk.php");
  include_once("GetPut.php");
  /* Remove any Participant overlay */
  global $FACTION;
  A_Check('GM');
  if (isset($_COOKIE['SKF'])) {
    unset($_COOKIE['SKF']);
    unset($FACTION);
    setcookie('SKF','',1,'/');
  }

  dostaffhead("SK Pages", ["/js/jquery.typeahead.min.js", "/css/jquery.typeahead.min.css", "/js/Staff.js"]);
  global $GAME, $Heads,$VERSION;
  $Heads = [];

  function SKTable($Section,$Heading,$cols=1) {
    global $Heads;
    static $ColNum = 3;
    $txt = '';
    if ($Section != 'Any' && !Capability("Enable$Section")) return '';
    $Heads[] = $Heading;
    if ($ColNum+$cols > 3) {
      $txt .= "<tr>";
      $ColNum =0;
    }
    $hnam = preg_replace("/[^A-Za-z0-9]/", '', $Heading);
    $txt .= "<td class=Stafftd colspan=$cols >";
    $txt .= "<h2 id='Staff$hnam'>$Heading</h2>";
    $ColNum+=$cols;
    return $txt;
  }

  function GameList(&$Games) {
    $GL = [];
    foreach(array_reverse($Games) as $G) $GL[$G['id']] = $G['id'] . ":" . $G['Name'];
    return $GL;
  }

  global $USER,$GAME,$ErrorMessage,$GAMESYS;
  if (!empty($ErrorMessage)) echo "<h2 class=Err>$ErrorMessage</h2>";

  $Facts = Get_Faction_Names();
// var_dump($Facts);
  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
      case 'Resolve' :
        $gid = $_REQUEST['id'];
        db_delete('GMLog4Later', $gid);
        break;
    }
  }

//echo php_ini_loaded_file() . "<P>";
  echo "<div class=floatright><h2>";
  if ($GAME['Turn'] <6 ) {
    echo "Turn : <a href=Staff.php?Y=0>Setup</a>";
    if ($GAME['Turn']) for($turn=1; $turn <= $GAME['Turn']; $turn++) {
      echo ", <a href=Staff.php?Y=$turn>$turn</a>";
    }
  } else {
    echo "Turn : <div id=ExpandTurnsDots class=InLine><b onclick=ExpandTurns()>...</b></div><div id=HiddenTurns hidden>";
    echo "<a href=Staff.php?Y=0>Setup</a>";
    for($turn=1; $turn <= $GAME['Turn']; $turn++) {
      if ($turn == ($GAME['Turn'] - 4)) echo "</div><div class=InLine>";
      echo ", <a href=Staff.php?Y=$turn>$turn</a>";
    }
    echo "</div>";
  }
  echo "</h2></div>";

  echo "<h2>SK Pages - " . (isset($GAME['Name'])?$GAME['Name']:"Star Kingdoms" ) . "</h2>\n";

  $Factions = Get_Factions();
  echo "<span style='line-height:1.8'>";
  foreach($Factions as $F) {
    $Fid = $F['id'];

    echo "<a href=Access.php?id=$Fid&Key=" . $F['AccessKey'] . " style='background:" . $F['MapColour'] . "; color: " . ($F['MapText']?$F['MapText']:'black') .
         ";text-shadow: 2px 2px 3px white;padding:2px'><b>"  . NoBreak($F['Name']) . "</b></a> ";
  }

  echo "</span><p>\n";

  $GMActs = Gen_Get_Cond('GMLog4Later','id>0');
  if ($GMActs) {
    echo "<h2>GMs need to be aware that:</h2>\n";
    foreach($GMActs as $G) {
      echo $G['What'] . " - <a href=Staff.php?ACTION=Resolve&id=" . $G['id'] . ">Resolved</a><br>";
    }
  }

  $txt = "<div class=tablecont><table border width=100% class=Staff style='min-width:800px'>\n";

  if ($x = SKTable('Docs','Document Storage')) {
    $txt .= $x;
      $txt .= "<ul>\n";
      if (Access('Staff')) {
        $txt .= "<li><a href=Dir>View Document Storage</a>\n";
        $txt .= "<li><a href=Search>Search Document Storage</a>\n";
      }
      $txt .= "<p>";
//      $txt .= "<li><a href=ProgrammeDraft1.pdf>Programme Draft</a>\n";
      $txt .= "<li><a href=StaffHelp>General Help</a>\n";

      if (Access('SysAdmin')) {
        $txt .= "<p>";
        $txt .= "<li class=smalltext><a href=DirRebuild?SC>Scan Directories - Report File/Database discrepancies</a>";
//      $txt .= "<li><a href=DirRebuild?FI>Rebuild Directorys - Files are YEARDATA</a>";
//      $txt .= "<li><a href=DirRebuild?DB>Rebuild Directorys - Database is YEARDATA</a>";
      }
      $txt .= "</ul>\n";
    }

// *********************** Maps ****************************************************
  if ($x = SKTable('Any','Mapping')) {
    $txt .= $x;
    $txt .= "<ul>\n";
    $txt .= "<li><a href=MapShow.php>Full Map Show</a>\n";
    $txt .= "<li><a href=MapShow.php?Hex>Hex Map Show</a>\n<p>";

    /*
    $txt .= "<li><form method=Post action=MapFull.php class=staffform>";
      $txt .= "<input type=submit name=f value='Map Show for' id=staffformid>" .
                fm_select($Facts,0,'f',1," onchange=this.form.submit()") . "</form>\n";

    $txt .= "<li><form method=Post action=MapFull.php?Hex&Links=0 class=staffform>";
      $txt .= "<input type=submit name=f1 value='Hex Map for' id=staffformid>" .
                fm_select($Facts,0,'f1',1," onchange=this.form.submit()") . "</form>\n";*/


    $txt .= "<li><a href=MapFull.php?Links=0>Full Map Generate</a>\n";
    $txt .= "<li><a href=MapFull.php?Hex&Links=0>Hex Map Generate</a>\n<p>";
    if (0 && Access('God')) {
      $txt .= "<li><a href=MapEdit.php>Map Edit</a><p>\n";
      $txt .= "<li><a href=MapValid.php>Map Validation</a>\n";
      $txt .= "<li><a href=MapFromCSV.php>Map from CSV</a>\n";
    }
    $txt .= "<p>";
    $txt .= "<li><a href=LinkLevels.php>Link Levels</a>\n";
    $txt .= "<li><a href=LinkInstaLevels.php>Link Instability Levels</a>\n";
    $txt .= "<li><a href=EditLinks.php>Edit Links</a>\n";
    $txt .= "<li><a href=LinkStats.php>Link Stats</a>\n";
    $txt .= "</ul><p>\n";
  }


// *********************** Misc *****************************************************************
  if ($x = SKTable('Any','Systems')) {
    $txt .= $x;
    $txt .= "<ul>\n";
    $txt .= "<li><a href=SysList.php>List Systems</a>\n";
    $txt .= "<p>";
    $txt .= "<li><a href=WhereIs.php>Where is a name?</a>\n";
    $txt .= "<P>";
    $txt .= "<li><a href=DTList.php>List District Types</a>\n";
    $txt .= "<li><a href=PlanetTypes.php>Planet Types</a>\n";
    $txt .= "<li><a href=PlanetStats.php>Planet Stats</a>\n";
    $txt .= "<li><a href=PlanetTraits.php>Planet Traits</a>\n";

    $txt .= "<p>";
    $txt .= "<li><a href=WorldList.php>List Worlds and colonies</a>\n";
    $txt .= "<li><a href=WorldMake.php>Rebuild list of Worlds and colonies</a> - re do Project homes first\n";
//    if (Access('God')) $txt .= "<li><a href=FixMinerals.php>Fix Minerals</a>\n";

    $txt .= "</ul>\n";
  }

// *********************** Misc *****************************************************************
  if ($x = SKTable('Any','Turns')) {
    $txt .= $x;
    $txt .= "<ul>\n";
 //   $txt .= "<li><a href=PlayerStates.php>Player States</a>\n";
    $txt .= "<li><a href=TurnActions.php>Turn Actions</a>\n";
    $txt .= "<li><a href=GMTurnTxt.php>Follow Progress</a>\n";
    $txt .= "<li><a href=Meetings.php>Systems with multiple Factions</a>\n";
    $txt .= "<li><a href=FollowUp.php>Turn Follow Ups needed</a>\n";
    $txt .= "<li><a href=TurnSpecials.php>Turn Specials</a>\n";

    $txt .= "<p>";
    $txt .= "<li><a href=PayFaction.php>Pay Faction (Credit/SP)</a>\n";
    $txt .= "<li><a href=Payments.php>See Payments (Credit/SP)</a>\n";
    $txt .= "<p>";
    $txt .= "<li><a href=UserGuide.php>User Guide</a>\n";
    $txt .= "<li><a href=RepeatFollowUp.php>Set up Repeat Follow Ups</a>\n";

    $txt .= "</ul>\n";
  }

// *********************** Misc *****************************************************************
  if ($x = SKTable('Any','Factions')) {
    $txt .= $x;
    $txt .= "<ul>\n";
    $txt .= "<li><a href=FactList.php>List Factions</a>\n";
//    $txt .= "<li><a href=AddFaction.php>Add Faction</a>\n";
    $txt .= "<li><a href=FactionFaction.php>Faction knows faction?</a><p>\n";

/*
    $txt .= "<li><form method=Post action=PlayerMove.php class=staffform>";
      $txt .= "<input type=submit name=F value='Player Move' id=staffformid>" .
                fm_select($Facts,0,'F',1," onchange=this.form.submit()") . "</form>\n";

    $txt .= "<li><form method=Post action=TechShow.php class=staffform>";
      $txt .= "<input type=submit name=F value='List Technologies' id=staffformid>" .
                fm_select($Facts,0,'F',1," onchange=this.form.submit()") . "</form>\n";

    $txt .= "<li><form method=Post action=TechShow.php?SETUP class=staffform>";
      $txt .= "<input type=submit name=F value='Edit Technologies' id=staffformid>" .
                fm_select($Facts,0,'F',1," onchange=this.form.submit()") . "</form>\n";

    $txt .= "<li><a href=SurveyRequest.php>Survey Request</a>\n";
    $txt .= "<li><form method=Post action=FactionName.php class=staffform>";
      $txt .= "<input type=submit name=F value='Name Things' id=staffformid>" .
                fm_select($Facts,0,'F',1," onchange=this.form.submit()") . "</form>\n";

    $txt .= "<li><form method=Post action=ProjDisp.php class=staffform>";
      $txt .= "<input type=submit name=F value='Projects' id=staffformid>" .
                fm_select($Facts,0,'F',1," onchange=this.form.submit()") . "</form>\n";
    */
//    if (Access('God')) $txt .= "<li><a href=Import.php>Import</a>\n";
    $txt .= "<li><a href=ListTraits.php>List All Faction Traits</a><p>\n";

    $txt .= "<li><a href=ListSocial.php>List Social Principles</a><p>\n";
    $txt .= "<li><a href=TrackedTypes.php>Tracked Properties</a><p>\n";
    $txt .= "<li><a href=ScienceLog.php>Science Point Logs</a> (Only some actions)<p>\n";

//    if (Access('God')) $txt .= "<li><a href=BuildFactLink.php>Build Factions link Knowledge</a>\n";

    $txt .= "</ul>\n";
  }

// *********************** Misc *****************************************************************
  if ($x = SKTable('Any','Things')) {
    $txt .= $x;
    $txt .= "<ul>\n";
    $F = ['F'=>-1];
    $txt .= "<li><form method=Post action=PThingList.php class=staffform>";
      $txt .= "<input type=submit name=F value='Thing List' id=staffformid>" .
                fm_select($Facts,$F,'F',1," onchange=this.form.submit()") . "</form>\n";

    $txt .= "<p>\n";
    $txt .= "<li><a href=ThingTypes.php>Thing Types</a>\n";
    $txt .= "<li><a href=VariantTypes.php>Variant Types</a>\n";
    $txt .= "<li><a href=ThingList.php?Blue>Blue Prints list</a>\n";
    $txt .= "<li><a href=ThingList.php>Thing list</a>\n";
    $txt .= "<li><a href=ThingEdit.php?ACTION=NEW>New Thing</a>";
    $txt .= "<li><a href=Prisoners.php?ACTION=RECALC>Recalc Prisoners</a>";
//    $txt .= "<li><a href=ThingStats.php>Thing Statistics</a>";
    $txt .= "<p>\n";

//    $txt .= "<li><a href=InstrList.php>List Instructions</a>\n";
    if (0 && Access('God')) {
      $txt .= "<li><a href=TidyThings.php>Tidy up</a>  - Call this once a turn to remove unused temp entries<p>\n";
      $txt .= "<li><a href=ModuleCheck.php>Check Things have modules</a>";

      $txt .= "<li><a href=SetAllSensors.php>Set All Sensor data</a> - Bug Fix";
      $txt .= "<li><a href=SetAllSpeeds.php>Set All Speeds</a> - Bug Fix";
      $txt .= "<li><a href=HistoryFudge.php>Set History files</a> - Bug Fix";
      $txt .= "<li><a href=FixAnomaly.php>Fix Anomaly records</a> - Bug Fix";
      $txt .= "<li><a href=ConvertHistory.php>Convert History Format</a>";
    }
    if (Access('God')) {

    }
    $txt .= "</ul>\n";
  }

// *********************** Misc *****************************************************************
  if ($x = SKTable('Any','Game')) {
    $txt .= $x;
    $txt .= "<ul>\n";
    $txt .= "<li><a href=TechShow.php>List Technologies</a> (Player Info)\n";
    $txt .= "<li><a href=TechList.php>Edit Technologies</a><p>\n";

    $txt .= "<li><a href=ModuleShow.php>List Module Types</a> (Player Info)\n";
    $txt .= "<li><a href=ModuleList.php>List/Edit Module Types</a>\n";
    $txt .= "<li><a href=ModFormulae.php>List Module Formulas</a>\n";
    $txt .= "<p>";

    $txt .= "<li><a href=AnomalyList.php>Anomalies</a>\n";
    $txt .= "<p>";
    if (0)    $txt .= "<li><a href=AnomalyImport.php>Anomaly Import</a>\n";
    $txt .= "<p>";

//    $txt .= "<li><a href=DeepSpace.php>Deep Space Projects</a>\n";

    $txt .= "<li><a href=StarKingdoms.php>Other Games</a>\n";

    $txt .= "</ul>\n";
  }

// *********************** Misc *****************************************************************
  if ($x = SKTable('Any','Projects')) {
    $txt .= $x;
    $txt .= "<ul>\n";
    $txt .= "<li><a href=ProjList.php>List Projects</a><p>\n";
    $txt .= "<li><a href=ProjTypes.php>List Project Types</a>\n";
    $txt .= "<li><a href=ProjHomes.php>List Project Homes</a><p>\n";

    $txt .= "<li><a href=RebuildHomes.php>Rebuild Project Homes</a>\n";
    if (Feature('Mines')) $txt .= "<li><a href=RebuildMines.php>Rebuild Mines Data</a>\n";
    $txt .= "<p>";


    $txt .= "</ul>\n";
  }

  // *********************** Misc *****************************************************************
  if (feature('Orgs') && ($x = SKTable('Any','Organisations'))) {
    $txt .= $x;
    $txt .= "<ul>\n";
    $txt .= "<li><a href=OperList.php>List Operations</a><p>\n";
    $txt .= "<li><a href=OrgTypes.php>Organisation Types</a>\n";
    $txt .= "<li><a href=BranchTypes.php>Branch Types</a>\n";
    $txt .= "<li><a href=OpTypes.php>Operation Types</a>\n";
    $txt .= "<li><a href=OrgList.php?ALL>List of all Organisations</a>\n";
    $txt .= "<li><a href=OfficeList.php>List of all Offices</a>\n";
    $txt .= "<li><a href=BranchList.php>List of all Branches</a>\n";
//    if (Access('God')) $txt .= "<li><a href=SetupTeams.php>Setup Teams for all Current Operations</a>\n";

    $txt .= "<p>";
    $txt .= "</ul>\n";
  }

  // *********************** Users  **************************************************************
  if ($x = SKTable('Any','Users')) {
    $txt .= $x;
    $txt .= "<ul>\n";
    $txt .= "<li><a href=Login.php?ACTION=NEWPASSWD>New Password</a>\n";
    if (Access('GM')) {
      $txt .= "<li><a href=AddUser.php>Add User</a>";
      $txt .= "<li><a href=ListUsers.php?FULL>List All Users</a>";
      $txt .= "<li><a href=GameUsers.php>Manage Users of Game</a> - Add them to the system first...";
    } else {
      $txt .= "<li><a href=ListUsers.php>List Group Users</a>";
    }
    $txt .= "</ul><p>\n";
  }



// *********************** GENERAL ADMIN *********************************************************
  if ($x = SKTable('Any','General Admin')) {
    $txt .= $x;
    $txt .= "<ul>\n";

    if (0 && Access('Player')) {
      $txt .= "<li><a href=AddBug.php>New Bug/Feature request</a>\n";
      $txt .= "<li><a href=ListBugs.php>List Bugs/Feature requests</a><p>\n";
    }

    if (Access('God')) {
      $xtra = '';
      if ($VERSION != ($GAMESYS['CurVersion'] ?? 0)) {
        foreach(glob("Schema/*.sql") as $sql) {
          if (filemtime($sql) > $GAMESYS['VersionDate']) {
            $xtra = " style='color:red;font-size:28;font-weight:bold;'";
            break;
          }
        }

        if ($xtra) {
          $txt .= "<li><a href=UpdateSystem $xtra>Update the system after pull</a> \n";
          $txt .= "<li class=smalltext><a href=UpdateSystem?MarkDone>Just mark done</a><p> \n";
        }

      }
      $txt .= "<li><a href=src1/Staff.php>Source 1</a>\n";

    }
    if (0 && Access('God')) $txt .= "<li><a href=TEmailProformas.php>EMail Proformas</a>";
    if (0 && Access('God')) $txt .= "<li><a href=AdminGuide.php>Admin Guide</a> \n";
//      $txt .= "<li><a href=BannerManage>Manage Banners</a> \n";
    $txt .= "<li><a href=GameData.php>Game Settings</a> \n";
    $txt .= "<li><a href=GameNew.php>Create New Game</a>\n";
    if (Access('God')) $txt .= "<li><a href=MasterData.php>Star Kingdoms System Data Settings</a> \n";
    $txt .= "</ul>\n";
  }

  $txt .= "</table></div>\n";

  echo "<h3>Jump to: ";
  $d = 0;
  foreach ($Heads as $Hd) {
    $hnam = preg_replace("/[^A-Za-z0-9]/", '', $Hd);
    $Hd = preg_replace("/ /",'&nbsp;',$Hd);
//    if ($d++) echo ", ";
    echo "&gt;&nbsp;<a href='#Staff$hnam'>$Hd</a> ";
  }
  echo "</h3><br>";
  echo $txt;
  dotail();
?>

