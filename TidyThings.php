<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("PlayerLib.php");
  include_once("SystemLib.php");
  include_once("ProjLib.php");
  
  A_Check('GM');


  global $db;
  
  dostaffhead("Tidy up debris in Things table");
  
  $res = $db->query("DELETE * FROM Things WHERE Type=0");
  
  echo "<h1>Tidy</h1>";
  
  dotail();
