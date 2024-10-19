<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("ProjLib.php");
  include_once("HomesLib.php");
  include_once("SystemLib.php");
  include_once("vendor/erusev/parsedown/Parsedown.php");
  global $FACTION,$Fid,$GAMEID;
  $Parsedown = new Parsedown();

  function WorldName(&$World) {
    global $Fid;
    static $WorldNames = [];
    $hid = $World['id'];
    if (isset($WorldNames[$hid])) return $WorldNames[$hid];

    $Tid = $World['ThingId'];
    switch ($World['ThingType']) {
      case 1: // Planet
        $P = Get_Planet($Tid);
        $System = Get_System($P['SystemId']);
        $HName = $P['Name'] . " in " . System_Name($System,$Fid);
        $Link = "WorldEdit.php?id=$hid";
        break;
      case 2: // Moon
        $M = Get_Moon($Tid);
        $P = Get_Planet($M['PlanetId']);
        $System = Get_System($P['SystemId']);
        $HName = $M['Name'] . " a moon of " . $P['Name'] . " in " . System_Name($System,$Fid);
        $Link = "WorldEdit.php?id=$hid";
        break;
      case 3: // Thing
        $T = Get_Thing($Tid);
        $System = Get_System($T['SystemId']);
        $HName = $T['Name'] . " a " . $T['Class'];
        $Link = "ThingEdit.php?id=$Tid";
        break;
      default: // Error
        echo "<h2 class=Err>There is a problem with World $hid - Tell Richard</h2>";
        dotail();

    }
    $WorldNames[$hid] = $HName;
    return $HName;
    // Link not YET used;
  }


  dostaffhead("List of Social Principles",["js/ProjectTools.js"]);
  $Fid = 0;
  $xtra = '';
  if (Access('Player')) {
    if (!isset($FACTION['id'])) {
      if (!Access('GM') ) Error_Page("Sorry you need to be a GM or a Player to access this");
    } else {
      $Fid = $FACTION['id'];
      $Faction = &$FACTION;
    }
  }
  if ($GM = Access('GM') ) {
    A_Check('GM');
    if (isset( $_REQUEST['F'])) {
      $Fid = $_REQUEST['F'];
    } else if (isset( $_REQUEST['f'])) {
      $Fid = $_REQUEST['f'];
    } else if (isset( $_REQUEST['id'])) {
      $Fid = $_REQUEST['id'];
    }
    if (isset($Fid)) $Faction = Get_Faction($Fid);
    if (isset($_REQUEST['FORCE'])) {
      $GM = 0;
    } else {
      if ($Fid) echo "<h2>GM: <a href=ListSocial.php?id=$Fid&FORCE>This page in Player Mode</a></h2>";
    }
  }
  A_Check('Player');
//  CheckFaction('WorldList',$Fid);

  if (!Access('GM') && $Faction['TurnState'] > 2) Player_Page();
  echo "<h1>Social Principles</h1>";

  $Worlds = Get_Worlds($Fid);
  $PlanetTypes = Get_PlanetTypes();
  $Homes = Get_ProjectHomes($Fid);
  foreach ($Worlds as $wi=>$W) $Homes[$W['Home']]['World'] = $wi;
  $TTypes = Get_ThingTypes();
  $Facts = Get_Factions();
  $FactNames = Get_Faction_Names(0);


  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
      case 'Add':
        $WList = [];
        foreach($Worlds as $hi=>$H) $WList[$hi] = WorldName($H);


        $S = [];
        echo "<form method=post><table>";
        echo "<tr><td>Who:<td>" . fm_select($FactNames,$S,'Whose',0);
        echo "<tr>" . fm_text('Principle',$S,'Principle',2);
        echo "<tr><td>Where:<td>" . fm_select($WList,$S,'World');
        echo "<tr>" . fm_number('Adherence', $S,'Value','',"min=0 max=5");
        echo "</table>";
        echo "<input type=submit name=ACTION value=Create>";
        dotail();

      case 'Create':
        $SP = [];
//        var_dump($_REQUEST);
        $_POST['GameId'] = $GAMEID;
        insert_db_post('SocialPrinciples',$SP);
        break;

    }
  }

  $SocPs = Gen_Get_Cond('SocialPrinciples', "GameId=$GAMEID " .($GM?'':"AND Whose=$Fid") . " ORDER BY Principle");

//  Register_AutoUpdate('SocialPrinciples',0);

  $coln = 0;
  echo "<div class=tablecont><table id=indextable border width=100% style='min-width:1400px'>\n";
  echo "<thead><tr>";
  if ($GM) echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Id</a>\n";
  if ($GM) echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Whose</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Principle</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Description</a>\n";
  echo "</thead><tbody>";

  foreach ($SocPs as $si=>$SP) {
    echo "<tr>";

    if ($GM) echo "<td>$si<td>" . $Facts[$SP['Whose']]['Name'];
    echo "<td><a href=SocialEdit.php?id=$si>" . $SP['Principle'] . "</a><td>" . $Parsedown->text(stripslashes($SP['Description']));
  }
  echo "</table></div>\n";

  if ($GM) echo "<h2><a href=ListSocial.php?ACTION=Add>Add Social Principle</a></h2>";


  dotail();


