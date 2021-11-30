<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("SystemLib.php");
  
  A_Check('GM'); // For now, will be player version

  dostaffhead("Survey Report",["js/dropzone.js","css/dropzone.css" ]);

  global $db, $GAME;

// START HERE
//  var_dump($_REQUEST);
  if (isset($_REQUEST['N'])) {
    $Sid = $_REQUEST['N'];
  } else if (isset($_REQUEST['id'])) {
    $Sid = $_REQUEST['id'];
  } else { 

    echo "<h2>No Systems Requested</h2>";
    dotail();
  }


  
  $N=Get_System($Sid);
  
  $pname = NameFind($N); // Need diff logic for player
  if (!$pname) $pname = $N['Ref'];
  
  echo "<div class=SReport><h1>Survey Report - $pname</h1>\n";
  echo "UniqueRef is: " . UniqueRef($Sid) . "<p>";
  
  if ($N['Description'] echo Format($N['Description']) . "<p>";
  
  echo "</div>";
  dotail();
  

    
  
/* Name, Control, Star(s), Planet(s), Jump Link(s), Anomalies, ships present, Planets: Districts, armies - other names */
/* Player based - Not in control
   Name, Control, Star(s), Planet(s), Jump Link(s), Anomalies?, ships */
/* In control + Planets contents - other names*/

/* Name rules if GM
    If ShortName Use
    if Name Use
    if Control Faction then
      use Factions short name|name if avail  
    else use Refcode
    
  If Not control by faction then
  
  If control by otheer faction then
          use Factions short name|name if avail  
          use sysname if avail
          use randow ref - unique to faction systemm SK#GsFyFsF

  echo "<div class=SReport><h1>Survey Report - " . ((isset($N['ShortName']) && $N['ShortName'])?$N['ShortName']:isset($N['Name']) && $N['Name'])?$N['Name':(Access('GM')?$N['Ref']:
  
*/

?>
