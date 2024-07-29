<?php
  include_once("sk.php");
  include_once("GetPut.php");

  global $FACTION,$GAMEID,$LinkType,$USERID;

  dostaffhead("Full Map");
 //var_dump($_REQUEST, $_COOKIE);

  $LinkType = Feature('LinkMethod');
  $XScale = Feature('XScale',1);

  $GM = Access('GM');
  $ShowLinks = 1;
  if (isset($_REQUEST['Links'])) $ShowLinks = $_REQUEST['Links'];

  $CatCols = ["white","grey", "Yellow"];
  $HexLegPos = [];
  eval("\$HexLegPos=" . Feature('LegPos','[[0,0]]') . ";" );
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
  $Fact = Get_Faction($Faction);

  if (!$GM && $Fact['TurnState'] > 2) Player_Page();

  $typ='';
  if (isset($_REQUEST['Hex'])) {
    if ($GM) {
      $typ = 'Hex';
    } elseif (Access('Player')) {
      if (Has_Tech($Faction,'Astral Mapping') || Feature('MapsinHex')) {
 //       echo "Found Hex...";
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

  $lfs = Feature('LinkFontSize',10);
  function LinkProps($L) {
    global $GM,$LinkType,$Levels,$lfs;


    $Res = [1,'solid','#' . $L['id'],14,'black'];

    if ($LinkType == 'Wormholes') {
      $Res[2] = $L['Name'];
      if ($GM) {
        $Res[0] = ($L['Instability']%3)+1;
        $Res[1] = ['solid','dashed','dotted'][min(2,intdiv($L['Instability'],3))];
      }
      $Res[4] = $Levels[$L['Concealment']]['Colour'];
    } else if ($LinkType == 'Gate') {
      if ($L['Level'] <0 || $L['Status'] > 0) $Res[1] = 'dotted';
      $Res[4] = '"' . $Levels[abs($L['Level'])]['Colour'] . '"';
    }
    if (strlen($Res[2]) > 4) $Res[3] = $lfs;
    return $Res;
  }

// echo "<h1>Faction $Faction</h1>";
  global $db, $GAME,$GAMEID;

  $HexLegPos = [];
  eval("\$HexLegPos=" . Feature('LegPos','[[0,0]]') . ";" );
//var_dump($HexLegPos);
  $RedoMap = 1;
//  $Dot = fopen("cache/Fullmap$Faction$typ.dot","w+");
//  if (!$Dot) { echo "Could not create dot file<p>"; dotail(); };


  $Nodes = Get_Systems();
  $PlanetTypes = Get_PlanetTypes();
  foreach($Nodes as $ref=>$N){
    $Sid = $N['id'];
    $Planets = Get_Planets($Sid);
    $Nodes[$ref]['Hospitable'] = 0;
    foreach ($Planets as $Pid=>$P) {
      if ($PlanetTypes[$P['Type']]['Hospitable']) {
        $Nodes[$ref]['Hospitable']++;
      }
      $Moons = Get_Moons($Pid);
      foreach ($Moons as $Mid=>$M) {
        if ($PlanetTypes[$M['Type']]['Hospitable']) {
          $Nodes[$ref]['Hospitable']++;
        }
      }
    }
  }

  $Levels = Get_LinkLevels();
// var_dump($Levels);
  $Factions = Get_Factions();
  $Historical = 0;
  $OtherInt = 0;
  $HabitableShape = Feature('HabitableShape','box');
  $InHabitableShape = Feature('InHabitableShape','box');
  $MultiHabitableShape = Feature('MultiHabitableShape',$HabitableShape);

  while ($RedoMap) {
    $Dot = fopen("cache/$GAMEID/Fullmap$Faction$typ.dot","w+");
    if (!$Dot) { echo "Could not create dot file<p>"; dotail(); };
//  ftruncate($Dot,0);  // Needed for redoing logic

 // echo "AA";

    $RedoMap = 0;

    $NebF = 0;
    $ShownNodes = [];
    $LinkSown = [];
    $UnknownLink = [];
    $LinkShown = [];

    fwrite($Dot,"graph skmap {\n") ; //size=" . '"8,12!"' . "\n");
    if ($typ) fwrite($Dot,"splines=true;\n");
//  echo "BB";
//  if (!$typ) fwrite($Dot, "[size=\"10,20!\"];\n");

    foreach ($Nodes as $N) {
      if (!ctype_alnum($N['Ref'])) continue;
      if (!isset($N['GridX']) || ( $N['GridX'] == 0 && $N['GridY'] == 0)) continue;
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
        if ($Colour == 'Yellow') $OtherInt = 1;
      } else {
        $Colour = "White";
      }

      if ($GM && $N['HistoricalControl']) $Factions[$N['HistoricalControl']]['Seen']=1;

      if ($Hide) $NodeName = '';
      $BdrColour = "Black";
      if ($Faction == 0 && $N['HistoricalControl']) {
        $BdrColour = $Factions[$N['HistoricalControl']]['MapColour'];
        $Historical = 1;
      }

      $atts .= " shape=" . ($N['Hospitable']?(($N['Hospitable']>1)?$MultiHabitableShape:$HabitableShape):$InHabitableShape);
      if ($typ) $atts .=
          " pos=\"" . ($N['GridX']*$XScale+(5-$N['GridY'])/2) . "," . (9-$N['GridY']) . "!\"";
      $atts .= " style=filled fillcolor=\"$Colour\" color=\"$BdrColour\"";
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
    }


    if ($Faction) {
      $ul = 1;
      $AllLinks = Has_Tech($Faction,'Know All Links');
      foreach($Nodes as $N) {
        $from = $N['Ref'];

        $FS = Get_FactionSystemFS($Faction,$N['id']);

        $Links = Get_Links($from);
        if (!isset( $ShownNodes[$N['Ref']])) continue;
        $Neb = $N['Nebulae'];
        foreach ($Links as $L) {
          if (isset($LinkShown[$L['id']])) continue;
          $Fl = Get_FactionLinkFL($Faction, $L['id']);
          $Ldat = LinkProps($L);
          if (isset($Fl['id']) && $Fl['Known'] || $AllLinks) {
            fwrite($Dot,$L['System1Ref'] . " -- " . $L['System2Ref'] .
                   " [color=" . $Ldat[4] .
                   " penwidth=" . $Ldat[0] . " style=" . $Ldat[1] .
                   ($ShowLinks? " fontsize=" . $Ldat[3] . " label=\"" . $Ldat[2] . "\"": '') . " ];\n");
            $LinkShown[$L['id']]=1;
          } else {
            if ($Neb && $FS['NebScanned'] < $Neb) continue;
            if (isset($FS['ScanLevel']) && $FS['ScanLevel']<2) continue;
            $rand = "B$ul";  // This kludge at least allows both ends to be displayed
            fwrite($Dot,"Unk$ul$rand [label=\"?\" shape=circle];\n");
            fwrite($Dot,"$from -- Unk$ul$rand [color=" . $Ldat[4] .
              " penwidth=" . $Ldat[0] . " style=" . $Ldat[1] .
              ($ShowLinks? " fontsize=" . $Ldat[3] . " label=\"" . $Ldat[2] . "\"": '') . " ];\n");
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
          $Ldat = LinkProps($L);
          fwrite($Dot,$L['System1Ref'] . " -- " . $L['System2Ref'] .
            " [color=" . $Ldat[4] .
            " penwidth=" . $Ldat[0] . " style=" . $Ldat[1] .
            ($ShowLinks? " fontsize=" . $Ldat[3] . " label=\"" . $Ldat[2] . "\"": '') . " ];\n");
         }
      }
    }

//  fwrite($Dot,"}\n");
//  fwrite($Dot,"graph legend {\n");

    $LegendShape = Feature('LegendShape','box');
// var_dump($LegendShape);
    $ls=0;
    foreach ($Factions as $F) {
      if (isset($F['Seen'])) {
        fwrite($Dot,"FF" . $F['id'] . " [shape=$LegendShape style=filled fillcolor=\"" . $F['MapColour'] . '"' .
          NodeLab($F['Name']) . ($typ?" penwidth=2 pos=\"" . $HexLegPos[$ls][0]*$XScale . "," . $HexLegPos[$ls][1] . "!\"" : "") . "];\n");
        $ls++;
      }
    }

    if ($NebF) {
      if ($typ) {
        fwrite($Dot,"Nebulae [shape=$LegendShape style=filled fillcolor=white penwidth=3" .
          ($typ?" pos=\"" . $HexLegPos[$ls][0]*$XScale . "," . $HexLegPos[$ls][1] . "!\"" : "") . "];\n");
      } else {
        fwrite($Dot,"Nebulae [shape=$LegendShape style=filled fillcolor=white penwidth=3];\n");
      }
      $ls++;
    };

    if (!$Faction) {
      if ($Historical) {
        fwrite($Dot,"Other [shape=$LegendShape style=filled fillcolor=white penwidth=2 color=\"CadetBlue\"" . NodeLab("Control","Other") .
          ($typ?" pos=\"" . $HexLegPos[$ls][0]*$XScale . "," . $HexLegPos[$ls][1] . "!\"" : "") . "];\n");
        $ls++;
      }
      if ($OtherInt) {
        fwrite($Dot,"ZZ99 [shape=$LegendShape style=filled fillcolor=yellow Epenwidth=2 " . NodeLab("Interest","Other") .
          ($typ?" pos=\"" . $HexLegPos[$ls][0]*$XScale . "," . $HexLegPos[$ls][1] . "!\"" : "") . "];\n");
        $ls++;
      }
    }

    fwrite($Dot,"}\n");
    fclose($Dot);
  } // Redo Loop


//  echo "<H1>Dot File written</h1>";

  if ($typ) {
    exec("fdp -Tpng -n cache/$GAMEID/Fullmap$Faction$typ.dot > cache/$GAMEID/Fullmap$Faction$typ.png");
    exec("fdp -Tcmapx -n cache/$GAMEID/Fullmap$Faction$typ.dot > cache/$GAMEID/Fullmap$Faction$typ.map");
    //    exec("fdp -Timap -n cache/$GAMEID/Fullmap$Faction$typ.dot > cache/$GAMEID/Fullmap$Faction$typ.imap");
    //    exec("fdp -Tsvg -n cache/$GAMEID/Fullmap$Faction$typ.dot > cache/$GAMEID/Fullmap$Faction$typ.svg");
  } else {
    exec("unflatten cache/$GAMEID/Fullmap$Faction$typ.dot > cache/$GAMEID/f$USERID.dot");
//    exec("dot -Tsvg cache/f.dot > cache/Fullmap$Faction.svg");
    exec("dot -Tpng cache/$GAMEID/f$USERID.dot > cache/$GAMEID/Fullmap$Faction$typ.png");
    exec("dot -Tcmapx cache/$GAMEID/f$USERID.dot > cache/$GAMEID/Fullmap$Faction$typ.map");
  }

//  echo "<h2>dot run</h2>";
  $Rand = rand(1,100000);
  echo "<img src=cache/$GAMEID/Fullmap$Faction$typ.png?$Rand maxwidth=100% usemap='#skmap'>";
  readfile("cache/$GAMEID/Fullmap$Faction$typ.map");

  //  echo "<object type='image/svg+xml' data=cache/$GAMEID/Fullmap$Faction$typ.svg?$Rand maxwidth=100%></object>";
//  echo "<svg type='image/svg+xml' data=cache/$GAMEID/Fullmap$Faction$typ.svg?$Rand maxwidth=100%></svg>";


  dotail();
?>
