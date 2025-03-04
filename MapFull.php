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

  if (isset($_REQUEST['f'])) {
    $Fid = $_REQUEST['f'];
  } else   if (isset($_REQUEST['F'])) {
    $Fid = $_REQUEST['F'];
  } else if (isset($_REQUEST['f1'])) {
    $Fid = $_REQUEST['f1'];
  } else {
    $Fid = 0;
  }
  if (($Fid ==0) && $FACTION) {
    $Fid = $FACTION['id'];
  }

  if ($Fid) $Fact = Get_Faction($Fid);

  $GM = Access('GM');
  if ($GM) {
    if (isset($_REQUEST['FORCE'])) $GM=0;
    if ($GM && $Fid) echo "<h2><a href=MapFull.php?Hex&Links=0&FORCE>This page in Player Mode</a></h2>";
  } else {
    if ($Fact['TurnState'] > 2) Player_Page();
  }

  if (!empty($FACTION)) $XScale *= $FACTION['ScaleFactor'];

  $CatCols = ["white","grey", "Yellow"];
  $HexLegPos = [];
  eval("\$HexLegPos=" . Feature('LegPos','[[0,0]]') . ";" );
// var_dump($HexLegPos);exit;

  $Extras = [];
  $AllLinks = 0;

  if ($Fid) {
    $AllLinks = Has_Tech($Fid,'Know All Links');
    // Setup Extras
    $TTypes = Get_ThingTypes();
    $Things = Get_Things_Cond_Ordered($Fid,"BuildState=3");
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

    if (Feature('ShowAnomsOnMap')) {
      $FAs = Gen_Get_Cond('FactionAnomaly',"FactionId=$Fid AND (State=1 OR State=2)");
      foreach($FAs as $FA) {
        $A = Get_Anomaly($FA['AnomalyId']);
        $Extras[$A['SystemId']][3] = 1;
      }
    }

    $LUsed = Get_LinksUsed($Fid);
    $FSs = Get_FactionSystemsF($Fid);
  }


  $typ=Feature('DefaultMapType','Hex');
  if (isset($_REQUEST['Hex'])) {
    if ($GM) {
      $typ = 'Hex';
    } elseif (Access('Player')) {
      if (Has_Tech($Fid,'Astral Mapping') || Feature('MapsinHex')) {
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
      $Levels[$L['Concealment']]['Used']++;
      $InstaLevels[$EInst]['Used']++;
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

  global $db, $GAME,$GAMEID;

  $HexLegPos = [];
  eval("\$HexLegPos=" . Feature('LegPos','[[0,0]]') . ";" );
//var_dump($HexLegPos);
  $RedoMap = 1;

  $Nodes = Get_Systems();
  $BackR = [];
  $PlanetTypes = Get_PlanetTypes();
  foreach($Nodes as $ref=>$N){
    $BackR[$ref] = $Sid = $N['id'];
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
  foreach ($Levels as $i=>$L) {$Levels[$i]['Used'] = 0;}
  foreach ($InstaLevels as $i=>$L) {$InstaLevels[$i]['Used'] = 0;}
  // var_dump($Levels);
  $Factions = Get_Factions();
  $Historical = 0;
  $OtherInt = 0;
  $HabitableShape = Feature('HabitableShape','box');
  $InHabitableShape = Feature('InHabitableShape','box');
  $MultiHabitableShape = Feature('MultiHabitableShape',$HabitableShape);

  while ($RedoMap) {
    $Dot = fopen("cache/$GAMEID/Fullmap$Fid$typ.dot","w+");
    if (!$Dot) { echo "Could not create dot file<p>"; dotail(); };
//  ftruncate($Dot,0);  // Needed for redoing logic

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
      if ($Fid) {
        $FS = ($FSs[$N['id']]??[]);
        if (!$FS && !$AllLinks) continue;
        if ($N['Control'] != $Fid) {
          if ($FS['Name']) $ShortName = $NodeName = $FS['Name'];
          if ($FS['ShortName']) $ShortName = $FS['ShortName'];
        }
      }
      $atts = "";

      $Colour = "white";
      if ($N['Control'] && !$Hide) {
        $Colour = $Factions[$N['Control']]['MapColour'];
        $Factions[$N['Control']]['Seen']=1;
      } else if ($N['Category']) {
        $Colour = ($Fid?"White":$CatCols[$N['Category']]);
        if ($Colour == 'Yellow') $OtherInt = 1;
      } else {
        $Colour = "White";
      }

      if ($GM && $Fid == 0 && $N['HistoricalControl']) $Factions[$N['HistoricalControl']]['Seen']=1;

      if ($Hide) $NodeName = '';
      $BdrColour = "Black";
      if ($Fid == 0 && $N['HistoricalControl']) {
        $BdrColour = $Factions[$N['HistoricalControl']]['MapColour'];
        $Historical = 1;
      } else if ($AllLinks) {
        if ($N['Control'] == $Fid || $N['HistoricalControl']==$Fid) {
          $Colour = $Factions[$Fid]['MapColour'];
        } else {
          $Colour = 'White';
        }
      }

      if ($GM || (($FS['ScanLevel']??0)>=0)) $atts .= " shape=" .
        ($N['Hospitable']?(($N['Hospitable']>1)?$MultiHabitableShape:$HabitableShape):$InHabitableShape);
      if ($typ) $atts .=
        " pos=\"" . ($N['GridX']*$XScale+(5-$N['GridY'])/2) . "," . (9-$N['GridY'])*$Scale . "!\"";
      $atts .= " style=filled fillcolor=\"$Colour\" color=\"$BdrColour\"";
      if ($NodeName) {
        $atts .= NodeLab($ShortName, $N['Ref']);
      }
      $atts .= " margin=" . ($N['Hospitable']?'0':'0.03');

      if ($N['Nebulae']) { $atts .= " penwidth=" . (2+$N['Nebulae']*2); $NebF = 1; }
      else { $atts .= " penwidth=2"; }

      if ($Fid) {
        $atts .= " href=\"/SurveyReport.php?R=" . $N['Ref'] . '" ';


      if ($Extras[$N['id']]??0) {
        $atts .= " xlabel=<";
        if ($Extras[$N['id']][1]??0) $atts .= '<font color="red">' . $Extras[$N['id']][1] . ' </font>';
        if ($Extras[$N['id']][2]??0) $atts .= '<font color="blue">' . $Extras[$N['id']][2] . ' </font>';
        if ($Extras[$N['id']][3]??0) $atts .= '<font color="darkgreen">A</font>';
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


    if ($Fid && !$AllLinks) {
      $ul = 1;
      foreach($Nodes as $N) {
        $from = $N['Ref'];

        $FS = $FSs[$N['id']]??[];

        $Links = Get_Links($from);
        if (!isset( $ShownNodes[$N['Ref']])) continue;
        $Neb = $N['Nebulae'];
        foreach ($Links as $L) {
          $Lid = $L['id'];
          if (isset($LinkShown[$Lid])) continue;
          if ($N['Ref'] == $L['System1Ref']) {
            $OSid = $BackR[$L['System2Ref']];
            $to = $L['System2Ref'];
          } else {
            $OSid = $BackR[$L['System1Ref']];
            $to = $L['System1Ref'];
          }

          $Ways = 0;

          if (isset($LUsed[$Lid]) || $AllLinks || ((($FS['ScanLevel']??-1)>=0) && ($L['Concealment'] == 0) && isset($FSs[$OSid]))) $Ways = 3;
          if (($Ways == 0) && (($FS['ScanLevel']??-1)>=0) && (($L['Concealment'] == 0) || ($FS['SpaceScan'] >= $L['Concealment']))) $Ways = 1;
          if (($Ways < 3) && isset($FSs[$OSid]) && ($FSs[$OSid]['SpaceScan'] >= $L['Concealment']))  $Ways +=2;

          if ($Ways == 0) continue; // Not Known

          $Ldat = LinkProps($L);

          $Arrow = '';
//  if ($Lid == 156)        var_dump($Lid,$from,$to,$Ways,$AllLinks,($LUsed[$Lid]??0),$FS,$L);
          switch ($Ways) {
            case 0: continue 2; // Not Known at all
            case 1: // From here may be unknown other end
              if (!isset($FSs[$OSid])) {
                $rand = "B$ul";  // This kludge at least allows both ends to be displayed
                $NodLab = (Feature('HideUnknownNodes')?'?':$to);
                $to = "Unk$ul$rand";
                fwrite($Dot, "$to [label=\"$NodLab\" shape=circle margin=0];\n");
                $ul++;
              }
              $Arrow = " dir=forward arrowsize=1 ";
              break;
            case 2: // To Here
              $Arrow = " dir=back arrowsize=1 ";
              break;
            case 3: // Both Ways (nothing special needed)
          }
          fwrite($Dot,"$from -- $to [color=" . $Ldat[4] .
                 " penwidth=" . $Ldat[0] . " style=" . $Ldat[1] .
                 $Arrow .
                 ($ShowLinks? " fontsize=" . $Ldat[3] . " label=\"" . $Ldat[2] . "\"": '') . " ];\n");
          $LinkShown[$L['id']]=1;
        }
      }
    } else { // GM mode
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

    if (!$Fid) {
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

/* This works as a method but is very crude
    $ConcealStuff = Feature('MapConceal3');
    if ($ConcealStuff) {
      $Conc = explode(',',$ConcealStuff);
      $Dx = array_shift($Conc);
      $Dy = array_shift($Conc);
      $Dz = array_shift($Conc);
      $Dadd = array_shift($Conc);
      foreach($Conc as $i=>$C) {
        [$W,$txt] = explode('=',$C,2);
        $DD = $Dy - $i*$Dadd;
        fwrite($Dot, "ConcealA$i [ pos=\"$Dx,$DD!\" label=\"\" shape=point margin=0 penwidth=0];\n");
        fwrite($Dot, "ConcealB$i [ pos=\"$Dz,$DD!\" label=\"\" shape=point margin=0 penwidth=0 ];\n");
        fwrite($Dot, "ConcealA$i -- ConcealB$i [ penwidth=$W  xlabel=\"    $txt\" fontsize=10];\n");
      }
    } */

    $Fudge = Feature('MapFudges','0.25,.3,3,1,10,1,2');

    [$Fht,$FDn,$FLns,$Fll,$Ffnt,$Fcmw,$Fimw] = explode(',',$Fudge );

    $PCount = 0;
    foreach ($Levels as $i=>$L) {
      if ($L['Used']>0) {
        $DX1 = $HexLegPos[$ls][0]*$XScale -($Fll/2);
        $DX2 = $DX1+$Fll;
        $DY = $HexLegPos[$ls][1]*$Scale - $Fht*$PCount +$FDn;
        fwrite($Dot, "ConcealA$i [ pos=\"$DX1,$DY!\" label=\"\" shape=point margin=0 penwidth=0 ];\n");
        fwrite($Dot, "ConcealB$i [ pos=\"$DX2,$DY!\" label=\"\" shape=point margin=0 penwidth=0 ];\n");
        fwrite($Dot, "ConcealA$i -- ConcealB$i [ color=" . ($L['Colour']?$L['Colour']:'black') . " penwidth=" . max($L['Width'],$Fcmw) .
          " style=" . ($L['Style']?$L['Style']:'Solid') . " label=\"Conceal " . $L['Level'] . "\" fontsize=$Ffnt];\n");
        if (++$PCount == $FLns) {
          $PCount = 0;
          $ls++;
        }

      }
    }

    if ($PCount) $ls++;
    $PCount = 0;

    foreach ($InstaLevels as $i=>$L) {
      if ($L['Used']>0) {
        $DX1 = $HexLegPos[$ls][0]*$XScale -($Fll/2);
        $DX2 = $DX1+$Fll;
        $DY = $HexLegPos[$ls][1]*$Scale - $Fht*$PCount +$FDn;
        fwrite($Dot, "InstaA$i [ pos=\"$DX1,$DY!\" label=\"\" shape=point margin=0 penwidth=0 ];\n");
        fwrite($Dot, "InstaB$i [ pos=\"$DX2,$DY!\" label=\"\" shape=point margin=0 penwidth=0 ];\n");
        fwrite($Dot, "InstaA$i -- InstaB$i [ color=" . ($L['Colour']?$L['Colour']:'black') . " penwidth=" . max($L['Width'],$Fimw) .
          " style=" . ($L['Style']?$L['Style']:'Solid') . " label=\"Instability " . $L['Instability'] . "\" fontsize=$Ffnt];\n");
        if (++$PCount == $FLns) {
          $PCount = 0;
          $ls++;
        }

      }
    }


    fwrite($Dot,"}\n");
    fclose($Dot);
  } // Redo Loop


//  echo "<H1>Dot File written</h1>";

  if ($typ) {
    exec("fdp -Tpng -n cache/$GAMEID/Fullmap$Fid$typ.dot > cache/$GAMEID/Fullmap$Fid$typ.png");
    exec("fdp -Tcmapx -n cache/$GAMEID/Fullmap$Fid$typ.dot > cache/$GAMEID/Fullmap$Fid$typ.map");
  } else {
    exec("unflatten cache/$GAMEID/Fullmap$Fid$typ.dot > cache/$GAMEID/f$USERID.dot");
    exec("dot -Tpng cache/$GAMEID/f$USERID.dot > cache/$GAMEID/Fullmap$Fid$typ.png");
    exec("dot -Tcmapx cache/$GAMEID/f$USERID.dot > cache/$GAMEID/Fullmap$Fid$typ.map");
  }

//  echo "<h2>dot run</h2>";
  $Rand = rand(1,100000);
  echo "<img src=cache/$GAMEID/Fullmap$Fid$typ.png?$Rand maxwidth=100% usemap='#skmap'>";
  readfile("cache/$GAMEID/Fullmap$Fid$typ.map");


  dotail();
?>
