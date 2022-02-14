<?php
// Lib of Thing related stuff
include_once("sk.php");
include_once("GetPut.php");
include_once("vendor/erusev/parsedown/Parsedown.php");
include_once("PlayerLib.php");  

global $ModuleCats,$ModFormulaes,$ModValues,$Fields,$Tech_Cats,$CivMil,$BuildState;

$ModuleCats = ['Ship','Civilian Ship','Support Ship','Military Ship','Army','Other'];
$Fields = ['Engineering','Physics','Xenology'];
$Tech_Cats = ['Core','Supp','Non Std'];
$CivMil = ['','Civilian','Military'];
$BuildState = ['Planning','Building','Shakedown','Complete','Ex','Abandonded'];

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

function Within_Sys_Locs(&$N,$PM=0,$Boarding=0,$Restrict=0) {// $PM +ve = Planet Number, -ve = Moon Number, restrict 1=ON, 2space only
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
      if ($Restrict == 0 || ($Restrict == 2 && $PTD[$P['Type']]['Hospitable'])) $L[200 +$pi] = "On $PName";
      $Ms = Get_Moons($P['id']);
      if ($Ms) {
        foreach ($Ms as $M) {
          if ($PM == -$M['id']) return 400+$mi;
          $MName = PM_Type($PTD[$M['Type']],"Moon") . " - " . NameFind($M);
          if ($Restrict !=1) $L[300 +$mi] = "Orbiting $MName";
          if ($Restrict == 0 || ($Restrict == 2 && $PTD[$M['Type']]['Hospitable'])) $L[400 +$mi] = "On $MName";
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
    if ($ModuleCats[$M['CivMil']] == 'Army') {
      if (($tprop & THING_HAS_ARMYMODULES) == 0) continue;
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
    if (Has_Tech($t['Whose'], 'Compact Ship Design') && $t['Level'] > 1) $v += $t['Level'];
    if ($t['Level'] > 2 && Has_Trait($t['Whose'],'Really Big')) $v += $t['Level']*$t['Level'];
    if ($TTs['Name'] == 'Satellite Defences') $v += Has_Tech($t['Whose'],7); 
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
  $t['BuildState'] = 3;
  $t['CurHealth'] = $t['OrigHealth'];
  Put_Thing($t);
}

function Show_Thing(&$t,$Force=0) {
  include_once("ProjLib.php");
  global $BuildState,$GAME,$GAMEID;
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

  if ($t['BuildState'] == 3) { // Complete Only
    $Links = (empty($N['Ref']) ? [] : Get_Links($N['Ref']));
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
        } else if ($NS['NebScanned'] >= $NearNeb) { // In a Neb...
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
  }

//var_dump($SelLinks);

  echo "Note Movement does not yet work for armies moving by ship.<p>\n";
  
  if ($t['BuildState'] == 0) echo "Note the Tech level of this will be recorded when it is built<br>";

  echo "<form method=post id=mainform enctype='multipart/form-data' action=ThingEdit.php>";
  echo "<div class=tablecont><table width=90% border class=SideTable>\n";
  Register_AutoUpdate('Thing',$tid);
  echo fm_hidden('id',$tid);
  
  if ($GM) {
    echo "<tr class=NotSide><td class=NotSide>Id:<td class=NotSide>$tid<td class=NotSide>Game<td class=NotSide>$GAMEID<td class=NotSide>" . $GAME['Name'];
    echo "<tr><td>Type:<td>" . fm_select($ttn,$t,'Type',1); 
    echo fm_number("Level",$t,'Level');
  } else {
    echo "<tr><td>Type: " . $ttn[$t['Type']] . "<td>Level: " . $t['Level'];
  }
  echo "<tr>" . fm_text('Name',$t,'Name',2);

  echo "<td rowspan=4 colspan=4><table><tr>";
    echo fm_DragonDrop(1,'Image','Thing',$tid,$t,1,'',1,'','Thing');
  echo "</table>";
  echo "<tr>" . fm_text('Class',$t,'Class',2);
  if ($GM) {
    echo "<tr><td>Build State:" . fm_select($BuildState,$t,'BuildState'); 
    if (isset($t['BuildState']) && $t['BuildState'] <= 1) {
      echo fm_number('Build Project',$t,'ProjectId');
      if ($t['ProjectId']) {
        $Proj = Get_Project($t['ProjectId']);
//var_dump($Proj,"<br>", $Project_Status);
        echo "Status: " . $Project_Status[$Proj['Status']];
        if ($Proj['TurnStart']) echo " Start Turn: " . $Proj['TurnStart'];
        if ($Proj['TurnEnd']) echo " End Turn: " . $Proj['TurnEnd'];
      }
      
    } else {
      if (! empty($t['SystemId'])) {
        if (!isset($t['LinkId']) || !isset($SelCols[$t['LinkId']])) $t['LinkId'] = 0;
        if ($tprops & THING_CAN_MOVE) echo "<td>Taking Link:<td>" . fm_select($SelLinks,$t,'LinkId',0," style=color:" . $SelCols[$t['LinkId']] ,'',0,$SelCols) . 
            "Update this normally";
        if ($tprops & THING_CAN_BETRANSPORTED) echo "<td>No mechanism<td>to move armies yet";  // TODO transport armies
      }
    }

    echo "<tr><td>System:<td>" . fm_select($Systems,$t,'SystemId',1);
    echo "<td>" . fm_select($Syslocs,$t,'WithinSysLoc');
    if ($tprops & THING_CAN_MOVE) {
      echo "<tr><td>New System:<td>" . fm_select($Systems,$t,'NewSystemId',1) . "This is derived data<td>" . fm_select($NewSyslocs,$t,'NewLocation');
    }
    echo "<tr>" . fm_radio('Whose',$FactNames ,$t,'Whose','',1,'colspan=6','',$Fact_Colours,0); 
  } else {
    echo "<tr><td>Build State:<td>" . $BuildState[$t['BuildState']]; 
    if (isset($t['BuildState']) && $t['BuildState'] <= 1) {
      if (!empty($t['ProjectId'])) {
        $Proj = Get_Project($t['ProjectId']);
        echo "<tr><td>See <a href=ProjEdit.php?id=" . $t['ProjectId'] . ">Project</a>";
        echo "Status: " . $Project_Status[$Proj['Status']];
        if ($Proj['TurnStart']) echo " Start Turn: " . $Proj['TurnStart'];
        if ($Proj['TurnEnd']) echo " End Turn: " . $Proj['TurnEnd'];
      }
      
    } else {
      echo "<tr><td>Current System:<td>" . $N['Ref'] . "<td>" . $Syslocs[$t['WithinSysLoc']];

      if ($tprops & THING_CAN_MOVE) {
        switch ($t['BuildState']) {
        
        case 0: // Plan
        case 1: // Build
        case 4: // Ex thing
          break; // Can't move
        case 2: // Shakedown - In sys only
          echo "<tr><td>Where in the system should it go? " . fm_select($Syslocs,$t,'WithinSysLoc');
          break;
        case 3: //
//        var_dump($SelLinks);
//           if (empty($SelCols[$t['LinkId']])) $t['LinkId'] = 0;
           if (count($SelLinks) > 1) {
             echo "<tr><td>Taking Link:<td>" . fm_select($SelLinks,$t,'LinkId',0," style=color:" . $SelCols[$t['LinkId']] ,'',0,$SelCols);
             if ($t['LinkId'] && !strpos($SelLinks[$t['LinkId']],'?')) {
               echo "<td>To:" . fm_select($NewSyslocs,$t,'NewLocation');
             }
           } else {
             echo "<tr><td>Row Not Used\n";
           }
        }
      } else if ($tprops & THING_CAN_BETRANSPORTED) {
        echo "<tr><td>Transport of armies<td>not yet written\n"; // TODO move armies
      } else { //Static, can specify where before start
        if (($t['BuildState'] == 0)) {
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
  if ($tprops & THING_HAS_2_FACTIONS) echo "<tr>" . fm_radio('Other Faction',$FactNames ,$t,'OtherFaction','',1,'colspan=6','',$Fact_Colours,0); 
  if  ($tprops & THING_HAS_MODULES) {
    if ($GM) {
      echo "<tr>" . fm_number('Orig Health',$t,'OrigHealth') . fm_number('Cur Health',$t,'CurHealth');
    } else {
      echo "<tr><td>Original Health: " . $t['OrigHealth'] . ($t['BuildState']? "<td>Current Health: " . $t['CurHealth'] : "");
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
//    $T['Sensors'] = $T['SensorLevel'] = $T['NebSensors'] = 0;
    
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
        
        if ($D['Type'] == 4) { 
          $T['Sensors'] = $D['Number'];
          $T['SensorLevel'] = D['Level'];
        } else if ($D['Type'] == 9) $D['NebSensors'] = $D['Number'];
      }
      echo "<tr><td>Add Module Type<td>" . fm_Select($MTNs, NULL , 'Number', 1,'',"ModuleTypeAdd-$tid");
      echo fm_number("Max Modules",$t,'MaxModules');
      if ($tprops & THING_HAS_CIVSHIPMODS) {
        echo fm_number1("Deep Space",$t,'HasDeepSpace');
        echo fm_number1("Cargo Space",$t,'CargoSpace');
      }
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
        echo "<td>" . $MTNs[$D['Type']] . ($t['BuildState']? ("(Level " . $D['Level'] . "): ")  : ": ") . $D['Number'];
        
        $CLvl = Calc_TechLevel($Fid,$D['Type']);
        if ($CLvl != $D['Level'] && $t['BuildState'] != 0 ) {
          echo ". <span class=Blue> Note you have Level: $CLvl </span>";
        }
        if (!isset($MTNs[$D['Type']])) $BadMods += $D['Number'];
        $totmodc += $D['Number'] * $MTs[$D['Type']]['SpaceUsed'];
        };

      if ($totmodc > $t['MaxModules']) {
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
    }

 // TODO 
 // Max modules, current count, what 
  }
  
  if ($GM && ($tprops & THING_HAS_CIVSHIPMODS)) {
    echo "<tr>" . fm_number('Sensors',$T,'Sensors') . fm_number('Sens Level',$T,'SensorLevel') . fm_number('Neb Sensors', $T,'NebSensors');
  }
  if (Access('God')) echo "<tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea>";  
  echo "</table></div>\n";

  if ($GM || empty($Fid)) {
    echo "<h2><a href=ThingList.php>Back to Thing list</a> &nbsp; <input type=submit name=ACTION value=Duplicate></h2>";
  } else {
    echo "<h2><a href=PThingList.php?id=$Fid>Back to Thing list</a></h2>";
  }
}

function Scanners(&$T) {
  return $T['Sensors'] * $T['SensorLevel'];
  
  $mods = Get_ModulesType(4,$T['id']);
  if ($mods) return $mods['Number'] * ($mods['Level']+1);
  return 0;
}

function NebScanners(&$T) {
  return $T['NebSensors'] * $T['SensorLevel'];
  
  $mods = Get_ModulesType(9,$T['id']);
  if ($mods) return $mods['Number'] * ($mods['Level']+1);
  return 0;
}

function Calc_Scanners(&$T) {
  $mods = Get_ModulesType(4,$T['id']);
  $nebs = Get_ModulesType(9,$T['id']);
  $Cargo = Get_ModulesType(8,$T['id']);
  $T['Sensors'] = ($mods?$mods['Number']:0);
  $T['SensorLevel'] = ($mods?$mods['Level']:0);
  $T['NebSensors'] = ($nebs?$nebs['Number']:0);
  $T['CargoSpace'] = ($Cargo?$Cargo['Number']*$Cargo['Level']:0);
  if ($Deep = Get_ModulesType(3,$T['id'])) {
    $T['HasDeepSpace'] = $Deep['Number']*$Deep['Level'];
  }else {
    $T['HasDeepSpace'] = 0;
  }
}

function RefitRepair(&$T) {
// Refit
  $tid = $T['id'];
  $Mods = Get_Modules($tid); 
  if ($Mods) {
    foreach ($Mods as $M) {
      $Lvl = Calc_TechLevel($T['Whose'],$M['Type']);
      $M['Level'] = $Lvl;
      Put_Module($M);
    }
  }
// Repair
  $Health = Calc_Health($T);
  $T['CurHealth'] = $T['OrigHealth'] = $Health;
  Put_Thing($T);
}

//  &#8373; = Credit symbol

function LogsticalSupport($Fid) {  // Note this sets the Economic rating of all PHs
  $PHomes = Get_ProjectHomes($Fid);
  
  $Logistics = [0,0,0];


  
  $ThingTypes = Get_ThingTypes();
  $DistTypes = Get_DistrictTypes();
  
  foreach($PHomes as $H) {
    $PH['Economy'] = 0;
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
        Put_Thing($PH);
        continue 2;  // Remove things without districts
      }
      $EndAct = 'Put_Thing';
      break;
    }
    
    $Prime = 0;
    $District_Type = [];
    foreach ($Dists as $D) {
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
        $Logistics[1]+= $D['Number'];
        break;
      case 'Shipyard' :
        $Logistics[0]+= $D['Number'];
        break;
      case 'Intelligence' :
        $Logistics[2]+= $D['Number'];
        break;
      default:
        break;
      }
    }
    
    $PH['Economy'] = $Prime * $Commerce*2 + (isset($PH['Minerals'])?(min($PH['Minerals'], $Commerce)):0);
    $EndAct($PH);
  }
        
  if ($ln = Has_Tech($Fid,'Naval Logistics')) $Logistics[0] += 2*$ln;
  if ($ln = Has_Tech($Fid,'Army Logistics')) $Logistics[1]  += 2*$ln;
  if ($ln = Has_Tech($Fid,'Intelligence Logistics')) $Logistics[3] += 2*$ln;
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
  return $t;
}

function EyesInSystem($Fid,$Sid) { // Eyes 1 = in space, 2= sens, 4= neb sens, 8=ground
//var_dump($Fid,$Sid);
  $Eyes = 0;
  $ThingTypes = Get_ThingTypes();
  $MyThings = Get_Things_Cond($Fid," SystemId=$Sid AND (BuildState=2 OR BuildState=3)");

  foreach ($MyThings as $T) {
    $Eyes |= $ThingTypes[$T['Type']]['Eyes'];
    if ($T['Sensors']) $Eyes |= 2;
    if ($T['NebSensors']) $Eyes != 4;
  }
  return $Eyes;
}

function SeeInSystem($Sid,$Eyes,$heading=0,$Images=1) {
//var_dump($Sid,$Eyes);
//  if (Access('GM')) $Eyes = 15;
    if (!$Eyes) return;
    $Things = Get_AllThingsAt($Sid);
    if (!$Things) return;
    $ThingTypes = Get_ThingTypes();
  
    $Factions = Get_Factions();
    
// var_dump ($Things); echo "XX<p>";   
    $N = Get_System($Sid);
    if ($heading) {
       echo "<h2>System " . $N['Ref'] . "</h2>"; // TODO Add name...
    } else {
       echo "<h2>In the System is:</h2>";
    }
    $LastWhose = 0;
    foreach ($Things as $T) {
//var_dump($T); echo "<p>";
      if (($ThingTypes[$T['Type']]['SeenBy'] & $Eyes) == 0 ) continue;
      if ($LastWhose && $LastWhose!= $T['Whose']) echo "<P>";
      echo (Access('GM')?( "<a href=ThingEdit.php?id=" . $T['id'] . ">" . $T['Name'] . "</a>") : $T['Name'] ) . " a ";
      if ($ThingTypes[$T['Type']]['Properties'] & THING_HAS_LEVELS) echo " level " . $T['Level'];
      if ($T['Class']) echo " " . $T['Class'] . " class ";
      if ($T['Whose']) echo " " . $Factions[$T['Whose']]['Name'];
      echo " " . $ThingTypes[$T['Type']]['Name'];
      if ($Images && !empty($T['Image'])) echo " <img valign=top src=" . $T['Image'] . " height=100> ";
      echo "<br clear=all>\n";
      $LastWhose = $T['Whose'];
    };
}
?>
