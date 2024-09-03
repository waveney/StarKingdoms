<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("SystemLib.php");
  include_once("PlayerLib.php");
  include_once("ThingLib.php");


  global $FACTION,$GAMEID,$ARMY;

  define('STAGES',8);

  A_Check('Player');
  if (Access('Player')) {
    if (!$FACTION) {
      if (!Access('GM') ) Error_Page("Sorry you need to be a GM or a Player to access this");
    } else {
      $Fid = $FACTION['id'];
      $F = &$FACTION;
    }
  }
  if (Access('GM') ) {
    A_Check('GM');
    if (isset( $_REQUEST['F'])) {
      $Fid = $_REQUEST['F'];
    } else if (isset( $_REQUEST['f'])) {
      $Fid = $_REQUEST['f'];
    } else if (isset( $_REQUEST['id'])) {
      $Fid = $_REQUEST['id'];
    }
    if (isset($Fid)) $F = Get_Faction($Fid);
  }
  $GM = 0; //Access('GM');

  dostaffhead("Setup Faction",["js/dropzone.js","css/dropzone.css" ]);

  global $db, $GAME;


  echo "<h2>This page is for Faction setup only</h2>" .
     "Once the game is running, subsequent changes are made using various tools and by doing turns.<p>";

  echo "There a number of stages in the setup.  Once you have been through a stage you can normally backwards and forwards changing things.<p>";

  $Stage = $_REQUEST['STAGE']??1;


function SetupStage1() {
  global $F, $Fid, $GAME, $FACTION, $GAMEID, $ARMY, $PTs;
  $PTypes = Get_PlanetTypes();
  $DTypes = Get_DistrictTypes();
  $SCredits = Feature('StartCredits',450);

  echo "<h1>Stage 1/" . STAGES . " - Your Faction</h1><table border>";
  echo "<form method=post>";
  Register_AutoUpdate('Faction', $Fid);
  echo "<tr>" . fm_text('Faction Name',$F,'Name',2);
  echo "<tr>" . fm_text('Player Name',$F,'Player',2);

  $PTs = Get_PlanetTypeNames();
  $PTs[0] = 'None';
  echo "<tr><td >Native BioSphere<td colspan=2 >" . fm_select($PTs,$F,'Biosphere',1);

  echo "<tr>" . fm_text('Adjective Name',$F,'Adjective',2) . "<td>To refer to your ships etc rather than your faction name - optional";
  echo "<tr>" . fm_text("Trait 1 Name",$F,'Trait1',2). "<td>Short name that is unique";
  echo "<tr>" . fm_textarea('Description',$F,'Trait1Text',8,2);
  echo "<tr>" . fm_text("Trait 2 Name",$F,'Trait2',2). "<td>Short name that is unique";
  echo "<tr>" . fm_textarea('Description',$F,'Trait2Text',8,2);

  if ($F['Credits']??0 != $SCredits) {
    $F['Credits'] = $SCredits;
    Put_Faction($F);
  }

  echo "</table><p>";

  echo "<h2><a href=SetupFaction.php?STAGE=2>Forward to stage 2 - Technologies</a></h2>";

  dotail();
}


function SetupStage2() {
  global $F, $Fid, $GAME, $FACTION, $GAMEID, $ARMY, $PTs;
  $PTypes = Get_PlanetTypes();
  $DTypes = Get_DistrictTypes();

  echo "<form method=post>";


  $CoreTechs = Get_CoreTechs();
  $CTechs = Get_Faction_Techs($Fid);

  echo "<hr><h1>Stage 2/" . STAGES . " - Starting Technologies</h1>\n";
//  echo "Click on technologies name to Toggle showing the definition and examples\n<p>";
  echo "Raise the level of " . Feature('SetupNumberTechs', 2) . " techs - checking will be by GM's eyes<p>";

  $CTNs = [];
  Register_AutoUpdate('FactTech',$Fid);
//  var_dump($CTechs);
  foreach ($CoreTechs as $Ti=>$CT) {
//    var_dump($Ti, $CT['Name'],$CTechs[$Ti]);
    if (!isset($CTechs[$Ti])) {
      $Tec = ['FactionId'=> $Fid, 'Tech_Id'=>$Ti, 'Level' =>1];
      Put_Faction_Tech($Tec);
      $CTechs[$Ti] = $Tec;
//      var_dump($CT['Name'],$Tec);
    }
    Show_Tech($CT,$CTNs,$F,$CTechs,1,1,0,3);
  }

  echo "<h2><a href=SetupFaction.php?STAGE=1>Back to stage 1 - Your Faction</a> - " .
       "<a href=SetupFaction.php?STAGE=3>Forward to stage 3 - Your World</a></h2>";
  dotail();
}


function SetupStage3() {
  global $F, $Fid, $GAME, $FACTION, $GAMEID, $ARMY, $PTs;
  $PTypes = Get_PlanetTypes();
  $DTypes = Get_DistrictTypes();

  echo "<form method=post>";


  echo "<hr><h1>Stage 3/" . STAGES . " - Your Home World</h1>";

  // Find Homeworld(s) if multiple find one with highest importance
//var_dump($F);
  if ($F['HomeWorld'] > 0 ) {
    $Home = $F['HomeWorld'];
    $World = Get_World($Home);
    $ThingType = $World['ThingType'];
    $ThingId = $World['ThingId'];
// var_dump("AA",$Home,$World);
    switch ($ThingType) {
      case 1:
        $Place = Get_Planet($ThingId);
        $Ds = Get_DistrictsP($ThingId);
 //       var_dump($Home,$World,$Place);
        $Table = 'Planets';
        $Put_Fn = 'Put_Planet';

        $SysId = $Place['SystemId'];
        $System = Get_System($SysId);
        break;
      case 2:
        $Place = Get_Moon($ThingId);
        $Ds = Get_DistrictsM($ThingId);
        $Table = 'Moons';
        $Put_Fn = 'Put_Moon';

        $P = Get_Planet($Place['PlanetId']);
        $SysId = $P['SystemId'];
        $System = Get_System($SysId);

        break;
      case 3:
        $Place = Get_Thing($ThingId);
        $Ds = Get_DistrictsT($ThingId);
        $Table = 'Things';
        $Put_Fn = 'Put_Thing';

        $SysId = $Place['SystemId'];
        $System = Get_System($SysId);
        break;
    }
  } else {
    $HomeList = [];
    $Systems = Get_Systems();
    foreach ($Systems as $Sid=>$N) {
      if (($N['Control'] == $Fid) || ($N['HistoricalControl'] == $Fid)) $HomeList[] = $N['id'];
    }
 //   var_dump($HomeList);
    if (empty($HomeList)) {
      echo "<h2 class=Err>The system can't find your home world - contact the GMs</h2>";
      dotail();
    } else if (count($HomeList) > 1 ) {
      echo "<h2 class=Err>The system can't find a single home world - contact the GMs</h2>";

      if (Access('GM')) {


      }

      dotail();
    }

    $HomeSys = $HomeList[0];
    $Ps = Get_Planets($HomeSys);
    $Found = 0;
    $Habs = [];
    $PrefHabs = [];
    $ThingType = 0;
    $Sref = $N['Ref'];
    $Pidx = 0;
    foreach ($Ps as $Pid=> $P) {
      $Pidx++;
      if ($P['Control'] == $Fid) {
        $ThingType = 1;
        $ThingId = $Pid;
        break;
      }
      if ($P['HistoricalControl'] == $Fid) {
        $ThingType = 1;
        $ThingId = $Pid;
        break;
      }
      if ($PTypes[$P['Type']]['Hospitable']) {
        $Habs[] = $Pid;
        if ($P['Type'] == $F['Biosphere']) $PrefHabs[] = $Pid;
      }
    }

    if ($ThingType == 0) {
      if (empty($Habs)) {
        echo "<h2 class=Err>The system $Sref can't find a Habitable Planet - contact the GMs</h2>";
        dotail();
      }
      if (count($Habs) > 1) {
//        var_dump($Habs);
        if (count($PrefHabs) == 1) {
          $ThingId = $PrefHabs[0];
        } else {
          echo "<h2 class=Err>The system $Sref has too many Planets - contact the GMs</h2>";
          dotail();
        }
      } else $ThingId = $Habs[0];
      $ThingType = 1;

    }
    // Note no treatment for Things - get those setup by hand
// var_dump("xx",$ThingType,$ThingId);
    if ($ThingType == 1) {
      $Place = Get_Planet($ThingId);
      $Table = 'Planets';
      $Put_Fn = 'Put_Planet';
      $SysId = $Place['SystemId'];
      $System = Get_System($SysId);

      $Place['Minerals'] = 3;
      $ThingType = 1;

      $PH = ['HostType'=>1, 'HostId' => $ThingId, 'GameId'=>$GAMEID, 'Whose'=>$Fid, 'SystemId' =>$HomeSys,
        'WithinSysLoc' => $Pidx*200, 'EconomyFactor' => 100];

      $PHnumber = Put_ProjectHome($PH);
      $Place['ProjHome'] = $PHnumber;

      $World = ['ThingType'=>1, 'ThingId' => $ThingId, 'GameId'=>$GAMEID, 'FactionId'=>$Fid, 'Home' => $PHnumber, 'RelOrder' => 100,
        'Minerals' => 3];

      $Wnumber = Put_World($World);

      Put_Planet($Place);
      $F['HomeWorld'] = $Wnumber;
      Put_Faction($F);

    } else if ($ThingType == 2) { // Moon
      $Place = Get_Moon($ThingId);
      $Table = 'Moons';
      $Put_Fn = 'Put_Moon';
      $Plan = Get_Planet($Place['PlanetId']);

      $SysId = $Plan['SystemId'];
      $System = Get_System($SysId);

      $Place['Minerals'] = 3;
      $ThingType = 1;

      $PH = ['HostType'=>2, 'HostId' => $ThingId, 'GameId'=>$GAMEID, 'Whose'=>$Fid, 'SystemId' =>$HomeSys,
        'WithinSysLoc' => $Pidx*400, 'EconomyFactor' => 100];

      $PHnumber = Put_ProjectHome($PH);
      $Place['ProjHome'] = $PHnumber;

      $World = ['ThingType'=>2, 'ThingId' => $ThingId, 'GameId'=>$GAMEID, 'FactionId'=>$Fid, 'Home' => $PHnumber, 'RelOrder' => 100,
        'Minerals' => 3];

      $Wnumber = Put_World($World);

      Put_Planet($Place);
      $F['HomeWorld'] = $Wnumber;
      Put_Faction($F);

    }
  }

  $Ds = Get_DistrictsP($ThingId);
  if (empty($Ds)) { // Make standard disctricts
    foreach ($DTypes as $di=>$DT) if ($DT['Props']&1) {
      $D = ['HostType'=>$ThingType, 'HostId' => $ThingId, 'GameId'=>$GAMEID, 'Type'=>$di, 'Number' =>($DT['Name'] == 'Industrial'?2:1)];
      Put_District($D);
    }

    $Ds = Get_DistrictsP($ThingId);
  }

  echo "<table border>";
  Register_AutoUpdate('Planets',$ThingId);
  echo "<tr>" . fm_text('Planet Name', $Place,'Name',2);
  echo "<tr>" . fm_textarea('Description<br>(as seen by others)',$Place,'Description',5,2);
  echo "<tr>" . fm_text('Planetary Trait',$Place,'Trait1',2);
  echo "<tr>" . fm_textarea('Trait Description',$Place,'Trait1Desc',5,2);
  echo "<tr><td colspan=6>Districts: (Industial is 2, one other should be level 2 - checking of this is by GM eyes.";
  foreach ($Ds as $D) {
    $did = $D['id'];
    echo "<tr><td>" . $DTypes[$D['Type']]['Name'] . fm_number1('', $D,'Number', '',' class=Num3 min=0 max=5',"DistrictNumber-$did");
  };

/*
  if ($Offices) {
    foreach ($Offices as $O) {
      echo "<tr><td>Organisational Office:<td>" . fm_select($OtNames,$O,'Type');

  } else {
    echo "<tr><td>Add Organisational Office:<td>" ;
  }
*/
  echo "</table>";

  if ($ThingType <3 && $Place['Type'] != $F['Biosphere'])
    echo "<h2 class=Err>Your home's biosphere does not match your own.  If the error is the biosphere of your home world, contact the GMs</h2>";


  echo "<h2><a href=SetupFaction.php?STAGE=2>Back to stage 2 - Your Technologies</a> - " .
    "<a href=SetupFaction.php?STAGE=4>Forward to stage 4 - Social Principles</a></h2>";
  dotail();
}

function SetupStage4() {
  global $F, $Fid, $GAME, $FACTION, $GAMEID, $ARMY, $PTs;
  $PTypes = Get_PlanetTypes();
  $DTypes = Get_DistrictTypes();

  $Wid = $F['HomeWorld'];
  $World = Get_World($Wid);
  $Hid = $World['Home'];
  $Home = Get_ProjectHome($Hid);

  echo "<h1>Stage 4/" . STAGES . " - Social Principles</h1>";

  echo "<form method=post>";
  Register_AutoUpdate('SocialPrinciples',0);
  $SPs = Get_SocialPs($Wid);
  if (empty($SPs)) {
    for ($i=3;$i>0;$i--) {
      $S = ['World'=>$Wid,'Whose'=>$Fid,'Value'=>$i];
      Put_SocialP($S);
    }
    $SPs = Get_SocialPs($Wid);
  }
  echo "<table border>";

  foreach($SPs as $si=>$S) {
    echo "<tr><td>Adherence: " . $S['Value'] . fm_text('Principle',$S,'Principle',4,'','',"Principle:$si");
  }

  echo "</table>";

  echo "<h2><a href=SetupFaction.php?STAGE=3>Back to stage 4 - Your Home World</a> - " .
    "<a href=SetupFaction.php?STAGE=5>Forward to stage 5 - Your System</a></h2>";
  dotail();
}

function SetupStage5() {
  global $F, $Fid, $GAME, $FACTION, $GAMEID, $ARMY, $PTs;
  $PTypes = Get_PlanetTypes();
  $DTypes = Get_DistrictTypes();

  echo "<form method=post>";

  echo "<h1>Stage 4/" . STAGES . " - System Names</h1>";
//var_dump($F);
  $Wid = $F['HomeWorld'];
  if (empty($Wid) ) {
    echo "<h2 class=err>You need your homeworld setup first</h2>";
    dotail();
  }

  $World = Get_World($Wid);
//var_dump($World);
  $Hid = $World['Home'];
  $Home = Get_ProjectHome($Hid);
  $SysId = $Home['SystemId'];
  $System = Get_System($SysId);

  Register_AutoUpdate('Systems',$SysId);
  echo "<table border>";
  echo "<tr>" . fm_text('System Name', $System,'Name',2);
  echo "<tr>" . fm_text('Star Name',$System, 'StarName',2);
  if ($System['Mass2']>0) "<tr>" . fm_text('Companion Name',$System, 'StarName2',2);

  echo "</table><p>";

  echo "<h2><a href=SetupFaction.php?STAGE=4>Back to stage 4 - Social Principles</a> - " .
    "<a href=SetupFaction.php?STAGE=6>Forward to stage 6 - Office</a></h2>";
  dotail();
}

function SetupStage6() {
  global $F, $Fid, $GAME, $FACTION, $GAMEID, $ARMY, $PTs;
  $PTypes = Get_PlanetTypes();
  $DTypes = Get_DistrictTypes();

  echo "<form method=post>";

  echo "<h1>Stage 6/" . STAGES . " - Starting Office</h1>";
  $Wid = $F['HomeWorld'];
  $Offices = Get_Offices($Wid);

  if (!$Offices) {
    $Off = ['Type'=>1, 'World'=>$Wid, 'Whose'=>$Fid, 'Number'=>1];
    Put_Office($Off);
  } else if (count($Offices) == 1) {
    $Off = array_pop($Offices);
  } else {
    echo "This world has more than one office - can't be managed with this tool<p>";
    echo "<h2><a href=SetupFaction.php?STAGE=5>Back to stage 5 - System Name</a> - " .
      "<a href=SetupFaction.php?STAGE=7>Forward to stage 7 - Starting Things and oddments</a></h2>";
    dotail();

  }
  Register_AutoUpdate('Offices',$Off['id']);

  $OTypes = [];
  $OrgTypes = Get_OrgTypes();
  foreach ($OrgTypes as $oi=>$O) $OTypes[$oi] = $O['Name'];

  echo "Office Type: " . fm_select($OTypes,$Off,'Type') . "<p>\n";


  echo "<h2><a href=SetupFaction.php?STAGE=5>Back to stage 5 - System Name</a> - " .
    "<a href=SetupFaction.php?STAGE=7>Forward to stage 7 - Other Planets</a></h2>";
  dotail();
}

function SetupStage7() {
  global $F, $Fid, $GAME, $FACTION, $GAMEID, $ARMY, $PTs;
  $PTypes = Get_PlanetTypes();
  $DTypes = Get_DistrictTypes();

  echo "<form method=post>";

  echo "<h1>Stage 7/" . STAGES . " - Other Planets</h1>";

  $Wid = $FACTION['HomeWorld'];
  $World = Get_World($Wid);
  if ($World['ThingType'] == 1) {// Only Planets for now
    Register_AutoUpdate('Generic',0);

    $Home = Get_ProjectHome($World['Home']);
    $Planets = Get_Planets($Home['SystemId']);
    echo "<table>";
    foreach ($Planets as $pi=>$P) {
      echo "<tr><td>" . fm_text1('',$P,'Name',2,'','',"Planets:Name:$pi");
    }
    echo "</table>";

  } else {
    echo "None to name";
  }

  echo "<h2><a href=SetupFaction.php?STAGE=6>Back to stage 6 - Offices</a> - " .
    "<a href=SetupFaction.php?STAGE=8>Forward to stage 8 - Starting Things and oddments</a></h2>";
  dotail();
}

function SetupStage8() {
  global $F, $Fid, $GAME, $FACTION, $GAMEID, $ARMY, $PTs;
  $PTypes = Get_PlanetTypes();
  $DTypes = Get_DistrictTypes();

  echo "<form method=post>";

  echo "<h1>Stage 8/" . STAGES . " - Starting Ships and $ARMY" ."s</h1>";

  echo "Use the <a href=ThingPlan.php>Plan a Thing Tool</a> to plan them.  They will be created when the game starts.<p>";

  echo "Use the <a href=PThingList.php>List of Things</a> to see what you have planned.<p>";

  echo "<h2><a href=SetupFaction.php?STAGE=7>Back to stage 6 - Other Planets</a> - ";

  echo "<h1>Automated Setup is complete</h1>";
  echo "There will probably be discussions with the GMs about your traits and social principles.<p>";
  dotail();
}

  "SetupStage$Stage"();

  dotail();

// Social Principles
// Starting Ships -> Use New thing to make 2 planned L1 Ships and 2 L1 armies
// Starting Organisation
