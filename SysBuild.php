<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("SystemLib.php");

  A_Check('GM');

  // Experimental system Build code

  dostaffhead("Experiments for now..");

  $SunStats = ['Mass'=>2E30,'Radius'=>695700,'Luminosity'=>3.828E26,'Temperature'=>5772];
  $EarthStats = ['Mass'=>5.97e24,'Radius'=>6378,'OrbitalRadius'=>1.496e8,'Period'=>365.25,'Gravity'=>9.8];

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

    }
  }

  dotail();
?>
