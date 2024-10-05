<?php
include_once("sk.php");
include_once("GetPut.php");
include_once("ThingLib.php");
include_once("SystemLib.php");
include_once("OrgLib.php");
global $FACTION,$GAMEID;

$OrgId = $_REQUEST['id'];
$Org = Gen_Get('Organisations',$OrgId);

$GM = Access('GM');
if (!$GM && $Org['Whose']!= $FACTION['id']) {
  Error_Page("Not your Organisation");
}

$Fid = $Org['Whose'];
$OrgTypes = Get_OrgTypes();
$BTypes = Get_BranchTypes();

$Offices = Gen_Get_Cond('Offices',"Organisation=$OrgId");
$Branches = Gen_Get_Cond('Branches',"Organisation=$OrgId");

dostaffhead("Organisation");

echo "<h1>" . $Org['Name'] . "</h1>";
echo "Type: <b>" . ($OrgTypes[$Org['OrgType']]['Name']??'Unknown') . "</b><p>";
if ($Org['OrgType2']) echo "Also Type: <b>" . ($OrgTypes[$Org['OrgType2']]['Name']??'Unknown') . "</b><p>";


echo "Decription: <p>" . $Org['Description'];
echo "<h2>Offices:</h2>";

if ($Offices) {
  $Head = 0;
  foreach ($Offices as $Off) {
    $W = Get_World($Off['World']);
    switch ($W['ThingType']) {
      case 1: // Planet
        $P = Get_Planet($W['ThingId']);
        $Sys = Get_System($P['SystemId']);
        $Where = $P['Name'] . " in " . System_Name($Sys,$Fid);
        break;
      case 2: // Moon
        $M = Get_Moon($W['ThingId']);
        $P = Get_Planet($M['PlanetId']);
        $Sys = Get_System($P['SystemId']);
        $Where = $M['Name'] . " a moon of " . $P['Name'] . " in " . System_Name($Sys,$Fid);
        break;
      case 3:// Thing
        $T = Get_Thing($W['ThingId']);
        $Sys = Get_System($T['SystemId']);
        $Where = $T['Name'] . " Currently in " . System_Name($Sys,$Fid);
        break;
    }
    echo "<li>$Where";
    if ($Head == 0) {
      echo " [ Head Office ]";
      $Head = 1;
    }
    echo "<br>";
  }
} else {
  echo "None Found<p>";
}
echo "<p><h2>Branches:</h2>";

if ($Branches) {
  foreach ($Branches as $B) {
    switch ($B['HostType']) {
      case 1: // Planet
        $P = Get_Planet($B['HostId']);
        $Sys = Get_System($P['SystemId']);
        $Where = $P['Name'] . " in " . System_Name($Sys,$Fid);
        break;
      case 2: // Moon
        $M = Get_Moon($B['HostId']);
        $P = Get_Planet($M['PlanetId']);
        $Sys = Get_System($P['SystemId']);
        $Where = $M['Name'] . " a moon of " . $P['Name'] . " in " . System_Name($Sys,$Fid);
        break;
      case 3:// Thing
        $T = Get_Thing($B['HostId']);
        $Sys = Get_System($T['SystemId']);
        $Where = $T['Name'] . " in " . System_Name($Sys,$Fid);
        break;
    }
    echo "<li>". $BTypes[$B['Type']]['Name'] . " at $Where" ;
  }
} else {
  echo "None Found<p>";
}

dotail();
