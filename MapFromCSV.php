<?php
  include_once("sk.php");
  include_once("GetPut.php");

  A_Check('GM');

  dohead("Map From CSV");

  global $db, $GAME;
  
  $File = fopen("SK Master Sheet.csv",'r');
  if (!$File) { echo "Data would not open"; dotail(); };
  $head = fgetcsv($File);
  $junk = fgetcsv($File);
  $junk = fgetcsv($File);
  $junk = fgetcsv($File);
  
  $Nodes = Get_Systems();
  
  $maxlinks = count($head);
  while ($line = fgetcsv($File)) {
    $sys = $line[0];
    if ($sys) {
      if (!isset($Nodes[$sys])) {
        $node = [ 'GameId' => $GAME['id'], 'Ref'=>$sys ];
        Put_System($node);
        $Nodes[$sys] = $node;
        echo "Made node $sys<br>";
      }
      
      $Sysid = $Nodes[$sys]['id'];
      
      $Links = Get_Links($Sysid);
      
      for ($i=1; $i<$maxlinks; $i++) {
        if (!$line[$i] || !is_numeric($head[$i])) continue;
        $dest = $line[$i];
        if ($Links) foreach ($Links as $L) {
          if ($L['System1Ref'] == $dest || $L['System2Ref'] == $dest) break 2;
        }
        
        $Lnk = ['System1Ref'=> $sys, 'System2Ref' => $dest, 'Level'=>$head[$i]];
        $lid = Put_Link($Lnk);
        $Lnk['id'] = $lid;
        
        $Links[] = $Lnk;
        echo "Inserted Link $sys to $dest <br>";
        }
      }
    }
  echo "<h1>All links inserted in data</h1>";      

  dotail();
?>
