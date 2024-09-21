<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("OrgLib.php");
  global $FACTION,$GAMEID;


  dostaffhead("List of Branches",["js/ProjectTools.js"]);
  A_Check('GM');


  if (isset($_REQUEST['ACTION']))
    switch ($_REQUEST['ACTION']) {
      case 'Delete':
  }


//  CheckFaction('WorldList',$Fid);

  $FactNames = Get_Faction_Names();

  $Offs = Gen_Get_Cond('Branches',"GameId=$GAMEID");
  $Orgs = Gen_Get_Cond('Organisations',"GameId=$GAMEID");
// var_dump($Orgs);
  $OrgTypes = Get_OrgTypes();
  $OrgTypeNames = NamesList($OrgTypes);
  $OrgNames = NamesList($Orgs);
  $BTypes = Get_BranchTypes();
  $BTypeNames = NamesList($BTypes);

  $Facts = Get_Factions();

  echo "<h1>Branches</h1>";
  Register_AutoUpdate('Generic',0);
  echo "<form method=post>";
  $coln = 0;
  echo "<div class=tablecont><table id=indextable border width=100% style='min-width:1400px'>\n";
  echo "<thead><tr>";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Id</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Org Type</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Organisation</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Branch Type</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Whose</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>ThingType</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>ThingId</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Name</a>\n";
  echo "</thead><tbody>";

  foreach ($Offs as $i=>$O) {
    echo "<tr><td>$i";
    echo "<td>" . fm_select($OrgTypeNames,$O,'OrgType',0,'',"Branches:OrgType:$i");
    echo "<td>" . fm_select($OrgNames,$O,'Organisation',1,'',"Branches:Organisation:$i");
    echo "<td>" . fm_select($BTypeNames,$O,'Type',1,'',"Branches:Type:$i");
    echo "<td>" . fm_select($FactNames,$O,'Whose',1,'',"Branches:Whose:$i");
    echo fm_number1('',$O,'HostType','','',"Branches:HostType:$i");
    echo fm_number1('',$O,'HostId','','',"Branches:HostId:$i");
    echo fm_text1('',$O,'Name',4,'','',"Branches:Name:$i");
  }
  if (Access('God')) echo "<tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea>";
  echo "</table>";
 // echo "<h2><input type=submit name=Update value='Add New Office'></h2>";

  echo "</form></div>";
  dotail();

