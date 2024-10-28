<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("PlayerLib.php");
  include_once("ThingLib.php");

  A_Check('GM');

  dostaffhead("List Factions");

  global $db, $GAME;
  global $PlayerState,$PlayerStates,$PlayerStateColours;
  $Factions = Get_Factions();

  if (!$Factions) {
    echo "<h2>No Factions found</h2>";
    echo "<h2><a href=FactionEdit.php?ACTION=NEW>New Faction</a> ";
    dotail();
  }

  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
    case 'Set State':
      $NewState = $_REQUEST['TurnState'];

      foreach ($Factions as &$F) {
        $F['TurnState'] = $NewState;
        Put_Faction($F);
      }
      $Factions = Get_Factions(1);
      break;
    }
  }

  echo "<h1>Factions</h1>";

  $coln = 0;
  echo "<div class=tablecont><table id=indextable border width=100% style='min-width:1400px'>\n";
  echo "<thead><tr>";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'n')>id</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Use</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Faction</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Who</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Map Colour</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Last Active</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>State</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Economy</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Sens</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Use</a>\n";
  echo "</thead><tbody>";

  foreach($Factions as $F) {
    $Fid = $F['id'];
    [$Econ,$Ectxt] = Income_Calc($Fid);
    echo "<tr><td><a href=FactionEdit.php?F=$Fid>$Fid</a>";
    echo "<td><a href=Access.php?id=$Fid&Key=" . $F['AccessKey'] . " ><b>Use</b></a>";
    echo "<td><a href=FactionEdit.php?F=$Fid>" . $F['Name'] . "</a>";
    echo "<td>" . $F['Player'];
    if ($F['Player2']) echo ", " . $F['Player2'];
    echo "<td style='background:" . $F['MapColour'] . ";'>";
    echo "<td>" . (isset($F['LastActive']) && $F['LastActive']? date('d/m/y H:i:s',$F['LastActive']) :"Never");
    echo "<td <span style='background:" . $PlayerStateColours[$F['TurnState']] . "'>"  . $PlayerState[$F['TurnState']];
    if ($PlayerState[$F['TurnState']] == 'Setup' && $F['Horizon']) echo " - used";
    echo "<td>" . $Econ*10;
    echo "<td>" . Has_Tech($Fid,'Sensors');
    echo "<td><a href=Access.php?id=$Fid&Key=" . $F['AccessKey'] . " ><b>Use</b></a>";
  }

  echo "</tbody></table></div>\n";

  echo "<form method=post action=FactList.php>";
  echo fm_select($PlayerState,NULL,'TurnState') . "<input type=submit name=ACTION value='Set State'>";
  echo "</form></h2>\n";
  echo "<h2><a href=FactionEdit.php?ACTION=NEW>New Faction</a> ";

  dotail();
?>
