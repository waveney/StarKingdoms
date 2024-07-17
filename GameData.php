<?php
  include_once("sk.php");
  include_once("GetPut.php");

  A_Check('GM');
  global $GAME,$GAMEID,$GameStatus;

  dostaffhead("General Game Settings");
  echo "Game states: Planning - World creation, rules coding, no players<br>Setup - Players setting up, trait specials being coded<br>" .
    "Active - In play<br>Historical - Game has ended, Readonly<p>\n";

  echo "<h2 class=Err>DO NOT EDIT ANYTHING HERE UNLESS YOU <u>KNOW</u> WHAT YOU ARE DOING - In doubt ask Richard...</h2>";
  include_once("DateTime.php");

  echo "<div class='content'><h2>General Game Settings</h2>\n";

  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
    case 'Update' :
//      Parse_DateInputs($Dates);
//      $Gen = $Gens[$_POST['Year']];
//      Update_db_post('General',$Gen);
//      $ynum = $Gen['Year'];
      break;

    case 'Create' :
//      Parse_DateInputs($Dates);
//      Insert_db_post('General',$Gen);
//      $ynum = $Gen['Year'];
      break;

    case 'Key' :
      $GAME['AccessKey'] = rand_string(40);
      Gen_Put('Games',$GAME);
      break;

    case 'Setup' :
//      $Gen = [];
      $ynum = 0;
      break;
    }
  }

  $Users = Get_People();
  $Userlist = [];
  foreach($Users as $U) $Userlist[$U['id']] = $U['Name'];

  echo "<form method=post>\n";
  echo "<div class=tablecont><table width=90% border>\n";
  Register_AutoUpdate('Games',$GAMEID);
  echo fm_hidden('id',$GAMEID);

  echo "<tr><td>Id: $GAMEID" . fm_text('Name',$GAME,'Name');
  echo "<tr>" . fm_number("Turn #",$GAME,'Turn') . "<td>State:" . fm_select($GameStatus,$GAME,'Status');
  echo "<tr><td>Access Key:<td colspan=3>" . $GAME['AccessKey'];
  echo "<tr>" . fm_text('Code Prefix',$GAME,'CodePrefix');
  echo "<tr>" . fm_textarea("Features",$GAME,'Features',10,20);
//  echo "<tr><td>GM 1<td>" . fm_select($Userlist,$GAME,'GM1',1);
//  echo "<tr><td>GM 2<td>" . fm_select($Userlist,$GAME,'GM2',1);
//  echo "<tr><td>GM 3<td>" . fm_select($Userlist,$GAME,'GM3',1);

  if (Access('God')) echo "</tbody><tfoot><tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea>";
  echo "</table></div>\n";

//  echo "<Center><input type=Submit name=ACTION value='Create New Game'></center>\n";
  if (Access('God')) echo "<a href=GameData.php?ACTION=Key>New Access Key</a>";
  echo "</form>\n";

  dotail();
?>
