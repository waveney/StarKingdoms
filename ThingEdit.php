<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");

function New_Thing(&$t) {
  global $BuildState;
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
  echo "<tr><td>BuildState:<td>" . fm_select($BuildState,$t,'BuildState');
  echo "<tr><td><td><input type=submit name=ACTION value=Create>\n";
  echo "</table></form>";
  dotail();
}

  $Force = (isset($_REQUEST['FORCE'])?1:0);
  
  if (Access('GM')) {
    A_Check('GM');
    
  } else {
    
  }


  dostaffhead("Edit and Create Things",["js/dropzone.js","css/dropzone.css" ]);

  global $db, $GAME, $GAMEID,$BuildState;
  
// START HERE
//  var_dump($_REQUEST);
  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
     case 'NEW' :
       $t = ['Level'=>1, 'BuildState'=>3, 'LinkId'=>0];
       New_Thing($t);
       break;
       
     case 'Create' :
       if (!isset($_POST['SystemId'])) {
         echo "No System Given";
         New_Thing($_POST);
       }
       $_POST['GameId'] = $GAMEID;
       $_POST['NewSystemId'] = $_POST['SystemId'];
       $tid = Insert_db_post('Things',$t);
       $t['id'] = $tid;
       break;
     
     case 'DELETE' :
       $tid = $_REQUEST['id'];
       $Discs = Get_DistrictsT($tid);
       if ($Discs) {
         foreach ($Discs as $D) {
           db_delete('Districts',$D['id']);
         }
       }
       $Mods = Get_DistrictsT($tid);
       if ($Mods) {
         foreach ($Mods as $M) {
           db_delete('Modules',$M['id']);
         }
       }

       db_delete('Things',$tid);

       echo "<h1>Deleted</h1>";
       echo "<h2><a href=ThingList.php>Back to Thing list</a></h2>";
       dotail();
       
     case 'Duplicate' :
       $t = Thing_Duplicate($_REQUEST['id']);
       $tid = $t['id'];
       break;
       
     case 'Refit' :
       $tid = $_REQUEST['id'];
       $t = Get_Thing($tid);
       Calc_Scanners($t);
       RefitRepair($t);
       break;     
           
    case 'None' :
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

  echo "<br>";

  if ($Force) {
    $GM = 0;
    $Fid = $t['Whose'];
  } else {
    $GM = Access('GM');
  }
    
  Show_Thing($t,$Force);
  if (($GM && !empty($tid)) || ($t['BuildState'] == 0)) echo "<br><p><br><p><h2><a href=ThingEdit.php?ACTION=DELETE&id=$tid>Delete Thing</a></h2>";
  
  
  dotail();
  
?>
  
  
  
  
  
  
  
  
  
  
  
