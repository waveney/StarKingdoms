<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");

  dostaffhead("Move Things",["js/dropzone.js","css/dropzone.css" ]);

  global $db, $GAME, $GAMEID,$BuildState,$Factions,$Dot;

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


function Node_Show($Fid,$Tid, $Lid, $N, $url='') {
  global $Factions,$Dot;
    $NodeName = $N['Name']?$N['Name']:"";
    $ShortName = $N['ShortName']?$N['ShortName']:$NodeName;
    $Hide = 0;
    if ($Fid) {
      if ($N['Control'] != $Fid) {
        $FS = Get_FactionSystemFS($Fid, $N['id']);
        if (isset($FS['id'])) {
          $Hide = 0;
          if ($FS['ScanLevel'] > 1) {
            if (!empty($FS['Name'])) $ShortName = $NodeName = $FS['Name'];
            if (!empty($FS['ShortName'])) $ShortName = $FS['ShortName'];
          } else {
            $Hide = 1;
          }
        }
      }
    }
    $atts = "";

    $Colour = "white";
    if ($N['Control'] && !$Hide) {
      $Colour = $Factions[$N['Control']]['MapColour'];
      $Factions[$N['Control']]['Seen']=1;
    } else {
      $Colour = "White";
    }

    if ($Hide) $NodeName = '';
    $BdrColour = "Black";

    $atts .= "  shape=box style=filled fillcolor=\"$Colour\" color=$BdrColour";
    if ($NodeName) {
      $atts .= NodeLab($ShortName, $N['Ref']); //($Faction==0?$N['Ref']:""));
    }
    if ($N['Nebulae']) { $atts .= " penwidth=" . (2+$N['Nebulae']*2); $NebF = 1; }
    else { $atts .= " penwidth=2"; }

    if ($url) $atts .= " href=\"$url\" ";

    if ($Lid) $atts .= " href=\"/PThingList.php?ACTION=MOVE&T=$Tid&L=$Lid\" ";
    fwrite($Dot,$N['Ref'] . " [$atts ];\n");
}

  $Force = (isset($_REQUEST['FORCE'])?1:0);

// START HERE
//  var_dump($_REQUEST);
  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
    case 'NEW' :

    case 'None' :
    default:
      break;
    }
  }

  if (isset($_REQUEST['id'])) {
    $Tid = $_REQUEST['id'];
    $T = Get_Thing($Tid);
  } else {
    echo "<h2>No Thing Requested movement</h2>";
    dotail();
  }

  echo "<br>";

  if ($Force) {
    $GM = 0;
  } else {
    $GM = Access('GM');
  }
  $Fid = $T['Whose'];

  $ThingProps = Thing_Type_Props();
  $tprops = $ThingProps[$T['Type']];
  [$Links, $SelLinks, $SelCols ] = Moves_4_Thing($T,1,($tprops & (THING_HAS_GADGETS | THING_CAN_BETRANSPORTED)));

// var_dump($Links,$SelLinks);

  $ThisSys = $T['SystemId'];
  $N = Get_System($ThisSys);

  echo "<h1>Click on the destination to move:</h1><h2> " . $T['Name'] . " - from " . $N['Ref'] . " next turn</h2>";

  $Dot = fopen("cache/$GAMEID/Movemap$Fid.dot","w+");
  if (!$Dot) { echo "Could not create dot file<p>"; dotail(); };

  $Levels = Get_LinkLevels();
  $Factions = Get_Factions();

  fwrite($Dot,"graph skmovemap {\n") ; //size=" . '"8,12!"' . "\n");
  // make make


  // Main node

  $ThisRef = $N['Ref'];

  Node_Show($Fid,$Tid,0,$N,"/PThingList.php?ACTION=CANCELMOVE&T=$Tid");

  // Each link

  foreach ($Links as $L) {
    $Lid = $L['id'];
    $OtherRef = ($L['System1Ref'] == $ThisRef? $L['System2Ref'] :$L['System1Ref'] );
    $ON = Get_SystemR($OtherRef);
    if (strchr($SelLinks[$Lid],'?')) {

//      if ($T['Type'] == 5) continue;
      fwrite($Dot,"$OtherRef [label=\"?\" shape=circle href=\"/PThingList.php?ACTION=MOVE&T=$Tid&L=$Lid\" ] ");
    } else {
      Node_Show($Fid,$Tid, $Lid, $ON);
    }
    fwrite($Dot,$L['System1Ref'] . " -- " . $L['System2Ref'] . ' [color="' . $Levels[abs($L['Level'])]['Colour'] . "\" label=\"#$Lid\" " .
                              (($L['Level'] <0)? ' style=dotted ':'') . " ];\n");
  }

  ///show mini manp click -> PthingList & loc update info

  fwrite($Dot,"}\n");
  fclose($Dot);

  exec("dot -Tpng cache/$GAMEID/Movemap$Fid.dot > cache/$GAMEID/Movemap$Fid.png");
  exec("dot -Tcmapx cache/$GAMEID/Movemap$Fid.dot > cache/$GAMEID/Movemap$Fid.map");

  $Rand = rand(1,100000);
  echo "<img src=cache/$GAMEID/Movemap$Fid.png?$Rand maxwidth=100% usemap='#skmovemap'>";
  readfile("cache/$GAMEID/Movemap$Fid.map");

  if (GameFeature('Follow')) {
    $Eyes = EyesInSystem($Fid,$ThisSys,$Tid);
    $Facts = Get_Factions();
    if ($Eyes) {
      $OtherShips = $db->query("SELECT t.* FROM Things t, ThingTypes tt WHERE t.type=tt.id AND (tt.Properties&0x100)!=0 AND t.SystemId=$ThisSys AND Whose!=$Fid");
      if ($OtherShips) {
        $List = [];
        $Colrs = [];
        $LastWhose = 0;
        while ($Thing = $OtherShips->fetch_array()) {
          $Ttxt = SeeThing($Thing,$LastWhose,$Eyes,$Fid,0,0,0); //$Thing['Name'] type Class, whose SeeThing(&$T,&$LastWhose,$Eyes,$Fid,$Images,$GM=0)
          if ($Ttxt) {
            $List[$Thing['id']] = $Ttxt;
            $Colrs[$Thing['id']] = $Facts[$Thing['Whose']]['MapColour'];
          }
        }
        if ($List) {
          echo "<P><h2>Or Follow:</h2>";
          echo "<form method=post action=PThingList.php?ACTION=FOLLOW&T=$Tid>";
          echo fm_radio('',$List,$_REQUEST,'ToFollow',tabs:0,colours:$Colrs, extra4:' onchange=this.form.submit()');
          //$extra='',$tabs=1,$extra2='',$field2='',$colours=0,$multi=0,$extra3='',$extra4='')
//          echo "<input type=submit value=Follow></form>";
        } else {
          echo "<h2>Nothing to follow</h2>";
        }
      }
    }
  }

  echo "<h2><a href=PThingList.php?ACTION=CANCELMOVE&T=$Tid>Cancel Move Order</a></h2>\n";
  dotail();

?>
