<?php

// Scan all plantets/moon's/Thing's for districts and deep space construction

  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("MinedLib.php");
  
  A_Check('GM');
  
  dostaffhead("Rebuild Mines database");
  
  Recalc_Mined_locs(); // All there now
  
  
  dotail();
  
                  // Need to add control of planet I think
   // Things with districts and deep spae construction
   // Agents do projects as well!!!
          
?>
