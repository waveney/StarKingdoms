<?php
// Lib of Thing related stuff
include_once("sk.php");
include_once("GetPut.php");
include_once("vendor/erusev/parsedown/Parsedown.php");
include_once("PlayerLib.php");

global $ModFormulaes,$ModValues,$Fields,$Tech_Cats,$CivMil,$BuildState,$ThingInstrs,$InstrMsg,$Advance,$ValidMines,$ARMY,$LogistCost;

$Fields = ['Engineering','Physics','Xenology','Special','Unknown'];
$Tech_Cats = ['Core','Supp','Non Std','Levelled Non Std'];
$CivMil = ['','Civilian','Military'];
$BuildState = ['Planning','Building','Shakedown','Complete','Ex','Abandonded','Missing In Action','Captured'];
$ThingInstrs = ['None','Colonise','Voluntary Warp Home','Decommision','Analyse Anomaly','Establish Embassy','Make Outpost',
                'Make Asteroid Mine','Make Minefield',//8
                'Make Orbital Repair Yard','Build Space Station','Expand Space Station','Make Deep Space Sensor',
                'Make Advanced Asteroid Mine','Build Stargate',//14
                'Make Planet Mine', 'Construct Command Relay Station', 'Repair Command Node','Build Planetary Mine',
                'Dismantle Stargate','Disband','Transfer',//21
                'Make Something','Make Warpgate','Retire','Stop Support','Make Advanced Minefield','Clear Minefield',
                'Make Advanced Deep Space Sensor','Salvage',//29
                'Terraform','Link Repair','Collaborative DSC','Space Survey','Planetary Survey','Collaborative Planetary Construction',
                'Collaborative Space Construction',
];
$IntructProps = [0,2,0,0,0,0,1,1,1,
                 1,1,1,1,1,1,
                 1,1,1,1,1,0,0,
                 1,1,0,0,1,0,1,1,
                 0,1,0,0,0,0,1,2,0]; // 1 = DSC, 2= Pc
$InstrNotBy =   [0,0,1,0,0,1,0,
                 1,0,
                 0,0,0,0,
                 0,1,
                 1,1,1,1,
                 1,0,1,
                 0,1,0,1,1,1,
                 0,0,
                 0,1,0,2,2,2,
                 2,2];

$Advance = ['','','Advanced ','Very Advanced ','Ultra Advanced ','Evolved '];
$ValidMines = [0,1,0,1,0,1,0,0,0,0,0];
$LinkStates = ['','Under Repair','In Safe Mode','Locked'];
$LogistCost = [0,1,3,6,10,15,21,28,36,45,55,66,78,91];

$ModFormulaes = [];
$ModValues = [];

define('THING_HAS_DISTRICTS',1);
define('THING_HAS_MODULES',  2);
define('THING_HAS_LEVELS',   4);
define('THING_HAS_SHIPMODULES',8);
define('THING_HAS_GADGETS',    16);
define('THING_HAS_ARMYMODULES',32);
define('THING_HAS_MILSHIPMODS',64);
define('THING_HAS_CIVSHIPMODS',128);
define('THING_CAN_MOVE',           0x100);
define('THING_CAN_BETRANSPORTED',  0x200);
define('THING_HAS_2_FACTIONS',     0x400);
define('THING_HAS_MINERALS',       0x800);
define('THING_CAN_BE_ADVANCED',   0x1000);
define('THING_CAN_BE_CREATED',    0x2000);
define('THING_NEEDS_CARGOSPACE',  0x4000);
define('THING_CANT_HAVENAMED',    0x8000);
define('THING_CAN_DO_PROJECTS',  0x10000);
define('THING_MOVES_DIRECTLY',   0x20000);
define('THING_MOVES_AFTER',      0x40000);
define('THING_HAS_HEALTH',       0x80000);
define('THING_HAS_CONTROL',     0x100000);
define('THING_NEEDS_SUPPORT',   0x200000);
define('THING_IS_HOSTILE',      0x400000);
define('THING_CAN_BE_SPLATED',  0x800000);
define('THING_LEAVES_DEBRIS',  0x1000000);
define('THING_IS_SMALL',       0x2000000);
define('THING_HAS_BLUEPRINTS', 0x4000000);
define('THING_NEEDS_PLANET',   0x8000000);
define('THING_HAS_AGE',       0x10000000);
define('THING_ISA_TEAM',      0x20000000);
define('THING_HAS_VARIANTS',  0x40000000);

define('THING_SHOW_CONTENTS',     1); // Prop2
define('THING_HAS_RECOVERY',      2);
define('THING_ALWAYS_OTHER',      4);


define('LINK_ON_BOARD',-1);
define('LINK_BOARDING',-2);
define('LINK_UNLOAD',-3);
define('LINK_LOAD_AND_UNLOAD',-4);
define('LINK_VALUE_UNUSED',-5);
define('LINK_DIRECTMOVE',-6);
define('LINK_FOLLOW',-7);
define('LINK_INBRANCH',-8);

define('BUILD_FLAG1',1);
define('BUILD_FLAG2',2);
define('BUILD_FLAG3',4);

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
  if (!$mt['Formula']) return 0;
  $mf = Get_ModFormula($mt['Formula']);

  if ($mf['Name'] == 'None') return 0;
  if (Access('GM') && !isset($mf['Num2x'])) echo "Report Module formula error $modtypeid<p>";
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


function Show_Tech(&$T,&$CTNs,&$Fact=0,&$FactTechs=0,$Descs=1,$Setup=0,$lvl=0,$MaxLvl=10) {
  global $ModFormulaes,$ModValues,$Fields,$Tech_Cats,$CivMil;
  static $AllTechs;
  if (empty($AllTechs)) $AllTechs = Get_Techs(0);

  $Blue = (str_contains($T['Name'],'Blueprint'));
  if ($Blue && !isset($_REQUEST['Blue'])) return;

  $Tid = $T['id'];

  $Class = ($T['Cat'] == 0?"TechDesc":"SuppTech");
  echo "<div class=$Class><h2 onclick=Toggle('TDesc$Tid')>" . $T['Name'];
  echo "<div class=TechLevel>";

  if ($Fact && ($T['Cat']==0 || $T['Cat']==3) && isset($FactTechs[$T['id']]['Level']) ) echo " ( at Level " . $FactTechs[$T['id']]['Level'] . " )";
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
    if (($T['Cat'] == 0) || ($T['Cat'] == 3)) {
      echo fm_number0("Level",$FactTechs[$Tid],'Level',''," min=0 max=$MaxLvl","Tech$Tid");
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
  case 3: // Specials
    echo "<span class=orange>Special Leveled Tech outside the normal tech trees.</span><br>";
    break;
  }

  if (!$Descs) echo "<div id=TDesc$Tid hidden>";

  if (feature('ShowModuleSlots') && $T['Slots']) echo "Uses " . $T['Slots'] . " module " . Plural($T['Slots'],"","slot","slots") . "  ";
  if (feature('ShowCivMilInfo') && $T['CivMil']) echo "<b>" . $CivMil[$T['CivMil']] . " ships</b>";
  echo "<br>";

  if ($T['Description']) echo ParseText($T['Description']);

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

// 200+ = on planet, 400+ = on moon rest in space

function Within_Sys_Locs(&$N,$PM=0,$Boarding=0,$Restrict=0,$Hab=0) {// $PM +ve = Planet Number, -ve = Moon Number, restrict 1=ON, 2space only
  include_once("SystemLib.php");
  $L[0] = "";
  if ($Restrict !=1) $L[1] = 'Deep space';
//  $L[2] = 'On board ship';
  $L[3] = 'On the Ground somewhere';
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
  if (feature('LinksValidLocations') && $Restrict !=1) foreach($LKs as $lk) { // Code currently goes wrong if concealed links used
    if (1) $L[500+$Li] = "At " . Feature('LinkRefText','stargate to link') . " " . $lk['id'];
    $Li++;
  }

  if ($Boarding) {
    $Things = Get_ThingsAt($N['id']);
    $ThingTypes = Get_ThingTypes();

    foreach ($Things as $T) {
      if (($ThingTypes[$T['Type']]['Properties'] & THING_HAS_DISTRICTS) || $T['CargoSpace'] ) $L[1000+$T['id']] = "On " . $T['Name'];
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

function ModuleCats() {
  global $ARMY;
  return ['Ship','Civilian Ship','Support Ship','Military Ship',$ARMY,'Other','All'];
}

function Get_Valid_Modules(&$T,$Who=0) {
  global $ARMY;
  $ModuleCats = ModuleCats();
  $MTs = Get_ModuleTypes();
  if ($Who == 0) $Who = $T['Whose'];
  $VMT = [];
  $ThingProps = Thing_Type_Props();
  $tprop = (empty($ThingProps[$T['Type']] )?0: $ThingProps[$T['Type']] ) ;
//  var_dump($tprop,$Who);
  foreach ($MTs as $Mi=>$M) {
 //   var_dump($M['Name'],$M['CivMil']); // ,$ModuleCats[$M['CivMil']],(($tprop & THING_HAS_ARMYMODULES)));
    if ($M['MinShipLevel'] > $T['Level']) continue;
    if (($ModuleCats[$M['CivMil']] == $ARMY) && (($tprop & THING_HAS_ARMYMODULES) == 0)) continue; // Armies
    if (($M['CivMil'] < 4) && (($tprop & THING_HAS_SHIPMODULES) ==0 )) continue; // Ships
    if ($ModuleCats[$M['CivMil']] == 'Military Ship' && ($tprop & THING_HAS_MILSHIPMODS) ==0 ) continue;
    if ($ModuleCats[$M['CivMil']] == 'Civilian Ship' && ($tprop & THING_HAS_CIVSHIPMODS) ==0) continue;


    if ($Who) {
      $l = Has_Tech($Who,$M['BasedOn']);
      if (!$l) continue;
    }
    $VMT[$M['id']] = $M['Name'];
  }
  return $VMT;
}


function Max_Modules(&$T) {
  if (empty($T['Type'])) return 0;
  $ThingTypes = Get_ThingTypes();
  $TTs = ($ThingTypes[$T['Type']]??0);
  if (!empty($TTs['Properties']) && ($TTs['Properties'] & THING_HAS_MODULES)) {
    if (Feature('StarCluster')) {
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
    } else {
      $v = [0,6,16,32,56,88,128,176,232,296,368][$T['Level']];
      if ($TTs['Properties'] & THING_IS_SMALL ) $v -= $T['Level'];
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

  if ($Other == 0) $Other = $T['Whose']??0;
  if (Has_Trait($Other,'Thick Skinned')) $Plus =1;
  $Health = (Feature('BaseHealth')?5*($T['Level']+$Plus):0);
  $Shield = 0;
  $Ms = Get_Modules($T['id']);
  $Mts = Get_ModuleTypes();
  $Techs = Get_Techs($Other);
  $Rescat = 0;
  foreach ($Mts as $mt) if (($mt['DefWep'] == 1 ) || ($mt['DefWep'] == 3 )) {
    foreach ($Ms as $M) if (($Mts[$M['Type']]['Name'] == $mt['Name']) && ($M['Number'] > 0)) {
      if ($KeepTechLvl) {
          $Mhlth = $M['Number'] * Mod_ValueSimple($M['Level']+$Plus,$M['Type'],$Rescat);
          if ($mt['DefWep'] == 1 ) {
            $Health += $Mhlth;
          } else {
            $Shield += $Mhlth;
            $Health +=  $M['Number'] * 15;
          }
      } else {
        $based = $Mts[$M['Type']]['BasedOn'];
        if ($based && ($l = Has_Tech($Other,$based))) {
          if ((($Techs[$based]['Cat'])??0) >= 1) {
            $l = Has_Tech($Other,$Techs[$based]['PreReqTech']);
          }
          $Mhlth = $M['Number'] * Mod_ValueSimple($l+$Plus,$M['Type'],$Rescat);
          if ($mt['DefWep'] == 1 ) {
            $Health += $Mhlth;
          } else {
            $Shield += $Mhlth;
            $Health +=  $M['Number'] * 15;
          }
        }
      }
    }
  }

  return [$Health,$Shield];
}

function Calc_Damage(&$T,&$Rescat) {

  if ($T['ActDamage']??0) return $T['ActDamage'];
  if ($T['Type'] ==20) return 8; // Militia
  $Dam = 0;
  $Ms = Get_Modules($T['id']);
  $Mts = Get_ModuleTypes();
  $ToHit = 0;

  $Rescat = 0;
  foreach ($Mts as $mti=>$mt) {
    if (!isset($Ms[$mti])) continue;
    $M = $Ms[$mti];
    if ($mt['DefWep'] == 2 ) {
      $dam = ($M['Level'] > 0?$M['Number'] * Mod_ValueSimple($M['Level'],$mti,$Rescat):0);
      $Dam += $dam;
    }
    $ToHit += $M['Number']*$mt['ToHitMod'];
  }

  if ($T['Variant']??0) {
    $V = Gen_Get('Variants',$T['Variant']);
    $vev = $V['Firepower'];
    if ($vev) {
      if (abs($vev)<5) {
        $Dam = $Dam + ceil($Dam * $vev/4);
      } else {
        $Dam += $vev;
      }
    }
  }

  return [$Dam,$ToHit];
}

function Calc_TechLevel($Fid,$MType) {
  $Mts = Get_ModuleTypes();
  $Techs = Get_Techs();
  $based = $Mts[$MType]['BasedOn'];
  if ($based == 0) return 0;
  if ($l = Has_Tech($Fid,$based)) {
    if ($Techs[$based]['Cat'] == 1) {
      $l = Has_Tech($Fid,$Techs[$based]['PreReqTech']);
    }
  }
  return $l;
}

function Thing_Finished($Tid) {
  $T = Get_Thing($Tid);
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
  if ($T['LinkId'] <0) return [[],[],[]];
//var_dump($T);exit;
  $Fid = $T['Whose'];
  if (!$N) $N = Get_System($T['SystemId']);
  $LinkTypes = Get_LinkLevels();

  $Links = (empty($N['Ref']) ? [] : Get_Links($N['Ref']));
  $SelLinks = [''];
  $SelCols = [''];
  if ($GM || Has_Tech($T['Whose'],'Know All Links')) {
    foreach ($Links as $Lid=>$L) {
      if (($L['Instability'] + $L['ThisTurnMod']) > $T['Stability']) {
        unset($Links[$Lid]);
        continue;
      }
      $Lnam = ($L['Name']?$L['Name']:"#" .$L['id']);
      $SelLinks[$L['id']] = "$Lnam to " . (($L['System1Ref'] == $N['Ref'])?$L['System2Ref']: $L['System1Ref'] );
      $SelCols[$L['id']] = $LinkTypes[abs($L['Level'])]['Colour'];
    }
  } else {
    $NearNeb = ($N['Nebulae']??0);
    $NS = (($Fid && $N['id'])?Get_FactionSystemFS($Fid,$N['id']):[]);

    foreach ($Links as $Lid=>$L) {
      if (($L['Instability'] + $L['ThisTurnMod']) > $T['Stability']) {
        unset($Links[$Lid]);
        continue;
      }
      $LinkText = "Unknown";
      $FLK = Gen_Get_Cond1('FactionLinkKnown',"FactionId=$Fid AND LinkId=$Lid");

      if (($L['Concealment'] == 0) || ($FLK['Used']??0) || ($NS['SpaceScan'] >= $L['Concealment'])) {

      } else {
        unset($Links[$Lid]);
        continue;
      }

/*
      $FL = Get_FactionLinkFL($Fid,$L['id']);
      if (($L['Concealment'] > 0) && ($L['Concealment'] > $NS['SpaceScan']) && (($FL['Known']??0) ==0)) {
        unset($Links[$Lid]);
        continue;
      }*/
      $FarSysRef =  (($L['System1Ref'] == $N['Ref'])?$L['System2Ref']: $L['System1Ref'] );
      $FSN = Get_SystemR($FarSysRef);
      $FarNeb = $FSN['Nebulae'];
      $FS = Get_FactionSystemFS($Fid,$FSN['id']);
 //echo "<p>doing link " . $L['id'] . " to $FarSysRef ". $FSN['id'] ; var_dump($FS);
      if ($FLK['Used']??0) {
        $LinkText = $FarSysRef;
      } else if ($NearNeb == 0) {
        if (isset($FS['id'])) {
          if ($FarNeb == 0) {
            $LinkText = $FarSysRef;
          } else if ($FS['ScanLevel'] >= 0) {
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
      } else if (($NS['ScanLevel'] >= $NearNeb ) && $T['NebSensors']) { // In a Neb...
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
      $Lnam = ($L['Name']?$L['Name']:"#" .$L['id']);

      $SelLinks[$L['id']] = "$Lnam to " . $LinkText . ($L['Status'] != 0) . " - " . $LinkStates[$L['Status']];
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
  if (empty($T['NebSensors']) || empty($T['SensorLevel']) || empty($T['Sensors'])) return 0;

  if ( Feature('OldNebScans')) return floor($T['NebSensors'] * $T['SensorLevel'] / 2);
  return $T['SensorLevel'];
}

function Calc_Scanners(&$T) { // And lots of other attributes
  $mods = Get_ModulesType($T['id'],'Sensors');
  $psurv = Get_ModulesType($T['id'],'Planetary Survey Corps');
  $nebs = Get_ModulesType($T['id'],'Nebula Sensors');
  $Cargo = Get_ModulesType($T['id'],'Cargo Space');
  $Cryo = Get_ModulesType($T['id'],'Cryo Pods');
  $T['Sensors'] = (($mods && ($mods['Number']>0))?$mods['Number']:0)  + (($psurv && ($psurv['Number']>0))?$psurv['Number']:0) ;
  $T['SensorLevel'] = max((($mods && ($mods['Level']>0))?$mods['Level']:0),(($psurv && ($psurv['Level']>0))?$psurv['Level']:0));
  $T['NebSensors'] = ( (($nebs && ($nebs['Number']>0))?$nebs['Number']:(($T['Type'] == 9 && $T['Level'] > 1)?($T['Level']-1):0)));
  $T['CargoSpace'] = ($Cargo?1:0) + ($Cryo?2:0);
  /*  $T['CargoSpace'] = ( (($Cargo && ($Cargo['Number']>0))?$Cargo['Number']*($Cargo['Level']+1):0) +
                       (($Cryo  && ($Cryo['Number']>0))?$Cryo['Number']*($Cryo['Level']+3):0)); */
  if (($Deep = Get_ModulesType($T['id'],'Deep Space Construction' )) && ($Deep['Number']>0)) {
    $T['HasDeepSpace'] = $Deep['Number']*$Deep['Level'];
  } else {
    $T['HasDeepSpace'] = 0;
  }
  $LvlMod = 0;
  if (Has_Trait($T['Whose']??0,'Ebonsteel Manufacturing')) $LvlMod = 1;
  $Engs = Get_ModulesType($T['id'],'Sublight Engines');
  $T['Speed'] = ceil(($Engs && $Engs['Number']>0)?$Engs['Number']*($Engs['Level']+$LvlMod)/$T['Level']:0);
  $Mobs = Get_ModulesType($T['id'],'Suborbital Transports');
  $T['Mobility'] = ceil(($Mobs && $Mobs['Number']>0)?$Mobs['Number']*($Mobs['Level']+$LvlMod)/$T['Level']:0);
  $Flux = Get_ModulesType($T['id'],'Flux Stabilisers');
  $T['Stability'] = ceil(max(1,(($Flux && $Flux['Number']>0)?$Flux['Number']*($Flux['Level']+$LvlMod)/$T['Level']:0)));
}

function Calc_Evasion(&$T) {
  $MTypes = Get_ModuleTypes();
  $TTypes = Get_ThingTypes();
  if (!isset($TTypes[$T['Type']])) return 0;
  $MMs = Get_Modules($T['id']);
  $ev = max(0,(5-$T['Level'])*10);
  $ev += $TTypes[$T['Type']]['EvasionMod'];
  foreach ($MMs as $M) {
    $ev += $MTypes[$M['Type']]['EvasionMod']*$M['Number'];
  }

  if ($T['Variant']) {
    $V = Gen_Get('Variants',$T['Variant']);
    $vev = $V['Evasion'];
    if ($vev) {
      if (abs($vev)<5) {
        $ev = $ev + ceil($ev * $vev/4);
      } else {
        $ev += $vev;
      }
    }
    $T['TargetEvasion'] = $V['TargetEvasion']??0; // Actual Number calculated when in battle
  }
  $T['Evasion'] = $ev;
}

function RefitRepair(&$T,$Save=1,$KeepTechLvl=0,$Other=0) {
  if (empty($T['Level']) ) return 0;
// Refit
// var_dump($T);
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
  [$Health,$Sld] = Calc_Health($T,$KeepTechLvl,$Other);
//var_dump($Health);
  Calc_Scanners($T);
  Calc_Evasion($T);
  $R = 0;
  [$DM,$ToHit] = Calc_Damage($T,$R);
  $T['ToHitBonus'] = $ToHit;
//  var_dump($Health,$Sld);
  $T['OrigHealth'] = $Health;
  $T['ShieldPoints'] = $Sld;
  if ($KeepTechLvl == 0) {
    $T['CurHealth'] = $Health;
    $T['CurShield'] = $Sld;
  } else {
    $T['CurHealth'] = min($T['CurHealth'],$Health);
    $T['CurShield'] = $Sld;
  }
  Put_Thing($T);
  return $Etxt;
}

//  &#8373; = Credit symbol

function LogisticalSupport($Fid) {  // Note this sets the Economic rating of all PHs
  $PHomes = Get_ProjectHomes($Fid);

  $Logistics = [0,0,0];

  $ThingTypes = Get_ThingTypes();
  $DistTypes = Get_DistrictTypes();
  $Dists = [];

  foreach($PHomes as $H) {
//    $PH['Economy'] = 0;
    $Commerce = 0;
    $Hi = $H['id'];
    switch ($H['ThingType']) {
    case 1: // Planet
      $PH = Get_Planet($H['ThingId']);
      $Dists = Get_DistrictsP($H['ThingId']);
      $EndAct = 'Put_Planet';
//      echo "At right spot $Hi<p>";
      if (Has_PTraitH($Hi,'CEOs Stomping Ground')) $Logistics[1] +=22;
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
    $LogisticMult = Feature('LogisticMultiplier',5);
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
        $Logistics[1]+= $D['Number']*$LogisticMult;
        break;
      case 'Shipyard' :
        $Logistics[0]+= $D['Number']*$LogisticMult;
        break;
      case 'Intelligence' :
        $Logistics[2]+= $D['Number']*$LogisticMult;
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

  $MilTheory = Has_Tech($Fid,'Military Theory');
  if (Has_Tech($Fid,'Naval Logistics')) $Logistics[0] += $LogisticMult*$MilTheory;
  if (Has_Tech($Fid,'Army Logistics')) $Logistics[1]  += $LogisticMult*$MilTheory;
  if (Has_Tech($Fid,'Intelligence Logistics')) $Logistics[2] += $LogisticMult*$MilTheory;
  return $Logistics;
}

function Thing_Duplicate($otid) {
  global $FACTION;
  $OrigT = $T = Get_Thing($otid);
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
  $T['LastMoved'] = -1;
  if ($T['GM_Notes']) {
    $T['GM_Notes'] = "This thing has been copied from " . $T['Name'] . "- id: $otid with this note:\n" . $T['GM_Notes'];
  }
  if ($T['BluePrint'] < 0) {
    $T['BluePrint'] = $otid;
    $T['Whose'] = ($FACTION['id'] ?? 0);
    $T['Class'] = $OrigT['Name'];
  }

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

function EyesInSystem($Fid,$Sid,$Of=0) { // Eyes 1 = in space, 2= sens, 4= neb sens, 8=ground
//var_dump($Fid,$Sid);
  $Neb = $Eyes = 0;
  $ThingTypes = Get_ThingTypes();
  if ($Of) {
    $MyThings = [ Get_Thing($Of) ];
  } else {
    $MyThings = Get_Things_Cond($Fid," SystemId=$Sid AND (BuildState=2 OR BuildState=3)");
  }
  $N = Get_System($Sid);
  if ($N) $Neb = $N['Nebulae'];

//var_dump($MyThings);
  foreach ($MyThings as $T) {
    if ($T['SeenTypeMask']) continue;
    if ($ThingTypes[$T['Type']] == 'Team') {
      $eye = ($T['WithinSysLoc']==1)?1:8;
    } else {
      $eye = $ThingTypes[$T['Type']]['Eyes'];
    }
    if (($Neb > 0) && ($T['NebSensors'] < $Neb) && (($eye&4 ==0))) continue;
    if ($T['PrisonerOf']) continue;
    $Eyes |= $eye;
    if ($T['Sensors']) $Eyes |= 2;
    if ($T['NebSensors']) $Eyes != 4;
  }
// echo "System " . $N['Ref'] . " eyes: $Eyes<br>";
  return $Eyes;
}

function is_vowel(&$Text) {
  return strpos(" aeiou",strtolower(substr($Text,0,1)));
}

function SeeThing(&$T,&$LastWhose,$Eyes,$Fid,$Images=0,$GM=0,$Div=1) {
  global $Advance,$FACTION;
  static $ThingTypes;
  static $Factions;
  static $OpProps;
  if (!$ThingTypes) $ThingTypes = Get_ThingTypes();
  if (!$Factions) $Factions = Get_Factions();
  $Locations = Within_Sys_Locs_Id($T['SystemId']);

  $txt = $itxt = '';
  $RawA = 0;

//  if ($T['id'] == 238) { echo "Here with outpost<br>"; var_dump($T); }
  $TTprops = ($ThingTypes[$T['Type']]['Properties']??0);
  $TTprops2 = ($ThingTypes[$T['Type']]['Prop2']??0);

  if ($T['CurHealth'] == 0 && ($TTprops & THING_CAN_BE_SPLATED)) return '';
  if ($TTprops & THING_ISA_TEAM) {
    if ($T['Whose'] != $Fid) {
      include_once('OrgLib.php');
      $Op = Get_Operation($T['ProjectId']);
      if (!$Op) return '';
      if (!$OpProps) $OpProps = Get_OpTypes();
      if (($OpProps[$Op['Type']]['TeamProps'] & TEAM_HIDDEN) && !$GM) return '';

      if (($OpProps[$Op['Type']]['TeamProps'] & TEAM_INSPACE) && (($Eyes & 1) == 0)) return '';
      if (($OpProps[$Op['Type']]['TeamProps'] & TEAM_ONGROUND) && (($Eyes & 8) == 0)) return '';
    }
  }

  $stm = (int) (~ (int)$T['SeenTypeMask']);
//echo "Failed to see:" . $ThingTypes[$T['Type']]['SeenBy'] . ":$stm:" . $T['SeenTypeMask'] . ":$Eyes<p>";
//var_dump($stm);
      if ($Fid >=0 && ($T['Whose'] != $Fid) && ($T['PrisonerOf'] != $Fid) &&
          (((($ThingTypes[$T['Type']]['SeenBy']??0) & $stm & $Eyes) == 0 ))) {
// echo "Failed to see:" . $ThingTypes[$T['Type']]['SeenBy'] . ":$stm:$Eyes<p>";
        return '';
      }
      if ($T['BuildState'] < 2 || $T['BuildState'] > 4) return ''; // Building or abandoned
      if ($Div && $LastWhose && $LastWhose!= $T['Whose']) $txt .= "<P>";
      $Imgxtra = 0;
      if ($Div) {
        if (($T['BuildState'] == 4) || (($T['Type'] == 23) && $GM)) { // Named Chars
          if ($GM) {
            $txt .= "<div class='FullD SeeThingTxt' hidden>";
            $Imgxtra = 1;
          } elseif ($T['BuildState'] >= 4) {
            $txt .= "<div class='FullD SeeThingTxt' hidden>The remains of: ";
            $Imgxtra = 1;
          } else {
            $txt .= "<div class=SeeThingTxt>";
          }
        } else {
          $txt .= "<div class=SeeThingTxt>";
        }
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
        if ($T['HideOwner'] && !$GM) {
          $txt .= "Unidentified";
        } else {
          $Who = ($Factions[$T['Whose']]['Adjective']?$Factions[$T['Whose']]['Adjective']:$Factions[$T['Whose']]['Name']);
          if ($RawA && is_vowel($Who)) $txt .= "n";
          if ($Div) {
            $txt .= " <span style='background:" . $Factions[$T['Whose']]['MapColour'] . "'>$Who</span>";
          } else {
            $txt .= " $Who";
          }
          if ($T['HideOwner']) $txt .= " (Hidden)";
          $RawA = 0;
        }
      }
      if (($T['Whose'] == $Fid) && ($TTprops & THING_CAN_BE_ADVANCED) && ($T['Level'] > 1)) $txt .= ' ' . $Advance[$T['Level']];
      if ($T['Whose']==0 && Access('GM')) {
        $txt .= "<a href=ThingEdit.php?id=" . $T['id'] . ">" . ($ThingTypes[$T['Type']]['Name']??'Unknown') . "</a>";
      } else {
        $txt .= " " . $ThingTypes[$T['Type']]['Name'];
      }

//var_dump($Locations);exit;
      if ($Div && $T['LinkId'] >= 0 && $T['SystemId'] > 0 && isset($T['WithinSysLoc']) && ($T['WithinSysLoc'] > 0)) {
        if (isset($Locations[$T['WithinSysLoc']])) {
          $txt .= " ( " . $Locations[$T['WithinSysLoc']] . " ) ";
        } else if ($GM) {
          $txt .= " ( Unknown Location " . $T['WithinSysLoc'] . " ) ";
        }
      }

      if ($T['PrisonerOf']) {
        if ($GM || (isset($FACTION['id']) && $T['PrisonerOf'] == $FACTION['id'])) {
          $Fact = Get_Faction($T['PrisonerOf']);
          $txt .= ", Prisoner Of: <span style='background:" . $Fact['MapColour'] . "'>" . $Fact['Name'] . "</span>";
        }
      } else if ($GM && !empty($T['Orders'])) $txt .= ", <span style='background:#ffd966;'>Orders: " . $T['Orders'] . "</span>";
      if ($Images && !empty($T['Image'])) {
        $itxt .= " <img valign=top src=" . $T['Image'] . " height=100 class=SeeImage> ";
      }

      if ($GM) {
        $Resc =0;
        [$BD,$ToHit] = Calc_Damage($T,$Resc);

        $txt .= " (";
        if ($TTprops & THING_HAS_HEALTH) {
          if ($TTprops & THING_CAN_MOVE) $txt .= "Speed: " . sprintf("%0.3g, ",ceil($T['Speed'])) ;
          $txt .= "Health: " . $T['CurHealth'] . "/" . $T['OrigHealth'] . ", ";
        }
        if ($BD) $txt .= "Dam: " . $BD . ($Resc? "<b>*</b>":'') . ", ";
        if ($ToHit) $txt .= "ToHit: $ToHit, ";
        if ($T['NebSensors']) $txt .= "N, ";
        $txt .= ")";
      } else {
        if (($TTprops & THING_HAS_HEALTH) && ($T['BuildState'] < 4)) {
          if ($T['CurHealth']*10 <= $T['OrigHealth']) {
            $txt .= " - Badly Damaged ";
          } else if ($T['CurHealth']*2 <= $T['OrigHealth']) {
            $txt .= " - Damaged ";
          }
        }
      }
      if ( $GM && ($TTprops & THING_ISA_TEAM)) {
        if ($T['Description']) {
          $txt .= "<br>" . ParseText($T['Description']);
          $Images = 1; // To add the br at end
        }
      }
      if ($TTprops2 & THING_SHOW_CONTENTS) {
        switch ($ThingTypes[$T['Type']]['Name']) {
          case 'Outpost':
            include_once('OrgLib.php');
            $Brans = Gen_Get_Cond('Branches',"HostType=3 AND Hostid=" . $T['id']);
            if ($Brans) {
              $BList = [];
              foreach ($Brans as $B) {
                $BT = Gen_Get('BranchTypes',$B['Type']);
                if ($B['Whose'] != ($FACTION['id']??0) && ($BT['Props'] & BRANCH_HIDDEN)) continue;
                $Org = Gen_Get('Organisations',$B['Organisation']);
                $OrgType = Gen_Get('OfficeTypes', $B['OrgType']);
                $BList[] = $Org['Name'] . " (" . $OrgType['Name'] .")";
              }
              if ($BList) $txt .= "<br>Branches: " . implode(',',$BList);
            } else {
              $txt .= "<br>No Branches found";
            }
          default:
        }
      }
      if ($Images) $txt .= "<br clear=all>\n";

      if ($Div) $txt .= "</div>";

      if ($itxt) {
        if ($Imgxtra) {
          $txt = "<div class=SeeThingwrap>$txt<div class='FullD SeeThingImg' hidden>$itxt</div></div>";
        } else {
          $txt = "<div class=SeeThingwrap>$txt<div class=SeeThingImg>$itxt</div></div>";
        }
      }
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
       $Col = Feature('DefaultSeeCol','White');
       if ($N['Control']) {
         $Fac = Get_Faction($N['Control']);
         $Col = $Fac['MapColour'];
       }
       $txt .= "<p><h2 style='background:$Col;'><a href=SurveyReport.php?id=$Sid>System " . System_Name($N,$Fid) . "</a></h2>";
    } else {
       $txt .= "<h2>In the System is:</h2>";
    }
    $LastWhose = 0;
    foreach ($Things as $T) {
      $txt .= SeeThing($T,$LastWhose,$Eyes,$Fid,$Images,$GM); //,$ThingTypes,$Factions);
    }
  return $txt;
}

function Update_Militia(&$W,&$Dists,$NewOwn=0,$Deploy=0) {
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
  $Hlth = feature('MilitiaHealth',25)+Has_Tech($FactN,'Militia Training Techniques')*2;

  $Dcount = 0;
  $DTs = Get_DistrictTypes();
  foreach($Dists as $D) {
    if (($DTs[$D['Type']]['Props'] &128) == 0) $Dcount += ($D['Number'] * ($D['Type'] ==2?2:1));
  }
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
      }
      $Ml['LinkId'] = ($Deploy?0:LINK_INBRANCH);
      Put_Thing($Ml);
    }

  } else {
    $MNames = [];

    foreach ($Mils as $Ml) $MNames[$Ml['Name']] = 1; // Didts & Dist2 give short cut to world and districts
    $M = ['Type'=>20, 'CurHealth'=>$Hlth, 'OrigHealth'=>$Hlth, 'Evasion'=>40,
           'Whose'=>$FactN, 'SystemId'=>$Sys, 'WithinSysLoc'=>$loc, 'BuildState'=>3,
           'Dist1'=> $W['ThingType'], 'Dist2'=>$W['ThingId'], 'LinkId' => ($Deploy?0:LINK_INBRANCH)  ];
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
      $Ml['LinkId'] = ($Deploy?0:LINK_INBRANCH);
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
  if (($T['Whose']??-1) == $Fid) return;
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
      if (!empty($OGates)) $Gates = array_merge($Gates,$OGates);
    }
  }
  return $Gates;
}

function Do_Mine_Damage(&$T,&$Mine,&$N=0,$InTurn=0) { // Needs changes
// Do damage and report

  if (Get_ModulesType($T['id'],'Minesweepers')) return;
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
  $N0 = 0;

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

function Empty_Thing(&$T) {
  $Tid = $T['id'];
  $Have = Get_Things_Cond(0," (LinkId=-1 OR LinkId=-3) AND SystemId=$Tid ");

  if ($Have) {
    foreach($Have as $CT) {
      $CTi = $CT['id'];
      if ($CT['Type'] != 23) {
        $InnerHave =  Get_Things_Cond(0," (LinkId=-1 OR LinkId=-3) AND SystemId=$CTi ");
        if ($InnerHave) {
          foreach($InnerHave as $InId=>$Inner) {
            FollowUp($Inner['Whose'], "<a href=ThingEdit.php?id=$InId>" . $Inner['Name'] . "</a> was on board " . $T['Name'] . " when it was destoyed.");
            $Inner['LinkId'] = 0;
            $Inner['SystemId'] = GameFeature('Limbo',0);
            Put_Thing($Inner);
          }
        }
        db_delete('Things',$CTi);
      } else {
        FollowUp($CT['Whose'], "<a href=ThingEdit.php?id=$CTi>" . $CT['Name'] . "</a> was on board " . $T['Name'] . " when it was destoyed.");
        $CT['LinkId'] = 0;
        $CT['SystemId'] = GameFeature('Limbo',0);
        Put_Thing($CT);
      }
    }
  }
}

function Thing_Delete($tid) {
  global $Project_Status,$Project_Statuses,$GAME;
  $TTs = Get_ThingTypes();
  $TTNames = NamesList($TTs);
  $NameTT = array_flip($TTNames);

  $T = Get_Thing($tid);

  $TT = $TTs[$T['Type']];
  switch ($TTNames[$T['Type']]) {
    case 'Team':
      $Oper = Get_Operation($T['ProjectId']);
      if ($Oper['Status'] ==1) {//is lost
        include_once("TurnTools.php");
        $Fact = Get_Faction($T['Whose']);
        $Oper['Status'] = 6;
        $Org = Gen_Get("Organisation" , $Oper['OrgId']);
        TurnLog($T['Whose'], "The Team doing Operation " . $Oper['Name'] . " for " . $Org['Name'] . " has been destroyed, the operation has been lost.");
        GMLog("The Team doing Operation " . $Oper['Name'] . " for " . $Org['Name'] . " has been destroyed, the operation has been lost.");
      }
      break;

    case 'Outpost':
      $Brans = Gen_Get_Cond('Branches',"HostType=3 AND HostId=$tid");
      if ($Brans) foreach ($Brans as $Bid=>$B) {
        db_delete('Branches',$Bid);
      }

      $Control = System_Owner($T['SystemId']);
      if ($Control) {
        TurnLog($T['Whose'], $Control);
        GMLog($Control);
      }
      break;

    default:
      break;
  }

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
  Empty_Thing($T);
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

function BluePrintList($Lvl=10000,$Props='',$WithISA=1) {
  global $GAMEID,$db,$FACTION;
  $BPlst = [];
  if ($Props) {
    $res = $db->query("SELECT T.* FROM Things T, ThingTypes Y WHERE T.GameId=$GAMEID AND Level<$Lvl AND T.Type=Y.id AND (Y.Props&$Props)!=0");
    if ($res) {
      while ($BP = $res->fetch_assoc()) {
        if (($BP['GatedOn']??0) && !eval("return " . $BP['GatedOn'] . ";" )) continue;
        $tt = $BP['Type'];
        if ($WithISA && empty($BPlst[$tt])) $BPlst[$tt][-1] = 'Is a Blueprint';
        $BPlst[$tt][$BP['id']] = $BP['Name'] . " (L" . $BP['Level'] . ")";
      };
    }
  } else {
    $BPs = Gen_Get_Cond('Things',"GameId=$GAMEID AND Level<=$Lvl AND BluePrint<0");
    $Fid = ($FACTION['id']??0);
    foreach($BPs as $i=>$BP) {
      if (($BP['GatedOn']??0) && !eval("return " . $BP['GatedOn'] . ";" )) continue;
      $tt = $BP['Type'];
      if ($WithISA && empty($BPlst[$tt])) $BPlst[$tt][-1] = 'Is a Blueprint';
      $BPlst[$tt][$BP['id']] = $BP['Name']  . " (L" . $BP['Level'] . ")";
    };
  }
  return $BPlst;
}

function BlueShow(&$T,$GM=0) {
  if ($T['BluePrint'] > 0) {
    echo "BluePrint: ";
    if ($GM) {
      echo fm_number0('',$T,'BluePrint','','min=-1 max=100000');
    }
    echo "<a href=ThingEdit.php?id=" . $T['BluePrint'] . ">View</a>";
  } else if ($T['BluePrint'] < 0) {
    echo "Is a Blue Print";
  } else  {
    echo "No Blue Print";
  }
}

function LinkVis($Fid,$Lid,$Sid) {
  static $KnowAll,$KnowFid,$LastSid,$Sys,$FS;
  if ($KnowFid != $Fid) {
    $KnowFid = $Fid;
    $KnowAll = Has_Tech($Fid,'Know All Links');
    $LastFS = [];
  }
  if (($Sid != $LastSid) || empty($Sys)) {
    $LastSid = $Sid;
    $Sys = Get_System($Sid);
    $FS = [];
  }
  if (empty($FS)) {
    $FS = Get_FactionSystemFS($Fid,$Sid);
  }
  $FLK = Gen_Get_Cond1('FactionLinkKnown',"FactionId=$Fid AND LinkId=$Lid");
  $L = Get_Link($Lid);

  return ($KnowAll || (($L['Concealment'] == 0) && ($Sys['Nebulae']==0)) || ($FLK['Used']??0) || $L['Concealment']<=$FS['SpaceScan']);
}


?>
