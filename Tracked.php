<?php
include_once("sk.php");
global $FACTION,$GAMEID,$USER,$GAME,$Fields;
include_once("GetPut.php");
include_once("PlayerLib.php");
include_once("ThingLib.php");

$TrackScale1 = [1=>['None','White'],5=>['Minimal','Green'],10=>['Low','Yellow'],20=>['Moderate','Orange'],30=>['High','Pink'],100000000=>['Extreeme','Red']];

A_Check('Player');

$GM = Access('GM');

dostaffhead("Tracked Resources");
if (isset($FACTION)) {
  $Fid = $FACTION['id'];
  if ( ($FACTION['TurnState'] == 3) && !$GM) Player_Page();
} else if ($GM) {
  if (isset($_REQUEST['id'])) {
    $Fid = $_REQUEST['id'];
  } else {
    $Fid = 0;
    echo "<h2>Note you are here without a faction...</h2>\n";
  }
}

if (isset($_REQUEST['FORCE'])) $GM = 0;
// var_dump($_REQUEST);

if (isset($_REQUEST['ACTION'])) {
  switch ($_REQUEST['ACTION']) {
    case 'Add':
      $Res = ['Type'=>$_REQUEST['Type'], 'Whose'=>$Fid, 'Value'=>0];
      Gen_Put('Resources',$Res);
      break;
    case 'Remove':
      $Ti = $_REQUEST['Ti'];
      db_delete('Resources',$Ti);
      break;
  }
}

$Tracks = Gen_Get_Cond('Resources',"Whose=$Fid");
$ResTypes = Gen_Get_All('ResourceTypes');
$NewResNames = NamesList($ResTypes);
//var_dump($ResTypes);

echo "<table border>";
if ($GM) {
  echo "<h2><a href= Tracked.php?FORCE>This Page in Player Mode</a></h2>";
  echo "Note: Changes to these here are not yet logged - use Pay Faction for logged changes<p>";

  echo "<form method=post>";
  Register_AutoUpdate('Generic',0);
  echo "<input type=submit hidden name=Ignore>";
  echo "<tr><th>Track<th>Value<th colspan=2>Actions";
  $Ign = [];
  echo "<tr>" . fm_number('Credits',$FACTION,'Credits','','',"Factions:Credits:$Fid") .
    fm_number1('',$Ign,"Ignore:Factions:Credits:$Fid") . "<button type=button onclick=AddTrack('Factions:Credits:$Fid')>Add</button>";
  echo "<tr>" . fm_number('Physics Points', $FACTION,'PhysicsSP','','',"Factions:PhysicsSP:$Fid").
    fm_number1('',$Ign,"Ignore:Factions:PhysicsSP:$Fid") . "<button type=button onclick=AddTrack('Factions:PhysicsSP:$Fid')>Add</button>";
  echo "<tr>" . fm_number('Engineering Points', $FACTION,'EngineeringSP','','',"Factions:EngineeringSP:$Fid") .
    fm_number1('',$Ign,"Ignore:Factions:EngineeringSP:$Fid") . "<button type=button onclick=AddTrack('Factions:EngineeringSP:$Fid')>Add</button>";
  echo "<tr>" . fm_number('Xenology Points', $FACTION,'XenologySP','','',"Factions:XenologySP:$Fid") .
    fm_number1('',$Ign,"Ignore:Factions:XenologySP:$Fid") . "<button type=button onclick=AddTrack('Factions:XenologySP:$Fid')>Add</button>";
  if ($Nam = GameFeature('Currency1')) echo "<tr>" . fm_number($Nam, $FACTION,'Currency1','','',"Factions:Currency1:$Fid") .
    fm_number1('',$Ign,"Ignore:Factions:Currency1:$Fid") . "<button type=button onclick=AddTrack('Factions:Currency1:$Fid')>Add</button>";
  if ($Nam = GameFeature('Currency2')) echo "<tr>" . fm_number($Nam, $FACTION,'Currency2','','',"Factions:Currency2:$Fid").
    fm_number1('',$Ign,"Ignore:Factions:Currency2:$Fid") . "<button type=button onclick=AddTrack('Factions:Currency2:$Fid')>Add</button>";
  if ($Nam = GameFeature('Currency3')) echo "<tr>" . fm_number($Nam, $FACTION,'Currency3','','',"Factions:Currency3:$Fid").
    fm_number1('',$Ign,"Ignore:Factions:Currency3:$Fid") . "<button type=button onclick=AddTrack('Factions:Currency3:$Fid')>Add</button>";

  foreach ($Tracks as $ti=>$Tr) {
    echo "<tr>" . fm_number(($ResTypes[$Tr['Type']]['Name']??'Unknown'),$Tr,'Value','','',"Resources:Value:$ti") .
      fm_number1('',$Ign,"Ignore:Resources:Value:$ti") . "<button type=button onclick=AddTrack('Resources:Value:$ti')>Add</button>" .
      fm_submit('ACTION','Remove',1,"formaction=Tracked.php?Ti=$ti");
      unset($NewResNames[$Tr['Type']]);
  }

//  var_dump($NewResNames);
  $es = [];
  Cancel_AutoUpdate();
  if ($NewResNames) {
    echo "<tr><td>New Track";
    echo "<td colspan=2>" . fm_select($NewResNames,$es,'Type',1) . fm_submit('ACTION','Add');
  }
  if (Access('God')) echo "<tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea>";


} else { // Player
  echo "<tr><td>Credits:<td>" . $FACTION['Credits'];
  echo "<tr><td>Physics Points<td>" . $FACTION['PhysicsSP'];
  echo "<tr><td>Engineering Points<td>" . $FACTION['EngineeringSP'];
  echo "<tr><td>Xenology Points<td>" . $FACTION['XenologySP'];
  if (($Nam = GameFeature('Currency1')) && ($FACTION['Currency1'])) echo "<tr><td>$Nam:<td>" . $FACTION['Currency1'];
  if (($Nam = GameFeature('Currency2')) && ($FACTION['Currency2'])) echo "<tr><td>$Nam:<td>" . $FACTION['Currency2'];
  if (($Nam = GameFeature('Currency3')) && ($FACTION['Currency3'])) echo "<tr><td>$Nam:<td>" . $FACTION['Currency3'];
  foreach ($Tracks as $ti=>$Tr) {
    echo "<tr><td>" . ($ResTypes[$Tr['Type']]['Name']??'Unknown');

    switch (($ResTypes[$Tr['Type']]['Props']&15)) { // lower 4 bits = track type
      case 1:
        $Colour = 'White';
        $Txt = 'None';
        foreach ($TrackScale1 as $Lim=>$Data) {
          if ($Tr['Value'] <= $Lim) {
            [$Txt,$Colour] = $Data;
            break;
          }
        }
        echo "<td style='background:$Colour'>$Txt";
        break;

      default:
        echo  "<td>" . $Tr['Value'];
    }
  }
}
echo "</table>";

dotail();
