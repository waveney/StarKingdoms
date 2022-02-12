<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("ProjLib.php");
  include_once("HomesLib.php");

  $Fid = 0;
  $xtra = '';
  if (Access('Player')) {
    if (!$FACTION) {
      if (!Access('GM') ) Error_Page("Sorry you need to be a GM or a Player to access this");
    } else {
      $Fid = $FACTION['id'];
      $Faction = &$FACTION;
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
  
  dostaffhead("Edit Worlds and Colonies",["js/ProjectTools.js"]);

  $Wid = $_REQUEST['id'];
  $W = Get_World($Wid);
  $H = Get_ProjectHome($W['Home']);
  $Dists = Get_DistrictsH($H['id']);
  $PlanetTypes = Get_PlanetTypes();
  $Fid = $W['FactionId'];
  
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
    
  $DTs = Get_DistrictTypes();

  $NumDists = count($Dists);
  $dc=0;
  $totdisc = 0;

  echo "<h1>Edit World</h1>";
  echo "The only things you can change (currently) is the name, description and relative importance - higher numbers appear first when planning projects.<p>\n";
  Register_AutoUpdate("Worlds",$Wid);
  echo fm_hidden('id', $Wid);
  echo "<table border>";
  if ($GM) echo "<tr><td>Id:<td>$Wid\n";
  echo "<tr>" . fm_text('Name',$WH,'Name',3,'',"Name:" . $W['ThingType'] . ":" . $W['ThingId']);
  echo "<tr>" . fm_textarea('Description',$WH,'Description',8,3,'','', "Description:" . $W['ThingType'] . ":" . $W['ThingId']);
  echo "<tr><td>Minerals<td>" . $W['Minerals'];
  echo "<tr>" . fm_number("Relative Importance", $W, 'RelOrder');

  $NumCom = 0;
  $NumPrime = 0;
  if ($NumDists) {
    if ($NumDists) echo "<tr><td rowspan=" . ($NumDists+1) . ">Districts:";
    foreach ($Dists as $D) {
      $did = $D['id'];
      echo "<tr><td>" . $DTs[$D['Type']]['Name'] . ": " . $D['Number'];
      $totdisc += $D['Number'];
      if ($D['Type'] == 1) $NumCom = $D['Number'];
      if ($DTs[$D['Type']]['Props'] & 1) $NumPrime += $D['Number'];
    }
    $Econ = (Has_Trait($Fid,'No customers')?($NumPrime - $NumCom):$NumPrime)*$NumCom*2 + min($W['Minerals'],$NumPrime);
    $H['Economy'] = $Econ;
    Put_ProjectHome($H);
  } else {
    echo "<tr><td>No Districts currently\n";
  }
  if (!empty($WH['MaxDistricts'])) echo "<td>Max Districts: " . $WH['MaxDistricts'];
  echo "<tr><td>Economy:<td>" . $H['Economy'];
  echo "</table>";
  
  dotail();
?>
