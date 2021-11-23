<?php
  include_once("sk.php");
  include_once("GetPut.php");
  
  A_Check('GM');

  dohead("Full Map");
  
  $CatCols = ["white","grey", "yellow"];
  $HexLegPos = [[1,8],[1,7.5],[1,7],[1,6.5], [9,8],[9,7.5],[9,7],[9,6.5],[1,0],[1,0.5],[1,2],[1,1],[1,1.5],[9,2],[9,1.5],[9,1],[9,0.5],[9,0]];

  
  $typ='';
  if (isset($_REQUEST['Hex'])) $typ = 'Hex';

  if (isset($_REQUEST['f'])) {
    $Faction = $_REQUEST['f'];
  } else if (isset($_REQUEST['f1'])) {
    $Faction = $_REQUEST['f1'];
  } else {
    $Faction = 0;
  }

  function NodeLab($txt,$Prefix) {
    $FSize = [14,14,14,14,14, 13,13,12,12, 11,11,10,10, 9,9,9,9, 8,8,8,8];
    if (strlen($txt) > 20 ) $txt = substr($txt,0,20);
    $len = strlen($txt);
    $ret = ' label="';
    if ($Prefix) $ret .= "$Prefix\n";
    $ret .= $txt . '"';
    if ($len < 5) return $ret;
    return $ret . " fontsize=" . $FSize[$len] . " ";
  }

// echo "<h1>Faction $Faction</h1>";
  global $db, $GAME;

  $Dot = fopen("cache/Fullmap$Faction$typ.dot","w");
  if (!$Dot) { echo "Could not create dot file<p>"; dotail(); };
  
  $Nodes = Get_Systems(); 
  $Levels = Get_LinkLevels();
  $Factions = Get_Factions(); 
  $Neb = 0;
  $ShownNodes = [];
  
  fwrite($Dot,"graph {\n") ; //size=" . '"8,12!"' . "\n");
  
//  if (!$typ) fwrite($Dot, "[size=\"10,20!\"];\n");
  
  foreach ($Nodes as $N) {
    $NodeName = $N['Name']?$N['Name']:"";
    if ($Faction) {
      if ($N['Control'] != $Faction) {
        $FS = Get_FactionSystemFS($Faction, $N['id']);
        if (!isset($FS['id'])) continue;
        if ($FS['Name']) $NodeName = $FS['Name'];
      }
    }
    $atts = "";

    $Colour = "white";
    if ($N['Control']) {
      $Colour = $Factions[$N['Control']]['MapColour'];
      $Factions[$N['Control']]['Seen']=1;
    } else if ($N['Category']) {
      $Colour = $CatCols[$N['Category']];
    } else {
      $Colour = "White";
    }
    
    if ($typ) $atts .= " shape=box pos=\"" . ($N['GridX']+(5-$N['GridY'])/2) . "," . (9-$N['GridY']) . "!\"";
    $atts .= "  shape=box style=filled fillcolor=$Colour";
    if ($NodeName) {
      $atts .= NodeLab($NodeName, ($Faction==0?$N['Ref']:""));
    }
    if ($N['Nebulae']) { $atts .= " penwidth=" . (1+$N['Nebulae']*2); $Neb = 1; };
    
    fwrite($Dot,$N['Ref'] . " [$atts ];\n");
    $ShownNodes[$N['Ref']]= 1;
    
//    if ($typ) {
//      fwrite($Dot,$N['Ref'] . " [shape=hexagon pos=\"" . ($N['GridX']+(5-$N['GridY'])/2) . "," . (9-$N['GridY']) . "!\" style=filled fillcolor=$Colour] ;\n");
//    } else {
//      fwrite($Dot,$N['Ref'] . " [style=filled fillcolor=$Colour];\n");
//    }
  }
  
  fwrite($Dot,"subgraph legend {\n");
  $ls=0;
  foreach ($Factions as $F) {
    if (isset($F['Seen'])) {
      fwrite($Dot,$F['Name'] . " [shape=box style=filled fillcolor=" . $F['MapColour'] . ($typ?" pos=\"" . $HexLegPos[$ls][0] . "," . $HexLegPos[$ls][1] . "!\"" : "") . "];\n");
      $ls++;
    }
  }
  
  if ($Neb) {
    if ($typ) {
      fwrite($Dot,"Nebulae [shape=box style=filled fillcolor=white penwidth=3" . ($typ?" pos=\"" . $HexLegPos[$ls][0] . "," . $HexLegPos[$ls][1] . "!\"" : "") . "];\n");
      $ls++;
    } else {
      fwrite($Dot,"Nebulae [shape=box style=filled fillcolor=white penwidth=3];\n");
    }
  };
  fwrite($Dot,"}\n");
  

  if ($Faction) {
    $ul = 1;
    foreach($Nodes as $N) {
      $from = $N['Ref'];
      $Links = Get_Links($from);
      if (!isset( $ShownNodes[$N['Ref']])) continue;

      $nl = 1;
      foreach ($Links as $L) {
        $Fl = Get_FactionLinkFL($Faction, $L['id']);
        if (isset($Fl['id'])) {
          fwrite($Dot,$L['System1Ref'] . " -- " . $L['System2Ref'] . " [color=" . $Levels[$L['Level']]['Colour'] . "];\n");
        } else {
          fwrite($Dot,"Unk$ul [label=\"?\" shape=circle];\n");
          fwrite($Dot,"$from -- Unk$ul [color=" . $Levels[$L['Level']]['Colour'] . " label=\"#$nl\" ];\n");
          $ul++;
          $nl++;
        }
      }
    }
  } else {
    foreach($Nodes as $N) {
      $from = $N['Ref'];
      $Links = Get_Links1end($from);

      foreach ($Links as $L) {
        fwrite($Dot,$L['System1Ref'] . " -- " . $L['System2Ref'] . " [color=" . $Levels[$L['Level']]['Colour'] . "];\n");
      }
    }
  }
  
  
  fwrite($Dot,"}\n");
  fclose($Dot);
//  echo "<H1>Dot File written</h1>";
  
  if ($typ) {  
    exec("fdp -Tpng -n cache/Fullmap$Faction$typ.dot > cache/Fullmap$Faction$typ.png");
  } else {
    exec("unflatten cache/Fullmap$Faction$typ.dot > cache/f.dot");
    exec("dot -Tpng cache/f.dot > cache/Fullmap$Faction.png");
  }

//  echo "<h2>dot run</h2>";
  echo "<img src=cache/Fullmap$Faction$typ.png maxwidth=100%>";
  
  dotail();
?>
