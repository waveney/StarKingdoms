<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("ProjLib.php");
  include_once("HomesLib.php");

  global $FACTION,$GAMEID;

  $Fid = 0;
  $xtra = '';
  $NeedDelta = 0;
  if (Access('Player')) {
    if (!$FACTION) {
      if (!Access('GM') ) Error_Page("Sorry you need to be a GM or a Player to access this");
    } else {
      $Fid = $FACTION['id'];
      $Faction = &$FACTION;

      $NeedDelta = Has_Trait($Fid,'This can be optimised');
    }
  }
  if ($GM = Access('GM') ) {
    A_Check('GM');
    if (isset( $_REQUEST['F'])) {
      $Fid = $_REQUEST['F'];
    } else if (isset( $_REQUEST['f'])) {
      $Fid = $_REQUEST['f'];
    }
    if (isset($Fid)) $Faction = Get_Faction($Fid);
  }

//  CheckFaction('WorldEdit',$Fid);

  dostaffhead("Edit Worlds and Colonies",["js/ProjectTools.js"]);

  $Wid = $_REQUEST['id'];

  if ($GM) {
    if (isset($_REQUEST['FORCE'])) {
      $GM = 0;
    } else {
      echo "<h2>GM: <a href=WorldEdit.php?id=$Wid&FORCE>This page in Player Mode</a></h2>";
    }
  }

  if (isset($_REQUEST['ACTION'])) { // Pre Home actions
    switch ($_REQUEST['ACTION']) {
      case 'DELETE':
        db_delete('Worlds',$Wid);
        echo "<h2>Deleted</h2>";
        dotail();
    }
  }

  $W = Get_World($Wid);

// var_dump($W)  ;
  if (!isset($W['id'])) {
    echo "<h2>No World selected</h2>";
    dotail();
  }
  $TTypes = Get_ThingTypes();
  $PlanetTypes = Get_PlanetTypes();
  $Fid = $W['FactionId'];
  $DTs = Get_DistrictTypes();
  $DistTypes = 0;
  $SysId = 0;
  $Facts = Get_Factions();
  $SocPs = [];

  $H = Get_ProjectHome($W['Home']);
//  var_dump($H);

  if (isset($H['id'])) {
    if ($H['ThingType'] == 0 || $H['ThingId'] == 0 ) {
      echo "<h2 class=Err>There is a fault with Project Home " . $H['id'] . " Tell Richard</h2>";

    }

    $Dists = Get_DistrictsH($H['id']);
    $SocPs = Get_SocialPs($W['id']);

// var_dump($Dists);
    switch ($W['ThingType']) {
      case 1: //Planet
        $WH = $P = Get_Planet($W['ThingId']);
        $type = $PlanetTypes[$P['Type']]['Name'];
        if ($PlanetTypes[$P['Type']]['Append']) $type .= " Planet";
        $Name = $P['Name'];
        $SysId = $P['SystemId'];
        $EditP = "PlanEdit.php";
        break;

      case 2: /// Moon
        $WH = $M = Get_Moon($W['ThingId']);
        $type = $PlanetTypes[$M['Type']]['Name'];
        if ($PlanetTypes[$M['Type']]['Append']) $type .= " Moon";
        $Name = $M['Name'];
        $P = Get_Planet($M['PlanetId']);
        $SysId = $P['SystemId'];
        $EditP = "MoonEdit.php";
        break;

      case 3: // Thing
        $WH = $T = Get_Thing($W['ThingId']);
        $type = $TTypes[$T['Type']]['Name'];
        $Name = $T['Name'];
        $SysId = $T['SystemId'];
        $EditP = "ThingEdit.php";
        break;
      default: // Error
        echo "<h2 class=Err>There is a fault with World " . $W['id'] . " Tell Richard</h2>";
        break;
    }

    $FS = Get_FactionSystemFS($Fid,$SysId);
    $PlanetLevel = max(1,($FS['PlanetScan']??1));

    if (isset($_REQUEST['ACTION'])) { // Post home actions
      switch ($_REQUEST['ACTION']) {
       case 'Militia' :
         Update_Militia($W,$Dists);
         echo "<h2>Militia Updated</h2>";
         break;
       case 'XMilitia' : // Transfer to who?
         $FactNames = Get_Faction_Names();
         [$Fact_Colours,$Fact_Text_Colours] = Get_Faction_Colours();

         echo "<form method=post action=WorldEdit.php?ACTION=XMilitia2>";
         echo fm_hidden('id',$Wid);
         echo  fm_radio('Whose',$FactNames ,$_REQUEST,'Whose','',1,'','',$Fact_Colours,0,'','',$Fact_Text_Colours);
         echo "<br><input type=submit value='Transfer'>";
         dotail();

       case 'XMilitia2' : // Transfer
         Update_Militia($W,$Dists,$_REQUEST['Whose']);
         echo "<h2>Militia Transfered and Updated</h2>";
         dotail();
         break;

       case 'MilitiaDeploy':
         $TotC = Update_Militia($W,$Dists,0,1);
         $Home = Get_ProjectHome($W['Home']);
         $Sid = $Home['SystemId'];
         echo "$TotC Militia have been deployed.<p><h2><a href=Meetings.php?ACTION=Check&S=$Sid>Return to Meetups</a></h2>";
         dotail();
         break;

       }
    }
    $NumDists = 0;
    foreach ($Dists as $DT) {
      $NumDists += $DT['Number'];
      $DistTypes++;
    }
  } else {
    $NumDists = 0;
  }

  ShowWorld($W,($GM?2:1),$NeedDelta);

  if ($GM) {
    echo "<h2><a href=WorldEdit.php?ACTION=Militia&id=$Wid>Update Militia</a>, <a href=WorldEdit.php?ACTION=XMilitia&id=$Wid>Transfer Militia</a>, ";
    if (!isset($H['id'])) {
      echo "No Home! - <a href=WorldEdit.php?ACTION=DELETE&id=$Wid>No Home! Delete?</a>, \n";
    } else {
      echo "<a href=ProjHomes.php?ACTION=EDIT&id=" . $H['id'] .">Goto Project Home</a>";
    }
    echo ", <a href=BranchEdit.php?Action=Add&W=$Wid>Add Branch</a>";
    echo ", <a href=OfficeEdit.php?Action=Add&W=$Wid>Add Office</a>";
    echo ", <a href=SocialEdit.php?Action=Add&W=$Wid>Add Social Principle</a>";
    echo "</h2>\n";
  }

  dotail();
?>
