<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");

  global $FACTION;

  if ($GM = Access('GM') ) {

  } else if (Access('Player')) {
    if (!$FACTION) {
      Error_Page("Sorry you need to be a GM or a Player to access this");
    }
  }

  A_Check('Player');
  dostaffhead("Technologies");
  if ($GM) {
    if (isset($_REQUEST['FORCE'])) {
      $GM = 0;
    } else {
      echo "<h2><a href=TechShow.php?PLAYER&FORCE>This list in Player Mode</a></h2>";
    }
  }

  $CTs = Get_CoreTechsByName();
  $CTNs = [];
  $CTNs[0] = '';
  foreach ($CTs as $TT) $CTNs[$TT['id']] = $TT['Name'];


  if (isset($_REQUEST['f'])) {
    $Fid = $_REQUEST['f'];
  } else   if (isset($_REQUEST['F'])) {
    $Fid = $_REQUEST['F'];
  } else {
    $Fid = 0;
  }

  if ($FACTION) {
    $Fid = $FACTION['id'];
  } else {
    $FACTION = Get_Faction($Fid);
  }

//  if (!Access('GM') && $FACTION['TurnState'] > 2) Player_Page();
//var_dump($Techs); exit;

  $TechFacts = [];
  if ($Fid) {
    $TechFacts = Get_Faction_Techs($Fid);
  }

  $Xtra = '';
  if ($Fid) $Xtra = "&id=$Fid";
  $Setup = isset($_REQUEST['SETUP']);
  if (isset($_REQUEST['FORCE'])) $Xtra .= "&FORCE";
  if (isset($_REQUEST['PLAYER'])) $Xtra .= "&PLAYER";
  if ($Setup) $Xtra .= "&SETUP";

  $Blue = 0;
  if (isset($_REQUEST['Blue'])) {
    echo "<div class=floatright><h2>Showing All Techs, including Blue Prints <a href=TechShow.php?$Xtra>No Blue Prints</a></h2></div>";
    $AllG = 1;
  } else {
    echo "<div class=floatright><h2>Showing All Teches except Blueprints -  Switch to <a href=TechShow.php?Blue$Xtra>Show Blue Prints</a></h2></div>";
  }


  $Techs = Get_TechsByCore($Fid,($Setup?1:0));
//var_dump ($TechFacts);
//var_dump($FACTION);
//var_dump($Techs);exit;

  echo "<h2>Technologies</h2>\n";
  echo "<div class=floatright id=ExpandAll><button type=button onclick=TechSet('ALL')>Expand All</button></div><p>";
  echo "<div class=floatright id=ContractAll hidden><button type=button onclick=TechSet('NONE')>Contract All</button></div><p>";
  echo "Click on technologies name to Toggle showing the definition and examples\n<p>";

//  if (Access('God')) echo "<table></tbody><tfoot><tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea></table>";
  if ($Setup) {
    echo "<form method=post>";
    Register_AutoUpdate('FactTech',$Fid);
  }
  foreach ($CTs as $ctind=>$CT) {
    echo "<div class=floatright><h2>" .
      "<button type=button id=ShowSupTechs$ctind onclick=TechSet($ctind)>Show Supplemental " . $CT['Name'] . " Techs</button>" .
      "<button type=button id=HideSupTechs$ctind onclick=HideTechSet($ctind) hidden>Hide Supplemental " . $CT['Name'] . " Techs</button>" .
      "</h2></div>";
    Show_Tech($CT,$CTNs,$FACTION,$TechFacts,0,$Setup);

    echo "<div id=SupTechs$ctind hidden>";
    foreach ($Techs as $T) {
//      echo "Checking "  . $T['Name'] . " Against " . $CT['Name']
      if (($T['Cat']>0) && ($T['PreReqTech'] == $CT['id'])) {
        if ((($T['Properties']&8) == 8) && !Access('God')) continue;
//        echo "Found " . $T['Name'] . "<br>";
        Show_Tech($T,$CTNs,$FACTION,$TechFacts,0,$Setup,1); //,' hidden');
      }
    }
    echo "</div>";
//    echo "</div></div>";
  }

  $Specials = Gen_Get_Cond('Technologies','Cat=3');
  $Head = 0;

  foreach ($Specials as $Sid=>$S) {
    if ($GM || isset($TechFacts[$Sid])) {
      if (!$Head) {
        echo "<h2>Special Technologies Outside the Normal Tech Trees</h2>";
        $Head = 1;
      }
      Show_Tech($S,$CTNs,$FACTION,$TechFacts,0,$Setup,1);
    }
  }

  if (Access('God')) echo "<table><tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea></table>";


  dotail();

  echo "<p>";


