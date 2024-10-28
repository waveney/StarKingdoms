<?php

// Scan all plantets/moon's/Thing's for districts and deep space construction

  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("PlayerLib.php");
  include_once("SystemLib.php");
  include_once("ProjLib.php");
  include_once("OrgLib.php");

  global $FACTION,$ARMY,$ARMIES;

  if (Access('Player')) {
    if (!$FACTION) {
      if (!Access('GM') ) Error_Page("Sorry you need to be a GM or a Player to access this");
    } else {
      $Fid = $FACTION['id'];
      $Faction = &$FACTION;
      if (!Access('GM') && $Faction['TurnState'] > 2) Player_Page();
    }
  }
  if (Access('GM') ) {
    A_Check('GM');
    if (isset( $_REQUEST['F'])) {
      $Fid = $_REQUEST['F'];
    } else if (isset( $_REQUEST['f'])) {
      $Fid = $_REQUEST['f'];
    } else if (isset( $_REQUEST['id'])) {
      $Fid = $_REQUEST['id'];
    }
    if (isset($Fid)) $Faction = Get_Faction($Fid);
  }

  dostaffhead("New Projects for faction");

  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
      case 'NEWSHIP':
        $Limit = Has_Tech($Fid,'Ship Construction');
      case 'NEWARMY':
        if ($_REQUEST['ACTION'] == 'NEWARMY') $Limit = Has_Tech($Fid,Feature('MilTech'));
      case 'NEWAGENT':
         if ($_REQUEST['ACTION'] == 'NEWAGENT') $Limit = Has_Tech($Fid,'Intelligence Operations');
        $Ptype = $_REQUEST['p'];
        $Turn = $_REQUEST['t'];
        $Hi = $_REQUEST['Hi'];
        $Di = $_REQUEST['Di'];
        $DT = $_REQUEST['DT'];
        $Place = base64_decode($_REQUEST['pl']);

        $TTypes = Get_ThingTypes();
        $Things = Get_Things_Cond($Fid, 'DesignValid=1 ');
        if ($_REQUEST['ACTION'] == 'NEWSHIP') {
          foreach ($Things as $Tid=>$T) if (($T['Type'] == 0) || (($TTypes[$T['Type']]['Properties'] & THING_HAS_SHIPMODULES) == 0)) unset($Things[$Tid]);
        } else if ($_REQUEST['ACTION'] == 'NEWARMY') {
          foreach ($Things as $Tid=>$T) if (($T['Type'] == 0) || (($TTypes[$T['Type']]['Properties'] & THING_HAS_ARMYMODULES) == 0)) unset($Things[$Tid]);
        } else if ($_REQUEST['ACTION'] == 'NEWAGENT') {
          foreach ($Things as $Tid=>$T) if (($T['Type'] == 0) || (($TTypes[$T['Type']]['Properties'] & THING_HAS_GADGETS) == 0)) unset($Things[$Tid]);
        }

        if (!$Things) {
          echo "<h2>Sorry you do not have any plans for these - go to <a href=ThingPlan.php?F=$Fid>Thing Planning</a> first</h2>";
          break;
        }

        $NameList = [];
        foreach ($Things as $T) {
          if ($T['Level'] > $Limit) continue;
          $pc = Proj_Costs($T['Level']);
          $Extra = '';
          if ($_REQUEST['ACTION'] == 'NEWARMY' && Has_Tech($Fid,'Efficient Robot Construction')) $pc[0] = max(1, $pc[0] - $T['Level']);
          if ($_REQUEST['ACTION'] == 'NEWSHIP' && Has_Tech($Fid,'Space Elevator')) $pc[0] = max(1, $pc[0] - $T['Level']);
          if ($T['BuildFlags'] & BUILD_FLAG1) {
            $Extra = "  Plus " . $T['Level'] . ' ' . Feature('Currency3','Unknown');
          }
          $NameList[$T['id']] = $T['Name'] . (empty($T['Class'])?'': ", a " . $T['Class']) . " ( Level " . $T['Level'] . " ); $Place;" .
            "Cost: " . $pc[1] .  " Needs " . $pc[0] . " progress.  $Extra";
          }

        if ($NameList) {
          echo "<h2>Select a deign to make</h2>";
          echo "If it is in planning, you are build that, if already built then it will be a copy.<br>";

          echo "<form method=post action=ProjDisp.php?ACTION=NEW&id=$Fid&p=$Ptype&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT>";
          echo fm_select($NameList,$_REQUEST,'ThingId',1," onchange=this.form.submit()") . "\n<br>";
        } else {
          echo "<h2>Sorry you do not have any plans for these - go to <a href=ThingPlan.php?F=$Fid>Thing Planning</a> first</h2>";
        }
        echo "<h2><a href=ProjDisp.php?id=$Fid>Cancel</a></h2>\n";
        dotail();

      break;

    }
  }




  echo "<h1>New Project</h1>";

  $Homes = Get_ProjectHomes($Fid);
  $DistTypes = Get_DistrictTypes();
  $ProjTypes = Get_ProjectTypes();
  $OrgTypes = Get_OrgTypes();
  $PTi = [];
  foreach ($ProjTypes as $PT) $PTi[$PT['Name']] = $PT['id'];
//var_dump($PTi);

  $ThingTypes = Get_ThingTypes();
  $GM = Access('GM');

  $DiCall = $_REQUEST['Di'];
  $Di = abs($DiCall);
  $Hi = $_REQUEST['Hi'];
  $NoC = 0;

  $PHx = 1;
  $Dis = [];
  $Offices = [];
  $MaxDists = 0;
  $ThingLevel = 1;
  foreach ($Homes as &$H) {
    $Hix = $H['id'];
    $NoC = 0;
    switch ($H['ThingType']) {
    case 1: // Planet
      $PH = Get_Planet($H['ThingId']);
      $Dists = Get_DistrictsP($H['ThingId']);
      $Sid = $PH['SystemId'];
      break;
    case 2: // Moon
      $PH = Get_Moon($H['ThingId']);
      $Dists = Get_DistrictsM($H['ThingId']);
      $Plan = Get_Planet($PH['PlanetId']);
      $Sid = $Plan['SystemId'];
      break;
    case 3: // Thing
      $PH = Get_Thing($H['ThingId']);
      if ($ThingTypes[$PH['Type']]['Properties'] & THING_CAN_DO_PROJECTS) {
        $ORY = 0;
        foreach($DistTypes as $DT) if ($DT['Name'] == 'Orbital Repair') $ORY = $DT['id'];
        $Dists = [$ORY=>['HostType'=>3,'HostId'=>$PH['id'],'Type'=>$ORY,'Number'=>1, 'id'=>-1]];
        $NoC = 1;
        break;
      }
      $Dists = Get_DistrictsT($H['ThingId']);
      if (!$Dists) {
        $H['Skip'] = 1;
        continue 2;  // Remove things without districts
      }
      $MaxDists = $PH['MaxDistricts'];
      $ThingLevel = $PH['Level'];
      $Sid = $PH['SystemId'];
      break;
    }


    //TODO Construction and Districts...

//    if ($NoC != 1) $Dists[] = ['HostType'=>-1, 'HostId' => $PH['id'], 'Type'=> -1, 'Number'=>0, 'id'=>-$PH['id']];
    foreach ($Dists as $D) {
      if ($D['Type'] > 0 && (($DistTypes[$D['Type']]['Props'] &2) == 0)) continue;
      if (($D['Type'] < 0) && ($PH['Type'] != $Faction['Biosphere']) &&
         ($PH['Type'] != $Faction['Biosphere2']) && ($PH['Type'] != $Faction['Biosphere3']) && (Has_Tech($Fid,3)<2)) continue;
      $Dix = $D['id'];

      if (($Hi == $Hix) && ($DiCall == $Dix)) {
        $HDists[$Hix] = $Dists;
        break 2;  // $D is now the relevant one
      }
      $Dis[$Hix][] = $Dix;
      }

    $HDists[$Hix] = $Dists;
    }

  $Turn = $_REQUEST['t'];

  $World = Gen_Get_Cond1('Worlds',"Home=$Hi");
  if ($World) {
    $Offices = Get_OfficesByType($World['id']);
  }


  $Stage = (isset($_REQUEST['STAGE'])? $_REQUEST['STAGE'] : 0);

  $Where =  ($D['Type'] > 0 ? $DistTypes[$D['Type']]['Name'] : 'Construction');

  $Place = " on " . $PH['Name'];
  $pl = "&pl=" . base64_encode($Place);

// echo "ZZ: $Where<p>";

 // var_dump($HDists);

  echo "<form method=post action=ProjNew.php>";
  echo fm_hidden('t',$Turn) . fm_hidden('Hi',$Hi) . fm_hidden('Di',$Di) . fm_hidden('DT',$D['Type']);
  $DT = $D['Type'];

//echo "DT = $DT<p>";
// echo "Doinfg $Where<p>";
  switch ($Where) {


  case 'Construction':
  case 'Industrial' :
    echo "<h2>Select Construction Project:</h2><p>";

    $PlanCon = PlanConst($Fid,$World['id']);
    $CurOff = $CurDists = 0;
    if ($MaxDists > 0) {

      foreach ($HDists[$Hi] as $DD) if ($DD['Type'] > 0) $CurDists += $DD['Number'];
      $CurOff = Count($Offices);

      echo "Maximum Districts: $MaxDists<br>Current Districts: $CurDists<br>";
      if (Feature('Orgs')) echo "Offices: $CurOff<br>";
    }

//         echo "Maximum Districts: $MaxDists<br>Current Districts: $CurDists<br>";

      if ($MaxDists==0 || (($CurOff + $CurDists) < $MaxDists)) {
        $DTs = Get_DistrictTypes();
        $DNames = [];
//var_dump("ONE", $HDists[$Hi],$Hi);
        foreach ($DTs as $DTz) {
          if (($D['Number']??0) > $PlanCon) continue;
          if ($DTz['Gate'] && eval("return " . $DTz['Gate'] . ";" )) {
            $DNames[$DTz['id']] = $DTz['Name'];

            $Lvl = 0;
//var_dump("TWO",$DTz,$HDists[$Hi]);
            foreach ($HDists[$Hi] as $D) {
//echo "<br>Checking "; var_dump($D);
              if ($D['Type'] == $DTz['id']) {
                $Lvl = $D['Number']-$D['Delta'];
                break;
              }
            }

//var_dump("THREE",$DTz,$HDists[$Hi]);
            // TODO bug if you already have that level in the pipeline - Add check to Turns Ready
            if ($Lvl >= $PlanCon ) { // $DTz['MaxNum'])) {
              if (!$GM) continue;
              echo "Not allowed (GM only):";
            }
            $Lvl++;
            $pc = Proj_Costs($Lvl);
            if (Has_Trait($Fid,"On the Shoulders of Giants")) {
              if ($DTz['Name'] == 'Mining') {
                $pc[1] = 0; // Mining
              } else if ($Lvl>1 ) {
                $HomeW = $FACTION['HomeWorld'];
                $World = Get_World($HomeW);
                if ($World['Home'] == $Hi) {
                  $pc = Proj_Costs($Lvl-1);
                }
              }
            }
            if (Has_PTraitH($Hi,'Irradiated Wasteland')) { $pc[0]++; }
            elseif (Has_Trait($Fid,"Military Society") && ($DTz['Name'] == 'Military')) $pc = Proj_Costs($Lvl-1);

            echo "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=" . $PTi['Construction'] .
                  "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT&Sel=" . $DTz['id'] .
                  "&Name=" . base64_encode("Build " . $DTz['Name'] . " District $Lvl$Place") . "&L=$Lvl&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .
                  "Build " . $DTz['Name'] . " District $Lvl; $Place; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>\n";

          }
        }

        if (Feature('Orgs') && count($Offices) < $PlanCon) {
          echo "<h2>Build Offices</h2>";
          $Lvl = count($Offices)+1;
          $pc = Proj_Costs($Lvl);
          $Orgs = Gen_Get_Cond('Organisations',"Whose=$Fid");
          if (count($Offices)< count($Orgs)) {
            foreach ($Orgs as $OrgId=>$Org) {
              foreach ($Offices as $O) if ($O['Organisation'] == $OrgId) continue 2;
              echo "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=" . $PTi['Construction'] .
                    "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT&Sel=-$OrgId" .
                    "&Name=" . base64_encode("Build " . $Org['Name'] . " Office $Place") .
                    "&L=$Lvl&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .
                    "Build " . $Org['Name'] . " Office; $Place; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>\n";
            }
          }
          // Get local offices, Get orgs.
          // Make offices to any orgs w/o an office
          // Office for new org
          $ValidOrgs = [];
          foreach($OrgTypes as $ot=>$O) {
            if ($O['Gate'] && !eval("return " . $O['Gate'] . ";" )) continue;
            $ValidOrgs[$ot] = $O['Name'];
          }

          echo "<h3>Office for a new Organisation</h3>";
          echo "<table>";
          echo "<tr><td>Org Type:" . fm_select($ValidOrgs,$O,'OrgType',0,'',"NewOrgType");
          echo fm_text1('',$O,'NewOrgName',2,'','placeholder="New Organisation Name"');
          echo "<tr><td colspan=4>" . fm_basictextarea($O, 'NewOrgDescription',5,3,"placeholder='New Organisation Description' style='width=70%'");
          $SocPs = SocPrinciples($Fid);
          echo "<tr><td colspan=4>Social Priniple (Religious / Ideological only)" . fm_select($SocPs,$O,'NewOrgSocialPrinciple');
          echo " You can edit this name and description from your list of organisations, until the first office is built.";
          echo "</table><br>";
          echo "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEWORG&id=$Fid&p=" . $PTi['Construction'] .
            "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT&Sel=-$ot" .
            "&Name=" . base64_encode("Build New Orgs Office $Place") .
            "&L=$Lvl&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .
            "Build New Orgs Office; $Place; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>\n";

        }
      }


      // find devestation locally if > 0 then
      // show project
      $H = $Homes[$Hi];
      if ($H['Devastation'] > 0) {
      echo "<h2>Rebuild and Repair</h2>";
        $pc = Proj_Costs(1);
        echo "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=" . $PTi['Rebuild and Repair'] .
                  "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT&Sel=" . $DTz['id'] .
                  "&Name=" . base64_encode("Rebuild and Repair") . "&L=1&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .
                  "Rebuild and Repair $Place; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>\n";
      }

      if (Feature('WarpGates')) {
        echo "<h2>Construct Warp Gate</h2>";

        $pc = Proj_Costs(4);
        echo "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=" . $PTi['Construct Warp Gate'] . "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT&Sel=0" .
                  "&Name=" . base64_encode("Build Warp Gate$Place"). "&L=4&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .
                  "Build Warp Gate $Place; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";
      }


    echo "<h2>Research Planetary Construction</h2><p>";
      $OldPc = Has_Tech($Fid,3);
      $Lvl = $OldPc+1;
      $pc = Proj_Costs($Lvl);
      echo "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=" . $PTi['Research Planetary Construction'] .
                "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT&Sel=3" .
                "&Name=" . base64_encode("Research Planetary Construction $Lvl$Place"). "&L=$Lvl&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .
                "Research Planetary Construction $Lvl; $Place; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";



      $Rbuts = [];
      $FactTechs = Get_Faction_Techs($Fid, $Turn);
      $Techs = Get_TechsByCore($Fid);
      foreach ($Techs as $T) {
        if ($T['Cat'] == 0 || (isset($FactTechs[$T['id']]) && $FactTechs[$T['id']]['Level'])) continue;
        if (!isset($FactTechs[$T['PreReqTech']])) continue;
        if ($T['PreReqTech']!=3) continue;
        if ( ($FactTechs[$T['PreReqTech']]['Level']<$T['PreReqLevel'] ) ) continue;

        $Lvl = $T['PreReqLevel'];
        $pc = Proj_Costs($Lvl);
        $Rbuts[] = "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=" . $PTi['Research Supplemental Planetary Construction Tech'] .
                "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT&Sel=" . $T['id'] .
                "&Name=" . base64_encode("Research " . $T['Name'] . $Place) . "&L=$Lvl&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .
                "Research " . $T['Name'] . "; $Place; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";
      }

      if ($Rbuts) {
        echo "<h2>Research Supplimental Planetary Construction Technology</h2>";
        foreach ($Rbuts as $rb) echo $rb;
      }

      if (Has_Tech($Fid,'Adianite Production Methods')) {
        $Lvl = 1;
        $pc = Proj_Costs($Lvl);
        echo "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=" . $PTi['Produce Adianite'] .
                "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT&Sel=3" .
                "&Name=" . base64_encode("Produce 1 unit of Adianite $Lvl$Place"). "&L=$Lvl&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .
                "Produce 1 unit of Adianite $Lvl; $Place; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";
        $Lvl = 2;
        $pc = Proj_Costs($Lvl);
        echo "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=" . $PTi['Produce Adianite'] .
                "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT&Sel=3" .
                "&Name=" . base64_encode("Produce 4 units of Adianite $Lvl$Place"). "&L=$Lvl&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .
                "Produce 4 units of Adianite $Lvl; $Place; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";

      }

    break;

  case 'Academic':

    echo "<h2>Research Core Technology</h2>";
      $FactTechs = Get_Faction_Techs($Fid, $Turn);
      $CTs = Get_CoreTechsByName();
      foreach ($CTs as $TT) {
        if (isset($FactTechs[$TT['id']]['Level'])) {
          $Lvl = $FactTechs[$TT['id']]['Level']+1;
        } else {
          $Lvl = 1;
        }
        $pc = Proj_Costs($Lvl);
        echo "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=" .
                $PTi['Research Core Technology'] . "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT&Sel=" . $TT['id'] .
                "&Name=" . base64_encode("Research " . $TT['Name'] . " $Lvl$Place"). "&L=$Lvl&C=" . $pc[1] . "&PN=" . $pc[0] ."'>" .
                "Research " . $TT['Name'] . " $Lvl; $Place; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";
        }

    echo "<h2>Research Supplimental Technology</h2>";
      $Techs = Get_TechsByCore($Fid);
      foreach ($Techs as $T) {
        if ($T['Cat'] == 0 || (isset($FactTechs[$T['id']]) && $FactTechs[$T['id']]['Level'])) continue;
        if (!isset($FactTechs[$T['PreReqTech']]) ) continue;
        if ( ($FactTechs[$T['PreReqTech']]['Level']<$T['PreReqLevel'] ) ) continue;
        if ($T['PreReqTech2'] && ((! isset($FactTechs[$T['PreReqTech2']])) || $FactTechs[$T['PreReqTech2']]== 0)) continue;
        if ($T['PreReqTech3'] && ((! isset($FactTechs[$T['PreReqTech3']])) || $FactTechs[$T['PreReqTech3']]== 0)) continue;
        $Lvl = $T['PreReqLevel'];
        $pc = Proj_Costs($Lvl);
        echo "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=" . $PTi['Research Supplemental Technology'] .
                "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT&Sel=" . $T['id'] .
                "&Name=" . base64_encode("Research " . $T['Name'] . $Place) . "&L=$Lvl&C=" . $pc[1] . "&PN=" . $pc[0] ."'>" .
                "Research " . $T['Name'] . "; $Place; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";
      }

      if (Feature('TechSharing')) {
        echo "<h2>Share Technology</h2>";

//      echo "This is manual at present. Please put this in your turn orders<p>";
        echo "</form>";
        echo "<form method=post action='ProjDisp.php?ACTION=NEW&id=$Fid&p=" . $PTi['Share Technology'] . "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT'>";

        $Shares = [];
        foreach ($CTs as $TT) {
          $Tid = $TT['id'];
          for($Lvl = 1; $Lvl <= $FactTechs[$Tid]['Level']; $Lvl++) {
            $pc = Proj_Costs($Lvl-1);
            $Shares["$Tid:$Lvl"] = $TT['Name'] . " at level $Lvl; Cost " . $pc[1] . " Needs " . $pc[0] . " progress";
          }
        }

        foreach ($Techs as $T) {
          if ($T['Cat'] == 0 || !isset($FactTechs[$T['id']]) ) continue;
          if (!isset($FactTechs[$T['PreReqTech']]) ) continue;
          $Tid = $T['id'];
          $Lvl = $T['PreReqLevel'];
          if ($Lvl < 1) continue;
          $pc = Proj_Costs($Lvl-1);
          $Shares["$Tid:$Lvl"] = $T['Name'] . " at level $Lvl; Cost " . $pc[1] . " Needs " . $pc[0] . " progress";
        }

        $Factions = Get_Factions();
        $Facts = Get_FactionFactions($Fid);
        $FactList = [];
        foreach ($Facts as $Fi=>$F) {
          $FactList[$Fi] = $Factions[$Fi]['Name'];
        }
        echo "Tecnology: " . fm_select($Shares,$_REQUEST,"Tech2Share") . " share with: " .fm_select($FactList,$_REQUEST,"ShareWith",1);
        echo "<button class=projtype type=submit>Share</button>";
        echo "</form><p>";
      }
    echo "<h2>Analyse</h2>";
      echo "These projects will be defined by a GM<p>";
      echo "<form method=post action='ProjDisp.php?ACTION=NEW&id=$Fid&p=" . $PTi['Analyse'] . "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT'>";
      echo "Name of project - meaningfull to you and the GM:" . fm_text0('',$_REQUEST,'AnalyseText'). fm_number0(' Level ',$_REQUEST,'Level');
      echo "<button class=projtype type=submit>Analyse</button>";
      echo "</form><p>";


//    echo "<h2>Decipher Alien Language</h2>";
//    echo "Not in this game<p>";
      break;

      if (HasTrait($Fid,"It's worth taking a long time to say it")) {
        echo "<h2>Academic Contemplation</h2>";
        $Lvl = 2;
        $pc = Proj_Costs($Lvl);
        echo "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=" .
        $PTi['Academic Contemplation'] . "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT&Sel=" . $TT['id'] .
        "&Name=" . base64_encode('Academic Contemplation' . " $Lvl$Place"). "&L=$Lvl&C=" . $pc[1] . "&PN=" . $pc[0] ."'>" .
        'Academic Contemplation' . " $Lvl; $Place; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";
      }


  case 'Shipyard':
      echo "<h2>Build a Ship</h2>";

      echo "This action is to build an already designed ship.  If you want a new design please go to <a href=ThingPlan.php>The Thing Planning Tool</a> first.<p>\n";
      echo "<button class=projtype type=submit formaction='ProjNew.php?ACTION=NEWSHIP&id=$Fid&p=10&t=$Turn&Hi=$Hi&Di=$Di$pl&DT=$DT'>Build a new ship$Place</button><p>";



      echo "<h2>Research Ship Construction</h2><p>";
        $OldPc = Has_Tech($Fid,7);
        $Lvl = $OldPc+1;
        $pc = Proj_Costs($Lvl);
        echo "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=" . $PTi['Research Ship Construction'] .
                "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT&Sel=7" .
                "&Name=" . base64_encode("Research Ship Construction $Lvl$Place"). "&L=$Lvl&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .
                "Research Ship Construction $Lvl; $Place; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";

      $Rbuts = [];
      $FactTechs = Get_Faction_Techs($Fid, $Turn);
      $Techs = Get_TechsByCore($Fid);
      foreach ($Techs as $T) {
        if ($T['Cat'] == 0 || (isset($FactTechs[$T['id']]) && $FactTechs[$T['id']]['Level'])) continue;
        if (!isset($FactTechs[$T['PreReqTech']])) continue;
        if ($T['PreReqTech']!=7) continue;
        if ( ($FactTechs[$T['PreReqTech']]['Level']<$T['PreReqLevel'] ) ) continue;

        $Lvl = $T['PreReqLevel'];
        $pc = Proj_Costs($Lvl);
        $Rbuts[] = "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=" . $PTi['Research Supplemental ship Tech'] .
                "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT&Sel=" . $T['id'] .
                "&Name=" . base64_encode("Research " . $T['Name'] . $Place) . "&L=$Lvl&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .
                "Research " . $T['Name'] . "; $Place; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";
      }

      if ($Rbuts) {
        echo "<h2>Research Supplimental Ship Technology</h2>";
        foreach ($Rbuts as $rb) echo $rb;
      }

      if (Has_Trait($Fid,'Grow Modules')) {
        echo "<h2>Grow Modules</h2>";
// var_dump($CurLevel);
        $ShipYards = $HDists[$Hi][3]['Number'];
        $Grow = min(max((Has_Tech($Fid,'Ship Construction')-1), 1),$ThingLevel)*$ThingLevel; // Consider ship yards rather than the tech
        $pc = Proj_Costs(1);
        echo "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=" . $PTi['Grow Modules'] . "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT" .
                "&Name=" . base64_encode("Grow $Grow Modules" . $Place) . "&L=1&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .
                "Grow $Grow Modules " . "; $Place; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";

        echo "<h2>Grow Districts</h2>";
          $DTs = Get_DistrictTypes();
          foreach([3,5] as $DTp) {
            $Lvl = 0;

            foreach ($HDists[$Hi] as $D) {
              if ($D['Type'] == $DTp) {
                $Lvl = $D['Number'];
                break;
              }
            }

            $Lvl++;
            $pc = Proj_Costs($Lvl);
            echo "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=" . $PTi['Grow District'] . "&t=$Turn&Hi=$Hi&Di=$Di&Sel=$DTp&DT=$DT" .
                  "&Name=" . base64_encode("Build " . $DTs[$DTp]['Name'] . " District $Lvl$Place") . "&L=$Lvl&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .
                  "Build " . $DTs[$DTp]['Name'] . " District $Lvl; $Place; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";

          }
      }
      if (Feature('RefitRepairSep')) {// THIS MUST be AFTER the simple buttons as the form gets lost
        $RRPs = ['Refit','Repair'];

        $HSys = $Homes[$Hi]['SystemId'];
        $HLoc = $Homes[$Hi]['WithinSysLoc'];
        $TTs = Get_ThingTypes();
        $Things = Get_Things_Cond($Fid," SystemId=$HSys AND ( BuildState=3 OR BuildState=2) "); // Get all things at xx then filter  by type == Ship & WithinSysLoc
        $RepShips = [];
        $Level1 = 0;
        $Count = 0;
        $MaxLvl = Has_Tech($Fid,'Ship Construction');


        foreach ($Things as $T) {
          if (($TTs[$T['Type']]['Properties'] & THING_HAS_SHIPMODULES) && ($T['Level'] <= $MaxLvl)) {
            //          if ($T['WithinSysLoc'] < 2 || $T['WithinSysLoc'] == $HLoc || $T['WithinSysLoc'] == ($HLoc-100)) {
            $RepShips[$T['id']] = $T['Name'] . " - level " . $T['Level'];
            if ($T['Level'] == 1) $Level1++;
            $Count++;
          }
          //        }
        }


        $Factions = Get_Factions();
        $FFdata = Get_FactionFactionsCarry($Fid);
        foreach($FFdata as $FC) {
          if ($FC['Props'] & 0xf000) {
            $OThings = Get_Things_Cond($FC['FactionId1']," SystemId=$HSys AND ( BuildState=3 OR BuildState=2) AND Level<=$MaxLvl "); // Warp Gates
            foreach ($OThings as $T) {
              if ($TTs[$T['Type']]['Properties'] & THING_HAS_SHIPMODULES) {
                $RepShips[$T['id']] = $T['Name'] . " - level " . $T['Level'] . " (" . $Factions[$T['Whose']]['Name'] . ")";
                if ($T['Level'] == 1) $Level1++;
                $Count++;
              }
            }
          }
        }

        foreach($RRPs as $RP){

          $pc = Proj_Costs(1);
          if ($Count) {
            if ($Count == 1) {
              foreach ($RepShips as $tid=>$Name) { // TODO Levls of ships need tweaking
                echo "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=" . $PTi[$RP] . "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT&Sel=$tid" .
                "&Name=" . base64_encode("$RP $Name" ) . "&L=1&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .
                "$RP $Name $Place; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";
              }
            } else if ($Level1 < 2) {
              echo "</form><form method=post action='ProjDisp.php?ACTION=NEW&id=$Fid&p=" . $PTi[$RP] . "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT'>";
              echo "Select the ship to $RP: " . fm_select($RepShips, $_REQUEST, 'Sel', 0) ;
              echo "<button class=projtype type=submit>$RP</button></form>";
            } else {
              echo "</form><form method=post action='ProjDisp.php?ACTION=NEW&id=$Fid&p=" . $PTi[$RP] . "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT'>";
              echo "You may select <b>2</b> level 1 ships or 1 ship of level 2 or more. (For PCs and Linux hold down Ctrl to select 2nd ship)<p>";
              echo "Select the ship to $RP: " . fm_select($RepShips, $_REQUEST, 'Sel', 0, ' multiple ','Sel2[]') ;
              echo "<button class=projtype type=submit>$RP</button><form>";
            }

          } else {
            echo "No ships are currently near the yard that need $RP" . "ing<p>";
          }
        }
        break;



      } else {
        echo "<h2>Refit and Repair</h2>"; // OLD

        $HSys = $Homes[$Hi]['SystemId'];
        $HLoc = $Homes[$Hi]['WithinSysLoc'];
        $TTs = Get_ThingTypes();
        $Things = Get_Things_Cond($Fid," SystemId=$HSys AND ( BuildState=3 OR BuildState=2) "); // Get all things at xx then filter  by type == Ship & WithinSysLoc
        $RepShips = [];
        $Level1 = 0;
        $Count = 0;
        $MaxLvl = Has_Tech($Fid,'Ship Construction');

        foreach ($Things as $T) {
          if (($TTs[$T['Type']]['Properties'] & THING_HAS_SHIPMODULES) && ($T['Level'] <= $MaxLvl)) {
  //          if ($T['WithinSysLoc'] < 2 || $T['WithinSysLoc'] == $HLoc || $T['WithinSysLoc'] == ($HLoc-100)) {
              $RepShips[$T['id']] = $T['Name'] . " - level " . $T['Level'];
              if ($T['Level'] == 1) $Level1++;
              $Count++;
            }
  //        }
        }

        $Factions = Get_Factions();
        $FFdata = Get_FactionFactionsCarry($Fid);
        foreach($FFdata as $FC) {
          if ($FC['Props'] & 0xf000) {
            $OThings = Get_Things_Cond($FC['FactionId1']," SystemId=$HSys AND ( BuildState=3 OR BuildState=2) AND Level<=$MaxLvl "); // Warp Gates
            foreach ($OThings as $T) {
              if ($TTs[$T['Type']]['Properties'] & THING_HAS_SHIPMODULES) {
                $RepShips[$T['id']] = $T['Name'] . " - level " . $T['Level'] . " (" . $Factions[$T['Whose']]['Name'] . ")";
                if ($T['Level'] == 1) $Level1++;
                $Count++;
              }
            }
          }
        }


        $pc = Proj_Costs(1);
        if ($Count) {
          if ($Count == 1) {
            foreach ($RepShips as $tid=>$Name) {
              echo "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=" . $PTi['Refit and Repair'] . "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT&Sel=$tid" .
                  "&Name=" . base64_encode("Refit and Repair " . $Name ) . "&L=1&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .
                  "Refit and Repair $Name $Place; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";
            }
          } else if ($Level1 < 2) {
            echo "</form><form method=post action='ProjDisp.php?ACTION=NEW&id=$Fid&p=" . $PTi['Refit and Repair'] . "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT'>";
            echo "Select the ship to refit and repair: " . fm_select($RepShips, $_REQUEST, 'Sel', 0) ;
            echo "<button class=projtype type=submit>Refit and Repair</button></form>";
          } else {
            echo "</form><form method=post action='ProjDisp.php?ACTION=NEW&id=$Fid&p=" . $PTi['Refit and Repair'] . "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT'>";
            echo "You may select <b>2</b> level 1 ships or 1 ship of level 2 or more. (For PCs and Linux hold down Ctrl to select 2nd ship)<p>";
            echo "Select the ship to refit and repair: " . fm_select($RepShips, $_REQUEST, 'Sel', 0, ' multiple ','Sel2[]') ;
            echo "<button class=projtype type=submit>Refit and Repair</button><form>";
          }

        } else {
          echo "No ships are currently near the yard that need repairing<p>";
        }

        break;
      }
  case 'Military':
      echo "<h2>Train a $ARMY</h2>";
//      echo "Not yet<p>";
      echo "This action is to build an already designed $ARMY.  If you want a new design please go to <a href=ThingPlan.php>The Thing Planning Tool</a> first.<p>\n";
      echo "<button class=projtype type=submit formaction='ProjNew.php?ACTION=NEWARMY&id=$Fid&p=" . $PTi["Train $ARMY"] .
           "&t=$Turn&Hi=$Hi&Di=$Di$pl&DT=$DT'>Train a new $ARMY$Place</button><p>";


      echo "<h2>Research " . Feature('MilTech') . "</h2><p>";
        $OldPc = Has_Tech($Fid,8);
        $Lvl = $OldPc+1;
        $pc = Proj_Costs($Lvl);
        echo "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=" .
                $PTi['Research ' . Feature('MilTech')] .
                "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT&Sel=8" .
                "&Name=" . base64_encode("Research " . Feature('MilTech') . " on $Lvl$Place") . "&L=$Lvl&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .
                "Research " . Feature('MilTech') . " $Lvl; $Place; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";

      $Rbuts = [];
      $FactTechs = Get_Faction_Techs($Fid);
      $Techs = Get_TechsByCore($Fid);
      foreach ($Techs as $T) {
        if ($T['Cat'] == 0 ||  (isset($FactTechs[$T['id']]) && $FactTechs[$T['id']]['Level'])) continue;
        if (!isset($FactTechs[$T['PreReqTech']])) continue;
        if ($T['PreReqTech']!=8) continue;
        if ( ($FactTechs[$T['PreReqTech']]['Level']<$T['PreReqLevel'] ) ) continue;

        $Lvl = $T['PreReqLevel'];
        $pc = Proj_Costs($Lvl);
        $Rbuts[] = "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=" . $PTi["Research Supplemental $ARMY Tech"] .
                "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT&Sel=" . $T['id'] .
                "&Name=" . base64_encode("Research " . $T['Name'] .  $Place) . "&L=$Lvl&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .
                "Research " . $T['Name'] . "; $Place; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";
      }

     if ($Rbuts) {
       echo "<h2>Research Supplimental $ARMY Technology</h2>";
       foreach ($Rbuts as $rb) echo $rb;
     }


      echo "<h2>Re-equip and Reinforce $ARMY</h2>"; // THIS MUST be AFTER the simple buttons as the form gets lost
      echo "You can only do this to $ARMIES on the same planet.<p>";
      $HSys = $Homes[$Hi]['SystemId'];
      $HLoc = $Homes[$Hi]['WithinSysLoc'];
      $TTs = Get_ThingTypes();
      $Things = Get_Things_Cond($Fid," SystemId=$HSys AND BuildState=3 "); // Get all things at xx then filter  by type == Ship & WithinSysLoc
      $RepShips = [];
      $Level1 = 0;
      $Count = 0;
      foreach ($Things as $T) {
        if ($TTs[$T['Type']]['Properties'] & THING_HAS_ARMYMODULES) {
          if ($T['WithinSysLoc'] == $HLoc ) {
            $RepShips[$T['id']] = $T['Name'] . " - level " . $T['Level'];
            if ($T['Level'] == 1) $Level1++;
            $Count++;
          }
        }
      }


      $pc = Proj_Costs(1);
      if ($Count) {
        if ($Count == 1) {
          foreach ($RepShips as $tid=>$Name) {
            echo "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=" . $PTi['Re-equip and Reinforce'] .
                "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT&Sel=$tid" .
                "&Name=" . base64_encode("Re-equip and Reinforce " . $Name ) . "&L=1&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .
                "Refit and Repair $Name $Place; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";
          }
        } else if ($Level1 < 2) {
          echo "</form><form method=post action='ProjDisp.php?ACTION=NEW&id=$Fid&p=" . $PTi['Re-equip and Reinforce'] . "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT'>";
          echo "Select the $ARMY to Re-equip and Reinforce: " . fm_select($RepShips, $_REQUEST, 'Sel', 0) ;
          echo "<button class=projtype type=submit>Re-equip and Reinforce</button></form>";
        } else {
          echo "</form><form method=post action='ProjDisp.php?ACTION=NEW&id=$Fid&p=" . $PTi['Re-equip and Reinforce'] . "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT'>";
          echo "You may select <b>2</b> level 1 $ARMY or 1 $ARMY of level 2 or more. (For PCs and Linux hold down Ctrl to select 2nd $ARMY)<p>";
          echo "Select the $ARMY to Re-equip and Reinforce: " . fm_select($RepShips, $_REQUEST, 'Sel', 0, ' multiple ','Sel2[]') ;
          echo "<button class=projtype type=submit>Re-equip and Reinforce</button><form>";
        }

      } else {
        echo "No armies are currently there<p>";
      }

      if (Has_Trait($Fid,"I Don't Want To Die")) {
        echo "<h2>Re-equip $ARMY</h2>"; // THIS MUST be AFTER the simple buttons as the form gets lost
        echo "You can only do this to $ARMIES on the same planet.<p>";
        $HSys = $Homes[$Hi]['SystemId'];
        $HLoc = $Homes[$Hi]['WithinSysLoc'];
        $TTs = Get_ThingTypes();
        $Things = Get_Things_Cond($Fid," SystemId=$HSys AND BuildState=3 "); // Get all things at xx then filter  by type == Ship & WithinSysLoc
        $RepShips = [];
        $Level1 = 0;
        $Count = 0;
        foreach ($Things as $T) {
          if ($TTs[$T['Type']]['Properties'] & THING_HAS_ARMYMODULES) {
            if ($T['WithinSysLoc'] == $HLoc ) {
              $RepShips[$T['id']] = $T['Name'] . " - level " . $T['Level'];
              if ($T['Level'] == 1) $Level1++;
              $Count++;
            }
          }
        }


        $pc = Proj_Costs(1);
        if ($Count) {
          if ($Count == 1) {
            foreach ($RepShips as $tid=>$Name) {
              echo "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=" . $PTi['Re-equip'] .
              "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT&Sel=$tid" .
              "&Name=" . base64_encode("Re-equip and Reinforce " . $Name ) . "&L=1&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .
              "Refit and Repair $Name $Place; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";
            }
          } else if ($Level1 < 2) {
            echo "</form><form method=post action='ProjDisp.php?ACTION=NEW&id=$Fid&p=" . $PTi['Re-equip'] . "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT'>";
            echo "Select the $ARMY to Re-equip: " . fm_select($RepShips, $_REQUEST, 'Sel', 0) ;
            echo "<button class=projtype type=submit>Re-equip and Reinforce</button></form>";
          } else {
            echo "</form><form method=post action='ProjDisp.php?ACTION=NEW&id=$Fid&p=" . $PTi['Re-equip'] . "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT'>";
            echo "You may select <b>2</b> level 1 $ARMY or 1 $ARMY of level 2 or more. (For PCs and Linux hold down Ctrl to select 2nd $ARMY)<p>";
            echo "Select the $ARMY to Re-equip: " . fm_select($RepShips, $_REQUEST, 'Sel', 0, ' multiple ','Sel2[]') ;
            echo "<button class=projtype type=submit>Re-equip and Reinforce</button><form>";
          }

        } else {
          echo "No armies are currently there<p>";
        }
      }


      break;

  case 'Intelligence':
      echo "<h2>Train an Agent</h2>";
//      echo "Not yet<p>";
      echo "This action is to build an already designed Agent.  If you want a new design please go to <a href=ThingPlan.php>The Thing Planning Tool</a> first.<p>\n";
      echo "<button class=projtype type=submit formaction='ProjNew.php?ACTION=NEWAGENT&id=$Fid&p=" . $PTi['Train Agent'] .
           "&t=$Turn&Hi=$Hi&Di=$Di$pl&DT=$DT'>Train an agent$Place</button><p>";



      echo "<h2>Research Intelligence Operations</h2><p>";
        $OldPc = Has_Tech($Fid,4);
        $Lvl = $OldPc+1;
        $pc = Proj_Costs($Lvl);
        echo "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=" . $PTi['Research Intelligence Operations'] .
                "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT&Sel=4" .
                "&Name=" . base64_encode("Research Intelligence Operations $Lvl$Place"). "&L=$Lvl&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .
                "Research Intelligence Operations $Lvl; $Place; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";

        $Rbuts = [];
        $FactTechs = Get_Faction_Techs($Fid, $Turn);
        $Techs = Get_TechsByCore($Fid);
        foreach ($Techs as $T) {
          if ($T['Cat'] == 0 ||  (isset($FactTechs[$T['id']]) && $FactTechs[$T['id']]['Level'])) continue;
          if (!isset($FactTechs[$T['PreReqTech']])) continue;
          if ($T['PreReqTech']!=4) continue;
          if ( ($FactTechs[$T['PreReqTech']]['Level']<$T['PreReqLevel'] ) ) continue;

          $Lvl = $T['PreReqLevel'];
          $pc = Proj_Costs($Lvl);
          $Rbuts[] = "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=" . $PTi['Research Supplemental Intelligence Tech'] .
                "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT&Sel=" . $T['id'] .
                "&Name=" . base64_encode("Research " . $T['Name'] . $Place) . "&L=$Lvl&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .
                "Research " . $T['Name'] . "; $Place; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";
        }

        if ($Rbuts) {
          echo "<h2>Research Supplimental Intelligence Technology</h2>";
          foreach ($Rbuts as $rb) echo $rb;
        }
      echo  "<h2>Seek Enemy Agents</h2>";
        echo "</form><form method=post action=ProjDisp.php?ACTION=NEW&id=$Fid&p=" . $PTi['Seek Enemy Agents'] . "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT>";
        echo fm_number0('Level',$_REQUEST,'Level');
        echo "<button class=projtype type=submit>Seek</button></form>";

      break;

  case 'Orbital Repair':
      echo "<h2>Refit and Repair</h2>"; // THIS MUST be AFTER the simple buttons as the form gets lost

      echo "You can only do this to ships at the same planet.<p>";
      $HSys = $Homes[$Hi]['SystemId'];
      $HLoc = $Homes[$Hi]['WithinSysLoc'];
      $TTs = Get_ThingTypes();
      $Things = Get_Things_Cond($Fid," SystemId=$HSys AND ( BuildState=3 OR BuildState=2) "); // Get all things at xx then filter  by type == Ship & WithinSysLoc
      $RepShips = [];
      $Level1 = 0;
      $Count = 0;
      $MaxLvl = Has_Tech($Fid,'Ship Construction');

      foreach ($Things as $T) {
        if ($TTs[$T['Type']]['Properties'] & THING_HAS_SHIPMODULES) {
//          if ($T['WithinSysLoc'] < 2 || $T['WithinSysLoc'] == $HLoc || $T['WithinSysLoc'] == ($HLoc-100)) {
            $RepShips[$T['id']] = $T['Name'] . " - level " . $T['Level'];
            if ($T['Level'] == 1) $Level1++;
            $Count++;
          }
//        }
      }

      $Factions = Get_Factions();
      $FFdata = Get_FactionFactionsCarry($Fid);
      foreach($FFdata as $FC) {
        if ($FC['Props'] & 0xf000) {
          $OThings = Get_Things_Cond($FC['FactionId1']," SystemId=$HSys AND ( BuildState=3 OR BuildState=2) AND Level<=$MaxLvl "); // Warp Gates
          foreach ($OThings as $T) {
            if ($TTs[$T['Type']]['Properties'] & THING_HAS_SHIPMODULES) {
              $RepShips[$T['id']] = $T['Name'] . " - level " . $T['Level'] . " (" . $Factions[$T['Whose']]['Name'] . ")";
              if ($T['Level'] == 1) $Level1++;
              $Count++;
            }
          }
        }
      }

      $pc = Proj_Costs(1);
      if ($Count) {
        if ($Count == 1) {
          foreach ($RepShips as $tid=>$Name) {
            echo "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=" . $PTi['Refit and Repair'] . "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT&Sel=$tid" .
                "&Name=" . base64_encode("Refit and Repair " . $Name ) . "&L=1&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .
                "Refit and Repair $Name $Place; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";
          }
        } else if ($Level1 < 2) {
          echo "</form><form method=post action='ProjDisp.php?ACTION=NEW&id=$Fid&p=" . $PTi['Refit and Repair'] . "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT'>";
          echo "Select the ship to refit and repair: " . fm_select($RepShips, $_REQUEST, 'Sel', 0) ;
          echo "<button class=projtype type=submit>Refit and Repair</button></form>";
        } else {
          echo "</form><form method=post action='ProjDisp.php?ACTION=NEW&id=$Fid&p=" . $PTi['Refit and Repair'] . "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT'>";
          echo "You may select <b>2</b> level 1 ships or 1 ship of level 2 or more. (For PCs and Linux hold down Ctrl to select 2nd ship)<p>";
          echo "Select the ship to refit and repair: " . fm_select($RepShips, $_REQUEST, 'Sel', 0, ' multiple ','Sel2[]') ;
          echo "<button class=projtype type=submit>Refit and Repair</button><form>";
        }

      } else {
        echo "No ships are currently near the yard that need repairing<p>";
      }
      break;

  case 'Training Camp':
      echo "<h2>Re-equip and Reinforce $ARMY</h2>";
      echo "You can only do this to armies on the same planet.<p>";
      $HSys = $Homes[$Hi]['SystemId'];
      $HLoc = $Homes[$Hi]['WithinSysLoc'];
      $TTs = Get_ThingTypes();
      $Things = Get_Things_Cond($Fid," SystemId=$HSys AND BuildState=3 "); // Get all things at xx then filter  by type == Ship & WithinSysLoc
      $RepShips = [];
      $Level1 = 0;
      $Count = 0;
      foreach ($Things as $T) {
        if ($TTs[$T['Type']]['Properties'] & THING_HAS_ARMYMODULES) {
          if ($T['WithinSysLoc'] == $HLoc ) {
            $RepShips[$T['id']] = $T['Name'] . " - level " . $T['Level'];
            if ($T['Level'] == 1) $Level1++;
            $Count++;
          }
        }
      }


      $pc = Proj_Costs(1);
      if ($Count) {
        if ($Count == 1) {
          foreach ($RepShips as $tid=>$Name) {
            echo "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=" . $PTi['Re-equip and Reinforce'] .
                "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT&Sel=$tid" .
                "&Name=" . base64_encode("Re-equip and Reinforce " . $Name ) . "&L=1&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .
                "Refit and Repair $Name $Place; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";
          }
        } else if ($Level1 < 2) {
          echo "</form><form method=post action='ProjDisp.php?ACTION=NEW&id=$Fid&p=" . $PTi['Re-equip and Reinforce'] . "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT'>";
          echo "Select the $ARMY to Re-equip and Reinforce: " . fm_select($RepShips, $_REQUEST, 'Sel', 0) ;
          echo "<button class=projtype type=submit>Re-equip and Reinforce</button></form>";
        } else {
          echo "</form><form method=post action='ProjDisp.php?ACTION=NEW&id=$Fid&p=" . $PTi['Re-equip and Reinforce'] . "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT'>";
          echo "You may select <b>2</b> level 1 $ARMY or 1 $ARMY of level 2 or more. (For PCs and Linux hold down Ctrl to select 2nd $ARMY)<p>";
          echo "Select the ship to Re-equip and Reinforce: " . fm_select($RepShips, $_REQUEST, 'Sel', 0, ' multiple ','Sel2[]') ;
          echo "<button class=projtype type=submit>Re-equip and Reinforce</button><form>";
        }

      } else {
        echo "No armies are currently there<p>";
      }


      break;



    }

  echo "</form><h2>Post It Note</h2>\n";
  echo "Block off some time and money for a project you can't yet do.<p>\n";
  echo "<form method=post action=ProjDisp.php?ACTION=NEW&id=$Fid&p=" . $PTi['Post It'] . "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT>";
  echo fm_number0('Level',$_REQUEST,'Level') . fm_text0("Message",$_REQUEST,'PostItTxt');
  echo "<button class=postit type=submit>Post it</button>";
  echo "</form><p>";

  echo "<h2><a href=ProjDisp.php?id=$Fid>Cancel</a></h2>\n";
  echo "</form>";

  dotail();
?>
