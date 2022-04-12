<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("ProjLib.php");
  include_once("HomesLib.php");
 
  
  A_Check('GM');

  dostaffhead("Import Anomalies");

  global $db, $GAME,$BuildState;

  echo "<h1>Import Anomalies</h1>";
  
  $TTypes = Get_ThingTypes();
  $Systems = Get_SystemRefs();
  $RefSys = array_flip($Systems);
  $File = fopen('Anomaly.csv','r');
  
  $HeadRow = fgetcsv($File);
  $HeadNames = array_flip($HeadRow);
// var_dump($HeadNames);
  $Facts = ['Zabanian'=>1, 'Sedranian'=>6, 'Wombles'=>2, 'Fongari'=>3, 'Haxan'=>4, 'Ratfolk'=>5];
  
  while ($F = fgetcsv($File)) {
    if (!isset($F[0])) continue;
    
    if ($F[0] == 'y') continue;
    if ($F[0] == 'ss') continue;
    
    if (empty($F[$HeadNames['Name']])) continue;
    $Loc = $F[$HeadNames['Location']];
    if (!$Loc) continue;
    $Sid = (isset($RefSys[$Loc]) ?$RefSys[$Loc] :0 );
    if (!$Sid) {
      echo "Unknown system for ";
      var_dump($F);
      continue;
    }

    
    $An = ['SystemId'=>$Sid, 'Description'=>$F[$HeadNames['Narrative Seed']], 'ScanLevel'=>$F[$HeadNames['Scan Level Needed']],
           'AnomalyLevel'=>$F[$HeadNames['Anomaly Level']], 'Name'=> $F[$HeadNames['Name']],
           'Reward' => $F[$HeadNames['Reward']], 'Notes' => $F[$HeadNames['Notes']], 'Comments' => $F[$HeadNames['Comments']] ];

    Gen_Put('Anomalies',$An);
    
    foreach ($Facts as $FCol=>$Fid) {
      if (!empty($F[$HeadNames[$FCol]])) {
        $FAn = ['FactionId'=>$Fid, 'State'=>1, 'Notes'=>$F[$HeadNames[$FCol]], 'AnomolyId'=>$An['id']];
        Gen_Put('FactionAnomaly',$FAn);
      }
    }
    echo "Saved Anomaly for $Loc " . $An['Name'] . "<br>\n";
  }
  
  echo "All done\n";
  dotail();
?>
