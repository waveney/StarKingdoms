<?php
  include_once("sk.php");
  A_Check('God');
  global $GAME,$GAMEID;

  dostaffhead("General Game Settings");

  include_once("DateTime.php");

  echo "<div class='content'><h2>General Game Settings</h2>\n";
  
  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
    case 'Update' :
      Parse_DateInputs($Dates);
      $Gen = $Gens[$_POST['Year']];
      Update_db_post('General',$Gen);
      $ynum = $Gen['Year'];
      break;
          
    case 'Create' :
      Parse_DateInputs($Dates);
      Insert_db_post('General',$Gen);
      $ynum = $Gen['Year'];
      break;    
    
    case 'Setup' :
      $Gen = [];
      $ynum = 0;
      break;
    }
  }
  
  
  echo "<form method=post>\n";
  echo "<div class=tablecont><table width=90% border>\n";
  Register_AutoUpdate('Game',$GAMEID);
  echo fm_hidden('id',$GAMEID);

  echo "<tr><td>Id: $GAMEID" . fm_text('Name',$GAME,'Name');
  echo "<tr>" . fm_number("Turn #",$GAME,'Turn') . "<td> 0 = Setup";
  
  echo "<tr>" . fm_textarea("Features",$GAME,'Features',4,4);
  if (Access('God')) echo "<tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea>";
  echo "</table></div>\n";

  echo "<Center><input type=Submit name=ACTION value='Create New Game'></center>\n";
  echo "</form>\n";

  dotail();
?>
