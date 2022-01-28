<?php

  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("PlayerLib.php");
  include_once("SystemLib.php");
  include_once("ProjLib.php");
  
  global $FACTION,$GAME,$Project_Status;
  
  if (Access('GM') ) {
    A_Check('GM');
    $Fid = $_REQUEST['id'];
    $Faction = Get_Faction($Fid);
  } else if (Access('Player')) {
    if (!$FACTION) {
      Error_Page("Sorry you need to be a GM or a Player to access this");
    }
    $Fid = $FACTION['id'];
    $Faction = &$FACTION;
  }

  dostaffhead("Edit a Project");

  if (isset($_REQUEST['id'])) {
    $Prid = $_REQUEST['id'];
    $P = Get_Project($Prid);
  } else { 
    echo "No Project given";
    dotail();
  }

  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
      case 'Delete': 
        db_delete('Projects',$Prid);
        echo "<h1>Deleted</h1>";
        echo "<h2><a href=ProjDisp.php?id=$Fid>Back to Project Display</a> , <a href=ProjList.php?F=$Fid>Back to Project List</a></h2>\n";
        dotail();
        exit;
    }
  }

  if ($P['TurnStart'] >= $GAME['Turn']) { // Future
    $when = 1;
  } elseif ($P['TurnEnd'] < $GAME['Turn']) { // Past
    $when = -1;
  } else {
    $when = 0; // In progress
  }
  
// var_dump($P);

  $FactionNames = Get_Faction_Names();
  $ProjTypes = Get_ProjectTypes();
  $DistTypeN = Get_DistrictTypeNames();
  $Techs = Get_Techs();
  $TechNames = Tech_Names($Techs);
  $ProjTypeNames = [];
  foreach ($ProjTypes as $PT) $ProjTypeNames[$PT['id']] = $PT['Name'];
  
  $H = Get_ProjectHome($P['Home']);
 
//var_dump($H);
  switch ($H['ThingType']) {
    case 1: // Planet
      $PH = Get_Planet($H['ThingId']);
      $Dists = Get_DistrictsP($H['ThingId']);
      $Sid = $PH['SystemId'];
      break;
    case 2: // Moon
      $PH = Get_Moon($H['ThingId']);
      $Dists = Get_DistrictsM($H['ThingId']);
      $Plan = Get_Planet($PH['PlanetId']);
      $Sid = $Plan['SystemId'];
      break;
    case 3: // Thing
      $PH = Get_Thing($H['ThingId']);
      $Dists = Get_DistrictsT($H['ThingId']);
      $Sid = $PH['SystemId'];
      break;
    }
  $System = Get_System($Sid);

  // Past is frozen unless GM
  // Future changeble by all
  // Player can cancel unless complete
  // God can delete
  
  
  echo "<form method=post action=ProjEdit.php><table border>";
  Register_Autoupdate("Project",$Prid);
  echo fm_hidden('id',$Prid);
  
  if (Access('GM')) {
    echo "<tr><td>Project Id:<td>$Prid<td>For<td>" . fm_select($FactionNames,$P,'FactionId');
    echo "<tr><td>Project Type<td>" . fm_select($ProjTypeNames,$P,'Type');
    echo fm_text("Project Name",$P,'Name',2);
    echo "<tr>" . fm_number('Level',$P,'Level') . "<td>Status<td>" . fm_select($Project_Status,$P,'Status');
    echo "<tr>" . fm_number("Turn Start",$P,'TurnStart') . fm_number('Turn Ended', $P, 'TurnEnd');
    echo "<tr>" . fm_number("Where",$P,'Home') . "<td>" . $PH['Name'] . " in " . NameFind($System);
    echo "<tr>" . fm_number('Cost',$P,'Costs') . fm_number('Prog Needed', $P,'ProgNeeded');
    echo "<tr>" . fm_number("Progress",$P,'Progress') . fm_number('Last Updated',$P,'LastUpdate'); 
    if ($P['ThingId']) {
      $Thing = Get_Thing($P['ThingId']);
      echo fm_number('Thing',$P,'ThingId') . "<td><a href=ThingEdit.php?id=" . $P['ThingId'] . ">" . $Thing['Name'] . "</a>"; 
    } else if (1 || $P['ThingType']) {
      echo "<td>" . ($P['Type'] == 1 ? fm_select($DistTypeN,$P,'ThingType') : fm_select($TechNames, $P, 'ThingType') );
    }
    echo "<tr>" . fm_textarea('Notes',$P,'Notes',8,2);
  
  } else { // Player TODO Testing
    echo "<tr><td>Project Type<td>" . $ProjTypes[$P['Type']]['Name'];
    echo fm_text("Project Name",$P,'Name',2);
    
    echo "<tr>" . fm_number('Level',$P,'Level') . "<td>Status<td>" . ($Project_Status[$P['Status']]);
    echo "<tr>" . (($when > 0)?fm_number("Turn Start",$P,'TurnStart'): "<td>Started Turn" . $P['TurnStart']);
    if ($when <0) echo "<td>Finished Turn" . $P['TurnEnd'];
    echo "<tr><td>Where:<td>" . $PH['Name'] . " in " . NameFind($System);
    echo "<tr><td>Cost:<td>" . $P['Costs'] . "<td>Progress needed:<td>" . $P['ProgNeeded'];
    echo "<tr><td>Progress:<td>" . $P['Progress']; 
    if ($P['ThingId']) {
      $Thing = Get_Thing($P['ThingId']);
      echo "<td><a href=ThingEdit.php?id=" . $P['ThingId'] . ">" . $Thing['Name'] . "</a>"; // May need Tweek for player edit
    } else if ($P['ThingType']) {
      echo "<td>" . ($P['Type'] == 1 ? ($DistTypeN[$P['ThingType']]) : $TechNames[$P['ThingType']] );
    }
    echo "<tr>" . fm_textarea('Notes',$P,'Notes',8,2);
  }
      
  if (Access('God')) echo "<tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea>";
  
  echo "</table><h2>";
  if ($when >=0) echo "<input type=submit name=ACTION value=Cancel> ";
  if ($when > 0 || Access('GM') )  echo "<input type=submit name=ACTION value=Delete> ";
//  if ($when == 0 || Access('GM') )  echo "<input type=submit name=ACTION value=Suspend> ";
//  if ($P['Status'] == 4)  echo "<input type=submit name=ACTION value=Resume> ";
  echo "</h2>";
  echo "</form>";
  
  echo "<h2><a href=ProjDisp.php?id=$Fid>Back to Project Display</a> , <a href=ProjList.php?F=$Fid>Back to Project List</a></h2>\n";
  
  dotail();
?>
  
