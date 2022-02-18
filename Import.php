<?php
require __DIR__ . '/vendor/autoload.php';


/*
 * We need to get a Google_Client object first to handle auth and api calls, etc.
 */
$client = new \Google_Client();
$client->setApplicationName('My PHP App');
$client->setScopes([\Google_Service_Sheets::SPREADSHEETS]);
$client->setAccessType('offline');
$client->setAuthConfig("cache/starkingdoms-ca1278acb7bb.json");

$sheets = new \Google_Service_Sheets($client);

$Factions = ['181ISkhyn218Q6-3bYfZLFYXf7q7f2uPh8GqP6MlHIQ4', // Blank
             '1hoT-rzRxAyKxqUbSkef8pSJ9O3S6GbrkMFCsWm_FQ7s', // Zabania
             '1-U9hdUP7IOfKXKm4FPG7r4kBCBFGsF78seEbGxgdyJI', // Wombles
             '114-XBfOgYYqRoPYPmPNqtqiTX0zr9OegC4xpMAj03-A', // Fongari
             '1MGtwAguxFUxF74i4FN7Yk2tHDIB0xSQ_0F2WtXB9g_M', // Haxan
             '1Ocnth43LRBUZ0YA04DISFax4OdxuOJPPm4gLoZn2jvM', // Ratfolk
             '1vlr7GhkC28Bye4k6px7COMgqP5S6Yy2nyUBLF9qI8dA',  // Sedrana
             '1q1IBZUNndSFa8MI9BUjuOAu_KGfTSAyDgpaWNRmyKoM', // Arachni
             '1MyYTJKms6WRRktiYkSKKAvfEdiOraZvmoiv1HYRnkV8', // Kalipolis
             '1SMMNBY_jZDoIUoMeLjqguJRtVUrRmIqShRU2wG8ih3A']; // Mammots

$SheetIds = ['Faction'=> 0, 'Setup'=> 1104884901, 'Main'=>1067465833, 'Colony1'=>1459791190, 'Colony2'=>1000668192, 'Colony3'=>1470733151,
             'Ships'=> 272145940, 'Armies'=>1086939544, 'Agents'=>1573267898, 'Techs'=>1264686687, 'Economy'=>1568340317];

  $StagNames = [];
  foreach ($SheetIds as $Name=>$Ssid) $StagNames[] = $Name;             
           
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("PlayerLib.php");
  include_once("SystemLib.php");
  include_once("ProjLib.php");
  include_once("HomesLib.php");

  global $db,$GAME;

  dostaffhead("Import");
  if (isset($_REQUEST['F'])) {
  $Fid = $_REQUEST['F'];  
  $F = Get_Faction($Fid);

  $Stage = $StagNames[$_REQUEST['S']];
  $SheetId = $SheetIds[$Stage];
    
  $SpreadsheetID = $Factions[$Fid];
  
  $CF = fopen("cache/Faction$Fid","r");
  $SheetName = "Fred";
  while ($line = fgets($CF)) {
    if (preg_match("/^$SheetId - (.*)/",$line,$match)) {
      $SheetName = $match[1];
      break;
    }
  }
  
  if ($SheetName == "Fred") {
    echo "Cant find sheet name<p>";
    exit;
  }
  $Turn = $GAME['Turn'];  
  $rows = ($sheets->spreadsheets_values->get($SpreadsheetID,"'$SheetName'!A1:Z99"))['values'];

//var_dump($rows);echo "<P>";
//exit;

  switch ($Stage) {
  case 'Faction':

    $PTs = Get_PlanetTypes();
    $F['Trait1'] = $rows[5][1];
    $F['Trait1Text'] = $rows[6][1];
    $F['Trait2'] = $rows[7][1];
    $F['Trait2Text'] = $rows[8][1];
    $F['Trait3'] = $rows[9][1];
    $F['Trait3Text'] = $rows[10][1];
    $Bio = 0;
    foreach($PTs as $PT) if ($PT['Name'] == $rows[14][1]) {
      $Bio = $PT['id'];
      break;
    }
    $F['BioSphere'] = $Bio;
    Put_Faction($F);
    echo "Done Faction data for " . $F['Name'] . "<br>";
    break;
  
  case 'Setup': 
    echo "No import needed<p>";
    break;
  
  case 'Main':
  case 'Colony1':
  case 'Colony2':
  case 'Colony3':
    $Hid = $_REQUEST['H'];
    $H = Get_ProjectHome($Hid);
    if (empty($H['id'])) {
      echo "Home not known<p>";
      break;
    }
    
    // Sort out Districts
    $Dtypes = [['Academic',5,16],['Military',2,30],['Shipyard',3,44],['Intelligence',4,58]];
    $Commerce = [76,10];
    $Others = [76,11];

    $PlanetCons = 3;
    
    $Dists = Get_DistrictsH($Hid);
    foreach ($Dtypes as $D) {
      if (empty($Dists[$D[1]])) {
        $ND = ['HostType'=>$H['ThingType'], 'HostId'=>$H['ThingId'], 'Type'=>$D[1], 'GameId'=>$GAME['id'], 'Number'=>$rows[$D[2]+$Turn-1][1]];
        Put_District($ND);
        echo "Created " . $D[0] . " districts " . $ND['Number'] . "<br>";
      } else {
        $OD = $Dists[$D[1]];
        $OD['Number'] = $rows[$D[2]+$Turn-1][1];
        Put_District($OD);
        echo "Updated  " . $D[0] . " districts " . $OD['Number'] . "<br>";
      }
    }
    if (empty($Dists[1])) {
        $ND = ['HostType'=>$H['ThingType'], 'HostId'=>$H['ThingId'], 'Type'=>1, 'GameId'=>$GAME['id'], 'Number'=>$rows[$Commerce[0]+$Turn-1][$Commerce[1]]];
        Put_District($ND);
        echo "Created Commerce districts " . $ND['Number'] . "<br>";
      } else {
        $OD = $Dists[1];
        $OD['Number'] = $rows[$Commerce[0]+$Turn-1][$Commerce[1]];
        Put_District($OD);
        echo "Updated  Commerce districts " . $OD['Number'] . "<br>";
      }
    
    if (!empty($rows[$Others[0]][$Others[1]])) {
      echo "Need to fix " . $rows[$Others[0]][$Others[1]] . " manually<br>";
    }
    
    echo "Projects to be done<p>";
    
    $Dtypes[] = ['Construction',-1,2];
    
    foreach ($Dtypes as $DT) {
      if ($rows[$DT[2]+$Turn-1][9] == 'Ongoing' && (empty($rows[$DT[2]+$Turn][2]) || $rows[$DT[2]+$Turn][2] == $rows[$DT[2]+$Turn-1][2]) ) {  // Ongoing Project
        // Work back to start of project
        $StartTurn = $Turn-1;
        while (empty($rows[$DT[2]+$StartTurn][2]) || $rows[$DT[2]+$StartTurn][2] == $rows[$DT[2]+$StartTurn-1][2]) {
          if ($StartTurn <= 1) {
            echo "Can't Find start of project type " . $DT[0] . "<br>\n";
            break;
          }
          $StartTurn--;
        }
        $ProName = $rows[$DT[2]+$StartTurn][2];
        $ProLvl = $rows[$DT[2]+$StartTurn][3];
        preg_match("/(\d*)\/(\d*)/", $rows[$DT[2]+$Turn-1][6],$match);
        
        
        $Pro = ['FactionId'=>$Fid, 'Type'=>30, 'Level'=>$ProLvl, 'Name'=>$ProName, 'TurnStart'=>$StartTurn, 'Home'=>$Hid, 'Status'=>1, 'ProgNeeded'=>$match[2],
                'Progress'=>$match[1]];
        $pid = Put_Project($Pro);
        echo "Project $ProName setup needs <a href=ProjEdit.php?id=$pid>editing</a> for type and things<br>\n";
      } else {
        $Pro = ['Name'=>'No Project', 'id' => -1];
      }
      
      for($tturn = $Turn; $tturn<=10; $tturn++) {
        $R = $rows[$DT[2]+$tturn];
        if (!empty($R[2]) && $R[2] != $rows[$DT[2]+$tturn-1]) { // New project
          $ProLvl = $R[3];
          $ProName = $R[2];
          $pc = Proj_Costs($ProLvl);        
          $Pro = ['FactionId'=>$Fid, 'Type'=>30, 'Level'=>$ProLvl, 'Name'=>$R[2], 'TurnStart'=>$tturn, 'Home'=>$Hid, 'Status'=>0, 'ProgNeeded'=>$pc[0],
                'Cost'=>$pc[1]];
          $pid = Put_Project($Pro);
          echo "Project $ProName setup needs <a href=ProjEdit.php?id=$pid>editing</a> for type and things<br>\n";       
        }
        if (!empty($R[8])) {
          $pt = ['ProjectId'=>$pid, 'TurnNumber'=>$tturn, 'Rush'=>$R[8]];
          Put_ProjectTurn($pt);
        }
      }
    }
    break;
    //Now sort projects
    
    /* scan for next project
      - all past - ignore
      - Current - work out properties and setup as they are and state
      - future - work out properties and setup for future
    */
  
  case 'Ships':
    $Things = Get_Things($Fid);
    for ($row=4; $row<30; $row++){
      $R = $rows[$row];

      if (empty($R[2])) { echo ""; continue; };

      $Tname = $R[0];
      foreach($Things as $T) {
        if ($T['Name'] == $Tname) { echo "Already Loaded "; continue 2;}
      }
      $Type = 1;
      switch ($R[2]) {
        case 'Military':
          $Type =1;
          break;
        case 'Civilan':
          $Type = 3;
          break;
        case 'Support':
          $Type = 2;
      }

      $BuildState = 0;
      switch ($R[12]) {
      case 'Building':
        $BuildState = 1;
        break;
      case 'Shakedown':
        $BuildState = 2;
        break;
      case 'Complete':
        $BuildState = 3;
        break;
      case 'Ex':
      case 'Ex ship':
        $BuildState = 4;
        break;
      default:
        $BuildState = 0;
      } 

      $Loc = 0;
      if (!empty($R[15])) {
        $Sys = Get_SystemR($R[15]);
        if ($Sys) $Loc = $Sys['id'];
      }
      $T = ['GameId'=>$GAME['id'], 'Type'=>$Type, 'Level'=>$R[3], 'SystemId'=>$Loc, 'Name'=>$Tname, 'Class'=>$R[1], 'Whose'=>$Fid, 'Notes'=>(empty($R[17])?'':$R[17]), 
            'MaxModules'=> $R[4], 'BuildState'=>$BuildState, 'Orders'=>(empty($R[14])?'':$R[14]), 'Sensors'=>$R[8]];

      $Tid = Put_Thing($T);
      if ($Loc == 0 && ($BuildState > 0 || $BuildState <4)) {
        echo "Ship <a href=ThingEdit.php?id=$Tid>$Tname</a> has an unknown location<br>\n";
      }
      // Modules
      if ($R[6]) {
        $M = ['ThingId'=>$Tid, 'Type'=>1, 'Number'=>$R[6]];
        Put_Module($M);
      }
      if ($R[7]) {
        $M = ['ThingId'=>$Tid, 'Type'=>2, 'Number'=>$R[7]];
        Put_Module($M);
      }
      if ($R[8]) {
        $M = ['ThingId'=>$Tid, 'Type'=>4, 'Number'=>$R[8]];
        Put_Module($M);
      }
      if ($R[9]) {
        $M = ['ThingId'=>$Tid, 'Type'=>5, 'Number'=>$R[9]];
        Put_Module($M);
      }

      if ($R[10] || $R[11]) {
        echo "Ship <a href=ThingEdit.php?id=$Tid>$Tname</a> has other modules: " . $R[10] . " - " . $R[11] . "<br>\n";  
        $T['GM_Notes'] = $R[10] . " - " . $R[11];   
      }

      Calc_Scanners($T);
      RefitRepair($T);
      echo "Imported ship $Tname<p>";
    }
    break;
  
  case 'Armies':
    $Things = Get_Things($Fid);
    for ($row=4; $row<30; $row++){
      $R = $rows[$row];

      if (empty($R[2])) { echo ""; continue; };

      $Tname = $R[0];
      foreach($Things as $T) {
        if ($T['Name'] == $Tname) { echo "Already Loaded $Tname"; continue 2;}
      }

      $BuildState = 0;
      switch ($R[9]) {
      case 'Building':
        $BuildState = 1;
        break;
      case 'Shakedown':
        $BuildState = 2;
        break;
      case 'Complete':
        $BuildState = 3;
        break;
      case 'Ex':
      case 'Ex Army':
        $BuildState = 4;
        break;
      default:
        $BuildState = 0;
      } 

      $Loc = 0;
      if (!empty($R[11])) {
        $Sys = Get_SystemR($R[11]);
        if ($Sys) $Loc = $Sys['id'];
      }
      $T = ['GameId'=>$GAME['id'], 'Type'=>4, 'Level'=>$R[2], 'SystemId'=>$Loc, 'Name'=>$Tname, 'Class'=>$R[1], 'Whose'=>$Fid, 'Notes'=>(empty($R[13])?'':$R[13]), 
            'MaxModules'=> $R[3], 'BuildState'=>$BuildState ];

      $Tid = Put_Thing($T);
      if ($Loc == 0 && ($BuildState > 0 || $BuildState <4)) {
        echo "Army <a href=ThingEdit.php?id=$Tid>$Tname</a> has an unknown location<br>\n";
      }
      // Modules
      if ($R[5]) {
        $M = ['ThingId'=>$Tid, 'Type'=>7, 'Number'=>$R[5]];
        Put_Module($M);
      }
      if ($R[6]) {
        $M = ['ThingId'=>$Tid, 'Type'=>6, 'Number'=>$R[6]];
        Put_Module($M);
      }

      if (!empty($R[7]) || !empty($R[8])) {
        echo "Army <a href=ThingEdit.php?id=$Tid>$Tname</a> has other modules: " . $R[7] . " - " . $R[8] . "<br>\n";  
        $T['GM_Notes'] = $R[7] . " - " . $R[8];   
      }

      Calc_Scanners($T);
      RefitRepair($T);
      echo "Imported Army $Tname<p>";
    }
    break;
  
  
  case 'Agents':
    $Things = Get_Things($Fid);
    for ($row=4; $row<30; $row++){
      $R = $rows[$row];

      if (empty($R[2])) { echo ""; continue; };

      $Tname = $R[0];
      foreach($Things as $T) {
        if ($T['Name'] == $Tname) { echo "Already Loaded $Tname"; continue 2;}
      }

      $BuildState = 0;
      switch ($R[4]) {
      case 'Building':
        $BuildState = 1;
        break;
      case 'Shakedown':
        $BuildState = 2;
        break;
      case 'Complete':
        $BuildState = 3;
        break;
      case 'Ex':
      case 'Ex Agent':
        $BuildState = 4;
        break;
      default:
        $BuildState = 0;
      } 

      $Loc = 0;
      if (!empty($R[6])) {
        $Sys = Get_SystemR($R[6]);
        if ($Sys) $Loc = $Sys['id'];
      }
      $T = ['GameId'=>$GAME['id'], 'Type'=>5, 'Level'=>$R[2], 'SystemId'=>$Loc, 'Name'=>$Tname, 'Class'=>$R[1], 'Whose'=>$Fid, 'Notes'=>(empty($R[13])?'':$R[13]), 
            'Gadgets'=> $R[3], 'BuildState'=>$BuildState ];

      $Tid = Put_Thing($T);
      if ($Loc == 0 && ($BuildState > 0 || $BuildState <4)) {
        echo "Agent <a href=ThingEdit.php?id=$Tid>$Tname</a> has an unknown location<br>\n";
      }
      echo "Imported Army $Tname<p>";
    }
    break;
    
  case 'Techs':
    $Techs = Get_Techs();
    for($row = 2; $row<75; $row++) {
      if (empty($rows[$row][5])) continue;
      $techname = trim($rows[$row][1]);
//      if ($techname == 'Sublight Travel') $techname = 
      $techhave = $rows[$row][5];
      if (empty($techname) || $techname == "Technology" || $techhave == 'FALSE') {
//        echo "Skipping $techname<br>";
        continue;
      }
      foreach ($Techs as $T) {
        if ($T['Name'] == $techname) {
          $FT = Get_Faction_TechFT($Fid,$T['id']);
          $FT['Level'] = (is_numeric($techhave)?$techhave: ($techhave == 'TRUE'?1:0)) ;
          Put_Faction_Tech($FT);
          echo "Have found $techname<br>";
          continue 2;
        }
      }
      echo "Could not find $techname <p>";
    }
    
    echo "Done Tech import for " . $F['Name'] . "<br>";
    break;
  
  case 'Economy':
    echo "The Only thing this does is copy over the Credits, do outposts and science points manually.</p>";
    $F['Credits'] = $rows[3+$Turn][2];
  
    Put_Faction($F);
  }
  
  }
    $Facts = Get_Faction_Names();
    
  echo "<h1>Import from a spreadsheet</h1>";
  echo "You must import ships, armies and agents BEFORE doing Worlds<p>\n";
  echo "<form method=post action=Import.php>";
  echo "Faction: " . fm_select($Facts,$_REQUEST,'F') . "<br>\n";
  echo "Stage: "  . fm_select($StagNames,$_REQUEST,'S') . "<br>\n";
  echo "For World/Colonies Home id: " . fm_number('',$_REQUEST,'H') . "<br>\n";
  echo "<input type=submit name=ACTION value=Import>\n<p>";
  
  dotail();
?>

