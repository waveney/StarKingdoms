<?php

// Scan all plantets/moon's/Thing's for districts and deep space construction

  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("PlayerLib.php");
  include_once("SystemLib.php");
  
  A_Check('GM');
  
  dostaffhead("Rebuild Project homes database");
  
  Recalc_Project_Homes(); // All there now
  
  
  dotail();
  
                  // Need to add control of planet I think
   // Things with districts and deep spae construction
   // Agents do projects as well!!!
          
?>
