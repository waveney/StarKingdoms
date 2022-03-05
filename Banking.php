<?php
  include_once("sk.php");
  global $FACTION,$GAMEID,$USER,$GAME;
  include_once("GetPut.php");
  include_once("PlayerLib.php");
  global $PlayerState,$PlayerStates,$Currencies;

  A_Check('Player');

  $FACTION['LastActive'] = time();
  $Fid = $FACTION['id'];

  CheckFaction('Banking',$Fid);

  $GM = Access('GM');
  if (!$GM) Put_Faction($FACTION);

  $Factions = Get_Factions();
  $Facts = Get_FactionFactions($Fid);

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
      case 'Transfer on Turn' :
        $BankRec = ['FactionId'=>$Fid, 'Recipient'=>$_REQUEST['Recipient'], 'Amount'=>$_REQUEST['Amount'], 
                    'StartTurn'=> $_REQUEST['StartTurn'], 'EndTurn' => (empty( $_REQUEST['EndTurn'])? $_REQUEST['StartTurn'] : $_REQUEST['EndTurn']), 
                    'YourRef' => $_REQUEST['YourRef'],'What'=>(isset($_REQUEST['What'])?$_REQUEST['What']:0) ];
        
        if (empty($BankRec['YourRef'])) $BankRec['YourRef'] = "Unspecified";
        Put_Banking($BankRec);
        $_REQUEST['Recipient'] = '';
        $_REQUEST['Amount'] = '';
        $_REQUEST['StartTurn'] = '';
        $_REQUEST['EndTurn'] = '';
        $_REQUEST['YourRef'] = '';
        break;
      case 'Transfer Now' :
        dostaffhead("Banking");
        $B = ['FactionId'=>$Fid, 'Recipient'=>$_REQUEST['Recipient'], 'Amount'=>$_REQUEST['Amount'], 
                    'StartTurn'=> $_REQUEST['StartTurn'], 'EndTurn' => (empty( $_REQUEST['EndTurn'])? $_REQUEST['StartTurn'] : $_REQUEST['EndTurn']), 
                    'YourRef' => $_REQUEST['YourRef']];
        if (empty($BankRec['YourRef'])) $BankRec['YourRef'] = "Unspecified";
        
        if (Spend_Credit($Fid,$B['Amount'],$B['YourRef'])) {
          echo "<h2>Transfered  &#8373;" . $B['Amount'] . " for " . $B['YourRef'] . " to " . $Factions[$B['Recipient']]['Name'] . "</h2>";
          if ($B['Recipient'] > 0) {
            Spend_Credit($B['Recipient'], - $B['Amount'],$B['YourRef']);
          }
        } else {
          echo "<h2 class=Err>Transfer failed you only have&#8373;" . $Factions[$Fid]['Credits'] . "</h2>\n";
        }
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
          
  
  dostaffhead("Banking");
  
  $FactList = [];
  $FactList[-1] = "Other";
  foreach ($Facts as $Fi=>$F) {
    $FactList[$Fi] = $Factions[$Fi]['Name'];
  }
  
  echo "<h1>" . $FACTION['Name'] . " - Banking - Turn $Turn</h1>\n";
  echo "<form method=post action=Banking.php>\n";

  $FT = Get_FactionTurnFT($Fid,$Turn);
  if (!isset($FT['id'])) Put_FactionTurn($FT);
  $FTid = $FT['id'];
  if ($Turn >= $GAME['Turn']) Register_Autoupdate('FactionTurn',$FTid);
  echo fm_hidden('Turn',$Turn);
  echo "<h2>Current credits: " . $FACTION['Credits'] . "</h2>\n";
  
  if ($Turn > 1) echo "<input type=Submit Name=ACTION Value='Prev Turn'>";
  echo "<input type=Submit Name=ACTION Value='Current Turn'>";
  echo "<input type=Submit Name=ACTION Value='Next Turn'><p>";

  echo "<h2>Recent transactions - Look back <input type=number size=3 style='width:30px;' min=0 name=LookBack id=LookBack value=$LookBack  onchange=this.form.submit() > Turns</h2>";
  $Creds = Get_CreditLogs($Fid,max($Turn-$LookBack,0),$Turn);
  echo "<div class=CLwrap><table class=CreditLog>";
  echo "<thead><tr><td class=CLTurn>Turn<td class=CLCredit>Start<td class=CLCredit>Credit<td class=CLCredit>Debit<td class=CLCredit>end<td class=CLWhat>What</thead>\n<tbody>";
  foreach ($Creds as $C) echo "<tr><td class=CLTurn>" . $C['Turn'] . "<td class=CLCredit>" . $C['StartCredits'] . "<td class=CLCredit>" . 
                       ($C['Amount']<0 ? -$C['Amount'] . "<td class=CLCredit>" : "<td class=CLCredit>" . $C['Amount']) . 
                      "<td class=CLCredit>" . $C['EndCredits'] . "<td class=CLWhat>" . $C['What'] . "\n";
  echo "</tbody></table></div>\n";
  
   
  
  echo "<h2>Bank transfers for this turn:</h2>\n";
  $Banks = Get_BankingFT($Fid,$Turn);

//var_dump($Banks);

  if ($Banks) {
    if ($Turn >= $GAME['Turn']) echo "Cancel will stop the transfer.  To edit it click Your Reference.<br>";
    echo "<table border><tr><td>Recipient<td>Amount<td>Your Reference<td>Start Turn<td>Last Turn\n";
    if ($Turn >= $GAME['Turn']) echo "<td>Actions\n";
    foreach ($Banks as $B) {
      echo "<tr><td>" . $FactList[$B['Recipient']] . "<td>" . $B['Amount'] ;
      echo "<td><a href=BankEdit.php?id=" . $B['id'] . ">" . $B['YourRef'] . "</a>";
      echo "<td>" . $B['StartTurn'];
      echo "<td>" . ($B['EndTurn']? $B['EndTurn']: $B['StartTurn']);
      if ($Turn >= $GAME['Turn']) echo "<td><input type=submit name=DELETE" . $B['id'] . " value=Cancel >";
    }
    echo "</table><p>\n";
  } else if ($Turn >= $GAME['Turn'])  {
    echo "None have been set up for turn $Turn.<p>\n";
  } else {
    echo "None were set up on turn $Turn.<p>\n";
  }
  echo "</form>";
  
  if (empty($_REQUEST['StartTurn'])) $_REQUEST['StartTurn'] = $Turn;
  echo "<form method=post action=Banking.php>\n";
  echo "<h2>Setup One Off Transfer</h2>";
  echo "<table border>";
  echo fm_hidden('StartTurn',$GAME['Turn']);
  echo "<tr><td>To:<td>" . fm_select($FactList,$_REQUEST,'Recipient') . "<td>Select <b>Other</b> for RP actions";
  echo "<tr>" . fm_number('Amount',$_REQUEST,'Amount');
  echo "<tr>" . fm_text('Your Reference',$_REQUEST,'YourRef') . "<td>Will be seen by both parties";
  echo "<tr><td><td><input type=submit name=ACTION value='Transfer Now'>\n";
    echo "  <td><input type=submit name=ACTION value='Transfer on Turn'>\n";
  echo "</table></form>";

  echo "<form method=post action=Banking.php>\n";
  echo "<h2>Setup Ongoing or Future Transfer</h2>";
  echo "<table border>";
  echo "<tr><td>To:<td>" . fm_select($FactList,$_REQUEST,'Recipient') . "<td>Select <b>Other</b> for RP actions";
  if ($GM) echo "<tr>" . fm_radio('Currency',$Currencies,$_REQUEST,'What');
  echo "<tr>" . fm_number('Amount',$_REQUEST,'Amount');
  echo "<tr>" . fm_number('Start Turn', $_REQUEST,'StartTurn'); 
  echo "<tr>" . fm_number('Last Turn', $_REQUEST,'EndTurn') . "<td>Leave blank for a one off payment";
  echo "<tr>" . fm_text('Your Reference',$_REQUEST,'YourRef') . "<td>Will be seen by both parties";
  echo "<tr><td><td><input type=submit name=ACTION value='Setup'>\n";
  echo "</table></form>";
  
/*    
  echo "table border>";
  echo "<h2Setup Transfer</h2>\n";
  echo "To: " fm_select(KnownFactions) + "Other" <- for RP actions with spend
  echo "Amount: ";
  echo "Start Turn: " fm_number(This onwards)
  echo "End Turn: " fm_number(This onwards);
  echo "Your Ref": fm_text(Whatever) 
  echo "Transaction name:" fm_text(whatextra)
  echo "<tr><td><td><input type=submit name=ACTION value=Setup>\n";
  echo "</table<p>\n";

*/  
  echo "<form method=post action=Banking.php>\n";
  echo "<h2>Other expected income</h2>\n";
  echo "What do expect from others, and amount - may stop annoying nag messages that you have overspent in your plans. No other effect. These are not checked<p>";
  echo "<table border>";
  echo "<tr>" . fm_textarea("Description",$_REQUEST,'IncomeText',2,2);
  echo "<tr>" . fm_number('Amount',$_REQUEST,'IncomeAmount');
  
  echo "</table></form>";
  
//  Player_Page();
  dotail();  
?>
