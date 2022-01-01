<?php

// Scan all plantets/moon's/Thing's for districts and deep space construction

  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("PlayerLib.php");
  include_once("SystemLib.php");
  
  A_Check('GM');
  
  $Facts = Get_Factions();

  $KnownHomes = [];
    
  foreach($Facts as $F) {
    $Homes = Get_ProjectHomes($F['id']);
    if ($Homes) array_push($KnownHomes,$Homes);
  }
  
  $Systems = Get_Systems();
  foreach ($Systems as $N) {
    if ($N['Control']) {
      $Planets = Get_Planets($N['id']);
      if ($Planets) {
        foreach ($Planets as $P) {
          $Dists = Get_Districts($P['id']);
          if ($Dists) {
            foreach ($KnownHomes as $H) {
              if ($H['ThingType'] == 1 && $H['ThingId'] == $P['id']) {
                if ($H['Whose'] == $N['Control']) {
                  $H['Inuse'] = 1;
                } else {
                  $H['Whose'] = $N['Control']
                  
                  
                  // Need to add control of planet I think
          
?>
