<?php
  include_once("sk.php");
  include_once("GetPut.php");
  
  if (Access('GM') ) {
    A_Check('GM');
  } else if (Access('Player')) {
    if (!$FACTION) {
      Error_Page("Sorry you need to be a GM or a Player to access this");
    }
 
 // var_dump($_REQUEST, $_COOKIE);  
  }
  dostaffhead("Full Map");
  
  global $FACTION;
  
  $CatCols = ["white","grey", "Yellow"];
  $HexLegPos = [[1,8],[1,7.5],[1,7],[1,6.5], [9,8],[9,7.5],[9,7],[9,6.5],[1,0],[1,0.5],[1,2],[1,1],[1,1.5],[9,2],[9,1.5],[9,1],[9,0.5],[9,0]];

  
  $typ='';
  if (isset($_REQUEST['Hex'])) $typ = 'Hex';

  if (isset($_REQUEST['f'])) {
    $Faction = $_REQUEST['f'];
  } else   if (isset($_REQUEST['F'])) {
    $Faction = $_REQUEST['F'];
  } else if (isset($_REQUEST['f1'])) {
    $Faction = $_REQUEST['f1'];
  } else {
    $Faction = 0;
  }
  
  if ($FACTION) $Faction = $FACTION['id'];

  function NodeLab($txt,$Prefix) {
    $FSize = [14,14,14,14,14, 13,13,12,12, 11,10,9,9, 8,8,7,7, 6,6,6,6];
    if (strlen($txt) > 20 ) $txt = substr($txt,0,20);
    $len = strlen($txt);
    $ret = ' label="';
    if ($Prefix) $ret .= "$Prefix\n";
    if ($len > 8) {
      $words = explode(' ',$txt); 
      $numwrds = count($words);
      if ($numwrds > 1) {
        $sp = round(($len+1)/2);
        $nxt = (($len&1)?1:-1);
        $i = 1;
        while ($sp > 0) {
          if (substr($txt,$sp,1) == ' ') { 
            $txt = substr($txt,0,$sp-1) . "\n" . substr($txt,$sp);
            $len = max($sp,$len-$sp);
            break;
          } else {
            $sp += $nxt*$i;
            $nxt = - $nxt;
            if ($i++ > 20 ) {
              break;
            }
          }
        }
      }
    }
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
  $LinkSown = [];
  
  fwrite($Dot,"graph {\n") ; //size=" . '"8,12!"' . "\n");
  
//  if (!$typ) fwrite($Dot, "[size=\"10,20!\"];\n");
  
  foreach ($Nodes as $N) {
    $NodeName = $N['Name']?$N['Name']:"";
    $ShortName = $N['ShortName']?$N['ShortName']:$NodeName;
    if ($Faction) {
      if ($N['Control'] != $Faction) {
        $FS = Get_FactionSystemFS($Faction, $N['id']);
        if (!isset($FS['id'])) continue;
        if ($FS['Name']) $ShortName = $NodeName = $FS['Name'];
        if ($FS['ShortName']) $ShortName = $FS['ShortName'];
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
    
    $BdrColour = "Black";
    if ($Faction == 0 && $N['HistoricalControl']) $BdrColour = $Factions[$N['HistoricalControl']]['MapColour'];
    
    if ($typ) $atts .= " shape=box pos=\"" . ($N['GridX']+(5-$N['GridY'])/2) . "," . (9-$N['GridY']) . "!\"";
    $atts .= "  shape=box style=filled fillcolor=$Colour color=$BdrColour";
    if ($NodeName) {
      $atts .= NodeLab($ShortName, ($Faction==0?$N['Ref']:""));
    }
    if ($N['Nebulae']) { $atts .= " penwidth=" . (2+$N['Nebulae']*2); $Neb = 1; }
    else { $atts .= " penwidth=2"; }
    
    fwrite($Dot,$N['Ref'] . " [$atts ];\n");
    $ShownNodes[$N['Ref']]= 1;
    
//    if ($typ) {
//      fwrite($Dot,$N['Ref'] . " [shape=hexagon pos=\"" . ($N['GridX']+(5-$N['GridY'])/2) . "," . (9-$N['GridY']) . "!\" style=filled fillcolor=$Colour] ;\n");
//    } else {
//      fwrite($Dot,$N['Ref'] . " [style=filled fillcolor=$Colour];\n");
//    }
  }
  

  if ($Faction) {
    $ul = 1;
    foreach($Nodes as $N) {
      $from = $N['Ref'];
      $Links = Get_Links($from);
      if (!isset( $ShownNodes[$N['Ref']])) continue;

      foreach ($Links as $L) {
        if (isset($LinkShown[$L['id']])) continue;
        
        $Fl = Get_FactionLinkFL($Faction, $L['id']);
        if (isset($Fl['id'])) {
          fwrite($Dot,$L['System1Ref'] . " -- " . $L['System2Ref'] . " [color=" . $Levels[$L['Level']]['Colour'] . " label=\"#" . $L['id'] . "\" ];\n");
        } else {
          fwrite($Dot,"Unk$ul [label=\"?\" shape=circle];\n");
          fwrite($Dot,"$from -- Unk$ul [color=" . $Levels[$L['Level']]['Colour'] . " label=\"#" . $L['id'] . "\" ];\n");
          $ul++;
        }
        $LinkShown[$L['id']]=1;
      }
    }
  } else {
    foreach($Nodes as $N) {
      $from = $N['Ref'];
      $Links = Get_Links1end($from);

      foreach ($Links as $L) {
        fwrite($Dot,$L['System1Ref'] . " -- " . $L['System2Ref'] . " [color=" . $Levels[$L['Level']]['Colour'] . ($typ?"":(" label=\"#" . $L['id'] . "\"")) . "];\n");
      }
    }
  }
  
  

//  fwrite($Dot,"}\n");
//  fwrite($Dot,"graph legend {\n");
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
    } else {
      fwrite($Dot,"Nebulae [shape=box style=filled fillcolor=white penwidth=3];\n");
    }
    $ls++;
  };
  
  if (!$Faction) {
    fwrite($Dot,"Historical [shape=box style=filled fillcolor=white penwidth=2 color=" . $F['MapColour'] . 
          ($typ?" pos=\"" . $HexLegPos[$ls][0] . "," . $HexLegPos[$ls][1] . "!\"" : "") . "];\n");  
    $ls++;  
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
