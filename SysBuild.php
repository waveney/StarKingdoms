<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("SystemLib.php");

  A_Check('GM');

  // Experimental system Build code

  dostaffhead("Experiments for now..");

  function frand($Bound=1) {
    return ($Bound*rand(0,1E10)/1e10);
  }

  $AU = 1.496e8;
  $G = 0.0000000000667431;
  $C = 299792458;
  $e = 0.98171334;
  $pi = pi();


  $SunStats = ['Mass'=>2E30,'Radius'=>695700,'Luminosity'=>3.828E26,'Temperature'=>5772];
  $EarthStats = ['Mass'=>5.97e24,'Radius'=>6378,'OrbitalRadius'=>$AU,'Period'=>365.25*24,'Gravity'=>9.8,'Heat'=>$SunStats['Luminosity']/(($AU*1000)^2)];
  $JupiterStats = ['Mass'=>1.8982e27,'Radius'=>69911 ];
  $StarClasses = ['K9'=>0.59,'K8'=>0.62,'K7'=>0.64,'K6'=>0.66,'K5'=>0.7,'K4'=>0.73,'K3'=>0.78,'K2'=>0.81,'K1'=>0.84,'K0'=>0.87,
    'G9'=>0.90,'G8'=>0.92,'G7'=>0.94,'G6'=>0.96,'G5'=>0.97,'G4'=>0.98,'G3'=>0.99,'G2'=>1.0,'G1'=>1.03,'G0'=>1.06,
    'F9'=>1.10,'F8'=>1.13,'F7'=>1.18,'F6'=>1.21,'F5'=>1.33,'F4'=>1.38,'F3'=>1.44,'F2'=>1.46,'F1'=>1.50,'F0'=>1.61,
  ];
  $PTD = Get_PlanetTypes();
  $PTN = NamesList($PTD);
  $NTP = array_flip($PTN);

  function NewStar($type=0) {
    $BaseMass1 = 0.45 + sqrt(rand(1,10000)/10000);
    $BaseMass2 = 0.45 + sqrt(rand(1,10000)/10000);
    $BaseMass3 = 0.45 + sqrt(rand(1,10000)/10000);
    return min($BaseMass1,$BaseMass2,$BaseMass3);
  }

  /// Start here

  if ($_REQUEST['ACTION']) {
    switch ($_REQUEST['ACTION']) {

      case 'X':
        $masses = [];
        for($i=0;$i<100;$i++) $masses[]= NewStar();
        sort($masses);

        $Classes = [0.8=>'K',1.1=>'G',1.4=>'F'];
        $Lastmass = 0;
        foreach ($Classes as $maxmass=>$Letter) {


        }
        echo implode(", ",$masses);
        break;

      case 'S':
        $Mass = NewStar() ;
  //      var_dump($Mass);
        foreach ($StarClasses as $Type=>$MaxMass) {
//var_dump($Type,$MaxMass);
          if ($Mass < $MaxMass) break;
        }

        $Stype = $Type . "V " . ['F'=>'Yellow-White','G'=>'Yellow','K'=>'Orange'][substr($Type,0,1)]. " Main Sequence";

        $L = $SunStats['Luminosity']*($Mass**3);
        $LineF = sqrt($L/$SunStats['Luminosity']);
        $N = ['Mass'=>$Mass*$SunStats['Mass'], 'Luminosity' => $L, 'Radius' =>$SunStats['Radius']*($Mass**0.74),
          'Temperature' => $SunStats['Temperature']*($Mass**0.505), 'Type'=>$Stype,
          'InnerLim'=>(0.1+frand(0.4))* $Mass*$AU, 'OuterLim'=>(30+frand(15))*$Mass*$AU,
          'FrostLine'=>4.85*$LineF*$AU, 'HabInner'=>0.70*$LineF*$AU, 'HabOuter'=>1.45*$LineF*$AU,
          'Type2'=>0,
        ];
//        $N['Roche'] = 0.78*(($N['Mass']/5400)**(1/3));

//        var_dump($Mass,$Mass, $N);
        echo "<table border>";
        echo "<tr>" . fm_number('Mass',$N,'Mass',1,"class=NotCSide") . "<td>Kg = " . RealWorld($N,'Mass');
        echo "<tr>" . fm_text('Type',$N,'Type',2,"class=NotCSide");
        echo "<tr>" . fm_number('Radius',$N,'Radius',1,"class=NotCSide") . "<td>Km = " . RealWorld($N,'Radius');
        echo "<tr>" . fm_number('Temperature',$N,'Temperature',1,"class=NotCSide") . "<td>K";
        echo "<tr>" . fm_number('Luminosity',$N,'Luminosity',1,"class=NotCSide") . "<td>W = " . RealWorld($N,'Luminosity');
        echo "<tr>" . fm_number('Inner Limit',$N,'InnerLim',1,"class=NotCSide") . "<td>Km = " . RealWorld($N,'InnerLim',1);
        echo "<tr>" . fm_number('Outer Limit',$N,'OuterLim',1,"class=NotCSide") . "<td>Km = " . RealWorld($N,'OuterLim',1);
        echo "<tr>" . fm_number('Frost Line',$N,'FrostLine',1,"class=NotCSide") . "<td>Km = " . RealWorld($N,'FrostLine',1);
        echo "<tr>" . fm_number('Habitable Zone inner',$N,'HabInner',1,"class=NotCSide") . "<td>Km = " . RealWorld($N,'HabInner',1);
        echo "<tr>" . fm_number('Habitable Zone outer',$N,'HabOuter',1,"class=NotCSide") . "<td>Km = " . RealWorld($N,'HabOuter',1);
//        echo "<tr>" . fm_number('Roche Limit',$N,'Roche',1,"class=NotCSide") . "<td>Km = " . RealWorld($N,'Roche',1);
        echo "</table>";


        echo "Now to do planets...<p>Planets at: "; // Classical distribution, todo = Hot Jupiter, double stars
        $d = $start = $N['FrostLine'] + $AU*$Mass*((rand(1,100)-50)/50);
        $Factor = 1.5+rand(1,100)/50;
 //       var_dump($d);exit;
        $PDists[] = $start;
        while (($d*=$Factor) < $N['OuterLim']) $PDists[] = $d;
        $d = $start;
        while (($d/=$Factor) > $N['InnerLim']) $PDists[] = $d;

        sort($PDists);
        $P =[];

        echo "<table border><tr><td>#<td>Type<td>Dist<td>Heat<td>Period<td>Mass<td>Density<td>Radius<td>Gravity<td>Moons";
        $PTN[-1] = "Habitable";

        foreach ($PDists as $i=>$d) {
//          $d = 108e6;
          $P[$i]['OrbitalRadius'] = $d;

          $P[$i]['Heat'] =   $heat = ($N['Luminosity']+($N['Type2']?$N['Luminosity2']:0))/(($d*1000)^2);

          $P[$i]['Period'] = 2*$pi*sqrt((($d*1000)**3)/($G*$N['Mass']))/(3600); // 3600 converts from seconds to hours
          // Work out type, mass, radius, density, gravity from type
          if ($d < $start) {
            if ($d< $N['HabInner']) {
              if ($P[$i]['Heat'] > (4* $EarthStats['Heat']) ) { // 4 * Earth
                $P[$i]['Type'] = $NTP['Molten'];
              } else if (frand(1)<0.25) {
                $P[$i]['Type'] = $NTP['Asteroid Belt'];
              } else {
                $P[$i]['Type'] = $NTP['Rock'];
              }
            } else if ($d > $N['HabOuter']) {
              if ($PDists[$i+1] >= $start) {
                $P[$i]['Type'] = $NTP['Asteroid Belt'];
              } else {
                $P[$i]['Type'] = $NTP['Rock'];
              }

            } else { // Hab Type after Mass etc
              $P[$i]['Type'] = $NTP['Habitable']; // Special
            }
          } else if ($d == $start || (($P[$i-1]['Type'] == $NTP['Gas Giant']) && (frand(1) < 0.5)) ){ // Gas and Ice giants
            $P[$i]['Type'] = $NTP['Gas Giant'];
          } else if ((($P[$i-1]['Type'] == $NTP['Gas Giant']) || ($P[$i-1]['Type'] = $NTP['Ice Giant'])) && (frand(1) < 0.6)) {
            $P[$i]['Type'] = $NTP['Ice Giant'];
          } else if (frand(1) < 0.1) {
            $P[$i]['Type'] = $NTP['Asteroid Belt'];
          } else {
            $P[$i]['Type'] = $NTP['Rock'];
          }

          /// Next work out masses - these depend on Planet Type

          $MasForm = $PTD[$P[$i]['Type']]['MassForm'];
          //var_dump($MasForm);
          $MFps = [];
          if (!(preg_match('/(\d*\.\d*) ?\- ?([0-9]*) ?M(.)/',$MasForm,$MFps))) {
            echo "Cant parse Mass formula for " .  $PTN[$P[$i]['Type']] ."<p>";
            break;
          }
          [$Junk,$MLow,$MHigh,$MBody] = $MFps;
  //        var_dump($MFps);
          $Drand = frand(1)*frand(1);
          $Ms = (($MHigh - $MLow)*$Drand+$MLow) * (($MBody=='E')?$EarthStats['Mass']:$JupiterStats['Mass']);
          if (($PTN[$P[$i]['Type']] == 'Gas Giant') && ($PTN[$P[$i-1]['Type']] == 'Gas Giant')) {
            $Ms = min($Ms, $P[$i-1]['Mass']*0.8);
            if ($Ms < $MLow) $P[$i]['Type'] = $NTP['Ice Giant'];
          }
          $P[$i]['Mass'] = $Ms;

          // Radius depend on type, mass & dist from star
          $DenForm = $PTD[$P[$i]['Type']]['DenForm'];
          if ($DenForm) {
            $DRng = [];
            if (!preg_match('/(\d*\.\d*) ?\- ?(\d*\.\d*)/',$DenForm,$DRng)) {
              echo "Cant parse Density formula for " .  $PTN[$P[$i]['Type']] ."<p>";
              break;
            }
            [$Junk,$DLow,$DHigh] = $DRng;
            $Dn = (($DHigh - $DLow)*$Drand+$DLow);
            $P[$i]['Density'] = $Dn; // in g/cc
            $Rad = $P[$i]['Radius'] = pow($Ms/(4000/3*$pi*$Dn),1.0/3.0)/1000;

            if ($Rad > $JupiterStats['Radius']*1.3) {
              $P[$i]['Radius'] = $Rad = ($JupiterStats['Radius'])*1.3  - frand(10000);
              $Den = $P[$i]['Density'] = $Ms/(4/3*$pi*($Rad*1000)**3)/1000;
//              var_dump($Ms,$Rad,$Den);
            }
            $P[$i]['Gravity'] = $G*$Ms/(($Rad*1000)**2);
            $P[$i]['Moons'] = round((($P[$i]['OrbitalRadius']/1.496e8)**2 * $P[$i]['Gravity'] * $PTD[$P[$i]['Type']]['MoonFactor'])/($P[$i]['Period']/100)+0.2);

            if ($PTN[$P[$i]['Type']] == 'Habitable') {
              $Em = $EarthStats['Mass'];
              if ($Ms < 0.3*$Em) {
                $Mindx = 0;
              } else if ($Ms < 0.6*$Em) {
                $Mindx = 1;
              } else if ($Ms < 1.3*$Em) {
                $Mindx = 2;
              } else if ($Ms < 1.6*$Em) {
                $Mindx = 3;
              } else {
                $Mindx = 4;
              }
              $Eh = $EarthStats['Heat'];
              $PH = $P[$i]['Heat'];
              if ($PH < 0.3*$Eh) {
                $Hindx = 0;
              } else if ($PH < 0.6*$Eh) {
                $Hindx = 1;
              } else if ($PH < 1.3*$Eh) {
                $Hindx = 2;
              } else if ($PH < 1.6*$Eh) {
                $Hindx = 3;
              } else {
                $Hindx = 4;
              }

              $HTypes = [
                ['Desolate','Desolate','Desert',  'Desolate','Desolate'],
                ['Desolate','Arctic',  'Temperate','Desert', 'Desolate'],
                ['Desolate','Arctic',  'Temperate','Desert', 'Desolate'],
                ['Desolate','Arctic',  'Temperate','Desert', 'Water'],
                ['Desolate','Arctic',  'Water',   'Water',   'Water'],
              ];

              $P[$i]['Type'] = $NTP[$HTypes[$Mindx][$Hindx]];
            }
 //           Mass = d . 4/3 pi r^3
 //           r = (mass/(4/3 pi d))^1/3
 // g/cc to kg/m^3  1000000/1000

          } else {
            $P[$i]['Moons'] = $P[$i]['Radius'] = $P[$i]['Density'] = $P[$i]['Gravity'] = 0;
          }

          // Density, Gravity, easy Moons follow

          // Hab types


//          $P[$i] = $EarthStats;
          echo "<tr><td>$i<td>" . $PTN[$P[$i]['Type']];
          echo "<td>" . RealWorld($P[$i],'OrbitalRadius',1);
          echo "<td>" . RealWorld($P[$i],'Heat',1);
          echo "<td>" . RealWorld($P[$i],'Period',1);
          echo "<td>" . RealWorld($P[$i],'Mass',1);
          if ($P[$i]['Density']) {
            echo "<td>" . RealWorld($P[$i],'Density',1);
            echo "<td>" . RealWorld($P[$i],'Radius',1);
            echo "<td>" . RealWorld($P[$i],'Gravity',1);
            echo "<td>" . $P[$i]['Moons'];
          } else {
            echo "<td><td><td><td>";
          }

//break;
          /*
          $P[$i]['Mass'] =
          $P[$i]['Radius'] =
          $P[$i]['Gravity'] =
          $P[$i]['Moons'] = (($P[$i]['OrbitalRadius']/1.496e8)**2 * $P[$i]['Gravity'] * $PTD[$P[$i]['Type']]['MoonFactor'])/($P[$i]['Period']/100);
          $P[$i]['DayLength'] =

          // P² = (4π²/GM) * a³.
          $P = [];
          $P['OrbitalRadius'] = $d;
          echo "<br>Orbit:" . RealWorld($PDists,$i,1);
          if ($d == $start) echo "<b>G</b>";
          if ($d >$N['HabInner'] && $d < $N['HabOuter']) echo "<b>H</b>";
          echo " Heat: " . RealHeat($N,$P);*/
        }

//        echo "<p>( " . count($PDists) . " ) ";
//        echo "<p>";
        echo "</table><p>";


    }
  }

//  var_dump($EarthStats,$N,$P);
  dotail();
  /*
  Habitable zone (inner) :     R = sqrt(L) x 0.95
  Habitable zone (outer):      R = sqrt(L) x 1.37

  Planet system:
  Inner limit:        I = 0.1 x M
  Outer limit:      O = 40 x M
  Frost Line:       R = 4.85 x sqrt(L)
  */

