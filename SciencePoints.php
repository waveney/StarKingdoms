<?php
  include_once("sk.php");
  global $FACTION,$GAMEID,$USER;
  include_once("GetPut.php");
  include_once("PlayerLib.php");
  include_once("ThingLib.php");
  
global $GAME,$ModValues,$Fields,$Tech_Cats,$CivMil,$BuildState,$ThingInstrs,$ThingInclrs;

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
  $GM = Access('GM');
  if ($GM) {
    if (isset( $_REQUEST['F'])) {
      $Fid = $_REQUEST['F'];
    } else if (isset( $_REQUEST['f'])) {
      $Fid = $_REQUEST['f'];
    } else if (isset( $_REQUEST['id'])) {
      $Fid = $_REQUEST['id'];
    }
    if (isset($Fid)) $Faction = Get_Faction($Fid);
  }

  dostaffhead("Science Points",["js/ProjectTools.js"]);
  $TechCats = [['Engineering','EngineeringSP'],['Physics','PhysicsSP'],['Xenology','XenologySP']];

// var_dump($_REQUEST);
  
  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
    case 'Cancel':
      db_delete('Breakthroughs',$_REQUEST['Bid']);
      break;
    case 'NEW':
      $Bt = ['FactionId'=>$Fid, 'TechId'=> $_REQUEST['Tid'], 'Level'=>$_REQUEST['L'], 'Field'=>$_REQUEST['Fld'], 'Cost'=>$_REQUEST['C'], 
             'Turn'=>$GAME['Turn'], 'Game'=>$GAMEID];
// echo "<br>"; var_dump($Bt);
      Gen_Put('Breakthroughs',$Bt);
      break;
    case 'Now':
      $Br = Gen_Get('Breakthroughs',$_REQUEST['Bid']);
      
      $Tid = $Br['TechId'];

      $CTech = Get_Faction_TechFT($Fid,$Tid);
      $Tech = Get_Tech($Tid);

      if ($Tech['Cat'] == 0) { // Core
        if ($CTech['Level'] < $Br['Level']) {
          $CTech['Level'] = $Br['Level'];
          Put_Faction_Tech($CTech);
          echo 'Using ' . $Br['Cost'] . $TechCats[$Br['Field']][0] . " science points " . $Tech['Name'] . " has been raised to level " . $Br['Level'];
          $Faction[$TechCats[$Br['Field']][1]] = max(0, $Faction[$TechCats[$Br['Field']][1]] - $Br['Cost']);
          Put_Faction($Faction);
        } else {
          echo "You already know it.<p>";
        }
      } else if ($CTech['Level'] == 0) { // Supp
        $CTech['Level'] = 1;
        Put_Faction_Tech($CTech);
        echo 'Using ' . $Br['Cost'] . $TechCats[$Br['Field']][0] . " science points " . $Tech['Name'] . " has been researched";
        $Faction[$TechCats[$Br['Field']][1]] = max(0, $Faction[$TechCats[$Br['Field']][1]] - $Br['Cost']);
        Put_Faction($Faction);
      }
      $Br['DoneTurn'] = $GAME['Turn']-1;
      Gen_Put('Breakthroughs',$Br);
      break;
    }
  }
  
/* Select types 
  Name, Class, What, sub cat, where, move, Level, Action
  */
  

  $SPCosts = [0,5,20,45,80,125,180,245,320,405,500,605,720,845,980];
  
  $Ts = Get_Techs();

  $CTs = Get_CoreTechs();
  $FactTechs = Get_Faction_Techs($Fid);
  $CTNames = Get_CoreTechsByName();
  $Avail = [$Faction['EngineeringSP'],$Faction['PhysicsSP'],$Faction['XenologySP']];
  $Used = [0,0,0];

  echo "<h1>Spend Science Points</h1>";
/*
  echo "<h2>Current Points</h2>";
  foreach($TechCats as $Fld=>$TC) {
    [$FName,$FldName] = $TC;
    echo "<b>$FName Science Points</b>: " . $Avail[$Fld] . "<br>\n";
  } 
*/    
  $Breaks = Gen_Get_Cond('Breakthroughs',"FactionId=$Fid AND Turn=" . $GAME['Turn']);
  
  if ($Breaks) {
    echo "<h2>Planned Breakthroughs</h2>";
    echo "<table border><tr><td>Field<td>Technology<td>Level<td>Cost<td>Cancel\n"; 
    foreach($Breaks as $Br) {
      echo "<tr><td>" . $TechCats[$Br['Field']][0] . "<td>" . $Ts[$Br['TechId']]['Name'] . "<td>" . $Br['Level'] . "<td>" . $Br['Cost'];
      
      if ($Br['DoneTurn']) {
        echo "<td>Already Done";
      } else {
        echo "<td><a href=SciencePoints.php?ACTION=Cancel&Bid=" . $Br['id'] . ">Cancel</a>, <a href=SciencePoints.php?ACTION=Now&Bid=" . $Br['id'] . ">Do Now</a>\n";
        $Used[$Br['Field']] += $Br['Cost'];
      }

    }
    echo "</table>\n";
  }
  
  echo "<form method=post action=SciencePoints.php>";
  echo fm_hidden('id',$Fid);
  foreach($TechCats as $Fld=>$TC) {
    [$FName,$FldName] = $TC;
    echo "<br><h1>$FName Science Points</h1>\n";
    
    echo "Current Points: " . $Avail[$Fld] . " Used: " . $Used[$Fld] . " Left: " . ($Avail[$Fld] - $Used[$Fld]);
    
    if (($Avail[$Fld] - $Used[$Fld]) < 5) continue;
    
  
    echo "<h2>Research Core Technology</h2>";
      foreach ($CTs as $TT) {
        if ($TT['Field'] != $Fld) continue;
        foreach($Breaks as $Br) if ($Br['TechId'] == $TT['id']) continue 2;
        if (isset($FactTechs[$TT['id']]['Level'])) {
          $Lvl = $FactTechs[$TT['id']]['Level']+1;
        } else {
          $Lvl = 1;
        }

        if ($Avail[$Fld]-$Used[$Fld] >= $SPCosts[$Lvl]) {
          echo "<button class=projtype type=submit formaction='SciencePoints.php?ACTION=NEW&id=$Fid&Tid=" . $TT['id'] . "&L=$Lvl&C=" . $SPCosts[$Lvl] . "&Fld=$Fld'>" .      
                "Research " . $TT['Name'] . " Level $Lvl; Cost " . $SPCosts[$Lvl] . "</button><p>";  
        }
      }
 
    echo "<h2>Research Supplimental Technology</h2>";
      $Techs = Get_TechsByCore($Fid);
      foreach ($Techs as $T) {
        if ($T['Field'] != $Fld) continue;
        foreach($Breaks as $Br) if ($Br['TechId'] == $T['id']) continue 2;
        if ($T['Cat'] == 0 || (isset($FactTechs[$T['id']]) && $FactTechs[$T['id']]['Level'])) continue;
        if (!isset($FactTechs[$T['PreReqTech']]) ) continue;
        if ( ($FactTechs[$T['PreReqTech']]['Level']<$T['PreReqLevel'] ) ) continue;
        $Lvl = $T['PreReqLevel'];
        if ($Avail[$Fld]-$Used[$Fld] >= $SPCosts[$Lvl]) {
          echo "<button class=projtype type=submit formaction='SciencePoints.php?ACTION=NEW&id=$Fid&Tid=" . $T['id'] . "&L=$Lvl&C=" . $SPCosts[$Lvl] . "&Fld=$Fld'>" .      
                "Research " . $T['Name'] . " Level $Lvl; Cost " . $SPCosts[$Lvl] . "</button><p>";  
        }
      }
  
  }
  echo "</form>";

  dotail();  
?>
