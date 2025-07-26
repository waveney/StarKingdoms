<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("PlayerLib.php");

  $field = $_POST['F'];
  $Value = $_POST['V'];
  $Reason = $_POST['R'];
  $NewTot = $_POST['T'];
  $ResTyp = $_POST['K'];
  $mtch = [];

  if (strchr($Reason,'"')) {
    $Reason = addslashes($Reason);
  }

  global $GAME,$GAMEID;

// var_dump($_POST);
//   $.post("addformfill.php", {'F':id, 'V':mval, 'Y':yearval, 'R':Reason, 'T':newtot, 'K':ResType}, function( data ) {

  if ((preg_match('/(\w*):(\w*):(\d*)/',$field,$mtch)?true:false)) {
    $t = $mtch[1];
    $f = $mtch[2];
    $i = $mtch[3];
    if (($t == 'Ignore') || ($i==0)) exit;

    $Fact = Get_Faction($i);
    if ($GAME['id'] != $Fact['GameId']) Get_Game( $Fact['GameId']);

    if (($t == 'Factions') && ($f == 'Credits')) {
      Spend_Credit($i,-$Value,$Reason);
    } else {
      $N = Gen_Get($t,$i);
      $N[$f] = $NewTot;
      echo Gen_Put($t,$N);

      $Spog = ['GameId'=>$GAME['id'],'Turn'=>$GAME['Turn'],'FactionId'=>$i, 'Type'=>$ResTyp, 'Number'=>$Value, 'Note'=>$Reason, 'EndVal'=>$NewTot];
      Gen_Put('SciencePointLog',$Spog);
    }

  }

