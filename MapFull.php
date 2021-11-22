<?php
  include_once("sk.php");
  include_once("GetPut.php");

  A_Check('GM');

  dohead("Full Map");
  
  $typ='';
  if (isset($_REQUEST['Hex'])) $typ = 'Hex';

  global $db, $GAME;

  $Dot = fopen("cache/Fullmap234234$typ.dot","w");
  if (!$Dot) { echo "Could not create dot file<p>"; dotail(); };
  
  $Nodes = Get_Systems(); 
  $Levels = Get_LinkLevels(); 
  
  fwrite($Dot,"graph {\n") ; //size=" . '"8,12!"' . "\n");
  foreach ($Nodes as $N) {
    if ($typ) {
      fwrite($Dot,$N['Ref'] . " [shape=hexagon pos=\"" . $N['GridX'] . "," . $N['GridY'] . "!\" " . ($N['Category']?" style=filled fillcolor=grey":"") . "] ;\n");
    } else {
      fwrite($Dot,$N['Ref'] . ($N['Category']?" [style=filled fillcolor=grey]":"") . ";\n");
    }
  }
  
  foreach($Nodes as $N) {
    $from = $N['Ref'];
    $Links = Get_Links($N['Ref']);
//echo "From $from : ";
//var_dump($Links);
//echo "<p>";
    foreach ($Links as $L) {
      if ($L['System1Ref'] == $from) {
        if ($L['System2Ref'] > $from) {
          fwrite($Dot,$L['System1Ref'] . " -- " . $L['System2Ref'] . " [color=" . $Levels[$L['Level']]['Colour'] . "];\n");
        }
      }
    }
//exit;
  }
  
  
  fwrite($Dot,"}\n");
  fclose($Dot);
  echo "<H1>Dot File written</h1>";

  if ($typ) {  
    exec("fdp -Tpng -n cache/Fullmap234234$typ.dot > cache/Fullmap34764576272$typ.png");
  } else {
    exec("dot -Tpng cache/Fullmap234234$typ.dot > cache/Fullmap34764576272.png");
  }

  echo "<h2>dot run</h2>";
  echo "<img src=cache/Fullmap34764576272$typ.png width=100%>";
  
  dotail();
?>
