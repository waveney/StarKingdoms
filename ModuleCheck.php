<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("ProjLib.php");
  include_once("HomesLib.php");


  A_Check('GM');

  dostaffhead("Check all Things have modules that should");

  global $db, $GAME;

  $Facts = Get_Factions();
  $Modules = Gen_Get_Cond('Modules','Number!=0');
  $TTypes = Get_ThingTypes();
  $ThimgMods = [];

  foreach ($Modules as $M) {
    $ThingMods[$M['ThingId']] = 1;
  }

  $Things = Get_AllThings();

  foreach ($Things as $Tid=>$T) {
    if (($TTypes[$T['Type']]['Properties'] & THING_HAS_MODULES) && ( $T['BuildState']== BS_SERVICE || $T['BuildState']==BS_COMPLETE)) {
      if (empty($ThingMods[$Tid])) {
        echo "<a href=ThingEdit.php?id=$Tid>" . $T['Name'] . " - $Tid</a> has no modules<br>\n";
      }
    }
  }

  echo "<p>All done\n";
  dotail();
?>
