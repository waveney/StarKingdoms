<?php

// Things far handling projects

function Proj_Costs($lvl) {
  return [[0,0],[1,50],[4,200],[9,450],[16,800],[25,1250],[36,1800],[49,2450],[64,4300],[81,4050],[100,5000]][$lvl];
}

function Rush_Cost($who) {
  if (Has_Trait('Bike-Shedders', $who)) return 100;
  return 75; 
}

global $Project_Status;
$Project_Status = [0=>'Live',1=>'Started', 2=>'Finished',3=>'Cancelled', 4=>'On Hold', 5=>'Not Started'];


?>
