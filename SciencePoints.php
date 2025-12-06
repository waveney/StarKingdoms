<?php
  include_once("sk.php");
  global $FACTION,$GAMEID,$USER;
  include_once("GetPut.php");
  include_once("PlayerLib.php");
  include_once("ThingLib.php");

global $GAME,$ModValues,$Fields,$Tech_Cats,$CivMil,$ThingInstrs,$ThingInclrs,$Currencies;

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
  $TechFields = [['Engineering','EngineeringSP'],['Physics','PhysicsSP'],['Xenology','XenologySP']];

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
          echo 'Using ' . $Br['Cost'] . ' ' . $TechFields[$Br['Field']][0] . " science points " . $Tech['Name'] . " has been raised to level " . $Br['Level'];
          Gain_Science($Fid,$TechFields[$Br['Field']][0],-$Br['Cost'],"Raising " . $Tech['Name'] . " to level " . $Br['Level']);

        } else {
          echo "You already know it.<p>";
        }
      } else if ($CTech['Level'] == 0) { // Supp
        $CTech['Level'] = 1;
        Put_Faction_Tech($CTech);
        echo 'Using ' . $Br['Cost'] . ' ' . $TechFields[$Br['Field']][0] . " science points " . $Tech['Name'] . " has been researched";
        Gain_Science($Fid,$TechFields[$Br['Field']][0],-$Br['Cost'],"Researching " . $Tech['Name'] . " (Level: " . $Br['Level'] . ")");
      }
      $Br['DoneTurn'] = $GAME['Turn']-1;
      Gen_Put('Breakthroughs',$Br);
      $Faction = Get_Faction($Fid);
      break;
    }
  }

/* Select types
  Name, Class, What, sub cat, where, move, Level, Action
  */


  $SPCosts = [5,5,20,45,80,125,180,245,320,405,500,605,720,845,980];

  $Ts = Get_Techs();

  $CTs = Get_CoreTechs();
  $FactTechs = Get_Faction_Techs($Fid);
  $CTNames = Get_CoreTechsByName();
  $Avail = [$Faction['EngineeringSP'],$Faction['PhysicsSP'],$Faction['XenologySP']];
  $Used = [0,0,0];

  echo "<h1>Spend Science Points, <a href=ScienceLog.php>Resource Logs</a></h1>";

  echo "<table border style='width:300'><tr><th>Tech Level<th>Points Required";
  for ($i = 1;$i<6; $i++) echo "<tr><td>$i<td>" . $SPCosts[$i];
  echo "</table>";

  $Breaks = Gen_Get_Cond('Breakthroughs',"FactionId=$Fid AND Turn=" . $GAME['Turn']);

  if ($Breaks) {
    echo "<h2>Planned Breakthroughs</h2>";
    echo "These will be done at the beginning of the next turn, before any projects, instructions or operations start.<br>";
    echo "If you change your mind click cancel.   If you really need it now - click do now (no going back)<p>";
    echo "<table border><tr><td>Field<td>Technology<td>Level<td>Cost<td>Cancel\n";
    foreach($Breaks as $Br) {
      echo "<tr><td>" . $TechFields[$Br['Field']][0] . "<td>" . $Ts[$Br['TechId']]['Name'] . "<td>" . $Br['Level'] . "<td>" . $Br['Cost'];

    if ($Faction['TurnState'] == 4 ) continue;
      if ($Br['DoneTurn']) {
        echo "<td>Already Done";
      } else {
        echo "<td><a href=SciencePoints.php?ACTION=Cancel&Bid=" . $Br['id'] . ">Cancel</a>, <a href=SciencePoints.php?ACTION=Now&Bid=" .
        $Br['id'] . ">Do Now</a>\n";
        $Used[$Br['Field']] += $Br['Cost'];
      }

    }
    echo "</table><p>\n";
  }

  echo "<p>";
  foreach($TechFields as $Fld=>$TC) {
    [$FName,$FldName] = $TC;
    echo "Current $FName Points: " . $Avail[$Fld] . " Used: " . $Used[$Fld] . " Left: " . ($Avail[$Fld] - $Used[$Fld]) . "<br>";
  }

  echo "<form method=post action=SciencePoints.php>";
  echo fm_hidden('id',$Fid);
  foreach($TechFields as $Fld=>$TC) {
    [$FName,$FldName] = $TC;
    echo "<h1>$FName Science Points</h1>\n";

    echo "Current Points: " . $Avail[$Fld] . " Used: " . $Used[$Fld] . " Left: " . ($Avail[$Fld] - $Used[$Fld]);

    if (($Avail[$Fld] - $Used[$Fld]) < 5) continue;

    if ($Faction['TurnState'] == 4 ) continue;

    echo "<table border class=striped >";
      $Head = 0;
      foreach ($CTs as $TT) {
        if ($TT['Field'] != $Fld) continue;
        foreach($Breaks as $Br) if ($Br['TechId'] == $TT['id']) continue 2;
        if (isset($FactTechs[$TT['id']]['Level'])) {
          $Lvl = $FactTechs[$TT['id']]['Level']+1;
        } else {
          $Lvl = 1;
        }

        if ($Avail[$Fld]-$Used[$Fld] >= $SPCosts[$Lvl]) {
          if ($Head++ == 0) echo "<tr><td colspan=2><h2>Research Core $FName Technology</h2>";
          echo "<tr><td><button class=projtype type=submit formaction='SciencePoints.php?ACTION=NEW&id=$Fid&Tid=" . $TT['id'] . "&L=$Lvl&C=" .
                $SPCosts[$Lvl] . "&Fld=$Fld'>" .
                "Research " . $TT['Name'] . " Level $Lvl; Cost " . $SPCosts[$Lvl] . ' ' . $TechFields[$Fld][0] . "</button>" .
                "<td><div class=ProjDesc>" . ParseText($TT['Description']) . "</div></tr>";
        }
      }

      $Head = 0;

      $Techs = Get_TechsByCore($Fid);
      foreach ($Techs as $T) {
        if ($T['Field'] != $Fld) continue;
        foreach($Breaks as $Br) if ($Br['TechId'] == $T['id']) continue 2;
        if ($T['Cat'] == 0 || (isset($FactTechs[$T['id']]) && $FactTechs[$T['id']]['Level'])) continue;
        if (!isset($FactTechs[$T['PreReqTech']]) ) continue;
        if ( ($FactTechs[$T['PreReqTech']]['Level']<$T['PreReqLevel'] ) ) continue;
        if ($T['PreReqTech2'] && !isset($FactTechs[$T['PreReqTech2']])) continue;
        if ($T['PreReqTech3'] && !isset($FactTechs[$T['PreReqTech3']])) continue;
        $Lvl = $T['PreReqLevel'];
        if ($Avail[$Fld]-$Used[$Fld] >= $SPCosts[$Lvl]) {
          if ($Head++ == 0) echo "<tr><td colspan=2><h2>Research Supplimental $FName Technology</h2>";
          echo "<tr><td><button class=projtype type=submit formaction='SciencePoints.php?ACTION=NEW&id=$Fid&Tid=" . $T['id'] . "&L=$Lvl&C=" .
                $SPCosts[$Lvl] . "&Fld=$Fld'>" .
                "Research " . $T['Name'] . " Level $Lvl; Cost " . $SPCosts[$Lvl] . ' ' . $TechFields[$Fld][0] . "</button>" .
                "<td><div class=ProjDesc>" . ParseText($T['Description']) . "</div></tr>";

        }
      }
      echo "</table>";

  }
  echo "</form>";

  dotail();
?>
