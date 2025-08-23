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
  global $Fid,$Tid,$GAMEID,$GAME;
  $N = Get_SystemR($SysR);
  $Sid = $N['id'];
  $FS = Get_FactionSystemFS($Fid,$Sid);
  $TFS = Get_FactionSystemFS($Tid,$Sid);
  $TFS['ScanLevel'] = max($FS['ScanLevel'],$TFS['ScanLevel']);
  if (isset($_REQUEST['SURV'])) {
    if (empty($TFS['Name'])) $TFS['Name'] = $FS['Name'];
    if (($TFS['SpaceScan'] < $FS['SpaceScan']) || (($TFS['SpaceScan'] == $FS['SpaceScan']) && ($TFS['SpaceTurn'] < $TFS['SpaceTurn']))) {
      $TFS['SpaceSurvey'] = $FS['SpaceSurvey'];
      $TFS['SpaceScan'] = $FS['SpaceScan'];
      $TFS['SpaceTurn'] = $FS['SpaceTurn']??$GAME['Turn'];
    }
    if (($TFS['PlanetScan'] < $FS['PlanetScan']) || (($TFS['PlanetScan'] == $FS['PlanetScan']) && ($TFS['PlanetTurn'] < $TFS['PlanetTurn']))) {
      $TFS['PlanetSurvey'] = $FS['PlanetSurvey'];
      $TFS['PlanetScan'] = $FS['PlanetScan'];
      $TFS['PlanetTurn'] = $FS['PlanetTurn']??$GAME['Turn'];
    }

    $Anoms = Gen_Get_Cond('Anomalies',"GameId=$GAMEID AND SystemId=$Sid");

    if ($Anoms) {
      foreach($Anoms as $Aid=>$A) {
        $FA = Gen_Get_Cond1('FactionAnomaly',"AnomalyId=$Aid AND FactionId=$Fid");
        $FTA = Gen_Get_Cond1('FactionAnomaly',"AnomalyId=$Aid AND FactionId=$Tid");
        if (isset($FA['id'])&& !isset($FTA['id']) && ($FA['State']>1)) {
          $FTA = ['State' => 1, 'FactionId'=>$Tid, 'AnomalyId'=>$Aid, 'Progress'=>0];
          Gen_Put('FactionAnomaly',$FTA);
        }
      }
    }
  }
  Put_FactionSystem($TFS);

  $Links = Get_Links($SysR);
  foreach ($Links as $Lid=>$L) {
    $FLK = Gen_Get_Cond1('FactionLinkKnown',"FactionId=$Fid AND LinkId=$Lid");
    if ($FLK['USED']??0) {
      $FLK = Gen_Get_Cond1('FactionLinkKnown',"FactionId=$Tid AND LinkId=$Lid");
      if ($FLK['id']??0) {
        $FLK['Used'] = 1;
        Gen_Put('FactionLinkKnown',$FLK);
      } else {
        $FLK = ['LinkId'=>$Lid, 'FactionId'=>$Tid, 'Used'=>1 ];
        Gen_Put('FactionLinkKnown',$FLK);
      }
    }
  }
  $LogE = ['FromFact'=>$Fid,'DestFact'=>$Tid,'SystemId'=>$Sid,'Survey'=>(isset($_REQUEST['SURV'])?1:0),'XferWhen'=>time(),'Turn'=>$GAME['Turn']];
  Gen_Put("TransferLog",$LogE);
}

global $Fid,$Tid;
$Factions = Get_Factions();

if (isset($_REQUEST['ACTION'])) {
  switch ($_REQUEST['ACTION']) {
    case 'Transfer':
      $SysR = $_REQUEST['R'];
      $Tid = $_REQUEST['T']??0;
      if ($_REQUEST['F'] != $Fid) {
        echo "<h2 class=Err>Something has gone wrong tell Richard - Code MT1 </h2>";
        break;
      }

      if (!$Tid) {
        echo "No destination Faction selected.<p>";
        break;
      }

      $ActPass = (isset($_REQUEST['SURV'])?'Active':'Passive');
      if ($SysR == 'ALLLL') {
        echo "Doing All<p>";
        foreach($WRefs as $Ref) {
          TransferSys($Ref);
          echo "Transferred $ActPass knowledge of system $Ref to " . $Factions[$Tid]['Name'] . "<br>";
        }
        echo "All done<p>";
      } else {
        TransferSys($SysR);
        echo "Transferred $ActPass knowledge of system $SysR to " . $Factions[$Tid]['Name'] . "<br>";
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



echo "<h1>Transfer Mapping Knowledge, <a href=MapTransferLogs.php>Map Transfer Logs</a></h1>";
echo "You can transfer for one system or all systems.  Note there is no undo to this.<p>";
echo "<form method=post>" . fm_hidden('F',$Fid);
if ($GM) {
  echo "From: " . $F['Name'] . "<p>";
}
echo "To: " . fm_select($FactList,$_REQUEST,'T') . "<p>";

echo "Include results of Space/Planetary Surveys " . fm_checkbox('',$_REQUEST,'SURV') . " (Includes known anomalies traits, and mineral ratings)";
echo "<h2>Select the System</h2>";
foreach ($WRefs as $Wi=>$Ref) {
  echo "<button class=projtype type=submit formaction='MapTransfer.php?ACTION=Transfer&R=$Ref'>$Ref</button> \n";
}

echo "<p> <h2>OR</h2><p><button class=projtype type=submit formaction='MapTransfer.php?ACTION=Transfer&R=ALLLL'>ALL</button>\n";


dotail();







