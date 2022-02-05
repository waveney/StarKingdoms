<?php

// Lib of player stuff
include_once("sk.php");
include_once("GetPut.php");

$PlayerState = ['Setup', 'Turn Planning' , 'Turn Submitted', 'Turn Being Processed'];
$PlayerStateColours = ['Orange','lightblue','LightGreen','pink'];
$PlayerStates = array_flip($PlayerState);
global $PlayerState,$PlayerStates;

function Player_Page() {
  global $FACTION,$PlayerState,$PlayerStates,$PlayerStateColours;


  dostaffhead("Things",["js/ProjectTools.js"]);  
  $GM = Access('GM');

  $FACTION['LastActive'] = time();
  
  if (!$GM || $FACTION['NPC']) Put_Faction($FACTION);
  
//var_dump($PlayerState,$FACTION);
  echo "<h1>Player Actions</h1>\n";
  

  
  echo "<h2>Player state: <span style='background:" . $PlayerStateColours[$FACTION['TurnState']] . "'>" . $PlayerState[$FACTION['TurnState']] . "</span></h2>";
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
    echo "<li>" . ($GM? "<a href=WhatCanIC.php>What can I See?</a>" : "What can I See?") . "\n";
    echo "<li>" . ($GM? "<a href=ProjDisp.php>Worlds and Things with Projects</a>" : "Worlds and Things with Projects") . "\n";
    echo "<li>" . ($GM? "<a href=PThingList.php>List of Things</a>" : "List of Things") . " List of Things (Ships, Armies, Agents, Space stations etc)";
    echo "<li>" . ($GM? "<a href=ThingPlan.php>Plan a Thing</a>" : "Plan a Things") . " Planning Things (Ships, Armies, Agents, Space stations etc)";
    echo "<li>Economy";
    echo "<li>" . ($GM? "<a href=Banking.php>Banking</a>" : "Banking") . " Sending credits to others and statements";
    echo "<li><a href=PlayerTurnTxt.php>Turn Actions Text</a> Logs of all automated actions";
    echo "<li>" . ($GM? "<a href=Player.php?ACTION=Submit>Submit Turn</a>" : "Submit Turn") . "\n";
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

  echo "</ul>";
  echo "</div>";
  
  dotail();

}

function Has_Trait($Name,$fid=0) {
  global $FACTION;
  
  if ($fid == 0) {
    if (!isset($FACTION)) return false;
    if ($FACTION['Trait1'] == $Name || $FACTION['Trait2'] == $Name || $FACTION['Trait2'] == $Name) return true;
    return false;
  }
  $Fact = Get_Faction($fid);
  if ($Fact['Trait1'] == $Name || $Fact['Trait2'] == $Name || $Fact['Trait2'] == $Name) return true;
  return false;
  
}

function Ships() {
  global $FACTION;
  
}

function Spend_Credit($Who,$Amount,$Why) { // Ammount is negative to gain credits
  $Fact = Get_Faction($Who);
  $StartC = $Fact['Credits'];
  $CR = ['Whose'=>$Who, 'StartCredits'=>$StartC, 'Amount'=>$Amount, 'What'=>$Why];
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
