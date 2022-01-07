<?php

/* $SurveyLevels = [0=>'Blind',1=>'No Sensors', 2=>'Minimal Scan', 3=>'A bit better', 4=>'Most Things', 5=>'Full Scan no control', 
                 6=>'Full Scan under control', 10=>'GM Only']; */
 $SurveyLevels = [0=>'Blind', 1=>'No Sensors', 5=>'Full Scan', 10=>'GM Only'];


global $SurveyLevels;


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
      if ($val < 1e7) {
        return sprintf("%0.2f x Moon",$val/384400);             
      }
      return sprintf("%0.2f AU",$val/1.496e8);            
    
    case 'Period' :
      if ($val > 8000) {
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
  $heat = ($N['Luminosity']+($N['Type2']?$N['Luminosity2']:0))/(($P['OrbitalRadius']*1000)^2);
  return $heat*(1.496e11^2)/3.83e26;
}

function PM_Type(&$Dat,$PM) {
  return $Dat['Name'] . ($Dat['Append']?" $PM":'');
}

function Show_System(&$N,$Mode=0) {
  global $GAME,$GAMEID;
  if (!isset($N['id'])) {
    echo "<h1 class=Error>No System to Display</h1>";
    dotail();
  }
  $Sid = $N['id'];
  $Ref = $N['Ref'];
  echo "<input  class=floatright type=Submit name='Update' value='Save Changes' form=mainform>";

  if ($Mode) {
    echo "<span class=NotSide>Fields marked are not visible to factions.</span>";
    echo "  <span class=NotCSide>Marked are visible if set, but not changeable by factions.</span>";
  }
  
  $FactNames = Get_Faction_Names();
  $Facts = Get_Factions();
  $Fact_Colours = Get_Faction_Colours();
  $Planets = Get_Planets($Sid);
  $PTNs = Get_PlanetTypeNames();
  $PTD = Get_PlanetTypes();
  $LinkLevels = Get_LinkLevels();
  $Ls = Get_Links($Ref);  
  
  echo "<form method=post id=mainform enctype='multipart/form-data' action=SysEdit.php>";
  echo "<div class=tablecont><table width=90% border class=SideTable>\n";
  Register_AutoUpdate('System',$Sid);
  echo fm_hidden('id',$Sid);
  echo "<tr class=NotSide><td class=NotSide>Id:<td class=NotSide>$Sid<td class=NotSide>Game<td class=NotSide>$GAMEID" .
       "<td class=NotSide>" . $GAME['Name'];
  echo "<tr class=NotSide>" . fm_text('Grid X',$N,'GridX',1,"class=NotSide") . fm_text('Grid Y',$N,'GridY',1,"class=NotSide") . fm_text('Ref',$N,'Ref',1,"class=NotSide");
//  echo "<tr class=NotCSide><td class=NotCSide>Control:<td class=NotCSide>" . fm_select($Facts,$N,'Control',1); // Known by TODO, Image
  echo "<tr>" . fm_radio('Control',$FactNames ,$N,'Control','',1,'colspan=6','',$Fact_Colours,0); 
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
    echo "<tr>" . fm_number('Temperature',$N,'Temperature2',1,"class=NotCSide") . "<td>K" ;
    echo "<tr>" . fm_number('Luminosity',$N,'Luminosity2',1,"class=NotCSide") . "<td>W = " . RealWorld($N,'Luminosity2');
    echo "<tr>" . fm_number('Distance',$N,'Distance',1,"class=NotCSide") . "<td>Km = " . RealWorld($N,'Distance') ;
    echo "<tr>" . fm_number('Period',$N,'Period',1,"class=NotCSide") . "<td>Hr = " . RealWorld($N,'Period');  
    if (Access('God')) echo "<td><a href=SysEdit.php?id=$Sid&ACTION=RECALC>Recalc</a>";
  } elseif ($Mode) {
    echo "<tr>" . fm_text('2nd Star Type',$N,'Type2',2,"class=NotCSide");  
  }

  if (Access('God')) echo "<tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea>";
  echo "<tr>" . fm_textarea('Notes',$N,'Notes',8,3);
  
  echo "</table></div>\n";
  
  if (!$Planets && $Mode==0) {
    echo "<h2>No Planets</h2>\n";
  } else {
    echo "<h1>Planets</h1>";
    echo "<div class=tablecont><table width=90% border class=SideTable>\n";
    echo "<tr><td>Name<td>Type<td>AU<td>Heat<td>Habitable\n";
//    if (Access('God')) echo "<td>Orbital Number";
    foreach ($Planets as $P) {
      $Pid = $P['id'];
      echo "<tr><td><a href=PlanEdit.php?id=$Pid>" . ($P['Name']?$P['Name']:$Pid) . "</a><td>" . PM_Type($PTD[$P['Type']], "Planet") . "<td>" . RealWorld($P,'OrbitalRadius')
           . "<td>Heat: " . RealHeat($N,$P) . "<td>" . $PTD[$P['Type']]['Hospitable'];
//      if (Access('God')) echo "<td>" . sqrt($P['OrbitalRadius']**3/($N['Mass']));
      }
    echo "</table></div>\n";
  }
  
  echo "<h2>Stargates</h2>";
  echo "Go to link to update knowledge for now.<p>";
  echo "<table border><tr><td>Link<td>To<td>Level";
  foreach ($Facts as $F) echo "<td>" . $F['Name'];
  echo "\n";
  
  foreach ($Ls as $L) {
    $Lid = $L['id'];
    $OSysRef = ($L['System1Ref']==$Ref? $L['System2Ref']:$L['System1Ref']);
    $ON = Get_SystemR($OSysRef); 
//var_dump($OSysRef, $ON);
    $ONid = $ON['id'];
    $Know = Get_Factions4Link($L['id']);
    $OName = NameFind($ON);
    
    echo "<tr><td><a href=LinkEdit.php?id=$Lid>#$Lid</a><td><a href=SysEdit.php?id=$ONid>$OSysRef - $OName</a><td>" . $LinkLevels[$L['Level']]['Colour'];
    foreach ($Facts as $F) echo "<td>" . (isset($Know[$F['id']]) && $Know[$F['id']]['Known']?"Yes " . NameFind($Know):"No");
    echo "\n";
    }
  echo "</table>\n";

  
  if (Access('God')) {
    echo "<center><form method=post action=SysEdit.php>" . fm_hidden('id', $Sid) .
         "<input type=submit name=ACTION value='Add Planet' class=Button> ";
    if (!$Planets && $Mode==1) { 
      echo "<input type=submit name=ACTION value='Auto Populate' class=Button>";
    } elseif ($Mode) {
      echo "<input type=submit name=ACTION value='Delete Planets' class=Button>";    
    }
    echo "<input type=submit name=ACTION value='Redo Moons' class=Button>";    
    echo "</form></center>";
  }
  echo "<center><h2><a href=SurveyReport.php?id=$Sid>Survey Report</a>";
  if (Access('God'))  echo ", <a href=SurveyReport.php?id=$Sid&F=1>Survey Report from Faction 1</a>"  ;
  echo "</h2></center>";

}

function Show_Planet(&$P,$Mode=0,$Buts=0) {
  global $GAME,$GAMEID;
  $Pid = $P['id'];
  if ($Mode) {
    echo "<span class=NotSide>Fields marked are not visible to factions.</span>";
    echo "  <span class=NotCSide>Marked are visible if set, but not changeable by factions.</span>";
  }
  
  $Facts = Get_Faction_Names();
  $DTs = Get_DistrictTypeNames();
  $Ds = Get_DistrictsP($Pid);
  $N = Get_System($P['SystemId']);
  $PTNs = Get_PlanetTypeNames();
  $PTD = Get_PlanetTypes();
  $Mns = Get_Moons($Pid);
  
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
  echo fm_number('Moons',$P,'Moons',1,"class=NotCSide");
  
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
  if (Access('God')) echo "<td><a href=PlanEdit.php?id=$Pid&ACTION=RECALC>Recalc</a>";      
  if ($Mns) {
    echo "<tr><td>Editable Moons\n";
    foreach ($Mns as $M) {
      $Mid = $M['id'];
      echo "<tr><td><a href=MoonEdit.php?id=$Mid>" . NameFind($M) . "<td colspan=6>";
      echo " is " . ($PTNs[$M['Type']] == 'Asteroid Belt'?" an ":($PTD[$M['Type']]['Hospitable']?" a <b>habitable ":" an uninhabitable ")) .
           PM_Type($PTD[$M['Type']],"moon") . "</b>.  ";
    
      if ($PTD[$M['Type']]['Hospitable'] && $M['Minerals']) echo "It has a minerals rating of <b>" . $M['Minerals'] . "</b>.  ";
      echo "Orbital radius: " . sprintf('%4g', $M['OrbitalRadius']) . " Km = " .  RealWorld($M,'OrbitalRadius') . 
           ", Period: " . sprintf('%4g', $M['Period']) . " Hr = " .  RealWorld($M,'Period');
      if ($P['Radius']) echo ", Radius " . sprintf('%4g', $M['Radius']) . " Km = " .  RealWorld($M,'Radius') .
                             ", Gravity: " . sprintf('%4g', $M['Gravity']) . " m/s<sup>2</sup> = " .  RealWorld($M,'Gravity');
                               
//      if ($M['Description']) echo "<p>" . $Parsedown->text($M['Description']) . "<p>";
      }
    }


  
  $NumDists = count($Ds);
  $dc=0;
  
  foreach ($Ds as $D) {
    $did = $D['id'];
    if ($dc++%4 == 0)  echo "<tr>";
    echo "<td>" . fm_Select($DTs, $D , 'Type', 1,'',"DistrictType-$did") . fm_number1('', $D,'Number', '','',"DistrictNumber-$did");
    };

  echo "<tr><td>Add District Type<td>" . fm_Select($DTs, NULL , 'Number', 1,'',"DistrictTypeAdd-$Pid");
  echo fm_number(($P['ProjHome']?"<a href=ProjHome.php?id=" . $P['ProjHome'] . ">Project Home</a>":"Project Home"),$P,'ProjHome');

  if (Access('God')) echo "<tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea>";
  echo "</table></div></form>\n";


  if ($Buts) {
    echo "<center>" .
         "<form method=post action=SysEdit.php>" . fm_hidden('id', $P['SystemId']) .
         "<input type=submit name=NOACTION value='Back to System' class=Button> " .
         "</form></center>";
       
    if (Access('God')) {
      echo "<center>" .
           "<form method=post action=PlanEdit.php>" . fm_hidden('id', $Pid);

      if ($PTD[$P['Type']]['Hospitable'] || ($PTD[$P['Type']]['Name'] == 'Gas Giant')) {
          echo "<input type=submit name=ACTION value='Add Editable Moon' class=Button> ";
      }
    
      echo "<input type=submit name=ACTION value='Delete Planet' class=Button> " .
           "<input type=submit name=ACTION value='Delete Moons' class=Button></form></center>";
    }
  }
}

function Show_Moon(&$M,$Mode=0) {
  global $GAME,$GAMEID;
  $Mid = $M['id'];
  if ($Mode) {
    echo "<span class=NotSide>Fields marked are not visible to factions.</span>";
    echo "  <span class=NotCSide>Marked are visible if set, but not changeable by factions.</span>";
  }
  
  $Pid = $M['PlanetId'];
  $P = Get_Planet($Pid);
  $Facts = Get_Faction_Names();
  $DTs = Get_DistrictTypeNames();
  $Ds = Get_DistrictsM($Mid);
  $N = Get_System($P['SystemId']);
  $PTNs = Get_PlanetTypeNames();
  $PTD = Get_PlanetTypes();
  
  echo "<form method=post id=mainform enctype='multipart/form-data' action=MoonEdit.php>";
  echo "<div class=tablecont><table width=90% border class=SideTable>\n";
  Register_AutoUpdate('Moon',$Mid);
  echo fm_hidden('id',$Mid);
  echo "<tr class=NotSide><td class=NotSide>PlanetId:<td class=NotSide>$Pid<td class=NotSide>Game<td class=NotSide>$GAMEID" .
       "<td class=NotSide>" . $GAME['Name'] . fm_text('System Ref',$N,'Ref',1,"class=NotSide");
  echo "<tr><td class=NotSide>Moon Id:<td class=NotSide>$Mid";
  echo "<tr>" . fm_text('Name',$M,'Name',8);  // TODO Image
  echo "<tr>" . fm_text('Short Name',$M,'ShortName');
  echo "<tr>" . fm_textarea('Description',$M,'Description',8,3);
  echo "<tr><td>Type:<td>" . fm_Select($PTNs,$M,'Type',1) . fm_number('Minerals',$M,'Minerals',1,"class=NotCSide"); 
  
  echo "<tr>" . fm_number('Orbital Radius',$M,'OrbitalRadius',1,"class=NotCSide") . "<td>Km = " . RealWorld($M,'OrbitalRadius'); 
  echo "<td rowspan=4 colspan=3>";
//  if ($N['Image']) echo "<img src='" . $P['Image'] . "'>";
  echo "<table><tr>";
    echo fm_DragonDrop(1,'Image','Moon',$Mid,$M,$Mode,'',1,'','Moon');
  echo "</table>";
  
  echo "<tr>" . fm_number('Period',$M,'Period',1,"class=NotCSide") . "<td>Hr = " . RealWorld($M,'Period');
  echo "<tr>" . fm_number('Gravity',$M,'Gravity',1,"class=NotCSide") . "<td>m/s<sup>2</sup> = " . RealWorld($M,'Gravity');
  echo "<tr>" . fm_number('Radius',$M,'Radius',1,"class=NotCSide") . "<td>Km = " . RealWorld($M,'Radius');
      
//  $heat = $N['Luminosity']/(($P['OrbitalRadius']*1000)^2);
  
  $NumDists = count($Ds);
  $dc=0;
  
  foreach ($Ds as $D) {
    $did = $D['id'];
    if ($dc++%4 == 0)  echo "<tr>";
    echo "<td>" . fm_Select($DTs, $D , 'Type', 1,'',"DistrictType-$did") . fm_number1('', $D,'Number', '','',"DistrictNumber-$did");
    };

  echo "<tr><td>Add District Type<td>" . fm_Select($DTs, NULL , 'Number', 1,'',"DistrictTypeAdd-$Mid");
  echo fm_number(($M['ProjHome']?"<a href=ProjHome.php?id=" . $M['ProjHome'] . ">Project Home</a>":"Project Home"),$M,'ProjHome');

  if (Access('God')) echo "<tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea>";
  echo "</table></div></form>\n";
}


function NameFind(&$thing) {
//var_dump($thing);
//return $thing['Name'];
  if (isset($thing['ShortName']) && $thing['ShortName']) return $thing['ShortName'];
  if (isset($thing['Name']) && $thing['Name']) return $thing['Name'];
  return '';
}

function UniqueRef($sid) {
  global $FACTION,$GAMEID;

//debug_print_backtrace();  
//  echo "Sid is $sid<p>";
  $cid = str_split(sprintf('%d', ($sid+54321)*48205429));
  if (isset($FACTION)) { $fid = str_split(sprintf('%d', ($FACTION['id']+12345)*303272303));
  } else { $fid = str_split(substr(time() . "1",6,6)*303272303); }
  
  return $GAMEID . $cid[5] . $fid[5] . $cid[6] . $fid[6] . $cid[7] . $fid[7];
}

function ReportEnd($N) {
  return $N['Ref'];//  UniqueRef($N['id']); // testing kluudge
}

?>
