<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("PlayerLib.php");

  $Traits = [];
  $TraitNames = [];
  $Facts = [];
  
  dostaffhead("All Traits");
  
  echo "<h1>List of all Traits</h1>";
  echo "Game No : Faction.<b>A</b> = Automated<P>";
  
  $Factions = Gen_Get_Cond('Factions','id>0');
  foreach($Factions as $F) {
    $Fid = $F['id'];
    $Facts[$Fid] = $F;
    if ($F['Trait1']) $TraitNames[$F['Trait1']][] = [$Fid,1, $F['Trait1Automated']];
    if ($F['Trait2']) $TraitNames[$F['Trait2']][] = [$Fid,2, $F['Trait2Automated']];
    if ($F['Trait3']) $TraitNames[$F['Trait3']][] = [$Fid,3, $F['Trait3Automated']];      
  };
  
  ksort($TraitNames);
  echo "<table border><tr><td>Name<Td>Use\n";
  
  foreach($TraitNames as $Name=>$TraitUse) {
    echo "<tr><td>$Name<td>";
    $use = 0;
    foreach($TraitUse as $Use) {
      [$Fid,$TN,$Auto] = $Use;
      echo ($use++?', ':'') . "<a href=FactionEdit.php?F=Fid>" . $Facts[$Fid]['GameId']  . ':' . $Facts[$Fid]['Name'] . ($Auto?" <b>A</b>":'') . "</a>";
    }
  }
  
  echo "</table>";
  
  dotail();
?>

