<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");

  A_Check('GM');

  dostaffhead("Edit Technoiology");

  global $db, $GAME, $ModuleCats;
  global $ModuleCats,$ModFormulaes,$ModValues,$Fields,$Tech_Cats,$CivMil;

//  var_dump($_REQUEST);
  if (isset($_REQUEST['P'])) {
    $Tid = $_REQUEST['P'];
  } else if (isset($_REQUEST['id'])) {
    $Tid = $_REQUEST['id'];
  } else { 

    echo "<h2>No Tech Requested</h2>";
    dotail();
  }



  if ($Tid) {
    $T = Get_Tech($Tid);
  } else {
    $T = [];
  }
  $CTs = Get_CoreTechs();
  $CTNs = [];
  $CTNs[0] = '';
  foreach ($CTs as $TT) $CTNs[$TT['id']] = $TT['Name'];
  
  $All_Techs = Get_Techs(0);
  $TechNames = [];
  foreach($All_Techs as $TI => $TT) $TechNames[$TI] = $TT['Name'];

  $MFN = ModFormulaes();
  if (isset($_REQUEST['SHOW'])) {
    Show_Tech($T,$CTNs);
    echo " <p>";
  }
  
  
  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
      case 'Update': // No action should be needed
        break;
      case 'Create':
        $Tid = Insert_db_post('Technologies', $T);
        break;
      case 'Delete':
        db_delete('Technologies',$Tid);
        echo "Deleted " . $T['Name'] . "<p>";
        dotail();
    }
  }
  
  
//var_dump($T);

  echo "Properties: 1=Ground Combat, 2=Space Combat, 4=Espionage, 8=Hide<p>\n";
  echo "<form method=post id=mainform enctype='multipart/form-data' action=TechEdit.php>";
  echo "<div class=tablecont><table width=90% border class=SideTable>\n";
  if ($Tid) Register_AutoUpdate('Tech',$Tid);
  echo fm_hidden('id',$Tid);

  echo "<tr><td>Id:$Tid<td>" .  fm_select($Tech_Cats,$T,'Cat') . fm_text("Name",$T,'Name',2);
  echo "<tr><td>Feild:<td>" . fm_select($Fields,$T,'Field') . "<td>Pre Req Tech:" . fm_select($CTNs,$T,'PreReqTech') . fm_number1('Pre Req Level',$T,'PreReqLevel') .
       "<td>Core Techs only - must have one of these";
  echo "<tr><td>Other Pre Reqs<td>" . fm_select($TechNames,$T,'PreReqTech2',1) . "<td>" . fm_select($TechNames,$T,'PreReqTech3',1) ;
  echo "<tr>" . fm_number("Min Thing Level",$T,'MinThingLevel') . "<td>Civ /Mil:<td>" . fm_select($CivMil,$T,'CivMil');
  echo "<td>" . fm_select($MFN,$T,'Formula',1) . fm_number('Properties',$T,'Properties');
  echo "<tr>" . fm_textarea('Description',$T,'Description',8,20);
  if (Access('God')) echo "</tbody><tfoot><tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea>";
  echo "</tbody></table></div>\n";

  if ($Tid) {
    echo "<h2><input type=submit name=ACTION value=Update> <input type=submit name=SHOW value=SHOW></h2>";
    if (Access('God')) echo "<input type=submit name=ACTION value=Delete>";
  } else {
    echo "<h2><input type=submit name=ACTION value=Create></h2>";  
  }

  echo "</form></div>";
  echo "<H2><a href=TechList.php>Back to Tech List</a></h2>";
  dotail();
?>

