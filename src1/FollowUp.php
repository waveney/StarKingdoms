<?php
  include_once("sk.php");
  global $FACTION,$GAMEID,$USER,$GAME;
  include_once("GetPut.php");
  include_once("PlayerLib.php");
  include_once("vendor/erusev/parsedown/Parsedown.php");
  global $PlayerState,$PlayerStates;

  A_Check('GM');

  dostaffhead("Follow Ups needed");


  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
      case 'Done' :  
        $fup = Gen_Get('FollowUp',$_REQUEST['i']);
        $fup['State'] = $USER['id'];
        Gen_Put('FollowUp',$fup);
        break;
    }
  }

  $TurnP = '';
  if (isset($_REQUEST['TurnP'])) $TurnP = "&TurnP=1";
  $Turn = $GAME['Turn'];
  if (isset($_REQUEST['Turn']))  $Turn = $_REQUEST['Turn'];
  
  $Ups = Gen_Get_Cond('FollowUp',"GameId=$GAMEID AND Turn=$Turn");
  $Facts = Get_Factions();

  echo "<button class='floatright FullD' onclick=\"($('.FullD').toggle())\">All Messages</button>" .
       "<button class='floatright FullD' hidden onclick=\"($('.FullD').toggle())\">Curent Messages</button> ";
  
  if (!$Ups) {
    echo "No follow ups needed";
    if ($TurnP) return;
    dotail();
  }

  $coln = $todo = 0;
  $People = Get_People();
  
  echo "<form method=post action=FollowUp.php>";
  echo "<div class=tablecont><table id=indextable border>\n";
  echo "<thead><tr>";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Index</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Faction</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Message</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Action</a>\n";
  echo "</thead><tbody>";
  foreach($Ups as $Up) {
    $i = $Up['id'];
    if ($Up['State'] == 0) $todo++;
    echo "<tr " . ($Up['State']?' class=FullD hidden':'') . "><td>$i<td>" . ($Up['FactionId']? $Facts[$Up['FactionId']]['Name'] : '') . 
        "<td>" . $Up['ActionNeeded'] . "<td>" . 
        ($Up['State']? "Done - " . $People[$Up['State']]['Name'] : "<a href=FollowUp.php?ACTION=Done&i=$i$TurnP>Done</a>");
  }
  echo "</table></div>";
  if (!$TurnP || $todo) dotail();

  if ($TurnP) echo "<h2><a href=TurnActions.php?ACTION=StageDone&Stage=CheckFollowUps&S=62>Back To Turn Processing</a></h2>";
  return;

?>
