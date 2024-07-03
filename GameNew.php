<?php
  include_once 'sk.php';

  A_Check('GM');

  dostaffhead("Create new game");
  global $USERID,$GAME,$GAMEID;

  $AllGames = Gen_Get_All('Games');
  $ListGames = [];
  foreach ($AllGames as $i=>$G) $ListGames[$i]= "$i : " . $G['Name'];

  $but = 0;
  if (isset($_REQUEST['ACTION']))
    switch ($_REQUEST['ACTION']) {
      case 'Create':
        // Does name exist?
        if (empty($_REQUEST['Name'])) {
          echo "<h2 class=Err>Please give your game a name</h2>";
          break;
        }
        foreach ($AllGames as $i=>$G) if ($G['Name'] == $_REQUEST['Name']) {
          echo "<h2>There is already a game called " . $G['Name'] . " (Press Force to force)</h2>";
          $but = 1;
          break;
        }  // Deliberate Fall through

      case 'Force':
        $CopyId = ($_REQUEST['CopyId']??0);
        $Feats = '';
        if ($CopyId) $Feats = $AllGames[$CopyId]['Features'];
        $ng = ['Status'=>0,'Name' =>$_REQUEST['Name']];
        $Gid = Put_Game($ng);
        if (1 || !Access('God')) {
          $gu = ['GameId'=>$Gid,'PlayerId'=>$USERID,'Type'=>1];
          Gen_Put('GameUsers',$gu);
        }
        setcookie('SKG',$Gid);
        $GAME = Get_Game($Gid);
        $ErrorMessage = "Game $Gid : " . $ng['Name'] . " has been setup";
        include ("Staff.php"); // No return
        break;  // TODO
  }

  $game = $_REQUEST;
  echo "<form method=post>";
  echo "<table border>";
  echo "<tr>" . fm_text('Name of Game', $game, 'Name');
  echo "<tr><td>Copy settings from<td>" . fm_select($ListGames,$game,'CopyId');
  echo "</table><p>";

  echo "<input type=submit name=ACTION value=Create>";
  if ($but) echo "<input type=submit name=ACTION value=Force>";
  echo "</form>";

  dotail();
?>