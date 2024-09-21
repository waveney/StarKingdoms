<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("OrgLib.php");
  global $FACTION,$GAMEID;


  dostaffhead("List of Offices",["js/ProjectTools.js"]);
  A_Check('GM');


  if (isset($_REQUEST['ACTION']))
    switch ($_REQUEST['ACTION']) {
      case 'Delete':
        break;
  }


//  CheckFaction('WorldList',$Fid);

  $FactNames = Get_Faction_Names();

  $Offs = Gen_Get_Cond('Offices',"GameId=$GAMEID");
  $Orgs = Gen_Get_Cond('Organisations',"GameId=$GAMEID");
// var_dump($Orgs);
  $OrgTypes = Get_OrgTypes();
  $OrgTypeNames = NamesList($OrgTypes);
  $OrgNames = NamesList($Orgs);

  $Facts = Get_Factions();

  echo "<h1>Offices</h1>";
  Register_AutoUpdate('Generic',0);
  echo "<form method=post>";
  $coln = 0;
  echo "<div class=tablecont><table id=indextable border width=100% style='min-width:1400px'>\n";
  echo "<thead><tr>";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Id</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Org Type</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Organisation</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Whose</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>World</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Name</a>\n";
  echo "</thead><tbody>";

  foreach ($Offs as $i=>$O) {
    echo "<tr><td>$i";
    echo "<td>" . fm_select($OrgTypeNames,$O,'OrgType',0,'',"Offices:OrgType:$i");
    echo "<td>" . fm_select($OrgNames,$O,'Organisation',1,'',"Offices:Organisation:$i");
    echo "<td>" . fm_select($FactNames,$O,'Whose',1,'',"Offices:Whose:$i");
    echo fm_number1('',$O,'World','','',"Offices:World:$i");
    echo fm_text1('',$O,'Name',4,'','',"Offices:Name:$i");
  }
  if (Access('God')) echo "<tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea>";
  echo "</table>";
 // echo "<h2><input type=submit name=Update value='Add New Office'></h2>";

  echo "GM Note: The count of offices is reset next time <b>Rebuild list of Worlds and colonies</b> is run.<p>";

  echo "</form></div>";
  dotail();

