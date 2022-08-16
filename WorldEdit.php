<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("ProjLib.php");
  include_once("HomesLib.php");

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

  $H = Get_ProjectHome($W['Home']);
  if (isset($H['id'])) {
    $Dists = Get_DistrictsH($H['id']);
  
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
    

  
    if (isset($_REQUEST['ACTION'])) { // Post home actions
      switch ($_REQUEST['ACTION']) {
       case 'Militia' :
         Update_Militia($W,$Dists);
         echo "<h2>Militia Updated</h2>";
         break;
       }
    }
    $NumDists = count($Dists);
  } else {
    $NumDists = 0;
  }

  $dc=0;
  $totdisc = 0;

  echo "<h1>Edit World</h1>";
  echo "The only things you can change (currently) is the name, description and relative importance - higher numbers appear first when planning projects.<p>\n";
  Register_AutoUpdate("Worlds",$Wid);
  echo fm_hidden('id', $Wid);
  echo "<table border>";
  if ($GM) echo "<tr><td>Id:<td>$Wid\n";
  echo "<tr>" . fm_text('Name',$WH,'Name',3,'','',"Name:" . $W['ThingType'] . ":" . $W['ThingId']);
  echo "<tr>" . fm_textarea('Description',$WH,'Description',8,3,'','', "Description:" . $W['ThingType'] . ":" . $W['ThingId']);
  echo "<tr><td>Minerals<td>" . $W['Minerals'];
  echo "<tr>" . fm_number("Relative Importance", $W, 'RelOrder');
/*
  if ($GM) {
    echo "<tr>" . fm_number('Devastation',$W,'Devastation');
    echo "<tr>" . fm_number('Economy Factor
*/
  $NumCom = 0;
  $NumPrime = $Mines = 0; $DeltaSum = 0;
  if ($NumDists) {
    if ($NumDists) echo "<tr><td rowspan=" . ($NumDists+1) . ">Districts:";
    foreach ($Dists as $D) {
      echo "<tr><td>" . $DTs[$D['Type']]['Name'] . ": " . $D['Number'];
      if ($NeedDelta) {
        echo fm_number1("Delta",$D,'Delta',''," min=-$NeedDelta max=$NeedDelta ","Dist:Delta:" . $D['id']);
        $DeltaSum += $D['Delta'];
      }
    }
    if ($NeedDelta && $DeltaSum != 0) {
      echo "<tr><td colspan=3 class=Err>The Deltas do not sum to zero";
    }
  } else {
    echo "<tr><td>No Districts currently\n";
  }
  $H['Economy'] = Recalc_Economic_Rating($H,$W,$Fid);
  if (isset($H['id'])) Put_ProjectHome($H);

  if (!empty($WH['MaxDistricts'])) echo "<td>Max Districts: " . $WH['MaxDistricts'];
  echo "<tr><td>Economy:<td>" . $H['Economy'];

  if (Access('God')) echo "<tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea>";  
  
  echo "</table>";
  
  if (Access('GM')) {
    echo "<h2><a href=WorldEdit.php?ACTION=Militia&id=$Wid>Update Militia</a></h2>";
    if (!isset($H['id'])) {
      echo "<h2>No Home! - <a href=WorldEdit.php?ACTION=DELETE&id=$Wid>Delete?</h2>\n";
    }
  }
  
  dotail();
?>
