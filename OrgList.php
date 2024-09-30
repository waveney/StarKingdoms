<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("OrgLib.php");
  global $FACTION,$GAMEID;


  dostaffhead("List of Organisations",["js/ProjectTools.js"]);
  $Fid = 0;
  $xtra = '';
  if (Access('Player')) {
    if (!isset($FACTION['id'])) {
      if (!Access('GM') ) Error_Page("Sorry you need to be a GM or a Player to access this");
    } else {
      $Fid = $FACTION['id'];
      $Faction = &$FACTION;
    }
  }
  if ($GM = Access('GM') ) {
    Recalc_Offices();
    if (isset( $_REQUEST['F'])) {
      $Fid = $_REQUEST['F'];
    } else if (isset( $_REQUEST['f'])) {
      $Fid = $_REQUEST['f'];
    } else if (isset( $_REQUEST['id'])) {
      $Fid = $_REQUEST['id'];
    }
    if (isset($Fid)) $Faction = Get_Faction($Fid);
    if (isset($_REQUEST['FORCE'])) {
      $GM = 0;
    } else {
      if ($Fid) echo "<h2>GM: <a href=OrgList.php?id=$Fid&FORCE>This page in Player Mode</a></h2>";
    }

    if (isset($_REQUEST['ACTION']))
      switch ($_REQUEST['ACTION']) {
        case 'Delete':
          $id = $_REQUEST['i'];
          $Org = Gen_Get('Organisations',$id);
          $Org['GameId'] = - $GAMEID;
          $Org['Whose'] = - $Org['Whose'];
          Gen_Put('Organisations',$Org);
          echo "<h2>Organisations $id has been removed</h2>";
          break;
    }

  }
  A_Check('Player');
//  CheckFaction('WorldList',$Fid);
  $GM = Access('GM');

  if (!$GM && $Faction['TurnState'] > 2) Player_Page();

  $FactNames = Get_Faction_Names();

  $Orgs = Gen_Get_Cond('Organisations',"( GameId=$GAMEID " . ($GM?" ) ":" AND Whose=$Fid ) ORDER BY RelOrder DESC"));
  $OrgTypes = Get_OrgTypes();
  $OrgTypeNames[0] = $NewOrgs[0] = '';
  foreach ($OrgTypes as $i=>$Ot) {
    $OrgTypeNames[$i] = $Ot['Name'];
    if (!$GM && $Ot['Gate'] && !eval("return " . $Ot['Gate'] . ";" )) continue;
    $NewOrgs[$i] = $Ot['Name'];
  }



  $Facts = Get_Factions();
//  var_dump($Orgs);
  if (UpdateMany('Organisations','',$Orgs,1,'','','Name','','Properties','',':'))
    $Orgs = Gen_Get_Cond('Organisations',($GM?"GameId=$GAMEID":"Whose=$Fid"));
  $SocPs = [];

  echo "<h1>Organisations</h1>";
  echo "To add a new one, fill in name, description and org type at the bottom and click the <b>Add New Organisation</b> button<p>";
  echo "Click on the View link under the office count, to see location of all Offices and Branches<p>";
  Register_AutoUpdate('Organisations',0);
  echo "<form method=post>";
  $coln = 0;
  echo "<div class=tablecont><table id=indextable border width=100% style='min-width:1400px'>\n";
  echo "<thead><tr>";
  if ($GM) echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Id</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Type</a>\n";
  echo "<th colspan=4><a href=javascript:SortTable(" . $coln++ . ",'T')>Name and description</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Relative Order</a>\n";
  if ($GM) echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Whose</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Offices</a>\n";
  echo "</thead><tbody>";

  foreach ($Orgs as $i=>$O) {
    $Of = $O['Whose'];
    echo "<tr>" . fm_hidden("GameId:$i", $GAMEID);
    if ($GM) echo "<td>$i";
    if ($GM) {
      echo "<td>" . fm_select($OrgTypeNames,$O,'OrgType',1,'',"OrgType:$i");
    } else {
      echo "<td>" . $OrgTypeNames[$O['OrgType']];
    }
    echo fm_text1('',$O,'Name',4,'','',"Name:$i");
    echo "<br>" . fm_basictextarea($O, 'Description',5,4," style='width=70%'","Description:$i");
    if (!isset($SocPs[$Of])) $SocPs[$Of] = SocPrinciples($Of);
    if ($GM) {
      echo "<br>Social Principle (Religious / Ideological Orgs only)" . fm_select($SocPs[$Of],$O,'SocialPrinciple',1,'',"SocialPrinciple:$i");
    } else {
      if ($O['OrgType'] == 5) echo "<br>Social Principle (Religious / Ideological Orgs only): " . $SocPs[$Of];
    }
    echo fm_number1('',$O,'RelOrder','','',"RelOrder:$i");
    if ($GM) {
      echo "<td>" . fm_select($FactNames,$O,'Whose',1,'',"Whose:$i");
      echo "<br><a href=OrgList.php?ACTION=Delete&i=$i>Delete</a>";
    }
    echo "<td>" . $O['OfficeCount'] . "<br><a href=OrgView.php?id=$i>View</a>";
  }
  $O = [];
  echo "<tr><td>";
  if ($GM) echo "<td>";
  echo fm_select($NewOrgs,$O,'OrgType',0,'',"OrgType:0");
  echo fm_text1('',$O,'Name:0',4,'','placeholder="New Organisation Name"');
  echo "<br>" . fm_basictextarea($O, 'Description:0',5,4,"placeholder='New Organisation Description' style='width=70%'");
  if ($GM) {
    echo "Set up a Social Principle (if appropriate) after creating the Organisation";
  } else {
    if (!isset($SocPs[$Fid])) $SocPs[$Fid] = SocPrinciples($Fid);
    echo "<br>Social Priniple (Religious / Ideological only)" . fm_select($SocPs[$Fid],$O,'SocialPrinciple:0');
  }
  echo fm_number1('',$O,'RelOrder','','',"RelOrder:0");
  if ($GM) {
    echo "<td>" . fm_select($FactNames,$O,'Whose:0',1);
  } else {
    echo fm_hidden('Whose:0',$Fid);
  }
  echo fm_hidden('GameId:0',$GAMEID);
  if (Access('God')) echo "<tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea>";
  echo "</table>";
  echo "<h2><input type=submit name=Update value='Add New Organisation'></h2>";

  if ($GM) echo "GM Note: The count of offices is reset next time <b>Rebuild list of Worlds and colonies</b> is run.<p>";

  echo "</form></div>";
  dotail();


