<?php
  include_once("sk.php");
  include_once("GetPut.php");

  A_Check('GM');

  dostaffhead("List Systems");

  global $db, $GAME, $GAMEID;

  $Systems = Get_Systems();
  $Factions = Get_Factions();
//  $SRefs = array_flip(Get_SystemRefs());

  if (!$Systems) {
    echo "<h2>No systems found</h2>";
    echo "<h2><a href=SysEdit.php?ACTION=New>New System</a></h2>";
    dotail();
  }

  $Links = Get_LinksGame();
//  var_dump($Systems);
//var_dump($SRefs);
//var_dump($Links);
  foreach($Systems as &$N) $N['LinkCount'] = 0;
  foreach($Links as $L) {
    $Systems[$L['System1Ref']]['LinkCount']++;
    $Systems[$L['System2Ref']]['LinkCount']++;
  }



  echo "<h1>Systems</h1>";

  $coln = 0;
  echo "<div class=tablecont><table id=indextable border width=100% style='min-width:1400px'>\n";
  echo "<thead><tr>";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Ref</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Control</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Name</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Nebulae</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Survey Report</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Things</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Anomalies</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Visited?</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Links</a>\n";
  echo "</thead><tbody>";

  foreach($Systems as $N) {
    $sid = $N['id'];
    $Name = $Ref = $N['Ref'];
    $Cont = $N['Control'];
    $Ctrl = ($Cont?"<a href=FactionEdit.php?F=$Cont>" . $Factions[$Cont]['Name'] . "</a>":"");
    if ($N['Name']) $Name = $N['Name'];
    if ($N['ShortName']) $Name = $N['ShortName'];
    if (strlen($Name)> 20) $Name = substr($Name,0,20);
    $Neb = $N['Nebulae'];
    echo "<tr><td><a href=SysEdit.php?N=$sid>$Ref</a>";
    echo "<td>$Ctrl";
    echo "<td>$Name";
    echo "<td>$Neb";
    echo "<td><a href=SurveyReport.php?id=$sid>Survey Report</a>"; // Generic
    echo "<td><a href=ThingList.php?AT=$Ref>Things in $Ref</a>\n";
    $Anoms = Gen_Get_Cond('Anomalies',"GameId=$GAMEID AND SystemId=$sid");
    echo "<td>";
    if ($Anoms) {
      foreach($Anoms as $A) {
        $Aid = $A['id'];
        echo "<a href=AnomalyEdit.php?id=$Aid>$Aid</a> ";
      }
    }
    $Who = Gen_Get_Cond('FactionSystem',"SystemId=$sid");
    $npc = 1;
    foreach($Who as $W) if (!isset($Factions[$W['FactionId']]) || (!$Factions[$W['FactionId']]['NPC'])) $npc = 0;
    echo "<td>" .($Who?($npc?"NPC only":"Yes"):"No");
    echo "<td>" . $N['LinkCount'];
  }

  echo "</tbody></table></div>\n";

  if (Access('GM')) echo "<h2><a href=SysEdit.php?ACTION=New>New System</a></h2>";


  dotail();
?>
