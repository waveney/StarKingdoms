<?php
// Lib of Thing related stuff
include_once("sk.php");
include_once("GetPut.php");
include_once("vendor/erusev/parsedown/Parsedown.php");
  
global $ModuleCats,$ModFormulaes,$ModValues,$Fields,$ShipTypes,$Tech_Cats,$CivMil;

$ModuleCats = ['Undefined','Civilian Ship','Support Ship','Military Ship','Army','Other'];
$Fields = ['Engineering','Physics','Xenology'];
$ShipTypes = ['Military','Support','Civilian'];
$Tech_Cats = ['Core','Supp','Non Std'];
$CivMil = ['','Civilian','Military'];

$ModFormulaes = [];
$ModValues = [];

function ModFormulaes() {
  global $ModFormulaes;
  if ($ModFormulaes) return $ModFormulaes;
  
  $MFs = Get_ModFormulaes();
  
  foreach ($MFs as $M) {
    $f = $M['Num1x']?"(" . $M['Num1x'] . " +TL)":"TL";
    if ($M['Num2x']>1) $f = $f . "*" . $M['Num2x'];
    if ($M['Num3x']) $f = "$f + " . $M['Num3x'];    
    $ModFormulaes[$M['id']] = $f;
  }
  return $ModFormulaes;
}

function Reset_ModFormulaes() {
  global $ModFormulaes,$ModValues;
  $ModFormulaes = [];
  $ModValues = [];
}

function ModValue($id) {

}


function Show_Tech(&$T,&$CTNs,&$Fact=0,&$FactTechs=0) {
  global $ModuleCats,$ModFormulaes,$ModValues,$Fields,$ShipTypes,$Tech_Cats,$CivMil;
  
  $Parsedown = new Parsedown();
  
  echo "<div class=TechDesc><h2>" . $T['Name'];
  if ($Fact && $T['Cat']==0 && isset($FactTechs[$T['id']]) ) echo " - at Level " . $FactTechs[$T['id']];
  if ($Fact && $T['Cat']>0 && isset($FactTechs[$T['id']]) ) echo " - Known";
  echo "</h2>";
  switch ($T['Cat']) {
  case 0:
    echo "Core Technology<br>";
    break;
  case 2:
    echo "Non Standard supplimentary Tech<br>";
  case 1:
    echo "Requires " . $CTNs[$T['PreReqTech']] . " at level " . $T['PreReqLevel'] . "<br>";
    if ($T['MinThingLevel']) echo "Size Limitation - Requires at least level " . $T['MinThingLevel'] . " ship<br>";
    break;
  }
  
  if ($T['Slots']) echo "Uses " . $T['Slots'] . " module slots.  ";
  if ($T['CivMil']) echo "<b>" . $CivMil[$T['CivMil']] . " ships</b>";
  echo "<br>";
  
  if ($T['Description']) echo  $Parsedown->text($T['Description']);
  
  echo "</div><p>";

}

?>
