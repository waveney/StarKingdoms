<?php

function Meetups() {
  // For each system get all things.  If more than 1 faction, note location flag for that system
  // if (more than 1 has "control" then there is a bundle
  // if only 1 and has control and system controlled then there is a bundle
  // ships, agents ...
  //  echo "Meetups is currently Manual<p>";
  $_REQUEST['TurnP'] = 1; // Makes Meetings think its part of turn processing
  include_once("Meetings.php");

  return 1;
}

function SpaceCombat() {
  GMLog("Space Combat is currently Manual<p>");
  return 1;
}


function OrbitalBombardment() {
  GMLog("Orbital Bombardment is currently Manual<p>");
  return 1;
}

function PlanetaryDefence() {
  GMLog("Planetary Defence is currently Manual<p>");
  return 1;
}

function GroundCombat() {
  GMLog("Ground Combat is currently Manual<p>");
  return 1;
}


function DevastationSelection() {
  global $ForceNoFaction;
  GMLog("<h2>Please mark those worlds with combat on</h2>");

  $_REQUEST['CONFLICT'] = 1; // Makes WorldList think its part of turn processing - 1 for setting flags

  $ForceNoFaction = 1;
  include_once("WorldList.php");
  return 1;

}

function Devastation() {
  $Worlds = Get_Worlds();
  foreach ($Worlds as $W) {
    $H = Get_ProjectHome($W['Home']);
    if ($W['Conflict']) {
      $H['EconomyFactor'] = 50;
      $H['Devastation']++;

      $Dists = Get_DistrictsH($H['id']);
      $Dcount = 0;
      foreach ($Dists as $D) $Dcount += $D['Number'];

      if ($H['Devastation'] > $Dcount) { // Lose districts...
        $LogT = Devastate($H,$W,$Dists,1);
        TurnLog($H['Whose'],$LogT);
        GMLog($LogT,1);
      }
      Put_ProjectHome($H);
    } else { // Recovery?
      if ($H['EconomyFactor'] != 100) {
        $H['EconomyFactor'] = 100;
        Put_ProjectHome($H);
      }
      //      $Things = Get_Things_Cond(0,"CurHealth != OrigHealth AND SystemId=$Sys AND WithinSysLoc=$Loc)
    }
  }

  // Look for Militia to recover

  GMLog("Devastation is Complete<p>");
  return 1;

}

function ReturnMilOrgForces() {
  $TTypes = Get_ThingTypes();
  $NTypes = array_flip(NamesList($TTypes));
  $Things = Get_Things_Cond(0,"( Type=" . $NTypes['Heavy Security'] . " OR Type=" . $NTypes['Fighter Defences'] . ") AND LinkId!=" . LINK_INBRANCH);
  $Count = 0;
  foreach($Things as $Tid=>$T) {
    $Count++;
    $Bid = $T['ProjectId'];
    if (Gen_Get('Branches',$Bid)) {
      $T['LinkId'] = LINK_INBRANCH;
      $T['SystemId'] = 0;
      Put_Thing($T);
    } else { // Branch missing
      GMLog( "BRANCH $Bid MISSING...");
      Thing_Delete($Tid);
    }
  }

  $Things = Get_Things_Cond(0,"Type=" . $NTypes['Militia'] . " AND LinkId!=" . LINK_INBRANCH);
  foreach($Things as $Tid=>$T) {
    $Count++;
    $T['LinkId'] = LINK_INBRANCH;
    Put_Thing($T);
  }

  GMLog("$Count Forces have returned<p>");
  return 1;

}

