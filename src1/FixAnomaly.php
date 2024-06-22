<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("PlayerLib.php");
  include_once("SystemLib.php");
  include_once("ProjLib.php");
  include_once("HomesLib.php");
  include_once("BattleLib.php");
  include_once("vendor/erusev/parsedown/Parsedown.php");
  
  A_Check('GM');
  
  $Things = Get_Things_Cond(0,"Instruction=4");
  $Facts = Get_Factions();
  
  foreach ($Things as $T) {
    $N = Get_System($T['SystemId']);
    $Tid = $T['id'];

        $Aid = $T['ProjectId'];
        $Fid = $T['Whose'];
        if ($Aid) {
          $A = Get_Anomaly($Aid);
          $FAs = Gen_Get_Cond('FactionAnomaly',"FactionId=$Fid AND AnomalyId=$Aid");
          if ($FAs) {
            $FA = $FAs[0];
            $T['Progress'] = $FA['Progress'];

            if ($FA['Progress'] >= $A['AnomalyLevel'] && $FA['State'] != 2) {
 //            $FA['State'] = 2;
             Gen_Put('FactionAnomaly',$FA);          
             echo $Facts[$T['Whose']]['Name'] . " Anomaly study on " . $A['Name'] . " has been completed - See sperate response from the GMs for what you get";
             $T['ProjectId'] = 0;
           }
            Put_Thing($T);
            echo "Updated records $Tid : " . $T['Name'] . "<p>";
         }
       }
    }
  echo "All Anomaly studies checked<p>";
  dotail();
?>
       

