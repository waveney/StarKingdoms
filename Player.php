<?php
  include_once("sk.php");
  global $FACTION,$GAMEID,$USER,$GAME;
  include_once("GetPut.php");
  include_once("PlayerLib.php");
  global $PlayerState,$PlayerStates;

function ValidateTurn() {
  global $FACTION,$GAME;
  $Fid = $FACTION['id'];
  $Turn = $GAME['Turn'];
  $Valid = 1;

  // Check for Post it notes
  $Projects = Get_Projects_Cond("FactionId=$Fid AND Type=38 AND ((TurnStart=$Turn AND Status=0 ) OR (Status=1 AND TurnStart<=$Turn AND ( TurnEnd=0 OR TurnEnd>=$Turn)))");
  if ($Projects) {
    echo "<h2 class=Err>Warning - you have " . count($Projects) . " Post It Note this turn</h2>\n";
    $Valid = 0;
  }

  $ProjTypes = Get_ProjectTypes();
  $Projects = Get_Projects_Cond("FactionId=$Fid AND TurnStart=$Turn");
  foreach ($Projects as $P) {
    if (($ProjTypes[$P['Type']]['Props'] & 6) == 2) { // Has one thing but not 2
      $Pid = $P['id'];
      if (empty($P['Name'])) $P['Name'] = 'Nameless';
      $Tid = $P['ThingId'];
      if ($Tid == 0) {
        echo "<h2 class=Err>Warning - your project: <a href=ProjEdit.php?id=$Pid>" . $P['Name'] . "</a> is level " . $P['Level'] . " trying to make an unknown thing</h2>\n";
        $Valid = 0;               
        continue;
      }
      $T = Get_Thing($Tid);
//var_dump($T); echo "<p>";

      if (empty($T)) {
        echo "<h2 class=Err>Warning - your project: <a href=ProjEdit.php?id=$Pid>" . $P['Name'] . "</a> is level " . $P['Level'] . " trying to make an unknown thing</h2>\n";
        $Valid = 0;               
      } else if ($P['Level'] != $T['Level']) {
        if ($P['Level'] < $T['Level']) {
          echo "<h2 class=Err>Warning - your project: <a href=ProjEdit.php?id=$Pid>" . $P['Name'] . "</a>  is level " . $P['Level'] . " trying to make a level " . $T['Level'] . " thing</h2>\n";
          $Valid = 0;          
        } else {
          echo "<h2 class=Err>Warning - your project: <a href=ProjEdit.php?id=$Pid>" . $P['Name'] . "</a>  is level " . $P['Level'] . " trying to make a level " . $T['Level'] . " thing</h2>\n";
          $Valid = 0;
        }
      }
    }
  }
     
  return $Valid;
}


  A_Check('Player');
  dostaffhead("Player Actions");
  
  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
      case 'Submit' :  // TODO add checking of turn
        if (!ValidateTurn()) {
          echo "<h2><a href=Player.php>Return to faction menu</a></h2>\n";
          echo "<h2><a href=Player.php?ACTION=SubmitForce>Force the submission past the errors</a></h2>\n";
          dotail();
        }

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


  echo "You can always get back here by clicking on 'Faction Menu' on the bar above.<br>\n";
  
  Player_Page();
  dotail();  
?>
