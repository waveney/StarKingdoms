<?php
  include_once("sk.php");
  global $FACTION,$GAMEID,$USER;
  include_once("GetPut.php");
  include_once("PlayerLib.php");
  include_once("ThingLib.php");

  global $ModFormulaes,$ModValues,$Fields,$Tech_Cats,$CivMil,$BuildState,$ThingInstrs,$ThingInclrs,$GAMEID,$LogistCost,$ARMY,$ARMIES,$GAME;

  $Fid = 0;
//var_dump($_COOKIE,$_REQUEST);
  A_Check('Player');
  if (Access('Player')) {
    if (!$FACTION) {
      if (!Access('GM') ) Error_Page("Sorry you need to be a GM or a Player to access this");
    } else {
      $Fid = $FACTION['id'];
      $Faction = &$FACTION;
    }
  }
  $GM = (Access('GM') && ! isset($_REQUEST['FORCE'])) ;
  if ($GM) {
    if (isset( $_REQUEST['F'])) {
      $Fid = $_REQUEST['F'];
    } else if (isset( $_REQUEST['f'])) {
      $Fid = $_REQUEST['f'];
    } else if (isset( $_REQUEST['id'])) {
      $Fid = $_REQUEST['id'];
    }
    if (isset($Fid)) $Faction = Get_Faction($Fid);
  } else if (!($Fid ?? 0)) {
    $Fid = $_REQUEST['id'] ?? $_REQUEST['F'] ?? $_REQUEST['f'] ?? 0;
    if (isset($Fid)) $Faction = Get_Faction($Fid);
  }

  if ($GM && isset($_REQUEST['FORCE'])) $GM = 0;

  dostaffhead("Things",["js/ProjectTools.js"],
             " onload=ListThingSetup(" . ($FACTION['id']??0) . ",$GM," .
             ($GM?($FACTION['GMThingType']??0):($Faction['ThingType']??0)) . "," .
             ($GM?($Faction['GMThingBuild']??0):($Faction['ThingBuild']??0)) . ")" );

  if ($GM && $Fid) {
    echo "<h2><a href=PThingList.php?FORCE>This page in Player Mode</a></h2>";
  }

  if ($GM && isset($Fid) && $Fid==0) {
  } else {
    CheckFaction('PThingList',$Fid);
  }

  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
    case 'MOVE':
      $Tid = $_REQUEST['T'];
      $Lid = $_REQUEST['L'];
      $T = Get_Thing($Tid);
      $N = Get_System($T['SystemId']);
      $NS = Get_FactionSystemFS($Fid,$T['SystemId']);

      $L = Get_Link($Lid);
      $SYS1 = Get_SystemR($L['System1Ref']);
      $SYS2 = Get_SystemR($L['System2Ref']);
      if ($T['SystemId'] == $SYS1['id']) {
        $T['NewSystemId'] = $SYS2['id'];
        $FSN = $SYS2;
      } else {
        $T['NewSystemId'] = $SYS1['id'];
        $FSN = $SYS1;
      }
      $T['NewLocation'] = 1;

      $NearNeb = $N['Nebulae'];

      $Known = 1;
      // Handle link knowledge - KLUDGE
      $FL = Get_FactionLinkFL($Fid,$Lid);
      $FarNeb = $FSN['Nebulae'];
      $FS = Get_FactionSystemFS($Fid,$FSN['id']);

      if (isset($FL['Known']) && $FL['Known']) {
      } else if ($NearNeb == 0) {
          if (isset($FS['id'])) {
            if ($FarNeb != 0 && $FS['ScanLevel'] < 0) {
              $Known = 0;
            }
          } else {
              $Known = 0;
          }
      } else if ($NS['NebScanned'] >= $NearNeb) { // In a Neb...
          if (!isset($FS['id'])) {
              $Known = 0;
          }
      } else {
        $Known = 0;
      }

      $T['TargetKnown'] = $Known;
      $T['LinkId'] = $Lid;
      $LinkTypes = Get_LinkLevels();
      $ThingProps = Thing_Type_Props();
      $tprops = $ThingProps[$T['Type']];

      $ll = ($Lid>>0 ? abs($L['Level']) : 0);
      $LOWho = GameFeature('LinkOwner',0);
// var_dump($Lid,$LinkTypes[$ll]['Cost'],$LOWho,$T['LinkPay']);
      if ($Lid>0 && ($LinkTypes[$ll]['Cost'] > 0) && $LOWho && $LOWho != $T['Whose']) {
        if ($tprops & THING_HAS_GADGETS) {
          $Lc = $LinkTypes[$ll]['AgentCost'];
        } else {
          $Lc = $LinkTypes[$ll]['Cost']*$T['Level'];
        }
        if ($T['LinkPay']==0 || $T['LinkCost'] < $Lc) {
          $LOwner = Get_Faction($LOWho);
          echo "<form method=post action=PThingList.php>";
          echo "<h2>You are taking a <span style='color:" . $LinkTypes[$ll]['Colour'] . "'>" . $LinkTypes[$ll]['Name'] .
            "</span> link do you need to pay " . credit() . "$Lc to " . $LOwner['Name'] . " for this? ";

          echo fm_hidden('LinkCost', $Lc) . fm_hidden('T',$T['id']) . fm_submit("ACTION",'Pay on Turn',0);
          echo "</h2>\n";
          echo "</form>";
        }
        $T['LinkCost'] = $Lc;
      }
      Put_Thing($T);
      break;
    case 'CANCELMOVE':
      $Tid = $_REQUEST['T'];
      $T = Get_Thing($Tid);
      $T['LinkId'] = 0;
      Put_Thing($T);
      break;
    case 'Pay on Turn':
      $Tid = $_REQUEST['T'];
      $T = Get_Thing($Tid);
      Check_MyThing($T,$Fid);
      $T['LinkCost'] = $_REQUEST['LinkCost'];
      $T['LinkPay'] = 1;
      Put_Thing($T);
      break;

    case 'SPLAT':
      $Tid = $_REQUEST['T'];
      $T = Get_Thing($Tid);
      $T['CurHealth'] = 0;
      Put_Thing($T);
      break;

    case 'FOLLOW':
      $Tid = $_REQUEST['T'];
      $T = Get_Thing($Tid);
      $Follow = $_REQUEST['ToFollow'] ?? 0;
      if ($Follow) {
        $T['LinkId'] = LINK_FOLLOW;
        $T['NewSystemId'] = $Follow;
        Put_Thing($T);
      }
      break;



    }
  }

/* Select types
  Name, Class, What, sub cat, where, move, Level, Action
  */

  $Things = Get_Things_Cond($Fid, "id>0 AND GameId=$GAMEID ORDER BY Priority DESC");
  if (!empty($FACTION['HasPrisoners'])) {
    $Held = Get_Things_Cond(0,"PrisonerOf=$Fid AND BuildState=3");
    $Things = array_merge($Things,$Held);
  }
  $ThingTypes = Get_ThingTypes();
  $Systems = Get_SystemRefs();
  $Factions = Get_Factions();
//  $Techs = Get_Techs($Fid);
  $ModTypes = Get_ModuleTypes();
  if ($Fid) {
    foreach ($ModTypes as &$Mt) {
      $Lvl = Calc_TechLevel($Fid,$Mt['id']);
      $Mt['Target'] = $Lvl;
    }
  }

  echo "<h1>Things</h1>";

  $ShowCats = ['All','Ships',$ARMIES,'','Chars', 'Other'];
  if (!empty($FACTION['HasPrisoners'])) $ShowCats[] = 'Prisoners';
  $Show['ThingShow'] = ($Faction[$GM?'GMThingType':'ThingType'] ?? 0);
  if (Feature('Shakedown')) {
    $BuildCats = ['All','Plan','Building','Shakedown','Complete','Other'];
  } else {
    $BuildCats = [0=>'All',1=>'Plan',2=>'Building',4=>'Complete',5=>'Other'];
  }
  $Build['BuildShow'] = ($Faction[$GM?'GMThingBuild':'ThingBuild'] ?? 0);

  echo "<div class=floatright ><b>" . fm_radio("Show",$ShowCats,$Show,'ThingShow',' onchange=ThingListFilter()') . "<br>";
  echo fm_radio("Build State",$BuildCats, $Build,'BuildShow',' onchange=ThingListFilter()') . "</b></div>";

  echo "To see more information about each thing and to do movement and changes click on the name<p>\n";
  echo "Click on column heading to sort by column - toggles up/down<br>\n";
  echo "Ex things only show up under state <b>Other</b><br>\n";
  if ($Fid) echo "If the Thing would benefit from refit/repair/re-equipping/reinforcing then the Refit has the number of modules (+1 if it needs repair as well)</br>";
  if ($FACTION['HasPrisoners']??0) echo "The Prisoner Tab shows Prisoners YOU have<p>\n";
  if ($GM) echo "Notes: <B>N</b> - GM Notes, Coloured start of name = hidden control<P>";
  echo "For loading/unloading of troops and characters, go to the thing<p>\n";
//  echo "Use only ONE of the filters to the right<br>\n";

  $MovesValid = 1;
  if (Has_Trait($Fid,'Star-Crossed')) {
    if (IsPrime($GAME['Turn'])) {
      $MovesValid = 0;
      echo "You are Star Crossed - No movement this turn<p>";
    }
  }

  $coln = 0;

  echo "<div class=tablecont><table class=striped id=indextable border width=100% style='min-width:1400px'>\n";
  echo "<thead><tr>";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Name</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Class</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Type</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Level</a>\n";
  if (!$Fid) echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Whose</a>\n";
  if (Feature('Orders')) echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Orders</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Health</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>State</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Where</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Actions</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Using Link / Details</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Moving to</a>\n";
  if ($Fid) echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Refit?</a>\n";
  if ($GM) echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Sensors</a>\n";
  if ($GM) echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Notes</a>\n";
  if (!$GM) echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Prio</a>\n";

  echo "</thead><tbody>";

  $LinkTypes = Get_LinkLevels();

  if ($Fid) {
    $Logistics = [0,0,0]; // Ship, Army, Intelligence
    $HasHomeLogistics = (Has_Tech($Fid,'Simplified Home Logistics') && Access('God'));
    $FactionHome = 0;
    if ($HasHomeLogistics) {
      $Faction = Get_Faction($Fid);
      $Home = $Faction['HomeWorld'];
      if ($Home) {
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

    $Tid = $T['id'];
    $Name = ($ThingTypes[$T['Type']]['Name']??'Unknown');
    if (!$Name) $Name = "Unknown Thing $Tid";

    if ($Fid && ($T['Whose'] != $Fid)) {
      $RowClass = 'Prisoner';
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

    $BuildClass = ($T['BuildState']<4 ? $T['BuildState'] : 4);

    if ($Fid) {
      $Logistics = Logistics($Fid,$Things);
    }

    echo "\n<tr class='ThingList Thing_$RowClass Thing_Build$BuildClass'>";
    echo "<td><a href=" . ($T['BuildState']? "ThingEdit.php" : "ThingPlan.php") . "?id=$Tid>" . ($T['Name'] ? $T['Name'] : "Nameless" ) . "</a>";
    echo "<td>" . $T['Class'];
    echo "<td>" . $Name;
    echo "<td>" . $T['Level'];
    if ($Fid == 0) echo "<td style='background:" . ($Factions[$T['Whose']]['MapColour']??'white') . "'>" . ($Factions[$T['Whose']]['Name']??'Unknown');
    if (0) echo "<td>" . (($RowClass == 'Prisoner') ? "<span style='background:" . ($Factions[$T['Whose']]['MapColour']??'white') . "'>[" .
                 ($Factions[$T['Whose']]['Name']??'Unknown') . "]</span>" : $T['Orders']);
    echo "<td><center>" . (($Props & THING_HAS_HEALTH)? $T['CurHealth'] . ' / ' . $T['OrigHealth'] : "-");
    if (!empty($T['PrisonerOf'])) {
      echo "<td>Prisoner";
      if (($T['Whose'] == $Fid) && (!$GM)) {
        echo "<td><td><td><td>";
        if ($Fid) echo "<td>";
        continue;
      }
    } else {
      echo "<td>" . $BuildState[$T['BuildState']];
    }
    if ($Props & THING_MOVES_DIRECTLY) {
      echo "<td>" . ($Systems[$T['SystemId']]??'???');
      echo "<td>Direct<td>";
      echo "<td>" . ($T['NewSystemId'] == 0? "" : $Systems[$T['NewSystemId']]);
    } else {
      echo "<td>" . ((($T['LinkId'] >= 0) || ($T['LinkId'] == LINK_FOLLOW )) ? (empty($Systems[$T['SystemId']]) ?'': $Systems[$T['SystemId']]) : 'On Board');
      echo "<td>";
      if ($T['Instruction']) echo $ThingInstrs[abs($T['Instruction'])];
      if (($T['Instruction'] == 0 || $T['Instruction'] == 5 ) && (($Props & THING_CAN_MOVE) && ( $T['BuildState'] == 3))) {
        if ( (($T['LinkId'] >=0 ) || ($T['LinkId'] == LINK_FOLLOW) )&& ($T['CurHealth'] > 0 || ($Props & THING_HAS_HEALTH) ==0)) {
          if ($MovesValid && (($Faction['TurnState']??0) == 1)) {
            echo " <a href=PMoveThing.php?id=" . $T['id'] . ">Move</a>";
          } else {
          }
          if ($T['Retreat']) {
            echo "<td>" . (($T['LastMoved'] <0)?'Retreated':'Retreating');
          } elseif ($T['LinkId'] > 0) {
            $L = Get_Link($T['LinkId']);
            echo "<td style=color:" . $LinkTypes[abs($L['Level'])]['Colour'] . " >Link " . ($L['Name']?$L['Name']: "#" . $T['LinkId']);
            if ($L['Level'] <0 ) echo "- Note under repair...";
            if ($T['NewSystemId'] && $T['TargetKnown'] || Has_Tech($T['Whose'],'Know All Links')) {
              echo "<td>" . $Systems[$T['NewSystemId']];
            } else {
              echo "<td> ? ";
            }
          } elseif ($T['LinkId'] == LINK_FOLLOW ) {
            echo "<td>Following<td>?";
          } else {
            echo "<td><td>";
          }
        } else {
          echo "<td>";
          if ($T['LinkId'] < 0) {
            $Host = Get_Thing($T['SystemId']);
            if ($Host) echo $Host['Name'];
          }
          echo "<td>";
        }
      } else {
        if ($GM && ($Props & THING_HAS_HEALTH) && ($Props & THING_CAN_BE_SPLATED) && ($T['CurHealth']>0)) {
          echo "<a href=PThingList.php?ACTION=SPLAT&T=$Tid&F=$Fid>SPLAT</a>";
        }
        echo "<td><td>";
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
        echo "<td><span style='background:" . ($Factions[$T['Whose']]['MapColour']??'white') . "'>[" .
           ($Factions[$T['Whose']]['Name']??'Unknown') . "]</span>";
      } else {
        echo "<td>" . ($Up?$Up:'');
      }
    }
    if (!$GM) echo "<td>" . $T['Priority'];

    if ($GM) {
      echo "<td>" . (($T['Sensors'] ? ($T['Sensors'] . '*L' . $T['SensorLevel']) : ''));
      if ($T['NebSensors'])  echo ' N';

      echo "<td>";
      if ($T['GM_Notes']) echo "<b>N</b> ";
      if ($T['HiddenControl']) echo "<span style='background:" , $Factions[$T['HiddenControl']]['MapColour'] . "'>" .
        substr($Factions[$T['HiddenControl']]['Name'],0,3) . "</span> ";
    }
 //   echo "<td>" . (isset($Systems[$T['NewSystemId']]) ? $Systems[$T['NewSystemId']] :"") ;

  }
  if (Access('God'))  echo "</tbody><tfoot><tr><td class=NotSide>Debug<td colspan=15 class=NotSide><textarea id=Debug></textarea>";
  echo "</table></div onload=ListThingSetup($Fid,$GM," . ($GM?($Faction['GMThingType']??0):$Faction['ThingType']) . "," .
       ($GM?($Faction['GMThingBuild']??0):$Faction['ThingBuild']) . ")>\n";

  if ($Fid) {
    echo "<h1>Logistics</h1>";

    $LogAvail = LogisticalSupport($Fid);

    $LogCats = ['Ships',$ARMIES,'Agents'];

    echo "<table border>";
    echo "<tr><td>Category<td>Logistical Support<td>Logistics needed<td>Logistics Penalty\n";
    foreach ($LogCats as $i => $n) {
      if ($Logistics[$i]) {
        $pen = min(0,$LogAvail[$i]-$Logistics[$i]);
        echo "<tr><td>$n<td>" . $LogAvail[$i] . "<td>" . $Logistics[$i] . "<td " . ($pen < 0? " class=Err":'') . ">$pen" ;
      }
    }
    echo "</table><p>\n";
  }

  echo "<h2><a href=ThingPlan.php?F=$Fid>Plan a new thing</a></h2>\n";

  dotail();
?>
