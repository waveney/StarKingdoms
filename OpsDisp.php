<?php

// Scan all plantets/moon's/Thing's for districts and deep space construction

  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("PlayerLib.php");
  include_once("SystemLib.php");
  include_once("ProjLib.php");

  global $FACTION,$ADDALL,$GAME,$ARMY;

// var_dump($_REQUEST);

  $HomeColours = ['#ff99ff', '#ccffff', '#ccffcc', '#ffffcc', '#ffcccc', '#e6ccff', '#cce6ff', '#ffd9b3', '#ecc6c6', '#ecc6d6', '#d6b3ff', '#d1e0e0', '#d6ff99',
     '#ffb3ff', '#b3b3ff', '#b3ffff', '#b3ffb3', '#ffffb3', '#ffb3b3', '#ecc6c6', '#ffb3cc', '#ffb3d9', '#ecc6d9', '#ffb3ff', '#ecc6ec', '#ecb3ff', '#e0b3ff',
     '#d9b3ff', '#d1d1e0', '#c6c6ec', '#b3b3ff', '#b3ccff', '#c2d1f0', '#c6d9ec', '#b3d9ff', '#d1e0e0', '#c6ecd9', '#d9ffb3', '#e5e5cc', '#e0e0d1', '#ecd9c6',
     '#ffd9b3', '#ffe6b3', '#ffc6b3', '#ffccb3',

   ];

  A_Check('Player');
  $mtch = [];

  $OpCosts = Feature('OperationCosts');
  $OpRushs = Feature('OperationRushes');

  $OpTypes = Get_OpTypes();
  $OrgTypes = Get_OrgTypes();

  $Fid = 0;
  $GM = Access('GM');
  if (Access('Player')) {
    if (!$FACTION) {
      if (!Access('GM') ) Error_Page("Sorry you need to be a GM or a Player to access this");
    } else {
      $Fid = $FACTION['id'];
      $Faction = &$FACTION;
    }
  } else if (!$GM) {
    Error_Page("Sorry you need to be a GM or a Player to access this");
  }
  if ($GM && !$Fid) {
    if (isset( $_REQUEST['F'])) {
      $Fid = $_REQUEST['F'];
    } else if (isset( $_REQUEST['f'])) {
      $Fid = $_REQUEST['f'];
    } else if (isset( $_REQUEST['id'])) {
      $Fid = $_REQUEST['id'];
    }
    CheckFaction('ProjDisp',$Fid);

    if (isset($Fid)) $Faction = Get_Faction($Fid);
  }

  if ($Fid == 0) {
    dostaffhead("Display Operations When no Faction selected");
    echo "<h1>Display Operations When no Faction selected</h1>";
    dotail();
  }
  $Orgs = Gen_Get_Cond('Organisations',"Whose=$Fid");

  if (!$GM && $Faction['TurnState'] > 2) Player_Page();
  dostaffhead("Display Operations for faction",["js/ProjectTools.js"]);
  $OpenOrg = -99;

  $OpTypes = Get_OpTypes();
  $Orgs = Gen_Get_Cond('Organisations',"Whose=$Fid ORDER BY RelOrder DESC");

  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {  // TODO This code is DREADFUL needs redoing
      case 'NEW':
        $Optype = $_REQUEST['p'];
        $Turn = $_REQUEST['t'];
        $OrgId = $_REQUEST['O'];
        $DT = (isset($_REQUEST['DT'])? $_REQUEST['DT'] : 0);
        $Valid = 1;
        $FreeRush = 0;

        switch ($OpTypes[$Optype]['Name']) {
        case 'Construction':
        case 'Grow District':
        case 'Research Planetary Construction':
        case 'Research Core Technology':
        case 'Research Supplemental Technology':
        case 'Research Ship Construction':
        case 'Research Supplemental ship Tech':
        case ('Research ' . Feature('MilTech')):
        case "Research Supplemental $ARMY Tech":
        case 'Research Intelligence Operations':
        case 'Research Supplemental Intelligence Tech':
        case 'Research Supplemental Planetary Construction Tech':
        case 'Construct Warp Gate':
        case 'Decipher Alien Language':
        case 'Rebuild and Repair':
        case 'Grow Modules' :
        case 'Produce Adianite' :

          if (isset($_REQUEST['Sel'])) $Sel = $_REQUEST['Sel'];
          $Level = $_REQUEST['L'];
          $Costs = $_REQUEST['C'];
          $ProgN = $_REQUEST['PN'];
          $Name = base64_decode($_REQUEST['Name']);
          break;

        case 'Re-equip and Reinforce':
          $Level = 1;
          $TthingId2 = 0;
          if (isset($_REQUEST['Sel'])) {
            $TthingId = $_REQUEST['Sel'];
          } else if (isset($_REQUEST['Sel2'])) {
            $Sels = $_REQUEST['Sel2'];
            $Count = count($Sels);
            if ($Count == 0 || $Count>2) {
              echo "<h2 class=Err>You must select 1 (or up to two level 1 armies)</h2>\n";
              $Valid = 0;
              break;
            }
            if ($Count == 1) {
              $TthingId = $Sels[0];
            } else {
              $TthingId =$Sels[0];
              $TthingId2 = $Sels[1];
              $T2 = Get_Thing($TthingId2);
            }
          } else {
            echo "<h2 class=Err>You must select an $ARMY</h2>\n";
            $Valid = 0;
            break;
          }
          $T1 = Get_Thing($TthingId);

          if (isset($_REQUEST['Name'])) {
            $Name = base64_decode($_REQUEST['Name']);
          } else {
            $T1 = Get_Thing($TthingId);
            $Name = "Re-equip and Reinforce " . $T1['Name'];
            if ($TthingId2) $Name .= " and " . $T2['Name'];
          }

          $pc = Proj_Costs(1);
          $Costs = $pc[1];
          $ProgN = $pc[0];
          break;

        case 'Refit and Repair':
          $Level = 1;
          $TthingId2 = 0;
          if (isset($_REQUEST['Sel'])) {
            $TthingId = $_REQUEST['Sel'];
          } else if (isset($_REQUEST['Sel2'])) {
            $Sels = $_REQUEST['Sel2'];
            $Count = count($Sels);
            if ($Count == 0 || $Count>2) {
              echo "<h2 class=Err>You must select 1 (or up to two level 1 ships)</h2>\n";
              $Valid = 0;
              break;
            }
            if ($Count == 1) {
              $TthingId = $Sels[0];
            } else {
              $TthingId =$Sels[0];
              $TthingId2 = $Sels[1];
              $T2 = Get_Thing($TthingId2);
            }
          } else {
            echo "<h2 class=Err>You must select a ship</h2>\n";
            $Valid = 0;
            break;
          }
          $T1 = Get_Thing($TthingId);

          if (isset($_REQUEST['Name'])) {
            $Name = base64_decode($_REQUEST['Name']);
          } else {
            $T1 = Get_Thing($TthingId);
            $Name = "Refit and Repair " . $T1['Name'];
            if ($TthingId2) $Name .= " and " . $T2['Name'];
          }

          $pc = Proj_Costs(1);
          $Costs = $pc[1];
          $ProgN = $pc[0];
          break;

         case 'Share Technology':
          $Tech2S = $_REQUEST['Tech2Share'];
          preg_match('/(\d*):(\d*)/',$Tech2S,$mtch);
          $With = $_REQUEST["ShareWith"];
          $Level = $mtch[2];
          $pc = Proj_Costs($Level-1);
          $Costs = $pc[1];
          $ProgN = $pc[0];
          $Sel = $mtch[1];
          $Tech = Get_Tech($Sel);
          $Fact = Get_Faction($With);
          $Name = "Share " . $Tech['Name'] . " at level $Level with " . $Fact['Name'];
          if (Get_Things_Cond($Fid, " Type=17 AND OtherFaction=$With ")) {
            $FreeRush=1;
          }
          break;

        case 'Analyse':
          $Level = $_REQUEST['Level'];
          $Name = $_REQUEST['AnalyseText'];
          if (empty($Name)) {
            echo "<h2 class=Err>No project name given</h2>";
            $Valid = 0;
            break;
          }
          $pc = Proj_Costs($Level);
          $Costs = $pc[1];
          $ProgN = $pc[0];
          break;

        case 'Post It':
          $Level = $_REQUEST['Level'];
          $pc = Proj_Costs($Level);
          $Costs = $pc[1];
          $ProgN = $pc[0];
          $Name = $_REQUEST['PostItTxt'];
          if (empty($Name)) {
            echo "<h2 class=Err>No message given</h2>";
            $Valid = 0;
            break;
          }
          break;

        case 'Seek Enemy Agents' :
          $Level = $_REQUEST['Level'];
          $pc = Proj_Costs($Level);
          $Costs = $pc[1];
          $ProgN = $pc[0];
          $Name = 'Seek Enemy Agents';
          break;

          /* These are treated as Instructions now */
        case 'Decommission Ship':
        case 'Build Outpost':
        case 'Build Asteroid Mining Facility':
        case 'Build Minefield':
        case 'Build Orbital Shipyard':
        case 'Build Space Station':
        case 'Extend Space Station':
        case 'Deep Space Sensors':
        case 'Build Advanced Asteroid Mining Facility':
        case 'Unknown' :
          break;
        default:
        }

        if ($Valid) {
          $OldPro = Get_OperationAt($OrgId, $Turn);
// var_dump($OldPro);
          $Pro = ['FactionId'=>$Fid, 'Type'=>$Optype, 'Level'=> $Level, 'OrgId'=>$OrgId, 'Progress'=>0, 'Status'=>0, 'TurnStart'=>$Turn, 'Name'=>$Name,
                  'Costs' => $Costs, 'ProgNeeded' => $ProgN, 'BuildState'=>0, 'DType' => $DT, 'FreeRushes'=>$FreeRush];
          if (isset($With)) $Pro['ThingId'] = $With;
          if (isset($Sel)) $Pro['ThingType'] = $Sel;
          if (isset($TthingId)) $Pro['ThingId'] = $TthingId;
          if (isset($TthingId2)) $Pro['ThingId2'] = $TthingId2;

          if (isset($OldPro['id'])) {
            $Pro['id'] = $OldPro['id'];
          }
          $OpenOrg = $OrgId;

          $Pid = Put_Operation($Pro);
        }
      break;

    }
  }

  echo "<h1>Display Operations</h1>";

  echo "<div class=floatright style=text-align:right><div class=Bespoke>" .
       "Showing:<button class=BigSwitchSelected id=BespokeM onclick=Add_Bespoke()>10 Turns</button><br>" .
       "Switch to: <button class=BigSwitch id=GenericM onclick=Add_Bespoke()>All Turns</button></div>" .
       "<div class=Bespoke hidden id=BespokeMess>" .
       "Showing:<button class=BigSwitchSelected id=GenericM1 onclick=Remove_Bespoke()>All Turns</button><br>" .
       "Switch to: <button class=BigSwitch id=BespokeM1 onclick=Remove_Bespoke()>10 Turns</button></div>" .
       "<h2><a href=ProjDisp.php>Refresh</a></h2>" .
       "</div>";

  echo "Click on Organisation to Expand/Contract<br>";
  echo "Click on Showing Options top right to see more than 2 turns back<br>\n";
  echo "Click on the Organisation to expand/contract that area<p><p>";
  echo "Click on <button type=submit class=PHStart id=StartExample formaction=''>+</button> buttons to start/change Operations<br>\n";
  echo "Click up/down or write number to rush projects<br>\n";
  echo "Only Organisations with at least one office are shown<br>\n";

  $Headline1 = '';
  $Headline2 = [];
  $SkipProgress = 0;

  if (Access('GM')) {
    $TurnData = Get_TurnNumber();
    if ($TurnData['Progress'] & 1<<47) {
      $SkipProgress = 1;
    }
  }

  $Dis = [];
  $FirstHome = 0;
  $NoC = 0;
  $proj = [];



  $BonusRushes = 0; // Has_Trait($Fid,'Built for Construction and Logistics');
  $ShowOtherCat = 0;

  $Opers = Get_Operations($Fid);

//    var_dump($Projs);echo "BEFORE<p>";
  $PHx = 0;

  foreach ($Opers as &$O) {
    $OrgId = $O['Organisation'];
    $Org = $Orgs[$OrgId];
    if ($Org['OfficeCount']==0) continue;

//     var_dump($O);
    if (!isset($O['Status']) || ($O['Status'] > 2 )) continue; // Cancelled, On Hold or Could not start
    $TurnStuff = Get_OperationTurns($O['id']);
    $TSi = 0;
    $PHx++;
    while (isset($TurnStuff[$TSi]['TurnNumber']) && ($TurnStuff[$TSi]['TurnNumber'] < $O['TurnStart'])) $TSi++;

    $Pro = [];
    $Pro['id'] = $O['id'];
    $Pro['Type'] = $O['Type'];
    $Pro['Name'] = $O['Name']; // $ProjTypes[$O['Type']]['Name'];
    $Pro['Level'] = $O['Level'];
    $Pro['Cost'] = $O['Costs'];
    $Pro['Acts'] = $O['ProgNeeded'];
    $Pro['GMOverride'] = $O['GMOverride'];
    $Pro['FreeRushes'] = $O['FreeRushes'];

    $Optype = $OpTypes[$O['Type']];
//      $PCat = $PPtype['Category'];


    $Pro['Status'] = 'Started';
    $TotProg = 0;

//var_dump($Pro, $ProjTypes[$O['Type']]);  echo "<p>";
        // Is there an existing project where this is going?
    if (isset($Opers[$O['TurnStart']][$OrgId]['id'])) {
      $What = $Opers[$O['TurnStart']][$OrgId]['id'];
      for ($t = $O['TurnStart']; $t <= $O['TurnStart']+50; $t++) {
        if (isset($Opers[$t][$OrgId]['id']) && $Opers[$t][$OrgId]['id'] == $What) {
          $Opers[$t][$OrgId] = [];
        } else {
          break;
        }
      }
    }

    $TotProg = $O['Progress'];
    for ($t = $O['TurnStart']; $t <= ($O['TurnEnd']?$O['TurnEnd']:$O['TurnStart']+50); $t++) {

      $Pro['Rush'] = $Rush = $BonusRush = $Bonus = 0;
      $Pro['MaxRush'] = $Org['OfficeCount'];
      if ($O['FreeRushes']) $Pro['Rush'] = $Rush = $Pro['MaxRush'];

    if (isset($TurnStuff[$TSi])) {
      if ($TurnStuff[$TSi]['TurnNumber'] == $t) {
        $Rush = $Pro['Rush'] = min($TurnStuff[$TSi]['Rush'], $Pro['MaxRush']);
        if (!empty($TurnStuff[$TSi]['Bonus'])) $Bonus = $Pro['Bonus'] = $TurnStuff[$TSi]['Bonus'];
        $TSi ++;
      }
    }

    if ($t == $O['TurnEnd']) {
      $Pro['Status'] = 'Complete';
      $Pro['Progress'] = $Pro['Acts'] . "/" . $Pro['Acts'];
    } else if ($t < $GAME['Turn'] -1) {
      $Pro['Progress'] = "? /" . $Pro['Acts'];
      $Pro['Status'] = (($t == $O['TurnStart'])?'Started' : 'Ongoing' );
    } else if ($t == $GAME['Turn'] -1) {
      $Pro['Progress'] = $O['Progress'] . "/" .  $Pro['Acts'];
      $Pro['Status'] = (($t == $O['TurnStart'])?'Started' : 'Ongoing' );
    } else {
      $Prog = min($Pro['Acts'] - $TotProg,$Pro['MaxRush'] + $Rush + $Bonus+ $BonusRush ); // Note Bonus can be negative
      if ($t == $GAME['Turn']) {
        if ($SkipProgress) $Prog = 0;
        $TotProg = $O['Progress'] + $Prog;
      } else {
        $TotProg += $Prog;
      }
      $Pro['Progress'] = "$TotProg/" . $Pro['Acts'];
      $Pro['Status'] = (($TotProg >= $Pro['Acts'])? 'Complete' : (($t == $O['TurnStart'])?'Started' : 'Ongoing' ));
    }

    $proj[$t][$OrgId] = $Pro;
    $Pro['Cost'] = 0;
    if ($Pro['Status'] == 'Complete') break;
  }
}

foreach ($Orgs as $OrgId=>$O) {
  if ($O['OfficeCount']==0) continue;

//  var_dump($OrgId,$O);
  $Hide = ($OpenOrg == $OrgId ? "" : "hidden");
  $back = "style='background:" .  ($OrgTypes[$O['OrgType']]['Colour']??'lightgrey') . ";'";


  $Headline1 .= "<th id=PHH$PHx $back><button type=button class=ProjHome id=PHome$OrgId>" . $O['Name'] . "</button>";

  $HL = "<th class='PHStart Group$OrgId Home$OrgId' id=PHDist:$OrgId $back $Hide><b>+</b>" .
  "<th $back class='PHName  Home$OrgId'><button type=button onclick=Toggle('Group$OrgId')>";

  $HL .= $O['Name'] . " - " . $O['OfficeCount'];
  $HL .= "</button><th $back class='PHLevel Group$OrgId Home$OrgId'id=PHLevel$OrgId $Hide>Lvl";
  if ($OpCosts) $HL .= "<th $back class='PHCost Group$OrgId Home$OrgId' id=PHCost$OrgId $Hide>Cost";
  if ($OpRushs) $HL .= "<th $back class='PHRush Group$OrgId Home$OrgId' id=PHRush$OrgId $Hide>Rush";

  //        "<th $back class='PHBonus Group$Di Home$Hi' id=PHBonus$Hi:$Di $GMHide>B" .
  $HL .= "<th $back class='PHProg Group$OrgId Home$OrgId' id=PHProg$OrgId $Hide>Prog" .
  "<th $back class='PHStatus Group$OrgId Home$OrgId' id=PHStatus$OrgId $Hide>Status";

  $Headline2[] = $HL;
}

  // That is just the nested headers


  // Now Present the data

  echo "<form method=post action=OpsDisp.php>";
 //???? echo fm_hidden('DistData', base64_encode(json_encode($Dis)));
  Register_AutoUpdate('Operations',$Fid);
  echo fm_hidden('id',$Fid);

  echo "<input type=submit name=Ignore value=Ignore hidden>\n";

  echo "<h2>Operations:</h2>";
  echo "<table border>";

  echo "<tr><td>Turn"; /* $Headline1;
  echo "<tr><td>"; */
  foreach ($Headline2 as $Hl) echo $Hl;

  if ($OpCosts)   echo "<th>Total Cost<th>Credits Left";
  for($Turn=0; $Turn<($GAME['Turn']+50); $Turn++) {
    $RowClass = "ProjHide";
    $hide = ' hidden';
    if ($Turn >= ($GAME['Turn']-1) && $Turn < ($GAME['Turn']+9)) {
        $RowClass = 'projhow';
        $hide = '';
    }

    $BG = " style='background:White;'";
    $tdclass= 'ProjWhite';
    if ($Turn < $GAME['Turn']) {
      $BG = " style='background:#F0F0F0;'";
    } else if ($Turn == $GAME['Turn']) {
      $BG = " style='background:#C0FFC0;'";
    }

    echo "<tr class=$RowClass $hide><td class=PHTurn >$Turn";
    $TotCost = 0;

    foreach ($Orgs as $OrgId=>&$O) {
      if ($O['OfficeCount']==0) continue;
      $Hide = '';

      if (isset($O['Skip'])) continue;

      $Hide = ($OrgId == $OpenOrg? "" : "hidden");
      echo "<td $BG id=proj$Turn:$OrgId: class='PHStart Group$OrgId Home$OrgId' $Hide>\n";


      if ($Turn >= $GAME['Turn'] && $ADDALL!='readonly') {

        $Warn = '';
        if (isset($proj[$Turn - 1 ][$OrgId]['Status']) && ($proj[$Turn - 1 ][$OrgId]['Status'] == 'Started' || $proj[$Turn - 1][$OrgId]['Status'] == 'Ongoing')) {
   //        Warn = "onclick=\"return confirm('Do you want to abandon " . $Proj[$Turn-1][$OrgId]['Name'] . '?\'"';
            $Action = "onclick='return NewProjectCheck($Turn,$OrgId)' formaction=OpsNew.php?t=$Turn&O=$OrgId";
        } else {
          $Action = "formaction=OpsNew.php?t=$Turn&O=$OrgId";
        }
        echo "<button type=submit class=PHStartButton id=Start:STurn:$OrgId: $Action ><b>+</b>\n";
      }
      if (isset($proj[$Turn][$OrgId]['Type'])) {
        $PN = $proj[$Turn][$OrgId]['id'];
        echo "\n<td $BG id=ProjN$Turn:$OrgId: class='PHName Home$OrgId" . ($proj[$Turn][$OrgId]['Type'] == 38?" PHpostit ":"") . "'>" .
              "<a href=ProjEdit.php?id=" . $proj[$Turn][$OrgId]['id'] . ">" .
              $proj[$Turn][$OrgId]['Name'] . "</a>";
        echo "\n<td $BG id=ProjL$Turn:$OrgId: class='PHLevel Group$OrgId Home$OrgId' $Hide>" . $proj[$Turn][$OrgId]['Level'];
        if ($OpCosts) echo "\n<td $BG id=ProjC$Turn:$OrgId: class='PHCost Group$OrgId Home$OrgId' $Hide>" . $proj[$Turn][$OrgId]['Cost'];
        if ($OpRushs) echo "\n<td $BG id=ProjR$Turn:$OrgId: class='PHRush Group$OrgId Home$OrgId' $Hide>" . (($Turn < $GAME['Turn'])?$proj[$Turn][$OrgId]['Rush'] :
             "<input type=number id=Rush$Turn:$PN name=Rush$Turn:$PN oninput=RushChange($Turn,$PN,$OrgId,0," .
             $proj[$Turn][$OrgId]['MaxRush'] . ") value=" . min($proj[$Turn][$OrgId]['Rush'],$proj[$Turn][$OrgId]['MaxRush'])  .
             " min=0 max=" . ($proj[$Turn][$OrgId]['GMOverride']?20:$proj[$Turn][$OrgId]['MaxRush']) .">" );
 //     if (!empty($Proj[$Turn][$OrgId]['Bonus'])) echo "<div id=ProjB$Turn:$OrgId: class='PHBonus hidden>" . $Proj[$Turn][$OrgId]['Bonus'] . "</div>";
        echo "\n<td $BG id=ProjP$Turn:$OrgId: class='PHProg Group$OrgId Home$OrgId' $Hide>" . $proj[$Turn][$OrgId]['Progress'];
        echo "\n<td $BG id=ProjT$Turn:$OrgId: class='PHStatus Group$OrgId Home$OrgId' $Hide>" . $proj[$Turn][$OrgId]['Status'] . "";

        $TotCost += $proj[$Turn][$OrgId]['Cost'] + ( $proj[$Turn][$OrgId]['FreeRushes']?0:($proj[$Turn][$OrgId]['Rush']*Rush_Cost($Fid)));

      } else {
        echo "<td $BG id=ProjN$Turn:$OrgId: class='PHName Home$OrgId'>";
        echo "<td $BG id=ProjL$Turn:$OrgId: class='PHLevel Group$OrgId Home$OrgId' $Hide>\n";
        if ($OpCosts) echo "<td $BG id=ProjC$Turn:$OrgId: class='PHCost Group$OrgId Home$OrgId' $Hide>\n";
        if ($OpCosts) echo "<td $BG id=ProjR$Turn:$OrgId: class='PHRush Group$OrgId Home$OrgId' $Hide>\n";
        echo "<td $BG id=ProjP$Turn:$OrgId: class='PHProg Group$OrgId Home$OrgId' $Hide>\n";
        echo "<td $BG id=ProjT$Turn:$OrgId: class='PHStatus Group$OrgId Home$OrgId' $Hide>\n";
      }
    }

    if ($OpCosts) {
    }
  }
  echo "</table></form>";

  echo "<table border>";
  if (Access('God')) echo "</tbody><tfoot><tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea>";
  echo "</table>";

  //<script>ToggleAllBut($FirstHome)</script>";


  dotail();


  // button to show history

  // Header

  // List of places, click to expand, click to shrink

  // History is wanted (2 before, current next 7)
  // Each Turn colour X for old turns, white current, pale yellow future

  // Project state

  // Buttons to start/change/ Up/down for rushing - total costs shown also total money spend against available

  // Future plans - changeable

  // Not Deep space, Not Agents

  // [S] Start Project, where, when, who [warning if it canels existing project]

?>
