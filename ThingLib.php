<?php
// Lib of Thing related stuff
include_once("sk.php");
include_once("GetPut.php");
include_once("vendor/erusev/parsedown/Parsedown.php");
include_once("PlayerLib.php");  

global $ModuleCats,$ModFormulaes,$ModValues,$Fields,$ShipTypes,$Tech_Cats,$CivMil,$BuildState;

$ModuleCats = ['Ship','Civilian Ship','Support Ship','Military Ship','Army','Other'];
$Fields = ['Engineering','Physics','Xenology'];
$ShipTypes = ['','Military','Support','Civilian'];
$Tech_Cats = ['Core','Supp','Non Std'];
$CivMil = ['','Civilian','Military'];
$BuildState = ['Planning','Building','Shakedown','Complete','Ex'];

$ModFormulaes = [];
$ModValues = [];

define('THING_HAS_DISTRICTS',1);
define('THING_HAS_MODULES',2);
define('THING_HAS_SUBTYPES',4);
define('THING_HAS_SHIPMODULES',8);
define('THING_HAS_GADGETS',16);
define('THING_HAS_ARMYMODULES',32);
define('THING_HAS_MILSHIPMODS',64);
define('THING_HAS_CIVSHIPMODS',128);
define('THING_CAN_MOVE',256);

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
  global $ModuleCats,$ModFormulaes,$ModValues,$Fields,$ShipTypes,$Tech_Cats,$CivMil;
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
    }
  }
 
  switch ($T['Cat']) {
  case 0:
    echo "Core Technology<br>";
    break;
  case 2:
    echo " <span class=blue>Non Standard supplimentary Tech</span><br>";
  case 1:
    echo " Requires " . $CTNs[$T['PreReqTech']] . " at level " . $T['PreReqLevel'] . "<br>";
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

function Within_Sys_Locs(&$N) {
  include_once("SystemLib.php");
  $L[0] = "";
  $L[1] = 'Deep space';
//  $L[2] = 'On board ship';
if (!isset($N['id'])) { var_dump($N); exit; }
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

function Get_Valid_Modules(&$t) {
  global $ModuleCats;
  $MTs = Get_ModuleTypes();
  $VMT = [];
  $ThingProps = Thing_Type_Props();
  foreach ($MTs as $M) {
    if ($ModuleCats[$M['CivMil']] == 'Army') {
      if (($ThingProps[$t['Type']] & THING_HAS_ARMYMODULES) == 0) continue;
    } else if ($M['CivMil'] < 4) {
       if ($ThingProps[$t['Type']] & THING_HAS_SHIPMODULES) { 
        if (isset($t['SubType']) && $t['SubType']) {
          if ($ModuleCats[$M['CivMil']] == 'Military Ship' && $t['SubType'] == 3) continue;
          if ($ModuleCats[$M['CivMil']] == 'Civilian Ship' && $t['SubType'] == 1) continue;
        }
      } else {
        continue;
      }
    } else {
      continue;
    }
    
    $l = Has_Tech($t['Whose'],$M['BasedOn']);
    if (!$l) continue;
    if ($M['MinShipLevel'] > $t['Level']) continue;
    $VMT[$M['id']] = $M['Name'];
  }
  
  return $VMT;
}


function Max_Modules(&$t) {
  $ThingProps = Thing_Type_Props();
  if ($ThingProps[$t['Type']] & THING_HAS_MODULES) {
    $v = [0,4,12,24,40,60,84,112,144,180,220][$t['Level']];
    if ($ThingProps[$t['Type']] & THING_HAS_SUBTYPES) {
      if ($t['SubType'] == 2) $v = $v*3/4;
      }
    if (Has_Tech($t['Whose'], 'Compact Ship Design') && $t['Level'] > 1) $v += $t['Level'];
    if ($t['Level'] > 2 && Has_Trait('Really Big',$t['Whose'])) $v += $t['Level']*$t['Level'];
    return $v;
  }
  return 0;
}

function Calc_Health(&$t) {
  $Health = 5*$t['Level'];
  $Ms = Get_Modules($t['id']);
  $Mts = Get_ModuleTypes();
  $Techs = Get_Techs();
  foreach ($Mts as $mt) if ($mt['DefWep'] == 1 ) {
    foreach ($Ms as $M) if ($Mts[$M['Type']]['Name'] == $mt['Name']) {
      $based = $Mts[$M['Type']]['BasedOn'];
      if ($l = Has_Tech($t['Whose'],$based)) {
        if ($Techs[$based]['Cat'] == 1) {
          $l = Has_Tech($t['Whose'],$Techs[$based]['PreReqTech']);
        }
        $Mhlth = $M['Number'] * Mod_ValueSimple($l,$M['Type']);
        $Health += $Mhlth;
      }
    }
  }
    
  return $Health;
}

function Calc_Damage(&$t) { 

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
  $t['Buildstate'] = 3;
  $t['CurHealth'] = $t['OrigHealth'];
  Put_Thing($t);
}

function Show_Thing(&$t,$Force=0) {
  global $ShipTypes,$BuildState,$GAME,$GAMEID;
  global $Project_Status;
  $tid = $t['id'];
  
  if ($Force) {
    $GM = 0;
    $Fid = $t['Whose'];
  } else {
    $GM = Access('GM');
  }
  
  $ttn = Thing_Type_Names();
  $FactNames = Get_Faction_Names();
  $Fact_Colours = Get_Faction_Colours();
  $ThingProps = Thing_Type_Props();
  $tprops = $ThingProps[$t['Type']];
  $N = Get_System($t['SystemId']);
  $Syslocs = Within_Sys_Locs($N);
  $LinkTypes = Get_LinkLevels();
  
  if ($t['SystemId'] == $t['NewSystemId']) {
    $NewSyslocs = $Syslocs;
  } elseif ($t['NewSystemId']) {
    $NN = Get_System($t['NewSystemId']);
    $NewSyslocs = Within_Sys_Locs($NN);
  } else {
    $NewSyslocs = [];
  }
  $Systems = Get_SystemRefs();
  $t['MaxModules'] = Max_Modules($t);
  if  ($tprops & THING_HAS_MODULES) $t['OrigHealth'] = Calc_Health($t);
  
  $Links = Get_Links($N['Ref']);
  $SelLinks = [''];
  $SelCols = [''];
  if ($GM) {
    foreach ($Links as $L) {
      $SelLinks[$L['id']] = "#" . $L['id'] . " to " . (($L['System1Ref'] == $N['Ref'])?$L['System2Ref']: $L['System1Ref'] );
      $SelCols[$L['id']] = $LinkTypes[$L['Level']]['Colour'];
    }
  } else {
    $NearNeb = $N['Nebulae'];
    $NS = Get_FactionSystemFS($Fid,$N['id']);

    foreach ($Links as $L) {
      $LinkText = "Unknowm";
      $FL = Get_FactionLink($Fid,$L['id']);
      $FarSysRef =  (($L['System1Ref'] == $N['Ref'])?$L['System2Ref']: $L['System1Ref'] );
      $FSN = Get_SystemR($FarSysRef);
      $FarNeb = $FSN['Nebulae'];
      $FS = Get_FactionSystemFS($Fid,$FSN['id']);
//echo "<p>doing link " . $L['id'] . " to $FarSysRef ". $FSN['id'] ; var_dump($FS);
      if (isset($FL['known']) && $FL['known']) {
        $LinkText = $FarSysRef;
      } else if ($NearNeb == 0) {
        if (isset($FS['id'])) {
          if ($FarNeb == 0) {
            $LinkText = $FarSysRef;
          } else if ($FS['NebScanned'] >= $FarWeb) {
            $LinkText = $FarSysRef;
          } else {
            $LinkText = '?';
          }
        } else {
          $LinkText = '?';
        }
      } else if ($NS['NebScanned'] >= $NearWeb) { // In a Neb...
        if (isset($FS['id'])) {
          $LinkText = $FarSysRef;
        } else {
          $LinkText = '?';
        }
      } else { 
        continue; // Can't see that link
      }
      $SelLinks[$L['id']] = "#" . $L['id'] . " to " . $LinkText;
      $SelCols[$L['id']] = $LinkTypes[$L['Level']]['Colour'];
    }
  }

//var_dump($SelLinks);

  echo "Note Movement does not yet work for armies moving by ship.<p>\n";

  echo "<form method=post id=mainform enctype='multipart/form-data' action=ThingEdit.php>";
  echo "<div class=tablecont><table width=90% border class=SideTable>\n";
  Register_AutoUpdate('Thing',$tid);
  echo fm_hidden('id',$tid);
  
  if ($GM) {
    echo "<tr class=NotSide><td class=NotSide>Id:<td class=NotSide>$tid<td class=NotSide>Game<td class=NotSide>$GAMEID<td class=NotSide>" . $GAME['Name'];
    echo "<tr><td>Type:<td>" . fm_select($ttn,$t,'Type',1); // . "<td>If Ship: " . fm_select($ShipTypes,$t,'SubType'); // need to make that easier
    echo fm_number("Level",$t,'Level');
  } else {
    echo "<tr><td>Type: " . $ttn[$t['Type']] . "<td>Level: " . $t['Level'];
  }
  echo "<tr>" . fm_text('Name',$t,'Name',2);
  echo "<td rowspan=4 colspan=4><table><tr>";
    echo fm_DragonDrop(1,'Image','Thing',$tid,$t,1,'',1,'','Thing');
  echo "</table>";

  if ($GM) {
    echo "<tr><td>Build State:" . fm_select($BuildState,$t,'Buildstate'); 
    if (isset($t['Buildstate']) && $t['Buildstate'] <= 1) {
      echo fm_number('Build Project',$t,'ProjectId');
      if ($t['ProjectId']) {
        $Proj = Get_Project($t['ProjectId']);
        echo "Status: " . $Project_Status[$Proj['Status']];
        if ($Proj['TurnStart']) echo " Start Turn: " . $Proj['TurnStart'];
        if ($Proj['TurnEnd']) echo " End Turn: " . $Proj['TurnEnd'];
      }
      
    } else {
      if ($tprops & THING_CAN_MOVE) echo "<td>Taking Link:<td>" . fm_select($SelLinks,$t,'LinkId',0," style=color:" . $SelCols[$t['LinkId']] ,'',0,$SelCols) . 
        "Update this normally";
    }

    echo "<tr><td>System:<td>" . fm_select($Systems,$t,'SystemId');
    echo "<td>" . fm_select($Syslocs,$t,'WithinSysLoc');
    if ($tprops & THING_CAN_MOVE) {
      echo "<tr><td>New System:<td>" . fm_select($Systems,$t,'NewSystemId',1) . "This is derived data<td>" . fm_select($NewSyslocs,$t,'NewLocation');
    }
    echo "<tr>" . fm_radio('Whose',$FactNames ,$t,'Whose','',1,'colspan=6','',$Fact_Colours,0); 
  } else {
    echo "<tr><td>Build State:<td>" . $BuildState[$t['Buildstate']]; 
    if (isset($t['Buildstate']) && $t['Buildstate'] <= 1) {
      if ($t['ProjectId']) {
        echo "<tr><td>See <a href=ProjEdit.php?id=" . $t['ProjectId'] . ">Project</a>";
        echo "Status: " . $Project_Status[$Proj['Status']];
        if ($Proj['TurnStart']) echo " Start Turn: " . $Proj['TurnStart'];
        if ($Proj['TurnEnd']) echo " End Turn: " . $Proj['TurnEnd'];
      }
      
    } else {
      echo "<tr><td>Current System:<td>" . $N['Ref'] . "<td>" . $Syslocs[$t['WithinSysLoc']];

      if ($tprops & THING_CAN_MOVE) {
        switch ($t['Buildstate']) {
        
        case 0: // Plan
        case 1: // Build
        case 4: // Ex thing
          break; // Can't move
        case 2: // Shakedown - In sys only
          echo "<tr><td>Where in the system should it go? " . fm_select($Syslocs,$t,'WithinSysLoc');
          break;
        case 3: //
           echo "<tr><td>Taking Link:<td>" . fm_select($SelLinks,$t,'LinkId',0," style=color:" . $SelCols[$t['LinkId']] ,'',0,$SelCols);
           if ($t['LinkId'] && !strpos($SelLinks[$t['LinkId']],'?')) {
             echo "<td>To:" . fm_select($NewSyslocs,$t,'NewLocation');
           }
        }
      } else { //Static, can specify where before start
        if (($t['Buildstate'] == 0)) {
          echo "<tr><td>Where in the system is it to be built? " . fm_select($Syslocs,$t,'WithinSysLoc');
        }
      }
    }


//    if ($tprops & THING_CAN_MOVE) {
//      echo "<tr><td>New System:<td>" . fm_select($Systems,$t,'NewSystemId',1) . "This is derived data<td>" . fm_select($NewSyslocs,$t,'NewLocation');
//    }
  }

  if  ($tprops & THING_HAS_GADGETS) echo "<tr>" . fm_textarea("Gadgets",$t,'Gadgets',8,3);
  echo "<tr>" . fm_textarea("Description\n(For others)",$t,'Description',8,2);
  echo "<tr>" . fm_textarea('Notes',$t,'Notes',8,2);
  echo "<tr>" . fm_textarea('Named Crew',$t,'NamedCrew',8,2);
  if ($GM) echo "<tr>" . fm_textarea('GM Notes',$t,'GM_Notes',8,2,'class=NotSide');
  echo "<tr>" . fm_textarea('History',$t,'History',8,2);
  if  ($tprops & THING_HAS_MODULES) {
    if ($GM) {
      echo "<tr>" . fm_number('Orig Health',$t,'OrigHealth') . fm_number('Cur Health',$t,'CurHealth');
    } else {
      echo "<tr><td>Original Health: " . $t['OrigHealth'] . "<td>Current Health: " . $t['CurHealth'];
    }
    echo "<td>Basic Damage: " . Calc_Damage($t);
  }
  if ($tprops & THING_HAS_DISTRICTS) {
    $DTs = Get_DistrictTypeNames();
    $Ds = Get_DistrictsT($tid);

    $NumDists = count($Ds);
    $dc=0;
    $totdisc = 0;

    if ($GM) {
      if ($NumDists) echo "<tr><td rowspan=" . ceil(($NumDists+2)/2) . ">Districts:";
      
      foreach ($Ds as $D) {
        $did = $D['id'];
        if (($dc++)%2 == 0)  echo "<tr>";
        echo "<td>" . fm_Select($DTs, $D , 'Type', 1,'',"DistrictType-$did") . fm_number1('', $D,'Number', '','',"DistrictNumber-$did");
        $totdisc += $D['Number'];      
      };


      echo "<tr><td>Add District Type<td>" . fm_Select($DTs, NULL , 'Number', 1,'',"DistrictTypeAdd-$tid");
      echo fm_number("Max Districts",$t,'MaxDistricts');
      echo fm_number(($t['ProjHome']?"<a href=ProjHome.php?id=" . $t['ProjHome'] . ">Project Home</a>":"Project Home"),$t,'ProjHome');

      if (!isset($t['MaxDistricts'])) $t['MaxDistricts'] = 0;
      if ($totdisc > $t['MaxDistricts']) echo "<td class=Err>TOO MANY DISTRICTS\n";
    } else {
      if ($NumDists) echo "<tr><td rowspan=" . ceil(($NumDists+4)/4) . ">Districts:";
      
      foreach ($Ds as $D) {
        $did = $D['id'];
        if (($dc++)%4 == 0)  echo "<tr>";
        echo "<td>" . $DTs[$D['Type']] . ": " . $D['Number'];
        $totdisc += $D['Number'];      
      };

      if (($dc++)%4 == 0)  echo "<tr>";
      echo "<td>Max Districts: " . $t['MaxDistricts'];
      if (($dc++)%4 == 0)  echo "<tr>"; 
      if ($t['ProjHome']) echo "<td><a href=ProjDisp.php?H=" . $t['ProjHome'] . ">Projects here</a>";
    }
  }
  if ($tprops & THING_HAS_MODULES) {
//    echo "<tr><td>Modules to be done\n";
    $MTs = Get_ModuleTypes();

//var_dump($MTs);
//    $MTNs = [];
//    foreach($DTs as $M) $MTNs[$M['id']] = $M['Name'];
    $MTNs = Get_Valid_Modules($t);
    $Ds = Get_Modules($tid);

    $NumMods = count($Ds);
    $dc=0;
    $totmodc = 0;
    $BadMods = 0;
    
    if ($GM) { // TODO Allow for setting module levels 
      if ($NumMods) echo "<tr><td rowspan=" . ceil(($NumMods+2)/2) . ">Modules:";
  
      foreach ($Ds as $D) {
        $did = $D['id'];
        if (($dc++)%2 == 0)  echo "<tr>";
        echo "<td>" . fm_Select($MTNs, $D , 'Type', 1,'',"ModuleType-$did")
                    . fm_number1('Level', $D,'Level', '', '',"ModuleLevel-$did") 
                    . fm_number0('', $D,'Number', '','',"ModuleNumber-$did") 
                    . "<button id=ModuleRemove-$did onclick=AutoInput('ModuleRemove-$did')>R</button>";
        if (!isset($MTNs[$D['Type']])) $BadMods += $D['Number'];
        $totmodc += $D['Number'] * $MTs[$D['Type']]['SpaceUsed'];
        };

      echo "<tr><td>Add Module Type<td>" . fm_Select($MTNs, NULL , 'Number', 1,'',"ModuleTypeAdd-$tid");
      echo fm_number("Max Modules",$t,'MaxModules');
      echo fm_number1("Deep Space",$t,'HasDeepSpace');
      if ($totmodc > $t['MaxModules']) {
        echo "<td class=Err>TOO MANY MODULES\n";
      } elseif ($BadMods) {
        echo "<td class=Err>$BadMods INVALID MODULES\n";
      } else {
        echo "<td>Module space used: $totmodc";
      }
    } else {
      if ($NumMods) echo "<tr><td rowspan=" . ceil(($NumMods+4)/4) . ">Modules:";
  
      $MTs = Get_ModuleTypes();
      foreach ($Ds as $D) {
        $did = $D['id'];
        if (($dc++)%4 == 0)  echo "<tr>";
        echo "<td>" . $MTNs[$D['Type']] . "(Level " . $D['Level'] . "): " . $D['Number'];
        
        $CLvl = Calc_TechLevel($Fid,$D['Type']);
        if ($CLvl != $D['Level']) {
          echo ". <span class=Blue> Note you have Level: $CLvl </span>";
        }
        if (!isset($MTNs[$D['Type']])) $BadMods += $D['Number'];
        $totmodc += $D['Number'] * $MTs[$D['Type']]['SpaceUsed'];
        };

      echo "<tr><td>Max Modules: " . $t['MaxModules'];
//      echo fm_number1("Deep Space",$t,'HasDeepSpace');
      if ($totmodc > $t['MaxModules']) {
        echo "<td class=Err>TOO MANY MODULES\n";
      } elseif ($BadMods) {
        echo "<td class=Err>$BadMods INVALID MODULES\n";
      } else {
        echo "<td>Module space used: $totmodc";
      }
    }

 // TODO 
 // Max modules, current count, what 
  }
  if (Access('God')) echo "<tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea>";  
  echo "</table></div>\n";

  if ($GM) {
    echo "<h2><a href=ThingList.php>Back to Thing list</a> &nbsp; <input type=submit name=ACTION value=Duplicate></h2>";
  } else {
    echo "<h2><a href=PThingList.php?id=$Fid>Back to Thing list</a></h2>";
  }
}

function Scanners(&$T) {
  $mods = Get_ModulesType(4,$T['id']);
  if ($mods) return $mods['Number'] * ($mods['Level']+1);
  return 0;
}

//  &#8373; = Credit symbol

?>
