<?php
  include_once("sk.php");
  global $Access_Type,$USER,$YEAR;
  Set_User();
  $hpre = Feature('HeaderPreTools');

  if ($hpre) echo $hpre;
  if (isset($_COOKIE{'SKC2'})) {
    echo "<div class=Staff><div class=navigation>";
    echo "<a href=/Staff.php onmouseover=NoHoverSticky()>Main Menu</a>";
    echo "<a href='Login.php?ACTION=LOGOUT' onmouseover=NoHoverSticky()>Logout " . $USER['Login'] . "</a>\n";
    echo "</div></div>";
  }
  $host= "https://" . $_SERVER['HTTP_HOST'];

  global $USERID,$PerfTypes;
  if ( isset($USER{'AccessLevel'}) && $USER{'AccessLevel'} == $Access_Type['Participant'] ) {
    echo "<div class=Staff><div class=navigation>";
    switch ($USER{'Subtype'}) {
    }
    echo "</div></div>\n";
  }
  $hpost = Feature('HeaderPostTools');
  if ($hpost) echo $hpost;
  
  
?>

