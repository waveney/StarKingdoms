<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("OrgLib.php");
  include_once("vendor/erusev/parsedown/Parsedown.php");
  global $FACTION,$GAMEID;
  $Parsedown = new Parsedown();

// var_dump($_REQUEST);
  dostaffhead("List of Organisations",["js/ProjectTools.js"]);
  $Fid = 0;
  $xtra = '';
  if (Access('Player')) {
    if (!isset($FACTION['id'])) {
 //     var_dump($FACTION); exit;
      if (!Access('GM') ) Error_Page("Sorry you need to be a GM or a Player to access this");
    } else {
      $Fid = $FACTION['id'];
      $Faction = $FACTION;
    }
  }
  if ($GM = Access('GM') ) {
    Recalc_Offices();
    if ($Fid == 0) {
      if (isset( $_REQUEST['F'])) {
        $Fid = $_REQUEST['F'];
      } else if (isset( $_REQUEST['f'])) {
        $Fid = $_REQUEST['f'];
      } else if (isset( $_REQUEST['id'])) {
        $Fid = $_REQUEST['id'];
      }
      if (isset($Fid)) $Faction = Get_Faction($Fid);
    }
    if (isset($_REQUEST['FORCE'])) {
      $GM = 0;
    } else {
      if ($Fid) echo "<h2>GM: <a href=OrgList.php?FORCE>This page in Player Mode</a></h2>";
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

  $All = ($GM && isset($_REQUEST['ALL']));

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
  if ($GM) echo "To add a new one, fill in name, description and org type at the bottom and click the <b>Add New Organisation</b> button<p>";
  echo "Click on the View link under the office count, to see location of all Offices and Branches<p>";
  Register_AutoUpdate('Generic',0);
  echo "<form method=post>";
  $coln = 0;
  echo "<div class=tablecont><table id=indextable border width=100% style='min-width:1400px'>\n";
  echo "<thead><tr>";
  if ($GM) echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Id</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Type</a>\n";
  echo "<th colspan=4><a href=javascript:SortTable(" . $coln++ . ",'T')>Name and description</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Relative Order</a>\n";
  if ($GM) echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Whose</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Offices &amp; Branches</a>\n";
  echo "</thead><tbody>";

  foreach ($Orgs as $i=>$O) {
    $Of = $O['Whose'];
    if (!isset($SocPs[$Of])) $SocPs[$Of] = SocPrinciples($Of);

    if (!$All && ($Of != $Fid)) continue;
    echo "<tr>";
    if (!isset($SocPs[$Of][$O['SocialPrinciple']])) {
      $SocPs[$Of][$O['SocialPrinciple']] = (Get_SocialP($O['SocialPrinciple'])['Principle']??'None');

    }
    if ($GM) {
      echo "<td>$i";
      echo "<td>" . fm_select($OrgTypeNames,$O,'OrgType',1,'',"Organisations:OrgType:$i") . "<br>";
      echo "also:<br>" . fm_select($OrgTypeNames,$O,'OrgType2',1,'',"Organisations:OrgType2:$i");
      echo fm_text1('',$O,'Name',4,'','',"Organisations:Name:$i");

      echo "<br>" . fm_basictextarea($O, 'Description',5,4," style='width=70%'","Organisations:Description:$i");
      if ($O['OrgType'] == 5 || $O['OrgType2'] == 5) {
        echo "<br>Social Principle (Religious / Ideological Orgs only)" .
          fm_select($SocPs[$Of],$O,'SocialPrinciple',1,'',"Organisations:SocialPrinciple:$i");
      }
    } else {
      echo "<td>" . $OrgTypeNames[$O['OrgType']];
      if ($O['OrgType2']) echo "<br>also: " . $OrgTypeNames[$O['OrgType2']];
      echo "<td colspan=4>" . $O['Name'] . "<br>";
      echo $Parsedown->text(stripslashes($O['Description']));
      if ($O['OrgType'] == 5 || $O['OrgType2'] == 5) {
        if (!isset($SocPs[$Fid][$O['SocialPrinciple']])) $SocPs[$Fid][$O['SocialPrinciple']] = Get_SocialP($O['SocialPrinciple']);
        echo "<br>Social Principle (Religious / Ideological Orgs only): " . $SocPs[$Of][$O['SocialPrinciple']];
      }
    }
    echo fm_number1('',$O,'RelOrder','','',"Organisations:RelOrder:$i");
    if ($GM) {
      echo "<td>" . fm_select($FactNames,$O,'Whose',1,'',"Organisations:Whose:$i");
      echo "<br><a href=OrgList.php?ACTION=Delete&i=$i>Delete</a>";
    }
    echo "<td>" . $O['OfficeCount'] . "<br><a href=OrgView.php?id=$i>View</a>";
    echo fm_hidden("Organisations:GameId:$i", $O['GameId']);
  }
  if ($GM) {
    $O = [];
    echo "<tr><td>";
    if ($GM) echo "<td>";
    echo fm_select($NewOrgs,$O,'OrgType',0,'',"Organisations:OrgType:0");
    echo fm_text1('',$O,'Organisations:Name:0',4,'','placeholder="New Organisation Name"');
    echo "<br>" . fm_basictextarea($O, 'Organisations:Description:0',5,4,"placeholder='New Organisation Description' style='width=70%'");
    if ($GM) {
      echo "Set up a Social Principle and 2nd Org type (if appropriate) after creating the Organisation";
    } else {
      if (!isset($SocPs[$Fid])) $SocPs[$Fid] = SocPrinciples($Fid);
      if (!isset($SocPs[$Fid][$O['SocialPrinciple']])) $SocPs[$Fid][$O['SocialPrinciple']] = Get_SocialP($O['SocialPrinciple']);

      echo "<br>Social Priniple (Religious / Ideological only)" . fm_select($SocPs[$Fid],$O,'SocialPrinciple:0');
    }
    echo fm_number1('',$O,'RelOrder','','',"Organisations:RelOrder:0");
    if ($GM) {
      echo "<td>" . fm_select($FactNames,$O,'Organisations:Whose:0',1);
    } else {
      echo fm_hidden('Organisations:Whose:0',$Fid);
    }
    echo fm_hidden('GameId:0',$GAMEID);
    if (Access('God')) echo "<tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea>";
    echo "</table>";
    echo "<h2><input type=submit name=Update value='Add New Organisation'></h2>";

    if ($GM) echo "GM Note: The count of offices is reset next time <b>Rebuild list of Worlds and colonies</b> is run.<p>";
  } else {
    echo "</table>";
    echo "New organisations are made by building them an office.<p>";
  }
  echo "</form></div>";
  dotail();


