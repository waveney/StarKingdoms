<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("PlayerLib.php");
  include_once("HomesLib.php");
  include_once("OrgLib.php");
  include_once("SystemLib.php");
  global $FACTION;

  dostaffhead("Details");

  A_Check('Player');
  $Facts = Get_Factions();
  $GM = Access('GM');
  $Fid = ($FACTION['id']??0);

  $TTypes = Get_ThingTypes();
  $TTNames = ListNames($TTypes);

  if ($GM) {
    if (isset($_REQUEST['FORCE'])) {
      $GM = 0;
    } else {
      $R = (isset($_REQUEST['R'])?"&R=" . $_REQUEST['R'] : '');
      $O = (isset($_REQUEST['O'])?"&O=" . $_REQUEST['O'] : '');
      echo "<h2><a href=Details.php?FORCE$R$O>This page in Player Mode</a></h2>";
    }
  }

//  var_dump($TTNames);
  // Validate caller against world control or owner of branch/office

  if (isset($_REQUEST['R'])) {
    $System = Get_SystemR($_REQUEST['R']);
    $Worlds = explode(',',$System['WorldList']);
    foreach($Worlds as $Pl) {
      if ($Pl>0) {
        $TType = 1;
        $Tid = $Pl;
      } else {
        $TType = 2;
        $Tid = -$Pl;
      }

      $Shown = 0;
      if (Gen_Get_Cond1('Districts',"HostType=$TType AND HostId=$Tid") ||
        Gen_Get_Cond1('Branches',"HostType=$TType AND HostId=$Tid") ||
        Gen_Select("SELECT O.* FROM Offices O INNER JOIN Worlds W on O.World=W.id WHERE W.ThingType=$TType AND W.ThingId=$Tid")
        ) {
        $W = Gen_Get_Cond1('Worlds',"ThingType=$TType AND ThingId=$Tid" );
        ShowWorld($W,($GM?2:0));
        $Shown = 1;
      }

    }
    if (!$Shown) {
      echo "<h1>No Districts, Branches or Districts Found</h1>";
    }
    dotail();
  } else if (isset($_REQUEST['O'])) { // Outposts
    $System = Get_SystemR($_REQUEST['O']);
    $OutPs = Get_Things_Cond(0,"Type=" . $TTNames['Outpost'] . " AND SystemId=" . $System['id'] . " AND BuildState=" . BS_COMPLETE);
    if (!$OutPs) {
      echo "<h1>No Outpost Found</h1>";
    } else {
    // Get branches, is one Factions's, show all public and hidden of factions
      $OutP = array_shift($OutPs);
      $Branches = Gen_Get_Cond('Branches',"HostType=3 AND HostId=" . $OutP['id']);
      $Found = 0;
      foreach ($Branches as $B) if ($GM || $B['Whose']==$Fid) $Found = 1;
      if (!$Found) {
        echo "<h1>No branches of yours at that Outpost</h1>";
      } else {
        ShowOutpost($OutP['id'],$Fid,$GM);
      }
    }
  }

  global $FACTION, $GAME, $GAMEID, $db, $Currencies, $LogistCost;
  dotail();

?>
