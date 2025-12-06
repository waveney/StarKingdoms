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
include_once("OrgLib.php");

dostaffhead('Embeded testing');

set_error_handler(function($_errno, $errstr) {
  // Convert notice, warning, etc. to error.
  throw new Error($errstr);
});

A_Check('God');
$Result = '';
$Code = $_REQUEST['CODE']??'';

if (isset($_REQUEST['ACTION'])) {
  switch ($_REQUEST['ACTION']) {
    case 'Run':
      try {
        $Result = eval($Code . ';');
      } catch (Throwable $e) {
        echo $e; // Error: Undefined variable: tw...
      }
      break;


  }
}

$Show['CODE'] = $Code;
echo "<form method=post><table><tr>" . fm_textarea('',$Show,'CODE',5,10);
echo "<tr><td>" . fm_submit('ACTION','Run');
echo "</table><hr>" . $Result??'' . "<hr>";


dotail();

//$SocPs Get_S
