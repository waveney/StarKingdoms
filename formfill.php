<?php
  include_once("sk.php");
  include_once("GetPut.php");
  $field = $_POST['F'];
  $Value = $_POST['V'];
  $id    = $_POST['I'];
  $type  = $_POST['D'];


var_dump($_POST);  
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
      $N= ['Type'=>$Value,'PlanetId'=>$mtch[1],'Number'=>1];
      if ($type == 'Planet') {
         $N['PlanetId'] =$mtch[1];
      } else {
         $N['MoonId'] =$mtch[1];      
      }
      echo 'FORCERELOAD54321:NOW' . Put_District($N);
      exit;

    case (preg_match('/District(\w*)-(\d*)/',$field,$mtch)?true:false):
      $N = Get_District($mtch[2]);
      if ($Value || $mtch[1] != 'Type') { 
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
  
  default:
    echo "Not setup $type for Auto Edit";
    break;
// Need to add stuff for control etc and names in faction_system as well    
  
    exit;  
     
  }
?>

