<?php
  include_once("sk.php");
  global $FACTION,$GAMEID,$USER;
  include_once("GetPut.php");
  include_once("PlayerLib.php");
  include_once("ThingLib.php");
  include_once("PThingListCore.php");

  global $ModFormulaes,$ModValues,$Fields,$Tech_Cats,$CivMil,$BuildState,$ThingInstrs,$ThingInclrs,$GAMEID,$LogistCost,$ARMY,$ARMIES,$GAME;
  global $MoveNames,$MoveProps;

  $Fid = 0;
//var_dump($_COOKIE,$_REQUEST);
  A_Check('Player');
  if (Access('Player')) {
    if (!$FACTION) {
      if (!Access('GM') ) Error_Page("Sorry you need to be a GM or a Player to access this");
    } else {
      $Fid = $FACTION['id'];
      $Faction = &$FACTION;
    }
  }
  $GM = (Access('GM') && ! isset($_REQUEST['FORCE'])) ;
  if ($GM) {
    if (isset( $_REQUEST['F'])) {
      $Fid = $_REQUEST['F'];
    } else if (isset( $_REQUEST['f'])) {
      $Fid = $_REQUEST['f'];
    } else if (isset( $_REQUEST['id'])) {
      $Fid = $_REQUEST['id'];
    }
    if (isset($Fid)) $Faction = Get_Faction($Fid);
  } else if (!($Fid ?? 0)) {
    $Fid = $_REQUEST['id'] ?? $_REQUEST['F'] ?? $_REQUEST['f'] ?? 0;
    if (isset($Fid)) $Faction = Get_Faction($Fid);
  }

  if ($GM && isset($_REQUEST['FORCE'])) $GM = 0;

  $TTypes = Get_ThingTypes();

  dostaffhead("Things",["js/ProjectTools.js"]) ;
  /*,
             " onload=ListThingSetup($Fid," . ($GM?1:0) . "," .
    ($GM?($Faction['GMThingType']??0):($Faction['ThingType']??0)) . "," .
             ($GM?($Faction['GMThingBuild']??0):($Faction['ThingBuild']??0)) . ")" );
*/
  if ($GM && $Fid && (($_REQUEST['ACTION']??'')!='OLDER')) {
    echo "<h2><a href=PThingList.php?FORCE>This page in Player Mode</a></h2>";
  }

  if ($GM && isset($Fid) && $Fid==0) {
  } else {
    CheckFaction('PThingList',$Fid);
  }

  function Show_Older($Fid) {
    global $GAME,$GAMEID;
    if ($GAME['Turn'] < Feature('StartOfThingLists',1)) return;
    echo "<div class=floatright><h2>";
    if ($GAME['Turn'] <5 ) {
      echo "End of Older Turns =&gt;";
      for($turn=1; $turn <= $GAME['Turn']; $turn++) {
        if ($turn == ($GAME['Turn'] - 5)) echo "</div><div class=InLine>";
        if (file_exists("Turns/$GAMEID/$turn/ThingList$Fid.html")) echo " <a href=PThingList.php?ACTION=OLDER&Turn=$turn>$turn</a>";
      }
      echo "</h2>";
    } else {
      echo "End of Older Turns =&gt; <div id=ExpandTurnsDots class=InLine><b onclick=ExpandTurns()>...</b></div><div id=HiddenTurns hidden>";
      for($turn=1; $turn <= $GAME['Turn']; $turn++) {
        if ($turn == ($GAME['Turn'] - 5)) echo "</div><div class=InLine>";
        if (file_exists("Turns/$GAMEID/$turn/ThingList$Fid.html")) echo " <a href=PThingList.php?ACTION=OLDER&Turn=$turn>$turn</a>";
      }
      echo "</div></h2>";
    }
    echo "</div>";
  }

  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
    case 'MOVE':
      $Tid = $_REQUEST['T'];
      $Lid = $_REQUEST['L'];
      $T = Get_Thing($Tid);
      $N = Get_System($T['SystemId']);
      $NS = Get_FactionSystemFS($Fid,$T['SystemId']);

      $L = Get_Link($Lid);
      $SYS1 = Get_SystemR($L['System1Ref']);
      $SYS2 = Get_SystemR($L['System2Ref']);
      if ($T['SystemId'] == $SYS1['id']) {
        $T['NewSystemId'] = $SYS2['id'];
        $FSN = $SYS2;
      } else {
        $T['NewSystemId'] = $SYS1['id'];
        $FSN = $SYS1;
      }
      $T['NewLocation'] = 1;

      $T['TargetKnown'] = LinkVis($Fid,$Lid,$T['NewSystemId']);
      $T['LinkId'] = $Lid;
      $LinkTypes = Get_LinkLevels();
      $ThingProps = Thing_Type_Props();
      $tprops = $ThingProps[$T['Type']];

      $ll = ($Lid>>0 ? abs($L['Level']) : 0);
      $LOWho = GameFeature('LinkOwner',0);
// var_dump($Lid,$LinkTypes[$ll]['Cost'],$LOWho,$T['LinkPay']);
      if ($Lid>0 && ($LinkTypes[$ll]['Cost'] > 0) && $LOWho && $LOWho != $T['Whose']) {
        if ($tprops & THING_HAS_GADGETS) {
          $Lc = $LinkTypes[$ll]['AgentCost'];
        } else {
          $Lc = $LinkTypes[$ll]['Cost']*$T['Level'];
        }
        if ($T['LinkPay']==0 || $T['LinkCost'] < $Lc) {
          $LOwner = Get_Faction($LOWho);
          echo "<form method=post action=PThingList.php>";
          echo "<h2>You are taking a <span style='color:" . $LinkTypes[$ll]['Colour'] . "'>" . $LinkTypes[$ll]['Name'] .
            "</span> link do you need to pay " . credit() . "$Lc to " . $LOwner['Name'] . " for this? ";

          echo fm_hidden('LinkCost', $Lc) . fm_hidden('T',$T['id']) . fm_submit("ACTION",'Pay on Turn',0);
          echo "</h2>\n";
          echo "</form>";
        }
        $T['LinkCost'] = $Lc;
      }
      Put_Thing($T);
      if (($TTypes[$T['Type']]['Properties'] & THING_HAS_ARMYMODULES) && ($TTypes[$T['Type']]['Properties'] & THING_CAN_BETRANSPORTED)) {
        $N = Get_System(($T['NewSystemId']?? $T['SystemId']));
        $NewSyslocs = Within_Sys_Locs($N,0,0,0,0,($GM?0:$Tid));
        echo "<form method=post action=PThingList.php?ACTION=FINALLOC&T=$Tid>";
        echo "<h2>Please Select Final Destination within the system:</h2>";
        foreach ($NewSyslocs as $Wsi=>$Loc) {
          if (!$Loc) continue;
          echo "<button method=submit name='FinalLoc' value=$Wsi >$Loc</button><br>";
        }
        dotail();
      } else {
        $CarryNow = Get_Things_Cond_Ordered(0,"GameId=$GAMEID AND LinkId=" . LINK_ON_BOARD . " AND SystemId=$Tid");
//        $CarryTurn = Get_Things_Cond(0,"GameId=$GAMEID AND LinkId=" . LINK_UNLOAD . " AND SystemId=$Tid");
        $CarryOnTurn = Get_Things_Cond_Ordered(0,"GameId=$GAMEID AND LinkId=" . LINK_BOARDING . " AND NewSystemId=$Tid");
//        $CarriedTurn = Get_Things_Cond(0,"GameId=$GAMEID AND LinkId=" . LINK_LOAD_AND_UNLOAD . " AND NewSystemId=$Tid");

        if ($CarryNow || $CarryOnTurn ) {
          $Tot = count($CarryNow) + count($CarryOnTurn);

          echo "<h2>The following will be on board, do you wish to unload at the destination?</h2><table border>";

          if ($CarryNow) {
            foreach ($CarryNow as $Ci=>$Ca) {
              $Ca['Selected'] = 1;
              echo "<tr><td><a href=ThingEdit.php?i=$Ci>" . $Ca['Name'] . "</a><td>" . fm_checkbox('Selected',$Ca,'Selected','',"Selected:$Ci");
            }
          }
          if ($CarryOnTurn) {
            foreach ($CarryOnTurn as $Ci=>$Ca) {
              $Ca['Selected'] = 1;
              echo "<tr><td><a href=ThingEdit.php?i=$Ci>" . $Ca['Name'] . "</a><td>" . fm_checkbox('Selected',$Ca,'Selected','',"Selected:$Ci");
            }
          }
          echo "</table><br><h2>To:</h2>";
          $N = Get_System($T['NewSystemId']);
          $NewSyslocs = Within_Sys_Locs($N,0,0,0,0,($GM?0:$Tid));
          echo "<form method=post action=PThingList.php?ACTION=UNLOADTO&T=$Tid>";
          foreach ($NewSyslocs as $Wsi=>$Loc) {
            if (!$Loc) continue;
            echo "<button method=submit name='FinalLoc' value=$Wsi >$Loc</button><br>";
          }

          echo "</form>";
          echo "<h2><a href=PThingList.php>Leave them aboard</a></h2>";
          dotail();
        }
      }
      break;

    case 'UNLOADTO':
      $Tid = $_REQUEST['T'];
      $T = Get_Thing($Tid);
      $Final = $_REQUEST['FinalLoc'];
      $CarryNow = Get_Things_Cond_Ordered(0,"GameId=$GAMEID AND LinkId=" . LINK_ON_BOARD . " AND SystemId=$Tid");
      $CarryOnTurn = Get_Things_Cond_Ordered(0,"GameId=$GAMEID AND LinkId=" . LINK_BOARDING . " AND NewSystemId=$Tid");

      if ($CarryNow) {
        foreach ($CarryNow as $Ci=>$Ca) {
          if (($_REQUEST["Selected:$Ci"]??'') == 'on') {
            $Ca['LinkId'] = LINK_UNLOAD;
            $Ca['NewSystemId'] = $T['NewSystemId'];
            $Ca['NewLocation'] = $Final;
            Put_Thing($Ca);
          }
        }
      }

      if ($CarryOnTurn) {
        foreach ($CarryNow as $Ci=>$Ca) {
          if ($_REQUEST["Selected:$Ci"] == 'on') {
            $Ca['LinkId'] = LINK_LOAD_AND_UNLOAD;
            $Ca['NewSystemId'] = $T['NewSystemId'];
            $Ca['NewLocation'] = $Final;
            Put_Thing($Ca);
          }
        }
      }
      echo "Unloads recorded<p>";
      dotail();

    case 'CANCELMOVE':
      $Tid = $_REQUEST['T'];
      $T = Get_Thing($Tid);
      $T['LinkId'] = 0;
      Put_Thing($T);
      break;

    case 'NOMOVE':
      $Tid = $_REQUEST['T'];
      $T = Get_Thing($Tid);
      if (($TTypes[$T['Type']]['Properties'] & THING_HAS_ARMYMODULES) && ($TTypes[$T['Type']]['Properties'] & THING_CAN_BETRANSPORTED)) {
        $N = Get_System($T['SystemId']);
        $NewSyslocs = Within_Sys_Locs($N,0,0,0,0,($GM?0:$Tid));
        echo "<form method=post action=PThingList.php?ACTION=FINALLOC&T=$Tid>";
        echo "<h2>Please Select Final Destination within the system:</h2>";
        foreach ($NewSyslocs as $Wsi=>$Loc) {
          if (!$Loc) continue;
          echo "<button method=submit name='FinalLoc' value=$Wsi >$Loc</button><br>";
        }
        dotail();
      }
      break;

    case  'FINALLOC':
      $Tid = $_REQUEST['T'];
      $T = Get_Thing($Tid);
      $T['NewLocation'] = $_REQUEST['FinalLoc'];
      Put_Thing($T);
      break;


    case 'DONOTMOVE' :
      $Tid = $_REQUEST['T'];
      $T = Get_Thing($Tid);
      $T['LinkId'] = LINK_NOT_MOVING;
      Put_Thing($T);
      break;

    case 'Pay on Turn':
      $Tid = $_REQUEST['T'];
      $T = Get_Thing($Tid);
      Check_MyThing($T,$Fid);
      $T['LinkCost'] = $_REQUEST['LinkCost'];
      $T['LinkPay'] = 1;
      Put_Thing($T);
      break;

    case 'SPLAT':
    case 'Splat':
      $Tid = $_REQUEST['T'];
      $T = Get_Thing($Tid);
      $T['CurHealth'] = 0;
      Put_Thing($T);
      break;

    case 'FOLLOW':
      $Tid = $_REQUEST['T'];
      $T = Get_Thing($Tid);
      $Follow = $_REQUEST['ToFollow'] ?? 0;
      if ($Follow) {
        $T['LinkId'] = LINK_FOLLOW;
        $T['NewSystemId'] = $Follow;
        Put_Thing($T);
      }
      break;

    case 'OLDER':
      $Turn = $_REQUEST['Turn'];
      $html =  file_get_contents("Turns/$GAMEID/$Turn/ThingList$Fid.html");
      if (!$html) {
        echo "<h1 class=Err>Save file $Turn not found</h1>";
        dotail();
      }

      Show_Older($Fid);

      echo "<h1>The list of things from the end of Turn $Turn</h1>";
      echo "If the thing is in <span class=blue>blue</span> it is a link to it currently<br>" .
        "If the thing is in <span class=red>red</span> it no longer exists<p>";

      echo preg_replace_callback('/\<a href\=ThingEdit\.php\?id\=(\d*)>(.*?)\<\/a\>/', function ($mtchs) {
          $Tid = $mtchs[1];
          $Name = $mtchs[2];
          $T = Get_Thing($Tid);
          if ($T) {
            return "<a href=ThingEdit.php?id=$Tid>$Name</a>";
          }
          return "<span class=red>$Name</span>";
        }, $html);
      dotail();

      case 'BOARD':
        $Tid = $_REQUEST['T'];
        $T = Get_Thing($Tid);

        $Conflict = 0;
        $Conf = Gen_Select("SELECT W.* FROM ProjectHomes PH, Worlds W WHERE PH.SystemId=" . $T['SystemId'] . " AND W.Home=PH.id AND W.Conflict=1");
        if ($Conf) $Conflict = $Conf[0]['Conflict'];

        if ($Conflict) {
          $T['LinkId'] = LINK_BOARDING;
          $T['NewSystemId'] = $_REQUEST['ToBoard'];
          $X = Get_Thing($T['NewSystemId']);
          echo "Loading on to " . $X['Name'] . " as part of turn processing<br>";
          Put_Thing($T);

        } else {
          $T['LinkId'] = LINK_ON_BOARD;
          $T['SystemId'] = $_REQUEST['ToBoard'];
          $X = Get_Thing($T['SystemId']);
          echo "Loaded on to " . $X['Name'] . "<br>";
          Put_Thing($T);
        }
        break;

    }
  }

  Show_Older($Fid);

  echo PTListCore($Fid,$Faction,$GM,0);
  if (Feature('Designs')) {
    echo "<h2><a href=PlanDesign.php>Plan a Design</a>, <a href=CreateNamed.php>Create a Named Character</a></h2>";
  } else {
    echo "<h2><a href=ThingPlan.php?F=$Fid>Plan a new thing</a></h2>\n";
  }

  dotail();
?>
