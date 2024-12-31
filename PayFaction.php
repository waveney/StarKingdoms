<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("PlayerLib.php");

  A_Check('GM');

  dostaffhead("GM Payments to Factions",["js/dropzone.js","css/dropzone.css" ]);

  global $db, $GAME,$GAMEID,$Currencies;

  $Factions = Get_Factions();
  $Facts = Get_Faction_Names();
  AddCurrencies();
  $Trade = TradeableCurrencies();

  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
    case 'Pay Now' :
      $What = $_REQUEST['What'];
      if ($What == 0) {
        Spend_Credit($_REQUEST['F'],- $_REQUEST['C'],$_REQUEST['R']);
        echo "<h1>" . $Facts[$_REQUEST['F']] . " has been paid " . $_REQUEST['C'] . "</h1>";
      } else if ($What <5) {
        Gain_Science($_REQUEST['F'],$What, $_REQUEST['C'],$_REQUEST['R']);
        echo  "<h1>" . $Facts[$_REQUEST['F']] . " have gained " . $_REQUEST['C'] . " Science Points</h1>";
      } else { // Extra currencies
        Gain_Currency($_REQUEST['F'],$What, $_REQUEST['C'],$_REQUEST['R']);
        echo  "<h1>" . $Facts[$_REQUEST['F']] . " have gained " . $_REQUEST['C'] . " " . $Currencies[$What] . "</h1>";
      }
      break;


    case 'Pay Next Turn' :
      $BankRec = ['FactionId'=>0, 'Recipient'=>$_REQUEST['F'], 'Amount'=>$_REQUEST['C'],
                    'StartTurn'=> $GAME['Turn'], 'EndTurn' => (isset($_REQUEST['O']) ?1000 :$GAME['Turn']),
                    'YourRef' => $_REQUEST['R'],'What'=>$_REQUEST['What'],'GameId'=>$GAMEID ];

      Put_Banking($BankRec);
      echo "<h1>" . $Facts[$_REQUEST['F']] . " Will been paid " . $_REQUEST['C'] . "Next Turn</h1>";

      break;

    default:
      break;
    }
  }


  if (!isset($_REQUEST['What'])) $_REQUEST['What'] = 0;
  echo "<h1>Pay a Faction directly</h1>";

  echo "<form method=post action=PayFaction.php>";
  echo "<table border>";
  echo "<tr><Td>Pay:<td>" . fm_select($Facts,$_REQUEST,'F');

  echo "<tr>" . fm_radio('',$Currencies,$_REQUEST,'What');
  echo "<tr>" . fm_number("Amount",$_REQUEST,'C');
  echo "<tr>" . fm_text("Reason",$_REQUEST,'R');
  echo "<tr><td>" . fm_checkbox("Ongoing:<td>",$_REQUEST,'O',1);
  echo "</table>\n";

  echo "<input type=submit name=ACTION value='Pay Now'> <input type=submit name=ACTION value='Pay Next Turn'>";

  dotail();
?>
