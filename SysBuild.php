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
  $SunStats = ['Mass'=>2E30,'Radius'=>695700,'Luminosity'=>3.828E26,'Temperature'=>5772];
  $EarthStats = ['Mass'=>5.97e24,'Radius'=>6378,'OrbitalRadius'=>$AU,'Period'=>365.25,'Gravity'=>9.8];
  $StarClasses = ['K9'=>0.59,'K8'=>0.62,'K7'=>0.64,'K6'=>0.66,'K5'=>0.7,'K4'=>0.73,'K3'=>0.78,'K2'=>0.81,'K1'=>0.84,'K0'=>0.87,
    'G9'=>0.90,'G8'=>0.92,'G7'=>0.94,'G6'=>0.96,'G5'=>0.97,'G4'=>0.98,'G3'=>0.99,'G2'=>1.0,'G1'=>1.03,'G0'=>1.06,
    'F9'=>1.10,'F8'=>1.13,'F7'=>1.18,'F6'=>1.21,'F5'=>1.33,'F4'=>1.38,'F3'=>1.44,'F2'=>1.46,'F1'=>1.50,'F0'=>1.61,
  ];

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
        $Factor = 1.6+rand(1,100)/50;
 //       var_dump($d);exit;
        $PDists[] = $start;
        while (($d*=$Factor) < $N['OuterLim']) $PDists[] = $d;
        $d = $start;
        while (($d/=$Factor) > $N['InnerLim']) $PDists[] = $d;

        sort($PDists);
        foreach ($PDists as $i=>$d) {
          echo " " . RealWorld($PDists,$i,1);
          if ($d == $start) echo "<b>G</b>";
          if ($d >$N['HabInner'] && $d < $N['HabOuter']) echo "<b>H</b>";
        }

        echo " ( " . count($PDists) . " ) ";
        echo "<p>";

    }
  }

  dotail();
  /*
  Habitable zone (inner) :     R = sqrt(L) x 0.95
  Habitable zone (outer):      R = sqrt(L) x 1.37

  Planet system:
  Inner limit:        I = 0.1 x M
  Outer limit:      O = 40 x M
  Frost Line:       R = 4.85 x sqrt(L)
  */

