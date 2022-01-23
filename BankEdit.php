<?php
  include_once("sk.php");
  global $FACTION,$GAMEID,$USER,$GAME;
  include_once("GetPut.php");
  include_once("PlayerLib.php");
  global $PlayerState,$PlayerStates;

  A_Check('Player');

  $FACTION['LastActive'] = time();
  $Fid = $FACTION['id'];
  
  if (!Access('GM')) Put_Faction($FACTION);

  $Turn = $GAME['Turn'];
  $LookBack = 2;
  if (isset($_REQUEST['LookBack'])) $LookBack = $_REQUEST['LookBack'];
  if (isset($_REQUEST['Turn'])) $Turn = $_REQUEST['Turn'];
  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
      case 'Next Turn' :
        $Turn++;
        break;
      case 'Prev Turn' :
        $Turn = max(1,$Turn -1);
        break;
      case 'Current Turn' :
        $Turn = $GAME['Turn'];    
        break;
      case 'Setup' :
        $BankRec = ['FactionId'=>$Fid, 'Recipient'=>$_REQUEST['Recipient'], 'Amount'=>$_REQUEST['Amount'], 
                    'StartTurn'=> $_REQUEST['StartTurn'], 'EndTurn' => (empty( $_REQUEST['EndTurn'])? $_REQUEST['StartTurn'] : $_REQUEST['EndTurn']), 
                    'YourRef' => $_REQUEST['YourRef']];
                    
        Put_Banking($BankRec);
        $_REQUEST['Recipient'] = '';
        $_REQUEST['Amount'] = '';
        $_REQUEST['StartTurn'] = '';
        $_REQUEST['EndTurn'] = '';
        $_REQUEST['YourRef'] = '';
        break;
    }
  } else {

    foreach($_REQUEST as $Ri=>$R) {
//echo "Checking $Ri<br>";
      if (preg_match('/DELETE(\d*)/',$Ri,$mtch)) {
        $brecid = $mtch[1];
        $Bank = Get_Banking($brecid);
        if ($Bank['StartTurn'] >= $GAME['Turn']) {
          db_delete('Banking',$brecid);
        } else if ($Bank['EndTurn'] >= $GAME['Turn']) {
          $Bank['EndTurn'] = $GAME['Turn']-1;
          Put_Banking($Bank);
        }
        break;
      }
    }
  }
          
  
  dostaffhead("Banking Edit");
  
  $Factions = Get_Factions();
  $Facts = Get_FactionFactions($Fid);
  $FactList = [];
  $FactList[-1] = "Other";
  foreach ($Facts as $Fi=>$F) {
    $FactList[$Fi] = $Factions[$Fi]['Name'];
  }
  
  echo "<h1>" . $FACTION['Name'] . " - Banking Edit</h1>\n";
  echo "<form method=post action=BankEdit.php>\n";
  $B = Get_Banking($_REQUEST['id']);
  Register_Autoupdate("Banking",$B['id']);
  echo fm_hidden('id',$B['id']);
    echo "<table border>\n";
      echo "<tr><td>Payment to:<td>" . fm_select($FactList, $B, 'Recipient') . "<td>Select <b>Other</b> for RP actions";
      echo "<tr>" . fm_number('Amount',$B,'Amount');
      echo "<tr>" . fm_number('Start Turn', $B,'StartTurn'); 
      echo "<tr>" . fm_number('End Turn', $B,'EndTurn') . "<td>Leave blank for a one off payment";
      echo "<tr>" . fm_text('Your Reference',$B,'YourRef') . "<td>Will be seen by both parties";

      if ($Turn >= $GAME['Turn']) echo "<td><input type=submit formaction=Banking.php formmethod=post name=DELETE" . $B['id'] . " value=Cancel >";

  
  echo "</table></form>";
  echo "<h2><a href=Banking.php>Return to Banking</a></h2>";
  
//  Player_Page();
  dotail();  
?>
