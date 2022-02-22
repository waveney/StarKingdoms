<?php

// Conflict code ...

function Devastate(&$H,&$W,&$Dists,$Numb=1) {
  // Chose a district - reduce it, update economy etc if zero consider projects, return is text to GM and Player
  
  $DTypes = Get_DistrictTypes();
  $Txt = 'Due to the conflict on ';
  switch ($H['ThingType']) {
  case 1: // Planet
    $P = Get_Planet($H['ThingId']);
    break;
  case 2: // Moon
    $P = Get_Moon($H['ThingId']);
    break;
  case 3: // Thing
    $P = Get_Thing($H['ThingId']);
    break;
  }
  $Txt .= $P['Name'] . "<br>";
    
  for($Hn = 1; $Hn <= $Numb; $Hn++) {
    $Dcount = 0;
    foreach ($Dists as $D) $Dcount += $D['Number'];
  
var_dump($Dcount);
    $Hit = rand(1,$Dcount);
    $DFind = 0;
    foreach ($Dists as $D) {
      $DFind += $D['Number'];
      if ($DFind >= $Hit) break;
    }
var_dump($D);
    $D['Number']--;
    Put_District($D);
    $Txt .= "A " . $DTypes[$D['Type']]['Name'] . " has be destroyed.<br>";
    if ($D['Number']<1) {
      $Txt .= "This causes bad things...<br>";  
    }
  }
  return $Txt;
}

?>
