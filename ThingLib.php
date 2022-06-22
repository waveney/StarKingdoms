<?php
// Lib of Thing related stuff
include_once("sk.php");
include_once("GetPut.php");
include_once("vendor/erusev/parsedown/Parsedown.php");
include_once("PlayerLib.php");  

global $ModuleCats,$ModFormulaes,$ModValues,$Fields,$Tech_Cats,$CivMil,$BuildState,$ThingInstrs,$InstrMsg,$Advance;

$ModuleCats = ['Ship','Civilian Ship','Support Ship','Military Ship','Army','Other'];
$Fields = ['Engineering','Physics','Xenology'];
$Tech_Cats = ['Core','Supp','Non Std'];
$CivMil = ['','Civilian','Military'];
$BuildState = ['Planning','Building','Shakedown','Complete','Ex','Abandonded','Missing In Action'];
$ThingInstrs = ['None','Colonise','Voluntary Warp Home','Decommision','Analyse Anomaly','Establish Embassy','Make Outpost','Make Asteroid Mine','Make Minefield',
                'Make Orbital Repair Yard','Build Space Station','Expand Space Station','Make Deep Space Sensor','Make Advanced Asteroid Mine','Build Stargate',
                'Make Planet Mine', 'Construct Command Relay Station', 'Repair Command Node','Build Planetary Mine','Dismantle Stargate','Disband'];
$Advance = ['','','Advanced ','Very Advanced ','Ultra Advanced ','Evolved '];


$ModFormulaes = [];
$ModValues = [];

define('THING_HAS_DISTRICTS',1);
define('THING_HAS_MODULES',2);
define('THING_HAS_LEVELS',4);
define('THING_HAS_SHIPMODULES',8);
define('THING_HAS_GADGETS',16);
define('THING_HAS_ARMYMODULES',32);
define('THING_HAS_MILSHIPMODS',64);
define('THING_HAS_CIVSHIPMODS',128);
define('THING_CAN_MOVE',256);
define('THING_CAN_BETRANSPORTED',512);
define('THING_HAS_2_FACTIONS',1024);
define('THING_HAS_MINERALS',2048);
define('THING_CAN_BE_ADVANCED',4096);
define('THING_CAN_BECREATED',8192);
define('THING_NEEDS_CARGOSPACE',16384);
define('THING_CANT_HAVENAMED',32768);
define('THING_CAN_DO_PROJECTS',65536);
define('THING_MOVES_DIRECTLY',131072);

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

function Mod_Value($fid,$modtypeid) {
//echo "Mod Value of $fid, $modtypeid<p>";
  $mt = Get_ModuleType($modtypeid);
  $mf = Get_ModFormula($mt['Formula']);
  if ($mf['Num2x']) {
    $tl = Has_Tech($fid,$mt['BasedOn']);
    $v = ($mf['Num1x'] + $tl) * $mf['Num2x'];
  } else {
    $v = 0;
  }
  $v = $v + $mf['Num3x'];
//echo "Is $v<p>";
  return $v;
}

function Mod_ValueSimple($tl,$modtypeid) {
//echo "Mod Value of $tl, $modtypeid<p>";
  $mt = Get_ModuleType($modtypeid);
  $mf = Get_ModFormula($mt['Formula']);
  if ($mf['Num2x']) {
    $v = ($mf['Num1x'] + $tl) * $mf['Num2x'];
  } else {
    $v = 0;
  }
  $v = $v + $mf['Num3x'];
//echo "Is $v<p>";
  return $v;
}


function Show_Tech(&$T,&$CTNs,&$Fact=0,&$FactTechs=0,$Descs=1,$Setup=0) {
  global $ModuleCats,$ModFormulaes,$ModValues,$Fields,$Tech_Cats,$CivMil;
  $Tid = $T['id'];
  $Parsedown = new Parsedown();

  $Class = ($T['Cat'] == 0?"TechDesc":"SuppTech");
  echo "<div class=$Class><h2 onclick=Toggle('TDesc$Tid')>" . $T['Name'];
  echo "<div class=TechLevel>";

  if ($Fact && $T['Cat']==0 && isset($FactTechs[$T['id']]) ) echo " ( at Level " . $FactTechs[$T['id']]['Level'] . " )";
  if ($Fact && $T['Cat']>0) {
    if (isset($FactTechs[$T['id']]) && $FactTechs[$Tid]['Level'] ) {
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
      if ($T['Cat'] == 2) echo fm_checkbox("Know about",$FactTechs[$Tid],'Level','',"Know$Tid");
    }
  }
 
  switch ($T['Cat']) {
  case 0:
    echo "Core Technology.  Field: <b>" . $Fields[$T['Field']] . "</b><br>";
    break;
  case 2:
    echo " <span class=blue>Non Standard supplimentary Tech</span><br>";
  case 1:
    echo " Requires " . $CTNs[$T['PreReqTech']] . " at level " . $T['PreReqLevel'] . ".  Field: <b>" . $Fields[$T['Field']] . "</b><br>";
    if ($T['MinThingLevel']) echo "Size Limitation - Requires at least level " . $T['MinThingLevel'] . " ship<br>";
    break;
  }
  
  if ($T['Slots']) echo "Uses " . $T['Slots'] . " module " . Plural($T['Slots'],"","slot","slots") . "  ";
  if ($T['CivMil']) echo "<b>" . $CivMil[$T['CivMil']] . " ships</b>";
  echo "<br>";
  
  if (!$Descs) echo "<div id=TDesc$Tid hidden>";
  if ($T['Description']) echo  $Parsedown->text($T['Description']);

  if (!$Descs) echo "</div>";  
  echo "</div><p>";

}

function Tech_Names(&$Techs) {
  $TNs = [0=>''];
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

function Within_Sys_Locs(&$N,$PM=0,$Boarding=0,$Restrict=0,$Hab=0) {// $PM +ve = Planet Number, -ve = Moon Number, restrict 1=ON, 2space only
  include_once("SystemLib.php");
  $L[0] = "";
  if ($Restrict !=1) $L[1] = 'Deep space';
//  $L[2] = 'On board ship';
  if (!isset($N['id'])) return $L;
  $Ps = Get_Planets($N['id']);
  $PTD = Get_PlanetTypes();
  
  if ($Ps) {
    $pi = $mi = 1;
    foreach ($Ps as $P) {
      if ($PM == $P['id']) return 200+$pi;
      $PName = PM_Type($PTD[$P['Type']],"Planet") . " - " . NameFind($P);
      if ($Restrict !=1) $L[100 +$pi] = "Orbiting $PName";
      if ($Restrict == 0 || ($Restrict == 2 && $PTD[$P['Type']]['Hospitable'])) {
        $L[200 +$pi] = "On $PName";
        if ($Hab && $PTD[$P['Type']]['Hospitable'])  $L[200 +$pi] .= " (Hospitable)";
      }
      $Ms = Get_Moons($P['id']);
      if ($Ms) {
        foreach ($Ms as $M) {
          if ($PM == -$M['id']) return 400+$mi;
          $MName = PM_Type($PTD[$M['Type']],"Moon") . " - " . NameFind($M);
          if ($Restrict !=1) $L[300 +$mi] = "Orbiting $MName";
          if ($Restrict == 0 || ($Restrict == 2 && $PTD[$M['Type']]['Hospitable'])) {
            $L[400 +$mi] = "On $MName";
            if ($Hab && $PTD[$M['Type']]['Hospitable'])  $L[400 +$mi] .= " (Hospitable)";
          }
          $mi++;
        }
      }
      $pi++;
    }
  }
  if ($PM) return 0;
  $LKs = Get_Links($N['Ref']);
  $Li = 1;
  if ($Restrict !=1) foreach($LKs as $lk) $L[500+$Li++] = "At stargate to link " . $lk['id'];
  
  if ($Boarding) {
    $Things = Get_ThingsAt($N['id']);
    $ThingTypes = Get_ThingTypes();
    
    foreach ($Things as $T) {
      if (($ThingTypes[$T['Type']]['Properties'] & THING_HAS_DISTRICTS) || $T['CargoSpace'] >0) $L[1000+$T['id']] = "On " . $T['Name'];
    }
  }
/*  
  $Anoms = Get_Anomolies($N['Ref']);
  $Ai = 1;
  foreach($Anoms as $Anom) $L[600+$Ai++] = "At " . $Anoms['Name'];  
  */
  return $L;
}

function Get_Valid_Modules(&$t) {
  global $ModuleCats;
  $MTs = Get_ModuleTypes();
  $VMT = [];
  $ThingProps = Thing_Type_Props();
  $tprop = (empty($ThingProps[$t['Type']] )?0: $ThingProps[$t['Type']] ) ;
  foreach ($MTs as $M) {
    if ($t['Whose']) {
      if ($ModuleCats[$M['CivMil']] == 'Army') {
        if (($tprop & THING_HAS_ARMYMODULES) == 0) continue; // Armies
      } else if ($M['CivMil'] <= 4) {
        if ($tprop & THING_HAS_SHIPMODULES) { 
          if ($ModuleCats[$M['CivMil']] == 'Military Ship' && ($tprop & THING_HAS_MILSHIPMODS) ==0 ) continue;
          if ($ModuleCats[$M['CivMil']] == 'Civilian Ship' && ($tprop & THING_HAS_CIVSHIPMODS) ==0) continue;
        } else {
          continue;
        }
      } else {
        continue;
      }
      $l = Has_Tech($t['Whose'],$M['BasedOn']);
      if (!$l) continue;
      if ($M['MinShipLevel'] > $t['Level']) continue;
    }
    $VMT[$M['id']] = $M['Name'];
  }
  return $VMT;
}


function Max_Modules(&$t) {
  if (empty($t['Type'])) return 0;
  $ThingTypes = Get_ThingTypes();
  $TTs = $ThingTypes[$t['Type']];
  if (!empty($TTs['Properties']) && ($TTs['Properties'] & THING_HAS_MODULES)) {
    $v = [0,4,12,24,40,60,84,112,144,180,220][$t['Level']];
    if ($t['Type'] == 2) $v = $v*3/4; // Support Ship
    if (Has_Tech($t['Whose'], 'Compact Ship Design') && $t['Level'] > 1 && ($TTs['Properties'] & THING_HAS_SHIPMODULES)) $v += $t['Level'];
    if ($t['Level'] > 2 && Has_Trait($t['Whose'],'Really Big')) $v += $t['Level']*$t['Level']*$t['Level'];
    if ($TTs['Name'] == 'Satellite Defences') $v += $t['Level'];
    if ($TTs['Name'] == 'Planetary Defence Force') {
      if (Has_Trait($Fid,"Strong military")) {
        $v *= 2;
      } else {
        $v += $t['Level'];
      }
    }
    return $v;
  }
  return 0;
}

function Calc_Health(&$t,$KeepTechLvl=0) {
  if (empty($t['Level'])) return 0;
  $Plus = 0;
  if (Has_Trait($t['Whose'],'Thick Skinned')) $Plus =1;
  $Health = 5*($t['Level']+$Plus);
  $Ms = Get_Modules($t['id']);
  $Mts = Get_ModuleTypes();
  $Techs = Get_Techs();
  foreach ($Mts as $mt) if ($mt['DefWep'] == 1 ) {
    foreach ($Ms as $M) if ($Mts[$M['Type']]['Name'] == $mt['Name']) {
      if ($KeepTechLvl) {
          $Mhlth = $M['Number'] * Mod_ValueSimple($M['Level']+$Plus,$M['Type']);
          $Health += $Mhlth;        
      } else {
        $based = $Mts[$M['Type']]['BasedOn'];
        if ($l = Has_Tech($t['Whose'],$based)) {
          if ($Techs[$based]['Cat'] == 1) {
            $l = Has_Tech($t['Whose'],$Techs[$based]['PreReqTech']);
          }
          $Mhlth = $M['Number'] * Mod_ValueSimple($l+$Plus,$M['Type']);
          $Health += $Mhlth;
        }
      }
    }
  }
    
  return $Health;
}

function Calc_Damage(&$t) { 

  if ($t['Type'] ==20) return 8; // Militia
  $Dam = 0;
  $Ms = Get_Modules($t['id']);
  $Mts = Get_ModuleTypes();

  foreach ($Mts as $mt) if ($mt['DefWep'] == 2 ) {
    foreach ($Ms as $M) if ($Mts[$M['Type']]['Name'] == $mt['Name']) { 
      $dam = $M['Number'] * Mod_ValueSimple($M['Level'],$M['Type']);
      $Dam += $dam;
    }
  }
    
  return $Dam;
}

function Calc_TechLevel($Fid,$MType) {
  $Mts = Get_ModuleTypes();
  $Techs = Get_Techs();
  $based = $Mts[$MType]['BasedOn'];
  if ($l = Has_Tech($Fid,$based)) {
    if ($Techs[$based]['Cat'] == 1) {
      $l = Has_Tech($Fid,$Techs[$based]['PreReqTech']);
    }
  }
  return $l;
}

function Thing_Finished($tid) {
  $t = Get_Thing($tid);
  $t['BuildState'] = 3;
  $t['CurHealth'] = $t['OrigHealth'];
  Put_Thing($t);
}


function Moves_4_Thing(&$T, $Force=0, $KnownOnly=0, &$N=0 ) {
  if ($Force) {
    $GM = 0;
  } else {
    $GM = Access('GM');    
  }
  if ($T['LinkId'] <0) return [[],[],[]];
//var_dump($T);exit;
  $Fid = $T['Whose'];
  if (!$N) $N = Get_System($T['SystemId']);
  $LinkTypes = Get_LinkLevels();

  $Links = (empty($N['Ref']) ? [] : Get_Links($N['Ref']));
  $SelLinks = [''];
  $SelCols = [''];
    if ($GM || Has_Tech($T['Whose'],'Know All Links')) {
      foreach ($Links as $L) {
        $SelLinks[$L['id']] = "#" . $L['id'] . " to " . (($L['System1Ref'] == $N['Ref'])?$L['System2Ref']: $L['System1Ref'] );
        $SelCols[$L['id']] = $LinkTypes[$L['Level']]['Colour'];
      }
    } else {
      $NearNeb = $N['Nebulae'];
      $NS = Get_FactionSystemFS($Fid,$N['id']);

      foreach ($Links as $Lid=>$L) {
        $LinkText = "Unknowm";
        $FL = Get_FactionLinkFL($Fid,$L['id']);
        $FarSysRef =  (($L['System1Ref'] == $N['Ref'])?$L['System2Ref']: $L['System1Ref'] );
        $FSN = Get_SystemR($FarSysRef);
        $FarNeb = $FSN['Nebulae'];
        $FS = Get_FactionSystemFS($Fid,$FSN['id']);
//echo "<p>doing link " . $L['id'] . " to $FarSysRef ". $FSN['id'] ; var_dump($FS);
        if (isset($FL['Known']) && $FL['Known']) {
          $LinkText = $FarSysRef;
        } else if ($NearNeb == 0) {
          if (isset($FS['id'])) {
            if ($FarNeb == 0) {
              $LinkText = $FarSysRef;
            } else if ($FS['NebScanned'] >= $FarWeb) {
              $LinkText = $FarSysRef;
            } else {
              if ($KnownOnly) {
                unset($Links[$Lid]);
                continue;
              }
              $LinkText = '?';
            }
          } else {
            if ($KnownOnly) {
              unset($Links[$Lid]);
              continue;
            }
            $LinkText = '?';
          }
        } else if ($NS['NebScanned'] >= $NearNeb) { // In a Neb...
          if (isset($FS['id'])) {
            $LinkText = $FarSysRef;
          } else {
            if ($KnownOnly) {
              unset($Links[$Lid]);
              continue;
            }
            $LinkText = '?';
          }
        } else {
          unset($Links[$Lid]);
          continue;
//          $LinkText = '?';
        }
        $SelLinks[$L['id']] = "#" . $L['id'] . " to " . $LinkText;
        $SelCols[$L['id']] = $LinkTypes[$L['Level']]['Colour'];
      }
    }
// var_dump($Links,"<br>",$SelLinks,"<p>");
  return [$Links, $SelLinks, $SelCols ];
}

function Scanners(&$T) {
  if (empty($T['Sensors']) || empty($T['SensorLevel'])) return 0;
  return $T['Sensors'] * $T['SensorLevel'];
}

function NebScanners(&$T) {
  if (empty($T['NebSensors']) || empty($T['SensorLevel'])) return 0;
  return $T['NebSensors'] * $T['SensorLevel'];
}

function Calc_Scanners(&$T) {
  $mods = Get_ModulesType($T['id'],4);
  $nebs = Get_ModulesType($T['id'],9);
  $Cargo = Get_ModulesType($T['id'],8);
  $T['Sensors'] = ($mods?$mods[0]['Number']:0);
  $T['SensorLevel'] = ($mods?$mods[0]['Level']:0);
  $T['NebSensors'] = ($nebs?$nebs[0]['Number']:0);
  $T['CargoSpace'] = ($Cargo?$Cargo[0]['Number']*($Cargo[0]['Level']+1):0);
  if ($Deep = Get_ModulesType(3,$T['id'])) {
    $T['HasDeepSpace'] = $Deep[0]['Number']*$Deep[0]['Level'];
  }else {
    $T['HasDeepSpace'] = 0;
  }
}

function RefitRepair(&$T,$Save=1,$KeepTechLvl=0) {
  if (empty($T['Level']) ) return 0;
// Refit
  $TTypes = Get_ThingTypes();
  $tid = $T['id'];
//  $Engines = (($TTypes[$T['Type']]['Properties'] & THING_CAN_MOVE)? 1:0);
  $Elvl = 1;
  $Engines = 0;
  $Mods = Get_Modules($tid); 
  if ($Mods) {
    foreach ($Mods as $M) {
      if ($KeepTechLvl) {
        if ($M['Type'] == 5) { 
          $Engines = $M['Number'];
          $Elvl = $M['Level'];
        }
      } else {
        $Lvl = Calc_TechLevel($T['Whose'],$M['Type']);
        if ($M['Type'] == 5) { 
          $Engines = $M['Number'];
          $Elvl = $Lvl;
        }
        $M['Level'] = $Lvl;
        Put_Module($M);
      }
    }
  }
// Repair
  $Health = Calc_Health($T,$KeepTechLvl);
  Calc_Scanners($T);
  $T['CurHealth'] = $T['OrigHealth'] = $Health;
  $T['Speed'] = $Engines*$Elvl/$T['Level'] +1;
  if ($Save) Put_Thing($T);
}

//  &#8373; = Credit symbol

function LogisticalSupport($Fid) {  // Note this sets the Economic rating of all PHs
  $PHomes = Get_ProjectHomes($Fid);
  
  $Logistics = [0,0,0];

  $ThingTypes = Get_ThingTypes();
  $DistTypes = Get_DistrictTypes();
  
  foreach($PHomes as $H) {
//    $PH['Economy'] = 0;
    $Commerce = 0;
    $Hi = $H['id'];
    switch ($H['ThingType']) {
    case 1: // Planet
      $PH = Get_Planet($H['ThingId']);
      $Dists = Get_DistrictsP($H['ThingId']);
      $EndAct = 'Put_Planet';
      break;
    case 2: // Moon
      $PH = Get_Moon($H['ThingId']);
      $Dists = Get_DistrictsM($H['ThingId']);
      $EndAct = 'Put_Moon';
      break;
    case 3: // Thing
      $PH = Get_Thing($H['ThingId']); // Filter for economic things - outposts, ast mines etc??
      $Dists = Get_DistrictsT($H['ThingId']);
      if (!$Dists) {
        $H['Skip'] = 1;
//        Put_Thing($PH);
        continue 2;  // Remove things without districts
      }
      $EndAct = 'Put_Thing';
      break;
    }
    
    $Prime = $Mine = 0;
    $District_Type = [];
    foreach ($Dists as $D) {
      if ($D['Type'] == 0) continue;
      if ($DistTypes[$D['Type']]['Props'] &1) {
        if (Has_Trait($Fid,'No customers') && $DistTypes[$D['Type']]['Name'] == 'Commerce') {
        } else {
          $Prime++;
        }
      }
      switch ($DistTypes[$D['Type']]['Name']) {
      case 'Commerce' :
        $Commerce+=$D['Number'];
        break;
      case 'Military' :
        $Logistics[1]+= $D['Number']*3;
        break;
      case 'Shipyard' :
        $Logistics[0]+= $D['Number']*3;
        break;
      case 'Intelligence' :
        $Logistics[2]+= $D['Number']*3;
        break;
      case 'Mining':
        $Mine += $D['Number'];
        break;
      default:
        break;
      }
    }

//var_dump($Prime, $Commerce, $Mine, $PH);
//    $H['Economy'] = $Prime * $Commerce*2 + (isset($PH['Minerals'])? (min($PH['Minerals'], $Commerce) + min($PH['Minerals'], $Mine*2)):0);
//    Put_ProjectHome($H);
//    $EndAct($PH);
  }

//var_dump($Logistics); echo "<br>";
  if ($ln = Has_Tech($Fid,'Naval Logistics')) $Logistics[0] += 2*$ln;
  if ($ln = Has_Tech($Fid,'Army Logistics')) $Logistics[1]  += 2*$ln;
  if ($ln = Has_Tech($Fid,'Intelligence Logistics')) $Logistics[2] += 2*$ln;
//var_dump($Logistics);
  return $Logistics;  
}

function Thing_Duplicate($otid) {
  $t = Get_Thing($otid);
  unset($t['id']);
  $t['Name'] = "Copy of " . $t['Name'];
  $t['id'] = $tid = Insert_db('Things',$t);
  $Discs = Get_DistrictsT($otid);
  $t['SystemId'] = 0;
  $t['LinkId'] = 0;
  $t['WithinSysLoc'] = 0;
  $t['BuildState'] = 0;
  $t['History'] = 0;
  
  $Fid = $t['Whose'];
       
  if ($Discs) {
    foreach ($Discs as $D) {
      $D['HostId'] = $tid;
      unset($D['id']);
      Insert_db('Districts',$D);
    }
  }
  $Mods = Get_Modules($otid); 
  if ($Mods) {
    foreach ($Mods as $M) {
      $M['ThingId'] = $tid;
      $Lvl = Calc_TechLevel($Fid,$M['Type']);
      unset($M['id']);
      $M['Level'] = $Lvl;
      Insert_db('Modules',$M);
    }
  }
  Put_Thing($t);
  return $t;
}

function EyesInSystem($Fid,$Sid) { // Eyes 1 = in space, 2= sens, 4= neb sens, 8=ground
//var_dump($Fid,$Sid);
  $Neb = $Eyes = 0;
  $ThingTypes = Get_ThingTypes();
  $MyThings = Get_Things_Cond($Fid," SystemId=$Sid AND (BuildState=2 OR BuildState=3)");
  $N = Get_System($Sid);
  if ($N) $Neb = $N['Nebulae'];

//var_dump($MyThings);
  foreach ($MyThings as $T) {
    if (($Neb > 0) && ($T['NebSensors'] < $Neb)) continue;
    $Eyes |= $ThingTypes[$T['Type']]['Eyes'];
    if ($T['Sensors']) $Eyes |= 2;
    if ($T['NebSensors']) $Eyes != 4;
  }
  return $Eyes;
}

function SeeThing(&$T,&$LastWhose,$Eyes,$Fid,$Images,$GM=0) {
  global $Advance;
  static $ThingTypes;
  static $Factions;
  if (!$ThingTypes) $ThingTypes = Get_ThingTypes();
  if (!$Factions) $Factions = Get_Factions();
  
      if ($Fid >=0 && ($T['Whose'] != $Fid) && (($ThingTypes[$T['Type']]['SeenBy'] & $Eyes) == 0 )) return;
      if ($T['BuildState'] < 2 || $T['BuildState'] > 4) return; // Building or abandoned
      if ($LastWhose && $LastWhose!= $T['Whose']) echo "<P>";
      if ($T['BuildState'] == 4) echo "The remains of: ";
      if ($T['Whose']) {
        echo ((($Fid < 0) || ($Fid == $T['Whose']))?( "<a href=ThingEdit.php?id=" . $T['id'] . ">" . (empty($T['Name'])?"Unnamed":$T['Name']) . "</a>") : $T['Name'] ) . " a ";
      } 
      if ($ThingTypes[$T['Type']]['Properties'] & THING_HAS_LEVELS) echo " level " . $T['Level'];
      if ($T['Class']) echo " " . $T['Class'] . " class ";
      if ($T['Whose']) echo " <span style='background:" . $Factions[$T['Whose']]['MapColour'] . "'>" . 
        ($Factions[$T['Whose']]['Adjective']?$Factions[$T['Whose']]['Adjective']:$Factions[$T['Whose']]['Name']) . "</span>";
      if (($T['Whose'] == $Fid) && ($ThingTypes[$T['Type']]['Properties'] & THING_CAN_BE_ADVANCED) && ($T['Level'] > 1)) echo ' ' . $Advance[$T['Level']];
      if ($T['Whose']==0 && Access('GM')) {
        echo "<a href=ThingEdit.php?id=" . $T['id'] . ">" . $ThingTypes[$T['Type']]['Name'] . "</a>";
      } else {
        echo " " . $ThingTypes[$T['Type']]['Name'];
      }
      if ($GM && !empty($T['Orders'])) echo ", <span style='background:#ffd966;'>Orders: " . $T['Orders'] . "</span>";
      if ($Images && !empty($T['Image'])) echo " <img valign=top src=" . $T['Image'] . " height=100> ";
      echo "<br clear=all>\n";
      $LastWhose = $T['Whose'];
}

function SeeInSystem($Sid,$Eyes,$heading=0,$Images=1,$Fid=0) {
  global $Advance;
  include_once("SystemLib.php");
//var_dump($Sid,$Eyes);
//  if (Access('GM')) $Eyes = 15;
    if (!$Eyes) return;
    $Things = Get_AllThingsAt($Sid);
    if (!$Things) return;
    
  $GM = Access('GM');
//    $ThingTypes = Get_ThingTypes();
  
//    $Factions = Get_Factions();
    
//if ($Sid == 58) var_dump ($Things); echo "XX<p>";   
    $N = Get_System($Sid);
    if ($heading) {
       $Col = 'White';
       if ($N['Control']) {
         $Fac = Get_Faction($N['Control']);
         $Col = $Fac['MapColour'];
       }
       echo "<h2 style='background:$Col;'>System " . System_Name($N,$Fid) . "</h2>"; 
    } else {
       echo "<h2>In the System is:</h2>";
    }
    $LastWhose = 0;
    foreach ($Things as $T) {
      SeeThing($T,$LastWhose,$Eyes,$Fid,$Images,$GM); //,$ThingTypes,$Factions);
    };
}

function Update_Militia(&$W,&$Dists) {
  switch ($W['ThingType']) {
    case 1: // Planet
      $P = Get_Planet($W['ThingId']);
      $Mname = $P['Name'];
      $Sys = $P['SystemId'];
      $N = Get_System($Sys);
      $loc = Within_Sys_Locs($N,$W['ThingId'],0,1);
      break;
    case 2: // Moon
      $M = Get_Moon($W['ThingId']);
      $Mname = $M['Name'];
      $P = Get_Planet($M['PlanetId']);
      $Sys = $P['SystemId'];
      $N = Get_System($Sys);
      $loc = Within_Sys_Locs($N,-$W['ThingId'],0,1);
      break;
    case 3: // Thing TODO
      echo "Not yet available";
      return;
  }
  
  $Mils = Get_Things_COND(0,"Type=20 AND SystemId=$Sys AND WithinSysLoc=$loc ");
  
  $Dcount = 0;
  foreach($Dists as $D) $Dcount += ($D['Number'] * ($D['Type'] ==2?2:1));

  if (count($Mils) >= $Dcount) {
    if (count($Mils) > $Dcount) {
      echo "<h2 class=Err>Too many setup...</h2>";
    } else {
      echo "<h2>Militia already setup</h2>";
    }
  } else {
    $MNames = [];
    foreach ($Mils as $Ml) $MNames[$Ml['Name']] = 1; // Didts & Dist2 give short cut to world and districts
    $M = ['Type'=>20, 'CurHealth'=>40, 'OrigHealth'=>40, 'Whose'=>$W['FactionId'], 'SystemId'=>$Sys, 'WithinSysLoc'=>$loc, 'BuildState'=>3, 
           'Dist1'=> $W['ThingType'], 'Dist2'=>$W['ThingType']];
    $Mn = 1;
    for ($Mnum = count($Mils); $Mnum < $Dcount; $Mnum++) {
      while (isset($MNames["Militia $Mname $Mn"])) $Mn++;
      $MNames["Militia $Mname $Mn"] = 1;
      $M['Name'] = "Militia $Mname $Mn";
      unset($M['id']);
      Put_Thing($M);
      echo $M['Name'] . " created<br>";
    }
  }
}

function Thing_Types_From_Names(&$TTs=0) {
  if ($TTs == 0) $TTs = Get_ThingTypes();
  $TTN = [];
  foreach ($TTs as $TT) $TTN[$TT['Name']] = $TT['id'];
  return $TTN;
}

function Mod_Types_From_Names(&$TTs=0) {
  if ($TTs == 0) $TTs = Get_ModuleTypes();
  $TTN = [];
  foreach ($TTs as $TT) $TTN[$TT['Name']] = $TT['id'];
  return $TTN;
}

function Check_MyThing(&$t,$Fid=0) {
  if ($t['Whose'] == $Fid) return;
  if (Access('GM')) return;
  echo "<h2 class=Err>Not Your Thing...</h2>\n";
  dotail();

}

?>
