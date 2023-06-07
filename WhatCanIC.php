<?php
  include_once("sk.php");
  global $FACTION,$GAMEID,$USER,$GAME;
  include_once("GetPut.php");
  include_once("PlayerLib.php");
  include_once("ThingLib.php");

  A_Check('Player');
  
  $GM = Access('GM');

  dostaffhead("What can I see");
  if (isset($FACTION)) {
    $Fid = $FACTION['id'];
    if ( $FACTION['TurnState'] > 2) Player_Page();
  } else if ($GM) {
    if (isset($_REQUEST['id'])) {
      $Fid = $_REQUEST['id'];
    } else {
      $Fid = 0;
      echo "<h2>Note you are here without a faction...</h2>\n";
    }
  } else {
    if ( $FACTION['TurnState'] > 2) Player_Page();
  }
  
  $File = "CouldC";
  if (isset($_REQUEST['C'])) $File = "Could" . $_REQUEST['C'];

  echo "<div class=floatright><h2>";
  if ($GAME['Turn'] <5 ) {
    echo "Older Turns =&gt;";
    if ($GAME['Turn']) for($turn=1; $turn <= $GAME['Turn']; $turn++) {
      echo ", <a href=WhatCanIC.php?Y=$turn>$turn</a>";
    }
  } else {
    echo "End of Older Turns =&gt; <div id=ExpandTurnsDots class=InLine><b onclick=ExpandTurns()>...</b></div><div id=HiddenTurns hidden>";
    for($turn=1; $turn <= $GAME['Turn']; $turn++) {
      if ($turn == ($GAME['Turn'] - 5)) echo "</div><div class=InLine>";
      if (file_exists("Turns/$GAMEID/$turn/CouldC$Fid.html")) echo " <a href=WhatCanIC.php?C=C&Turn=$turn>$turn</a>";
    }    
    echo "</div></h2>";
    if (file_exists("Turns/$GAMEID/" . ($GAME['Turn']-1) . "/CouldM$Fid.html")) {
      echo "<br>";
      echo "<h2>After Movement on Turn =&gt; <div id=ExpandTurnsDotsM class=InLine><b onclick=ExpandTurnsM()>...</b></div><div id=HiddenTurnsM hidden>";
      for($turn=1; $turn <= $GAME['Turn']; $turn++) {
        if ($turn == ($GAME['Turn'] - 5)) echo "</div><div class=InLine>";
        if (file_exists("Turns/$GAMEID/$turn/CouldM$Fid.html")) echo " <a href=WhatCanIC.php?C=M&Turn=$turn>$turn</a>";
      }  
   
      echo "</div></h2>";    
    }
  }
  echo "</div>";

    
  if (isset($_REQUEST['Turn'])) {
    $Turn = $_REQUEST['Turn'];
    $When = (($File == 'CouldC')? " at the end of " : " After Movement on ");
    echo "<h1>What Could I See $When Turn $Turn?</h1>";
    $html = file_get_contents("Turns/$GAMEID/$Turn/$File$Fid.html");
    $html = preg_replace('/Orders: .*?</',"<",$html);
    echo $html;
  } else {  
    echo "<h1>What Can I See Now?</h1>";
    echo WhatCanBeSeenBy($Fid);
  }
  
  dotail();  
?>
