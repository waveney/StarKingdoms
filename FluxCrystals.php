<?php
include_once("sk.php");
include_once("ThingLib.php");
global $GAME,$FACTION,$GAMEID;

// START HERE
A_Check('Player');
$Fid = $FACTION['id'];

dostaffhead("Use Flux Crystals");
echo "<h1>Use Flux Crystals, <a href=ScienceLog.php>Resource Logs</a></h1>";

if (isset($_REQUEST['ACTION'])) {
  switch ($_REQUEST['ACTION']) {
    case 'Del':
      $Cid = $_REQUEST['C'];
      db_delete('FluxCrystalUse',$Cid);
      break;
    case 'USE':
      if ($FACTION['Currency1'] == 0) {
        echo "<h2>You dont have any Flux Crystals left</h2>";
        break;
      }
      $FACTION['Currency1']--;
      Put_Faction($FACTION);
      $Lid = $_REQUEST['L']??0;

      $L = Get_Link($Lid);
      if (($L['FluxCrystals']++) == 0) {
        $L['ThisTurnMod']--;
      }
      Put_Link($L);
      $EInst = $L['Instability'];
      if ($L['ThisTurnMod']) $EInst = max(1,$EInst+$L['ThisTurnMod']);
      echo "<h2>Link " . $L['Name'] . " has its current instablity set to $EInst</h2>";

//      $FCU = ['GameId'=>$GAMEID,'Turn'=>$GAME['Turn'],'LinkId'=>$Lid,'FactionId'=>$Fid];
//      Gen_Put('FluxCrystalUse',$FCU);
      break;
    default:
      break;
  }
}


echo "Currently have: " . $FACTION['Currency1'] . " Crystals.<p>";
// Number of flux crystals

$Cuse = Gen_Get_Cond('FluxCrystalUse',"FactionId=$Fid AND Turn=" . $GAME['Turn']);
if ($Cuse) {
  $Used = 0;
  echo "<h2>Currently planned use</h2>\n";

  TableStart();
  TableHead('Link');
  TableHead('Current Instability','N');
  TableHead('Actions');
  TableTop();

  foreach($Cuse as $Cid=>$C) {
    $Used++;
    $Lid = $C['LinkId'];
    $L = Get_Link($Lid);
    if (!$L) {
      echo "<h2 class=Err>Fault in Flux Crystal record $Cid - tell Richard</h2>";
      GMLog4Later("Fault in Flux Crystal record $Cid");
      continue;
    }

    $EInst = $L['Instability'];
    if ($L['ThisTurnMod']) $EInst = max(1,$EInst+$L['ThisTurnMod']);

    echo "<tr><td>" . $L['Name'] . "<td>$EInst<td><a href=FluxCrystals.php?ACTION=Del&C=$Cid>Delete</a>";
  }
  TableEnd();
}

// echo "Using $Used Flux Crystals.  ";
// if ($Used > $FACTION['Currency1']) echo "<span class=err>This is more than you have - results will be random...</span>";
// Current list of use

// New Use

// Find all ships & worlds - get systems - list links in each system

$Sys = [];
$Worlds = Get_Worlds($Fid);
foreach ($Worlds as $W) {
  $Home = Get_ProjectHome($W['Home']);
  if ($Home['SystemId']) $Sys[$Home['SystemId']] = 1;
}
$Tings = Get_Things($Fid);
foreach ($Tings as $T) {
  if ($T['LinkId']>=0 && $T['SystemId']) $Sys[$T['SystemId']] = 1;
}

$LinkList = [];

foreach(array_keys($Sys) as $Sid) {
  $N = Get_System($Sid);
  $Ref = $N['Ref'];
  $Ls = Get_Links($Ref);
  foreach ($Ls as $Lid=>$L) {
    if ($L['FluxCrystals']) continue;
    $EInst = $L['Instability'] + $L['ThisTurnMod'] ;
    if ($EInst < 1) continue;  // Link can't be improved
    $LinkKnow = LinkVis($Fid,$L['id'],$Sid);
    if ($LinkKnow) {
      $LinkList[$Ref][$Lid] = $L['Name'];
    }
  }
}

ksort($LinkList);

echo "<p><h2>Select a Wormhole to improve stability next turn</h2><form method=post action=FluxCrystals.php>" .
  "<table border><th>System<th>Wormholes that could have their instability reduced by one next turn";
foreach ($LinkList as $Ref=>$Links) {
  echo "<tr><td>$Ref<td>";
  foreach ($Links as $Lid=>$Name) {
    echo "<button type=submit formaction='FluxCrystals.php?ACTION=USE&L=$Lid' >$Name</button> ";
  }
}
echo "</table>";
dotail();

