<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("SystemLib.php");

  A_Check('GM');

  dostaffhead("Edit Planet",["js/dropzone.js","css/dropzone.css" ]);

  global $db, $GAME;

//  var_dump($_REQUEST);
  if (isset($_REQUEST['P'])) {
    $Pid = $_REQUEST['P'];
  } else if (isset($_REQUEST['id'])) {
    $Pid = $_REQUEST['id'];
  } else if (isset($_REQUEST['i'])) {
    $Pid = $_REQUEST['i'];
  } else {

    echo "<h2>No Systems Requested</h2>";
    dotail();
  }

  $P = Get_Planet($Pid);
  $N = get_System($P['SystemId']);
  $Factions = Get_Factions();
  $Dists = Get_DistrictsP($Pid);
  $Mns = Get_Moons($Pid);
  $MC = count($Mns);
  $PTD = Get_PlanetTypes();
  $PTs = Get_PlanetTypeNames();
  $N2Ps = array_flip($PTs);



  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
    case 'Delete Planet' :
      db_delete('Planets',$Pid);

      Show_System($N,1);
      dotail();
      break;

    case 'RECALC':
      $P['Period'] = ((2*pi()*sqrt(($P['OrbitalRadius']*1000)**3/(($N['Mass']+ $N['Mass2'])*6.7e-11)))/3600);
      Put_Planet($P);
      break;

    case 'Delete Moons':
      db_delete_cond('Moons',"PlanetId=$Pid");
      break;

    case 'Add Editable Moon': // Habitable and Gas Giants ONLY coded for
      $heat = RealHeatValue($N,$P);
      if ($PTD[$P['Type']]['Hospitable']) {
//var_dump($P);

        $rad = 1000 + rand(0,max(3000,$P['Radius']/4));

        $PMass = pi()*4/3*(($P['Radius']*1000)**3)* 6000 *$P['Gravity']/9.8;
        $Orb = 3.8e5*($MC+1)*rand(1,20)/4;
        $Peri = (2*pi()*sqrt(($Orb*1000)**3/($PMass*6.7e-11))/3600);
        $MMass = pi()*4/3*(($rad*1000)**3)* 3000;
        $Grav = 6.7e-11 * $MMass /($rad*1000)**2;

        $heat *= $MMass/$PMass;

        if ($heat < 0.5) { $type = $N2Ps['Desolate']; }
        else if ($heat < 0.9) { $type = $N2Ps['Arctic']; }
        else if ($heat < 1.5) { $type = $N2Ps['Temperate']; }
        else if ($heat < 2.5) { $type = $N2Ps['Desert']; }
        else { $type = $N2Ps['Desolate']; };
        $P['Minerals'] = Minerals4Planet();
//echo "<br>PMass: $PMass MMass $MMass<p>";

      } else if ($PTD[$P['Type']]['Name'] == 'Gas Giant') {
        $rad = rand(1500,4000);
        $PMass = pi()*4/3*($P['Radius']*1000)**3 * 1300;
        $Orb = 1e6*($MC+1)*rand(1,20)/4;
        $Peri = (2*pi()*sqrt(($Orb*1000)**3/($PMass*6.7e-11))/3600);
        $MMass = pi()*4/3*($rad*1000)**3* 3000;
        $Grav = 6.7e-11 * $MMass /($rad*1000)**2;

//        $heat *= $MMass/$PMass;

        if ($heat < 0.5) { $type = $N2Ps['Desolate']; }
        else if ($heat < 0.9) { $type = $N2Ps['Arctic']; }
        else if ($heat < 1.5) { $type = $N2Ps['Temperate']; }
        else if ($heat < 2.5) { $type = $N2Ps['Desert']; }
        else { $type = $N2Ps['Desolate']; };
        $P['Minerals'] = Minerals4Planet();
//echo "<br>PMass: $PMass MMass $MMass<p>";

      } else {
        echo "Not allowed sorry<p>";
        break;
      }

      $M = ['PlanetId'=>$Pid, 'Name'=> ($P['Name'] . " moon " . number2roman($MC+1)), 'Radius' => $rad, 'OrbitalRadius' => $Orb, 'Period' => $Peri,
            'Gravity'=> $Grav, 'Type' => $type, 'SystemId' => $P['SystemId']];
      Put_Moon($M);
      $P['Moons']++;
      Put_Planet($P);
      break;

    case 'Tidy Districts':
      $Ds = Get_DistrictsP($Pid,1);
      foreach ($Ds as $D) {
        if ($D['Number'] == 0) {
          db_delete('Districts',$D['id']);
        }
      };
      echo "<h2>Districts Tidied Up</h2>";
      break;


    default:
      break;
    }
  }
  Show_Planet($P,1,1);

  dotail();
?>
