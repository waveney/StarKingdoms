<?php
// Lib of Thing related stuff
include_once("sk.php");
include_once("GetPut.php");
include_once("vendor/erusev/parsedown/Parsedown.php");
include_once("PlayerLib.php");

function EyesInSystem($Fid,$Sid,$Of=0) { // Eyes 1 = in space, 2= sens, 4= neb sens, 8=ground, 16=static this turn
//var_dump($Fid,$Sid);
  $Neb = $Eyes = 0;
  $ThingTypes = Get_ThingTypes();
  if ($Of) {
    $MyThings = [ Get_Thing($Of) ];
  } else {
    $MyThings = Get_Things_Cond($Fid," SystemId=$Sid AND BuildState=" . BS_COMPLETE );
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
      if ($T['LinkId'] == 0) $eye +=16;
    }
    if (($Neb > 0) && ($T['NebSensors'] < $Neb) && (($eye&4 ==0))) continue;
    if ($T['PrisonerOf']) continue;
    $Eyes |= $eye;
    if ($T['Sensors']) $Eyes |= 2;
    if ($T['NebSensors']) $Eyes |= 4;
  }
// echo "System " . $N['Ref'] . " eyes: $Eyes<br>";
  if (($Eyes&1) == 0) { // Check for Branch on outpost
    include_once("OrgLib.php");
    $OP = Outpost_In($Sid,0,0);
    if ($OP) {
      $Bs = Gen_Get_Cond1('Branches',"Whose=$Fid AND HostType=3 AND HostId=" . $OP['id']);
      if ($Bs) $Eyes |= 0x11;  // Space+Static
    }
  }
  if (($Eyes&8) == 0) { // Check for branch on a world
    $Ps = Get_Planets($Sid);
    foreach ($Ps as $Pid=>$P) {
      if ($P['Control']) {
        $Bs = Gen_Get_Cond1('Branches',"Whose=$Fid AND HostType=1 AND HostId=$Pid");
        if ($Bs) {
          $Eyes |=8;
          break;
        }
        $Ms = Get_Moons($Pid);
        foreach ($Ms as $Mid=>$M) {
          if ($M['Control']) {
            $Bs = Gen_Get_Cond1('Branches',"Whose=$Fid AND HostType=2 AND HostId=$Mid");
            if ($Bs) {
              $Eyes |=8;
              break 2;
            }
          }
        }
      }
    }
  }
  return $Eyes;
}

function SeeThing(&$T,&$LastWhose,$Eyes,$Fid,$Images=0,$GM=0,$Div=1,$Contents=0, $Depth=0) {
  // Contents 0 = None, 1 = All, 2 = Most
  global $Advance,$FACTION,$ThingInstrs,$Relations,$MoveNames,$MoveProps;
  static $ThingTypes;
  static $Factions;
  static $OpProps;
  static $ModuleTypes;
  static $FB;
  if (!$ThingTypes) $ThingTypes = Get_ThingTypes();
  if (!$Factions) $Factions = Get_Factions();
  if (!$ModuleTypes) $ModuleTypes = Get_ModuleTypes();
  $Locations = Within_Sys_Locs_Id($T['SystemId']);
  if (!$FB) $FB = array_flip(NamesList($ModuleTypes))['Fighter Bays'];

  $txt = '';
  $itxt = '';
  $Tid = $T['id'];
//  var_dump($Tid);
  $RawA = 0;

//  if ($T['id'] == 238) { echo "Here with outpost<br>"; var_dump($T); }
  $TTprops = ($ThingTypes[$T['Type']]['Properties']??0);
  $TTprops2 = ($ThingTypes[$T['Type']]['Prop2']??0);

  if ($T['CurHealth'] == 0 && ($TTprops & THING_CAN_BE_SPLATED)) return '';
  if ($T['LinkId'] == LINK_INBRANCH) return '';
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
//var_dump($stm);
      if ($Fid >=0 && ($T['Whose'] != $Fid) && ($T['PrisonerOf'] != $Fid) &&
          (((($ThingTypes[$T['Type']]['SeenBy']??0) & $stm & $Eyes) == 0 ))) {
        return '';
      }
      if ($T['BuildState'] < BS_SERVICE || ($T['BuildState'] >= BS_EX && !$Div)) return ''; // Building or abandoned
      $LocClass='Space';
      $LocType = intdiv($T['WithinSysLoc'],100);
      if (($T['WithinSysLoc'] == 3) || $LocType==2 || $LocType==4 ) $LocClass = 'Ground';

      if ($Div && $LastWhose && $LastWhose!= $T['Whose']) $txt .= "<P>";
      $Imgxtra = 0;
      if ($Div) {
        if (($T['BuildState'] >= BS_EX) || (($T['Type'] == 23) && $GM)) { // Named Chars
          if ($GM) {
            $txt .= "<div class='FullD SeeThingTxt $LocClass' hidden>";
            $Imgxtra = 1;
          } elseif ($T['BuildState'] == BS_ABANDON) {
            $txt .= "<div class='SeeThingTxt $LocClass' >The Abandoned: ";
            $Imgxtra = 1;
          } elseif ($T['BuildState'] == BS_DELETE) {
            $txt .= "<div class='FullD SeeThingTxt $LocClass' hidden>The pending deletion remains of: ";
            $Imgxtra = 1;
          } elseif ($T['BuildState'] >= BS_EX) {
            $txt .= "<div class='FullD SeeThingTxt $LocClass' hidden>The remains of: ";
            $Imgxtra = 1;
          } else {
            $txt .= "<div class='SeeThingTxt $LocClass'>";
          }
        } else {
          $txt .= "<div class='SeeThingTxt $LocClass'>";
        }
      }

      if ($T['Whose'] || $GM) {
        $txt .= ((($Fid < 0) || ($Fid == $T['Whose']) || $GM )?( "<a href=ThingEdit.php?id=$Tid>" .
                (empty($T['Name'])?"Unnamed":$T['Name']) . "</a>") : $T['Name'] ) . ", a";
        $RawA = 1;
      }
      if ($TTprops & THING_HAS_LEVELS) {
        $txt .= " level " . $T['Level'] . " ";
        $RawA = 0;
      }
      if ($T['Class']) {
        if ($RawA && is_vowel($T['Class'])) $txt .= "n";
        $txt .= " " . $T['Class'];
        $RawA = 0;
      }
      if ($T['Whose']) {
        if ($T['HideOwner'] && !$GM) {
          $txt .= " (Unidentified Owner) ";
        } else {
          $Who = (isset($Factions[$T['Whose']])?
               ($Factions[$T['Whose']]['Adjective']?$Factions[$T['Whose']]['Adjective']:$Factions[$T['Whose']]['Name'])
             :'Unknown');
          if ($RawA && is_vowel($Who)) $txt .= "n";
          $txt .= " <span " . FactColours($T['Whose']) . ">$Who</span>";
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
      if ($Div && ($T['LinkId'] >= 0 || ($MoveProps[$T['LinkId']]&1)) && $T['SystemId'] > 0 && isset($T['WithinSysLoc']) && ($T['WithinSysLoc'] > 0)) {
        if ($TTprops2 & THING_AT_LINK) {
          $L = Get_Link($T['Dist1']);
          $txt .= " ( At " . ($L['Name']??'Unknown') . " ) ";
        } else if (isset($Locations[$T['WithinSysLoc']])) {
          $txt .= " ( " . $Locations[$T['WithinSysLoc']] . " ) ";
        } else if ($GM) {
          $txt .= " ( Unknown Location " . $T['WithinSysLoc'] . " ) ";
        }
      }

      if (($ThingTypes[$T['Type']]['Name'] == 'Fighter') &&
        ($T['LinkId'] == LINK_ON_BOARD) &&
        (!Get_ModulesType($T['SystemId'], $FB) )) $txt .= " (Flatpacked) ";

      if ($Div) {
//        var_dump($Fid, $T['Whose']);
        if ($T['Whose'] == $Fid) {
//          $txt .=  " <span style=background:limegreen> Yours </span>";
        } else {
          $FA = Get_FactionFactionFF($T['Whose'],$Fid);

          $Rel = $FA['Relationship']??5;
          if (!isset($Relations[$Rel])) $Rel = 5;
          $R = $Relations[$Rel];
// var_dump($FA,$R);
          $txt .=  " <span style=background:" . $R[1] . ">" . $R[0] . " </span>";
        }
      }

      if ($T['PrisonerOf']) {
        if ($GM || (isset($FACTION['id']) && $T['PrisonerOf'] == $FACTION['id'])) {
          $Fact = Get_Faction($T['PrisonerOf']);
          $txt .= ", Prisoner Of: <span " . FactColours($T['PrisonerOf']) . ">" . $Fact['Name'] . "</span>";
        }
      } else if ($GM && !empty($T['Orders'])) $txt .= ", <span style='background:#ffd966;'>Orders: " . $T['Orders'] . "</span>";
      if ($Images) {
        if (!empty($T['Image'])) {
          $itxt .= " <img valign=top src=" . $T['Image'] . " height=100 class=SeeImage> ";
        } else {
          $dflt = DefaultImage($T['Whose'], $T['Type']);
// var_dump($dflt);
          if ($dflt) $itxt .= " <img valign=top src=$dflt height=100 class=SeeImage> ";
        }
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
        if (($TTprops & THING_HAS_HEALTH) && ($T['BuildState'] < BS_EX)) {
          if ($T['CurHealth']*10 <= $T['OrigHealth']) {
            $txt .= " - Badly Damaged ";
          } else if ($T['CurHealth']*2 <= $T['OrigHealth']) {
            $txt .= " - Damaged ";
          } else if ($T['CurHealth'] <= ($T['OrigHealth']*0.8)) {
            $txt .= " - Minor Damage ";
          }
        }
      }
//      if ( $GM && ($TTprops & THING_ISA_TEAM)) {
        if ($T['Description'] && (($T['Whose'] == $Fid) || ($TTprops & THING_ISA_TEAM))) {
          $txt .= "<br>" . ParseText($T['Description']);
          $Images = 1; // To add the br at end
        }
//      }
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

      if ($Contents) {
        if ($T['BuildState'] == BS_SERVICE) {
          $txt .= "<br>Being Serviced";
        } else if ($T['Instruction']) {
          $txt .= "<br>Doing: " . $ThingInstrs[$T['Instruction']];
        }
        $OnBoard = Get_Things_Cond(0,"(LinkId=-1 OR LinkId=-3 OR LinkId=-4) AND SystemId=$Tid AND BuildState=" . BS_COMPLETE);
 //       var_dump($Tid,$Contents,$OnBoard);
        if ($OnBoard) {
          $txt .= "<br>On Board: <ul>";
 //         if ($Div) $txt .= "<div class=$LocClass>" ;
          foreach ($OnBoard as $H) {
            $Hid = $H['id'];
            if (!$Depth) $txt .= "<form method=post action=Meetings.php?ACTION=UNLOAD&id=$Hid>";
            $hprops = $ThingTypes[$H['Type']]['Properties'];
            $txt .= "<li>" . SeeThing($H,$LastWhose,$Eyes,$Fid,0,$GM,0,1,$Depth+1);
  /*             $txt .=  "<li><a href=ThingEdit.php?id=" . $H['id'] . ">" . $H['Name'] . "</a> a " . (($hprops & THING_HAS_LEVELS)? "Level " . $H['Level'] : "") .
                   " " . $H['Class'] . " " . $ThingTypes[$H['Type']]['Name'];
*/

            if ($GM) {
//              $FB = array_flip(NamesList($ModuleTypes))['Fighter Bays'];

              if ($hprops & ( THING_HAS_ARMYMODULES | THING_HAS_SHIPMODULES )) {
                $txt .= "<button type=submit >Unload Before Fight</button><br>";
              }
            }
            if (!$Depth) echo "</form>";
          }
          $txt .= "</ul>";
//          if ($Div) $txt .= "</div>";
        }
      }

      if ($Images) $txt .= "<br clear=all>\n";

      if ($Div) $txt .= "<p></div>";

      if ($itxt) {
        if ($Imgxtra) {
          $txt = "<div class='SeeThingwrap $LocClass'>$txt<div class='FullD SeeThingImg' hidden>$itxt</div></div>";
        } else {
          $txt = "<div class='SeeThingwrap $LocClass'>$txt<div class=SeeThingImg>$itxt</div></div>";
        }
      }
      $LastWhose = $T['Whose'];
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
       if ($N['Control']) {
         $Fac = Get_Faction($N['Control']);
       } else {
         $Fac = [];
       }
       $xtra = '';
       if ($GM && $N['Nebulae']) $xtra = " - Nebula " . Plural($N['Nebulae'],'',''," (" . $N['Nebulae'] . ")");
       $txt .= "<p><h2 " . FactColours($N['Control']??0,'beige') . "><a href=SurveyReport.php?id=$Sid>System " . System_Name($N,$Fid) . $xtra . "</a></h2>";
    } else {
       $txt .= "<h2>In the System is:</h2>";
    }
    $LastWhose = 0;
    foreach ($Things as $T) {
      $txt .= SeeThing($T,$LastWhose,$Eyes,$Fid,$Images,$GM,1,($GM || ($Fid == $T['Whose'])),0); //,$ThingTypes,$Factions);
    }
  return $txt;
}

?>
