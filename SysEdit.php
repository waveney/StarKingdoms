<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("SystemLib.php");
  
  A_Check('GM');

  dostaffhead("Edit System");

  global $db, $GAME;
  
  function PTrim(&$N,&$things,$what,$saveas=0) {
    if (!isset($things[$what])) return;
    if (!$saveas) $saveas=$what;
    $orig = $things[$what];
    if (preg_match('/([\d\.]*) x 10<sup>(\d*)</',$orig,$mtch)) {
//var_dump($mtch);
      $num = $mtch[1] * 10**$mtch[2];
    } else if (preg_match('/([\d\.]*?) /',$orig,$mtch)) {
      $num = $mtch[1];
    } else {
      $N[$saveas] = $orig;
      return;
    }
    $N[$saveas] = $num+0;
  }

  function Auto_Populate(&$N) {
    $sid = $N['id'];
    $seed = time();
  
    $Html_Req = "https://donjon.bin.sh/scifi/system/index.cgi?seed=$seed&binary=Random&n-planets=Few&cmd=Create";
    if ((($seed%3 == 0)) || (isset($N['Category']) && $N['Category'] )) $Html_Req .= "&force_terran=1";
    
//    echo "$Html_Req<p>";
    $html = file_get_contents($Html_Req);
    
// echo htmlspecialchars($html); // exit;
    
    $tabrows = explode("<tr", $html);
    array_shift($tabrows);
    array_pop($tabrows);
//echo "<P>Rows:" . count($tabrows) . "<p>";
    
    $tabrows[] = ' class="section"';
//var_dump($tabrows);
//exit;

    $PTs = Get_PlanetTypeNames();
    $N2Ps = array_flip($PTs);

//var_dump($N2Ps);exit;    
    $state = 0;
    foreach ($tabrows as $tab) {

//echo "<br>Evaluating " . htmlspecialchars($tab) . "<br>";
 
      preg_match_all('/\<td.*?\>(.*?)\<\/td\>/',$tab,$tdsarray);
      $tds = (isset($tdsarray[1])?$tdsarray[1]:[]) ;

/*
if ($tds) {
echo "tds are:";
  foreach ($tds as $t) echo htmlspecialchars($t) . "<br>";
} else {
echo "No tds<p>";

}*/

      switch (substr($tab,8,5)) {
      
      case 'secti':
        if ($state ==2) {

//echo "saving "; var_dump($things);

          if ($things['Title'] == 'Star Data') {
            $N['Type'] = (isset($things['Type'])?$things['Type']:'Unknown Star');
            if (isset($things['Image'])) $N['Image'] = $things['Image'];
            PTrim($N,$things,'Radius');
            PTrim($N,$things,'Mass');
            PTrim($N,$things,'Temperature');
            PTrim($N,$things,'Luminosity');
          } else if (strstr($things['Title'],'Companion')) {
            $N['Type2'] = (isset($things['Type'])?$things['Type']:'Unknown Star');
            if (isset($things['Image'])) $N['Image2'] = $things['Image'];
            PTrim($N,$things,'Radius','Radius2');
            PTrim($N,$things,'Mass','Mass2');
            PTrim($N,$things,'Temperature','Temperature2');
            PTrim($N,$things,'Luminosity','Luminosity2');
            PTrim($N,$things,'Distance');
            PTrim($N,$things,'Period'); // Not always calculated by donjon
            if (!isset($things['Period'])) {
              $N['Period'] = ((2*pi()*sqrt(($N['Distance']*1000)^3/($N['Mass']*6.7e-11)))/60);
            }
          } else { // Is a Planet
            $P['Name'] = $things['Title'];
            $PlanTypeXlate = ['Terrestrial World'=>'Temperate Planet', 'Rock Planet'=>'Rock Planet', 'Gas Giant'=>'Gas Giant', 'Asteroid Belt'=>'Asteroid Belt', 
                              'Neptunian Planet'=>'Ice Giant', 'Jovian Planet'=>'Gas Giant', 'Chthonian Planet' => 'Molten Planet', 'Ice Planet'=>'Ice Planet'];
          
            $pt = (isset($PlanTypeXlate[$things['Type']])? $PlanTypeXlate[$things['Type']] : 'Other Planet');
            if (isset($N2Ps[$pt])) {            
              $P['Type'] = $N2Ps[$pt];
              if ($pt == 'Other Planet') $P['Name'] = $things['Type'];
            } else {
              echo "Unrecognised planet type $pt - using Other";
              $P['Type'] = $N2Ps['Other Planet'];
              $P['Name'] = $things['Type'];
            }
            if (isset($things['Image'])) $P['Image'] = $things['Image'];
            PTrim($P,$things,'Orbital Radius','OrbitalRadius');
            PTrim($P,$things,'Period');
            PTrim($P,$things,'Radius','Radius');
            PTrim($P,$things,'Gravity');
            $P['Name'] = $things['Title'];
            $P['SystemId'] = $N['id'];
            $P['Description'] = $things['Type'];

            if ($things['Type'] == 'Terrestrial World') {
              $heat = RealHeatValue($N,$P);
//var_dump($heat);echo "<p>";
              if ($heat < 0.5) { $P['Type'] = $N2Ps['Desolate Planet']; }
              else if ($heat < 0.9) { $P['Type'] = $N2Ps['Arctic Planet']; }
              else if ($heat < 1.5) { $P['Type'] = $N2Ps['Temperate Planet']; }
              else if ($heat < 2.5) { $P['Type'] = $N2Ps['Desert Planet']; }
              else { $P['Type'] = $N2Ps['Desolate Planet']; };
            }

            Put_Planet($P);
                                    
            $P = $things = [];
                  
          }
        } // Save 
        $things = [];
        if (isset($tds[0])) {
          if (preg_match('/img src="(.*?)"/',$tds[0],$img)) {
            $things['Image'] = "https://donjon.bin.sh" . $img[1];
          }
        }
        if (isset($tds[2])) {
          if (preg_match('/>(..*?)</',$tds[2],$tight)) {        
            $things['Title'] = $tight[1];
          } else {
            $things['Title'] = $tds[2];
          }
        } else {
          $things['Title'] = 'Unknown';         
        }
        $state = 1;
        break;
        
      default:
        $state = 2;
        if (isset($tds[1])) $things[$tds[0]] = $tds[1];
        break;
      }
   
    }
//  var_dump($N);
  Put_System($N);
//  echo "System updated<p>";
  }

// START HERE
//  var_dump($_REQUEST);
  if (isset($_REQUEST['N'])) {
    $Sysid = $_REQUEST['N'];
  } else if (isset($_REQUEST['id'])) {
    $Sysid = $_REQUEST['id'];
  } else { 

    echo "<h2>No Systems Requested</h2>";
    dotail();
  }

  $N = Get_System($Sysid);
  $Factions = Get_Factions();
  
  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
    case 'Add Planet' :
      $plan = ['SystemId'=>$Sysid];
      Put_Planet($plan);
      break;
      
    case 'Auto Populate' :
      Auto_Populate($N);
      break;
      
    case 'Delete Planets' :
      db_delete_cond('Planets',"SystemId=$Sysid");
      break;
      
    default: 
      break;
    }
  }
  
  
  Show_System($N,1);
  
  dotail();
?>
