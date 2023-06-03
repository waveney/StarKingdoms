<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("PlayerLib.php");
  include_once("SystemLib.php");
  include_once("ProjLib.php");
  include_once("HomesLib.php");
  include_once("BattleLib.php");
  include_once("TurnTools.php");
  include_once("vendor/erusev/parsedown/Parsedown.php");
  
  global $LinkStates,$GAME;
  
  A_Check('GM');

  $LinkState = array_flip($LinkStates);            
  dostaffhead("Special Turn Processing");
  echo "<h1>Special Turn Actions - Only needed once a Blue Moon</h1>\n";

  
  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
    case 'ExplodeLink':
      echo "<form method=post action=TurnSpecials.php?ACTION=ExplodeLink2>";
      echo fm_number('Link id #:',$_REQUEST,'LinkId');
      echo "</form>";
      dotail();
      
    case 'ExplodeLink2':
      $Lid = $_REQUEST['LinkId'];
      $L = Get_Link($Lid);

      $SR1 = Get_SystemR($L['System1Ref']);      
      $SR2 = Get_SystemR($L['System2Ref']);           

      $DamageDice = (abs($L['Level'])+1)*2;
      GMLog("<span class=Red>LINK EXPLOSION </span> on link $Lid from " . $L['System1Ref'] . " to " . $L['System2Ref'] );        
      GMLog("Do ($DamageDice D10) x 10 to everything (Including Outposts, Space Stations etc) in " .
            "<a href=Meetings.php?ACTION=Check&R=" . $L['System1Ref'] . ">" . $L['System1Ref'] . "</a> And " .
            "<a href=Meetings.php?ACTION=Check&R=" . $L['System2Ref'] . ">" . $L['System2Ref'] . "</a>");
            // Emergency lockdown both ends

      db_delete('Links',$L['id']);                        
      SetAllLinks($L['System1Ref'], $SR1['id'],$LinkState['In Safe Mode']);
      SetAllLinks($L['System2Ref'], $SR2['id'],$LinkState['In Safe Mode']);

      Report_Others(0, $SR1['id'], 31, "Link #$Lid Exploded.  All other links in " . $L['System1Ref'] . " have been put in Safe Mode");
      Report_Others(0, $SR2['id'], 31, "Link #$Lid Exploded.  All other links in " . $L['System2Ref'] . " have been put in Safe Mode");

            // Remove the link!
            
      $L['GameId'] = - $L['GameId'];
      Put_Link($L);
      echo "Link Exploded<p>";
    }
  }
  
  echo "</form>";
  echo "<h2><a href=TurnSpecials.php?ACTION=ExplodeLink>Explode Link</a>, ";
  
  echo "</h2>";
  echo "<h2><a href=TurnActions.php>Back to Turn Processing</a></h2>";


?>
