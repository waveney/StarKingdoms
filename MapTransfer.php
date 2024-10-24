<?php
include_once("sk.php");
include_once("SystemLib.php");
include_once("PlayerLib.php");
global $FACTION,$GAME;

dostaffhead('Transfer mapping knowledge');

$Fid = 0;
//var_dump($_COOKIE,$_REQUEST);
A_Check('Player');
if (Access('Player')) {
  if (!$FACTION) {
    if (!Access('GM') ) Error_Page("Sorry you need to be a GM or a Player to access this");
  } else {
    $Fid = $FACTION['id'];
    $F = $FACTION;
  }
}
$GM = (Access('GM') && ! isset($_REQUEST['FORCE'])) ;
if ($GM) {
  $Fid = ($FACTION['id']??0);
  $F = $FACTION;
  }
  if (!$Fid) {
    echo "No faction selected<p>";
    dotail();
}

$SRefs = Get_SystemRefs();
$FSs = Gen_Get_Cond('FactionSystem', "FactionId=$Fid");
$WRefs = [];
foreach ($FSs as $FS) {
  $WRefs[$FS['SystemId']] = $SRefs[$FS['SystemId']];
}
asort($WRefs);

function TransferSys($SysR) {
  global $Fid,$Tid;
  $N = Get_SystemR($SysR);
  $FS = Get_FactionSystemFS($Fid,$N['id']);
  $TFS = Get_FactionSystemFS($Tid,$N['id']);
  $TFS['ScanLevel'] = max($FS['ScanLevel'],$TFS['ScanLevel']);
  Put_FactionSystem($TFS);

  $Links = Get_Links($SysR);
  foreach ($Links as $Lid=>$L) {
    $FL = Get_FactionLinkFL($Fid,$Lid);
    if ($FL['Known']) {
      $FL = Get_FactionLinkFL($Tid,$Lid);
      if (!$FL['Known']) {
        $FL['Known'] = 1;
        Put_FactionLink($FL);
      }
    }
  }
}

global $Fid,$Tid;
$Factions = Get_Factions();

if (isset($_REQUEST['ACTION'])) {
  switch ($_REQUEST['ACTION']) {
    case 'Transfer':
      $SysR = $_REQUEST['R'];
      $Tid = $_REQUEST['T'];

      if ($SysR == 'ALLLL') {
        echo "Doing All<p>";
        foreach($WRefs as $Ref) {
          TransferSys($Ref);
          echo "Transfered $Ref to " . $Factions[$Tid]['Name'] . "<br>";
        }
        echo "All done<p>";
      } else {
        TransferSys($SysR);
        echo "Transfered knowledge of system $SysR to " . $Factions[$Tid]['Name'] . "<br>";
      }
    default:

  }
}

//  START HERE

$Factions = Get_Factions();
$Facts = Get_FactionFactions($Fid);

$FactList = [0=>''];
foreach ($Facts as $Fi=>$Fa) {
  $FactList[$Fi] = $Factions[$Fi]['Name'];
}



echo "<h1>Transfer Mapping Knowledge</h1>";
echo "You can transfer for one system or all systems.  Note there is no undo to this.<p>";
echo "<form method=post>" . fm_hidden('F',$Fid);
if ($GM) {
  echo "From: " . $F['Name'] . "<p>";
}
echo "To: " . fm_select($FactList,$_REQUEST,'T') . "<p>";

echo "<h2>Select the System</h2>";
foreach ($WRefs as $Wi=>$Ref) {
  echo "<button class=projtype type=submit formaction='MapTransfer.php?ACTION=Transfer&R=$Ref'>$Ref</button> \n";
}

echo "<p> <h2>OR</h2><p><button class=projtype type=submit formaction='MapTransfer.php?ACTION=Transfer&R=ALLLL'>ALL</button>\n";


dotail();







