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
//  Register_AutoUpdate('Generic',0);
  echo "<input type=submit hidden name=Ignore>";
  echo "<tr><th>Track<th>Current Value<th colspan=4>Actions";
  $Ign = [];
  echo "<tr><td>Credits<td align=right id='Factions:Credits:$Fid'>" . $FACTION['Credits'] .
    fm_number1('Number',$Ign,"Ignore:Factions:Credits:$Fid") .
    fm_text1('Reason',$Ign,"ReasonFor:Factions:Credits:$Fid",2) .
    "<button type=button onclick=AddTrack('Factions:Credits:$Fid',0)>Add</button>";

  echo "<tr><td>Physics Science Points<td align=right id='Factions:PhysicsSP:$Fid'>" . $FACTION['PhysicsSP'] .
    fm_number1('Number',$Ign,"Ignore:Factions:PhysicsSP:$Fid") .
    fm_text1('Reason',$Ign,"ReasonFor:Factions:PhysicsSP:$Fid",2) .
    "<button type=button onclick=AddTrack('Factions:PhysicsSP:$Fid',1)>Add</button>";

  echo "<tr><td>Engineering Science Points<td align=right id='Factions:EngineeringSP:$Fid'>" . $FACTION['EngineeringSP'] .
    fm_number1('Number',$Ign,"Ignore:Factions:EngineeringSP:$Fid") .
    fm_text1('Reason',$Ign,"ReasonFor:Factions:EngineeringSP:$Fid",2) .
    "<button type=button onclick=AddTrack('Factions:EngineeringSP:$Fid',2)>Add</button>";

  echo "<tr><td>Xenology Science Points<td align=right id='Factions:XenologySP:$Fid'>" . $FACTION['XenologySP'] .
    fm_number1('Number',$Ign,"Ignore:Factions:XenologySP:$Fid") .
    fm_text1('Reason',$Ign,"ReasonFor:Factions:XenologySP:$Fid",2) .
    "<button type=button onclick=AddTrack('Factions:XenologySP:$Fid',3)>Add</button>";

  if ($Nam = GameFeature('Currency1')) echo "<tr><td>$Nam<td align=right id='Factions:Currency1:$Fid'>" . $FACTION['Currency1'] .
    fm_number1('Number',$Ign,"Ignore:Factions:Currency1:$Fid") .
    fm_text1('Reason',$Ign,"ReasonFor:Factions:Currency1:$Fid",2) .
    "<button type=button onclick=AddTrack('Factions:Currency1:$Fid',11)>Add</button>";

  if ($Nam = GameFeature('Currency2')) echo "<tr><td>$Nam<td align=right id='Factions:Currency2:$Fid'>" . $FACTION['Currency2'] .
    fm_number1('Number',$Ign,"Ignore:Factions:Currency2:$Fid") .
    fm_text1('Reason',$Ign,"ReasonFor:Factions:Currency2:$Fid",2) .
    "<button type=button onclick=AddTrack('Factions:Currency2:$Fid',12)>Add</button>";

  if ($Nam = GameFeature('Currency3')) echo "<tr><td>$Nam<td align=right id='Factions:Currency3:$Fid'>" . $FACTION['Currency3'] .
    fm_number1('Number',$Ign,"Ignore:Factions:Currency3:$Fid") .
    fm_text1('Reason',$Ign,"ReasonFor:Factions:Currency3:$Fid",2) .
    "<button type=button onclick=AddTrack('Factions:Currency3:$Fid',13)>Add</button>";

  foreach ($Tracks as $ti=>$Tr) {
    echo "<tr><td>" . ($ResTypes[$Tr['Type']]['Name']??'Unknown') . "<td align=right id='Resources:Value:$Fid'>" . $Tr['Value'] .
      fm_number1('Number',$Ign,"Ignore:Resources:Value:$ti") .
      fm_text1('Reason',$Ign,"ReasonFor:Resources:Value:$Fid",2) .
      "<button type=button onclick=AddTrack('Resources:Value:$ti',20+$ti)>Add</button>" .
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
