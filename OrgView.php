<?php
include_once("sk.php");
include_once("GetPut.php");
include_once("ThingLib.php");
include_once("PlayerLib.php");
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
$Facts = Get_Factions();

$Offices = Gen_Get_Cond('Offices',"Organisation=$OrgId");
$Branches = Gen_Get_Cond('Branches',"Organisation=$OrgId ORDER BY HostType");

dostaffhead("Organisation");

echo "<h1>" . $Org['Name'] . "</h1>";
echo "Type: <b>" . ($OrgTypes[$Org['OrgType']]['Name']??'Unknown') . "</b><p>";
if ($Org['OrgType2']) echo "Also Type: <b>" . ($OrgTypes[$Org['OrgType2']]['Name']??'Unknown') . "</b><p>";


echo "Decription: <p>" . ParseText($Org['Description']);
echo "<h2>Offices:</h2>";

if ($Offices) {
  $Head = 0;
  foreach ($Offices as $Off) {
    $W = Get_World($Off['World']);
    $control = 0;
    switch ($W['ThingType']) {
      case 1: // Planet
        $P = Get_Planet($W['ThingId']);
        $Sys = Get_System($P['SystemId']);
        $Where = $P['Name'] . " in " . System_Name($Sys,$Fid);
        $control = $P['Control'];
        break;
      case 2: // Moon
        $M = Get_Moon($W['ThingId']);
        $P = Get_Planet($M['PlanetId']);
        $Sys = Get_System($P['SystemId']);
        $Where = $M['Name'] . " a moon of " . $P['Name'] . " in " . System_Name($Sys,$Fid);
        $control = $M['Control'];
        break;
      case 3:// Thing
        $T = Get_Thing($W['ThingId']);
        $Sys = Get_System($T['SystemId']);
        $Where = $T['Name'] . " Currently in " . System_Name($Sys,$Fid);
        $control = $T['Whose'];
        break;
    }
    echo "<li>$Where";
    if ($Head == 0) {
      echo " [ Head Office ]";
      $Head = 1;
    }

    if (Has_Trait($Fid,'Goverwhat now?') && ($control != $Fid)) {
      $World = Gen_Get_Cond1('Worlds',"ThingType=" . $W['ThingType'] . " AND ThingId=" . $W['ThingId']);
      $SocPs = Get_SocialPs($World['id']);
      if ($SocPs) {
        echo "<br>It's social principles are:<ul>";
        foreach ($SocPs as $si=>$SP) {
          $Prin = Get_SocialP($SP['Principle']);
          echo "<li><b>" . $Prin['Principle'] . "</b> - Adherence: " . $SP['Value'];
          echo "<br>" . ParseText($Prin['Description']);
        }
        echo "</ul>";
      } else {
        echo "<br>No Social Principles Currently";
      }
    }

    echo "<br>";
  }
} else {
  echo "None Found<p>";
}

$Teamid = $Org['Team'];
if ($Teamid == 0) {
  echo "Team not currently deployed.<p>";
} else {
  $Team = Get_Thing($Teamid);
  $N = Get_System($Team['SystemId']);
  echo "Team: <a href=ThingEdit.php?id=$Teamid>" . $Team['Name'] . "</a> currently in " . System_Name($N,$Org['Whose']) . "<P>";
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
    if ($B['HostType'] != 3) {
      $World = Gen_Get_Cond1('Worlds',"ThingType=" . $B['HostType'] . " AND ThingId=" . $B['HostId']);
      if (!$World) {
        echo "<h2 class=Err>Branch without a World tell Richard #" . $B['id'] . "</h2>";
        break;
      }
      $SocPs = Get_SocialPs($World['id']);
      if ($SocPs) {
        echo "<br>It's social principles are:<ul>";
        foreach ($SocPs as $si=>$SP) {
          $Prin = Get_SocialP($SP['Principle']);
          echo "<li><b>" . ($Prin['Principle']??'Unknown') . "</b> - Adherence: " . $SP['Value'];
          echo "<br>" . ParseText($Prin['Description']??'Unknown');
        }
        echo "</ul>";
      } else {
        echo "<br>No Social Principles Currently";
      }

    }
    if ($B['Suppressed']) echo "<span class=Red> This branch is suppressed and can not do any thing for " . $B['Suppressed'] . " turns.";
  }
} else {
  echo "None Found<p>";
}

dotail();
