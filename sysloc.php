<?php
include_once("sk.php");
include_once("GetPut.php");
$field = $_REQUEST['F'];
$id    = $_REQUEST['I'];
$type  = $_REQUEST['D'];
$sid   = $_REQUEST['S'];
$mtch = [];

// var_dump($_REQUEST);
global $GAME,$GAMEID;

include_once('ThingLib.php');
$A = [];
$N = Get_System($sid);
$syslocs = Within_Sys_Locs($N);
echo "<td id=AnomalyLoc>" . fm_select($syslocs,$A,'WithinSysLoc');


?>