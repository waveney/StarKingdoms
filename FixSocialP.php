<?php

include_once("sk.php");

dostaffhead('Fix Social Priniciples');

$AllSPs = Gen_Get_All('SocialPrinciples');

foreach($AllSPs as $Spid=>$S) {
  $Rec = ['Principle'=>$Spid,'World'=>$S['World'],'Value'=>$S['Value']];
  Gen_Put('SocPsWorlds',$Rec);
}

echo "Data converted";
dotail();

//$SocPs Get_S
