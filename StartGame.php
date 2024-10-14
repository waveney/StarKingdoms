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

global $LinkStates,$GAME,$FACTION;

A_Check('GM');

$LinkState = array_flip($LinkStates);
dostaffhead("Special Turn Processing");

A_Check('Player');
$Fid = $FACTION['id'];
$F = $FACTION;
      $Facts = Get_Factions();
        echo "Starting " . $F['Name'] . "<p>";
        $Wid = $F['HomeWorld'];
        if (!$Wid) {
          echo "No homeworld - missing out seting starting loc<p>";
          $StartSys = 0;
        } else {
          $World = Get_World($Wid);
          $Planet = Get_Planet($World['ThingId']);
          $StartSys = ($Planet['SystemId']??0);
        }
        $Things = Get_Things_Cond($Fid);
        foreach ($Things as $Tid=>$T) {
          if ($StartSys && ($T['SystemId'] == 0)) $T['SystemId'] = $StartSys;
          Put_Thing($T);
          if ($T['BuildState'] < 3) $T['BuildState'] = 3;
          RefitRepair($T);
          echo "Setup thing $Tid - " . $T['Name'] . "<br>";
        }
        echo "Finished " . $F['Name'] . "<p>";

dotail();
