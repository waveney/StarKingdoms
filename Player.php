<?php
  include_once("sk.php");
  global $FACTION,$GAMEID,$USER,$GAME;
  include_once("GetPut.php");
  include_once("PlayerLib.php");
  global $PlayerState,$PlayerStates;

  A_Check('Player');

  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
      case 'Submit' :  // TODO add checking of turn
        $FACTION['TurnState'] = $PlayerStates['Turn Submitted'];
        Put_Faction($FACTION);   
        break;

      case 'SubmitForce' :  // Yes to submit unfinished
        $FACTION['TurnState'] = $PlayerStates['Turn Submitted'];
        Put_Faction($FACTION);   
        break;
          
      case 'Unsub' :
        if ($FACTION['TurnState'] == $PlayerStates['Turn Submitted']) {
          $FACTION['TurnState'] = (($GAME['Turn'] == 0)? $PlayerStates['Setup']: $PlayerStates['Turn Planning']);
          Put_Faction($FACTION);   
        } else {
          echo "<h1 class=Err>Sorry, not allowed now</h1>";
        }
    
        break;
    }
  }

  dostaffhead("Player Actions");
  echo "You can always get back here by clicking on 'Faction Menu' on the bar above.<br>\n";
  
  Player_Page();
  dotail();  
?>
