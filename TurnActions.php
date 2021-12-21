<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("PlayerLib.php");
  
  A_Check('GM');
  
function StartTurnProcess() {
  // Lock players out
  $Facts - Get_Factions();
  foreach ($Facts as $F) {
    $F['TurnState'] = 3;
    Put_Faction($F);
  }
  echo "<br>All Factions marked as Turn Processing<p>\n";
}

function StartProjects() {
  /// Pay upfront costs
}

function StartAnomaly() {

}

function Economy() {
  // Transfer all monies and tolls, work out economies and generate income for each faction.
  // Blockades, theft and new things affect - this needs to be done BEFORE projects complete
}  

function LoadTroops() {

}

function Movements() {
  // Foreach thing, do moves, do payments, generate list of new survey reports & levels, update "knowns" 
  
  $Things = Get_AllThings();
  foreach ($Things as $T) {
    if ($T['LinkId'] ) {
      $Lid = $T['LinkId']; 
      
      $L = Get_Link($Lid);
      
      $SR1 = Get_SystemR($L['System1Ref']);
      $SR2 = Get_SystemR($L['System2Ref']);
      
      $FL = Get_FactionLinkFL($Fid,$Lid);
      $FL['Known'] = 1;
      Put_FactionLink($FL);
      
      $FS1 = Get_FactionSystemFS($Fid,$SR1['id']);

      if (isset($FS1['ScanLevel'])) { 
        echo "Already seen system " . $L['System1Ref'] . " at level " . $FS1['ScanLevel'];
      } else {
        $FS1['ScanLevel'] = 1;
        echo "System " . $L['System1Ref'] . " is new give a survey report";
        Put_FactionSystem($FS1);
      }
      echo "<p>";
        
      $FS2 = Get_FactionSystemFS($Fid,$SR2['id']);
      if (isset($FS2['ScanLevel'])) { 
        echo "Already seen system " . $L['System2Ref'] . " at level " . $FS2['ScanLevel'];
      } else {
        $FS2['ScanLevel'] = 1;
        echo "System " . $L['System2Ref'] . " is new give a survey report";
        Put_FactionSystem($FS2);
      }
      echo "<p>";
  
  
   // Log movement into history

}


function Meetups() {
  // For each system get all things.  If more than 1 faction, note location flag for that system
  // if (more than 1 has "control" then there is a bundle
  // if only 1 and has control and system controlled then there is a bundle
  // ships, agents ...

}

function SpaceCombat() {

}

function OrbitalBombardment() {

}

function GroundCombat() {

}


function ProjectProgress() {
  // Mark progress on all things, if finished change state appropriately 
  
}


function Espionage() {

}


function ProjectsComplete() {

}

function GenerateTurns() {

}

function FinishTurnProcess() {
  // Change faction state

  $Facts - Get_Factions();
  foreach ($Facts as $F) {
    $F['TurnState'] = 1;
    Put_Faction($F);
  }
  echo "<br>All Factions marked as Turn Processing<p>\n";


  // TODO Send messages to discord ??
}

  dotail();
?>
