<?php

// Scan all plantets/moon's/Thing's for districts and deep space construction

  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("PlayerLib.php");
  include_once("SystemLib.php");
  include_once("ProjLib.php");
  
  global $FACTION;
  
  $HomeColours = ['#ff99ff', '#ccffff', '#ccffcc', '#ffffcc', '#ffcccc', '#e6ccff', '#cce6ff', '#ffd9b3', '#ecc6c6', '#ecc6d6', '#d6b3ff', '#d1e0e0', '#d6ff99' ];
  
  
  if (Access('Player')) {
    if (!$FACTION) {
      if (!Access('GM') ) Error_Page("Sorry you need to be a GM or a Player to access this");
    } else {
      $Fid = $FACTION['id'];
      $Faction = &$FACTION;
    }
  } 
  if (Access('GM') ) {
    A_Check('GM');
    if (isset( $_REQUEST['F'])) {
      $Fid = $_REQUEST['F'];
    } else if (isset( $_REQUEST['f'])) {
      $Fid = $_REQUEST['f'];
    } else if (isset( $_REQUEST['id'])) {
      $Fid = $_REQUEST['id'];
    }
    if (isset($Fid)) $Faction = Get_Faction($Fid);
  }
  
  dostaffhead("Display Projects for faction",["js/ProjectTools.js"]);
  
  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
      case 'NEW': 
        $Ptype = $_REQUEST['p'];
        $Turn = $_REQUEST['t'];
        $Hi = $_REQUEST['Hi'];
        $Di = $_REQUEST['Di'];
        if (isset($_REQUEST['ThingId'])) {
          $T = Get_Thing($_REQUEST['ThingId']);
          if ($T['BuildState'] > 0) {
            $T = Thing_Duplicate($T['id']);
          }
          $Level = $T['Level'];
          $pc = Proj_Costs($Level);            
          $Costs = $pc[1];
          $ProgN = $pc[0];
          $TthingId = $T['id'];
          $Name = "Build " . $T['Name'] . " (level $Level)"; 
        } else {
          $Sel = $_REQUEST['Sel'];
          $Level = $_REQUEST['L'];
          $Costs = $_REQUEST['C'];
          $ProgN = $_REQUEST['PN'];
          $Name = base64_decode($_REQUEST['Name']);
        }

        $OldPro = Get_ProjectAT($Hi, $Di, $Turn);
        $Pro = ['FactionId'=>$Fid, 'Type'=>$Ptype, 'Level'=> $Level, 'Home'=>$Hi, 'Progress'=>0, 'Status'=>0, 'TurnStart'=>$Turn, 'Name'=>$Name,
                'Costs' => $Costs, 'ProgNeeded' => $ProgN, 'BuildState'=>0];
        if (isset($Sel)) $Pro['ThingType'] = $Sel;
        if (isset($TthingId)) $Pro['ThingId'] = $TthingId;
        if (isset($TthingId2)) $Pro['ThingId2'] = $TthingId2;

        if (isset($OldPro['id'])) {
          $Pro['id'] = $OldPro['id'];          
        }

        Put_Project($Pro);
      break;
      
    }
  }
  
  echo "<h1>Display Projects</h1>";
 
  echo "<div class=floatright style=text-align:right><div class=Bespoke>" .
       "Showing:<button class=BigSwitchSelected id=BespokeM onclick=Add_Bespoke()>10 Turns</button><br>" .  
       "Switch to: <button class=BigSwitch id=GenericM onclick=Add_Bespoke()>All Turns</button></div>" .
       "<div class=Bespoke hidden id=BespokeMess>" .
       "Showing:<button class=BigSwitchSelected id=GenericM1 onclick=Remove_Bespoke()>All Turns</button><br>" .  
       "Switch to: <button class=BigSwitch id=BespokeM1 onclick=Remove_Bespoke()>10 Turns</button></div>" .
       "</div>";

  echo "Click on Place to Expand/Contract<br>";
  echo "Click on Showing Options top right to see more than 2 turns back<br>\n";
  echo "Click on the World, Distict type or Construction to expand/contract that area<p><p>";
  echo "Click on <button type=submit class=PHStart id=StartExample formaction=''>+</button> buttons to start/change projects<br>\n";
  echo "Click up/down or write number to rush projects<br>\n";

  echo "Note the cost totals are on the far right<p>";
  
  echo "Currently this display is for construction and district based projects only.<br>\n";
  
  $Homes = Get_ProjectHomes($Fid);
  $DistTypes = Get_DistrictTypes();
  $ProjTypes = Get_ProjectTypes();
  
//var_dump($ProjTypes);exit;

  $Headline1 = '';
  $Headline2 = [];
//  $Turns = [][];
  
// Stage 1 Scan the data

// var_dump($Homes);exit;
  
  $PHx = 1;
  $Dis = [];
  $FirstHome = 0;
  foreach ($Homes as &$H) {
    $Hi = $H['id'];
    switch ($H['ThingType']) {
    case 1: // Planet
      $PH = Get_Planet($H['ThingId']);
      $Dists = Get_DistrictsP($H['ThingId']);
      break;
    case 2: // Moon
      $PH = Get_Moon($H['ThingId']);
      $Dists = Get_DistrictsM($H['ThingId']);
      break;
    case 3: // Thing
      $PH = Get_Thing($H['ThingId']);
      $Dists = Get_DistrictsT($H['ThingId']);
      if (!$Dists) {
        $H['Skip'] = 1;
        continue 2;  // Remove things without districts
      }
      break;
    }
    
    //TODO Construction and Districts... 

    $Dists[] = ['HostType'=>-1, 'HostId' => $PH['id'], 'Type'=> -1, 'Number'=>0, 'id'=>-1];
    $back = "style='background:" . $HomeColours[$PHx-1] . ";'";
    $Headline1 .= "<th id=PHH$PHx $back><button type=button class=ProjHome id=PHome$Hi onclick=ToggleHome($Hi)>" . $PH['Name'] . "</button>";
    if ($FirstHome == 0) $FirstHome=$Hi;
    
    $District_Type = [];
    foreach ($Dists as $D) {
      if ($D['Type'] > 0 && (($DistTypes[$D['Type']]['Props'] &2) == 0)) continue;
      if ($D['Type'] < 0 && $PH['Type'] != $Faction['Biosphere'] && Has_Tech($Fid,3)<2 ) continue;
      $Di = $D['id'];
      $Headline2[] = "<th class='PHStart Group$Di Home$Hi' id=PHDist$Hi:$Di $back hidden><b>+</b>" .
        "<th $back class='PHName  Home$Hi'><button type=button onclick=Toggle('Group$Di')>" . 
        ($D['Type'] > 0 ? $DistTypes[$D['Type']]['Name'] : 'Construction') . "</button>" .
        "<th $back class='PHLevel Group$Di Home$Hi'id=PHLevel$Hi:$Di hidden>Lvl" .
        "<th $back class='PHCost Group$Di Home$Hi' id=PHCost$Hi:$Di hidden>Cost" .
        "<th $back class='PHRush Group$Di Home$Hi' id=PHRush$Hi:$Di hidden>Rush" .
        "<th $back class='PHProg Group$Di Home$Hi' id=PHProg$Hi:$Di hidden>Prog" .
        "<th $back class='PHStatus Group$Di Home$Hi' id=PHStatus$Hi:$Di hidden>Status";
     
      $District_Type[$D['Type']] = $D['Number'];
      $Dis[$Hi][] = $D;
      }

//var_dump($Dis);exit;
    
    $Projs = Get_Projects($Hi);
//var_dump($Projs); echo "<p>";

    foreach ($Projs as &$P) {
//var_dump($P); echo "<P>";
      $TurnStuff = Get_ProjectTurns($P['id']);
//
//var_dump($TurnStuff); echo "<br>";
      $Pro = [];
      $Pro['id'] = $P['id'];
      $Pro['Type'] = $P['Type'];
      $Pro['Name'] = $P['Name']; // $ProjTypes[$P['Type']]['Name'];
      $Pro['Level'] = $P['Level'];
      $Pro['Cost'] = $P['Costs'];
      $Pro['Acts'] = $P['ProgNeeded'];
      
      $PPtype = $ProjTypes[$P['Type']];
      $PCat = $PPtype['Category'];
      
      $WantedDT = -10;
      if ($PCat & 1) { $WantedDT = 5; }// Academic
      else if ($PCat & 2) { $WantedDT = 3; } // Shipyard
      else if ($PCat & 4) { $WantedDT = 2; } // Military      
      else if ($PCat & 8) { $WantedDT = 4; } // Intelligence
      else if ($PCat & 16) { $WantedDT = -1; } // Construction
      else if ($PCat & 32) { $WantedDT = 100; } // Deep Space       
      else if ($PCat & 64) { $WantedDT = 101; } // Intelligence
      
      $Di = -10;
      foreach ($Dis[$Hi] as $Dix) {
        if ($Dix['Type'] == $WantedDT) {
          $Di = $Dix['id'];
          break;
        }
      }
      
      if ($Di == -10) {
        echo "Faulty project " . $P['id'];
      } else {
      
        $Pro['MaxRush'] =  (($ProjTypes[$P['Type']]['BasedOn'])? Has_Tech($Fid,$ProjTypes[$P['Type']]['BasedOn']) : (isset ($District_Type[5]) ?$District_Type[5]:0));
        if ($PH['Type'] != $Faction['Biosphere']) $Pro['MaxRush'] = max(0,$Pro['MaxRush']-1);
        $Pro['Status'] = 'Started';
        $TotProg = 0;
      
//var_dump($Pro, $ProjTypes[$P['Type']]);  echo "<p>";    
        $TSi = 0;
        for ($t = $P['TurnStart']; $t <= ($P['TurnEnd']?$P['TurnEnd']:$P['TurnStart']+50); $t++) {

          $Pro['Rush'] = $Rush = 0;
          if (isset($TurnStuff[$TSi])) {
            if ($TurnStuff[$TSi]['TurnNumber'] == $t) {
              $Rush = $Pro['Rush'] = $TurnStuff[$TSi]['Rush'];
              $TSi ++;
            }
          }
          $Prog = min($Pro['Acts'],$Pro['MaxRush'] + $Rush);
          if ($TotProg || $Prog >= $Pro['Acts'] ) {
            $TotProg += $Prog;
            if ($TotProg >= $Pro['Acts']) {
              $Pro['Progress'] = $Pro['Acts'] . "/" . $Pro['Acts'];
              $Pro['Status'] = 'Complete';
            } else {
              $Pro['Progress'] = "$TotProg/" . $Pro['Acts'];
              $Pro['Status'] = 'Ongoing';
            }
          } else {
              $TotProg += $Prog;
              $Pro['Progress'] = "$TotProg/" . $Pro['Acts'];
              $Pro['Status'] = 'Started';
          }
          
        
          $Proj[$t][$Hi][$Di] = $Pro;
          $Pro['Cost'] = 0;
          if ($Pro['Status'] == 'Complete') break;
        }
      }
    }
  $PHx++;
  }
  


//var_dump($Homes);
//var_dump($Proj);
//var_dump($Dis);

/*    
    echo "<th id=PHH$PHx><button class=ProjHome id=PHome$PHx>" . $PH['Name'] . "</button>";
    
    $p=1;
    echo "<th id=PJCol$p:$PHx>[S]<th id=PJCol$p>Construction<th id=PJCol$p:$PHx>Rush<th id=PJCol$p:$PHx>Prog<th id=PJCol$p:$PHx>State";
        
    foreach ($Dists as &$D) {
      $DistType = $DistTypes[$D['Type']];
      if (!($DistType['Props'] & 2)) continue;
      $p++;
      $Dtname = $DistType['Name'];  // add cost
      echo "<th class=PJCol$p:$PHx>[S]<th class=PJCol$p>$Dtname<th class=PJCol$p:$PHx>Rush<th class=PJCol$p:$PHx<th class=class=PJCol$p:$PHx>State";
      }

    
    }
*/
  // That is just the nested headers  
  
  
  // Now Present the data
    
  echo "<form method=post action=ProjDisp.php>";
  echo fm_hidden('DistData', base64_encode(json_encode($Dis)));  
  Register_AutoUpdate('Projects',$Fid);
  echo fm_hidden('id',$Fid);

  echo "<table border>";
  echo $Headline1;
  echo "</table>";
  echo "<table border>";
  


  echo "<tr><td>Turn";
  foreach ($Headline2 as $Hl) echo $Hl;
  echo "<th>Total Cost<th>Credits Left";
  for($Turn=0; $Turn<($GAME['Turn']+50); $Turn++) {
    $RowClass = "ProjHide";
    $hide = ' hidden';
    if ($Turn >= ($GAME['Turn']-1) && $Turn < ($GAME['Turn']+9)) {
        $RowClass = 'ProjShow';
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
    
    foreach ($Homes as &$H) {

      if (isset($H['Skip'])) continue;
      if (!isset($H['id'])) continue;

      $Hi = $H['id'];
      if (isset($Dis[$Hi])) foreach($Dis[$Hi] as $Dix) {
        $Di = $Dix['id'];
//echo "Doing $Hi - $Di<br>";
        // TODO ids need setting...
      // TODO Warning
      
        echo "<td $BG id=ProjS$Turn:$Hi:$Di class='PHStart Group$Di Home$Hi' hidden>";
        if ($Turn >= $GAME['Turn']) {
          $Warn = '';
          if (isset($Proj[$Turn - 1 ][$Hi][$Di]['Status']) && ($Proj[$Turn - 1 ][$Hi][$Di]['Status'] == 'Started' || $Proj[$Turn-1][$Hi][$Di]['Status'] == 'Ongoing')) {
            $Warn = "onclick=\"return confirm('Do you want to abandon " . $Proj[$Turn-1][$Hi][$Di]['Name'] . '?\'"';
          }
          echo "<button type=submit class=PHStartButton id=Start:STurn:$Hi:$Di $Warn formaction=ProjNew.php?t=$Turn&Hi=$Hi&Di=$Di><b>+</b>"; 
        }
        if (isset($Proj[$Turn][$Hi][$Di]['Type'])) {
          $PN = $Proj[$Turn][$Hi][$Di]['id'];
          echo "<td $BG id=ProjN$Turn:$Hi:$Di class='PHName Home$Hi'><a href=ProjEdit.php?id=" . $Proj[$Turn][$Hi][$Di]['id'] . ">" . 
                $Proj[$Turn][$Hi][$Di]['Name'] . "</a>";
          echo "<td $BG id=ProjL$Turn:$Hi:$Di class='PHLevel Group$Di Home$Hi' hidden>" . $Proj[$Turn][$Hi][$Di]['Level'];
          echo "<td $BG id=ProjC$Turn:$Hi:$Di class='PHCost Group$Di Home$Hi' hidden>" . $Proj[$Turn][$Hi][$Di]['Cost'];
          echo "<td $BG id=ProjR$Turn:$Hi:$Di class='PHRush Group$Di Home$Hi' hidden>" . (($Turn < $GAME['Turn'])?$Proj[$Turn][$Hi][$Di]['Rush'] : 
               "<input type=number id=Rush$Turn:$PN name=Rush$Turn:$PN oninput=RushChange($Turn,$PN,$Hi,$Di," . 
               $Proj[$Turn][$Hi][$Di]['MaxRush'] . ") value=" . $Proj[$Turn][$Hi][$Di]['Rush'] .
               " min=0 max=" . $Proj[$Turn][$Hi][$Di]['MaxRush'] .">" );
          echo "<td $BG id=ProjP$Turn:$Hi:$Di class='PHProg Group$Di Home$Hi' hidden>" . $Proj[$Turn][$Hi][$Di]['Progress'];
          echo "<td $BG id=ProjT$Turn:$Hi:$Di class='PHStatus Group$Di Home$Hi' hidden>" . $Proj[$Turn][$Hi][$Di]['Status'] . "";
          
          $TotCost += $Proj[$Turn][$Hi][$Di]['Cost'] + $Proj[$Turn][$Hi][$Di]['Rush']*Rush_Cost($Fid);
          
        } else {
          echo "<td $BG id=ProjN$Turn:$Hi:$Di class='PHName Home$Hi'>";
          echo "<td $BG id=ProjL$Turn:$Hi:$Di class='PHLevel Group$Di Home$Hi' hidden>";
          echo "<td $BG id=ProjC$Turn:$Hi:$Di class='PHCost Group$Di Home$Hi' hidden>";
          echo "<td $BG id=ProjR$Turn:$Hi:$Di class='PHRush Group$Di Home$Hi' hidden>";
          echo "<td $BG id=ProjP$Turn:$Hi:$Di class='PHProg Group$Di Home$Hi' hidden>";
          echo "<td $BG id=ProjT$Turn:$Hi:$Di class='PHStatus Group$Di Home$Hi' hidden>";
        }
      }
//      break;
    }
    
    echo "<td id=TotCost$Turn class=PHTurn>$TotCost<td class=PHTurn>?";
  }
  echo "</table></form>";    
  
  echo "<table border>";
  if (Access('God')) echo "<tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea>";
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
