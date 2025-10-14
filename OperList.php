<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ProjLib.php");

  A_Check('GM');

  dostaffhead("List Operations");

  global $db, $GAME, $GAMEID;
  global $Project_Status;

  $Operations = Get_Operations();
  $Factions = Get_Factions();
  $Orgs = Gen_Get_Cond('Organisations',"GameId=$GAMEID");
  $OrgTypes = Get_OrgTypes();

  if (!$Operations) {
    echo "<h2>No Operations found</h2>";
    dotail();
  }

  // TODO allow selecting by status and age and who

  echo "<h1>Operations</h1>";

  $coln = 0;
  echo "<div class=tablecont><table id=indextable border width=100% style='min-width:1400px'>\n";
  echo "<thead><tr>";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Id</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Name</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Who</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Organisation</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Status</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Start Turn</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Finish Turn</a>\n";
  //  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Cost</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Progress</a>\n";
  echo "</thead><tbody>";

  foreach($Operations as $O) {
    $oid = $O['id'];
    echo "<tr><td>$oid";
    echo "<td><a href=OperEdit.php?id=$oid>" . $O['Name'] . "</a>";
    echo "<td>" . $Factions[$O['Whose']]['Name'];
    echo "<td>" . (isset($Orgs[$O['OrgId']])? $Orgs[$O['OrgId']]['Name'] . " (" . $OrgTypes[$Orgs[$O['OrgId']]['OrgType']]['Name'] .
      ($Orgs[$O['OrgId']]['OrgType2']?"/" . $OrgTypes[$Orgs[$O['OrgId']]['OrgType2']]['Name']:'') . " )" : 'Unknown');
    echo "<td>" . $Project_Status[$O['Status']];
    echo "<td>" . $O['TurnStart'];
    echo "<td>" . $O['TurnEnd'];
    //    echo "<td>" . $O['Costs'];
    echo "<td>" . $O['Progress'] . "/" . $O['ProgNeeded'];
  }

  echo "</tbody></table></div>\n";


  dotail();
?>
