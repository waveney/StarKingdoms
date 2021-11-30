<?php
  include_once("sk.php");
  include_once("GetPut.php");

  A_Check('GM');

  dostaffhead("List Systems");

  global $db, $GAME;

  $Systems = Get_Systems();
  $Factions = Get_Factions();
  
  if (!$Systems) {
    echo "<h2>No systems found</h2>";
    dotail();
  }
  
  echo "<h1>Systems</h1>";
  
  $coln = 0;
  echo "<div class=tablecont><table id=indextable border width=100% style='min-width:1400px'>\n";
  echo "<thead><tr>";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Ref</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Control</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Name</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Nebulae</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Survey Report</a>\n";
  echo "</thead><tbody>";

  foreach($Systems as $N) {
    $sid = $N['id'];
    $Name = $Ref = $N['Ref'];
    $Cont = $N['Control'];
    $Ctrl = ($Cont?"<a href=FactEdit.php?F=$Cont>" . $Factions[$Cont]['Name'] . "</a>":"");
    if ($N['Name']) $Name = $N['Name'];
    if ($N['ShortName']) $Name = $N['ShortName'];
    if (strlen($Name)> 20) $Name = substr($Name,0,20);
    $Neb = $N['Nebulae'];
    echo "<tr><td><a href=SysEdit.php?N=$sid>$Ref</a>";
    echo "<td>$Ctrl";
    echo "<td>$Name";
    echo "<td>$Neb";
    echo "<td><a href=SurveyReport.php?id=$sid>Survey Report</a>"; // Generic 
  }
      
  echo "</tbody></table></div>\n";


  dotail();
?>
