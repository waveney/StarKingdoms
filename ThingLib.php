<?php
// Lib of Thing related stuff
include_once("sk.php");
include_once("GetPut.php");
include_once("vendor/erusev/parsedown/Parsedown.php");
  
global $ModuleCats,$ModFormulaes,$ModValues,$Fields,$ShipTypes,$Tech_Cats,$CivMil,$BuildState;
global $THING_HAS_DISTRICTS,$THING_HAS_MODULES;

$ModuleCats = ['Undefined','Civilian Ship','Support Ship','Military Ship','Army','Other'];
$Fields = ['Engineering','Physics','Xenology'];
$ShipTypes = ['Military','Support','Civilian'];
$Tech_Cats = ['Core','Supp','Non Std'];
$CivMil = ['','Civilian','Military'];
$BuildState = ['','Building','Launching','Built'];

$ModFormulaes = [];
$ModValues = [];

$THING_HAS_DISTRICTS =1;
$THING_HAS_MODULES = 2;


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


function Show_Tech(&$T,&$CTNs,&$Fact=0,&$FactTechs=0,$Descs=1,$Setup=0) {
  global $ModuleCats,$ModFormulaes,$ModValues,$Fields,$ShipTypes,$Tech_Cats,$CivMil;
  $Tid = $T['id'];
  $Parsedown = new Parsedown();

  $Class = ($T['Cat'] == 0?"TechDesc":"SuppTech");
  echo "<div class=$Class><h2 onclick=Toggle('TDesc$Tid')>" . $T['Name'];
  echo "<div class=TechLevel>";

  if ($Fact && $T['Cat']==0 && isset($FactTechs[$T['id']]) ) echo " ( at Level " . $FactTechs[$T['id']]['Level'] . " )";
  if ($Fact && $T['Cat']>0) {
    if (isset($FactTechs[$T['id']]) ) {
      echo " ( Known )";
    } else if (!isset($FactTechs[$T['PreReqTech']]) || ($FactTechs[$T['PreReqTech']]['Level']<$T['PreReqLevel'] ) ) {
      echo " <span class=NotAllow>( Not yet allowed )</span>";
    } else {
      echo " <span class=Allow>( Allowed )</span>";
    }
  }
  
  echo "</div></h2>";
  
  if ($Setup) {
    if ($T['Cat'] == 0) {
      echo fm_number0("Levels",$FactTechs[$Tid],'Level','','',"Tech$Tid");
    } else {
      echo fm_checkbox("Have",$FactTechs[$Tid],'Level','',"Tech$Tid");
    }
  }
 
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

function Tech_Names(&$Techs) {
  $TNs = [];
  foreach($Techs as $T) $TNs[$T['id']] = $T['Name'];
  return $TNs;
}


function Thing_Type_Names() {
  $tts = Get_ThingTypes();
  $tns = [];
  foreach ($tts as $t) $tns[$t['id']] = $t['Name'];
  return $tns;
}

function Thing_Type_Props() {
  $tts = Get_ThingTypes();
  $tns = [];
  foreach ($tts as $t) $tns[$t['id']] = $t['Properties'];
  return $tns;
}

function Within_Sys_Locs(&$N) {
  include_once("SystemLib.php");
  $L[1] = 'Deep space';
  $Ps = Get_Planets($N['id']);
  $PTD = Get_PlanetTypes();
  
  if ($Ps) {
    $pi = $mi = 1;
    foreach ($Ps as $P) {
      $PName = PM_Type($PTD[$P['Type']],"Planet") . " - " . NameFind($P);
      $L[100 +$pi] = "Orbiting $PName";
      $L[200 +$pi] = "On $PName";
      $Ms = Get_Moons($P['id']);
      if ($Ms) {
        foreach ($Ms as $M) {
          $MName = PM_Type($PTD[$M['Type']],"Moon") . " - " . NameFind($M);
          $L[300 +$mi] = "Orbiting $MName";
          $L[400 +$mi] = "On $MName";
          $mi++;
        }
      }
      $pi++;
    }
  }
  $LKs = Get_Links($N['Ref']);
  $Li = 1;
  foreach($LKs as $lk) $L[500+$Li++] = "At stargate to link " . $lk['id'];
/*  
  $Anoms = Get_Anomolies($N['Ref']);
  $Ai = 1;
  foreach($Anoms as $Anom) $L[600+$Ai++] = "At " . $Anoms['Name'];  
  */
  return $L;
}

function Show_Thing(&$t) {
  global $THING_HAS_DISTRICTS,$THING_HAS_MODULES,$GAMEID,$GAME;
  $tid = $t['id'];
  
  $ttn = Thing_Type_Names();
  $FactNames = Get_Faction_Names();
  $Fact_Colours = Get_Faction_Colours();
  $ThingProps = Thing_Type_Props();
  $N = Get_System($t['SystemId']);
  $Syslocs = Within_Sys_Locs($N);
  $Systems = Get_SystemRefs();
    
  echo "<form method=post id=mainform enctype='multipart/form-data' action=ThingEdit.php>";
  echo "<div class=tablecont><table width=90% border class=SideTable>\n";
  Register_AutoUpdate('Thing',$tid);
  echo fm_hidden('id',$tid);
  
  echo "<tr class=NotSide><td class=NotSide>Id:<td class=NotSide>$tid<td class=NotSide>Game<td class=NotSide>$GAMEID<td class=NotSide>" . $GAME['Name'];
  echo "<tr><td>Type:<td>" . fm_select($ttn,$t,'Type',1) . fm_number("SubType", $t,'SubType'); // need to make that easier
  echo fm_number("Level",$t,'Level');
  echo "<tr><td>System:<td>" . fm_select($Systems,$t,'SystemId') . "<td>" . fm_select($Syslocs,$t,'WithinSysLoc');
  echo "<tr>" . fm_text('Name',$t,'Name',2);
  echo "<tr>" . fm_number('Build Project',$t,'ProjectId'). "<td>No meaning yet";
  echo "<tr>" . fm_radio('Whose',$FactNames ,$t,'Whose','',1,'colspan=6','',$Fact_Colours,0); 
  echo "<tr>" . fm_textarea("Description\n(For others)",$t,'Description',8,3);
  echo "<tr>" . fm_textarea('Notes',$t,'Notes',8,3);
  echo "<tr>" . fm_textarea('GM_Notes',$t,'GM_Notes',8,3);
  if ($ThingProps[$t['Type']] & $THING_HAS_DISTRICTS) {
    $DTs = Get_DistrictTypeNames();
    $Ds = Get_DistrictsT($tid);

    $NumDists = count($Ds);
    $dc=0;
  
    foreach ($Ds as $D) {
      $did = $D['id'];
      if ($dc++%4 == 0)  echo "<tr><td>Districts:";
      echo "<td>" . fm_Select($DTs, $D , 'Type', 1,'',"DistrictType-$did") . fm_number1('', $D,'Number', '','',"DistrictNumber-$did");
      };

    echo "<tr><td>Add District Type<td>" . fm_Select($DTs, NULL , 'Number', 1,'',"DistrictTypeAdd-$tid");
    echo fm_number("Max Districts",$t,'MaxDistricts');
  }
  if ($ThingProps[$t['Type']] & $THING_HAS_MODULES) {
    echo "<tr><td>Modules to be done\n";
 // TODO 
 // Max modules, current count, what 
  }
  if (Access('God')) echo "<tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea>";  
  echo "</table></div>\n";

  echo "<h2><a href=ThingList.php>Back to Thing list</a></h2>";
}

?>
