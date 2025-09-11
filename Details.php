<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("PlayerLib.php");
  include_once("HomesLib.php");
  include_once("SystemLib.php");
  global $FACTION;

  dostaffhead("Details");

  A_Check('Player');
  $Facts = Get_Factions();
  $GM = Access('GM');
  $Fid = ($FACTION['id']??0);

  $TTypes = Get_ThingTypes();
  $TTNames = ListNames($TTypes);

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
        ShowWorld($W);
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
        echo "<h1>Details of " . ($OutP['Name']??'') . " Outpost in " . System_Name($System,$Fid) . "<h1>";
        echo "<table border>";
        echo "<tr><td>Controlled by:<td " . FactColours($OutP['Whose']) . ">" . ($Facts[$OutP['Whose']]['Name']??'No One');
        echo "<tr><td>Branches:<td>";
        foreach ($Branches as $B) {
          $BT = Gen_Get('BranchTypes',$B['Type']);
          if (($B['Whose'] != $Fid) && ($BT['Props'] & BRANCH_HIDDEN)) continue;
          $Org = Gen_Get('Organisations',$B['Organisation']);
          $OrgType = Gen_Get('OfficeTypes', $B['OrgType']);
          if ($B['OrgType2'] && ($GM || $B['Whose']==$Fid)) $OrgType2 = Gen_Get('OfficeTypes', $B['OrgType2']);
          echo $Org['Name'] . " (" . $OrgType['Name'] . (($OrgType2??0)? "/" . $OrgType2['Name']:'') . ")<br>";
          echo "<td " . FactColours($B['Whose']) . ">" . ($Facts[$B['Whose']]['Name']??'No One');
        }
        echo "</table>";
      }
    }
  }

  global $FACTION, $GAME, $GAMEID, $db, $Currencies, $LogistCost;
  dotail();

?>
