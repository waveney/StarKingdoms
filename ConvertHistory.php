<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("PlayerLib.php");
  include_once("SystemLib.php");
  include_once("ProjLib.php");
  include_once("HomesLib.php");


//  function FixFudge() { // Tempcode called to fix thi9ngs
  global $db,$GAMEID,$GAME;
  
  dostaffhead("Fred");
  
  echo "<h1>Fudges...</h1>";
  
  $Things = Get_AllThings();
  foreach($Things as $T) {
    if ($T['History']) {
      $History = preg_split("/\n/",($T['History'] ?? ''));
      foreach($History as $H) {
        if (preg_match('/Turn#(\d*): (.*)/',$H,$mtch)) {
          $rec = ['ThingId'=>$T['id'],'TurnNum'=>$mtch[1],'Text'=>$mtch[2]];
        } else {
          $rec = ['ThingId'=>$T['id'],'TurnNum'=>-1,'Text'=>$H];          
        }
        if ($rec['Text']) Gen_Put('ThingHistory',$rec);
      }
    $T['History'] = '';
    Put_Thing($T);
    echo "Sorted " . $T['id'] . " " . $T['Name'] . "<br>";
    }
  }
   
  echo "<p>ALL Done - I hope...";

  dotail();
?>


