<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("SystemLib.php");
  
  A_Check('GM');

  dostaffhead("Edit System",["js/dropzone.js","css/dropzone.css" ]);

  global $db, $GAME;

function Show_Faction(&$F,$Mode) {
  global $GAME,$GAMEID;
  if (!isset($F['id'])) {
    echo "<h1 class=Error>No Faction to Display</h1>";
    dotail();
  }
  $Fid = $F['id'];
  $PTs = Get_PlanetTypeNames();
  
  echo "<form method=post id=mainform enctype='multipart/form-data' action=FactionEdit.php>";
  echo "<div class=tablecont><table width=90% border class=SideTable>\n";
  Register_AutoUpdate('Faction',$Fid);
  echo fm_hidden('id',$Fid);
  echo "<tr><td>Id:<td>$Fid<td>Game<td>$GAMEID<td>" . $GAME['Name'];
  
  echo "<tr>" . fm_text('Faction Name',$F,'Name') . "<td>Native BioSphere<td>" . fm_select($PTs,$F,'Biosphere',1);
  echo "<tr>" . fm_text('Player Name',$F,'Player');
  echo "<tr>" . fm_number('Credits',$F,'Credits') . fm_number('Science Points', $F,'SciencePoints');
  echo "<tr>" . fm_text("Trait 1 Name",$F,'Trait1'). "<td>Short name that is unique";
  echo "<tr>" . fm_textarea('Description',$F,'Trait1Text',8,2);
  echo "<tr>" . fm_text("Trait 2 Name",$F,'Trait2'). "<td>Short name that is unique";
  echo "<tr>" . fm_textarea('Description',$F,'Trait2Text',8,2);
  echo "<tr>" . fm_text("Trait 3 Name",$F,'Trait3'). "<td>Short name that is unique";
  echo "<tr>" . fm_textarea('Description',$F,'Trait3Text',8,2);
  
  
  
  
  
  echo "<tr>" . fm_textarea('Notes',$F,'Notes',8,2);
//  echo "<tr>" . fm_textarea('Features',$F,'Features',8,2);
  echo "<tr>" . fm_textarea('GM_Notes',$F,'GM_Notes',8,2);  
  echo "<tr>" . fm_text('Map Colour',$F,'MapColour');
  echo "<tr>" . fm_date('Last Active',$F,'LastActive');
  echo fm_text('Access Key',$F,'AccessKey',3);
  echo "<td><a href=Access.php?id=$Fid&Key=" . $F['AccessKey'] . ">Use</a>";
  if (Access('God')) echo "<tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea>";
  echo "</table></div>\n";
  
  if (Access('God')) {
    echo "<center><form method=post action=FactionEdit.php>" . fm_hidden('id', $Fid) .
         "<input type=submit name=ACTION value='New Key' class=Button> ";
    echo "</form></center>";
  }
  
  echo "<h2><a href=MapFull.php?F=$Fid>Faction Map</a></h2>";
}

// START HERE
//  var_dump($_REQUEST);
  if (isset($_REQUEST['F'])) {
    $Fid = $_REQUEST['F'];
  } else if (isset($_REQUEST['id'])) {
    $Fid = $_REQUEST['id'];
  } else { 

    echo "<h2>No Systems Requested</h2>";
    dotail();
  }

  $F = Get_Faction($Fid);
  
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
