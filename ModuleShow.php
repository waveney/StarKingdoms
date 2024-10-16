<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("vendor/erusev/parsedown/Parsedown.php");

  global $FACTION;

  $GM = Access('GM');
  if (!$GM && !$FACTION) {
      Error_Page("Sorry you need to be a GM or a Player to access this");
  }
  dostaffhead("Modules");


  $Techs = Get_Techs();
  $TNames = NamesList($Techs);
  $Mods = Get_ModuleTypes();

  function Msort($a,$b) {
    return $a['Name'] <=> $b['Name'];
  }

  uasort($Mods,'Msort');

  if (isset($_REQUEST['f'])) {
    $Fid = $_REQUEST['f'];
  } else   if (isset($_REQUEST['F'])) {
    $Fid = $_REQUEST['F'];
  } else {
    $Fid = 0;
  }

  if ($FACTION) {
    $Fid = $FACTION['id'];
  } else {
    $FACTION = Get_Faction($Fid);
  }

  if (!Access('GM') && $FACTION['TurnState'] > 2) Player_Page();
//var_dump($Techs); exit;

  $TechFacts = [];
  if ($Fid) {
    $TechFacts = Get_Faction_Techs($Fid);
  }
  $Parsedown = new Parsedown();

  echo "<h1>Modules</h1>\n";
  echo "Click on Modules name to Toggle showing the definition and examples or <button type=button onclick=SeeAll('MDesc')>Expand All</button>\n<p>";

  foreach ($Mods as $Mid=>$M) {
    if ($M['Leveled'] & 16) {
      if (!Has_Tech($Fid,$M['BasedOn'])) continue;
    }

    echo "<div class=TechDesc><h2 onclick=Toggle('MDesc$Mid')>" . $M['Name'] . "</h2>\n";
    if ($M['BasedOn']) echo "Based On: " . $TNames[$M['BasedOn']];
    if ($M['MinShipLevel']) echo " - Min Ship Level: " . $M['MinShipLevel'];
    if ($GM && ($M['Leveled'] & 16)!=0) echo " - <span class=red>Restricted</span>";

    echo "<p><div id=MDesc$Mid hidden>";
    if ($M['Description']) {
      echo  $Parsedown->text(stripslashes($M['Description']));
    } else {
      echo "Description not yet written - inform the GMs...";
    }

    echo "</div></div><p>";
  }

  dotail();

  echo "<p>";


