<?php
  include_once("sk.php");
  global $Access_Type,$USER,$YEAR,$FACTION;
  global $PlayerState,$PlayerStates,$PlayerStateColours,$Currencies;
  Set_Faction();
  $hpre = Feature('HeaderPreTools');

  if ($hpre) echo $hpre;

  echo "<div class=Staff>";
  echo "<div class=navigation>";
  if ($FACTION) {
    include_once("PlayerLib.php");
    echo "<span style='background:" . $PlayerStateColours[$FACTION['TurnState']] . "'>" . $PlayerState[$FACTION['TurnState']] . "</span>";
    }


  if ($FACTION) echo "<a href=Player.php onmouseover=NoHoverSticky()>Faction Menu</a>";
  if (Access('GM')) echo "<a href=Staff.php onmouseover=NoHoverSticky()>GM Menu</a>";
  echo "<a href='Login.php?ACTION=LOGOUT' onmouseover=NoHoverSticky()>Logout " . (isset($USER['Login'])?$USER['Login']:" ") . "</a>";
  if (Access('GM') && $FACTION ) {
    echo "<a href=Access.php?id=" . $FACTION['id'] . "&Key=" . $FACTION['AccessKey'] . " style='background:" . $FACTION['MapColour'] . "; color: " .
         ($FACTION['MapText']?$FACTION['MapText']:'black') .
         ";text-shadow: 2px 2px 3px white;padding:2px'>"  . $FACTION['Name'] . "</a>";
  }

  echo "</div></div>\n";
  $hpost = Feature('HeaderPostTools');
  if ($hpost) echo $hpost;


?>

