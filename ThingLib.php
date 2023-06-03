<?php
// Lib of Thing related stuff
include_once("sk.php");
include_once("GetPut.php");
include_once("vendor/erusev/parsedown/Parsedown.php");
include_once("PlayerLib.php");  

global $ModuleCats,$ModFormulaes,$ModValues,$Fields,$Tech_Cats,$CivMil,$BuildState,$ThingInstrs,$InstrMsg,$Advance,$ValidMines;

$ModuleCats = ['Ship','Civilian Ship','Support Ship','Military Ship','Army','Other'];
$Fields = ['Engineering','Physics','Xenology'];
$Tech_Cats = ['Core','Supp','Non Std'];
$CivMil = ['','Civilian','Military'];
$BuildState = ['Planning','Building','Shakedown','Complete','Ex','Abandonded','Missing In Action','Captured'];
$ThingInstrs = ['None','Colonise','Voluntary Warp Home','Decommision','Analyse Anomaly','Establish Embassy','Make Outpost','Make Asteroid Mine','Make Minefield',
                'Make Orbital Repair Yard','Build Space Station','Expand Space Station','Make Deep Space Sensor','Make Advanced Asteroid Mine','Build Stargate',
                'Make Planet Mine', 'Construct Command Relay Station', 'Repair Command Node','Build Planetary Mine','Dismantle Stargate','Disband','Transfer',
                'Make Something','Make Warpgate','Retire','Stop Support','Make Advanced Minefield','Clear Minefield', 'Make Advanced Deep Space Sensor','Salvage',
                'Terraform','Link Repair','Collaborative DSC'];
$Advance = ['','','Advanced ','Very Advanced ','Ultra Advanced ','Evolved '];
$ValidMines = [0,1,0,1,0,1,0,0,0,0,0];
$LinkStates = ['','Under Repair','In Safe Mode','Locked'];

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
define('THING_CAN_BE_CREATED',8192);
define('THING_NEEDS_CARGOSPACE',16384);
define('THING_CANT_HAVENAMED',32768);
define('THING_CAN_DO_PROJECTS',65536);
define('THING_MOVES_DIRECTLY',131072);
define('THING_MOVES_AFTER',262144);
define('THING_HAS_HEALTH',524288);
define('THING_HAS_CONTROL',1048576);
define('THING_NEEDS_SUPPORT',2097152);
define('THING_IS_HOSTILE',4194304);
define('THING_CAN_BE_SPLATED',8388608);
define('THING_LEAVES_DEBRIS',16777216);

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

function Mod_ValueSimple($tl,$modtypeid,&$Rescat) {
//echo "Mod Value of $tl, $modtypeid<p>";
  $mt = Get_ModuleType($modtypeid);
  $mf = Get_ModFormula($mt['Formula']);
  if ($mf['Num2x']) {
    $v = ($mf['Num1x'] + $tl) * $mf['Num2x'];
  } else {
    $v = 0;
  }
  $v = $v + $mf['Num3x'];
  if ($mf['Name'] != 'Basic Weapons') $Rescat = 1;
//echo "Is $v<p>";
  return $v;
}


function Show_Tech(&$T,&$CTNs,&$Fact=0,&$FactTechs=0,$Descs=1,$Setup=0,$lvl=0) {
  global $ModuleCats,$ModFormulaes,$ModValues,$Fields,$Tech_Cats,$CivMil;
  static $AllTechs;
  if (empty($AllTechs)) $AllTechs = Get_Techs(0);
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
    if ($T['PreReqTech2']) {
      echo "Also requires: " . $AllTechs[$T['PreReqTech2']]['Name'];
      if ($T['PreReqTech3']) echo " and  " . $AllTechs[$T['PreReqTech3']]['Name'];
      echo "<br>";
    }
    if ($T['MinThingLevel']) echo "Size Limitation - Requires at least level " . $T['MinThingLevel'] . " ship<br>";
    break;
  }
  
  if ($T['Slots']) echo "Uses " . $T['Slots'] . " module " . Plural($T['Slots'],"","slot","slots") . "  ";
  if ($T['CivMil']) echo "<b>" . $CivMil[$T['CivMil']] . " ships</b>";
  echo "<br>";
  
  if (!$Descs) echo "<div id=TDesc$Tid hidden>";
  if ($T['Description']) echo  $Parsedown->text($T['Description']);

//  if ($lvl == 0) {
    if (!$Descs) echo "</div>";  
    echo "</div><p>";
//  }

}

function Tech_Names(&$Techs) {
  $TNs = [0=>''];
  foreach($Techs as $T) $TNs[$T['id']] = $T['Name'];
  return $TNs;
}


function Thing_Type_Names() {
  $tts = Get_ThingTypes();
  $tns = [];
  foreach ($tts as $T) $tns[$T['id']] = $T['Name'];
  return $tns;
}

function Thing_Type_Props() {
  $tts = Get_ThingTypes();
  $tns = [];
  foreach ($tts as $T) $tns[$T['id']] = $T['Properties'];
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

function Within_Sys_Locs_Id($Nid,$PM=0,$Boarding=0,$Restrict=0,$Hab=0) {// $PM +ve = Planet Number, -ve = Moon Number, restrict 1=ON, 2space only
  $N = Get_System($Nid);
  return Within_Sys_Locs($N,$PM,$Boarding,$Restrict,$Hab);
}

function Get_Valid_Modules(&$T,$Other=0) {
  global $ModuleCats;
  $MTs = Get_ModuleTypes();
  if ($Other == 0) $Other = $T['Whose'];
  $VMT = [];
  $ThingProps = Thing_Type_Props();
  $tprop = (empty($ThingProps[$T['Type']] )?0: $ThingProps[$T['Type']] ) ;
  foreach ($MTs as $M) {
    if ($T['Whose']) {
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
      $l = Has_Tech($Other,$M['BasedOn']);
      if (!$l) continue;
      if ($M['MinShipLevel'] > $T['Level']) continue;
    }
    $VMT[$M['id']] = $M['Name'];
  }
  return $VMT;
}


function Max_Modules(&$T) {
  if (empty($T['Type'])) return 0;
  $ThingTypes = Get_ThingTypes();
  $TTs = $ThingTypes[$T['Type']];
  if (!empty($TTs['Properties']) && ($TTs['Properties'] & THING_HAS_MODULES)) {
    $v = [0,4,12,24,40,60,84,112,144,180,220][$T['Level']];
    if ($T['Type'] == 2) $v = $v*3/4; // Support Ship
    if (Has_Tech($T['Whose'], 'Compact Ship Design') && $T['Level'] > 1 && ($TTs['Properties'] & THING_HAS_SHIPMODULES)) $v += $T['Level'];
    if ($T['Level'] > 2 && Has_Trait($T['Whose'],'Really Big')) $v += $T['Level']*$T['Level']*$T['Level'];
    if ($TTs['Name'] == 'Satellite Defences') $v += $T['Level'];
    if ($TTs['Name'] == 'Planetary Defence Force') {
      if (Has_Trait($T['Whose'],"Strong military")) {
        $v *= 2;
      } else {
        $v += $T['Level'];
      }
    }
    return $v;
  }
  return 0;
}

function Calc_Health(&$T,$KeepTechLvl=0,$Other=0) {
  if (empty($T['Level'])) return 0;
  $Plus = 0;
  if ($T['Type'] == 20) { // Militia
    return 40 + Has_Tech($T['Whose'],'Militia Training Techniques')*2;
  }
  
  if ($Other == 0) $Other = $T['Whose'];
  if (Has_Trait($Other,'Thick Skinned')) $Plus =1;
  $Health = 5*($T['Level']+$Plus);
  $Ms = Get_Modules($T['id']);
  $Mts = Get_ModuleTypes();
  $Techs = Get_Techs($Other);
  $Rescat = 0;
  foreach ($Mts as $mt) if ($mt['DefWep'] == 1 ) {
    foreach ($Ms as $M) if (($Mts[$M['Type']]['Name'] == $mt['Name']) && ($M['Number'] > 0)) { 
      if ($KeepTechLvl) {
          $Mhlth = $M['Number'] * Mod_ValueSimple($M['Level']+$Plus,$M['Type'],$Rescat);
          $Health += $Mhlth;        
      } else {
        $based = $Mts[$M['Type']]['BasedOn'];
        if ($l = Has_Tech($Other,$based)) {
          if ($Techs[$based]['Cat'] == 1) {
            $l = Has_Tech($Other,$Techs[$based]['PreReqTech']);
          }
          $Mhlth = $M['Number'] * Mod_ValueSimple($l+$Plus,$M['Type'],$Rescat);
          $Health += $Mhlth;
        }
      }
    }
  }
    
  return $Health;
}

function Calc_Damage(&$T,&$Rescat) { 

  if ($T['Type'] ==20) return 8; // Militia
  $Dam = 0;
  $Ms = Get_Modules($T['id']);
  $Mts = Get_ModuleTypes();

  $Rescat = 0;
  foreach ($Mts as $mt) if ($mt['DefWep'] == 2 ) {
    foreach ($Ms as $M) if (($Mts[$M['Type']]['Name'] == $mt['Name']) && ($M['Number'] > 0)) { 
      $dam = ($M['Level'] > 0?$M['Number'] * Mod_ValueSimple($M['Level'],$M['Type'],$Rescat):0);
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

function Thing_Finished($Tid) {
  $T = Get_Thing($tid);
  $T['BuildState'] = 3;
  $T['CurHealth'] = $T['OrigHealth'];
  Put_Thing($T);
}


function Moves_4_Thing(&$T, $Force=0, $KnownOnly=0, &$N=0 ) {
  global $LinkStates;
  if ($Force) {
    $GM = 0;
  } else {
    $GM = Access('GM');    
  }
  $KnownOnly=0;
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
        $SelCols[$L['id']] = $LinkTypes[abs($L['Level'])]['Colour'];
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
            } else if ($FS['NebScanned'] >= $FarNeb) {
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
        $SelLinks[$L['id']] = "#" . $L['id'] . " to " . $LinkText . ($L['Status'] != 0) . " - " . $LinkStates[$L['Status']];
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
  return floor($T['NebSensors'] * $T['SensorLevel'] / 2);
}

function Calc_Scanners(&$T) {
  $MTNames = Gen_Get_Names_Flip('ModuleTypes');
  $mods = Get_ModulesType($T['id'],$MTNames['Sensors']);
  $nebs = Get_ModulesType($T['id'],$MTNames['Nebula Sensors']);
  $Cargo = Get_ModulesType($T['id'],$MTNames['Cargo Space']);
  $Cryo = Get_ModulesType($T['id'],$MTNames['Cryo Pods']);
  $T['Sensors'] = (($mods && ($mods[0]['Number']>0))?$mods[0]['Number']:0);
  $T['SensorLevel'] = (($mods && ($mods[0]['Level']>0))?$mods[0]['Level']:0);
  $T['NebSensors'] = ( (($nebs && ($nebs[0]['Number']>0))?$nebs[0]['Number']:(($T['Type'] == 9 && $T['Level'] > 1)?($T['Level']-1):0)));
  $T['CargoSpace'] = ( (($Cargo && ($Cargo[0]['Number']>0))?$Cargo[0]['Number']*($Cargo[0]['Level']+1):0) +
                       (($Cryo  && ($Cryo[0]['Number']>0))?$Cryo[0]['Number']*($Cryo[0]['Level']+3):0));
  if (($Deep = Get_ModulesType($T['id'],$MTNames['Deep Space Construction'] )) && ($Deep[0]['Number']>0)) {
    $T['HasDeepSpace'] = $Deep[0]['Number']*$Deep[0]['Level'];
  }else {
    $T['HasDeepSpace'] = 0;
  }
}

function RefitRepair(&$T,$Save=1,$KeepTechLvl=0,$Other=0) {
  if (empty($T['Level']) ) return 0;
// Refit
  $TTypes = Get_ThingTypes();
  $MTypes = Get_ModuleTypes();
  $tid = $T['id'];
//  $Engines = (($TTypes[$T['Type']]['Properties'] & THING_CAN_MOVE)? 1:0);
  $Elvl = 1;
  $Engines = 0;
  $Mods = Get_Modules($tid); 
  $Etxt = "";
  $VMTs = Get_Valid_Modules($T,$Other) ;
  if ($Mods) {
    foreach ($Mods as $M) {
      if ($KeepTechLvl) {
        if ($M['Type'] == 5) { 
          $Engines = $M['Number'];
          $Elvl = $M['Level'];
        }
        continue;
      }
      if (isset($VMTs[$M['Type']])) {
        $Lvl = Calc_TechLevel(($Other?$Other:$T['Whose']),$M['Type']);
        if ($Lvl) {
          if ($M['Type'] == 5) { 
            $Engines = $M['Number'];
            $Elvl = $Lvl;
          }
          $M['Level'] = $Lvl;
          $M['Number'] = abs($M['Number']);
          Put_Module($M);
        } else {
          $Etxt .= abs($M['Number']) . " " . $MTypes[$M['Type']]['Name'] . Plural($M['Number']," Modules"," Module"," Modules") . " inactive as unknown tech";
          $M['Number'] = - abs($M['Number']);
        }
      } else {
          $Etxt .= abs($M['Number']) . " " . $MTypes[$M['Type']]['Name'] . Plural($M['Number']," Modules"," Module"," Modules") . " inactive as unknown tech";
          $M['Number'] = - abs($M['Number']);      
      }
    }
  }
// Repair
  $Health = Calc_Health($T,$KeepTechLvl,$Other);
  Calc_Scanners($T);
  $T['CurHealth'] = $T['OrigHealth'] = $Health;
  $T['Speed'] = $Engines*$Elvl/$T['Level'] +1;
  Put_Thing($T);
  return $Etxt;
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
  $T = Get_Thing($otid);
  unset($T['id']);
  $T['Name'] = "Copy of " . $T['Name'];
  $T['id'] = $Tid = Insert_db('Things',$T);

  $T['SystemId'] = 0;
  $T['LinkId'] = 0;
  $T['WithinSysLoc'] = 0;
  $T['BuildState'] = 0;
  $T['History'] = 0;
  $T['Instruction'] = 0;
  $T['LinkPay'] = 0;
  $T['LinkCost'] = 0;
  $T['Progress'] = 0;
  $T['ActionsNeeded'] = 0;
  $T['Dist1'] = 0;
  $T['Dist2'] = 0;
  $T['CurInst'] = 0;
  $T['MakeName'] ='';
  $T['CurHealth'] = $T['OrigHealth'];
  $T['PrisonerOf'] = 0;
  $T['ProjHome'] = 0;

    
  $Fid = $T['Whose'];
 
  $Dists = Get_DistrictsT($otid);      
  if ($Dists) {
    foreach ($Dists as $D) {
      $D['HostId'] = $Tid;
      unset($D['id']);
      Insert_db('Districts',$D);
    }
  }
  $Mods = Get_Modules($otid); 
  if ($Mods) {
    foreach ($Mods as $M) {
      $M['ThingId'] = $Tid;
      $Lvl = Calc_TechLevel($Fid,$M['Type']);
      unset($M['id']);
      $M['Level'] = $Lvl;
      Insert_db('Modules',$M);
    }
  }
  Put_Thing($T);
  return $T;
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
    if ($T['SeenTypeMask']) continue;
    if (($Neb > 0) && ($T['NebSensors'] < $Neb)) continue;
    if ($T['PrisonerOf']) continue;
    $Eyes |= $ThingTypes[$T['Type']]['Eyes'];
    if ($T['Sensors']) $Eyes |= 2;
    if ($T['NebSensors']) $Eyes != 4;
  }
// echo "System " . $N['Ref'] . " eyes: $Eyes<br>";
  return $Eyes;
}

function is_vowel(&$Text) {
  return strpos(" aeiou",strtolower(substr($Text,0,1)));
}

function SeeThing(&$T,&$LastWhose,$Eyes,$Fid,$Images,$GM=0) {
  global $Advance,$FACTION;
  static $ThingTypes;
  static $Factions;
  if (!$ThingTypes) $ThingTypes = Get_ThingTypes();
  if (!$Factions) $Factions = Get_Factions();
  $Locations = Within_Sys_Locs_Id($T['SystemId']);
  
  $txt = '';
  $RawA = 0;
  
//  if ($T['id'] == 238) { echo "Here with outpost<br>"; var_dump($T); }
  $TTprops = $ThingTypes[$T['Type']]['Properties'];
  
  if ($T['CurHealth'] == 0 && ($TTprops & THING_CAN_BE_SPLATED)) return '';
  
  $stm = (int) (~ (int)$T['SeenTypeMask']);
//echo "Failed to see:" . $ThingTypes[$T['Type']]['SeenBy'] . ":$stm:" . $T['SeenTypeMask'] . ":$Eyes<p>";
//var_dump($stm);
      if ($Fid >=0 && ($T['Whose'] != $Fid) && ($T['PrisonerOf'] != $Fid) &&
          ((($ThingTypes[$T['Type']]['SeenBy'] & $stm & $Eyes) == 0 ))) {
// echo "Failed to see:" . $ThingTypes[$T['Type']]['SeenBy'] . ":$stm:$Eyes<p>";
        return '';
      }
      if ($T['BuildState'] < 2 || $T['BuildState'] > 4) return ''; // Building or abandoned
      if ($LastWhose && $LastWhose!= $T['Whose']) $txt .= "<P>";
      if (($T['BuildState'] == 4) || (($T['Type'] == 23) && $GM)) { // Named Chars
        if ($GM) {
          $txt .= "<div class=FullD hidden>";
        } else {
          $txt .= "<div>";
        }
        if ($T['BuildState'] == 4) $txt .= "The remains of: ";
      } else {
        $txt .= "<div>";
      }
        
      if ($T['Whose'] || $GM) {
        $txt .= ((($Fid < 0) || ($Fid == $T['Whose']) || $GM )?( "<a href=ThingEdit.php?id=" . $T['id'] . ">" . 
                (empty($T['Name'])?"Unnamed":$T['Name']) . "</a>") : $T['Name'] ) . " a";
        $RawA = 1;
      } 
      if ($TTprops & THING_HAS_LEVELS) {
        $txt .= " level " . $T['Level'] . " ";
        $RawA = 0;
      }
      if ($T['Class']) {
        if ($RawA && is_vowel($T['Class'])) $txt .= "n";
        $txt .= " " . $T['Class'] . " class ";
        $RawA = 0;
      }
      if ($T['Whose']) {
        $Who = ($Factions[$T['Whose']]['Adjective']?$Factions[$T['Whose']]['Adjective']:$Factions[$T['Whose']]['Name']);
        if ($RawA && is_vowel($Who)) $txt .= "n";      
        $txt .= " <span style='background:" . $Factions[$T['Whose']]['MapColour'] . "'>$Who</span>";
        $RawA = 0;
      }
      if (($T['Whose'] == $Fid) && ($TTprops & THING_CAN_BE_ADVANCED) && ($T['Level'] > 1)) $txt .= ' ' . $Advance[$T['Level']];
      if ($T['Whose']==0 && Access('GM')) {
        $txt .= "<a href=ThingEdit.php?id=" . $T['id'] . ">" . $ThingTypes[$T['Type']]['Name'] . "</a>";
      } else {
        $txt .= " " . $ThingTypes[$T['Type']]['Name'];
      }
 
//var_dump($Locations);exit;
      if ($T['LinkId'] >= 0 && $T['SystemId'] > 0 && isset($T['WithinSysLoc']) && ($T['WithinSysLoc'] > 0)) {
        if (isset($Locations[$T['WithinSysLoc']])) {
          $txt .= " ( " . $Locations[$T['WithinSysLoc']] . " ) ";
        } else if ($GM) {
          $txt .= " ( Unknown Location " . $T['WithinSysLoc'] . " ) ";
        }
      }
      
      if ($T['PrisonerOf']) {
        if ($GM || (isset($FACTION['id']) && $T['PrisonerOf'] == $FACTION['id'])) {
          $Fact = Get_Faction($T['PrisonerOf']);
          $txt .= ", Prisoner Of: <span style='background:" . $Fact['MapColour'] . ">" . $Fact['Name'] . "</span>";
        }
      } else if ($GM && !empty($T['Orders'])) $txt .= ", <span style='background:#ffd966;'>Orders: " . $T['Orders'] . "</span>";
      if ($Images && !empty($T['Image'])) $txt .= " <img valign=top src=" . $T['Image'] . " height=100> ";
      
      if ($GM) {
        $Resc =0;
        $BD = Calc_Damage($T,$Resc);
           
        $txt .= " (";
        if ($TTprops & THING_HAS_HEALTH) {
          if ($TTprops & THING_CAN_MOVE) $txt .= "Speed: " . sprintf("%0.3g, ",$T['Speed']) ;
          $txt .= "Health: " . $T['CurHealth'] . "/" . $T['OrigHealth'] . ", "; 
        }
        if ($BD) $txt .= "Dam: " . $BD . ($Resc? "<b>*</b>":'') . ", ";
        if ($T['NebSensors']) $txt .= "N, ";
        $txt .= ")";
      }
      $txt .= "<br clear=all>\n";
      if ($GM) $txt .= "</div>";
      $LastWhose = $T['Whose'];
// if ($T['id'] == 238) echo "Txt is:$txt<p>";
   return $txt;
}

function SeeInSystem($Sid,$Eyes,$heading=0,$Images=1,$Fid=0,$Mode=0) {
  global $Advance;
  include_once("SystemLib.php");
  $txt ='';
// var_dump($Sid,$Eyes);
//  if (Access('GM')) $Eyes = 15;
    if (!$Eyes) return '';
    $Things = Get_AllThingsAt($Sid);
    if (!$Things) return '';
    
  $GM = (Access('GM') & $Mode);
//    $ThingTypes = Get_ThingTypes();
  
//    $Factions = Get_Factions();
    
//if ($Sid == 4) var_dump ($Things); echo "XX<p>";   
    $N = Get_System($Sid);
    if ($heading) {
       $Col = 'White';
       if ($N['Control']) {
         $Fac = Get_Faction($N['Control']);
         $Col = $Fac['MapColour'];
       }
       $txt .= "<h2 style='background:$Col;'>System " . System_Name($N,$Fid) . "</h2>"; 
    } else {
       $txt .= "<h2>In the System is:</h2>";
    }
    $LastWhose = 0;
    foreach ($Things as $T) {
      $txt .= SeeThing($T,$LastWhose,$Eyes,$Fid,$Images,$GM); //,$ThingTypes,$Factions);
    };
  return $txt;
}

function Update_Militia(&$W,&$Dists,$NewOwn=0) {
//var_dump($W,$Dists,$NewOwn);
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
  
  $FactN = ($NewOwn?$NewOwn:$W['FactionId']);
  $Mils = Get_Things_Cond(0,"Type=20 AND SystemId=$Sys AND WithinSysLoc=$loc ");
  $Hlth = 40+Has_Tech($FactN,'Militia Training Techniques')*2;
  
  $Dcount = 0;
  foreach($Dists as $D) $Dcount += ($D['Number'] * ($D['Type'] ==2?2:1));
//echo count($Mils);
  if (count($Mils) >= $Dcount) {
    if (count($Mils) > $Dcount) {
      echo "<h2 class=Err>Too many setup...</h2>";
    } else {
      echo "<h2>Militia already setup</h2>";
    }
    foreach($Mils as $Ml) {
      if (($Ml['OrigHealth'] != $Hlth) || ($Ml['Whose'] != $FactN)) {
        $Diff = $Hlth - $Ml['OrigHealth'];
        $Ml['OrigHealth'] = $Hlth;
        $Ml['CurHealth'] = min($Ml['CurHealth']+$Diff,$Hlth );
        $Ml['Whose'] = $FactN;
        Put_Thing($Ml);
      }
    }
    
  } else {
    $MNames = [];

    foreach ($Mils as $Ml) $MNames[$Ml['Name']] = 1; // Didts & Dist2 give short cut to world and districts
    $M = ['Type'=>20, 'CurHealth'=>$Hlth, 'OrigHealth'=>$Hlth, 
           'Whose'=>$FactN, 'SystemId'=>$Sys, 'WithinSysLoc'=>$loc, 'BuildState'=>3, 
           'Dist1'=> $W['ThingType'], 'Dist2'=>$W['ThingId'] ];
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
  foreach ($Mils as $Ml) {
    if ($Ml['OrigHealth'] != $Hlth) {
      $Ml['OrigHealth'] = $Hlth;
      Put_Thing($Ml);
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

function Check_MyThing(&$T,$Fid=0) {
  if ($T['Whose'] == $Fid) return;
  if (Access('GM')) return;
  echo "<h2 class=Err>Not Your Thing...</h2>\n";
  dotail();
}

function Gates_Avail($Fid) {
  $Gates = [];
  $OwnGates = Get_Things_Cond($Fid,' Type=15 AND BuildState=3'); // Warp Gates
  if (!empty($OwnGates)) $Gates = array_merge($Gates,$OwnGates);
  
  $FFdata = Get_FactionFactionsCarry($Fid);
  foreach($FFdata as $FC) {
    if ($FC['Props'] & 0xf00) {
      $OGates = Get_Things_Cond($FC['FactionId1'],' Type=15 AND BuildState=3'); // Warp Gates
      if (!empty($OwnGates)) $Gates = array_merge($Gates,$OGates); 
    }
  }
  return $Gates;
}

function Do_Mine_Damage(&$T,&$Mine,&$N=0,$InTurn=0) { // Needs changes
// Do damage and report

  if (Get_ModulesType($T,23)) return;
  $Dsc = Has_Tech($Mine['Whose'],'Deep Space Construction');
  $Dam = $Dsc * $Mine['Level']*5;
  if (empty($N)) $N = Get_System($Mine['SystemId']);
  
  if ($T['CurHealth'] > $Dam) {
    $T['CurHealth'] -= $Dam;
  } else {
    $T['BuildState'] = 4;
  }
  Put_Thing($T);
  $N0 = 0;
  
  $Locations = Within_Sys_Locs($N);
  $LocText = $Locations[$Mine['WithinSysLoc']];
  
  if ($InTurn) {
    TurnLog($T['Whose'],"The " . $T['Name'] . " has recieved $Dam damage from a minefield in " . $N['Ref'] . " $LocText " . 
      ($T['BuildState'] > 3? " and has been destroyed." : ""),$T);
    GMLog("The <a href=ThingEdit.php?id=" . $T['id'] . ">" . $T['Name'] . "</a> took $Dam from a minefield in " . $N['Ref'] . " $LocText " . 
      ($T['BuildState'] > 3? " and has been destroyed." : ""));
    Report_Others($T['Whose'], $T['SystemId'],2,$T['Name'] . " has recieved damage from a minefield in " . $N['Ref'] . " $LocText " . 
      ($T['BuildState'] > 3? " and has been destroyed." : ""),$T);
      return "";
  } else {
    // Report Out Of Turn
    include_once("TurnTools.php");
    $msg = "The " . $T['Name'] . " has recieved $Dam damage from a minefield in " . $N['Ref'] . " $LocText " . ($T['BuildState'] > 3? " and has been destroyed." : "");
    TurnLog($T['Whose'],$msg,$T);
    GMLog4Later("The <a href=ThingEdit.php?id=" . $T['id'] . ">" . $T['Name'] . "</a> took $Dam from a minefield in " . $N['Ref'] . " $LocText " . 
      ($T['BuildState'] > 3? " and has been destroyed." : ""));
    Report_Others($T['Whose'], $T['SystemId'],2,$T['Name'] . " has recieved damage from a minefield in " . $N['Ref'] . " $LocText " . 
      ($T['BuildState'] > 3? " and has been destroyed." : ""),$T);
    return $msg;
  }
}

function Move_Thing_Within_Sys(&$T,$Dest,$InTurn) {
  if ($T['WithinSysLoc'] == $Dest) return;
  $WSL = $T['WithinSysLoc'];
// Is there a mine here?
  $Mines = Get_Things_Cond(0,"Type=10 AND SystemId=" . $T['SystemId'] . " AND BuildState=3");
  if (empty($Mines)) return;
  foreach($Mines as $i=>&$M) if ($M['WithinSysLoc']>500) unset($Mines[$i]); // 
  if (empty($Mines)) return;
  
// From current to Deep space
  if ($WSL > 2 && $WSL < 500) {
    switch (intdiv($WSL,100)) {
    case 1: //Orbiting
    case 3:
      break; 
    case 2: //On
    case 4:
      foreach($Mines as $M) {
        if ($M['WithinSysLoc'] == ($WSL-100)) {
          Do_Mine_Damage($T,$M,$N0,$InTurn);
          break 2;
        }
      }
      break;
          
    default:
    }
  }
// From Deep space to Dest
  if ($Dest > 2 && $Dest < 500) {
    switch (intdiv($Dest,100)) {
    case 1: //Orbiting
    case 3:
      foreach($Mines as $M) {
        if ($M['WithinSysLoc'] == $Dest) {
          Do_Mine_Damage($T,$M,$N0,$InTurn);
          break 2;
        }
      }
      break;

    case 2: //On
    case 4:
      foreach($Mines as $M) {
        if ($M['WithinSysLoc'] == ($Dest-100)) {
          Do_Mine_Damage($T,$M,$N0,$InTurn);
          break 2;
        }
      }
      break;
          
    default:
    }
  }
  
  $T['WithinSysLoc'] = $Dest;
}

function Thing_Delete($tid) {
      $T = Get_Thing($tid);
      $Discs = Get_DistrictsT($tid);
      if ($Discs) {
        foreach ($Discs as $D) {
          db_delete('Districts',$D['id']);
        }
      }
      $Mods = Get_DistrictsT($tid);
      if ($Mods) {
        foreach ($Mods as $M) {
          db_delete('Modules',$M['id']);
        }
      }

      db_delete('Things',$tid);
}

function Recalc_Prisoner_Counts() {
  $Facts = Get_Factions();
  $PCounts = [];
  foreach ($Facts as $F) $PCounts[$F['id']] = 0;
  $Prisoners = Get_Things_Cond(0,"PrisonerOf!=0");
  foreach ($Prisoners as $P) {
    $PCounts[$P['PrisonerOf']]++;
  }
  
  foreach ($Facts as $F) {
    if ($PCounts[$F['id']] > 0) {
      $F['HasPrisoners'] = $PCounts[$F['id']];
      Put_Faction($F);
    } else if ($F['HasPrisoners'] > 0) {
      $F['HasPrisoners'] = 0;
      Put_Faction($F);
    }
  }
  echo "Prisoners Reclaculated<p>";
}

?>
