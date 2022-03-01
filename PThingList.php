<?php
  include_once("sk.php");
  global $FACTION,$GAMEID,$USER;
  include_once("GetPut.php");
  include_once("PlayerLib.php");
  include_once("ThingLib.php");
  
global $ModuleCats,$ModFormulaes,$ModValues,$Fields,$Tech_Cats,$CivMil,$BuildState,$ThingInstrs,$ThingInclrs;

//var_dump($_COOKIE,$_REQUEST);
  if (Access('Player')) {
    if (!$FACTION) {
      if (!Access('GM') ) Error_Page("Sorry you need to be a GM or a Player to access this");
    } else {
      $Fid = $FACTION['id'];
      $Faction = &$FACTION;
    }
  } 
  if (Access('GM') ) {
    A_Check('GM');
    if (isset( $_REQUEST['F'])) {
      $Fid = $_REQUEST['F'];
    } else if (isset( $_REQUEST['f'])) {
      $Fid = $_REQUEST['f'];
    } else if (isset( $_REQUEST['id'])) {
      $Fid = $_REQUEST['id'];
    }
    if (isset($Fid)) $Faction = Get_Faction($Fid);
  }

  dostaffhead("Things",["js/ProjectTools.js"]);
  
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
      $FL = Get_FactionLink($Fid,$Lid);
      $FarNeb = $FSN['Nebulae'];
      $FS = Get_FactionSystemFS($Fid,$FSN['id']);

      if (isset($FL['known']) && $FL['known']) {
      } else if ($NearNeb == 0) {
          if (isset($FS['id'])) {
            if ($FarNeb != 0 && $FS['NebScanned'] < $FarWeb) {
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
      Put_Thing($T);
      if ($L['Level']>1 && ($Who = GameFeature('LinkOwner',0)) && $Who != $Fid) {
        $LinkTypes = Get_LinkLevels();
        $LOwner = Get_Faction($Who);
        echo "<h2>You are taking a <span style='color:" . $LinkTypes[$L['Level']]['Colour'] . "'>" . $LinkTypes[$L['Level']]['Colour'] .
             "</span> link do you need to pay " . $LOwner['Name'] . " for this?</h2>\n";
      }
      break;
    }
  }
  
/* Select types 
  Name, Class, What, sub cat, where, move, Level, Action
  */
  
  $Things = Get_Things($Fid);
  $ThingTypes = Get_ThingTypes();
  $Systems = Get_SystemRefs();

  echo "<h1>Things</h1>";
  echo "To see more information about each thing and to do movement and changes click on the name<p>\n";
  echo "Click on column heading to sort by column - toggles up/down<br>\n";
  echo "Buttons to selectively list only one type will follow at some point<br>\n";
  
  $coln = 0;
  echo "<div class=tablecont><table id=indextable border width=100% style='min-width:1400px'>\n";
  echo "<thead><tr>";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Name</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Class</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Type</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Level</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Orders</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Health</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>State</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Where</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Actions</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Using Link</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Moving to</a>\n";

  echo "</thead><tbody>";
  
  $Logistics = [0,0,0]; // Ship, Army, Intelligence
  $LinkTypes = Get_LinkLevels();
  
  foreach ($Things as $T) {
    if (empty($T['Type'])) continue;
    $Props = $ThingTypes[$T['Type']]['Properties'];
    
    $Tid = $T['id'];
    $Name = $ThingTypes[$T['Type']]['Name'];
    if (!$Name) $Name = "Unknown Thing $Tid";
    
    echo "<tr><td><a href=" . ($T['BuildState']? "ThingEdit.php" : "ThingPlan.php") . "?id=$Tid>" . ($T['Name'] ? $T['Name'] : "Nameless" ) . "</a>";
    echo "<td>" . $T['Class'];
    echo "<td>" . $Name;
    echo "<td>" . $T['Level'];
    echo "<td>" . $T['Orders'];
    echo "<td><center>" . $T['CurHealth'] . ' / ' . $T['OrigHealth'];
    echo "<td>" . $BuildState[$T['BuildState']];
    echo "<td>" . (empty($T['SystemId']) ?'': $Systems[$T['SystemId']]);
    if ($T['Instruction']) {
      echo "<td>" . $ThingInstrs[$T['Instruction']] . "<td><td>";
    } else if (($Props & THING_CAN_MOVE) && ( $T['BuildState'] == 3)) {
      echo "<td><a href=PMoveThing.php?id=" . $T['id'] . ">Move</a>";
      if ($T['LinkId']) {
        $L = Get_Link($T['LinkId']);
        echo "<td style=color:" . $LinkTypes[$L['Level']]['Colour'] . " >Link #" . $T['LinkId'];
        if ($T['NewSystemId'] && $T['TargetKnown']) {
          echo "<td>" . $Systems[$T['NewSystemId']];
        } else {
          echo "<td> ? ";
        }
      } else {
        echo "<td><td>";
      }
    } else {
      echo "<td><td><td>";
    }
 //   echo "<td>" . (isset($Systems[$T['NewSystemId']]) ? $Systems[$T['NewSystemId']] :"") ;
    
    if ($T['BuildState'] == 2 || $T['BuildState'] == 3) {
      if ($Props & THING_CAN_BETRANSPORTED) $Logistics[1] += $T['Level'];
      if ($Props & THING_HAS_GADGETS) $Logistics[2] += $T['Level'];
      if ($Props & ( THING_HAS_MILSHIPMODS | THING_HAS_CIVSHIPMODS)) $Logistics[0] += $T['Level'];
    };
    
  }
  
  echo "</table></div>\n";
  echo "<h1>Logistics</h1>";
  
  $LogAvail = LogisticalSupport($Fid);
  
  $LogCats = ['Ships','Armies','Agents'];
  
  echo "<table border>";
  echo "<tr><td>Category<td>Logistical Support<td>Logistics needed<td>Logistics Penalty\n";
  foreach ($LogCats as $i => $n) {
    if ($Logistics[$i]) {
      $pen = min(0,$LogAvail[$i]-$Logistics[$i]);
      echo "<tr><td>$n<td>" . $LogAvail[$i] . "<td>" . $Logistics[$i] . "<td " . ($pen < 0? " class=Err":'') . ">$pen" ;
    }
  }
  echo "</table><p>\n";

  echo "<h2><a href=ThingPlan.php?F=$Fid>Plan a new thing</a></h2>\n";

  dotail();  
?>
