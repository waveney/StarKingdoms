<?php
  include_once("sk.php");
  include_once("GetPut.php");
  $field = $_POST['F'];
  $Value = $_POST['V'];
  $id    = $_POST['I'];
  $type  = $_POST['D'];
  
  global $GAME,$GAMEID;

//var_dump($_POST);  
// Special returns @x@ changes id to x, #x# sets feild to x, !x! important error message
  switch ($type) {
  case 'System' :
    $N = Get_System($id);
    $N[$field] = $Value;
    echo Put_System($N);
    exit;
    
  case 'Planet' :
  case 'Moon' :
    switch ($field) {
    case (preg_match('/DistrictTypeAdd-(.*)/',$field,$mtch)?true:false):
      $Ds = ($type == 'Planet')?Get_DistrictsP($id):Get_DistrictsM($id);
      foreach ($Ds as $D) {
        if ($D['Type'] == $Value) {
          $D['Number']++;
          echo 'FORCERELOAD54321:NOW' . Put_District($D);
          exit;
        }
      }
      $N= ['Type'=>$Value,'HostId'=>$mtch[1],'Number'=>1];
      $N['HostType'] = ($type == 'Planet'?1:2);
      echo 'FORCERELOAD54321:NOW' . Put_District($N);
      exit;

    case (preg_match('/District(\w*)-(\d*)/',$field,$mtch)?true:false):
      $N = Get_District($mtch[2]);
//var_dump($N,$Value,$mtch);
      if ($Value && $mtch[1] != 'Type') { 
        $N[$mtch[1]] = $Value;     
        echo Put_District($N);
      } else { 
        echo 'FORCERELOAD54321:NOW' . db_delete('Districts',$mtch[2]);
      }
      exit;
    
    default:
      if ($type == 'Planet') {
        $N = Get_Planet($id);
        $N[$field] = $Value;
        echo Put_Planet($N);
      } else {
        $N = Get_Moon($id);
        $N[$field] = $Value;
        echo Put_Moon($N);  
      }
      exit;
    }
    
  case 'Link' :
    $N = Get_Link($id);
    $N[$field] = $Value;
    echo Put_Link($N);
    exit;
  
  case 'Tech' :
    $N = Get_Tech($id);
    $N[$field] = $Value;
    echo Put_Tech($N);
    exit;
  
  case 'Thing' : // Will need some complex handling for districts, modules and subtype
    switch ($field) {
    case (preg_match('/DistrictTypeAdd-(.*)/',$field,$mtch)?true:false):
      $Ds = Get_DistrictsT($id);
      foreach ($Ds as $D) {
        if ($D['Type'] == $Value) {
          $D['Number']++;
          echo 'FORCERELOAD54321:NOW' . Put_District($D);
          exit;
        }
      }
      $N= ['Type'=>$Value,'HostId'=>$mtch[1],'Number'=>1,'HostType' => 3, 'GameId' => $GAMEID ];
      echo 'FORCELOADCHANGE54321:NOW' . Put_District($N);
      exit;

    case (preg_match('/District(\w*)-(\d*)/',$field,$mtch)?true:false):
      $N = Get_District($mtch[2]);
      if ($Value && $mtch[1] != 'Type') { 
        $N[$mtch[1]] = $Value;     
        echo 'FORCERELOAD54321:NOW' . Put_District($N);
      } else { 
        echo 'FORCERELOAD54321:NOW' . db_delete('Districts',$mtch[2]);
      }
      exit;
    case (preg_match('/ModuleTypeAdd-(.*)/',$field,$mtch)?true:false): // GM type add module
      $Ds = Get_Modules($id);
      foreach ($Ds as $D) {
        if ($D['Type'] == $Value) {
          $D['Number']++;
          echo 'FORCERELOAD54321:NOW' . Put_Module($D);
          exit;
        }
      }
      if ($Value == 3) {
        $T = Get_Thing($id);
        $T['HasDeepSpace'] = 1;
        Put_Thing($T);
      }
      $N= ['Type'=>$Value,'ThingId'=>$mtch[1],'Number'=>1];
      echo 'FORCELOADCHANGE54321:NOW' . Put_Module($N);
      exit;
    case (preg_match('/ModuleAddType-(.*)/',$field,$mtch)?true:false): // Player type add module
      if ($Value == 3) {
        $T = Get_Thing($id);
        $T['HasDeepSpace'] = 1;
        Put_Thing($T);
      }
      $Ns = Get_ModulesType($id,$mtch[1]);
//var_dump($Ns);
      if ($Ns) {
        $N = $Ns[0];
        $N['Number'] = $Value;
      } else {
        $N= ['Type'=>$mtch[1],'ThingId'=>$id, 'Number'=>$Value];
      }
      //echo 'FORCELOADCHANGE54321:NOW' . 
      echo Put_Module($N);
//      echo "REPLACE_ID_WITH:ModuleNumber-$mid ";
      exit;

    case (preg_match('/Module(\w*)-(\d*)/',$field,$mtch)?true:false):
//echo "In right place "; var_dump($mtch);
      $N = Get_Module($mtch[2]);
      if ($Value && ($mtch[1] == 'Type')) { 
        $N[$mtch[1]] = $Value;   
        echo 'FORCERELOAD54321:NOW' . Put_Module($N);
      } else if ($mtch[1] == 'Remove') { 
       if ($N['Type'] == 3) {
          $T = Get_Thing($id);
          $T['HasDeepSpace'] = 0;
          Put_Thing($T);
        }
//echo "About to delete: "; var_dump( $mtch);
      //  echo 'FORCELOADCHANGE54321:NOW' . 
        echo db_delete('Modules',$mtch[2]);
      } else { 
        $N[$mtch[1]] = $Value;     
//var_dump($N);
        //echo 'FORCELOADCHANGE54321:NOW' . 
        echo Put_Module($N);
      }
      exit;

    }

    $N = Get_Thing($id);
    if ($field == 'Instruction' && $N['Instruction'] != $Value) {
      $N['Progress'] = 0;
    }  
    
    $N[$field] = $Value;
    if ($field == 'LinkId') {
      $L = Get_Link($Value);
      $SYS1 = Get_SystemR($L['System1Ref']);
      $SYS2 = Get_SystemR($L['System2Ref']);      
      if ($N['SystemId'] == $SYS1['id']) {
        $N['NewSystemId'] = $SYS2['id'];
        $FSN = $SYS2;
      } else {
        $N['NewSystemId'] = $SYS1['id'];
        $FSN = $SYS1;
      }
      $N['NewLocation'] = 1;

      $NearNeb = $N['Nebulae'];      
      $Fid = $N['Whose'];
      $Known = 1;
      // Handle link knowledge - KLUDGE 
      $FL = Get_FactionLinkFL($Fid,$L['id']);
      $FarNeb = $FSN['Nebulae'];
      $FS = Get_FactionSystemFS($Fid,$FSN['id']);

      if (isset($FL['Known']) && $FL['Known']) {
      } else if ($NearNeb == 0) {
          if (isset($FS['id'])) {
            if ($FarNeb != 0 && $FS['NebScanned'] < $FarWeb) {
              $Known = 0;
            }
          } else {
              $Known = 0;
          }
        } else if ($NS['NebScanned'] >= $NearNeb) { // In a Neb...
          if (!isset($FS['id'])) {
              $Known = 0;
          }
        } else { 
          echo "Error!"; exit;// Can't see that link
        }
      $N['TargetKnown'] = $Known; 
    } 
    if ($field == 'Type' || $field == 'Instruction' ) { // || $field == 'Level') {
      echo 'FORCELOADCHANGE54321:NOW';
    } else if ( $field == 'LinkId' ) {  //|| ($field == 'Name' && strlen($Value) < 2)) {
      echo 'FORCERELOAD54321:NOW';
    }
    echo Put_Thing($N);
    exit;
  
  case 'FactTech' :
    if (preg_match('/Tech(\d*)/',$field,$mtch)?true:false) {
      $N = Get_Faction_TechFT($id,$mtch[1]);
      $N['Level'] = $Value;
      echo Put_Faction_Tech($N);
      exit;
    } else if (preg_match('/Know(\d*)/',$field,$mtch)?true:false) {
      $N = Get_Faction_TechFT($id,$mtch[1]);
      if ($Value) {
        $N['Level'] = 0;
        echo Put_Faction_Tech($N);
      } else if ($N['id']) {
        db_delete('FactionTechs',$mtch[1]);
      }
      exit;
    } 
    echo "Unknown... $field";
    exit;
   
  
  case 'Faction' :
    if ($field == 'LastActive') {
      include_once("DateTime.php");
      $Value = Date_BestGuess($Value);
    }
    $N = Get_Faction($id);
    $N[$field] = $Value;
    echo Put_Faction($N);
    exit;
  
    
  case 'District' :
    $N = Get_District($id);
    $N[$field] = $Value;
    echo Put_District($N);
    exit;
  
  case 'Game' :
    $N = Get_Game($id);
    $N[$field] = $Value;
    echo Put_Game($N);
    exit;
  
  case 'Turn' :
    $N = Get_Turn($id);
    $N[$field] = ($field == 'Progress')?hexdec($Value):$Value;
    echo Put_Turn($N);
    exit;
    
  case 'Projects' :
    // id is Fid
    if (preg_match('/Rush(\d*):(\d*)/',$field,$mtch)?true:false) {
      $Turn = $mtch[1];
      $Proj = $mtch[2];
      $N = Get_ProjectTurnPT($Proj,$Turn);
      $N['Rush'] = $Value;
//       echo 'FORCELOADCHANGE54321:NOW' . 
      Put_ProjectTurn($N);
    }
    exit;
    
   case 'Project' : // Note this is for ProjEdit, see above for per turn rushing
    $N = Get_Project($id);
    $N[$field] = $Value;
    echo Put_Project($N);
    exit;
  
   case 'ProjectHome' : // Note this is for ProjEdit, see above for per turn rushing
    $N = Get_ProjectHome($id);
    $N[$field] = $Value;
    echo Put_ProjectHome($N);
    exit;
 

  case 'FFaction' :
    if (preg_match('/Know(\d*):(\d*)/',$field,$mtch)?true:false) {
    
      if ($Value) {
        $FF = ['FactionId1'=> $mtch[1], 'FactionId2'=> $mtch[2]];
        return Put_FactionFaction($FF); 
      } else {
        return db_delete_cond('FactionFaction', "FactionId1=" . $mtch[1] . " AND FactionId2=" . $mtch[2]);
      }
    }
    exit;

  case 'FactionFaction' :
    if (preg_match('/Set:(\d*):(\d*)/',$field,$mtch)?true:false) {
      $N = Get_FactionFaction($mtch[2]);
      if ($mtch[1] == 0) {
        $N['Props'] = ($N['Props']& 0xfff0) + $Value;
      } else {
        $N['Props'] = ($N['Props']& 0xff0f) + $Value<<4;      
      }
      echo Put_FactionFaction($N);
    }
    exit;
    
  case 'Banking':
    $N = Get_Banking($id);
    $N[$field] = $Value;
    echo Put_Banking($N);
    exit;
  
 
   case 'FactionTurn':
    $N = Get_FactionTurn($id);
    $N[$field] = $Value;
    echo Put_FactionTurn($N);
    exit;
  
   case 'ProjectHome':
    $N = Get_ProjectHome($id);
    $N[$field] = $Value;
    echo Put_ProjectHome($N);
    exit;
  
   case 'Worlds':
//var_dump($_REQUEST);
    if ((preg_match('/(\w*):(\d*):(\d*)/',$field,$mtch)?true:false)) {
      switch ($mtch[2]) {
      case 1: // Planet
        $N = Get_Planet($mtch[3]);
        $N[$mtch[1]] = $Value;
        return Put_Planet($N);
      case 2: // Moon
        $N = Get_Moon($mtch[3]);
        $N[$mtch[1]] = $Value;
        return Put_Moon($N);
      case 3: // Planet
        $N = Get_Thing($mtch[3]);
        $N[$mtch[1]] = $Value;
        return Put_Thing($N);
      case 99: // Worlds
        $N = Get_World($mtch[3]);
        $N[$mtch[1]] = $Value;
        return Put_World($N);

      }
      echo "ERROR";
      var_dump($_REQUEST);
      exit;
    }
    $N = Get_World($id);
    $N[$field] = $Value;
    echo Put_World($N);
    exit;
  
  case 'ScansDue':
//       var_dump($_REQUEST);
    if ((preg_match('/(\w*):(\d*)/',$field,$mtch)?true:false)) {
      $N = Gen_Get('ScansDue',$mtch[2]);
      $N[$mtch[1]] = $Value;
//var_dump($N);
      echo Gen_Put('ScansDue',$N);
    }
    exit;

  default:
    echo "Not setup $type for Auto Edit";
    break;
// Need to add stuff for control etc and names in faction_system as well    
  
    exit;  
     
  }
?>

