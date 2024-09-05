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
    A_Check('GM');
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
  }
  A_Check('Player');
//  CheckFaction('WorldList',$Fid);

  if (!Access('GM') && $Faction['TurnState'] > 2) Player_Page();

  $FactNames = Get_Faction_Names();

  $Orgs = Gen_Get_Cond('Organisations',($GM?"GameId=$GAMEID":"Whose=$Fid"));
  $OrgTypes = Get_OrgTypes();
  $OrgTypeNames = [];
  foreach ($OrgTypes as $i=>$Ot) $OrgTypeNames[$i] = $Ot['Name'];
  $Facts = Get_Factions();
  if (UpdateMany('Organisations','Put_ThingType',$Orgs,1,'','','Name','','Properties'))
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
  if ($GM) echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Whose</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Offices</a>\n";
  echo "</thead><tbody>";

  foreach ($Orgs as $oi=>$O) {
    $Of = $O['Whose'];
    echo "<tr>";
    if ($GM) echo "<td>$oi";
    if ($GM) {
      echo "<td>" . fm_select($OrgTypeNames,$O,'OrgType',0,'',"OrgType$i");
    } else {
      echo "<td>" . $OrgTypeNames[$O['OrgType']];
    }
    echo fm_text1('',$O,'Name',4,'','',"Name$i");
    echo "<br>" . fm_basictextarea($O, 'Description',5,4,'',"Description$i");
    if (!isset($SocPs[$Of])) $SocPs[$Of] = SocPrinciples($Of);
    if ($GM) {
      echo "<br>Social Principle (Religious / Ideological Orgs only)" . fm_select($SocPs[$Of],$O,'SocialPrinciple',1,'',"SocialPrinciple$i");
    } else {
      if ($O['OrgType'] == 5) echo "<br>Social Principle (Religious / Ideological Orgs only): " . $SocPs[$Of];
    }
    if ($GM) echo "<td>" . fm_select($FactNames,$O,'Whose',1,'',"Whose$i");
    echo "<td>" . $O['OfficeCount'] . "<br><a href=OrgView.php?id=$oi>View</a>";
  }
  $O = [];
  echo "<tr><td>";
  if ($GM) echo "<td>";
  echo fm_select($OrgTypeNames,$O,'OrgType',0,'',"OrgType0");
  echo fm_text1('',$O,'Name0',4,'','placeholder="New Organisation Name"');
  echo "<br>" . fm_basictextarea($O, 'Description0',5,4,'placeholder="New Organisation Description"');
  if ($GM) {
    echo "Set up a Social Principle (if appropriate) after creating the Organisation";
  } else {
    if (!isset($SocPs[$Fid])) $SocPs[$Fid] = SocPrinciples($Fid);
    echo "<br>Social Priniple (Religious / Ideological only)" . fm_select($SocPs[$Fid],$O,'SocialPrinciple0');
  }
  if ($GM) {
    echo "<td>" . fm_select($FactNames,$O,'Whose0',1);
  } else {
    echo fm_hidden('Whose0',$Fid);
  }
  echo fm_hidden('GameId0',$GAMEID);
  echo "</table>";
  echo "<h2><input type=submit name=Update value='Add New Organisation'></h2>";

  if ($GM) echo "GM Note: The count of offices is reset next time <b>Rebuild list of Worlds and colonies</b> is run.<p>";

  echo "</form></div>";
  dotail();


