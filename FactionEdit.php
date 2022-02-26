<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("SystemLib.php");
  include_once("PlayerLib.php");
    
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
  $GM = Access('GM');
  $Setup = 0;
  
  $ReadOnly = (($F['TurnState'] != 0) && !$GM)? " readonly " : "";// Readonly when out of setup
  
  echo "<form method=post id=mainform enctype='multipart/form-data' action=FactionEdit.php>";
  echo "<div class=tablecont><table width=90% border class=SideTable>\n";
  Register_AutoUpdate('Faction',$Fid);
  echo fm_hidden('id',$Fid);
  if ($GM) echo "<tr><td>Id:<td>$Fid<td>Game<td>$GAMEID<td>" . $GAME['Name'];
  
  echo "<tr>" . fm_text('Faction Name',$F,'Name',2);
  echo "<td rowspan=4 colspan=4><table><tr>";
    echo fm_DragonDrop(1,'Image','Faction',$Fid,$F,1,'',1,'','Faction');
  echo "</table>";

  echo "<tr><td>Native BioSphere<td>" . (($GM || $Setup)?fm_select($PTs,$F,'Biosphere',1): $PTs[$F['Biosphere']]);
  echo "<tr>" . fm_text('Player Name',$F,'Player',2);
  echo "<tr><td>Player State:<td>" . (($GM)? fm_select($PlayerState,$F,'TurnState'): $PlayerState[$F['TurnState']]);
  if ($GM) {
    echo "<td>" . fm_checkbox("NPC",$F,'NPC');
    echo "<tr>" . fm_number('Credits',$F,'Credits') . fm_number('Physics Points', $F,'PhysicsSP');
    echo "<tr>" . fm_number('Engineering Points', $F,'EngineeringSP') . fm_number('Xenology Points', $F,'XenologySP');
  } else {
    echo "<tr><td>Credits:<td>" . $F['Credits'];
    echo "<tr><td>Physics Points<td>" . $F['PhysicsSP'];
    echo "<tr><td>Engineering Points<td>" . $F['EngineeringSP'];
    echo "<tr><td>Xenology Points<td>" . $F['XenologySP'];
  }  
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
    echo "<tr>" . fm_text('Map Colour',$F,'MapColour');
    echo "<tr>" . fm_date('Last Active',$F,'LastActive');
    echo fm_text('Access Key',$F,'AccessKey',3);
    echo "<a href=Access.php?id=$Fid&Key=" . $F['AccessKey'] . " ><b>Use</b></a>";
    if (Access('God')) echo "<tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea>";
  }
  echo "</table></div>\n";
  
  if (Access('God')) {
    echo "<center><form method=post action=FactionEdit.php>" . fm_hidden('id', $Fid) .
         "<input type=submit name=ACTION value='New Key' class=Button onclick=\"return confirm('Are you sure?')\" >";
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
           
    default: 
      break;
    }
  }
  
  
  Show_Faction($F,1);
  
  
  
  dotail();
?>
