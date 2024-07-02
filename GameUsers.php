<?php
  include_once 'sk.php';

  global $GAME,$GAMEID,$USERID,$USER,$Access_Type,$PlayerLevel ;

  A_Check('GM');

  dostaffhead("Add users to Game");

  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
      case 'Add to Game':
        if (empty($_REQUEST['Who'])) {
          echo "<h2>Please select someone to add...</h2>";
          break;
        }
        $new = ['GameId'=>$GAMEID,'PlayerId'=>$_REQUEST['Who'],'Type'=>$_REQUEST['Type']];
        Gen_Put('GamePlayers',$new);
        break;
      case 'Tidy':
        $GUsers = Gen_Get_Cond('GamePlayers',"GameId=$GAMEID");
        foreach ($GUsers as $i=>$Gu) if ($Gu['Type'] == 2) db_delete('GamePlayers',$i);
        break;
    }
  }

  echo "<h1>Games users for Game: $GAMEID - " . $GAME['Name'] . "</h1>";
  $GUsers = Gen_Get_Cond('GamePlayers',"GameId=$GAMEID");
  $People = Gen_Get_Cond('People', "AccessLevel>0");

  $ListP = [];
  $NeedTidy = 0;
  foreach ($People as $i=>$P) $ListP[$i] = $P['Name'];
  foreach ($GUsers as $Gu) if ($Gu['Type'] == 2) $NeedTidy = 1;

  echo "<form method=post action=GameUsers.php>";
  if ($GUsers) {
    Register_AutoUpdate('GamePlayers', 0);
    echo "<table border><tr><td>Who<td>Role";
    foreach ($GUsers as $id=>$Gu) {
      echo "<tr><td>" . $ListP[$Gu['PlayerId']] . "<td>" .
        fm_select($PlayerLevel,$Gu,'Type',0,'',"Type:$id");
    }
    echo "</table><p>\n";
    echo "<div hidden><input type=submit></div>\n";
  }

  $new = [];
  echo "<h2>Add to Game:</h2>";
  echo fm_select($ListP,$new,'Who');
  echo " as a: " . fm_select($PlayerLevel,$new,'Type');
  echo "<br><input type=submit name=ACTION value='Add to Game'><p>";
  if ($NeedTidy) echo "<input type=submit name=ACTION value='Tidy'><p>\n";
  echo "</form>";

  dotail();
