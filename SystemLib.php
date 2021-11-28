<?php

// System common code

function RealWorld(&$Data,$fld) {
    $Star = (isset($Data['SystemId'])?1:0);
    $val = $Data[$fld];
    switch ($fld) {
    case 'Radius' :
    case 'Radius2' :
      if ($val > 20000) {
        return sprintf("%0.2f x Sun",$val/695700);      
      } else {
        return sprintf("%0.2f x Earth",$val/6378);      
      }
    case 'Mass' :
    case 'Mass2' :
      if ($val > 1E28) {
        return sprintf("%0.2f x Sun",$val/2E30);      
      } else {
        return sprintf("%0.2f x Earth",$val/5.97e24);      
      }
    
    case 'Temperature' :
    case 'Temperature2' :    
      return "";
      
    case 'Luminosity' :
    case 'Luminosity2' :    
      return sprintf("%0.2f x Sun",$val/3.83e26);          
      
    case 'OrbitalRadius' :
      return sprintf("%0.2f AU",$val/1.496e8);            
    
    case 'Period' :
      if ($val > 4000) {
        return sprintf("%0.2f Years",$val/8760);            
      } elseif ($val > 50) {
        return sprintf("%0.2f Days",$val/24);                    
      } elseif ($val > 2) {
        return sprintf("%0.2f Hours",$val);      
      } elseif ($val > 2/60) {
        return sprintf("%0.2f Minutes",$val*60);      
      } else {
        return sprintf("%0.2f Seconds",$val*3600);      
      }
      return;
    
    case 'Gravity' :
      return sprintf("%0.2f G",$val/9.8);
    
    case 'Distance' :
      if ($val > 2e6) {
        return sprintf("%0.2f AU",$val/1.496e8);      
      } else {
        return sprintf("%0.2f x Radius of Stars",$val/($Data['Radius']+$Data['Radius2']));      
      }

    default:
      return "";
    }
}
  
function RealHeat(&$N,&$P) {
  $heat = $N['Luminosity']/(($P['OrbitalRadius']*1000)^2);
  return sprintf("%0.2f Earth", $heat*(1.496e11^2)/3.83e26);
}

function RealHeatValue(&$N,&$P) {
  $heat = $N['Luminosity']/(($P['OrbitalRadius']*1000)^2);
  return $heat*(1.496e11^2)/3.83e26;
}

function Show_System(&$N,$Mode=0) {
  global $GAME,$GAMEID;
  if (!isset($N['id'])) {
    echo "<h1 class=Error>No System to Display</h1>";
    dotail();
  }
  $Sid = $N['id'];
  echo "<input  class=floatright type=Submit name='Update' value='Save Changes' form=mainform>";

  if ($Mode) {
    echo "<span class=NotSide>Fields marked are not visible to factions.</span>";
    echo "  <span class=NotCSide>Marked are visible if set, but not changeable by factions.</span>";
  }
  
  $Facts = Get_Faction_Names();
  $Fact_Colours = Get_Faction_Colours();
  $Planets = Get_Planets($Sid);
  $PTNs = Get_PlanetTypeNames();
  $PTD = Get_PlanetTypes();
  
  echo "<form method=post id=mainform enctype='multipart/form-data' action=SysEdit.php>";
  echo "<div class=tablecont><table width=90% border class=SideTable>\n";
  Register_AutoUpdate('System',$Sid);
  echo fm_hidden('id',$Sid);
  echo "<tr class=NotSide><td class=NotSide>Id:<td class=NotSide>$Sid<td class=NotSide>Game<td class=NotSide>$GAMEID" .
       "<td class=NotSide>" . $GAME['Name'];
  echo "<tr class=NotSide>" . fm_text('Grid X',$N,'GridX',1,"class=NotSide") . fm_text('Grid Y',$N,'GridY',1,"class=NotSide") . fm_text('Ref',$N,'Ref',1,"class=NotSide");
//  echo "<tr class=NotCSide><td class=NotCSide>Control:<td class=NotCSide>" . fm_select($Facts,$N,'Control',1); // Known by TODO, Image
  echo "<tr>" . fm_radio('Control',$Facts ,$N,'Control','',1,'colspan=6','',$Fact_Colours,0); 
  echo "<tr>" . fm_text('Name',$N,'Name',8);
  echo "<tr>" . fm_text('Short Name',$N,'ShortName') . fm_text('Nebulae',$N,'Nebulae',1,"class=NotCSide"). fm_number('Category',$N,'Category',1,"class=NotSide") ;
  echo "<tr>" . fm_textarea('Description',$N,'Description',8,3);
  echo "<tr>" . fm_text('Type',$N,'Type',2,"class=NotCSide") . "<td rowspan=5 colspan=3>";
//  if ($N['Image']) echo "<img src='" . $N['Image'] . "'>";
  echo "<table><tr>";
    echo fm_DragonDrop(1,'Image','System',$Sid,$N,$Mode,'',1,'','System');
  echo "</table>";
//  echo $N['Image'];
  echo "<tr>" . fm_number('Radius',$N,'Radius',1,"class=NotCSide") . "<td>Km = " . RealWorld($N,'Radius'); 
  echo "<tr>" . fm_number('Mass',$N,'Mass',1,"class=NotCSide") . "<td>Kg = " . RealWorld($N,'Mass'); 
  echo "<tr>" . fm_number('Temperature',$N,'Temperature',1,"class=NotCSide") . "<td>K";
  echo "<tr>" . fm_number('Luminosity',$N,'Luminosity',1,"class=NotCSide") . "<td>W = " . RealWorld($N,'Luminosity');
  if ($N['Type2']) {
    echo "<tr>" . fm_text('2nd Star Type',$N,'Type2',2,"class=NotCSide") . "<td rowspan=5 colspan=3>";
//    if ($N['Image2']) echo "<img src='" . $N['Image2'] . "'>";
    echo "<table><tr>";
      echo fm_DragonDrop(1,'Image2','System2',$Sid,$N,$Mode,'',1,'','System');
    echo "</table>";
//  echo $N['Image2'];
    echo "<tr>" . fm_number('Radius',$N,'Radius2',1,"class=NotCSide") . "<td>Km = " . RealWorld($N,'Radius2') ;
    echo "<tr>" . fm_number('Mass',$N,'Mass2',1,"class=NotCSide") . "<td>Kg = " . RealWorld($N,'Mass2');
    echo "<tr>" . fm_number('Temperature2',$N,'Temperature2',1,"class=NotCSide") . "<td>K" ;
    echo "<tr>" . fm_number('Luminosity',$N,'Luminosity2',1,"class=NotCSide") . "<td>W = " . RealWorld($N,'Luminosity');
    echo "<tr>" . fm_number('Distance',$N,'Distance',1,"class=NotCSide") . "<td>Km = " . RealWorld($N,'Distance') ;
    echo "<tr>" . fm_number('Period',$N,'Period',1,"class=NotCSide") . "<td>Hr = " . RealWorld($N,'Period');
  } elseif ($Mode) {
    echo "<tr>" . fm_text('2nd Star Type',$N,'Type2',2,"class=NotCSide");  
  }

  if (Access('God')) echo "<tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea>";
  echo "</table></div>\n";
  
  if (!$Planets && $Mode==0) {
    echo "<h2>No Planets</h2>\n";
  } else {
    echo "<h1>Planets</h1>";
    echo "<div class=tablecont><table width=90% border class=SideTable>\n";
    echo "<tr><td>Name<td>Type<td>AU<td>Heat<td>Habitable\n";
    foreach ($Planets as $P) {
      $Pid = $P['id'];
      echo "<tr><td><a href=PlanEdit.php?id=$Pid>" . ($P['Name']?$P['Name']:$Pid) . "</a><td>" . $PTNs[$P['Type']] . "<td>" . RealWorld($P,'OrbitalRadius')
           . "<td>Heat: " . RealHeat($N,$P) . "<td>" . $PTD[$P['Type']]['Hospitable'];
      }
    echo "</table></div>\n";
  }
  if (Access('God')) {
    echo "<center><form method=post action=SysEdit.php>" . fm_hidden('id', $Sid) .
         "<input type=submit name=ACTION value='Add Planet' class=Button> ";
    if (!$Planets && $Mode==1) { 
      echo "<input type=submit name=ACTION value='Auto Populate' class=Button>";
    } elseif ($Mode) {
      echo "<input type=submit name=ACTION value='Delete Planets' class=Button>";    
    }
    echo "</form></center>";
  }

}

function Show_Planet(&$P,$Mode=0) {
  global $GAME,$GAMEID;
  $Pid = $P['id'];
  if ($Mode) {
    echo "<span class=NotSide>Fields marked are not visible to factions.</span>";
    echo "  <span class=NotCSide>Marked are visible if set, but not changeable by factions.</span>";
  }
  
  $Facts = Get_Faction_Names();
  $DTs = Get_DistrictTypeNames();
  $Ds = Get_Districts($Pid);
  $N = Get_System($P['SystemId']);
  $PTNs = Get_PlanetTypeNames();
  
  echo "<form method=post id=mainform enctype='multipart/form-data' action=PlanEdit.php>";
  echo "<div class=tablecont><table width=90% border class=SideTable>\n";
  Register_AutoUpdate('Planet',$Pid);
  echo fm_hidden('id',$Pid);
  echo "<tr class=NotSide><td class=NotSide>Id:<td class=NotSide>$Pid<td class=NotSide>Game<td class=NotSide>$GAMEID" .
       "<td class=NotSide>" . $GAME['Name'] . fm_text('System Ref',$N,'Ref',1,"class=NotSide");
  echo "<tr>" . fm_text('Name',$P,'Name',8);  // TODO Image
  echo "<tr>" . fm_text('Short Name',$P,'ShortName');
  echo "<tr>" . fm_textarea('Description',$P,'Description',8,3);
  echo "<tr><td>Type:<td>" . fm_Select($PTNs,$P,'Type',1) . fm_number('Minerals',$P,'Minerals',1,"class=NotCSide"); 
  
  echo "<tr>" . fm_number('Orbital Radius',$P,'OrbitalRadius',1,"class=NotCSide") . "<td>Km = " . RealWorld($P,'OrbitalRadius'); 
  echo "<td rowspan=4 colspan=3>";
//  if ($N['Image']) echo "<img src='" . $P['Image'] . "'>";
  echo "<table><tr>";
    echo fm_DragonDrop(1,'Image','Planet',$Pid,$P,$Mode,'',1,'','Planet');
  echo "</table>";
  
  echo "<tr>" . fm_number('Period',$P,'Period',1,"class=NotCSide") . "<td>Hr = " . RealWorld($P,'Period');
  echo "<tr>" . fm_number('Gravity',$P,'Gravity',1,"class=NotCSide") . "<td>m/s<sup>2</sup> = " . RealWorld($P,'Gravity');
  echo "<tr>" . fm_number('Radius',$P,'Radius',1,"class=NotCSide") . "<td>Km = " . RealWorld($P,'Radius');
      
//  $heat = $N['Luminosity']/(($P['OrbitalRadius']*1000)^2);
  echo "<tr><td>Heat:<td>" . RealHeat($N,$P); //sprintf("%0.2f", $heat) . "<td> = " . sprintf("%0.2f Earth", $heat*(1.496e11^2)/3.83e26);
  $NumDists = count($Ds);
  $dc=0;
  
  foreach ($Ds as $D) {
    $did = $D['id'];
    if ($dc++%4 == 0)  echo "<tr>";
    echo "<td>" . fm_Select($DTs, $D , 'Type', 1,'',"DistrictType-$did") . fm_number1('', $D,'Number', '','',"DistrictNumber-$did");
    };

  echo "<tr><td>Add District Type<td>" . fm_Select($DTs, NULL , 'Number', 1,'',"DistrictTypeAdd-$Pid");

  if (Access('God')) echo "<tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea>";
  echo "</table></div></form>\n";
}
?>
