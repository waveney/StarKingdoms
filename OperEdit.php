<?php

  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("PlayerLib.php");
  include_once("SystemLib.php");
  include_once("ProjLib.php");
  include_once("OrgLib.php");

  global $FACTION,$GAME,$Project_Status,$Fields;


  $GM = Access('GM');
  if ($GM ) {
    if ($FACTION) {
      $Fid = $FACTION['id'];
      $Faction = Get_Faction($Fid);
    } else {
      $Fid = 0;
    }
  } else if (Access('Player')) {
    if (!$FACTION) {
      Error_Page("Sorry you need to be a GM or a Player to access this");
    }
    $Fid = $FACTION['id'];
    $Faction = &$FACTION;
  }

  dostaffhead("Edit an Operation");

  if (isset($_REQUEST['id'])) {
    $Oid = $_REQUEST['id'];
    $O = Get_Operation($Oid);
    if (!$GM && $O['GMLock']) fm_addall('READONLY');
  } else {
    echo "No Project given";
    dotail();
  }

//var_dump($_REQUEST);
  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
      case 'Delete':
        db_delete('Operations',$Oid);
        echo "<h1>Deleted</h1>";
        db_delete_cond('OperationTurn',"OperationId=$Oid");
        echo "<h2><a href=OpsDisp.php>Back to Operations Display</a>";
        if (Access('GM')) echo " , <a href=OperList.php?F=$Fid>Back to Operation List</a>";
        echo "</h2>\n";
        dotail();
        exit;
      case 'Abandon Project':
        if ($O['Status'] == 0) { // Never started
          db_delete('Operations',$Oid);
          echo "<h1>Deleted</h1>";
          db_delete_cond('OperationTurn',"OperationId=$Oid");
          echo "<h2><a href=OpsDisp.php>Back to Operations Display</a>";
          if (Access('GM')) echo " , <a href=OperList.php?F=$Fid>Back to Operation List</a>";
          echo "</h2>\n";
          dotail();
          exit;
        }
        $O['Status'] = 3;
        Put_Operation($O);
        echo "<h1>Abandoned</h1>";
        db_delete_cond('OperationTurn',"OperationId=$Oid AND TurnNumber>=" . $GAME['Turn']);
        echo "<h2><a href=OpsDisp.php>Back to Operations Display</a>";
        if (Access('GM')) echo " , <a href=OperList.php?F=$Fid>Back to Operation List</a>";
        echo "</h2>\n";
        dotail();
        exit;
      case 'Raise 1 Level': // Not for operations YET
        break;

      case 'Start 1 Turn Later':
        $O['TurnStart']++;
        Put_Operation($O);
/*        $PTs = Get_ProjectTurns($Oid);
        if ($PTs) foreach($PTs as $PT){
          $PT['TurnNumber']++;
          Put_ProjectTurn($PT);
        }*/

        break;

      case 'Start 1 Turn Earlier':
        $O['TurnStart']--;
        Put_Operation($O);
/*        $PTs = Get_ProjectTurns($Oid);
        if ($PTs) foreach($PTs as $PT){
          $PT['TurnNumber']--;
          Put_ProjectTurn($PT);
        }*/

        break;


    }
  }

  if ($O['TurnStart'] >= $GAME['Turn']) { // Future
    $when = 1;
  } elseif ($O['TurnEnd'] < $GAME['Turn']) { // Past
    $when = -1;
  } else {
    $when = 0; // In progress
  }

// var_dump($O);

  $FactionNames = Get_Faction_Names();
  $OpTypes = Get_OpTypes();
  $OpCosts = Feature('OperationCosts');
  $OpRushs = Feature('OperationRushes');

  $OrgTypes = Get_OrgTypes();
  $OpTypeNames = NamesList($OpTypes);
  $Systems = Get_SystemRefs();
  $Orgs = Gen_Get_Cond('Organisations',"Whose=$Fid");
//var_dump($Orgs,$Fid);
  $OrgNames = NamesList($Orgs);

  // Past is frozen unless GM
  // Future changeble by all
  // Player can cancel unless complete
  // God can delete


  echo "<form method=post action=OperEdit.php><table border>";
  Register_Autoupdate("Operations",$Oid);
  echo fm_hidden('id',$Oid);
  $PProps = $OpTypes[$O['Type']]['Props'];

  if (Access('GM')) {
    echo "<tr><td>Operation Id:<td>$Oid<td>For<td>" . fm_select($FactionNames,$O,'Whose') . fm_number('Turn State', $O,'TurnState');
    echo "<tr><td>Organisation:<td>" . fm_select($OrgNames,$O,'OrgId') . "<td>Operation Type<td>" . fm_select($OpTypeNames,$O,'Type');
    echo "<tr>" . fm_text("Operation Name",$O,'Name',2);
    echo "<tr>" . fm_number('Level',$O,'Level') . "<td>Status<td>" . fm_select($Project_Status,$O,'Status');
    echo "<td class=NotSide>" . fm_checkbox('GM Lock',$O,'GMLock');
    echo "<tr>" . fm_number("Turn Start",$O,'TurnStart') . fm_number('Turn Ended', $O, 'TurnEnd');
    echo "<tr><td>Where:<td>" . fm_select($Systems,$O,'SystemId',1);
    echo "<td>" . fm_checkbox('GM Override',$O,'GMOverride') . " Set to override maxrush";
    echo "<tr>" . ($OpCosts?fm_number('Cost',$O,'Costs'):'') . fm_number('Prog Needed', $O,'ProgNeeded');
    echo "<tr>" . fm_number("Progress",$O,'Progress') . fm_number('Last Updated',$O,'LastUpdate');
    if ($PProps & OPER_TECH) {
      $Techs = Get_Techs();
      $TechNames = NamesList($Techs);

      echo "<tr>" . fm_select($TechNames,$O,'Para1') . fm_number1('Level',$O,'Para2');
    } else if ($PProps & OPER_SOCP) {
      $SocPs = Get_SocialP($O['Para1']);
      echo "<tr><td>Social Principe:<td>" . $SocPs['Principle'] . " - Edit via Richard if needed";
      if (Access('God')) echo "<tr>" . fm_number('Para1',$O,'Para1');
    } else if ($PProps & OPER_CIVILISED) {
      array_unshift($Fields,'');
      echo "<tr><td>Science Points:<td>" . fm_select($Fields,$O,'Para1');
    } else if ($PProps & OPER_MONEY) {
      echo "<tr>" . fm_number('Credits',$O,'Para1');
    } else if ($PProps & OPER_ANOMALY) {
      $Anom = Gen_Get('Anomalies', $O['Para1']);
      echo "<tr>" . fm_number('Anomaly',$O,'Para1')  . "<td>" . $Anom['Name'];

    }

    if ($PProps & OPER_DESC) {
      echo "<tr>" . fm_text('Description',$O,'Description',6);
    }
    echo "<tr>" . fm_textarea('Notes',$O,'Notes',8,2);

  } else { // Player TODO Testing
    echo "<tr><td>Operation Type<td>" . $OpTypes[$O['Type']]['Name'];
    echo "<tr><td>Organisation:<td>" . $OrgNames[$O['Organisation']];
    echo "<tr>" . fm_text("Operation Name",$O,'Name',2);

    echo "<tr>" . fm_number('Level',$O,'Level') . "<td>Status<td>" . ($Project_Status[$O['Status']]);
    echo "<tr>" . (($when > 0)?fm_number("Turn Start",$O,'TurnStart'): "<td>Started Turn" . $O['TurnStart']);
    if ($when <0) echo "<td>Finished Turn" . $O['TurnEnd'];
    echo "<tr><td>Where:<td>" . $Systems[$O['SystemId']];

    echo "<tr>" . ($OpCosts?"<td>Cost:<td>" . $O['Costs']:'') . "<td>Progress needed:<td>" . $O['ProgNeeded'];
    echo "<tr><td>Progress:<td>" . $O['Progress'];
    if ($PProps & OPER_TECH) {
      $Tech = Get_Tech($O['Para1']);
      echo "<tr><td>Tech:<td>" . $Tech['Name'] . "<td>Level: " . $O['Para2'];
    } else if ($PProps & OPER_SOCP) {
      $SP = Get_SocialP($O['Para1']);
      echo "<tr><td>Social Principe:<td>" . $SP['Principle'];
    } else if ($PProps & OPER_CIVILISED) {
      echo "<tr><td>Science Points:<td>" . $Fields[$O['Para1']-1];
    } else if ($PProps & OPER_MONEY) {
      echo "<tr><td>Credits:<td>" . Credit() . $O['Para1'];
    }
    echo "<tr>" . fm_textarea('Notes',$O,'Notes',8,2);
  }

  if (Access('God')) echo "</tbody><tfoot><tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea>";

  echo "</table><h2>";
  echo fm_submit("Ignore","Ignore",0," hidden");
  if ($when >=0) echo fm_submit("ACTION",'Abandon Operation',0) . " ";
  if ($when > 0 || $GM )  {
    echo fm_submit("ACTION",'Delete',0) . " ";
  /*
    if (($PProps & 128) && ($P['ThingType']>0)) {
      if (($P['Type'] == 1) && ($P['Level'] >= $DistTypes[$P['ThingType']]['MaxNum'] )) {
        if ($GM) echo "Not allowed (GM only): <input type=submit name=ACTION value='Raise 1 Level'>";
      } else {
        echo fm_submit("ACTION",'Raise 1 Level',0);
      }
    }*/
  }
  echo "</h2>";
  echo "</form>";

  echo "<h2><a href=OpsDisp.php>Goto to Operations Display</a>";
  if (Access('GM')) echo " , <a href=OperList.php?F=$Fid>Goto to Operations List</a>";
  echo "</h2>\n";

  dotail();

