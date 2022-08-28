<?php
  include_once("sk.php");
  global $Access_Type,$USER,$YEAR,$FACTION;
  global $PlayerState,$PlayerStates,$PlayerStateColours,$Currencies;
  Set_User();
  $hpre = Feature('HeaderPreTools');

  if ($hpre) echo $hpre;
 
  echo "<div class=Staff>";
  echo "<div class=navigation>"; 
  if (isset($FACTION)) {
    include_once("PlayerLib.php");
 //   echo "<div class=NavFact>";
    echo "<span style='background:" . $PlayerStateColours[$FACTION['TurnState']] . "'>" . $PlayerState[$FACTION['TurnState']] . "</span>";
    }
  

  if (isset($_COOKIE{'SKD'})) {
    echo "<a href=Player.php onmouseover=NoHoverSticky()>Faction Menu</a>";
  }
  if (isset($_COOKIE{'SKC2'})) {
    echo "<a href=Staff.php onmouseover=NoHoverSticky()>GM Menu</a>";
    echo "<a href='Login.php?ACTION=LOGOUT' onmouseover=NoHoverSticky()>Logout " . (isset($USER['Login'])?$USER['Login']:" ") . "</a>\n";
  }
  $host= "https://" . $_SERVER['HTTP_HOST'];

  echo "</div></div>\n";
  $hpost = Feature('HeaderPostTools');
  if ($hpost) echo $hpost;
  
  
?>

