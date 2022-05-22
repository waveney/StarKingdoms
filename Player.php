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
  
  if (Has_Trait($Fid, 'This can be optimised')) {
    $Ws = Get_Worlds($Fid);
    $TTypes = Get_ThingTypes();
    $PlanetTypes = Get_PlanetTypes();
    $DTs = Get_DistrictTypes();

    foreach($Ws as $W) {
      $H = Get_ProjectHome($W['Home']);
      switch ($W['ThingType']) {
      case 1: //Planet
        $WH = $P = Get_Planet($W['ThingId']);
        $type = $PlanetTypes[$P['Type']]['Name'];
        if ($PlanetTypes[$P['Type']]['Append']) $type .= " Planet";
        $Name = $P['Name'];
        break;
        
      case 2: /// Moon
        $WH = $M = Get_Moon($W['ThingId']);
        $type = $PlanetTypes[$M['Type']]['Name'];
        if ($PlanetTypes[$M['Type']]['Append']) $type .= " Moon";
        $Name = $M['Name'];
        break;
    
      case 3: // Thing
        $WH = $T = Get_Thing($W['ThingId']);
        $type = $TTypes[$T['Type']]['Name'];
        $Name = $T['Name'];
        break;
      }

      $Dists = Get_DistrictsH($H['id']);

      $DeltaSum = $Used = 0;
      foreach($Dists as $D) {
        if ($D['Delta']) {
          $Used++;
          $DeltaSum += $D['Delta'];
        }
      }
      if ($Used == 0) {
        echo "<h2 class=Err>Warning - No use of <b>This can be optimised</b> on $Name</h2>";
        $Valid = 0;
      } elseif ($Used > 2) {
         echo "<h2 class=Err>Warning - <b>This can be optimised</b> can only be used once on $Name</h2>";
        $Valid = 0;                    
      } elseif ($DeltaSum  != 0) { 
        echo "<h2 class=Err>Warning - <b>This can be optimised</b> on $Name Does not Sum to Zero</h2>";
        $Valid = 0;               
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
