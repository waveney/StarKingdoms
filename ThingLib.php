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


function Show_Tech(&$T,&$CTNs,&$Fact=0,&$FactTechs=0,$Descs=1) {
  global $ModuleCats,$ModFormulaes,$ModValues,$Fields,$ShipTypes,$Tech_Cats,$CivMil;
  $Tid = $T['id'];
  $Parsedown = new Parsedown();
  
  $Class = ($T['Cat'] == 0?"TechDesc":"SuppTech");
  echo "<div class=$Class><h2 onclick=Toggle('TDesc$Tid')>" . $T['Name'];
  if ($Fact && $T['Cat']==0 && isset($FactTechs[$T['id']]) ) echo " - at Level " . $FactTechs[$T['id']];
  if ($Fact) {
    if ($T['Cat']>0 && isset($FactTechs[$T['id']]) ) {
      echo " - Known";
    } else if (!isset($FactTechs[$T['PreReqTech']]) || ($FactTechs[$T['PreReqTech']['Level']]<$T['PreReqLevel'] ) ) {
      echo " - Not yet allowed";
    }
  }
  
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
  
  if (!$Descs) echo "<div id=TDesc$Tid hidden>";
  if ($T['Description']) echo  $Parsedown->text($T['Description']);

  if (!$Descs) echo "</div>";  
  echo "</div><p>";

}

?>
