<?php
  include_once("sk.php");
  include_once("GetPut.php");
  
  global $FACTION;
  
  if (Access('GM') ) {
    A_Check('GM');
  } else if (Access('Player')) {
    if (!$FACTION) {
      Error_Page("Sorry you need to be a GM or a Player to access this");
    }
 

  }
  dostaffhead("Full Map");
 //var_dump($_REQUEST, $_COOKIE);  

  A_Check('Player');
  $ShowLinks = 1;
  if (isset($_REQUEST['Links'])) $ShowLinks = $_REQUEST['Links'];

  $CatCols = ["white","grey", "Yellow"];
//  $HexLegPos = [[1,8],[1,7.5],[1,7],[1,6.5], [9,8],[9,7.5],[9,7],[9,6.5], [1,0],[1,0.5],[1,1],[1,1.5], [9,0],[9,0.5],[9,1.5],[9,2]];
  eval("\$HexLegPos=" . GameFeature('HexLegPos','') . ";" ); // [[1,8],[1,7.5],[1,7],[1,6.5], [9,8],[9,7.5],[9,7],[9,6.5], [1,0],[1,0.5],[1,1],[1,1.5], [9,0],[9,0.5],[9,1.5],[9,2]];
// var_dump($HexLegPos);exit;
  
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

  $typ='';
  if (isset($_REQUEST['Hex'])) {
    if (Access('GM')) {
      $typ = 'Hex';   
    } elseif (Access('Player')) {
      if (Has_Tech($Faction,'Astral Mapping')) {
        echo "Found Hex...";
        $typ = 'Hex';
      }
    } else {
      $typ = 'Hex';
    }
  }


//var_dump($typ);exit;

  function NodeLab($txt,$Prefix='') {
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
            $txt = substr($txt,0,$sp) . "\n" . substr($txt,$sp);
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
  
  $RedoMap = 1;
//  $Dot = fopen("cache/Fullmap$Faction$typ.dot","w+");
//  if (!$Dot) { echo "Could not create dot file<p>"; dotail(); };


  while ($RedoMap) {
  $Dot = fopen("cache/Fullmap$Faction$typ.dot","w+");
  if (!$Dot) { echo "Could not create dot file<p>"; dotail(); };
//  ftruncate($Dot,0);  // Needed for redoing logic
  
 // echo "AA";
  
  $RedoMap = 0;
    
  $Nodes = Get_Systems(); 
  $Levels = Get_LinkLevels();
  $Factions = Get_Factions(); 
  $NebF = 0;
  $ShownNodes = [];
  $LinkSown = [];
  $UnknownLink = [];
  
  fwrite($Dot,"graph skmap {\n") ; //size=" . '"8,12!"' . "\n");
//  echo "BB";
//  if (!$typ) fwrite($Dot, "[size=\"10,20!\"];\n");
  
  foreach ($Nodes as $N) {
    if (!ctype_alnum($N['Ref'])) continue;
    $NodeName = $N['Name']?$N['Name']:"";
    $ShortName = $N['ShortName']?$N['ShortName']:$NodeName;
    $Hide = 0;
    $FS = 0;
    if ($Faction) {
      $FS = Get_FactionSystemFS($Faction, $N['id']);
      if ($N['Control'] != $Faction) {
        if (!isset($FS['id'])) continue;
        if ($FS['ScanLevel'] > 1) {
          if ($FS['Name']) $ShortName = $NodeName = $FS['Name'];
          if ($FS['ShortName']) $ShortName = $FS['ShortName'];
        } else {
          $Hide = 1;
        }
      }
    }
    $atts = "";

    $Colour = "white";
    if ($N['Control'] && !$Hide) {
      $Colour = $Factions[$N['Control']]['MapColour'];
      $Factions[$N['Control']]['Seen']=1;
    } else if ($N['Category']) {
      $Colour = ($Faction?"White":$CatCols[$N['Category']]);
    } else {
      $Colour = "White";
    }
    
    if ($Hide) $NodeName = '';
    $BdrColour = "Black";
    if ($Faction == 0 && $N['HistoricalControl']) $BdrColour = $Factions[$N['HistoricalControl']]['MapColour'];
    
    if ($typ) $atts .= " shape=box pos=\"" . ($N['GridX']+(5-$N['GridY'])/2) . "," . (9-$N['GridY']) . "!\"";
    $atts .= "  shape=box style=filled fillcolor=\"$Colour\" color=\"$BdrColour\"";
    if ($NodeName) {
      $atts .= NodeLab($ShortName, $N['Ref']); //($Faction==0?$N['Ref']:""));
    }
    if ($N['Nebulae']) { $atts .= " penwidth=" . (2+$N['Nebulae']*2); $NebF = 1; }
    else { $atts .= " penwidth=2"; }
   
    if ($Faction) {
      $atts .= " href=\"/SurveyReport.php?R=" . $N['Ref'] . '" ';
      
      if (!empty($FS['Xlabel'])) $atts .= " xlabel=\"" . $FS['Xlabel'] . '" ';
    } else {
      $atts .= " href=\"/SysEdit.php?N=" . $N['id'] . '" ';
    }
    
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
    $AllLinks = Has_Tech($Faction,'Know All Links');
    foreach($Nodes as $N) {
      $from = $N['Ref'];
      
      $if = Get_FactionSystemFS($Faction,$N['id']);
// var_dump($if);
 
      $Links = Get_Links($from);
      if (!isset( $ShownNodes[$N['Ref']])) continue;
      $Neb = $N['Nebulae'];
      foreach ($Links as $L) {
        if (isset($LinkShown[$L['id']])) continue;
        $Fl = Get_FactionLinkFL($Faction, $L['id']);
        if (isset($Fl['id']) && $Fl['Known'] || $AllLinks) {
          fwrite($Dot,$L['System1Ref'] . " -- " . $L['System2Ref'] . " [color=\"" . $Levels[$L['Level']]['Colour'] . '"' . ($ShowLinks? " label=\"#" . $L['id'] . '"' : '') . " ];\n");
          $LinkShown[$L['id']]=1;
        } else {
          if ($Neb && $Fl['NebScanned'] < $Neb) continue;
//          $To = ($L['System1Ref'] == $from ? $L['System2Ref'] : $L['System1Ref']);
//          $if = Get_FactionSystemFRef($Faction,$To);

//if ($from == 'DDE') { var_dump($if); echo "<br>"; }
          if (isset($if['ScanLevel']) && $if['ScanLevel']<2) continue;
          $rand = "B$ul";  // This kludge at least allows both ends to be displayed
          fwrite($Dot,"Unk$ul$rand [label=\"?\" shape=circle];\n");
          fwrite($Dot,"$from -- Unk$ul$rand [color=\"" . $Levels[$L['Level']]['Colour'] . '"' . ($ShowLinks? " label=\"#" . $L['id'] . '"' : '') . " ];\n");
          $ul++;
          if (isset($UnknownLink[$L['id']])) {
            $RedoMap = 1;
            $Fl['Known'] = 1;
            Put_FactionLink($Fl);
          } else {
            $UnknownLink[$L['id']] = 1;
          }
        }
 //       $LinkShown[$L['id']]=1;
      }
    }
  } else {
    foreach($Nodes as $N) {
      $from = $N['Ref'];
      $Links = Get_Links1end($from);

      foreach ($Links as $L) {
        fwrite($Dot,$L['System1Ref'] . " -- " . $L['System2Ref'] . " [color=\"" . $Levels[$L['Level']]['Colour'] . '"' . ($typ?"":(" label=\"#" . $L['id'] . "\"")) . "];\n");
      }
    }
  }
  
  

//  fwrite($Dot,"}\n");
//  fwrite($Dot,"graph legend {\n");
  $ls=0;
  foreach ($Factions as $F) {
    if (isset($F['Seen'])) {
      fwrite($Dot,"FF" . $F['id'] . " [shape=box style=filled fillcolor=\"" . $F['MapColour'] . '"' .
          NodeLab($F['Name']) . ($typ?" pos=\"" . $HexLegPos[$ls][0] . "," . $HexLegPos[$ls][1] . "!\"" : "") . "];\n");
      $ls++;
    }
  }
  
  if ($NebF) {
    if ($typ) {
      fwrite($Dot,"Nebulae [shape=box style=filled fillcolor=white penwidth=3" . ($typ?" pos=\"" . $HexLegPos[$ls][0] . "," . $HexLegPos[$ls][1] . "!\"" : "") . "];\n");
    } else {
      fwrite($Dot,"Nebulae [shape=box style=filled fillcolor=white penwidth=3];\n");
    }
    $ls++;
  };
  
  if (!$Faction) {
    fwrite($Dot,"Historical [shape=box style=filled fillcolor=white penwidth=2 color=\"CadetBlue\"" .
          ($typ?" pos=\"" . $HexLegPos[$ls][0] . "," . $HexLegPos[$ls][1] . "!\"" : "") . "];\n");  
    $ls++;  
    fwrite($Dot,"ZZ99 [shape=box style=filled fillcolor=yellow penwidth=2 " . NodeLab("Interest","Other") . 
          ($typ?" pos=\"" . $HexLegPos[$ls][0] . "," . $HexLegPos[$ls][1] . "!\"" : "") . "];\n");  
    $ls++;  
  }

  fwrite($Dot,"}\n"); 
  fclose($Dot);
  } // Redo Loop

    
//  echo "<H1>Dot File written</h1>";
  
  if ($typ) {  
    exec("fdp -Tpng -n cache/Fullmap$Faction$typ.dot > cache/Fullmap$Faction$typ.png");
    exec("fdp -Tcmapx -n cache/Fullmap$Faction$typ.dot > cache/Fullmap$Faction$typ.map");    
//    exec("fdp -Timap -n cache/Fullmap$Faction$typ.dot > cache/Fullmap$Faction$typ.imap");
//    exec("fdp -Tsvg -n cache/Fullmap$Faction$typ.dot > cache/Fullmap$Faction$typ.svg");
  } else {
    exec("unflatten cache/Fullmap$Faction$typ.dot > cache/f.dot");
//    exec("dot -Tsvg cache/f.dot > cache/Fullmap$Faction.svg");
    exec("dot -Tpng cache/f.dot > cache/Fullmap$Faction$typ.png");
    exec("dot -Tcmapx cache/f.dot > cache/Fullmap$Faction$typ.map");
  }

//  echo "<h2>dot run</h2>";
  $Rand = rand(1,100000);
  echo "<img src=cache/Fullmap$Faction$typ.png?$Rand maxwidth=100% usemap='#skmap'>";
  readfile("cache/Fullmap$Faction$typ.map");
  
//  echo "<object type='image/svg+xml' data=cache/Fullmap$Faction$typ.svg?$Rand maxwidth=100%></object>";
//  echo "<svg type='image/svg+xml' data=cache/Fullmap$Faction$typ.svg?$Rand maxwidth=100%></svg>";

  
  dotail();
?>
