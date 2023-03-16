<?php
  include_once("sk.php");
  global $FACTION,$GAMEID,$USER,$GAME;
  include_once("GetPut.php");
  include_once("PlayerLib.php");
  include_once("ThingLib.php");


  global $PlayerState,$PlayerStates,$NewF;
  Set_User();
  if (empty($FACTION)) {
    echo "NOT IN A FACTION";
    dotail();
  }
  $Fid = $FACTION['id'];

function Create_Faction_Q() {
  global $Fid, $FACTION, $Nid, $NewF;

  $MT = [];
  $FactNames = Get_Faction_Names();
  $Fact_Colours = Get_Faction_Colours();
  
  echo "<h1>Split Faction - Stage 1 - Into What?</h1>";
  
  echo "<table border>";
  echo "<tr><td colspan=5><h1>Select either one of</h1>";
  echo "<tr>" . fm_radio('These', $FactNames ,$MT,'Nid','',1,'colspan=6','',$Fact_Colours,0);
  echo "<tr><td><input type=submit name=ACTION value='Existing Faction'>";
  echo "<tr><td colspan=5><h1>OR create a new faction</h1>";
  echo "<tr>" . fm_text('Name',$MT,'Name');
  echo "<tr>" . fm_text('Map Colour',$FACTION,'MapColour');
  echo "<tr><td><input type=submit name=ACTION value='Create New'>";
  echo "</table>";
  echo "</form>";
  dotail();
}

function Copy_Knowledge_Q() {
  global $Fid, $FACTION, $Nid, $NewF;

  $MT = [];  
  echo "<h1>Split Faction - Stage 2 - Copy Knowledge</h1>";
  echo "This is techs, link, system, and anomaly knowlede.<p>";

  echo "<input type=submit name=ACTION value='No Knowledge'> only use this for splitting to an existing faction<p>";
  echo "<h2>OR</h2>";
  echo fm_number('Cap Techs at level',$MT,'CapTech') . " leave empty or 0 to copy all<p>";
  echo "<input type=submit name=ACTION value='Copy Knowledge'><p>";
  echo "</form>";
  dotail();
}

function Transfer_Things_Q() {
  global $Fid, $FACTION, $Nid, $NewF;
  $TTypes = Get_ThingTypes();
  $Systems = Get_SystemRefs();
  $MT = [];
  
  echo "<h1>Split Faction - Stage 3 - Transfer Things</h1>"; 
  echo "<table border><tr><td>Name<td>What<td>Level<td>Where<td>Transfer?";

  $Things = Get_Things($Fid);

  foreach($Things as $Tid=>$T) {
    if ($T['BuildState'] == 0 || $T['BuildState'] > 3 ) continue;

    echo "<tr><td><a href=ThingEdit.php?id=$Tid>" . (empty($T['Name'])? "Nameless $Tid" : $T['Name']) .
         "<td>" . $TTypes[$T['Type']]['Name'] . "<td>" . $T['Level'] . "<td>" . ($T['SystemId'] > 0 ? $Systems[$T['SystemId']] : 'On Board') .
         "<td>" . fm_checkbox('Transfer',$MT,"Transfer$Tid");
  }
  echo "</table>";
  echo "<input type=submit name=ACTION value='Transfer Things'>";
  echo "</form>";
  dotail();
}

function Transfer_Places_Q() {
  global $Fid, $FACTION, $Nid, $NewF;
  $Systems = Get_Systems();
  $Planets = Gen_Get_Cond('Planets',"Control=$Fid");
  $Moons = Gen_Get_Cond('Moons',"Control=$Fid");
  $MT = [];
  
  echo "<h1>Split Faction - Stage 4 - Transfer Places</h1>"; 
  // Systems
  
  echo "<h2>Systems</h2>";
  echo "<table border><tr><td>Ref<td>Name<td>Transfer?";  
  foreach($Systems as $N) {
    if ($N['Control'] == $Fid) {
      $Sid = $N['id'];
      echo "<tr><td><a href=SysEdit.php?id=$Sid>" . $N['Ref'] . "</a><td>" . $N['Name'] . "<td>" . fm_checkbox('Transfer',$MT,"Transfer:S:$Sid");
    }
  }  
  echo "</table>";
  
  // Planets

  echo "<h2>Planets</h2>";
  echo "<table border><tr><td>Ref<td>Name<td>Transfer?";  
  foreach($Planets as $Pl) {
    if ($Pl['Control'] == $Fid) {
      $Pid = $Pl['id'];
      echo "<tr><td><a href=PlanEdit.php?id=$Pid>" . $Systems[$Pl['SystemId']]['Ref'] . "</a><td>" . $Pl['Name'] . 
           "<td>" . fm_checkbox('Transfer',$MT,"Transfer:P:$Pid");
    }
  }  
  echo "</table>";
    
  // Moons
  echo "<h2>Moons</h2>";
  echo "<table border><tr><td>Ref<td>Planet<td>Name<td>Transfer?";  
  foreach($Moons as $M) {
    if ($M['Control'] == $Fid) {
      $Mid = $M['id'];
      echo "<tr><td><a href=MoonEdit.php?id=$Pid>" . $Systems[$Planets[$M['PlanetId']]['SystemId']]['Ref'] . "</a><td>" . $Planets[$M['PlanetId']]['Name'] .
           "<td>" . $Pl['Name'] . "<td>" . fm_checkbox('Transfer',$MT,"Transfer:P:$Pid");
    }
  }  
  echo "</table>";
    
  echo "<input type=submit name=ACTION value='Transfer Places'>";
  echo "</form>";
  dotail();
}


function Create_Faction_A() {
  global $Fid, $FACTION, $Nid, $NewF;
  $NewF = $FACTION;
  $NewF['Name'] = $_REQUEST['Name'];
  $NewF['MapColour'] = $_REQUEST['MapColour'];
  unset($NewF['id']);
  
  $Nid = Put_Faction($NewF);
  Copy_Knowledge_Q();
}

function Existing_Faction_A() {
  global $Fid, $FACTION, $Nid, $NewF;
  Copy_Knowledge_Q();
}

function Copy_Knowledge_A() {
  global $Fid, $FACTION, $Nid, $NewF;
  $Limit = 0;
  if (isset($_REQUEST['CapTech'])) $Limit = $_REQUEST['CapTech'];
  
  // Techs
  $Techs = Get_Faction_Techs($Fid);
  $NewTechs = Get_Faction_Techs($Nid);
  foreach($Techs as $T) {
    if (isset($NewTechs[$T['id']])) continue;
    if ($Limit && $T['Level']>$Limit) $T['Level'] = $Limit;
    unset($T['id']);
    $T['Faction_Id'] = $Nid;
    Put_Faction_Tech($T);
  }
  echo "Copied Techs<p>";
  
  // Systems
  
  $FSs = Gen_Get_Cond('FactionSystem',"FactionId=$Fid");
  if ($FSs) {
    foreach($FSs as $FS) {
      unset($FS['id']);
      $FS['FactionId'] = $Nid;
      $NFS = Get_FactionSystemFS($Nid,$FS['SystemId']);
      if (empty($NFS['id'])) Put_FactionSystem($FS);
    }
  }
  echo "Copied Faction System Data<p>";
  
  // Planets
  
  $FSs = Gen_Get_Cond('FactionPlanet',"FactionId=$Fid");
  if ($FSs) {
    foreach($FSs as $FS) {
      unset($FS['id']);
      $FS['FactionId'] = $Nid;
      $NFS = Get_FactionSystemFS($Nid,$FS['PlanetId']);
      if (empty($NFS['id'])) Put_FactionPlanet($FS);
    }
  }
  echo "Copied Faction Planet Data<p>";
  
  // Moons
  
  $FSs = Gen_Get_Cond('FactionMoon',"FactionId=$Fid");
  if ($FSs) {
    foreach($FSs as $FS) {
      unset($FS['id']);
      $FS['FactionId'] = $Nid;
      $NFS = Get_FactionSystemFS($Nid,$FS['MoonId']);
      if (empty($NFS['id'])) Put_FactionMoon($FS);
    }
  }
  echo "Copied Faction Moon Data<p>";
  
  // Anomalies
  
  $FSs = Gen_Get_Cond('FactionAnomaly',"FactionId=$Fid");
  if ($FSs) {
    foreach($FSs as $FS) {
      unset($FS['id']);
      $FS['FactionId'] = $Nid;
      $NFS = Gen_Get_Cond('FactionAnomaly',"FactionId=$Nid AND AnomalyId=" . $FS['AnomalyId']);
      if (empty($NFS['id'])) Gen_Put('FactionAnomaly',$FS);
    }
  }
  echo "Copied Faction Anomaly Data<p>";
  
  echo "No Faction/Faction data is copied - do that manually afterwards - there are too many special cases.<p>";
  
  Transfer_Things_Q();
}

function Transfer_Things_A() {
  global $Fid, $FACTION, $Nid, $NewF;
  
  foreach($_REQUEST as $Lab=>$Val) {
    if (preg_match('/Transfer(\d*)/',$Lab,$Mtch) && !empty($Val)) {
      $T = Get_Thing($Mtch[1]);
      $T['Whose'] = $Nid;
      Put_Thing($T);
      echo "Transfered " . $T['Name'] . "<br>";
    }
  }
  
  echo "All Done<p>";
  Transfer_Places_Q();
  
}

function Transfer_Places_A() {
  global $Fid, $FACTION, $Nid, $NewF;

  foreach($_REQUEST as $Lab=>$Val) {
    if (preg_match('/Transfer:(\w):(\d*)/',$Lab,$Mtch) && !empty($Val)) {
      switch ($Mtch[1]) {
      case 'S' :
        $N = Get_System($Mtch[2]);
        $N['Control'] = $Nid;
        Put_System($N);
        echo "Transfered " . $N['Ref'] . "<br>";
        break;

      case 'P' :
        $P = Get_Planet($Mtch[2]);
        $P['Control'] = $Nid;
        Put_Planet($P);
        echo "Transfered " . $P['Name'] . "<br>";
        break;

      
      case 'M' :
        $P = Get_Moon($Mtch[2]);
        $M['Control'] = $Nid;
        Put_Moon($M);
        echo "Transfered " . $M['Name'] . "<br>";
        break;
      }
    }
  }
  
  echo "All Done<p>";
  Transfer_Homes_and_Worlds();
  
}

function Transfer_Homes_and_Worlds() {
  global $Fid, $FACTION, $Nid, $NewF;
  
  
  // Find all homes that have changed owners
  $Homes = Gen_Get_Cond('ProjectHomes',"Whose=$Fid");
  foreach ($Homes as $H) {
    switch ($H['ThingType']) {
      case 1: // Planet
        $P = Get_Planet($H['ThingId']);
        if (empty($P['Control'])) {
          $N = Get_System($P['SystemId']);
          if ($N['Control'] != $Nid) continue 2;
        } else if ($P['Control'] != $Nid) continue 2;
        
        break;

      case 2: // Moon
        $M = Get_Moon($H['ThingId']);
        if (empty($M['Control'])) {
          $P = Get_Planet($M['PlanetId']);
          if (empty($P['Control'])) {
            $N = Get_System($P['SystemId']);
            if ($N['Control'] != $Nid) continue 2;
          } else if ($P['Control'] != $Nid) continue 2;
        } else if ($M['Control'] != $Nid) continue 2;
        
        break;

      case 3: // Thing
        $T = Get_Thing($H['ThingId']);
        if ($T['Whose'] != $Nid) continue 2;
    }
    $H['Whose'] = $Nid;
    Put_ProjectHome($H);
  }
  
  echo "Project Homes moved<p>";

  // Find all Worlds that have changed owners
  $Worlds = Gen_Get_Cond('Worlds',"FactionId=$Fid");
  foreach ($Worlds as $H) {
    switch ($H['ThingType']) {
      case 1: // Planet
        $P = Get_Planet($H['ThingId']);
        if (empty($P['Control'])) {
          $N = Get_System($P['SystemId']);
          if ($N['Control'] != $Nid) continue 2;
        } else if ($P['Control'] != $Nid) continue 2;
        
        break;

      case 2: // Moon
        $M = Get_Moon($H['ThingId']);
        if (empty($M['Control'])) {
          $P = Get_Planet($M['PlanetId']);
          if (empty($P['Control'])) {
            $N = Get_System($P['SystemId']);
            if ($N['Control'] != $Nid) continue 2;
          } else if ($P['Control'] != $Nid) continue 2;
        } else if ($M['Control'] != $Nid) continue 2;
        
        break;

      case 3: // Thing
        $T = Get_Thing($H['ThingId']);
        if ($T['Whose'] != $Nid) continue 2;
    }
    $H['FactionId'] = $Nid;
    Put_World($H);
  }
  
  echo "Worlds moved<p>";
  
  
  // Projects
  
  $Homes = Get_ProjectHomes();
  $Projects = Gen_Get_Cond('Projects',"FactionId=$Fid");
  foreach ($Projects as $P) {
    if ($Homes[$P['Home']]['Whose'] == $Nid) {
      $P['FactionId'] = $Nid;
      Put_Project($P);
    }
  }
  echo "Projects moved<p>";
  Split_Resorces_Q();
}

function Split_Resorces_Q() {
  global $Fid, $FACTION, $Nid, $NewF;
  $MT = [];
    
  echo "<h1>Split Faction - Stage 5 - Resources</h1>"; 
  
  echo "<table border><tr>What<td>Amount<td>Transfer\n";
  foreach(['Credits','PhysicsSP','EngineeringSP','XenologySP'] as $R) {
    echo "<tr><td>$R<td>" . $FACTION[$R] . fm_Number1('',$MT,"Transfer:$R");
  }
  
  $Coms = [];
  
  for($Ci = 1 ; $Ci<4; $Ci++) {
    if ($Nam = GameFeature("Currency$Ci")) echo "<tr><td>$Nam<td>" . $FACTION["Currency$Ci"] . fm_Number1('',$MT,"Transfer:Currency$Ci");     
  }

  echo "</table>";
  echo "<input type=submit name=ACTION value='Transfer Resources'>";
  echo "</form>";
  dotail();
}

function Split_Resorces_A() {
  global $Fid, $FACTION, $Nid, $NewF;

  foreach($_REQUEST as $Lab=>$Val) {
    $Val = max($Val,0);
    if (preg_match('/Transfer:(.*)/',$Lab,$Mtch) && !empty($Val)) {
      $What = $Mtch[1];
      if ($Val > $FACTION[$What]) $Val = $FACTION[$What];
      $FACTION[$What] = max(0,$FACTION[$What] - $Val);
      $NewF[$What] += $Val;
    }
  }
  Put_Faction($FACTION);
  $NewF['HomeWorld'] = 0;
  Put_Faction($NewF);
  
  echo "Setup the home world of " . $NewF['Name'] . " Manually afterwards (as well as Faction/Faction information).<p>";
  echo "THEN Rebuild Project Homes, then Rebuild list of Worlds and colonies.<p>";
  echo "<h1>All Done (I hope)</h1>";
  dotail();

}





  A_Check('GM');  // For now
//  dostaffhead("Player Actions");

  dostaffhead("Split Faction",['js/NoReturn.js']);
  echo "<form method=post action=SplitFaction.php>";  
  if (isset($_REQUEST['Nid'])) {
    $Nid = $_REQUEST['Nid'];
    $NewF = Get_Faction($Nid);
    echo fm_hidden('Nid',$Nid);
  } else if (empty($_REQUEST['ACTION']) || $_REQUEST['ACTION']!= 'Start') {
    echo "<h1 class=Err>Lost what it was being split into - please restart from the begining</h1>";
    dotail();
  }
  
  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
      case 'Start' :
        Create_Faction_Q();

      case 'Existing Faction' :  
        Copy_Knowledge_Q();
          
      case 'Create New' :
        Create_Faction_A();
        
      case 'No Knowledge':
        echo "<h1>NO KNOWLEDGE HAS BEEN COPIED</h1>";
        Transfer_Things_Q();
        
      case 'Copy Knowledge':
        Copy_Knowledge_A();
      
      case 'Transfer Things':
        Transfer_Things_A();        

      case 'Transfer Places':
        Transfer_Places_A();
        
      case 'Transfer Resources':
        Split_Resorces_A();
        
    }
  }
  
  Create_Faction_Q(); 
  dotail();  
?>
