<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");

function New_Thing(&$t) {
  $ttn = Thing_Type_Names();
  $FactNames = Get_Faction_Names();
  $Fact_Colours = Get_Faction_Colours();
  $Systems = Get_SystemRefs();
  if (!isset($t['Whose'])) $t['Whose'] = 0;

  echo "<h1>Create Thing:</h1>";
  echo "<form method=post action=ThingEdit.php>";
  echo "<table><tr><td>Type:<td>" . fm_select($ttn,$t,'Type');
  echo "<tr>" . fm_text("Name",$t,'Name');
  echo "<tr>" . fm_number("Level",$t,'Level');
  echo "<tr>" . fm_radio('Whose',$FactNames ,$t,'Whose','',1,'colspan=6','',$Fact_Colours,0); 
  echo "<tr><td>System:<td>" . fm_select($Systems,$t,'SystemId');
  echo "<tr><td><td><input type=submit name=ACTION value=Create>\n";
  echo "</table></form>";
  dotail();
}

  A_Check('GM');

  dostaffhead("Edit and Create Things",["js/dropzone.js","css/dropzone.css" ]);

  global $db, $GAME, $GAMEID,$BuildState;
  
// START HERE
//  var_dump($_REQUEST);
  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
     case 'NEW' :
       $t = ['Level'=>1];
       New_Thing($t);
       break;
       
     case 'Create' :
       if (!isset($_POST['SystemId'])) {
         echo "No System Given";
         New_Thing($_POST);
       }
       $_POST['GameId'] = $GAMEID;
       $_POST['NewSystemId'] = $_POST['SystemId']
       $tid = Insert_db_post('Things',$t);
       $t['id'] = $tid;
       break;
     
     case 'DELETE' :
       $tid = $_REQUEST['id'];
       db_delete('Things',$tid);
       echo "<h1>Deleted</h1>";
       echo "<h2><a href=ThingList.php>Back to Thing list</a></h2>";
       dotail();
       
     case 'Duplicate' :
       $otid = $tid = $_REQUEST['id'];
       $t = Get_Thing($tid);
       unset($t['id']);
       $t['id'] = $tid = Insert_db('Things',$t);
       $Discs = Get_DistrictsT($otid);
       if ($Discs) {
         foreach ($Discs as $D) {
           $D['HostId'] = $tid;
           unset($D['id']);
           Insert_db('Districts',$D);
         }
       }
       $Mods = Get_Modules($otid);
       if ($Mods) {
         foreach ($Mods as $M) {
           $M['ThingId'] = $tid;
           unset($M['id']);
           Insert_db('Modules',$M);
         }
       }
           
    default: 
      break;
    }
  }

  if (isset($t)) {
  } else if (isset($_REQUEST['id'])) {
    $tid = $_REQUEST['id'];
    $t = Get_Thing($tid);
  } else {
    echo "<h2>No Thing Requested</h2>";
    dotail();
  }

  
  Show_Thing($t);
  echo "<br><p><br><p><h2><a href=ThingEdit.php?ACTION=DELETE&id=$tid>Delete Thing</a></h2>";
  
  
  dotail();
  
?>
  
  
  
  
  
  
  
  
  
  
  
