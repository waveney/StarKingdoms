<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");

  A_Check('GM');

  dostaffhead("Edit Technoiology");

  global $db, $GAME, $ModuleCats;
  global $ModuleCats,$ModFormulaes,$ModValues,$Fields,$ShipTypes,$Tech_Cats,$CivMil;

//  var_dump($_REQUEST);
  if (isset($_REQUEST['P'])) {
    $Tid = $_REQUEST['P'];
  } else if (isset($_REQUEST['id'])) {
    $Tid = $_REQUEST['id'];
  } else { 

    echo "<h2>No Tech Requested</h2>";
    dotail();
  }



  $T = Get_Tech($Tid);
  $CTs = Get_CoreTechs();
  $CTNs = [];
  $CTNs[0] = '';
  foreach ($CTs as $TT) $CTNs[$TT['id']] = $TT['Name'];

  $MFN = ModFormulaes();
  if (isset($_REQUEST['SHOW'])) {
    Show_Tech($T,$CTNs);
    echo " <p>";
  }
//var_dump($T);

  echo "<form method=post id=mainform enctype='multipart/form-data' action=TechEdit.php>";
  echo "<div class=tablecont><table width=90% border class=SideTable>\n";
  Register_AutoUpdate('Tech',$Tid);
  echo fm_hidden('id',$Tid);

  echo "<tr><td>Id:$Tid<td>" .  fm_select($Tech_Cats,$T,'Cat') . fm_text("Name",$T,'Name',2);
  echo "<tr><td>Feild:<td>" . fm_select($Fields,$T,'Field') . "<td>Pre Req Tech:" . fm_select($CTNs,$T,'PreReqTech') . fm_number1('Module Slots',$T,'PreReqLevel');
  echo "<tr>" . fm_number("Min Thing Level",$T,'MinThingLevel') . "<td>Civ /Mil:<td>" . fm_select($CivMil,$T,'CivMil');
  echo "<td>" . fm_select($MFN,$T,'Formula',1);
  echo "<tr>" . fm_textarea('Description',$T,'Description',8,20);
  if (Access('God')) echo "<tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea>";
  echo "</tbody></table></div>\n";

  echo "<h2><input type=submit name=Update value=Update> <input type=submit name=SHOW value=SHOW></h2>";
  echo "</form></div>";
  dotail();
?>

