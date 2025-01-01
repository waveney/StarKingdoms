<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");

  global $FACTION,$GAMEID,$LinkType,$USERID;

  dostaffhead("Full Map");
 //var_dump($_REQUEST, $_COOKIE);

  $LinkType = Feature('LinkMethod','Gates');
  $Scale = ($_REQUEST['Scale']??1);
  $XScale = Feature('XScale',1)*$Scale;
  $ShowLinks = ($_REQUEST['Links']??1);

  $GM = Access('GM');
  if ($GM) {
    if (isset($_REQUEST['FORCE'])) $GM=0;
    if ($GM) echo "<h2><a href=MapFull.php?Hex&Links=0&FORCE>This page in Player Mode</a></h2>";
  }

  if (!empty($FACTION)) $XScale *= $FACTION['ScaleFactor'];

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

  $Extras = [];

  if ($FACTION) $Faction = $FACTION['id'];
  $Fact = Get_Faction($Faction);

  if ($Faction) {
    // Setup Extras
    $TTypes = Get_ThingTypes();
    $Things = Get_Things_Cond_Ordered($Faction,"BuildState=3");
    foreach ($Things as $Tid => $T) {
      if (!($TTypes[$T['Type']]['Properties'] & THING_HAS_BLUEPRINTS)) continue;
      $TCat = 0;
      $tex = '';
      if ($T['LinkId'] >=0) {
        $sid = $T['SystemId'];
      } else {
        $Carrier = $T['SystemId'];
        $sid = $Things[$Carrier]['SystemId'];
        $tex = " (on " .$Things[$Carrier]['Name'] . ")";
      }
      if (!isset($Extras[$sid])) $Extras[$sid] = ['',0,0];
      if ($TTypes[$T['Type']]['Properties'] & THING_HAS_SHIPMODULES) {
        $Extras[$sid][1]++;
      } else if ($TTypes[$T['Type']]['Properties'] & THING_HAS_ARMYMODULES) {
        $Extras[$sid][2]++;
      } else continue;

      $Extras[$sid][0] .= $T['Name'] . " L" . $T['Level'] . " " . $TTypes[$T['Type']]['Name'] . "$tex\n";
    }


  }

  if (!$GM && $Fact['TurnState'] > 2) Player_Page();

  $typ=Feature('DefaultMapType','Hex');
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
  function LinkPropSet($def,$Conc,$Insta,$Tst) {
    if ($Insta != $Tst) return $Insta;
    if ($Conc != $Tst) return $Conc;
    return $def;
  }

  function LinkProps($L) {
    global $GM,$LinkType,$Levels,$lfs,$InstaLevels;

    $Res = [1,'solid','#' . $L['id'],14,'black'];

    if ($LinkType == 'Wormholes') {
      $EInst = $L['Instability'] + $L['ThisTurnMod'];
      $Res[2] = $L['Name'];
      $Res[0] = LinkPropSet(1,$Levels[$L['Concealment']]['Width'],$InstaLevels[$EInst]['Width'],0);
      $Res[1] = LinkPropSet('solid',$Levels[$L['Concealment']]['Style'],$InstaLevels[$EInst]['Style'],'');
      $Res[4] = LinkPropSet('black',$Levels[$L['Concealment']]['Colour'],$InstaLevels[$EInst]['Colour'],'');
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
  $InstaLevels = Get_LinkInstaLevels();
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
          if (1) {  // ($FS['PassiveScan'] > 0) || ($FS['PassiveNebScan'] > 0)) {
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

      if ($GM && $Faction == 0 && $N['HistoricalControl']) $Factions[$N['HistoricalControl']]['Seen']=1;

      if ($Hide) $NodeName = '';
      $BdrColour = "Black";
      if ($Faction == 0 && $N['HistoricalControl']) {
        $BdrColour = $Factions[$N['HistoricalControl']]['MapColour'];
        $Historical = 1;
      }

      if ($GM || (($FS['ScanLevel']??0)>0)) $atts .= " shape=" .
        ($N['Hospitable']?(($N['Hospitable']>1)?$MultiHabitableShape:$HabitableShape):$InHabitableShape);
      if ($typ) $atts .=
      " pos=\"" . ($N['GridX']*$XScale+(5-$N['GridY'])/2) . "," . (9-$N['GridY'])*$Scale . "!\"";
      $atts .= " style=filled fillcolor=\"$Colour\" color=\"$BdrColour\"";
      if ($NodeName) {
        $atts .= NodeLab($ShortName, $N['Ref']) . " margin=0.03 "; //($Faction==0?$N['Ref']:""));
      }
      if ($N['Nebulae']) { $atts .= " penwidth=" . (2+$N['Nebulae']*2); $NebF = 1; }
      else { $atts .= " penwidth=2"; }

      if ($Faction) {
        $atts .= " href=\"/SurveyReport.php?R=" . $N['Ref'] . '" ';


      if ($Extras[$N['id']]??0) {
        $atts .= " xlabel=<";
        if ($Extras[$N['id']][1]) $atts .= '<font color="red">' . $Extras[$N['id']][1] . ' </font>';
        if ($Extras[$N['id']][2]) $atts .= '<font color="blue">' . $Extras[$N['id']][2] . ' </font>';
        $atts .= '> ';

        $atts .= ' tooltip="' . $Extras[$N['id']][0] . '" ';

      } else if (!empty($FS['Xlabel'])) {
          $atts .= " xlabel=\"" . $FS['Xlabel'] . '" ';
        }
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
            if ($Fl['Known']==0) continue;
            if ($LinkType == 'Gates') { // VERY DUFF
              if ($Neb && $FS['NebScanned'] < $Neb) continue;
              if (isset($FS['ScanLevel']) && $FS['ScanLevel']<2) continue; // Wrong now wont fix unless Gates reused
            } elseif ($LinkType == 'Wormholes') {
              if (($L['Concealment'] > max(0,$FS['SpaceScan'])) && !isset($Fl['id'])) continue;
            } else continue;

            $rand = "B$ul";  // This kludge at least allows both ends to be displayed

            $OL = ($from==$L['System1Ref']?$L['System2Ref']:$L['System1Ref']);
            $NodLab = (Feature('HideUnknownNodes')?'?':$OL);
            fwrite($Dot,"Unk$ul$rand [label=\"$NodLab\" shape=circle margin=0];\n");
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
          NodeLab($F['Name']) . ($typ?" penwidth=2 pos=\"" . $HexLegPos[$ls][0]*$XScale . "," . $HexLegPos[$ls][1]*$Scale . "!\"" : "") . "];\n");
        $ls++;
      }
    }

    if ($NebF) {
      if ($typ) {
        fwrite($Dot,"Nebulae [shape=$LegendShape style=filled fillcolor=white penwidth=3" .
          ($typ?" pos=\"" . $HexLegPos[$ls][0]*$XScale . "," . $HexLegPos[$ls][1]*$Scale . "!\"" : "") . "];\n");
      } else {
        fwrite($Dot,"Nebulae [shape=$LegendShape style=filled fillcolor=white penwidth=3];\n");
      }
      $ls++;
    };

    if (!$Faction) {
      if ($Historical) {
        fwrite($Dot,"Other [shape=$LegendShape style=filled fillcolor=white penwidth=2 color=\"CadetBlue\"" . NodeLab("Control","Other") .
          ($typ?" pos=\"" . $HexLegPos[$ls][0]*$XScale . "," . $HexLegPos[$ls][1]*$Scale . "!\"" : "") . "];\n");
        $ls++;
      }
      if ($OtherInt) {
        fwrite($Dot,"ZZ99 [shape=$LegendShape style=filled fillcolor=yellow Epenwidth=2 " . NodeLab("Interest","Other") .
          ($typ?" pos=\"" . $HexLegPos[$ls][0]*$XScale . "," . $HexLegPos[$ls][1]*$Scale . "!\"" : "") . "];\n");
        $ls++;
      }
    }

    $MapDirStuff = Feature('MapDirections');
    if ($MapDirStuff) {
      [$Dx,$Dy,$Dl,$DN,$DE,$DS,$DW] = explode(',',$MapDirStuff); // X,Y,Len,North,East,South,West
      $Ds = [[$Dx,$Dy,$Dx,$Dy+$Dl*.7,$DN],[$Dx,$Dy,$Dx+$Dl,$Dy,$DE],[$Dx,$Dy,$Dx,$Dy-$Dl*.7,$DS],[$Dx,$Dy,$Dx-$Dl,$Dy,$DW]];

      fwrite($Dot,"Direct [shape=point pos=\"$Dx,$Dy!\"];\n");
      foreach($Ds as $i=>$D) {
        fwrite($Dot, "Direct$i [ pos=\"" . $D[2]. "," . $D[3] . "!\" label=" . $D[4] . " margin=0 fontsize=10];\n");
        fwrite($Dot, "Direct -- Direct$i [penwidth=2 dir=forward arrowsize=1 ];\n");
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
