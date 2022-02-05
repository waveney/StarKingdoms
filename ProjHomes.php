<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("ProjLib.php");
  include_once("HomesLib.php");
 
  
  A_Check('GM');

  dostaffhead("List Project Homes");

  global $db, $GAME,$BuildState,$ShipTypes;
  global $HomeTypes;

  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
      case 'EDIT': 
        $Hid = $_REQUEST['id'];
        Show_Home($Hid);
        echo "<h2><a href=ProjHomes.php>Back to list of Homes</a></h2>\n";
        dotail();
        exit;
    }
  }



  $Systems = Get_SystemRefs();
  $Factions = Get_Factions();
  $Homes = Get_ProjectHomes();
  
  if (!$Homes) {
    echo "<h2>No Homes found</h2>";
    dotail();
  }
  
  echo "<h1>List Project Homes</h1>";
  echo "All worlds with districts, space stations and things which can do deep space<br>\n";
  
  $coln = 0;
  echo "<div class=tablecont><table id=indextable border width=100% style='min-width:1400px'>\n";
  echo "<thead><tr>";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Id</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Home Type</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Home id</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Whose</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Economy</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Projects</a>\n";
  echo "</thead><tbody>";

  foreach($Homes as $H) {
    $Hid = $H['id'];
    echo "<tr><td><a href=ProjHomes.php?ACTION=EDIT&id=$Hid>$Hid</a>";
    echo "<td>" . $HomeTypes[$H['ThingType']];
    echo "<td><a href=" . (['','PlanEdit.php','MoonEdit.php','ThingEdit.php'][$H['ThingType']]) . "?id=" . $H['ThingId'] . ">" . $H['ThingId'] . "</a>";
    echo "<td>" . $Factions[$H['Whose']]['Name'];
    echo "<td>" . $H['Economy'];
    echo "<td><a href=ProjDisp.php?id=" . $H['Whose'] . ">Projects</a>\n";
  }
      
  echo "</tbody></table></div>\n";
  

  dotail();
?>
