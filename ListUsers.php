<?php
  include_once("sk.php");
  include_once("GetPut.php");

  global $USER;

  if (isset($_REQUEST['FULL'])) {
    $Full = 1;
    A_Check('God','Users');
  } else {
    $Full = 0;
    A_Check('GM');
  }

  dostaffhead("List Starkingdom Users");
//  include_once("DocLib.php");
  include_once("UserLib.php");
  global $Access_Levels;

  $Users = Get_People();

// var_dump($Users);
//  echo "<button class='floatright FullD' onclick=\"($('.FullD').toggle())\">All Users</button><button class='floatright FullD' hidden onclick=\"($('.FullD').toggle())\">Curent Users</button> ";

  $coln = 0;
  if ($Full) {
    echo "Click on the Name or User Id to edit.  Click on column to sort by column.<p>\n";
  }
  echo "<div class=tablecont><table id=indextable border>\n";
  echo "<thead><tr>";

  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>User Id</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Name</a>\n";
//  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Abrev</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Login</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Email</a>\n";
//  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Phone</a>\n";
//  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Fest Email</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Access Level</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Last IP</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Last Access</a>\n";
  //  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Roll</a>\n";
//  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Rel Order</a>\n";
//  if (feature('ShowContactPhotos')) echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Image</a>\n";
//  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Public</a>\n";
  if ($Full) {
//    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Test User</a>\n";
//    echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Show</a>\n";
//    foreach ($Sections as $sec)
//      echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>$sec</a>\n";
    }
  echo "</thead><tbody>";



  foreach ($Users as $usr) {
//    if ($Full == 0 && $usr['NoTasks']) continue;
    echo "<tr>"; //. (($usr['id']<11 || $usr['AccessLevel'] == 0)?" class=FullD hidden" : "" ) . ">";
    echo "<td>" . $usr['id'] . "<td>";
    echo  (($Full || Access('SysAdmin') || $USER['AccessLevel'] >= $usr['AccessLevel'])? ("<a href=AddUser.php?usernum=" . $usr['id'] . ">" . $usr['Name'] . "</a>") : $usr['SN']);
//    echo "<td>" . $usr['Abrev'];
    echo "<td>" . $usr['Login'] . "<td>" . $usr['Email'] . "<td>" . $Access_Levels[$usr['AccessLevel']] . "<td>" . $usr['LastIP'];
    if ($usr['LastAccess']) echo "<td>" . date('d/m/y H:i:s',$usr['LastAccess']);
    //    echo "<td>" . $usr['Roll'] . "<td>" . $usr['RelOrder'] ;
    if (feature('ShowContactPhotos')) {
      echo "<td>";
      if ($usr['Image']) echo "<img src='" . $usr['Image'] . "' width=50>";
      }
//    echo "<td>" . $User_Public_Vis[$usr['Contacts']];

//    if ($Full) {
//      echo "<td>";
//      echo "<td>";
//      if ($usr['NoTasks']) echo "Y";
//      echo "<td>";
//      if ($usr['Contacts']) echo "Y";
//      foreach ($Sections as $sec) {
//        echo "<td>" . $Area_Levels[$usr[$sec]];
//      }
    }

  echo "</tbody></table></div>\n";

  if ($Full) echo "<h2><a href=AddUser.php>Add User</a></a>";

  dotail();
?>

