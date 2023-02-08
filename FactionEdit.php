<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("SystemLib.php");
  include_once("PlayerLib.php");
    
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
  global $GAME,$GAMEID,$PlayerState;;
  if (!isset($F['id'])) {
    echo "<h1 class=Error>No Faction to Display</h1>";
    dotail();
  }
  $Fid = $F['id'];
  $PTs = Get_PlanetTypeNames();
  $GM = (isset($_REQUEST['FORCE'])?0:Access('GM'));
  $Setup = 0;
  
  $ReadOnly = (($F['TurnState'] != 0) && !$GM)? " readonly " : "";// Readonly when out of setup
  
  if (Access('God')) echo "You can only delete a faction with -99 credits<br>";
  
  echo "<form method=post id=mainform enctype='multipart/form-data' action=FactionEdit.php>";
  if ($GM) echo "<h2>GM: <a href=FactionEdit.php?id=$Fid&FORCE>This page in Player Mode</a></h2>";  
  echo "<div class=tablecont><table width=90% border class=SideTable>\n";
  Register_AutoUpdate('Faction',$Fid);
  echo fm_hidden('id',$Fid);
  if ($GM) echo "<tr><td>Id:<td>$Fid<td>Game<td>$GAMEID<td>" . $GAME['Name'];
  
  echo "<tr>" . fm_text('Faction Name',$F,'Name',2);
  echo "<td rowspan=4 colspan=4><table><tr>";
    echo fm_DragonDrop(1,'Image','Faction',$Fid,$F,1,'',1,'','Faction');
  echo "</table>";

  echo "<tr><td>Native BioSphere<td colspan=2>" . (($GM || $Setup)?fm_select($PTs,$F,'Biosphere',1): $PTs[$F['Biosphere']]);
  if ($GM) {
    echo ", " . fm_select($PTs,$F,'Biosphere2',1) . ", " . fm_select($PTs,$F,'Biosphere3',1);
  } else if ($F['Biosphere2']) {
    echo ", " .  $PTs[$F['Biosphere2']];
  } else if ($F['Biosphere3']) {
    echo ", " .  $PTs[$F['Biosphere3']];    
  } 
  echo "<tr>" . fm_text('Player Name',$F,'Player',2);

  echo "<tr><td>Player State:<td>" . (($GM)? fm_select($PlayerState,$F,'TurnState'): $PlayerState[$F['TurnState']]);

  if ($GM) {
    echo "<td>" . fm_checkbox("NPC",$F,'NPC');
    echo "<tr>" . fm_number('Credits',$F,'Credits') . fm_number('Physics Points', $F,'PhysicsSP');
    echo "<tr>" . fm_number('Engineering Points', $F,'EngineeringSP') . fm_number('Xenology Points', $F,'XenologySP');
    if ($Nam = GameFeature('Currency1')) echo "<tr>" . fm_number($Nam, $F,'Currency1');
    if ($Nam = GameFeature('Currency2')) echo "<tr>" . fm_number($Nam, $F,'Currency2');
    if ($Nam = GameFeature('Currency3')) echo "<tr>" . fm_number($Nam, $F,'Currency3');
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
  echo "<tr>" . fm_text("Trait 1 Name",$F,'Trait1',1,'',$ReadOnly). "<td>Short name that is unique";
  echo "<td>" . ($GM ? fm_checkbox("Automated ",$F,'Trait1Automated') : ($F['Trait1Automated']? "Automated" : "Not Automated"));
  echo "<tr>" . fm_textarea('Description',$F,'Trait1Text',8,2);
  echo "<tr>" . fm_text("Trait 2 Name",$F,'Trait2',1,'',$ReadOnly). "<td>Short name that is unique";
  echo "<td>" . ($GM ? fm_checkbox("Automated ",$F,'Trait2Automated') : ($F['Trait2Automated']? "Automated" : "Not Automated"));
  echo "<tr>" . fm_textarea('Description',$F,'Trait2Text',8,2);
  echo "<tr>" . fm_text("Trait 3 Name",$F,'Trait3',1,'',$ReadOnly). "<td>Short name that is unique";
  echo "<td>" . ($GM ? fm_checkbox("Automated ",$F,'Trait3Automated') : ($F['Trait3Automated']? "Automated" : "Not Automated"));
  echo "<tr>" . fm_textarea('Description',$F,'Trait3Text',8,2);
  
  echo "<tr>" . fm_textarea('Notes',$F,'Notes',8,2);

  if ($GM) {
    echo "<tr>" . fm_textarea('Features',$F,'Features',8,2);
    echo "<tr>" . fm_textarea('GM_Notes',$F,'GM_Notes',8,2);  
    echo "<tr>" . fm_text('Map Colour',$F,'MapColour') . fm_text('Map Text Colour',$F,'MapText') . fm_number('HomeWorld',$F,'HomeWorld');
    echo fm_number("Prisoner Count",$F,'HasPrisoners');
    echo "<tr>" . fm_number('Thing List Type', $F,'ThingType') . fm_number('List Build State', $F,'ThingBuild');
    echo "<tr>" . fm_number('GM:Thing List Type', $F,'GMThingType') . fm_number('GM: List Build State', $F,'GMThingBuild');
    echo "<tr>" . fm_date('Last Active',$F,'LastActive');
    echo fm_text('Access Key',$F,'AccessKey',3);
    echo "<a href=Access.php?id=$Fid&Key=" . $F['AccessKey'] . " ><b>Use</b></a>";
    if (Access('God')) echo "</tbody><tfoot><tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea>";
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
