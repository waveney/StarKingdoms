<?php
  include_once("sk.php");
  include_once("PlayerLib.php");
  global $Access_Type,$USER,$YEAR,$FACTION,$GAME;
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
  $GM = Access('GM');

  if ($FACTION) echo "<a href=Player.php onmouseover=NoHoverSticky()>Faction Menu</a>";
  if ($GM) echo "<a href=Staff.php onmouseover=NoHoverSticky()>GM Menu</a>";
  echo "<a href='Login.php?ACTION=LOGOUT' onmouseover=NoHoverSticky()>Logout " . //"User</a>";
    Faction_Feature('LoginAKA', (!empty($USER['AKA'])?$USER['AKA']: (isset($USER['Login'])?$USER['Login']:" "))) . "</a>";
  if ($GM && $FACTION ) {
    echo "<a href=Access.php?id=" . $FACTION['id'] . "&Key=" . $FACTION['AccessKey'] . " style='background:" . $FACTION['MapColour'] . "; color: " .
         ($FACTION['MapText']?$FACTION['MapText']:'black') .
         ";text-shadow: 2px 2px 3px white;padding:2px'>"  . $FACTION['Name'] . "</a>";
  }

  echo "<div class=floatright><a href=" . ($GM?'Staff.php':'Player.php') . ">" . $GAME['Name'] . "</a></div>";
  echo "</div></div>\n";
  $hpost = Feature('HeaderPostTools');
  if ($hpost) echo $hpost;


?>

