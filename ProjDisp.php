<?php

// Scan all plantets/moon's/Thing's for districts and deep space construction

  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("PlayerLib.php");
  include_once("SystemLib.php");
  include_once("ProjLib.php");
  
  global $FACTION;
  
  if (Access('GM') ) {
    A_Check('GM');
    $Fid = $_REQUEST['id'];
    $Faction = Get_Faction($Fid);
  } else if (Access('Player')) {
    if (!$FACTION) {
      Error_Page("Sorry you need to be a GM or a Player to access this");
    }
    $Fid = $FACTION['id'];
    $Faction = &$FACTION;
  }

  dostaffhead("Display Projects for faction");
  
  echo "<h1>Display Projects</h1>";
 
  echo "<div class=floatright style=text-align:right><div class=Bespoke>" .
       "Showing:<button class=BigSwitchSelected id=BespokeM onclick=Add_Bespoke()>10 Turns</button><br>" .  
       "Switch to: <button class=BigSwitch id=GenericM onclick=Add_Bespoke()>All Turns</button></div>" .
       "<div class=Bespoke hidden id=BespokeMess>" .
       "Showing:<button class=BigSwitchSelected id=GenericM1 onclick=Remove_Bespoke()>All Turns</button><br>" .  
       "Switch to: <button class=BigSwitch id=BespokeM1 onclick=Remove_Bespoke()>10 Turns</button></div>" .
       "</div>";

  echo "Click on Place to Expand/Contract<P>";
  echo "Click on History top right to see more than 1 turn back<p>\n";
  echo "Click on [S] buttons to start/change projects<p>\n";
  echo "Click up/down or write number to rush projects<p>\n";
  
  $Homes = Get_ProjectHomes($Fid);
  $DistTypes = Get_DistrictTypes();
  $ProjTypes = Get_ProjectTypes();
  

  $Headline1 = '';
  $Headline2 = [];
//  $Turns = [][];
  
// Stage 1 Scan the data
  
  $PHx = 1;
  $Dis = [];
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
    
    $Headline1 .= "<th id=PHH$PHx><button class=ProjHome id=PHome$PHx>" . $PH['Name'] . "</button>";
    

    foreach ($Dists as &$D) {
      if ($D['Type'] > 0 && (($DistTypes[$D['Type']]['Props'] &2) == 0)) continue;
      if ($D['Type'] < 0 && $PH['Type'] != $Faction['Biosphere'] && Has_Tech($Fid,3)<2 ) continue;
      $Di = $D['id'];
      $Headline2[] = "<th class=PHStart id=PHDist$Hi:$Di>[S]<th class=PHName>" . ($D['Type'] > 0 ? $DistTypes[$D['Type']]['Name'] : 'Construction') .
        "<th class=PHLevel id=PHLevel$Hi:$Di>Level<th class=PHCost id=PHCost$Hi:$Di>Cost<th class=PHRush id=PHRush$Hi:$Di>Rush" .
        "<th class=PHProg id=PHProg$Hi:$Di>Prog<th class=PHStatus id=PHStatus$Hi:$Di>Status";
     
      $Dis[$Hi][] = $Di;
      }

//var_dump($Dis);exit;
    
    $Projs = Get_Projects($Hi);
    foreach ($Projs as &$P) {
      $TurnStuff = Get_ProjectTurns($P['id']);
      $Pro = [];
      $Pro['id'] = $P['id'];
      $Pro['Type'] = $P['Type'];
      $Pro['Name'] = $ProjTypes[$P['Type']]['Name'];
      $Pro['Level'] = $P['Level'];
      if ($ProjTypes[$P['Type']]['StandardCosts']) {
        $Dat = Proj_Costs($P['Level']);
        $Pro['Cost'] = $Dat[1];
        $Pro['Acts'] = $Dat[0];
      } else if ($ProjTypes[$P['Type']]['Cost']) {
        $Pro['Cost'] = $ProjTypes[$P['Type']]['Cost'];
        $Pro['Acts'] = $ProjTypes[$P['Type']]['CompTarget'];
      } else { 
        // TODO 
      }
      
      $Pro['MaxRush'] = Has_Tech($Fid,$ProjTypes[$P['Type']]['BasedOn']);
      if ($PH['Type'] != $Faction['Biosphere']) $Pro['MaxRush'] = max(0,$Pro['MaxRush']-1);
      $Pro['Status'] = 'Started';
      $Pro['TotProg'] = 0;
      
      
      $TSi = 0;
      for ($t = $P['TurnStart']; $t <= $P['TurnEnd']; $t++) {

        $Rush = 0;
        if (isset($TurnStuff[$TSi])) {
          if ($TurnStuff[$TSi]['TurnNumber'] == $t) {
            $Rush = $Pro['Rush'] = $TurnStuff[$TSi]['Rush'];
            $TSi ++;
          }
        }
        $Prog = max($Pro['Acts'],$Pro['MaxRush'] + $Rush);
        if ($Pro['TotProg'] || $Prog >= $Pro['Acts'] ) {
          $Pro['TotProg'] += $Prog;
          if ($Pro['TotProg'] >= $Pro['Acts']) {
            $Pro['Progress'] = "$Prog/" . $Pro['Acts'];
            $Pro['Status'] = 'Complete';
          } else {
            $Pro['Progress'] = "$Prog/" . $Pro['Acts'];
            $Pro['Status'] = 'Ongoing';
          }
        } else {
            $Pro['Progress'] = "$Prog/" . $Pro['Acts'];
            $Pro['Status'] = 'Started';
        }
        
        $Proj[$t][$Hi][$Di] = $Pro;        
      }
    }
  }

//var_dump($Homes);


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
    $PHx++;
    
    }
*/
  // That is just the nested headers  
  
  
  // Now Present the data
    
  echo "<form method=post action=ProjDisp.php>";
  Register_AutoUpdate('Projects',$Fid);
  echo fm_hidden('id',$Fid);

  echo "<table border>";
  echo $Headline1;
  echo "</table>";
  echo "<table border>";
  

  echo "<tr><td>Turn#";
  foreach ($Headline2 as $Hl) echo $Hl;
  for($Turn=0; $Turn<($GAME['Turn']+10); $Turn++) { // 50 for real code
    $RowClass = "ProjFutureHide";
    if ($Turn < $GAME['Turn']) {
      if ($Turn < $GAME['Turn']-2) {
        $RowClass = 'ProjPastHide';
      } else {
        $RowClass = 'ProjPastShow';
      }
    } else if ($Turn == $GAME['Turn']) {
      $RowClass = 'ProjCurrent';
    } else if ($Turn < $GAME['Turn']+7) {
      $RowClass = 'ProjFutureShow';        
    }
      
    echo "<tr class=$RowClass><td>$Turn";

    foreach ($Homes as $H) {

      if (isset($H['Skip'])) continue;
      if (!isset($H['id'])) continue;
      $Hi = $H['id'];
//var_dump("Dis",$Dis[$Hi]);
      if (isset($Dis[$Hi])) foreach($Dis[$Hi] as $Di) {
        // TODO ids need setting...
        echo "<td id=ProjS$Turn:$Hi:$Di><button class=PHStart id=xx action=ProjNew.php?t=$Turn&Hi=$Hi&Di=$Di>[S]"; // Need Warning
        if (isset($Proj[$Turn][$Hi][$Di]['Type'])) {
          echo "<td id=ProjN$Turn:$Hi:$Di class=PHName><a href=ProjEdit.php?id=" . $Proj[$Turn][$Hi][$Di]['id'] . ">" . $Proj[$Turn][$Hi][$Di]['Name'] . "</a>";
          echo "<td id=ProjL$Turn:$Hi:$Di class=PHLevel>" . $Proj[$Turn][$Hi][$Di]['Level'];
          echo "<td id=ProjC$Turn:$Hi:$Di class=PHCost>" . $Proj[$Turn][$Hi][$Di]['Cost'];
          echo "<td id=ProjR$Turn:$Hi:$Di class=PHRush>" . (($Turn < $GAME['Turn'])?$Proj[$Turn][$Hi][$Di]['Rush'] : 
               "<input type=number id=Rush$x:$y name=Rush$x:$y onchange... min=0 max=" . $Proj[$Turn][$Hi][$Di]['MaxRush'] );
          echo "<td id=ProjP$Turn:$Hi:$Di class=PHProg>" . $Proj[$Turn][$Hi][$Di]['Progress'];
          echo "<td id=ProjT$Turn:$Hi:$Di class=PHStatus>" . $Proj[$Turn][$Hi][$Di]['Status'] . "";
        } else {
          echo "<td id=ProjN$Turn:$Hi:$Di class=PHName>";
          echo "<td id=ProjL$Turn:$Hi:$Di class=PHLevel>";
          echo "<td id=ProjC$Turn:$Hi:$Di class=PHCost>";
          echo "<td id=ProjR$Turn:$Hi:$Di class=PHRush>";
          echo "<td id=ProjP$Turn:$Hi:$Di class=PHProg>";
          echo "<td id=ProjT$Turn:$Hi:$Di class=PHStatus>";
        }
      }
    }
  }
  echo "</table></form>";    
    
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
