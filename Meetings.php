<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("PlayerLib.php");
  include_once("SystemLib.php");
  include_once("ProjLib.php");
  include_once("HomesLib.php");
  include_once("TurnTools.php");
  include_once("OrgLib.php");
  A_Check('GM');

  global $Relations,$GAME, $GAMEID;

  $FactFact = [];

/* Get all systems and Factions

  Go through each Home - record systems

  Go through each Things - record systems

  For each system with more than 1 faction - report (Embassy?) - simple list and details button - details == WWhatCanIC
*/

  if (isset($_REQUEST['CSV'])) {
    $Sid = $_REQUEST['S'];
    $N = Get_System($Sid);
    $filename = "SK:$GAMEID:" .$GAME['Turn'] . ':' . $N['Ref'] . ':' . rand(1,1000);
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename=$filename.csv");

    // create a file pointer connected to the output stream
    $CSV = fopen('php://output', 'w');
    $CSVHead = 0;

  } else {
    dostaffhead("Meetups");
    $CSV = 0;
  }

  $Sys = Get_Systems();
  $Sids = [];
  $Facts = Get_Factions();

  $Homes = Get_ProjectHomes();
  $TTypes = Get_ThingTypes();
  $ModTypes = Get_ModuleTypes();
  $Techs = Get_Techs();
  $ThingProps = Thing_Type_Props();
  $DevTotal = 0;


  //  var_dump($_REQUEST);

  function Set_Excludes() {
    Global $Excludes,$ExcludeList,$Battle;
    $ExcludeList = explode(',',$Battle['Excludes']);
    $Excludes = [];
    if ($ExcludeList) {
      foreach ($ExcludeList as $Ex) if ($Ex) $Excludes[$Ex] = 1 ;
    }
  }

  function Save_Excludes() {
    Global $Excludes,$ExcludeList,$Battle;
    if ($Excludes) {
      $Battle['Excludes'] = implode(',',array_keys($Excludes));
    } else {
      $Battle['Excludes'] = '';
    }
    Gen_Put('MeetupTurn',$Battle);
  }

  function Get_Battle($Sid) {
    global $Battle, $GAME, $GAMEID;
    static $BatLoc;
    if ($BatLoc == $Sid) return;
    $Battle = Gen_Get_Cond1('MeetupTurn',"GameId=$GAMEID AND Turn=" . $GAME['Turn'] . " AND SystemId=$Sid");
    $BatLoc = $Sid;
  }

function ForceReport($Sid,$Cat) {
  global $Facts, $Homes, $TTypes, $ModTypes, $N, $Techs, $ThingProps,$ARMY,$DevTotal,$Battle,$Excludes,$CSV,$CSVHead;
/*
  $Things = Get_Things_Cond(0,
    "SystemId=$Sid AND ( BuildState=" . BS_SERVICE . " OR BuildState=" . BS_COMPLETE . ") AND (LinkId>=0 OR LinkId=" . LINK_NOT_MOVING .
    ") ORDER BY Whose, Type, Level, Class");*/

  $Things = Gen_Select_Ordered("SELECT t.* FROM Things t LEFT JOIN ThingTypes tt ON t.Type=tt.id WHERE SystemId=$Sid AND ( BuildState=" . BS_SERVICE .
    " OR BuildState=" . BS_COMPLETE . ") AND (LinkId>=0 OR LinkId=" . LINK_NOT_MOVING .
    ") ORDER BY Whose, tt.Priority DESC, Level DESC, Class, Name");
  $LastF = $Home = $Control = -1;
  $txt = $ftxt = $htxt = $Battct = $rpt = '';
  $TMsk = ($Cat=='G'?1:2);
  $PlanMoon = [];
  $FirePower = $Wid = $Bat = 0;
  $HomeType = "Unknown";
  $Variants = Gen_Get_All('Variants');
  $VarIndex = NamesList($Variants);

  Set_Excludes();

  if ($Cat == 'G') {
    $PTD = Get_PlanetTypes();  // ONLY works for single target at present
    $Planets = Get_Planets($Sid);
    foreach($Planets as $Pl) {
      if ($PTD[$Pl['Type']]['Hospitable']) {
        $PlanMoon = $Pl;
        $HomeType = $PTD[$Pl['Type']]['Name'];
        $Control = (!empty($Pl['Control']) ? $Pl['Control'] : $N['Control']);
        foreach ($Homes as $H) if (($H['ThingType'] == 1) && ($H['ThingId'] == $Pl['id'])) {
          $Home = $H;
          $World = Gen_Get_Cond1('Worlds',"ThingType=" . $H['ThingType'] . " AND ThingId=" . $H['ThingId']);
          if (isset($World['id'])) $Wid = $World['id'];
          break 2;
        }
        echo "Home not found";
        $Home = $Wid = 0;
        break; // CHANGE For Multi targets
      }
    }
  }

  if ($CSV) {
    if ($CSVHead == 0) {
      fputcsv($CSV,['Who','Name','Type','Level','Evasion','Cur Health','Orig Health','Firepower','To Hit','Variant','Mobility/Speed']);
      $CSVHead = 1;
    }

  } else {
    echo "<h2>" . ($Cat =='G' ?'Ground':'Space') . " Force Report for " . $N['Ref'] . ($Cat =='G' ? " - " . ($PlanMoon['Name'] ?? 'Nameless') .
         " ($HomeType)" :'') . "</h2>\n";
    if ($Cat =='G' && $Wid) {
      echo "<h2><a href=WorldEdit.php?ACTION=MilitiaDeploy&id=$Wid>Deploy Militia</a></h2>";
      $MilOrgB = Gen_Get_Cond('Branches',"HostType=" . $H['ThingType'] . " AND HostId=" . $H['ThingId'] . " AND (OrgType=3 OR OrgType2=3)");
      $MilOrgO = Gen_Get_Cond('Offices',"World=$Wid AND (OrgType=3 OR OrgType2=3)");
      if ($MilOrgB || $MilOrgO) {
        echo "<h2><a href=BranchEdit.php?Action=SPAWN_HS&Hid=" . $H['ThingId'] . "&Sid=$Sid&Wid=$Wid&Hyp=" . $H['ThingType'] .
          ">Deploy Heavy Security</a></h2>";
      }
    } else if ($Cat == 'S') {
      $Outpost = Outpost_In($Sid,0,0);
      if ($Outpost) {
        $MilOrg = Gen_Get_Cond('Branches',"HostType=3 AND HostId=" . $Outpost['id'] . " AND (OrgType=3 OR OrgType2=3)");
        if ($MilOrg) {
          echo "<h2><a href=BranchEdit.php?Action=SPAWN_FS&Oid=" . $Outpost['id'] . "&Sid=$Sid>Deploy Defensive Fighters</a></h2>";
        }

      }
    }

    echo "<table border>";
    echo "<tr><td>What<td>Type<td>Level<td>Evasion<td>Health<td>Attack<td>To Hit<td>" . (($Cat == 'S')?'Speed':'Mobility') . "<td>Actions<td>Return /<br>Exclude\n";
  }

  $Kaiju = $KaijuL = 0;
  $FactLvls = $Movement = 0;

  foreach($Things as $T) {
    $Tid = $T['id'];

    if ($Excludes[$Tid] ??0) continue;
    $Ground = is_on_ground($T);
//    if ($Tid==2517) var_dump($Ground,$Cat);

    if ((($Cat == 'S') && !$Ground) || (($Cat == 'G') && $Ground)) {

 //   if ((($Cat == 'S') && ((($TTypes[$T['Type']]['Properties']??0) & 8) != 0)) ||
 //       (($Cat == 'G') && ((($TTypes[$T['Type']]['Properties']??0) & 0x20) != 0))) {
      if (($T['CurHealth'] == 0) && ($T['Type'] == 20)) continue; // Skip Militia at zero
      if ($T['PrisonerOf'] != 0) continue; // Prisoners
      if ($LastF != $T['Whose']) {
        if (($CSV==0) && ($LastF >=0)) {
          echo $htxt;
          if ($Bat) echo "Battle Tactics: Effectively " . ($Kaiju?$KaijuL:$Bat) . " ( $Battct ) <br>";
          echo  $ftxt. "<br>Total Firepower: $FirePower";

          echo "<br>Average " . (($Cat == 'S')?'Speed':'Mobility') . ": $Movement / $FactLvls = " . ceil($Movement / max($FactLvls,1));
          echo $txt;
        }
        $BD = $Bat = 0;
        $FactLvls = $Movement = 0;

        $LastF = $T['Whose'];
        $FirePower = 0;
        $htxt = "<tr><td colspan=9 " . FactColours($LastF,'bisque') . "><h2>" .
          ($Facts[$LastF]['Name']??'Independant') . "</h2><td>" . fm_checkbox('All',$_REQUEST,"ExcludeFact:$LastF"," onchange=BattleExclude($LastF)")
          . "<tr><td colspan=9>";
        $txt = $Battct = $ftxt = '';
        $txt .= "<br>Damage recieved: <span id=DamTot$Cat$LastF>0</span>";

        $Kaiju = Has_Trait($LastF,'Giant Kaiju')?1:0;
        $KaijuL = 0;
        $FTs = Get_Faction_Techs($LastF);
//        if ($Tid==2517) var_dump("120");

        foreach ($FTs as $FT) {
          if (empty($FT['Tech_Id']) || empty($Techs[$FT['Tech_Id']])) continue;

          $Tech = $Techs[$FT['Tech_Id']];
          if ($Tech['Properties'] & $TMsk) {
            switch ($Tech['Name']) {
            case 'Battle Tactics':
              $Bat += $FT['Level'];
              $Battct .= " Base: " . $FT['Level'];
              continue 2;

            case 'Combined Arms':
              $Bat += 1;
              $Battct .= ", Combined Arms +1 ";
              break;

            case 'Battlefield Intelligence':
              $Agents = Get_Things_Cond($LastF," Type=5 AND Class='Military' AND SystemId=" . $T['SystemId'] . " ORDER BY Level DESC");
              if ($Agents) {
                $Bi = ($Agents[0]['Level']/2);
                $Bat += $Bi;
                $Battct .= ", Battlefield Intelligence +$Bi ";
              }
              break;

            case "$ARMY Tactics - Arctic":
            case "$ARMY Tactics - Desert":
            case "$ARMY Tactics - Desolate":
            case "$ARMY Tactics - Temperate":
              if (strstr($Tech['Name'],$HomeType)) {
                $Bat += 1;
                $Battct .= ", " . $Tech['Name'] . " +1 ";
                break;
              }
              continue 2;

            default:

            }
            $ftxt .= $Tech['Name'] . (($Tech['Cat'] == 0)? ( " Level: " . $FT['Level']) : '') . "<br>\n";
          }
        }
      }
//      if ($Tid==2517) var_dump("165");

      $txt .= "<tr><td><a href=ThingEdit.php?id=" . $T['id'] . ">" . $T['Name'] . "</a>";

      $Mods = Get_Modules($T['id']);
      foreach($Mods as $M) {
        if ($ModTypes[$M['Type']]['Leveled'] & MOD_LEVELED) {
          $txt .= "<br>" . $ModTypes[$M['Type']]['Name'] . (($ModTypes[$M['Type']]['Leveled'] & MOD_LEVELED)?" Lvl:" .
            $M['Level'] :'') . " Num: " . $M['Number'] . "\n";
        }
      }
      $Resc = 0;
      [$BD,$ToHit] = Calc_Damage($T,$Resc);
      $tprops = $ThingProps[$T['Type']];
      if (($Cat == 'G') && ($TTypes[$T['Type']]['Name'] != 'Militia') && (($VarIndex[$T['Variant']]??'') != 'Precision') && $BD>0) {
        $DevTotal += $T['Level'];
        if (($VarIndex[$T['Variant']]??'') == 'Artillery') $DevTotal += $T['Level'];
      }


      if ($Kaiju) {
        if (str_contains($T['Class'], 'Kaiju')) {
          $KaijuL = max($KaijuL,$T['Level']);
        } else {
          $KaijuL = $Bat;
        }
      }

      if ($CSV) {
        fputcsv($CSV,[($Facts[$LastF]['Name']??'Unknown'), $T['Name'], $TTypes[$T['Type']]['Name'], $T['Level'], $T['Evasion'],
          $T['CurHealth'], $T['OrigHealth'], $BD, $T['ToHitBonus'], ($Variants[$T['Variant']]['Name']??''),
          (($TTypes[$T['Type']]['Properties'] & THING_HAS_ARMYMODULES)?$T['Mobility']:$T['Speed'])]
          );

      } else {
        $txt .= fm_hidden("OrigData$Tid",implode(":",[$T['CurHealth'], $T['OrigHealth'],0,$T['CurShield'],$T['ShieldPoints']]));
        $txt .= "<td>" . $TTypes[$T['Type']]['Name'] . "<td>" . $T['Level'] . "<td>" . $T['Evasion'];
        $txt .= "<td><span id=StateOf$Tid>" . $T['CurHealth'] . " / " . $T['OrigHealth'];
        if ($T['ShieldPoints']) $txt .= " (" . $T['CurShield'] . "/" . $T['ShieldPoints'] . ") ";
        $txt .= "</span><td><span id=Attack$Tid>$BD</span><td>" . $T['ToHitBonus'] . "<td>";
        if (($TTypes[$T['Type']]['Properties'] & THING_CAN_MOVE) || ($TTypes[$T['Type']]['Prop2'] & THING_HAS_SPEED)) {
          $FactLvls += $T['Level'];
          if ($TTypes[$T['Type']]['Properties'] & THING_HAS_ARMYMODULES) {
            $txt .= "Mobility: " . sprintf("%0.3g ",$T['Mobility']);
            $Movement += ceil($T['Mobility'])*$T['Level'];
          } else {
            $txt .= "Speed: " . sprintf("%0.3g ",$T['Speed']);
            $Movement += ceil($T['Speed'])*$T['Level'];
          }
        }
        $txt .=  fm_number1(" Do",$T,'Damage', ''," class=Num3 onchange=Do_Damage($Tid,$LastF,'$Cat')","Damage:$Tid") . " damage";
        if ($TTypes[$T['Type']]['Properties'] & THING_CAN_MOVE ) $txt .= fm_checkbox(', Retreat?',$T,'RetreatMe','',"RetreatMe:$Tid");
        if ($TTypes[$T['Type']]['Properties'] & THING_LEAVES_DEBRIS ) $txt .= fm_checkbox(', No Debris?',$T,'NoDebris','',"NoDebris:$Tid");

        $txt .= "<td>" . fm_checkbox('',$T,'Exclude','',"Exclude:$LastF:$Tid class=Exclude$LastF");
        $FirePower += $BD;
      }
    } else {
    }

  }
  if (($CSV == 0)  && $htxt) {
    echo $htxt;
    if ($Bat) echo "Battle Tactics: Effectively " . ($Kaiju?$KaijuL:$Bat) . " ( $Battct ) <br>";
    echo  $ftxt. "<br>Total Firepower: $FirePower";

    echo "<br>Average " . (($Cat == 'S')?'Speed':'Mobility') . ": $Movement / $FactLvls = " . ceil($Movement / max($FactLvls,1));
    echo $txt;
  }
//  var_dump($txt,$htxt);
  if (!$CSV) echo "</table>";
}

function SystemSee($Sid) {
  global $DevTotal,$Battle,$GAMEID,$GAME,$Excludes,$CSV;
 // echo "<form>";

  if (!$CSV) {
    $txt = SeeInSystem($Sid,31,1,0,-1,1);

    echo $txt;

    echo "</form><p><hr><h1>To Run Combat do the following in order:</h1>";
    echo "<ol><li>Make sure you have unloaded troops, and deployed any Militia and Mil Org Forces<p>";
    echo "<li>Look through the force report and mark to return any Militia/Mil Org Forces, and tick the Exclude from Battle boxes" .
          " - if you have done any of these click the Remove forces button<p>";
    echo "<li>For Ground combat click the 'Devastation' button<p>";
    echo "<li>Then and only the Export the data to the combat program<p>";
    echo "<li>Run the fight and apply damage and/or set retreat, when happy click the 'Do All Damage' Button<p>";
    echo "<li>After the fight tick the appropriate Fight done box on the system lists</ol>";


    echo "<form method=post action=Meetings.php?ACTION=Check&S=$Sid onkeydown=\"return event.key != 'Enter';\">";

    $_REQUEST['IgnoreShield'] = 0;
    echo "<h2>" . fm_checkbox('Bypass shields - (eg missiles)',$_REQUEST,'IgnoreShield') . "</h2><p>";
    $mtch = [];
    if (preg_match('/<div class=FullD hidden>/',$txt,$mtch)) {
      echo "<button class='floatright FullD' onclick=\"($('.FullD').toggle())\">Show Remains of Things and Named Characters</button>";
    }
  }

  Get_Battle($Sid);
  Set_Excludes();

  // var_dump($Battle, $Excludes);
  //      Register_AutoUpdate('Meetings',$Sid);

  ForceReport($Sid,'G');
  ForceReport($Sid,'S');

  if ($CSV) {
    fclose($CSV);
    exit;
  }
  echo fm_submit("ACTION",'Do ALL Damage',0);
  if ($DevTotal) {
    $Sys = Get_System($Sid);

    $Worlds = explode(',',$Sys['WorldList']);

    if ($Worlds) {
      if (count($Worlds) == 1) {
        $Wid = $Worlds[0];
        if ($Wid >0) {
          $WH = Get_Planet($Wid);
        } else {
          $WH = Get_Moon(-$Wid);
        }

        $Wtxt = fm_hidden('Place',$Wid) . $WH['Name'];
      } else {
        $Places = $Data = [];
        $Place = 0;
        foreach ($Worlds as $Wid) {
          if ($Wid >0) {
            $WH = Get_Planet($Wid);
          } else {
            $WH = Get_Moon(-$Wid);
          }
          if ($Place ==0) $Place = $Wid;
          $Data['Place'] = $Place;
          $Places[$Wid] = $WH['Name'];
        }

        $Wtxt = fm_radio('World',$Places,$Data,'Place');
      }
      $Devo = intdiv($DevTotal + rand(0,9),10);
      $Dev = ['Devastate'=>$Devo];
      echo "<br>Total Ground Force Levels: <b>$DevTotal</b> - Do " . fm_number1('',$Dev,'Devastate','', ' min=0 max=100') .
           fm_submit("ACTION", "Devastation") . " to <b>$Wtxt</b> " .
           " and set the conflict flag - this assumes all ground forces are fighting, adjust if they are not<br>";
      echo "<p><input type=submit name='CSV' value='CSV' >";
    }

  }
  echo fm_submit('ACTION','Remove Forces');

  if ($Excludes) echo fm_submit('ACTION','Reset the excluded Forces');
  echo "</form>";


}

// Planetary Defences + Militia for Ground Combat

  $TurnP = '';
  if (isset($_REQUEST['TurnP'])) $TurnP = "&TurnP=1";
  $Things = Get_AllThings();
//  $Hostiles = [];
  $Mtch = [];

//  echo "Checked Homes<p>";

  foreach ($Things as $T){
    if ($T['BuildState'] != BS_COMPLETE || (($T['LinkId'] < 0) && ($T['LinkId'] != LINK_NOT_MOVING))) continue;
// var_dump($T['id']);
    $Sid = $T['SystemId'];
    $Hostile = (($TTypes[$T['Type']]['Properties']??0) & THING_IS_HOSTILE) && ($T['PrisonerOf'] == 0);
    $Ground = is_on_ground($T);
//      var_dump($Sid,$T['Whose']);
    if (!isset($Sids[$Sid][$Ground][$T['Whose']]) || $Hostile) {
      $Sids[$Sid][$Ground][$T['Whose']] = $Hostile;
    }
  }

//  var_dump($Sids);
//  var_dump($Sys);
  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
    case 'Do ALL Damage':
//    var_dump($_REQUEST);
      $IgnoreShield = ($_REQUEST['IgnoreShield'] ?? 0);
      foreach ($_REQUEST as $RN=>$RV) {
        if ($RV && preg_match('/Damage:(\d*)/',$RN,$Mtch)) {
          $Tid = $Mtch[1];
//var_dump($Tid,$RN,$RV);
          $T = Get_Thing($Tid);
          $RDam = $Dam = $RV;
          $T['Conflict'] = 1;
          if (($IgnoreShield == 0) && $T['CurShield']) {
            $shldD = min($T['CurShield'],$Dam);
            $T['CurShield'] = $T['CurShield'] - $shldD;
            $RDam -= $shldD;
          }
          if ($RDam) {
            $T['CurHealth'] = min(max(0,$T['CurHealth'] - $RDam), $T['OrigHealth']);
          }

          if (($T['CurHealth'] == 0) && ($ThingProps[$T['Type']] & (THING_HAS_SHIPMODULES | THING_HAS_ARMYMODULES)) != 0) {
            TurnLog($T['Whose'],$T['Name'] . " took $RV damage and has been destroyed\n",$T);

            $tprops = $ThingProps[$T['Type']];

            if ($$TTypes[$T['Type']]['Props2'] & THING_HAS_RECOVERY) {
              GMLog($T['Name'] . " took $RV damage\n",$T);

            } else {
              GMLog($T['Name'] . " took $RV damage and has been destroyed\n",$T);
              if (isset($_REQUEST["NoDebris:$Tid"])) {
                Thing_Delete($Tid);
              } else {
                Thing_Destroy($T);
                Put_Thing($T);
              }
            }
          } else if ($T['CurHealth']) {
            TurnLog($T['Whose'],$T['Name'] . " took $RV damage\n",$T);
            Put_Thing($T);
          } else {
            TurnLog($T['Whose'],$T['Name'] . " took $RV damage and has been destroyed\n",$T);
            GMLog($T['Name'] . " took $RV damage and has been destroyed\n",$T);
            if (isset($_REQUEST["NoDebris:$Tid"])) {
              Thing_Delete($Tid);
            } else {
              Thing_Destroy($T);
              Put_Thing($T);
            }
          }

          // Reports??
        }
        if ($RV && preg_match('/RetreatMe:(\d*)/',$RN,$Mtch)) {
          $Tid = $Mtch[1];
          $T = Get_Thing($Tid);

          $T['Retreat'] = 2;
          TurnLog($T['Whose'],$T['Name'] . " Will retreat from combat",$T);
          GMLog($T['Name'] . " will retreat from combat",$T);
          Put_Thing($T);
        }

      }


      // Deliberate fall through

    case 'Check':
      if (isset($_REQUEST['S'])) {
        $Sid = $_REQUEST['S'];
        $N = Get_System($Sid);
      } else if (isset($_REQUEST['R'])) {
        $Ref = $_REQUEST['R'];
        $N = Get_SystemR($Ref);
        $Sid = $N['id'];
      } else {
        echo "Should Never Get Here";
        exit;
      }

      SystemSee($Sid);

//      echo "<p><h2><a href=Meetings.php?S=$Sid&ACTION=FRGROUND>Force Report for Ground Combat</a>, " .
//                  "<a href=Meetings.php?S=$Sid&ACTION=FRSPACE>Force Report for Space Combat</a></h2>";
      break;

    case 'UNLOAD' :
      $Tid = $_REQUEST['id'];
      $T = Get_Thing($Tid);
      if ($T['LinkId'] >= 0 ) {
        echo "Data for $Tid inconsistent call Richard";
        dotail();
      }
      $Hid = $T['SystemId'];
      $H = Get_Thing($Hid);
      if ($TTypes[$T['Type']]['Properties'] & THING_HAS_ARMYMODULES ) {
        $T['WithinSysLoc'] =3;
      } else {
        $T['WithinSysLoc'] =1;
      }
      $T['LinkId'] = 0;
      $Sid = $T['SystemId'] = $H['SystemId'];
      Put_Thing($T);
      echo $T['Name'] . " unloaded<p>";
      $N = Get_System($Sid);
      SystemSee($Sid);
      break;


    case 'FRGROUND':
      $Sid = $_REQUEST['S'];
      $N = Get_System($Sid);
      ForceReport($Sid,'G');
      break;

    case 'FRSPACE':
      $Sid = $_REQUEST['S'];
      $N = Get_System($Sid);
      ForceReport($Sid,'S');
      break;

    case 'Devastation':
      $Dev = $_REQUEST['Devastate'];
      $Place = $_REQUEST['Place'];

      if ($Place>0) { //Planet
        $P = Get_Planet($Place);
        $Home = Gen_Get_Cond1('ProjectHomes', "ThingType=1 AND ThingId=$Place");
        if(!$Home) {
          echo "Could not find Project home for planet $Place<p>";
          break;
        }
        $Home['Devastation'] += $Dev;
        Put_ProjectHome($Home);
        $World = Gen_Get_Cond1('Worlds',"Home=" . $Home['id']);
        $World['Conflict'] = 1;
        Put_World($World);
        echo "$Dev devastation applied to " . $P['Name'] . "<p>";
      } else { /// Moon
        $P = Get_Moon(-$Place);
        $Home = Gen_Get_Cond1('ProjectHomes', "ThingType=2 AND ThingId=-$Place");
        if(!$Home) {
          echo "Could not find Project home for Moon $Place<p>";
          break;
        }
        $Home['Devastation'] += $Dev;
        Put_ProjectHome($Home);
        $World = Gen_Get_Cond1('Worlds',"Home=" . $Home['id']);
        $World['Conflict'] = 1;
        Put_World($World);
        echo "$Dev devastation applied to " . $P['Name'] . "<p>";
      }

    case 'Remove Forces':
      $Sid = $_REQUEST['S'];
      $N = Get_System($Sid);
      Get_Battle($Sid);
      Set_Excludes();

      foreach ($_REQUEST as $R=>$V) {
        if (preg_match('/Exclude:(\d*):(\d*)/',$R,$Mtch)) {
          $Fid = $Mtch[1];
          $Tid = $Mtch[2];
          $T = Get_Thing($Tid);
          if ($TTypes[$T['Type']]['Prop2'] & THING_HAS_RECOVERY) {
            $T['LinkId'] = LINK_INBRANCH;
            Put_Thing($T);
          } else {
            $Excludes[$Tid] = 1;
          }
        }
      }

      Save_Excludes();
      SystemSee($Sid);

      break;

    case 'Reset the excluded Forces':
      $Sid = $_REQUEST['S'];
      $N = Get_System($Sid);
      Get_Battle($Sid);
      Set_Excludes();
      $Excludes = [];
      Save_Excludes();
      SystemSee($Sid);
      break;



    case 'Check 2': // Same as above
      if (isset($_REQUEST['S'])) {
        $Sid = $_REQUEST['S'];
        $N = Get_System($Sid);
      } else if (isset($_REQUEST['R'])) {
        $Ref = $_REQUEST['R'];
        $N = Get_SystemR($Ref);
        $Sid = $N['id'];
      } else {
        echo "Should Never Get Here";
        exit;
      }
      SystemSee($Sid);
      break;

      // Not Complete



    }
  }
  echo "<h1>Checking</h1>";

  echo "<span class=NotHostile>Factions</span> thus marked have only Never Hostile Things.<br>" .
       "The most hostile rating between factions present is displayed.<p>";

  $RelNames = $RelCols = [];
  foreach($Relations as $r=>$v) {
    $RelNames[$r] = $v[0];
    $RelCols[$r] = $v[1];
  }

  $RR['Show'] = 5;
  echo fm_radio("Min relationship to Show",$RelNames,$RR,'Show',' onchange=MeetupFilter()',1,'','',$RelCols);

  if (!$CSV) {
    TableStart();
    TableHead('System');
    TableHead('Space');
    TableHead('Done?');
    TableHead('Ground');
    TableHead('Done?');
    TableTop();
  }
//  echo "Checked Things<p>";

//  var_dump($Sids);

  $Worlds = Get_Worlds();
  $Homes = Get_ProjectHomes();
  $Turn = $GAME['Turn'];

  foreach ($Worlds as $W) {
    $H = $Homes[$W['Home']];
    $Sid = $H['SystemId'];
    $Fid = $W['FactionId'];

    switch ($H['ThingType']) {
      case 1: // Planet
        $Dat = Get_Planet($H['ThingId']);
        break;
      case 2: // Moon
        $Dat = Get_Moon($H['ThingId']);
        break;
      case 3: // Tghing
        $Dat = [];
        break;
    }
    if ((($Dat['Attributes']??0)& 1) == 0) $Sids[$Sid][1][$Fid] = 1; // Ground present (Militia)
  }

  echo "<form method=post>";
  Register_AutoUpdate('Generic', 0);
  foreach ($Sys as $N) {
    $Sid = $N['id'];
    if (!isset($Sids[$Sid])) continue;

    $MeetupTurn = Gen_Get_Cond1('MeetupTurn',"GameId=$GAMEID AND Turn=$Turn AND SystemId=$Sid");
    if (!$MeetupTurn) {
      $MeetupTurn = ['GameId'=>$GAMEID,'Turn'=>$Turn,'SystemId'=>$Sid,'Ground'=>0,'Space'=>0];
      Gen_Put('MeetupTurn',$MeetupTurn);
    }
    $MTid = $MeetupTurn['id'];
    if ((isset($Sids[$Sid][0]) && (count($Sids[$Sid][0]) > 1)) || (isset($Sids[$Sid][1]) && (count($Sids[$Sid][1]) > 1))) {
      $HostC = [0,0];
      for($gs=0;$gs<2;$gs++) {
        if (isset($Sids[$Sid][$gs])) {
          foreach($Sids[$Sid][$gs] as $Fid=>$Host) if ($Host) $HostC[$gs]++;
        }
      }

//      var_dump($HostC);
      if ($HostC[0] <2 && $HostC[1]<2) continue; // Not 2 hostile possibles present

      $WorstRel=100;
      $ltxt = '';
      for($gs=0;$gs<2;$gs++) {
        $height = ['Space','Ground'][$gs];
        $React = 9;
        $ltxt .= "<td>";
        if (isset($Sids[$Sid][$gs]) && (count($Sids[$Sid][$gs]) > 1)) {
          foreach ($Sids[$Sid][$gs] as $F1=>$Hostile1) {
            if ($F1 == 0) continue;
            $R1 = $Facts[$F1]['DefaultRelations'];
            if ($R1 == 0) $R1 = 5;
            foreach ($Sids[$Sid][$gs] as $F2=>$Hostile2) {
              if ($F1 == $F2) continue;
              $R2 = ($Facts[$F2]['DefaultRelations']??5);
              if ($R2 == 0) $R2 = 5;
              if (!isset($FactFact[$F1][$F2])) {
                $FF = Gen_Get_Cond1('FactionFaction',"FactionId1=$F1 AND FactionId2=$F2");
                if ($FF) {
                  $FactFact[$F1][$F2] = (($FF['Relationship']??0)?$FF['Relationship']:5);
                } else {
                  $FactFact[$F1][$F2] = $R1;
                }
              }
              if (!isset($FactFact[$F2][$F1])) {
                $FF = Gen_Get_Cond1('FactionFaction',"FactionId1=$F2 AND FactionId2=$F1");
                if ($FF) {
                  $FactFact[$F2][$F1] = (($FF['Relationship']??0)?$FF['Relationship']:5);
                } else {
                  $FactFact[$F2][$F1] = $R2;
                }
              }
              $React = min($React,$FactFact[$F1][$F2],$FactFact[$F2][$F1]);
            }
          }

          $DR = $Relations[$React];
          $ltxt .= "<span style='background:" . $DR[1]  . "'>" . $DR[0] . "</span> ";
          if ($React < $WorstRel) $WorstRel = $React;

          foreach ($Sids[$Sid][$gs] as $Fid=>$Hostile) {
            $F = ($Facts[$Fid]??[]);
            if (empty($F['Name'])) {
              $ltxt .=  'Other , ';
            } else {
              $Fid = $F['id'];
              if ($Hostile) {
                $ltxt .= $F['Name'] . " , ";
              } else {
                $ltxt .=  "<span class=NotHostile>" . $F['Name'] . "</span> , ";
              }
            }
          }
        } else {

        }
        $ltxt .= "<td>" . fm_checkbox('', $MeetupTurn, $height,'',"MeetupTurn:$height:$MTid");
      }

      $hide = ($WorstRel<=$RR['Show']?'':' hidden');
      echo "\n<tr class=WorstRel$WorstRel $hide><td><a href=Meetings.php?ACTION=Check&S=$Sid$TurnP>" . $N['Ref'] . "</a>";
      echo $ltxt;
    }
  }

  TableEnd();
  echo "<P>Scan Finished<p>";

  if ($TurnP) echo "<h2><a href=TurnActions.php?ACTION=Complete&Stage=Meetups>Back To Turn Processing</a></h2>";

  dotail();

?>
