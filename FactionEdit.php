<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("SystemLib.php");
  include_once("PlayerLib.php");

  global $FACTION,$GAMEID;

  A_Check('Player');
  if (Access('Player')) {
    if (!$FACTION) {
      if (!Access('GM') ) Error_Page("Sorry you need to be a GM or a Player to access this");
    } else {
      $Fid = $FACTION['id'];
      $F = &$FACTION;
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
    if (isset($Fid)) $F = Get_Faction($Fid);
  }
  $GM = 0; //Access('GM');

  dostaffhead("Edit Faction",["js/dropzone.js","css/dropzone.css" ]);

  global $db, $GAME;

function Show_Faction(&$F,$Mode) {
  global $GAME,$GAMEID,$PlayerState,$FoodTypes;
  if (!isset($F['id'])) {
    echo "<h1 class=Error>No Faction to Display</h1>";
    dotail();
  }
  $Fid = $F['id'];
  $PTs = Get_PlanetTypeNames();
  $PTs[0] = 'None';
  $GM = (isset($_REQUEST['FORCE'])?0:Access('GM'));
  $Setup = ($F['TurnState'] == 0);

  $ReadOnly = (!$Setup && !$GM)? " readonly " : "";// Readonly when out of setup

  if (Access('God')) echo "You can only delete a faction with -99 credits<br>";

  echo "<form method=post id=mainform enctype='multipart/form-data' action=FactionEdit.php>";
  if ($GM) echo "<h2>GM: <a href=FactionEdit.php?id=$Fid&FORCE>This page in Player Mode</a></h2>";
  echo "<div class=tablecont><table width=90% border class=SideTable>\n";
  Register_AutoUpdate('Faction',$Fid);
  echo fm_hidden('id',$Fid);
  if ($GM) echo "<tr><td class=NotSide>Id:<td class=NotSide>$Fid" .
                "<td class=NotSide>Game<td class=NotSide>$GAMEID<td class=NotSide>" . $GAME['Name'];
  $cls = (($PlayerState[$F['TurnState']] == 'Setup')?'':' class=NotCSide');

  echo "<tr>" . fm_text('Faction Name',$F,'Name',2);
  echo "<td rowspan=3 colspan=4><table><tr>";
    echo fm_DragonDrop(1,'Image','Faction',$Fid,$F,1,'',1,'','Faction');
  echo "</table>";

  echo "<tr><td $cls>Native BioSphere<td colspan=2 $cls>" . (($GM || $Setup)?fm_select($PTs,$F,'Biosphere',1): $PTs[$F['Biosphere']]);
  if ($GM) {
    echo ", " . fm_select($PTs,$F,'Biosphere2',1) . ", " . fm_select($PTs,$F,'Biosphere3',1);
  } else if (feature('MultiBiosphere')) {
    if ($F['Biosphere2']) {
      echo ", " .  $PTs[$F['Biosphere2']];
    } else if ($F['Biosphere3']) {
      echo ", " .  $PTs[$F['Biosphere3']];
    }
  }
  echo "<tr>" . ($GM? fm_radio('Diet?',$FoodTypes,$F,'FoodType','',1,'colspan=2') : "<td>Diet is " . $FoodTypes[$F['FoodType']]);
  echo "<tr>" . fm_text('Player Name',$F,'Player',2,$cls) . fm_text1('Scale Factor',$F,'ScaleFactor') .
    "<td colspan=4>If your maps are confused, change this a LITTLE from 1.0";

  echo "<tr><td $cls>Player State:<td $cls>" .
    (($GM)? fm_select($PlayerState,$F,'TurnState'): $PlayerState[$F['TurnState']]);
  if ($GM || $F['Player2']!='') echo fm_Text('2nd Player',$F,'Player2');

  if ($GM) {
    echo "<td class=NotSide" . fm_checkbox("NPC",$F,'NPC') . "<td class=NotSide" . fm_checkbox('No Anomalies',$F,'NoAnomalies');
    echo "<tr>" . fm_number('Credits',$F,'Credits',' class=NotCSide') . fm_number('Physics Points', $F,'PhysicsSP',' class=NotCSide');
    echo "<tr>" . fm_number('Engineering Points', $F,'EngineeringSP',' class=NotCSide') .
      fm_number('Xenology Points', $F,'XenologySP',' class=NotCSide');
    if ($Nam = GameFeature('Currency1')) echo "<tr>" . fm_number($Nam, $F,'Currency1',' class=NotCSide');
    if ($Nam = GameFeature('Currency2')) echo "<tr>" . fm_number($Nam, $F,'Currency2',' class=NotCSide');
    if ($Nam = GameFeature('Currency3')) echo "<tr>" . fm_number($Nam, $F,'Currency3',' class=NotCSide');
  } else {
    echo "<tr><td>Credits:<td>" . $F['Credits'];
    echo "<tr><td>Physics Points<td>" . $F['PhysicsSP'];
    echo "<tr><td>Engineering Points<td>" . $F['EngineeringSP'];
    echo "<tr><td>Xenology Points<td>" . $F['XenologySP'];
    if ($Nam = GameFeature('Currency1')) echo "<tr><td>$Nam:<td>" . $F['Currency1'];
    if ($Nam = GameFeature('Currency2')) echo "<tr><td>$Nam:<td>" . $F['Currency2'];
    if ($Nam = GameFeature('Currency3')) echo "<tr><td>$Nam:<td>" . $F['Currency3'];
  }


  echo "<tr>" . fm_text('Adjective Name',$F,'Adjective',2) . "<td>To refer to your ships etc rather than your faction name - optional";
  echo "<tr>" . fm_text("Trait 1 Name",$F,'Trait1',2,$cls,$ReadOnly). "<td>Short name that is unique";
  echo "<td $cls>" . ($GM ? fm_checkbox("Automated ",$F,'Trait1Auto') . fm_number('Concealment',$F,'Trait1Conceal') : ($F['Trait1Auto']? "Automated" : "Not Automated"));
  echo "<tr>" . fm_textarea('Description',$F,'Trait1Text',8,2,$cls);
  echo "<tr>" . fm_text("Trait 2 Name",$F,'Trait2',2, $cls,$ReadOnly). "<td>Short name that is unique";
  echo "<td $cls>" . ($GM ? fm_checkbox("Automated ",$F,'Trait2Auto') . fm_number('Concealment',$F,'Trait2Conceal') : ($F['Trait2Auto']? "Automated" : "Not Automated"));
  echo "<tr>" . fm_textarea('Description',$F,'Trait2Text',8,2,$cls);
  if ($ReadOnly && $F['Trait3']=='') {

  } else {
    echo "<tr>" . fm_text("Trait 3 Name",$F,'Trait3',2, $cls,$ReadOnly). "<td>Short name that is unique";
    echo "<td $cls>" . ($GM ? fm_checkbox("Automated ",$F,'Trait3Auto') . fm_number('Concealment',$F,'Trait3Conceal') : ($F['Trait3Auto']? "Automated" : "Not Automated"));
    echo "<tr>" . fm_textarea('Description',$F,'Trait3Text',8,2,$cls);
  }

  echo "<tr>" . fm_textarea('Notes',$F,'Notes',8,2);
  echo "<tr>" . fm_textarea('Features',$F,'Features',8,2,'');

  if ($GM) {
    echo "<tr>" . fm_textarea('GM_Notes',$F,'GM_Notes',8,2,' class=NotSide');
    echo "<tr>" . fm_text('Map Colour',$F,'MapColour',1,' class=NotSide') . fm_text('Map Text Colour',$F,'MapText',1,' class=NotSide')
    . fm_number('HomeWorld',$F,'HomeWorld',' class=NotSide');
    echo fm_number("Prisoner Count",$F,'HasPrisoners',' class=NotSide');
    if (Access('God')) {
      echo "<tr>" . fm_number('Thing List Type', $F,'ThingType') . fm_number('List Build State', $F,'ThingBuild');
      echo "<tr>" . fm_number('GM:Thing List Type', $F,'GMThingType') . fm_number('GM: List Build State', $F,'GMThingBuild');
      echo "<tr>" . fm_date('Last Active',$F,'LastActive');
      echo fm_text('Access Key',$F,'AccessKey',3);
      echo "<a href=Access.php?id=$Fid&Key=" . $F['AccessKey'] . " ><b>Use</b></a>";
      echo "</tbody><tfoot><tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea>";
    }
  } else {
    echo "<tr><td>Home World<td colspan=6>";
    if (empty($F['HomeWorld']) || $F['HomeWorld']==0) {
      echo "Not set up!";
    } else {
      $W = Get_World($F['HomeWorld']);
//var_dump($W);
      switch ($W['ThingType']) {
      case 1: // Planet
        $P = Get_Planet($W['ThingId']);
        $N = Get_System($P['SystemId']);
        $Name = $P['Name'] . " in " . $N['Name'] . " (" . $N['Ref'] . ")";
        break;
      case 2: // MOon
        $M = Get_Moon($W['ThingId']);
        $P = Get_Planet($M['PlanetId']);
        $N = Get_System($P['SystemId']);
        $Name = $M['Name'] . " a moon of " . $P['Name'] . " in " . $N['Name'] . " (" . $N['Ref'] . ")";
        break;
      case 3: // Thing
        $T = Get_Thing($W['ThingId']);
        $TTypes = Get_ThingTypes();
        if ($T['SystemId'] > 0) {
          $N = Get_System($T['SystemId']);
          $Name = $T['Name'] . " a " . $TTypes[$T['Type']]['Name'] . " in " . $N['Name'] . " (" . $N['Ref'] . ")";
        } else {
          $Name = $T['Name'] . " a " . $TTypes[$T['Type']]['Name'];
        }
      }
      echo $Name;
    }
  }
  echo "</table></div>\n";

  if (Access('God')) {
    echo "<input type=submit name=nothing value=nothing hidden>";
    echo "<center><form method=post action=FactionEdit.php>" . fm_hidden('id', $Fid) .
         "<input type=submit name=ACTION value='New Key' class=Button onclick=\"return confirm('Are you sure?')\" >";
    if ($F['Credits'] == -99) echo "<input type=submit name=ACTION value='Delete' class=Button onclick=\"return confirm('Are you sure?')\" >";
    echo "</form></center>";
  }

//  echo "<h2><a href=MapFull.php?F=$Fid>Faction Map</a></h2>";
}

// START HERE
//  var_dump($_REQUEST);
//  $F = Get_Faction($Fid);

  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
    case 'New Key' :
      $F['AccessKey'] = rand_string(40);
      Put_Faction($F);
      break;

    case 'NEW':
      $F = [];
      echo "<form method=post action=FactionEdit.php?ACTION=Create>";
      echo "Name: <input type=text name=Name  onchange=this.form.submit()>";
      echo "</form>";
      dotail();

    case 'Create':
      $F = ['Name'=> $_REQUEST['Name'], 'GameId'=>$GAMEID, 'AccessKey'=> rand_string(40)] ;
      $Fid = Put_Faction($F);
      $F = Get_Faction($Fid);
      break;

    case 'Delete':
      db_delete('Factions',$Fid);
      echo "<h2>Faction deleted</h2>\n";
      dotail();
      break;

    default:
      break;
    }
  }


  Show_Faction($F,1);



  dotail();
?>
