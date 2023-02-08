<?php
  include_once("sk.php");
  global $FACTION,$GAMEID,$USER;
  include_once("GetPut.php");
  include_once("PlayerLib.php");
  include_once("ThingLib.php");

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
  
  echo "<h1>Allow Other Factions Aboard Your Ships, use your Warp gates, to repair your ships</h1>";
  
  $coln = 0;
  echo "<form method=post action=FactionCarry.php>";
  Register_Autoupdate('FactionFaction',$Fid);
  echo "<div class=tablecont><table id=indextable border width=100% style='min-width:1000px'>\n";
  echo "<thead><tr>";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Faction Name</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Carry Armies</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Carry Individuals</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Use your Warp Gates</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Repair your Ships</a>\n";  
  echo "</thead><tbody>";

  foreach($FactFacts as $FF) {
    $F = Get_Faction($FF['FactionId2']);
    echo "<tr height=50><Td>" . $F['Name'];
//echo  "\nProps:" . dechex($FF['Props']) . "\n";
    $perms = [ $FF['Props'] & 15, (($FF['Props'] >>4)&15), (($FF['Props'] >>8)&15),(($FF['Props'] >>12)&15),];
//var_dump($perms);
    echo "<td>" . fm_radio('',$Boarding,$perms,0,'',0,'',"Set:0:" . $FF['id']);
    echo "<td>" . fm_radio('',$Boarding,$perms,1,'',0,'',"Set:1:" . $FF['id']);
    echo "<td>" . fm_radio('',$Boarding,$perms,2,'',0,'',"Set:2:" . $FF['id']);
    echo "<td>" . fm_radio('',$Boarding,$perms,3,'',0,'',"Set:3:" . $FF['id']);
  }
  if (Access('God')) echo "</tbody><tfoot><tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea>";  
  echo "</table></div></form>\n";
  dotail();  
?>
