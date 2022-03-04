<?php

  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("PlayerLib.php");
  include_once("SystemLib.php");
  
  global $FACTION,$GAME,$Project_Status;
  
  A_Check('GM');

  dostaffhead("Edit a Project");

  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
    case 'Find': // Systems
      $Name = $_REQUEST['Name'];
      $Ns = Gen_Get_Cond('Systems',"Name LIKE'%$Name%'");
      if ($Ns) {
        echo "<h2>Systems with that name</h2>";
        foreach($Ns as $N) {
          echo "<a href=SysEdit.php?i=" . $N['id'] . ">" . System_Name($N) . "</a><br>\n";
        }
      }
        
      // Faction Systems
      $Ns = Gen_Get_Cond('FactionSystems',"Name LIKE '%$Name%'");
      if ($Ns) {
        echo "<h2>Factions name for Systems with that name</h2>";
        foreach($Ns as $FN) {
          $N = Get_System($FN['SystemId']);
          echo "<a href=SysEdit.php?i=" . $N['id'] . ">" . System_Name($N,$FN['FactionId']) . "</a><br>\n";
        }
      }
     
      
      // Planets
      $Ps = Gen_Get_Cond('Planets',"Name LIKE'%$Name%'");
      if ($Ps) {
        echo "<h2>Planets with that name</h2>";
        foreach($Ps as $P) {
          $N = Get_System($P['SystemId']);
          echo "<a href=PlanEdit.php?i=" . $P['id'] . ">" . $P['Name'] . " in " . System_Name($N) . "</a><br>\n";
        }
      }
      
      
      // Faction Planets
      $Ps = Gen_Get_Cond('FactionPlanet',"Name LIKE '%$Name%'");
      if ($Ps) {
        echo "<h2>Factions name for Planets with that name</h2>";
        foreach($Ps as $FP) {
          $P = Get_Planet($FP['PlanetId']);
          $N = Get_System($P['SystemId']);
          echo "<a href=PlanEdit.php?i=" . $P['id'] . ">" . $FP['Name'] . " in " . System_Name($N) . "</a><br>\n";
        }
      }
      
     
      
      // Moons

      $Ms = Gen_Get_Cond('Moons',"Name LIKE '%$Name%'");
      if ($Ps) {
        echo "<h2>Moons with that name</h2>";
        foreach($Ms as $M) {
          $P = Get_Planet($M['PlanetId']);
          $N = Get_System($P['SystemId']);
          echo "<a href=MoonEdit.php?i=" . $M['id'] . ">" . $M['Name'] . " a moon of " . $P['Name'] . " in " . System_Name($N) . "</a><br>\n";
        }
      }
      
      
      // Faction Moons
      $FPs = Gen_Get_Cond('FactionMoon',"Name LIKE '%$Name%'");
      if ($FPs) {
        echo "<h2>Factions name for Moons with that name</h2>";
        foreach($Ps as $FP) {
          $M = Get_Moon($FP['MoonId']);
          $P = Get_Planet($FP['PlanetId']);
          $N = Get_System($P['SystemId']);
          echo "<a href=MoonEdit.php?i=" . $M['id'] . ">" . $FP['Name'] . " a moon of " . $P['Name']  . " in " . System_Name($N) . "</a><br>\n";
        }
      }
      
      
      //Things
      $Ts = Gen_Get_Cond('Things',"Name LIKE '%$Name%'");
      if ($Ts) {
        echo "<h2>Things with that name</h2>";
        $TTs = Get_ThingTypes();
        foreach($Ts as $T) {
          $N = Get_System($T['SystemId']);
          echo "<a href=ThingEdit.php?i=" . $T['id'] . ">" . $T['Name'] . " a " . $TTs[$T['Type']]['Name'] . "</a> Currently in " . $N['Ref'] . "<br>\n";
        }
      }
 
    }
  }
  
echo "<H1>Where is a name?</h1>\n";
echo "<form method=post action=WhereIs.php?ACTION=Find>\n";
echo "<input type=text name=Name onchange=this.form.submit()>\n";
dotail();
?>
