<?php

/* $SurveyLevels = [0=>'Blind',1=>'No Sensors', 2=>'Minimal Scan', 3=>'A bit better', 4=>'Most Things', 5=>'Full Scan no control',
                 6=>'Full Scan under control', 10=>'GM Only']; */
 $SurveyLevels = [0=>'Blind', 1=>'No Sensors', 3=>'Unable to Scan', 5=>'Full Scan', 10=>'GM Only'];
 $FAnomalyStates = [-1=>'Can Find', 0=>'Unknown', 1=>'Found', 2=>'Can Analyse',
                     3=> 'Analysed, Reward unclaimed', 4=> 'Analysed, Reward Claimed'];
 $GAnomStates = ['Open','One Use','Completed','Removed'];
 $SurveyTypes = ['Passive','Space','Planetary'];

 global $SurveyLevels,$FAnomalyStates,$GAnomStates,$SurveyTypes;

 function Minerals4Planet() {
   if (Feature('Minerals')) {
     return eval(Feature('Minerals'));
   }
   return [1,1,2,2,2,3,3,3,4][rand(1,8)];
 }

 function Minerals4AsteroidBelt() {
   if (Feature('AsteroidMinerals')) {
     return eval(Feature('AsteroidMinerals'));
   }
   return rand(1,5);
 }
// System common code

function Dynamic_Update(&$N,$Tinc=0)  {
  global $GAME;
  // Period and distance dynamic update based on Turn number and date
  ///  Called as part of Turn Actions and every showing of system
  $G = 0.0000000000667431;
  $C = 299792458;
  $M1 = $N['Mass'];
  $M2 = $N['Mass2'];
  $e = 0.98171334;
  $pi = pi();
  $Friction =1.25;

// var_dump($G,$C,$M1,$M2,$e,$pi);

  $CurPeriod = $N['Period']*3600; // Hours
  $CurDist = $N['Distance']; // KM

//  $CurPeriod = 0.077385053686678*3600;
//  $CurDist = 85238.5273897988;

//var_dump($CurPeriod*3600);

  $Decay = (192*$pi*($G**(5/3)*$M1*$M2*($M1+$M2)**(-1/3))/((5*$C**5)*(1-$e**2)**(7/2)*(1+73/24*($e**2)+37/96*($e**4))*($CurPeriod/2*$pi)))*$Friction;
  $TurnDecay = $Decay*60*24*365;

//   echo "Decay = $Decay TurnDec=$TurnDecay<br>\n";
  $NewPeriod = $CurPeriod - $TurnDecay;
  $NewDistance = ((($NewPeriod/(2*$pi))**2)*$G*($M1+$M2))**(1/3)/1000;

//  echo "NewPeriod = $NewPeriod, NewDistance = $NewDistance<br\n";
//  Decay = // =(192*PI()*($B$1^(5/3)*$B$2*$B$3*($B$3+$B$2)^(-1/3))/(5*$B$4^5*(1-$B$5^2)^(7/2)*(1+73/24*$B$5^2+37/96*$B$5^4)*(E6/2*PI())))

  if ($Tinc) {

    $OldSpeed = 2*$pi*$CurDist*1000/$CurPeriod;
    $OldEnergy = ($M1+$M2)*$C*$C;

    $NewSpeed = 2*$pi*$NewDistance*1000/$NewPeriod;
    $LostEnergy = 0;


    $N['Period'] = $NewPeriod/3600;
    $N['Distance'] = $NewDistance;




    Put_System($N);
  } else {
    $tt = 3600*24*14;
    $Duration = (time() - $GAME['DateCompleted']);
// if (Access('GM')) echo "Duration: $Duration<br>CurP $CurPeriod<br>NewP $NewPeriod";
    $N['Period'] = ($NewPeriod*$Duration + $CurPeriod*($tt-$Duration))/($tt*3600);
    $N['Distance'] = ($NewDistance*$Duration + $CurDist*($tt-$Duration))/$tt;
  }
}

function RealWorld(&$Data,$fld) {
    $Star = (isset($Data['SystemId'])?1:0);
    $val = $Data[$fld] ?? 0;
    $Acc = "%0.2f";
    if (isset($Data['Flags']) && ($Data['Flags'] &1)) $Acc="%0.8f";
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
        return sprintf("$Acc Hours",$val);
      } elseif ($val > 2/60) {
        return sprintf("$Acc Minutes",$val*60);
      } else {
        return sprintf("$Acc Seconds",$val*3600);
      }
      return;

    case 'Gravity' :
      return sprintf("%0.2f G",$val/9.8);

    case 'Distance' :
      if ($val > 2e6) {
        return sprintf("%0.2f AU",$val/1.496e8);
      } else {
        return sprintf("$Acc x Radius of Stars",$val/($Data['Radius']+$Data['Radius2']));
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
    echo " Flags: 1=Dynamic, 2=Off Map<br>\n";
  }

  $FactNames = Get_Faction_Names();
  $Facts = Get_Factions();
  $Fact_Colours = Get_Faction_Colours();
  $Planets = Get_Planets($Sid);
  $PTNs = Get_PlanetTypeNames();
  $PTD = Get_PlanetTypes();
  $LinkLevels = Get_LinkLevels();
  $Ls = Get_Links($Ref);

  if ($N['Flags'] & 1) Dynamic_Update($N);

  echo "<form method=post id=mainform enctype='multipart/form-data' action=SysEdit.php>";
  echo "<div class=tablecont><table width=90% border class=SideTable>\n";
  Register_AutoUpdate('Systems',$Sid);
  echo fm_hidden('id',$Sid);
  echo "<tr class=NotSide><td class=NotSide>Id:<td class=NotSide>$Sid<td class=NotSide>Game<td class=NotSide>$GAMEID" .
       "<td class=NotSide>" . $GAME['Name'];
  echo "<tr class=NotSide>" . fm_text('Grid X',$N,'GridX',1,"class=NotSide") . fm_text('Grid Y',$N,'GridY',1,"class=NotSide") . fm_text('Ref',$N,'Ref',1,"class=NotSide");

  echo "<tr>" . fm_radio('Control',$FactNames ,$N,'Control','',1,'colspan=6','',$Fact_Colours,0);
  echo "<tr>" . fm_text('Name',$N,'Name',4);
  if (Feature ('OtherControl') && $Mode) echo "<td class=NotSide colspan=2>Other Control: " . fm_select($FactNames,$N,'HistoricalControl');
  echo "<tr>" . fm_text('Short Name',$N,'ShortName') . fm_text1('Nebulae',$N,'Nebulae',1,"class=NotCSide") .
     fm_number1('Category',$N,'Category',1,"class=NotSide") ;
    echo fm_number1('Flags',$N,'Flags');
  echo "<tr>" . fm_textarea('Description',$N,'Description',8,3);
  echo "<tr>" . fm_text('Type',$N,'Type',2,"class=NotCSide") . "<td rowspan=6 colspan=3>";
  echo "<table><tr>";
    echo fm_DragonDrop(1,'Image','System',$Sid,$N,$Mode,'',1,'','System');
  echo "</table>";
  echo "<tr>" . fm_text('Star Name',$N,'StarName',2);
  echo "<tr>" . fm_number('Radius',$N,'Radius',1,"class=NotCSide") . "<td>Km = " . RealWorld($N,'Radius');
  echo "<tr>" . fm_number('Mass',$N,'Mass',1,"class=NotCSide") . "<td>Kg = " . RealWorld($N,'Mass');
  echo "<tr>" . fm_number('Temperature',$N,'Temperature',1,"class=NotCSide") . "<td>K";
  echo "<tr>" . fm_number('Luminosity',$N,'Luminosity',1,"class=NotCSide") . "<td>W = " . RealWorld($N,'Luminosity');
  if ($N['Type2']) {
    echo "<tr>" . fm_text('2nd Star Type',$N,'Type2',2,"class=NotCSide") . "<td rowspan=6 colspan=3>";
    echo "<table><tr>";
      echo fm_DragonDrop(1,'Image2','System2',$Sid,$N,$Mode,'',1,'','System');
    echo "</table>";
    echo "<tr>" . fm_text('Star Name',$N,'StarName2',2);
    echo "<tr>" . fm_number('Radius',$N,'Radius2',1,"class=NotCSide") . "<td>Km = " . RealWorld($N,'Radius2') ;
    echo "<tr>" . fm_number('Mass',$N,'Mass2',1,"class=NotCSide") . "<td>Kg = " . RealWorld($N,'Mass2');
    echo "<tr>" . fm_number('Temperature',$N,'Temperature2',1,"class=NotCSide") . "<td>K" ;
    echo "<tr>" . fm_number('Luminosity',$N,'Luminosity2',1,"class=NotCSide") . "<td>W = " . RealWorld($N,'Luminosity2');
    echo "<tr>" . fm_number('Distance',$N,'Distance',1,"class=NotCSide step='any'") . "<td>Km = " . RealWorld($N,'Distance') ;
    echo "<tr>" . fm_number('Period',$N,'Period',1,"class=NotCSide step='any'") . "<td>Hr = " . RealWorld($N,'Period');
    if (Access('GM')) echo "<td><a href=SysEdit.php?id=$Sid&ACTION=RECALC>Recalc</a>";
  } elseif ($Mode) {
    echo "<tr>" . fm_text('2nd Star Type',$N,'Type2',2,"class=NotCSide");
  }

  if (Feature('SysTraits')) {
    echo "<tr>" . fm_text('Trait',$N,'Trait1',2) . "<td>" . fm_checkbox('Automated',$N,'Trait1Auto') . fm_Number('Concealment',$N,'Trait1Conceal');
    echo "<tr>" . fm_textarea('Description',$N,'Trait1Desc',5,2);
    echo "<tr>" . fm_text('Trait',$N,'Trait2',2) . "<td>" . fm_checkbox('Automated',$N,'Trait2Auto') . fm_Number('Concealment',$N,'Trait2Conceal');
    echo "<tr>" . fm_textarea('Description',$N,'Trait2Desc',5,2);
    echo "<tr>" . fm_text('Trait',$N,'Trait3',2) . "<td>" . fm_checkbox('Automated',$N,'Trait3Auto') . fm_Number('Concealment',$N,'Trait3Conceal');
    echo "<tr>" . fm_textarea('Description',$N,'Trait3Desc',5,2);
  }

  if (Access('God')) {
    if ($N['WorldList']) echo "<tr><td colspan=3>Controlled Planets/Moons: " . ($N['WorldList']??'');
    echo "</tbody><tfoot><tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea>";
  }
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
           . "<td>Heat: " . RealHeat($N,$P) . "<td>" . ($PTD[$P['Type']]['Hospitable']?'Yes':'No');
//      if (Access('God')) echo "<td>" . sqrt($P['OrbitalRadius']**3/($N['Mass']));
      }
    echo "</table></div>\n";
  }

  $LinkType = Feature('LinkMethod');
  if ($LinkType=='Gates') {
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

 // OLD CODE
 // echo "<tr><td><a href=LinkEdit.php?id=$Lid>#$Lid</a><td><a href=SysEdit.php?id=$ONid>$OSysRef - $OName</a><td>" . $LinkLevels[$L['Level']]['Name'];
      foreach ($Facts as $F) echo "<td>" . (isset($Know[$F['id']]) && $Know[$F['id']]['Known']?"Yes " . NameFind($Know):"No");
      echo "\n";
      }
    echo "</table>\n";
  } else if ($LinkType=='Wormholes') {
    echo "<h2>Wormholes</h2>";
    echo "Go to link to mark as Used, otherwise change scan levels.<p>";
    echo "<table border><tr><td>Link<td>To<td>Instability<td>Mod<td>Concealment";
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
      $EInst = $L['Instability'];
      if ($L['ThisTurnMod']) $EInst = max(1,$EInst+$L['ThisTurnMod']);

      echo "<tr><td><a href=LinkEdit.php?id=$Lid>" . $L['Name'] . "</a><td><a href=SysEdit.php?id=$ONid>$OSysRef - $OName</a><td>" . $L['Instability'] .
        "<td>" . $L['ThisTurnMod'] . "<td>" . $L['Concealment'] ;
      foreach ($Facts as $F) echo "<td>" . (isset($Know[$F['id']])?$Know[$F['id']]:"No");
      echo "\n";
    }
    echo "</table>\n";


  }
  $Anoms = Gen_Get_Cond('Anomalies',"GameId=$GAMEID AND SystemId=$Sid");
  if ($Anoms) {
    echo "<h2>Anomalies</h2>";
    foreach($Anoms as $A) {
      $Aid = $A['id'];
      echo "<a href=AnomalyEdit.php?id=$Aid>" . $A['Name'] . "</a></br>";
    }
  }

  $FactionSys = Gen_Get_Cond('FactionSystem',"SystemId=$Sid");
  if ($FactionSys) {
    echo "<h2>Factions Knowledge</h2>";
    echo "<table border><tr><td>Faction<td>Passive Scan Level<td>Space Scan Level<td>Planetary Scan Level";
    foreach ($FactionSys as $FS) {
      $fsid = $FS['id'];
      echo "<tr><td><a href=EditFactionSys.php?id=$fsid>" . ($Facts[$FS['FactionId']]['Name']??'Unknown') .
         "</a><td>" . $FS['ScanLevel'] . "<td>" . $FS['SpaceScan'] . "<td>" . $FS['PlanetScan'];
    }
    echo "</table>";
  }
  if (Access('GM')) {
    echo "<h2>Add Faction Knowledge</h2>";
    echo "<tr><td>" . fm_select($FactNames,$_REQUEST,'F');
    echo "<td><input type=submit name=ACTION value=ADDFACT>\n";
  }

  if (Access('GM')) {
    echo "<center><form method=post action=SysEdit.php>" . fm_hidden('id', $Sid) .
         "<input type=submit name=ACTION value='Add Planet' class=Button> ";
    if (!$Planets && $Mode==1) {
      echo "<input type=submit name=ACTION value='Auto Populate' class=Button>";
    } elseif ($Mode) {
      echo "<input type=submit name=ACTION value='Delete Planets' class=Button>";
    }
    echo "<input type=submit name=ACTION value='Redo Moons' class=Button>";
    echo "<input type=submit name=ACTION value='Delete System' class=Button>";
    echo fm_submit("ACTION",'Update Control');
    echo "<input type=submit name=ACTION value='Change Control To' class=Button>";
      echo fm_select($FactNames,$N,'NewControl');
    echo "</form></center>";
  }
  echo "<center><h2><a href=SurveyReport.php?id=$Sid>Survey Report</a>";
//  if (Access('God'))  echo ", <a href=SurveyReport.php?id=$Sid&F=1>Survey Report from Faction 1</a>"  ;
  echo "</h2></center>";

}

function Show_Planet(&$P,$Mode=0,$Buts=0) {
  global $GAME,$GAMEID;
  $Pid = $P['id'];
  if ($Mode) {
    echo "<span class=NotSide>Fields marked are not visible to factions.</span>";
    echo "  <span class=NotCSide>Marked are visible if set, but not changeable by factions.</span>";
  }
  echo "Attributes:1=Hide,2,4,8=Inherit System Traits1,2,3<br>";
  echo "Max Districts: 0 = no limit.  Max Offices:0 No limit, -1 include in district limit<p>";

  $FactNames = Get_Faction_Names();
  $Facts = Get_Factions();
  $Fact_Colours = Get_Faction_Colours();
  $DTs = Get_DistrictTypeNames();
  $Ds = Get_DistrictsP($Pid,1);
  $N = Get_System($P['SystemId']);
  $PTNs = Get_PlanetTypeNames();
  $PTD = Get_PlanetTypes();
  $Mns = Get_Moons($Pid);

  echo "<form method=post id=mainform enctype='multipart/form-data' action=PlanEdit.php>";
  echo "<div class=tablecont><table width=90% border class=SideTable>\n";
  Register_AutoUpdate('Planets',$Pid);
  echo fm_hidden('id',$Pid);
  echo "<tr class=NotSide><td class=NotSide>Id:<td class=NotSide>$Pid<td class=NotSide>Game<td class=NotSide>$GAMEID" .
       "<td class=NotSide>" . $GAME['Name'] . fm_text('System Ref',$N,'Ref',1,"class=NotSide");
  echo "<tr>" . fm_radio('Control',$FactNames ,$P,'Control','',1,'colspan=5','',$Fact_Colours,0);
  echo "<tr>" . fm_text('Name',$P,'Name',4) . fm_number('Colonise Mod',$P,'ColonyTweak');  // TODO Image
  echo "<tr>" . fm_text('Short Name',$P,'ShortName') . fm_number('Attributes',$P,'Attributes') . fm_number('Mined',$P,'Mined');
  echo "<tr>" . fm_number('Max Districts', $P,'MaxDistricts') . fm_number('Max Offices', $P,'MaxOffices');
  echo "<tr>" . fm_textarea('Description',$P,'Description',4,3);
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
  if (Access('GM')) echo "</tbody><tfoot><td><a href=PlanEdit.php?id=$Pid&ACTION=RECALC>Recalc</a>";
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
  $Tidy = 0;

  foreach ($Ds as $D) {
    $did = $D['id'];
    if ($dc++%3 == 0)  echo "<tr>";
    echo "<td>" . fm_Select($DTs, $D , 'Type', 1,'',"DistrictType-$did") . fm_number1('', $D,'Number', '',' class=Num3 ',"DistrictNumber-$did");
    if ($D['Number'] == 0) $Tidy = 1;
    echo fm_number0("&Delta; ",$D,'Delta','',' class=Num3 ',"DistrictDelta-$did");
    };

  echo "<tr><td>Add District Type<td>" . fm_Select($DTs, NULL , 'Number', 1,'',"DistrictTypeAdd-$Pid");
  echo fm_number(($P['ProjHome']?"<a href=ProjHomes.php?id=" . $P['ProjHome'] . ">Project Home</a>":"Project Home"),$P,'ProjHome');

  if (Feature('SysTraits')) {
    echo "<tr>" . fm_text('Trait',$P,'Trait1',2) . "<td>" . fm_checkbox('Automated',$P,'Trait1Auto') . fm_Number('Concealment',$P,'Trait1Conceal');
    echo "<tr>" . fm_textarea('Description',$P,'Trait1Desc',5,2);
    echo "<tr>" . fm_text('Trait',$P,'Trait2',2) . "<td>" . fm_checkbox('Automated',$P,'Trait2Auto') . fm_Number('Concealment',$P,'Trait2Conceal');
    echo "<tr>" . fm_textarea('Description',$P,'Trait2Desc',5,2);
    echo "<tr>" . fm_text('Trait',$P,'Trait3',2) . "<td>" . fm_checkbox('Automated',$P,'Trait3Auto') . fm_Number('Concealment',$P,'Trait3Conceal');
    echo "<tr>" . fm_textarea('Description',$P,'Trait3Desc',5,2);
  }

  if (Access('God')) echo "<tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea>";
  echo "</table></div></form>\n";


  if ($Buts) {
    echo "<center>" .
         "<form method=post action=SysEdit.php>" . fm_hidden('id', $P['SystemId']) .
         "<input type=submit name=NOACTION value='Back to System' class=Button> " .
         "</form></center>";

    if (Access('GM')) {
      echo "<center>" .
           "<form method=post action=PlanEdit.php>" . fm_hidden('id', $Pid);

      if ($PTD[$P['Type']]['Hospitable'] || ($PTD[$P['Type']]['Name'] == 'Gas Giant')) {
          echo "<input type=submit name=ACTION value='Add Editable Moon' class=Button> ";
      }

      echo "<input type=submit name=ACTION value='Delete Planet' class=Button> " .
           "<input type=submit name=ACTION value='Delete Moons' class=Button>";
      if ($Tidy) echo "<input type=submit name=ACTION value='Tidy Districts' class=Button>";

      echo fm_submit('Action','New Branch',0,"formaction=BranchEdit.php?Planid=$Pid");
      echo "</form></center>";
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
  echo "Attributes:1=Hide,2,4,8=Inherit System Traits1,2,3<br>";
  echo "Max Districts: 0 = no limit.  Max Offices:0 No limit, -1 include in district limit<p>";

  $Pid = $M['PlanetId'];
  $P = Get_Planet($Pid);
  $FactNames = Get_Faction_Names();
  $Facts = Get_Factions();
  $Fact_Colours = Get_Faction_Colours();
  $DTs = Get_DistrictTypeNames();
  $Ds = Get_DistrictsM($Mid,1);
  $N = Get_System($P['SystemId']);
  $PTNs = Get_PlanetTypeNames();
  $PTD = Get_PlanetTypes();

  echo "<form method=post id=mainform enctype='multipart/form-data' action=MoonEdit.php>";
  echo "<div class=tablecont><table width=90% border class=SideTable>\n";
  Register_AutoUpdate('Moons',$Mid);
  echo fm_hidden('id',$Mid);
  echo "<tr class=NotSide><td class=NotSide>PlanetId:<td class=NotSide>$Pid<td class=NotSide>Game<td class=NotSide>$GAMEID" .
       "<td class=NotSide>" . $GAME['Name'] . fm_text('System Ref',$N,'Ref',1,"class=NotSide");
  echo "<tr><td class=NotSide>Moon Id:<td class=NotSide>$Mid";
  echo "<tr>" . fm_radio('Control',$FactNames ,$M,'Control','',1,'colspan=6','',$Fact_Colours,0);
  echo "<tr>" . fm_text('Name',$M,'Name',8) . fm_number('Colonise Mod',$P,'ColonyTweak');  // TODO Image
  echo "<tr>" . fm_text('Short Name',$M,'ShortName') . fm_number('Attributes',$M,'Attributes') . fm_number('Mined',$M,'Mined');
  echo "<tr>" . fm_number('Max Districts', $P,'MaxDistricts') . fm_number('Max Offices', $P,'MaxOffices');
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
  $Tidy = 0;

  foreach ($Ds as $D) {
    $did = $D['id'];
    if ($dc++%4 == 0)  echo "<tr>";
    echo "<td>" . fm_Select($DTs, $D , 'Type', 1,'',"DistrictType-$did") . fm_number1('', $D,'Number', '',' class=Num3 ',"DistrictNumber-$did");
    if ($D['Number'] == 0) $Tidy = 1;
    echo fm_number0("&Delta; ",$D,'Delta','',' class=Num3 ',"DistrictDelta-$did");
    };

  echo "<tr><td>Add District Type<td>" . fm_Select($DTs, NULL , 'Number', 1,'',"DistrictTypeAdd-$Mid");
  echo fm_number(($M['ProjHome']?"<a href=ProjHomes.php?id=" . $M['ProjHome'] . ">Project Home</a>":"Project Home"),$M,'ProjHome');

  if (Feature('SysTraits')) {
    echo "<tr>" . fm_text('Trait',$M,'Trait1',2) . "<td>" . fm_checkbox('Automated',$M,'Trait1Auto') . fm_Number('Concealment',$M,'Trait1Conceal');
    echo "<tr>" . fm_textarea('Description',$M,'Trait1Desc',5,2);
    echo "<tr>" . fm_text('Trait',$M,'Trait2',2) . "<td>" . fm_checkbox('Automated',$M,'Trait2Auto') . fm_Number('Concealment',$M,'Trait2Conceal');
    echo "<tr>" . fm_textarea('Description',$M,'Trait2Desc',5,2);
    echo "<tr>" . fm_text('Trait',$M,'Trait3',2) . "<td>" . fm_checkbox('Automated',$M,'Trait3Auto') . fm_Number('Concealment',$M,'Trait3Conceal');
    echo "<tr>" . fm_textarea('Description',$M,'Trait3Desc',5,2);
  }

  if (Access('God')) echo "</tbody><tfoot><tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea>";
  echo "</table></div></form>\n";

  if ($Tidy) {
    echo "<center>" .
      "<form method=post action=MoonEdit.php>" . fm_hidden('id', $Mid) .
      "<input type=submit name=ACTION value='Tidy Districts' class=Button>";
    echo "</form></center>";
  }
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
  if (isset($FACTION['id'])) { $fid = str_split(sprintf('%d', ($FACTION['id']+12345)*303272303));
  } else { $fid = str_split(substr(time() . "1",6,6)*303272303); }

  return $GAMEID . $cid[5] . $fid[5] . $cid[6] . $fid[6] . $cid[7] . $fid[7];
}

function ReportEnd($N) {
  return $N['Ref'];//  UniqueRef($N['id']); // testing kluudge
}

function System_Name(&$N,$Fid=0) {
  $pname = NameFind($N);
  $Ref = $N['Ref'];
  if ($Fid) {
    $FS = Get_FactionSystemFS($Fid, $N['id']);
    if (!empty($FS['Name'])) {
      $Fname = NameFind($FS);
      if ($pname != $Fname) {
        if (strlen($pname) > 1) {
          $pname = $Fname . " ( $pname | $Ref ) ";
        } else {
          $pname = $Fname . " ( $Ref ) ";
        }
      } else {
        $pname .= " ( $Ref ) ";
      }
    } else if ($pname) {
      $pname .= " ( $Ref ) ";
    } else {
      $pname = $Ref;
    }
  } else if ($pname) {
    $pname .= " ( $Ref ) ";
  } else {
    $pname = $Ref;
  }
  return $pname;
}

function Survey_Save($Fid,$Sid,&$Survey) {
  global $GAME,$GAMEID;
  $dir = "Games/$GAMEID/Factions/$Fid";
  if (!is_dir($dir)) mkdir($dir);
  file_put_contents("$dir/$Sid", $Survey);
}

function Survey_Read($Fid,$Sid) {
  global $GAME,$GAMEID;
  $file = "Games/$GAMEID/Factions/$Fid/$Sid";
//  if (!file_exists($file)) return 0;
  return file_get_contents($file);
}

function Planet_Save($Fid,$Pid,&$Survey) {
  global $GAME,$GAMEID;
  $dir = "Games/$GAMEID/Factions/$Fid/Planet";
  if (!is_dir($dir)) mkdir($dir);
  file_put_contents("$dir/$Pid", $Survey);
}

function Moon_Save($Fid,$Mid,&$Survey) {
  global $GAME,$GAMEID;
  $dir = "Games/$GAMEID/Factions/$Fid/Moon";
  if (!is_dir($dir)) mkdir($dir);
  file_put_contents("$dir/$Mid", $Survey);
}

function PlanetScanBlob($Sid,$Fid,$SpaceLevel,$PlanetLevel,&$Syslocs,$GM=0) {
  global $LinkStates,$GAMEID,$FAnomalyStates;
  $PTD = Get_PlanetTypes();
  $DistTypes = Get_DistrictTypes();

  $blobs = [];

  $N = Get_System($Sid);
  $Ref = $N['Ref'];

  $Ps = Get_Planets($Sid);
  if (!$Ps) return '';
  foreach ($Ps as $P) {
    $ptxt = '';

    $Pid = $P['id'];
    $Mns = [];
    if ($P['Moons']) $Mns = Get_Moons($Pid);
    if ($P['Minerals']) {
      if (( $PTD[$P['Type']]['Hospitable'] && ($PlanetLevel>0)) ||
        (!$PTD[$P['Type']]['Hospitable'] && ($SpaceLevel>0))) {
          $ptxt .= "It has a minerals rating of <b>" . $P['Minerals'] . "</b><p> ";
        }
    }

    if ($PTD[$P['Type']]['Hospitable'] && ($PlanetLevel>0)) {
      $ptxt .= "It has a colonisation rating of <b>" . (Feature('BaseColonise',10) + $P['ColonyTweak']) . "</b><p>";
    }

    if ($ptxt) {
      $blobs[]= "A$Pid";
      $blobs[]= $ptxt;
      $ptxt = '';
    }

    $Ds = Get_DistrictsP($Pid);
    if ($Ds) {
      $ptxt .= "<p>Districts: ";
      $dc = 0;
      foreach ($Ds as $DD) {
        if (!isset($DistTypes[$DD['Type']])) continue;
        if ($dc++) $ptxt .=  ", ";
        $ptxt .= $DistTypes[$DD['Type']]['Name'] . ": " . $DD['Number'];
      }
      echo "<p>";

      $World = Gen_Get_Cond1('Worlds',"ThingType=1 AND ThingId=$Pid");
      if ($World) {
        $Offs = Gen_Get_Cond('Offices',"World=" . $World['id']);
        if ($Offs) {
          $Clean = [];
          foreach ($Offs as $i=>$Of) {
            $Org = Gen_Get('Organisations',$Of['Organisation']);
            $OrgType = Gen_Get('OfficeTypes',$Org['OrgType']);
            if ($OrgType) {
              if ($OrgType['Props']&1) continue; // Hidden
              $Clean[]= $Org['Name'] . " (" . $OrgType['Name'] . ") ";
            }
          }

          if ($Clean) {
            $ptxt .=  "<p>Offices: " . implode(', ',$Clean) . "<p>";
          }
        }
      }
    }

    // Traits
    $PlanTrait = 0;
    for($i=1;$i<4;$i++) {
      if ($P["Trait$i"] && ($P["Trait$i" . "Conceal"] <= $PlanetLevel)) {
        if ($PlanTrait++ == 0) {
          $ptxt .=  "<h2>Planet Traits:</h2>";
        }
        //            var_dump($P,$PlanetLevel);
        $ptxt .=  "Planet " . $P['Name'] . " has the trait: " . $P["Trait$i"] . "<br>" . ParseText($P["Trait$i" . "Desc"]) . "<p>";
      }
    }

    if ($ptxt) {
      $blobs[]= "B$Pid";
      $blobs[]= $ptxt;
      $ptxt = '';
    }

    if ($Mns) {
      //Moons of
      foreach ($Mns as $M) {
        $Mid = $M['id'];

        if ($M['Minerals']) {
          if (( $PTD[$M['Type']]['Hospitable'] && ($PlanetLevel>0)) ||
            (!$PTD[$M['Type']]['Hospitable'] && ($SpaceLevel>0))) {
              $ptxt .= "It has a minerals rating of <b>" . $M['Minerals'] . "</b><p>.  ";
            }
        }
        if ($PTD[$P['Type']]['Hospitable'] && ($PlanetLevel>0)) {
          $ptxt .= "It has a colonisation rating of <b>" . (Feature('BaseColonise',10) + $M['ColonyTweak']) . "</b><p>";
        }

        if ($ptxt) {
          $blobs[]= "C$Mid";
          $blobs[]= $ptxt;
          $ptxt = '';
        }

        // Districts
        $Ds = Get_DistrictsM($Mid);

        if ($Ds) { // &&
          $ptxt .= "<p>Districts: ";
          $dc = 0;
          foreach ($Ds as $D) {
            if ($dc++) $ptxt .= ", ";
            $ptxt .= ($DistTypes[$D['Type']]['Name']??'Unknown') . ": " . $D['Number'];
          }
          $ptxt .= "<p>";

          $World = Gen_Get_Cond1('Worlds',"ThingType=2 AND ThingId=$Mid");
          if ($World) {
            $Offs = Gen_Get_Cond('Offices',"World=" . $World['id']);
            if ($Offs) {
              $Clean = [];
              foreach ($Offs as $i=>$Of) {
                $Org = Gen_Get('Organisations',$Of['Organisation']);
                $OrgType = Gen_Get('OfficeTypes',$Of['OrgType']);

                if ($OrgType['Props']&1) continue; // Hidden
                $Clean[]= $Org['Name'] . " (" . $OrgType['Name'] . ") ";

              }

              if ($Clean) {
                $ptxt .= "<p>Offices: " . implode(', ',$Clean) . "<p>";
              }
            }
          }
        }
      }

      // Traits
      $PlanTrait = 0;
      for($i=1;$i<4;$i++) {
        if ($M["Trait$i"] && ($M["Trait$i" . "Conceal"] <= $PlanetLevel)) {
          if ($PlanTrait++ == 0) {
            $ptxt .=  "<h2>Planet Traits:</h2>";
          }
          $ptxt .=  "Moon " . $M['Name'] . " has the trait: " . $M["Trait$i"] . "<br>" .
            ParseText($M["Trait$i" . "Desc"]) . "<p>";
        }
      }

      if ($ptxt) {
        $blobs[]= "D$Pid";
        $blobs[]= $ptxt;
        $ptxt = '';
      }
    }
  }

  $Anoms = Gen_Get_Cond('Anomalies',"GameId=$GAMEID AND SystemId=$Sid");
  $Shown = 0;
  $AnStateCols = ['White','Lightgreen','Yellow','Pink','Green'];

  if ($Anoms) {
    foreach($Anoms as $Aid=>$A) {
      $Loc = 0; // Space
      $LocCat = intdiv($A['WithinSysLoc'],100);
      if (($A['ScanLevel']<=$PlanetLevel) && ($LocCat ==2 || $LocCat == 4)) {
        if ($A['Complete'] > 1) continue; // Completed or Removed
        if (!$GM){
          $FA = Gen_Get_Cond1('FactionAnomaly',"AnomalyId=$Aid AND FactionId=$Fid");
          if (empty($FA['id'])) {
            if ($A['ScanLevel'] <0) continue;
          }
        }

        if (!$Shown) {
          $ptxt .=  "<h2>Ground Anomalies</h2>" .
            "For up to date information on their state look at your <a href=PAnomalyList.php>Anomaly List</a>.";

          $Shown = 1;
        }


        $ptxt .=  "<br><h3>Anomaly: " . $A['Name'] . "</h3>location: " .
           ($Syslocs[$A['WithinSysLoc']]? $Syslocs[$A['WithinSysLoc']]: "Space") . "<p>";
        if ($A['Description']) $ptxt .=  "Description: " . ParseText($A['Description']) . "<p>";
        if (!$GM) {
          $FA = Gen_Get_Cond1('FactionAnomaly',"AnomalyId=$Aid AND FactionId=$Fid");
          if (!isset($FA['id'])) {
            $FA = ['State' => 1, 'FactionId'=>$Fid, 'AnomalyId'=>$Aid, 'Progress'=>0];
            Gen_Put('FactionAnomaly',$FA);
          } else if (($FA['State']??0) < 1) {
            $FA['State'] = 1;
            Gen_Put('FactionAnomaly',$FA);
          }
          $ptxt .=  "<span style='Background:" . $AnStateCols[$FA['State']] . ";'>" . $FAnomalyStates[$FA['State']] . "</span>";
 //         $ptxt .=  "<br>Progress: " . ($FA['Progress']??0) . " / " . $A['AnomalyLevel'];

          if (($FA['State'] >= 3) && $A['Completion']) {
            $ptxt .=  "Complete: " . ParseText($A['Completion']) . "<p>";
          }
        }
      }
    }

    if ($ptxt) {
      $blobs[]= "Z0";
      $blobs[]= $ptxt;
      $ptxt = '';
    }
  }

  return implode('::::',$blobs);
}

function Record_PlanetScan(&$FS,$DEBUG=0) {
  global $GAME;
  $N = Get_System($FS['SystemId']);
  $SLocs = Within_Sys_Locs($N);

  $Res = PlanetScanBlob($FS['SystemId'],$FS['FactionId'],$FS['SpaceScan'],$FS['PlanetScan'],$SLocs,0,$DEBUG);
  if (!$DEBUG) {
    $FS['PlanetSurvey'] = $Res;
    $FS['PlanetTurn'] = $GAME['Turn'];
    Put_FactionSystem($FS);
  }
  return $Res;
}

function SpaceScanBlob($Sid,$Fid,$SpaceLevel,$PlanetLevel,&$Syslocs,$GM=0,$DEBUG=0) {
  global $LinkStates,$GAMEID,$FAnomalyStates,$GAME;

  $txt = '';
  $LFromtxt = '';

  $N = Get_System($Sid);
  $Ref = $N['Ref'];

  $Ls = Get_Links($Ref);
  $txt .= "<BR CLEAR=ALL><h2>There are " . Feature('LinkRefText','Stargate') . "s to:</h2><ul>\n";

  foreach ($Ls as $Lid=>$L) {
    $OSysRef = ($L['System1Ref']==$Ref? $L['System2Ref']:$L['System1Ref']);
    $ON = Get_SystemR($OSysRef);
    $EInst = $L['Instability'];
    if ($L['ThisTurnMod']) $EInst = max(1,$EInst+$L['ThisTurnMod']);
    if ($GM ) {
      $LinkKnow = 1;
    } else {
 //     $FLK = Gen_Get_Cond1('FactionLinkKnown',"FactionId=$Fid AND LinkId=". $L['id']);

      $LinkKnow = LinkVis($Fid,$L['id'],$Sid);
      if (!$LinkKnow) {
        $FLs = Get_Factions4Link($Lid);
        $mtch = [];
        if (preg_match('/From (.*)/',($FLs[$Fid]??''),$mtch) && ($mtch[1] != $Ref)) {
          $LFromtxt .= "<li>Link " .  ($L['Name']?$L['Name']:"#" . $L['id']) . ' from ' .
             ReportEnd($ON) . " Instability: " . $L['Instability'] . " Concealment: " . $L['Concealment'];
          if ($L['ThisTurnMod']) $LFromtxt .= " - Currently Instability $EInst (Turn " . $GAME['Turn'] . ")";
        }
        if ($DEBUG) $txt .= "<li>Link" . ($L['Name']?$L['Name']:"#" . $L['id']) . " NOT KNOWN";
        continue;
      }
    }

    $txt .=  "<li>Link " . ($L['Name']?$L['Name']:"#" . $L['id']) . " ";

    if ($LinkKnow) { // WRONG WRONG  - needs to be a test of Far end system known on feature X
      //        $name = NameFind($L);
      //        if ($name) echo " ( $name ) ";
      $txt .=  " to " . ReportEnd($ON) . " Instability: " . $L['Instability'] . " Concealment: " . $L['Concealment'];
      // " level " . $LinkLevels[abs($L['Level'])]['Colour'];
    } else {
      $txt .=  " to an unknown location." . " Instability: " . $L['Instability'] . " Concealment: " . $L['Concealment'];
      //Level " .  $LinkLevels[abs($L['Level'])]['Colour'];
    }
    if ($L['Status'] != 0) $txt .= " <span class=Red>" . $LinkStates[$L['Status']] . "</span>";
    if ($L['ThisTurnMod']) $txt .= " - Currently Instability $EInst (Turn " . $GAME['Turn'] . ")";

  }
  $txt .=  "</ul><p>\n";
  if ($LFromtxt) {
    $txt .= "<h2>There are also these " . Feature('LinkRefText','Stargate') . "s:<h2>" .
      "You know that come here.  You do not yet know how to use them from this end<P><ul>$LFromtxt</ul><p>";
  }

// Known Space ANOMALIES
  $Anoms = Gen_Get_Cond('Anomalies',"GameId=$GAMEID AND SystemId=$Sid");
  $Shown = 0;
  $AnStateCols = ['White','Lightgreen','Yellow','Pink','Green'];

  if ($Anoms) {
    foreach($Anoms as $Aid=>$A) {
      $Loc = 0; // Space
      $LocCat = intdiv($A['WithinSysLoc'],100);
      if ($LocCat ==2 || $LocCat == 4) $Loc=1; // Ground;
      if (($Loc == 1) && $A['VisFromSpace']) $Loc=3; // Vis From Space

      if ($A['ScanLevel']>=0 && ($A['ScanLevel']<=$SpaceLevel) && ($Loc ==0 || $Loc==3)) {
        if ($A['Complete'] > 1) continue; // Completed or Removed

        if (!$GM){
          $FA = Gen_Get_Cond1('FactionAnomaly',"AnomalyId=$Aid AND FactionId=$Fid");
          if (empty($FA['id'])) {
            if ($A['ScanLevel'] <0) continue;
          }
        }

        if (!$Shown) {
          $txt .=  "<h2>Anomalies Visible from Space</h2>" .
             "For up to date information on their state look at your <a href=PAnomalyList.php>Anomaly List</a>.";
          $Shown = 1;
        }


        $txt .=  "<br><h3>Anomaly: " . $A['Name'] . "</h3>Location: " . ($Syslocs[$A['WithinSysLoc']]? $Syslocs[$A['WithinSysLoc']]: "Space") . "<p>";
        if ($A['Description']) $txt .=  "Description: " . ParseText($A['Description']) . "<p>";

        if ((($FA['State']??0) >= 3) && $A['Completion']) {
          $ptxt .=  "Complete: " . ParseText($A['Completion']) . "<p>";
        }

        if (!$GM) {
          $FA = Gen_Get_Cond1('FactionAnomaly',"AnomalyId=$Aid AND FactionId=$Fid");
          if (!isset($FA['id'])) {
            $FA = ['State' => 1, 'FactionId'=>$Fid, 'AnomalyId'=>$Aid, 'Progress'=>0];
            Gen_Put('FactionAnomaly',$FA);
          } else if (($FA['State']??0) < 1) {
            $FA['State'] = 1;
            Gen_Put('FactionAnomaly',$FA);
          }
        }
      }
    }
  }

  if ($SpaceLevel>0) {
    $SysTrait = 0;
    for($i=1;$i<4;$i++) {
      if ($N["Trait$i"] && ($N["Trait$i" . "Conceal"] <= $SpaceLevel)) {
        if ($SysTrait++ == 0) {
          $txt .=  "<h2>System Traits:</h2>";
        }
        $txt .=  "System has the trait: " . $N["Trait$i"] . "<br>" . ParseText($N["Trait$i" . "Desc"]) . "<p>";
      }
    }
  }

  return $txt;
}

function Record_SpaceScan(&$FS,$DEBUG=0) {
  global $GAME;
  $N = Get_System($FS['SystemId']);
  $SLocs = Within_Sys_Locs($N);

  $Res = SpaceScanBlob($FS['SystemId'],$FS['FactionId'],$FS['SpaceScan'],$FS['PlanetScan'],$SLocs,0,$DEBUG);
  if (!$DEBUG) {
    $FS['SpaceSurvey'] = $Res;
    $FS['SpaceTurn'] = $GAME['Turn'];
    Put_FactionSystem($FS);
  }
  return $Res;
}

function System_Owner($Sid) {
  // Verifies/Sets System Owner - Called when Colony, Outpost created/destroyed, as needed from System by GM

  $N = Get_System($Sid);
  $Ps = Get_Planets($Sid);
  $Facts = Get_Factions();

  $Cont = 0;
  foreach ($Ps as $P) {
    if ($P['Attributes'] & 1) continue; // Hidden
    if ($P['Control']) {
      if ($P['Control'] != $N['Control']) {
        $N['Control'] = $P['Control'];
        $FS = Get_FactionSystemFS($N['Control'],$Sid);
        if ($FS['Name']??'') $N['Name'] = $FS['Name'];
        Put_System($N);
  //      var_dump("Change 1 to " .$N['Control'] );
        return "Control of " . $N['Ref'] . " now " . $Facts[$N['Control']]['Name'];
      }
      $FS = Get_FactionSystemFS($N['Control'],$Sid);
      if (($FS['Name']??'') && ($FS['Name'] != $N['Name'])){
        $N['Name']= $FS['Name'];
        Put_System($N);
      }

      return;
    }
    $Ms = Get_Moons($P['id']);
    foreach ($Ms as $M) {
      if ($M['Attributes'] & 1) continue; // Hidden
      if ($M['Control'] != $N['Control']) {
        $N['Control'] = $M['Control'];
        $FS = Get_FactionSystemFS($N['Control'],$Sid);
        if ($FS['Name']??'') $N['Name'] = $FS['Name'];
        Put_System($N);
   //     var_dump("Change 3 to " .$N['Control'] );
        return "Control of " . $N['Ref'] . " now " . ($Facts[$N['Control']??0]['Name']??'Unknown');
      }
  //    var_dump("No change 4");
      if (($FS['Name']??'') && ($FS['Name'] != $N['Name'])){
        $N['Name'] = $FS['Name'];
        Put_System($N);
      }
      return;
    }
  }
//var_dump("Checking for outpost");
  $TTypes = Get_ThingTypes();
  include_once("ThingLib.php");
  $Outpost = Gen_Get_Cond1('Things',"Type=" . array_flip(NamesList( $TTypes))['Outpost'] . " AND SystemId=$Sid AND BuildState=" . BS_COMPLETE);
  if ($Outpost) {
    if ($Outpost['Whose'] != $N['Control']) {
      $N['Control'] = $Outpost['Whose'];
      $FS = Get_FactionSystemFS($N['Control'],$Sid);
      if ($FS['Name']??'') $N['Name'] = $FS['Name'];
      Put_System($N);
 //     var_dump("Change 5 to " .$N['Control'] );
      return "Control of " . $N['Ref'] . " now " . $Facts[$N['Control']]['Name'];
    }
//    var_dump("No change 6");
    if (($FS['Name']??'') && ($FS['Name'] != $N['Name'])){
      $N['Name']= $FS['Name'];
      Put_System($N);
    }
    return;
  }
  if ($N['Control']) {
    $N['Control'] = 0;
    Put_System($N);
//    var_dump("Change 7 to None " );

    return "Control of " . $N['Ref'] . " now none";
  }
 // var_dump("No change 8");

}


