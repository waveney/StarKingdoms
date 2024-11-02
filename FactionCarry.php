<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("PlayerLib.php");
  include_once("ThingLib.php");
  global $FACTION,$GAMEID,$USER,$Relations;

//var_dump($_COOKIE,$_REQUEST);
  A_Check('Player');
  if (Access('Player')) {
    if (!$FACTION) {
      if (!Access('GM') ) Error_Page("Sorry you need to be a GM or a Player to access this");
    } else {
      $Fid = $FACTION['id'];
      $Faction = &$FACTION;
    }
  }
  if (Access('GM') ) {
    A_Check('GM');
    if (isset( $_REQUEST['F'])) {
      $Fid = $_REQUEST['F'];
    } else if (isset( $_REQUEST['f'])) {
      $Fid = $_REQUEST['f'];
    } else if (isset( $_REQUEST['id'])) {
      $Fid = $_REQUEST['id'];
    }
    if (isset($Fid)) $Faction = Get_Faction($Fid);
  } else {
    if ($Faction['TurnState'] > 2) Player_Page();
  }

  dostaffhead("Carry Control",["js/ProjectTools.js"]);

  CheckFaction('PThingList',$Fid);

  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
    case 'MOVE':
      break;
    }
  }

/* Select types
  Name, Class, What, sub cat, where, move, Level, Action
  */

  $FactFacts = Get_FactionFactions($Fid);
  $Boarding = ['No', 'This turn only', 'Ongoing'];

  $RelVals = $RelCols = $RelClass = [];
  foreach ($Relations as $i=>$D) {
    $RelVals[$i] = $D[0];
    $RelClass[$i] = $D[1];
    $RelCols[$i] = $D[1];
  }

  echo "<h1>Relationship to Other Factions, and to allow them to board and repair your ships</h1>";

  $coln = 0;
  echo "<form method=post action=FactionCarry.php>";
  Register_Autoupdate('FactionFaction',$Fid);
  echo "<div class=tablecont><table id=indextable border width=100% style='min-width:1000px'>\n";
  echo "<thead><tr>";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Faction Name</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Relationship</a>\n";

  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Carry Armies</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Carry Individuals</a>\n";
 // echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Use your Warp Gates</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Repair your Ships</a>\n";
  echo "</thead><tbody>";

  echo "<tr height=50><Td>Unknown Aliens";
  if ($FACTION['DefaultRelations'] == 0) $FACTION['DefaultRelations']= 5;
  echo "<td>" . fm_radio('',$RelVals,$FACTION,'DefaultRelations','',-2,'',"DefaultRelations:$Fid",$RelCols);

  foreach($FactFacts as $FF) {
    $F = Get_Faction($FF['FactionId2']);
    $id = $FF['id'];
    echo "<tr height=50><Td>" . $F['Name'];
    //    &$Options,$data,$field,$blank=0,$selopt='',$field2='',$Max=0, &$optclass=0, $Raw=0, &$BGColour=0) {
// style='background:" . $RelCols[$FF['Relationship']] . "'
//    function fm_radio($Desc,&$defn,&$data,$field,$extra='',$tabs=1,$extra2='',$field2='',$colours=0,$multi=0,$extra3='',$extra4='') {
    if ($FF['Relationship'] == 0) $FF['Relationship']= 5;
    echo "<td>" . fm_radio('',$RelVals,$FF,'Relationship','',-2,'',"Relationship:$id",$RelCols);
//echo  "\nProps:" . dechex($FF['Props']) . "\n";
    $perms = [ $FF['Props'] & 15, (($FF['Props'] >>4)&15), (($FF['Props'] >>8)&15),(($FF['Props'] >>12)&15),];
//var_dump($perms);
    echo "<td>" . fm_radio('',$Boarding,$perms,0,'',0,'',"Set:0:" . $FF['id']);
    echo "<td>" . fm_radio('',$Boarding,$perms,1,'',0,'',"Set:1:" . $FF['id']);
 //   echo "<td>" . fm_radio('',$Boarding,$perms,2,'',0,'',"Set:2:" . $FF['id']); // Warp gates
    echo "<td>" . fm_radio('',$Boarding,$perms,3,'',0,'',"Set:3:" . $FF['id']);
  }
  if (Access('God')) echo "</tbody><tfoot><tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea>";
  echo "</table></div></form>\n";
  dotail();
?>
