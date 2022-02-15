<?php

// Lib of player stuff
include_once("sk.php");
include_once("GetPut.php");

$PlayerState = ['Setup', 'Turn Planning' , 'Turn Submitted', 'Turn Being Processed'];
$PlayerStateColours = ['Orange','lightblue','LightGreen','pink'];
$PlayerStates = array_flip($PlayerState);
global $PlayerState,$PlayerStates;


function FactionFeature($Name,$default='') {  // Return value of feature if set from GAMESYS
  static $Features;
  global $FACTION;
  if (!$Features) {
    $Features = [];
    foreach (explode("\n",$FACTION['Features']) as $i=>$feat) {
      $Dat = explode(":",$feat,4);
      if ($Dat[0] && isset($Dat[1])) {
        $Features[$Dat[0]] = trim($Dat[1]);
      } elseif ($Dat[0] && isset($Dat[4])) {
        $Features[$Dat[0]] = trim($Dat[4]);
      }
    }
  }
  if (isset($Features[$Name])) return $Features[$Name];
  return $default;
}

function Player_Page() {
  global $FACTION,$PlayerState,$PlayerStates,$PlayerStateColours,$GAME;


  dostaffhead("Things",["js/ProjectTools.js"]);  
  $GM = Access('GM');
  $FF = FactionFeature('AllowActions',$GM);  // Eventually change GM to 1

  $FACTION['LastActive'] = time();
  
  if (!$GM || $FACTION['NPC']) Put_Faction($FACTION);
  
//var_dump($PlayerState,$FACTION);
  echo "<h1>Player Actions</h1>\n";
  

  
  echo "<h2>Player state: <span style='background:" . $PlayerStateColours[$FACTION['TurnState']] . "'>" . $PlayerState[$FACTION['TurnState']] . "</span> Turn:" .
       $GAME['Turn'] . "</h2>";
  echo "<div class=Player>";
  echo "The only current actions are:";
  echo "<ul>";
    
  switch ($PlayerState[$FACTION['TurnState']]) {
  case 'Setup':
    echo "<li><a href=TechShow.php?PLAYER>Technologies</a>\n";    
    echo "<li><a href=Setup.php>Setup</a>\n";
    echo "<li><a href=ThingSetup.php?T=Ships>Setup Ships</a>\n";    
    echo "<li><a href=ThingSetup.php?T=Army>Setup Armys</a>\n";    
    echo "<li><a href=ThingSetup.php?T=Agent>Setup Agents</a>\n";    
    break;
  
  
  case 'Turn Planning':
    echo "<li><a href=MapFull.php>Faction Map</a>\n";
    if (Has_Tech($FACTION['id'],'Astral Mapping')) echo "<li><a href=MapFull.php?Hex>Faction Map</a> - with spatial location of nodes\n";
    echo "<li><a href=TechShow.php?PLAYER>Technologies</a>\n";
    echo "<li>" . ($FF? "<a href=WhatCanIC.php>What Things can I See?</a>" : "What Things can I See?") . "\n";
    echo "<li>" . ($FF? "<a href=WorldList.php>Worlds and Colonies</a>" : "Worlds and Colonies") . " - High Level info only<p>\n";  
    echo "<li>" . ($FF? "<a href=ProjDisp.php>Worlds and Things with Projects</a>" : "Worlds and Things with Projects") . "\n";
    echo "<li>" . ($FF? "<a href=PThingList.php>List of Things</a>" : "List of Things") . " List of Things (Ships, Armies, Agents, Space stations etc)";
    echo "<li>" . ($FF? "<a href=ThingPlan.php>Plan a Thing</a>" : "Plan a Things") . " Planning Things (Ships, Armies, Agents, Space stations etc)<P>";
    echo "<li>Economy";
    echo "<li>" . ($FF? "<a href=Banking.php>Banking</a>" : "Banking") . " Sending credits to others and statements<p>";
    echo "<li><a href=PlayerTurnTxt.php>Turn Actions Text</a> Logs of all automated actions";
    echo "<li>" . ($FF? "<a href=Player.php?ACTION=Submit>Submit Turn</a>" : "Submit Turn") . "<p>\n";
    echo "<li>" . ($FF? "<a href=FactionEdit.php>Faction Information</a>" : "Faction Information") . " Mostly read only once set up.\n";
    break;
      
  case 'Turn Submitted':
    echo "<li><a href=MapFull.php?PLAYER>Faction Map</a>\n";
    echo "<li><a href=TechShow.php?PLAYER>Technologies</a>\n";  
    echo "<li><a href=Player.php?ACTION=Unsub>Cancel Submission</a>\n";        
    break;
      
  case 'Turn Being Processed':
    echo "<li><a href=TechShow.php?PLAYER>Technologies</a>\n";
    
    break;  

  }

  echo "<p><li><a href=UserGuide.php>User Guide</a>\n";
  
  echo "</ul>";
  echo "</div>";
  
  dotail();

}

function Has_Trait($fid,$Name) {
  global $FACTION;
  if ($fid == 0) {
    if (!isset($FACTION)) return false;
    if ($FACTION['Trait1'] == $Name || $FACTION['Trait2'] == $Name || $FACTION['Trait3'] == $Name) return true;
    return false;
  }
  $Fact = Get_Faction($fid);
  if ($Fact['Trait1'] == $Name || $Fact['Trait2'] == $Name || $Fact['Trait3'] == $Name) return true;
  return false; 
}

function Ships() {
  global $FACTION;
  
}

function Spend_Credit($Who,$Amount,$Why) { // Ammount is negative to gain credits
  global $GAME;
  $Fact = Get_Faction($Who);
  $StartC = $Fact['Credits'];
  $CR = ['Whose'=>$Who, 'StartCredits'=>$StartC, 'Amount'=>$Amount, 'What'=>$Why, 'Turn'=>$GAME['Turn']];
  if ($StartC-$Amount < 0) {
    $CR['Status'] = 0;
    Put_CreditLog($CR);
    return 0;
  } else {
    $Fact['Credits'] -= $Amount;
    Put_Faction($Fact);
    $CR['Status'] = 1;  
    $CR['EndCredits'] = $Fact['Credits'];
    Put_CreditLog($CR);
    return 1;
  }
}

function Link_Cost($Fid,$LinkLevel,$ShipLevel) {
  if ($LinkLevel == 1) return '';
  
}




?>
