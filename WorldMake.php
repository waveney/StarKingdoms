<?php

// Scan all plantets/moon's/Thing's for districts and deep space construction

  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("PlayerLib.php");
  include_once("SystemLib.php");
  include_once("HomesLib.php");
  include_once("OrgLib.php");

  A_Check('GM');

  dostaffhead("Rebuild Worlds database");

  Recalc_Worlds(); // All there now
  Recalc_Offices();
  Set_System_List(); // Lists of worlds in Systems

  dotail();

                  // Need to add control of planet I think
   // Things with districts and deep spae construction
   // Agents do projects as well!!!

