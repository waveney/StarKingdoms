<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("ProjLib.php");
  include_once("PlayerLib.php");

  A_Check('GM');

  dostaffhead("List Payments");


  global $GAME,$GAMEID,$Currencies,$PayFactionRates;

  AddCurrencies();
  $Facts = Get_Faction_Names();
//  var_dump($_REQUEST);
  echo "<h2>Payments in Credits or SP</h2>\n";
  $Banks = Gen_Get_Cond('Banking', "GameId=$GAMEID AND FactionId=0 AND EndTurn>=" . $GAME['Turn']);

  if (UpdateMany('Banking','Put_Banking',$Banks,2,'','','Amount',0))
    $Banks = Gen_Get_Cond('Banking', "GameId=$GAMEID AND FactionId=0 AND EndTurn>=" . $GAME['Turn']);

  $coln = 0;
  echo "<form method=post action=Payments.php>";
  echo "<div class=tablecont><table id=indextable border width=100% style='min-width:1400px'>\n";
  echo "<thead><tr>";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Id</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Recipient</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>What</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Amount</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Start Turn</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>End Turn</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Decay Rate</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>When</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Last Paid</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Reference</a>\n";
  echo "</thead><tbody>";

  foreach($Banks as $B) {
    $i = $B['id'];
    echo "<tr><td>$i";
    echo "<td>" . fm_select($Facts,$B,'Recipient',1,'',"Recipient$i");
    echo "<td>" . fm_select($Currencies,$B,'What',0,'',"What$i");
    echo fm_number1('',$B,'Amount','','',"Amount$i");
    echo fm_number1('',$B,'StartTurn','',' min=0 max=1000',"StartTurn$i");
    echo fm_number1('',$B,'EndTurn','',' min=0 max=1000',"EndTurn$i");
    echo fm_number1('',$B,'DecayRate','','',"DecayRate$i");
    echo "<td>" .fm_select($PayFactionRates,$B,'Frequency',0,'',"Frequency$i");
    echo fm_number1('',$B,'DoneTurn','',' min=0 max=1000',"DoneTurn$i");
    echo fm_text1('',$B,'YourRef',1,'','',"YourRef$i");
  }
  echo "</table>\n";
  echo "<input type=Submit name=Update value=Update>";
  dotail();



