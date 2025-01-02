<?php
// Builds Faction Link Known from movement history records
include_once("sk.php");
include_once("GetPut.php");
include_once("ThingLib.php");
include_once("PlayerLib.php");
include_once("SystemLib.php");

A_Check('God');

// Get all links, Factions,
$Links = Get_LinksGame();
$Systems = Get_Systems(); // Indexed by ref
$SRefs = Get_SystemRefs();
$Facts = Get_Factions();
$TTypes = Get_ThingTypes();
$LinkNames = array_flip(NamesList($Links));
$mtch = $imtch = [];

// var_dump($LinkNames);

dostaffhead('Make Link used table)');
// Loop Factions
foreach ($Facts as $Fid=>$F) {
  $LKnown = [];
  echo "<p>Started " . $F['Name'] . "<p>";

// Loop things
  $Things = Get_Things($Fid);
  foreach ($Things as $Tid=>$T) {
    if (!isset($TTypes[$T['Type']])) continue;
    if (($TTypes[$T['Type']]['Properties'] & THING_CAN_MOVE) ==0) continue;

    $Hist = Gen_Get_Cond('ThingHistory',"ThingId=$Tid");
    if (!$Hist) continue;
    foreach ($Hist as $H) {
 //     var_dump($H['Text']);
      if (!preg_match('/has moved from (.*?) along link (\w*?) to (.*?)$/',$H['Text'],$mtch)) continue;
      $Fref = $mtch[1];
      $Lnam = $mtch[2];
      $Tref = $mtch[3];
 //     var_dump($Lnam,$Fref,$Tref);

      $Lid = $LinkNames[$Lnam];
      if (preg_match('/\( (\w*) \)/',$Fref,$imtch)) $Fref = $imtch[1];
      if (preg_match('/\( (\w*) \)/',$Tref,$imtch)) $Tref = $imtch[1];

//      var_dump($Lid,$Fref,$Tref);
      $LKnown[$Lid] = 1;
    }
  }

  foreach ($LKnown as $Lid=>$D) {
    $FLK = Gen_Get_Cond1('FactionLinkKnown',"FactionId=$Fid AND LinkId=$Lid");
    if ($FLK) continue; // Already recorded
    $FLK = ['FactionId' => $Fid,'LinkId'=>$Lid,'Used'=>1];
    Gen_Put('FactionLinkKnown',$FLK);
    echo "Used $Lid<br>";
  }

  echo "<p>Finished " . $F['Name'] . "<p>";
}

echo "All Done";
dotail();

