<?php
  include_once("sk.php");
  A_Check('GM','Users');

  dostaffhead("Add/Change User");
  include_once("UserLib.php");
  global $GAMESYS,$Sections,$Access_Levels,$Access_Type;

  Set_User_Help();

  $God = Access('God');


//var_dump($_REQUEST);

  echo "<h2>Add/Edit Users</h2>\n";
  echo "Users need a full name, login name, most other things are irrelevant for sk.<p>";
  echo "<form method=post action='AddUser.php'>\n";
  if (isset($_POST['UserId'])) { /* Response to update button */
    $unum = $_POST['UserId'];
    if ($unum > 0) {                                 // existing User
      $User = Get_User($unum);
      if (isset($_POST['ACTION'])) {
        switch ($_POST['ACTION']) {
        case 'Set Password' :
          $hash = crypt($_POST['NewPass'],"WM");
          $User['Password'] = $hash;

          $a = Put_User($User);
          break;
        case 'Remove Access' :
          $User['AccessLevel'] = 0;
          $User['Password'] = 'impossible2guess'; // that is not a valid password
          $User['WMFFemail'] = '';
          $User['Roll'] = 'No Access' . date(' j/m/Y');
          $User['Contacts'] = 0;
          foreach ($Sections as $sec) $User[$sec] = 0;
          $a = Put_User($User);
        }
      } else {
        Update_db_post('People',$User);
      }
    } else { /* New User */
      $proc = 1;
      if (!isset($_POST['Name'])) {
        echo "<h2 class=Err>NO NAME GIVEN</h2>\n";
        $proc = 0;
      }
      if ($proc && !isset($_POST['Login'])) {
        echo "<h2 class=Err>NO login GIVEN</h2>\n";
        $proc = 0;
      }
      if (!Access('God')) $User['AccessLevel'] = $Access_Type['Player'];
      $unum = Insert_db_post('People',$User,$proc);
    }
  } elseif (isset($_GET['usernum']) && $_GET['usernum']) {
    $unum = $_GET['usernum'];
    $User = Get_User($unum);
  } else {
    $unum = -1;
  }

//  echo "<!-- " . var_dump($User) . " -->\n";
  echo "<div class=tablecont><table width=90% border>\n";
    echo "<tr><td>User Id:<td>";
      if (isset($unum) && $unum > 0) {
        echo $unum . fm_hidden('UserId',$unum);
      } else {
        echo fm_hidden('UserId',-1);
        $User['AccessLevel'] = $Access_Type['GM'];
      }
      echo "<tr>" . fm_text('Name', $User,'Name',3,'','autocomplete=off');
      echo "<tr>" . fm_text('AKA', $User,'AKA',3,'','autocomplete=off');
      //    echo "<tr>" . fm_text('Abrev', $User,'Abrev',1,'','autocomplete=off');
    echo "<tr>" . fm_text('Email',$User,'Email',3,'','autocomplete=off');
    echo "<tr>" . fm_text('Phone',$User,'Phone',1,'','autocomplete=off');
    echo "<tr>" . fm_text('Login',$User,'Login');
    echo "<tr>" . fm_text('Roll',$User,'Roll',3);
    echo "<tr>" . fm_text('Relative Order',$User,'RelOrder',3);
    echo "<tr><td>No Tasks (test users only) " . fm_checkbox('',$User,'NoTasks');
    if (Access('God')) {
      echo "<tr><td>Access Level<td>" . fm_select($Access_Levels,$User,'AccessLevel',0,'','',$User['AccessLevel']);
    } else {
      echo "<tr><td>Access Level<td>" . $Access_Type[$User['AccessLevel']];
    }
    echo "<tr>" . fm_text('Image', $User,'Image',3);
//    echo "<tr>" . fm_radio('Show on Contacts Page',$User_Public_Vis,$User,'Contacts');
/*
    $r = 0;
    foreach($Sections as $sec) {
      if ((($r++)&1) == 0) echo "<tr>";
      echo fm_radio("Change " . $sec ,$Area_Levels,$User,$sec,0);
    }
*/
    if (isset($User['LastAccess'])) echo "<tr><td>Last Login:<td>" . ($User['LastAccess']? date('d/m/y H:i:s',$User['LastAccess']):'Never');
    if (Access('SysAdmin')) {
      echo "<tr>" . fm_text('Change Sent',$User,'ChangeSent',1,'','readonly');
      echo "<tr>" . fm_text('Access Key',$User,'AccessKey',1,'','readonly');
      echo "<tr>" . fm_textarea('Prefs',$User,'Prefs',6,2);
      echo "<tr><td>Log Use" . fm_checkbox('',$User,'LogUse');
    }
    echo "</table></div>\n";

  if ($unum > 0) {
    echo "<Center><input type=Submit name='Update' value='Update'>\n";
    echo "</center>\n";
    echo "</form><form method=post action=AddUser.php>" . fm_hidden('UserId',$unum);
    echo " <input type=text name=NewPass size=10>";
    echo "<input type=submit name=ACTION value='Set Password'>\n";

    echo "<input type=submit name=ACTION value='Remove Access' " .
                  "onClick=\"javascript:return confirm('are you sure you want to remove this user?');\"></form> ";
    echo "<h2><a href=Welcome?U=$unum>Send Welcome Email with New Password Link</a> , \n";
  } else {
    echo "<Center><input type=Submit name=Create value='Create'></center>\n";
    echo "</form>\n<h2>";
  }
  echo "<a href=ListUsers.php?FULL>List Users</a> , \n";
  if ($unum >0) echo "<a href=AddUser>Add Another User</a>\n";
  echo "</h2>";

  dotail();
?>
