<?php

// Conflict code ...

function Devastate(&$H,&$W,&$Dists,&$Offs,$Numb=1) {
  // Chose a district - reduce it, update economy etc if zero consider projects, return is text to GM and Player
  $TTypes = Get_ThingTypes();
  $TNames = ListNames($TTypes);
  $Something = $TNames['Something'];

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

  $Walls = Get_Things_Cond(0,"Type=$Something AND (Name='Shield Wall' OR Name='Sea Wall')");
  if ($Walls) {
    $Wall = array_shift($Walls);
    Thing_Delete($Wall['id']);
    $Txt .= " the " . $Wall['Name'] . " has been destroyed by Devastation (instead of a Distict or Office)";
    return $Txt;
  }

  for($Hn = 1; $Hn <= $Numb; $Hn++) {
    $Dcount = $OCount = count($Offs);
    foreach ($Dists as $D) $Dcount += $D['Number'];

    if ($Dcount) {
//var_dump($Dcount);
      $Hit = rand(1,$Dcount);
      if ($Hit <= $OCount) {
        $Ofind = 0;
        foreach ($Offs as $O) {
          if (++$Ofind == $Hit) {
            $O['GameId'] = -$O['GameId'];
            $O['World'] = -$W['id'];
            Gen_Put('Offices',$O);
            $Org = Gen_Get('Organisations',$O['Organisation']);
            $Txt .= "The office of " . $Org['Name'] . " has been destroyed because of the devastation.<br>";
            if (--$Org['OfficeCount'] < 1) $Txt .= "The organisation is now inactive.<p>";
            Gen_Put('Organisations',$Org);
            continue;
          }
        }
      }
      $DFind = $OCount;
      foreach ($Dists as $D) {
        $DFind += $D['Number'];
        if ($DFind >= $Hit) break;
      }
//var_dump($D);
      $D['Number']--;
      Put_District($D);
      $Txt .= "A " . $DTypes[$D['Type']]['Name'] . " has been destroyed because of the devastation.<br>";
      if ($D['Number']<1) {
        $Txt .= "This causes bad things...<br>";
      }
    } else {
      $Txt .= "There are no districts or offices left to be destroyed.<br>";
      break;
    }
  }
  return $Txt;
}

?>
