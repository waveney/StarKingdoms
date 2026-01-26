<?php

function PTListCore($Fid,&$Faction,$GM=0,$Mode=0) {
  // Mode 0=to display, 1=to be saved
  global $FACTION,$GAMEID,$USER;
  global $ModFormulaes,$ModValues,$Fields,$Tech_Cats,$CivMil,$BuildState,$ThingInstrs,$ThingInclrs,$GAMEID,$LogistCost,$ARMY,$ARMIES,$GAME;
  global $MoveNames,$MoveProps;

  $Designs = Feature('Designs');
  if ($Designs) $BuildState[BS_PLANNING] = 'Design';
  $txt = '';
  $Things = Get_Things_Cond($Fid, "id>0 AND GameId=$GAMEID ORDER BY Priority DESC, Name");
  if (!empty($FACTION['HasPrisoners'])) {
    $Held = Get_Things_Cond(0,"PrisonerOf=$Fid AND BuildState=" . BS_COMPLETE);
    $Things = array_merge($Things,$Held);
  }
  $ThingTypes = Get_ThingTypes();
  $Systems = Get_SystemRefs();
  $Varies = Gen_Get_All_Game('Variants');
  $Factions = Get_Factions();
  $ModTypes = Get_ModuleTypes();
  if ($Fid) {
    foreach ($ModTypes as &$Mt) {
      $Lvl = Calc_TechLevel($Fid,$Mt['id']);
      $Mt['Target'] = $Lvl;
    }
  }

  $Blockades[] = 0;
  $DesLink = (Feature('Designs')?'PlanDesign.php':'ThingPlan.php');

  $txt .= "<h1>Things</h1>";

  $ShowCats = ['All','Ships',$ARMIES,'','Chars', 'Other'];
  if (!empty($FACTION['HasPrisoners'])) $ShowCats[] = 'Prisoners';
  $Show['ThingShow'] = ($Faction[$GM?'GMThingType':'ThingType'] ?? 0);
  $BuildCats = [0=>'All',1=>($Designs?'Design':'Plan'),2=>'Building',3=>'Servicing', 4=>'Complete',5=>'Other']; // VERY similar to Buildstate

  $Build['BuildShow'] = ($Faction[$GM?'GMThingBuild':'ThingBuild'] ?? 0);

  $txt .=  "<div class=floatright ><b>" . fm_radio("Show",$ShowCats,$Show,'ThingShow',' onchange=ThingListFilter()') . "<br>";
  $txt .=  fm_radio("Build State",$BuildCats, $Build,'BuildShow',' onchange=ThingListFilter()') . "</b></div>";

  $txt .=  "To see more information about each thing and to do movement and changes click on the name<p>\n";
  $txt .=  "Click on column heading to sort by column - toggles up/down<br>\n";
  $txt .=  "Ex things only show up under state <b>Other</b><br>\n";
  if ($Fid) $txt .=  "If the Thing would benefit from refit/repair/re-equipping/reinforcing then the Refit has the number of modules (+1 " .
    "if it needs repair as well)</br>";
  if ($FACTION['HasPrisoners']??0) $txt .=  "The Prisoner Tab shows Prisoners YOU have<p>\n";
  $txt .=  "Notes: <b>G/S/O</b> - Ground/Space/Other<br>";
  if ($GM) $txt .=  "<B>N</b> - GM Notes, Coloured start of name = hidden control<P>";
  if (!$GM) $txt .=  "<b>StSpN</b> = Stability, Speed or Mobility  N= Nebula Sensors<p>";
  $txt .=  "For loading/unloading of troops and characters, go to the thing<p>\n";
//  $txt .=  "Use only ONE of the filters to the right<br>\n";

  $MovesValid = 1;
  if (Has_Trait($Fid,'Star-Crossed')) {
    if (IsPrime($GAME['Turn'])) {
      $MovesValid = 0;
      $txt .=  "You are Star Crossed - No movement this turn<p>";
    }
  }

  $coln = 0;

  $txt .=  "<div class=tablecont><table class=striped id=indextable border width=100% style='min-width:1400px'>\n";
  $txt .=  "<thead><tr>";
  $txt .=  "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Name</a>\n";
  $txt .=  "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Class</a>\n";
  $txt .=  "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Type</a>\n";
  $txt .=  "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Level</a>\n";
  if (!$Fid) $txt .=  "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Whose</a>\n";
  if (Feature('Orders')) $txt .=  "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Orders</a>\n";
  $txt .=  "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Health</a>\n";
  if (!$GM) $txt .=  "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>StSpN</a>\n";
  $txt .=  "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>State</a>\n";
  $txt .=  "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Where</a>\n";
  $txt .=  "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>G/S/O</a>\n";
  $txt .=  "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Actions</a>\n";
  $txt .=  "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Using Link / Details</a>\n";
  $txt .=  "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Moving to</a>\n";
  if ($Fid) $txt .=  "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Refit?</a>\n";
  if ($GM) $txt .=  "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Sensors</a>\n";
  if ($GM) $txt .=  "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Notes</a>\n";
  if (!$GM) $txt .=  "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Prio</a>\n";

  $txt .=  "</thead><tbody>";

  $LinkTypes = Get_LinkLevels();

  if ($Fid) {
    $Logistics = [0,0,0]; // Ship, Army, Intelligence
    $HasHomeLogistics = (Has_Tech($Fid,'Simplified Home Logistics') && Access('God'));
    $FactionHome = 0;
    if ($HasHomeLogistics) {
      $Faction = Get_Faction($Fid);
      $Home = $Faction['HomeWorld'];
      if ($Home > 0) {
        $W = Get_World($Home);
        if ($W) {
          switch ($W['ThingType']) {
            case 1: // Planet
              $P = Get_Planet($W['ThingId']);
              $FactionHome = $P['SystemId'];
              break;
            case 2: // Moon
              $M = Get_Moon($W['ThingId']);
              $P = Get_Planet($W['PlanetId']);
              $FactionHome = $P['SystemId'];
              break;
            case 3: // Things
              $TH = Get_Thing($W['ThingId']);
              $FactionHome = $TH['SystemId'];
              break;
          }
        }
      }
    }
  }

  foreach ($Things as $T) {
    if (empty($T['Type'])) continue;
    $Props = ($ThingTypes[$T['Type']]['Properties']??0);
    $Prop2 = ($ThingTypes[$T['Type']]['Prop2']??0);

    $Tid = $T['id'];
    $Name = ($ThingTypes[$T['Type']]['Name']??'Unknown');
    if (!$Name) $Name = "Unknown Thing $Tid";

    if ($Fid && ($T['Whose'] != $Fid)) {
      $RowClass = 'Prisoner';
    } else if ($Prop2 & THING_ALWAYS_OTHER) {
      $RowClass = 'Other';
    } else if ($Props & THING_HAS_SHIPMODULES) {
      $RowClass = 'Ship';
    } else  if ($Props & THING_HAS_ARMYMODULES) {
      $RowClass = 'Army';
    } else  if ($Props & THING_HAS_GADGETS) {
      $RowClass = 'Agent';
    } else  if ($Name == 'Named Character') {
      $RowClass = 'Chars';
    } else {
      $RowClass = 'Other';
    }

    $BuildClass = ($T['BuildState']<BS_EX ? $T['BuildState'] : BS_EX);

    if ($Fid) {
      $Logistics = Logistics($Fid,$Things);
    }

    $txt .=  "\n<tr class='ThingList Thing_$RowClass Thing_Build$BuildClass'>";
    $txt .=  "<td><a href=" . (($T['BuildState'] || $Mode==1)? "ThingEdit.php" : $DesLink) . "?id=$Tid>" .
      ($T['Name'] ? $T['Name'] : "Nameless" ) . "</a>";
    $txt .=  "<td>" . $T['Class'];
    $txt .=  "<td>" . $Name;
    if ($T['Variant']) $txt .=  " ( " . $Varies[$T['Variant']]['Name'] . " )";
    $txt .=  "<td>" . (($Props & THING_HAS_LEVELS)?$T['Level']:'');
    if ($Fid == 0) $txt .=  "<td " . FactColours($T['Whose']) . ">" . ($Factions[$T['Whose']]['Name']??'Unknown');
    if (0) $txt .=  "<td>" . (($RowClass == 'Prisoner') ? "<span " . FactColours($T['Whose']) . ">[" .
                 ($Factions[$T['Whose']]['Name']??'Unknown') . "]</span>" : $T['Orders']);
    $txt .=  "<td><center>" . (($Props & THING_HAS_HEALTH)? $T['CurHealth'] . ' / ' . $T['OrigHealth'] : "-");
    if (!$GM) {
      if (($Props & THING_CAN_MOVE) || ($Prop2 & THING_HAS_SPEED)) {
        $txt .=  "<td>" . $T['Stability'] . ",";
        $txt .=  ceil(sprintf('%0.3g', ($Props  & THING_HAS_ARMYMODULES)?$T['Mobility']:$T['Speed']));
        if ($T['NebSensors']) $txt .=  " N";
      } else $txt .=  "<td>-";
    }
    if (!empty($T['PrisonerOf'])) {
      $txt .=  "<td>Prisoner";
      if (($T['Whose'] == $Fid) && (!$GM)) {
        $txt .=  "<td><td><td><td>";
        if ($Fid) $txt .=  "<td>";
        continue;
      }
    } else {
      $txt .=  "<td>" . ($BuildState[$T['BuildState']]??'Undefined');
    }
    if ($Props & THING_MOVES_DIRECTLY) {
      $txt .=  "<td>" . ($Systems[$T['SystemId']]??'???');
      $txt .=  "<td>Direct<td>";
      $txt .=  "<td>" . ($T['NewSystemId'] == 0? "" : $Systems[$T['NewSystemId']]);
      $txt .=  "<td>"; //GSO to be set
    } else if ($Prop2 & THING_AT_LINK) {
      $L = Get_Link($T['Dist1']);
      $txt .=  "<td>" . (empty($Systems[$T['SystemId']]) ?'': $Systems[$T['SystemId']]) . "<td>S<td><td>At: " . ($L['Name']??'Unknown') . "<td>";
    } else {
      $Lid = $T['LinkId'];

      $txt .=  "<td>";
      $Host = 0;
      if ($Lid >= 0 || ($MoveProps[$Lid] &1)) {
        $txt .=  (empty($Systems[$T['SystemId']]) ?'': $Systems[$T['SystemId']]);
        $txt .=  "<td>" . (($T['BuildState'] == BS_PLANNING)?'-':(is_in_space($T)?'S':'G'));
      } else if ($Lid == LINK_INBRANCH ) {
        $txt .=  (empty($Systems[$T['SystemId']]) ?'': $Systems[$T['SystemId']]) . "<td>0";
      } else if (($MoveProps[$Lid] &2)) {
        $txt .=  'On Board<td>O';
        $Host = Get_Thing($T['SystemId']);
      } else {
        if ($MoveProps[$Lid] & 4) {
          $txt .=  (empty($Systems[$T['SystemId']]) ?'': $Systems[$T['SystemId']]);
          $IS = (is_in_space($T)?'S':'G');
          $txt .= "<td>" . [0=>'?', -1=>'O', -2=>'G', -3=>'S', -4=>'G' ,-5=>$IS, -6=>$IS, -7=>$IS, -8=>'O'][$Lid];

        } else {
          $txt .=  $MoveNames[$Lid];
          $IS = (is_in_space($T)?'S':'G');
          $txt .= "<td>" . [0=>'?', -1=>'O', -2=>'G', -3=>'S', -4=>'G' ,-5=>$IS, -6=>$IS, -7=>$IS, -8=>'O'][$Lid];
        }
      }

      $txt .=  "<td>";

      if (!isset($Blockades[$T['SystemId']])) {
        $Conf = Gen_Select("SELECT W.* FROM ProjectHomes PH, Worlds W WHERE PH.SystemId=" . $T['SystemId'] . " AND W.Home=PH.id AND W.Blockade>0");
        $Blockades[$T['SystemId']] = ($Conf?$Conf[0]['Blockade']:0);
      }
//      var_dump($T['SystemId'],$Blockades[$T['SystemId']]);
      if ($T['Instruction']) $txt .=  $ThingInstrs[abs($T['Instruction'])];
      if (($T['Instruction'] == 0 || $T['Instruction'] == 5 ) && (($Props & THING_CAN_MOVE) && ( $T['BuildState'] == BS_COMPLETE))) {
        if ( ($T['LinkId'] >=0 ) || (($MoveProps[$T['LinkId']] & 1) && ($T['CurHealth'] > 0 || ($Props & THING_HAS_HEALTH) ==0))) {
          if ($MovesValid && (($Faction['TurnState']??0) == 1) && (is_in_space($T) || $Blockades[$T['SystemId']] <= max($T['Speed'],1))) {
            $txt .=  ($Mode?'Move':" <a href=PMoveThing.php?id=" . $T['id'] . ">" . (($T['LinkId'] >=0 )?'Move':$MoveNames[$T['LinkId']]) . "</a>");
          } else {
          }
          if ($T['Retreat']) {
            $txt .=  "<td>" . (($T['LastMoved'] <0)?'Retreated':'Retreating');
          } elseif ($T['LinkId'] > 0) {
            $L = Get_Link($T['LinkId']);
            $txt .=  "<td style=color:" . $LinkTypes[abs($L['Level'])]['Colour'] . " >Link " . ($L['Name']?$L['Name']: "#" . $T['LinkId']);
            if ($L['Level'] <0 ) $txt .=  "- Note under repair...";
            if ($T['NewSystemId']>0 && $T['TargetKnown']) {
              $txt .=  "<td>" . $Systems[$T['NewSystemId']];
            } else if (Has_Tech($T['Whose'],'Know All Links') || Feature('AllwaysShowLinkEnds')) {
              $txt .=  "<td>" . $Systems[$T['NewSystemId']];
            } else {
              $txt .=  "<td> ? ";
            }
          } elseif ($T['LinkId'] == LINK_FOLLOW ) {
            $FT = Get_Thing($T['NewSystemId']);
            if ($FT) {
              $link = ((($FT['Whose']==$T['Whose']) || $GM)?"<a href=ThingEdit.php?id=" .$T['NewSystemId'] . ">":'');
              $txt .=  "<td>$link" . $FT['Name'] . "</a><td>";
            } else {
              $txt .=  "<td>Confused...<td>";
            }
          } else if ($T['LinkId'] == LINK_NOT_MOVING) {
            $txt .=  "<td>Not Moving<td>";
          } else {
            $txt .=  "<td>";
            if ($Host) {
              if ($Host['Whose']==$T['Whose']) {
                $txt .= "<a href=ThingEdit.php?id=" . $Host['id'] . ">" . $Host['Name'] . "</a>";
              } else {
                $txt .= $Host['Name'];
              }
            }
            $txt .=  "<td>";
          }
        } else if ($T['LinkId'] == LINK_NOT_MOVING) {
          $txt .=  "<td>Not Moving<td>";
        } else {
          $txt .=  "<td>";
          if ($T['LinkId'] < 0) {
            if ($Host) {
              if ($Host['Whose']==$T['Whose']) {
                $txt .= "<a href=ThingEdit.php?id=" . $Host['id'] . ">" . $Host['Name'] . "</a>";
              } else {
                $txt .= $Host['Name'];
              }
            }
          }
          $txt .=  "<td>";
        }
      } else {
        if ($GM && ($Props & THING_HAS_HEALTH) && ($Props & THING_CAN_BE_SPLATED) && ($T['CurHealth']>0)) {
          $txt .=  "<a href=PThingList.php?ACTION=SPLAT&T=$Tid&F=$Fid>Splat</a>";
        }
        $txt .=  "<td>";
        if ($Host) {
          if ($Host['Whose']==$T['Whose']) {
            $txt .= "<a href=ThingEdit.php?id=" . $Host['id'] . ">" . $Host['Name'] . "</a>";
          } else {
            $txt .= $Host['Name'];
          }
        } else if ($Lid == LINK_INBRANCH) {
          $txt .=  "Not Deployed";
        }
        $txt .=  "<td>";
      }
    }
    $Up = 0;
    if ($Fid) {
      $Modules = Get_Modules($Tid);
      foreach ($Modules as $M) {
        $Mt = ($ModTypes[$M['Type']]??0);
        if ((($Mt['Leveled']??0) & 1) == 0) continue;
        if ($M['Level'] < $Mt['Target']) {
          $Up += $M['Number'];
        }
      }

      if (($T['CurHealth'] < $T['OrigHealth']) && ($Props & THING_HAS_HEALTH) && (($Props & THING_CAN_BE_SPLATED) == 0)) $Up++;
      if ($RowClass == 'Prisoner'){
        $txt .=  "<td><span " . FactColours($T['Whose']) . ">[" .
           ($Factions[$T['Whose']]['Name']??'Unknown') . "]</span>";
      } else {
        $txt .=  "<td>" . ($Up?$Up:'');
      }
    }
    if (!$GM) $txt .=  "<td>" . $T['Priority'];

    if ($GM) {
      $txt .=  "<td>" . (($T['Sensors'] ? ($T['Sensors'] . '*L' . $T['SensorLevel']) : ''));
      if ($T['NebSensors'])  $txt .=  ' N';

      $txt .=  "<td>";
      if ($T['GM_Notes']) $txt .=  "<b>N</b> ";
      if ($T['HiddenControl']) $txt .=  "<span " . FactColours($T['HiddenControl']) . ">" .
        substr($Factions[$T['HiddenControl']]['Name'],0,3) . "</span> ";
    }
 //   $txt .=  "<td>" . (isset($Systems[$T['NewSystemId']]) ? $Systems[$T['NewSystemId']] :"") ;

  }
  if (Access('God'))  $txt .=  "</tbody><tfoot><tr><td class=NotSide>Debug<td colspan=15 class=NotSide><textarea id=Debug></textarea>";
  $txt .=  "</table></div>\n";

  $txt .=  "<script type=text/javascript>$(document).ready(function(){ ListThingSetup($Fid," . ($GM?1:0) . "," .
       ($GM?($Faction['GMThingType']??0):$Faction['ThingType']) . "," .
       ($GM?($Faction['GMThingBuild']??0):$Faction['ThingBuild']) . ");});</script>\n";

  if ($Fid) {
    $txt .=  "<h1>Logistics</h1>";

    $LogAvail = LogisticalSupport($Fid);

    $LogCats = ['Ships',$ARMIES,'Agents'];

    $txt .=  "<table border>";
    $txt .=  "<tr><td>Category<td>Logistical Support<td>Logistics needed<td>Logistics Penalty\n";
    foreach ($LogCats as $i => $n) {
      if ($Logistics[$i]) {
        $pen = min(0,$LogAvail[$i]-$Logistics[$i]);
        $txt .=  "<tr><td>$n<td>" . $LogAvail[$i] . "<td>" . $Logistics[$i] . "<td " . ($pen < 0? " class=Err":'') . ">$pen" ;
      }
    }
    $txt .=  "</table><p>\n";
  }

  return $txt;
}

