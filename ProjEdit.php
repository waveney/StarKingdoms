<?php

  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("PlayerLib.php");
  include_once("SystemLib.php");
  include_once("ProjLib.php");

  global $FACTION,$GAME,$Project_Status,$Fields;
  dostaffhead("Edit a Project");

  if (isset($_REQUEST['id'])) {
    $Prid = $_REQUEST['id'];
    $P = Get_Project($Prid);
  } else {
    echo "No Project given";
    dotail();
  }

  $GM = Access('GM');
  if ($GM ) {
    A_Check('GM');
    $Fid = $_REQUEST['id'];
    $Faction = Get_Faction($Fid);
    if (isset($_REQUEST['FORCE'])) {
      $GM = 0;
    } else {
      echo "<h2><a href=ProjEdit.php?id=$Prid&FORCE>This page in player mode</a></h2>";
    }
  } else if (Access('Player')) {
    if (!$FACTION) {
      Error_Page("Sorry you need to be a GM or a Player to access this");
    }
    $Fid = $FACTION['id'];
    $Faction = &$FACTION;
  }
  if (!$GM && $P['GMLock']) fm_addall('READONLY');

//var_dump($_REQUEST);
  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
      case 'Delete':
        db_delete('Projects',$Prid);
        echo "<h1>Deleted</h1>";
        db_delete_cond('ProjectTurn',"ProjectId=$Prid");
        echo "<h2><a href=ProjDisp.php>Back to Project Display</a>";
        if (Access('GM')) echo " , <a href=ProjList.php?F=$Fid>Back to Project List</a>";
        echo "</h2>\n";
        dotail();
        exit;
      case 'Abandon Project':
        if ($P['Status'] == 0) { // Never started
          db_delete('Projects',$Prid);
          echo "<h1>Deleted</h1>";
          db_delete_cond('ProjectTurn',"ProjectId=$Prid");
          echo "<h2><a href=ProjDisp.php>Back to Project Display</a>";
        if (Access('GM')) echo " , <a href=ProjList.php?F=$Fid>Back to Project List</a>";
          echo "</h2>\n";
          dotail();
          exit;
        }
        $P['Status'] = 3;
        Put_Project($P);
        echo "<h1>Abandoned</h1>";
        db_delete_cond('ProjectTurn',"ProjectId=$Prid AND TurnNumber>=" . $GAME['Turn']);
        echo "<h2><a href=ProjDisp.php>Back to Project Display</a>";
        if (Access('GM')) echo " , <a href=ProjList.php?F=$Fid>Back to Project List</a>";
        echo "</h2>\n";
        dotail();
        exit;
      case 'Raise 1 Level':

        $DTs = Get_DistrictTypes();
        $OldLvl = $P['Level'];
        $P['Level'] ++;
        $Newlvl = $P['Level'];
        $pc = Proj_Costs($Newlvl);
        if ( ($P['Type'] == 1) && (Has_Trait($P['FactionId'],"On the Shoulders of Giants"))) {

          if ( $DTs[$P['ThingType']]['Name'] == 'Mining') {
            $pc[1] = 0; // Mining
          } else if ($Newlvl>1 ) {
            $HomeW = $FACTION['HomeWorld'];
            $World = Get_World($HomeW);
            if ($World['Home'] == $P['Home']) {
              $pc = Proj_Costs(max($Newlvl-1,1));
            }
          }
        }

        if (Has_Trait($P['FactionId'],"Military Society") && ($DTs[$P['ThingType']]['Name'] == 'Military')) $pc = Proj_Costs($OldLvl);

        $Costs = $pc[1];
        $ProgN = $pc[0];
        $P['Costs'] = $Costs;
        $P['ProgNeeded'] = $ProgN;
        $P['Name'] = preg_replace("/ $OldLvl /", " $Newlvl ",$P['Name'],1);
        Put_Project($P);
        break;

      case 'Start 1 Turn Later':
        $P['TurnStart']++;
        Put_Project($P);
        $PTs = Get_ProjectTurns($Prid);
        if ($PTs) foreach($PTs as $PT){
          $PT['TurnNumber']++;
          Put_ProjectTurn($PT);
        }

        break;

      case 'Start 1 Turn Earlier':
        $P['TurnStart']--;
        Put_Project($P);
        $PTs = Get_ProjectTurns($Prid);
        if ($PTs) foreach($PTs as $PT){
          $PT['TurnNumber']--;
          Put_ProjectTurn($PT);
        }

        break;

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
  $DistTypes = Get_DistrictTypes();
  $Techs = Get_Techs();
  $TechNames = Tech_Names($Techs);
  $ProjTypeNames = [];
  foreach ($ProjTypes as $PT) $ProjTypeNames[$PT['id']] = $PT['Name'];

  $H = Get_ProjectHome($P['Home']);

  if (!isset($H['ThingType'])) {
    $PH['Name'] = 'Bug Bug Bug';
    $Sid = 0;
  } else {
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
  }

  // Past is frozen unless GM
  // Future changeble by all
  // Player can cancel unless complete
  // God can delete


  echo "<form method=post action=ProjEdit.php><table border>";
  Register_Autoupdate("Projects",$Prid);
  echo fm_hidden('id',$Prid);
  $PProps = $ProjTypes[$P['Type']]['Props'];

  if ($GM) {
    echo "<tr><td>Project Id:<td>$Prid<td>For<td>" . fm_select($FactionNames,$P,'FactionId');
    echo "<tr><td>Project Type<td>" . fm_select($ProjTypeNames,$P,'Type');
    echo fm_text("Project Name",$P,'Name',2);
    echo "<tr>" . fm_number('Level',$P,'Level') . "<td>Status<td>" . fm_select($Project_Status,$P,'Status');
//    echo "<td class=NotSide>" . fm_checkbox('GM Lock',$P,'GMLock');
    echo "<tr>" . fm_number("Turn Start",$P,'TurnStart') . fm_number('Turn Ended', $P, 'TurnEnd');
    echo "<tr>" . fm_number("Where",$P,'Home') . "<td>" . $PH['Name'] . " in " . NameFind($System);
    echo "<td>" . fm_checkbox('GM Override',$P,'GMOverride') . " Set to override maxrush";
    echo "<tr>" . fm_number('Cost',$P,'Costs') . fm_number('Prog Needed', $P,'ProgNeeded') . fm_number('DType',$P,'DType');
    echo "<tr>" . fm_number("Progress",$P,'Progress') . fm_number('Last Updated',$P,'LastUpdate');
    if ($PProps &2) {
      if ($P['ThingId']) {
        $Thing = Get_Thing($P['ThingId']);
        echo "<tr>" . fm_number('Thing',$P,'ThingId') . "<td><b><a href=ThingEdit.php?id=" . $P['ThingId'] . ">" . $Thing['Name'] . "</a></b>";
      } else {
        echo "<tr>" . fm_number('Thing',$P,'ThingId');
      }
      if ($PProps & 4) {
        if ($P['ThingId2']) {
          $Thing2 = Get_Thing($P['ThingId2']);
          echo fm_number('Thing 2',$P,'ThingId2') . "<td><a href=ThingEdit.php?id=" . $P['ThingId2'] . ">" . $Thing2['Name'] . "</a>";
        } else {
          echo fm_number('Thing 2',$P,'ThingId2');
        }
      }
    } else if (1 || $P['ThingType']) {
      echo "<tr>" . fm_number('ThingType',$P,'ThingType') . "<td>";
      if ($P['Type'] == 1) {
        if ($P['ThingType'] < 0) {// Office
          $OTypes = Get_OrgTypes();
          $OfficeTypeN = [];
          foreach ($OTypes as $oi=>$O) $OfficeTypeN[-$oi] = $O['Name'];
          echo fm_select($OfficeTypeN,$P,'ThingType');
        } else {
          echo fm_select($DistTypeN,$P,'ThingType');
        }
      } else {
        echo fm_select($TechNames, $P, 'ThingType'). "<td>" . $Fields[($Techs[$P['ThingType']]['Cat']??4)];

      }
      if ($PProps & 8) {
        echo fm_select($FactionNames,$P,'ThingId');
      }
    }
    echo "<tr>" . fm_textarea('Notes',$P,'Notes',8,2);

  } else { // Player TODO Testing
    echo "<tr><td>Project Type<td>" . $ProjTypes[$P['Type']]['Name'];
    echo fm_text("Project Name",$P,'Name',2);

    echo "<tr><td>Level:<td>" . $P['Level'] . "<td>Status<td>" . ($Project_Status[$P['Status']]);
    echo "<tr>" . (($when > 0)?fm_number("Turn Start",$P,'TurnStart','',"min=".$GAME['Turn']): "<td>Started Turn" . $P['TurnStart']);
    if ($when <0) echo "<td>Finished Turn" . $P['TurnEnd'];
    echo "<tr><td>Where:<td>" . $PH['Name'] . " in " . NameFind($System);
    echo "<tr><td>Cost:<td>" . $P['Costs'] . "<td>Progress needed:<td>" . $P['ProgNeeded'];
    echo "<tr><td>Progress:<td>" . $P['Progress'];
    if ($P['ThingId'] ) {
      $Thing = Get_Thing($P['ThingId']);
      if ($GM) {
        echo "<td><a href=ThingEdit.php?id=" . $P['ThingId'] . ">" . $Thing['Name'] . "</a>"; // May need Tweek for player edit
      } else {
        echo "<td><a href=ThingPlan.php?id=" . $P['ThingId'] . ">" . $Thing['Name'] . "</a>";
      }
    } else if ($P['ThingType']) {
      echo "<td>";
      if (($P['Type']??0) == 1) {
        echo ($DistTypeN[$P['ThingType']]??'??');
      } else {
        echo ($TechNames[$P['ThingType']]??'??') . "<td>" . $Fields[$Techs[$P['ThingType']]['Cat']];

      }
      if ($PProps & 8) {
        echo " Recipient: " . $FactionNames[$P['ThingId']];
      }
    }
    echo "<tr>" . fm_textarea('Notes',$P,'Notes',8,2);
  }

  if (Access('God')) echo "</tbody><tfoot><tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea>";

  echo "</table><h2>";
  echo fm_submit("Ignore","Ignore",0," hidden");
  if ($when >=0) echo fm_submit("ACTION",'Abandon Project',0) . " ";
  if ($when > 0 || $GM )  {
    echo fm_submit("ACTION",'Delete',0) . " ";
    if (($PProps & 128) && ($P['ThingType']>0)) {
      if (($P['Type'] == 1) && ($P['Level'] >= $DistTypes[$P['ThingType']]['MaxNum'] )) {
        if ($GM) echo "Not allowed (GM only): <input type=submit name=ACTION value='Raise 1 Level'>";
      } else {
        echo fm_submit("ACTION",'Raise 1 Level',0);
      }
    }
    if ($P['TurnStart'] > $GAME['Turn']) echo fm_submit('ACTION','Start 1 Turn Earlier');
    echo fm_submit('ACTION','Start 1 Turn Later');
  }
  echo "</h2>";
  echo "</form>";

  echo "<h2><a href=ProjDisp.php>Goto to Project Display</a>";
  if (Access('GM')) echo " , <a href=ProjList.php?F=$Fid>Goto to Project List</a>";
  echo "</h2>\n";

  dotail();
?>

