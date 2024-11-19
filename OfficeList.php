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
  $SysRs = Get_SystemRefs();

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
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Where</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>World</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Number</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Name</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Actions</a>\n";

  echo "</thead><tbody>";

  foreach ($Offs as $i=>$O) {
    echo "<tr><td>$i";
    echo "<td>" . fm_select($OrgTypeNames,$O,'OrgType',0,'',"Offices:OrgType:$i");
    echo "<td>" . fm_select($OrgNames,$O,'Organisation',1,'',"Offices:Organisation:$i");
    echo "<td>" . fm_select($FactNames,$O,'Whose',1,'',"Offices:Whose:$i");
    $World = Get_World($O['World']);
    switch ($World['ThingType']) {
      case 1:// Planet
        $P = Get_Planet($World['ThingId']);
        $Where = $P['SystemId'];
        break;
      case 2:// Moon
        $M = Get_Moon($World['ThingId']);
        $P = Get_Planet($M['PlanetId']);
        $Where = $P['SystemId'];
        break;
      case 3: // Thing;
        $T = get_Thing($World['ThingId']);
        $Where = $T['SystemId'];
        break;
    }
    echo "<td>" . $SysRs[$Where];
    echo fm_number1('',$O,'World','','',"Offices:World:$i");
    echo fm_number1('',$O,'Number','','',"Offices:Number:$i");
    echo fm_text1('',$O,'Name',1,'','',"Offices:Name:$i");
    echo "<td><a href=OfficeEdit.php?id=$i>Edit</a>, <a href=OfficeEdit.php?Action=Delete&id=$i>Del</a>";
  }
  if (Access('God')) echo "<tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea>";
  echo "</table>";
 // echo "<h2><input type=submit name=Update value='Add New Office'></h2>";

  echo "GM Note: The count of offices and the indications of type etc are reset next time <b>Rebuild list of Worlds and colonies</b> is run.<p>";

  echo "</form></div>";
  dotail();


