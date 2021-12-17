<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");

function New_Thing(&$t) {
  $ttn = Thing_Type_Names();
  $FactNames = Get_Faction_Names();
  $Fact_Colours = Get_Faction_Colours();
  $Systems = Get_SystemRefs();

  echo "<h1>Create Thing:</h1>";
  echo "<form method=post action=ThingEdit.php>";
  echo "<table><tr><td>Type:<td>" . fm_select($ttn,$t,'Type');
  echo "<tr>" . fm_text("Name",$t,'Name');
  echo "<tr>" . fm_radio('Whose',$FactNames ,$t,'Whose','',1,'colspan=6','',$Fact_Colours,0); 
  echo "<tr><td>System:<td>" . fm_select($Systems,$t,'SystemId');
  echo "<tr><td><td><input type=submit name=ACTION value=Create>\n";
  echo "</table></form>";
  dotail();
}

  A_Check('GM');

  dostaffhead("Edit and Create Things");

  global $db, $GAME, $GAMEID,$BuildState;
  
// START HERE
//  var_dump($_REQUEST);
  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
     case 'NEW' :
       $t = [];
       New_Thing($t);
     
     case 'Create' :
       if (!isset($_POST['Name'])) {
         echo "No Name Given";
         New_Thing($_POST);
       }
       if (!isset($_POST['SystemId'])) {
         echo "No System Given";
         New_Thing($_POST);
       }
       $_POST['GameId'] = $GAMEID;
       $tid = Insert_db_post('Things',$t);
       
     break;
     
            
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
  
  
  
  dotail();
  
?>
  
  
  
  
  
  
  
  
  
  
  
