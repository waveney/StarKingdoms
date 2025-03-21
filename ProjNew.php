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

//  var_dump($_REQUEST);

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
  $DistTypes = Get_DistrictTypes();
  $ProjTypes = Get_ProjectTypes();
  $OrgTypes = Get_OrgTypes();
  $PTi = [];
  foreach ($ProjTypes as $PT) $PTi[$PT['Name']] = $PT['id'];

  $ThingTypes = Get_ThingTypes();

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
        $MTypes = Get_ModuleTypes();
        $Place = base64_decode($_REQUEST['pl']);

        $TTypes = Get_ThingTypes();
        $Things = Get_Things_Cond($Fid, 'DesignValid=1 ');
        if ($_REQUEST['ACTION'] == 'NEWSHIP') {
          foreach ($Things as $Tid=>$T) if (($T['Type'] == 0) || (($TTypes[$T['Type']]['Properties'] & THING_HAS_SHIPMODULES) == 0))
            unset($Things[$Tid]);
        } else if ($_REQUEST['ACTION'] == 'NEWARMY') {
          foreach ($Things as $Tid=>$T) if (($T['Type'] == 0) || (($TTypes[$T['Type']]['Properties'] & THING_HAS_ARMYMODULES) == 0))
            unset($Things[$Tid]);
        } else if ($_REQUEST['ACTION'] == 'NEWAGENT') {
          foreach ($Things as $Tid=>$T) if (($T['Type'] == 0) || (($TTypes[$T['Type']]['Properties'] & THING_HAS_GADGETS) == 0))
            unset($Things[$Tid]);
        }

        if (!$Things) {
          echo "<h2>Sorry you do not have any plans for these - go to <a href=ThingPlan.php?F=$Fid>Thing Planning</a> first</h2>";
          break;
        }

        foreach ($Things as $Tid=>$T) {
          if ($T['Level'] > $Limit) unset($Things[$Tid]);
        }

        if ($Things) {
          echo "<h2>Select a design to make</h2>";
          echo "If it is in planning, you will build that, if already built then it will be a copy.<br>";

          echo "<table class=ProjThingTab border><tr><td>Project<td>Level<td>Cost<td>Other<br>Cost<td>Progress<br>Needed<td>Description";

          echo "<form method=post action=ProjDisp.php?ACTION=NEW&id=$Fid&p=$Ptype&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT>";

          foreach($Things as $T) {
            $Tid = $T['id'];
            $pc = Proj_Costs($T['Level']);
            $Extra = '';
            if ($_REQUEST['ACTION'] == 'NEWARMY' && Has_Tech($Fid,'Efficient Robot Construction')) $pc[0] = max(1, $pc[0] - $T['Level']);
            if ($_REQUEST['ACTION'] == 'NEWSHIP' && Has_Tech($Fid,'Space Elevator')) $pc[0] = max(1, $pc[0] - $T['Level']);
            if ($T['BuildFlags'] & BUILD_FLAG1) {
              $Extra = $T['Level'] . ' ' . Feature('Currency3','Unknown');
            }
            $Mods = [];
            $Ms = Get_Modules($Tid);
            foreach ($Ms as $Mi=>$M) if (($MTypes[$Mi]['Leveled'] &8)== 0) $Mods[]= $M['Number'] . " " . ($MTypes[$Mi]['Name']??"Unknown $Mi");
            $Moddesc = implode(', ',$Mods);

            echo "<tr><td><button class=projtype formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=$Ptype&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT&ThingId=$Tid'>" .
              $T['Name'] . (empty($T['Class'])?'': ", a " . $T['Class']) . "</buton><td>" . $T['Level'] . "<td>" . $pc[1] .  "<td>$Extra<td>" . $pc[0] .
              "<td>$Moddesc";
            }
          echo "</table><p>";

        } else {
          echo "<h2>Sorry you do not have any plans for these - go to <a href=ThingPlan.php?F=$Fid>Thing Planning</a> first</h2>";
        }
        echo "<h2><a href=ProjDisp.php?id=$Fid>Cancel</a></h2>\n";
        dotail();

      break;


    case 'Service': // Service Projects that need costs calculated - Those that don't go strait to ProjDisp

      $Ptype = $_REQUEST['p'];
      $Turn = $_REQUEST['t'];
      $Hi = $_REQUEST['Hi'];
      $Di = $_REQUEST['Di'];
      $DT = (isset($_REQUEST['DT'])? $_REQUEST['DT'] : 0);
      $Place = base64_decode($_REQUEST['Pl']);

      $Sels = $_REQUEST['Sels'];
      if ($Sels[2]??0) {
        echo "<h2 class=Err>You can only select at most 2 things</h2>";
        break;
      }
      $TthingId = $Sels[0];
      $TthingId2 = $Sels[1]??0;

      $T = Get_Thing($TthingId);

      $T1L = $T['Level'];

      if (!$TthingId2) {
        $L = max(0,$T1L-2);
        $pc = Proj_Costs($L);
        $Name = $T['Name'];

        echo "<form method=post><button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=$Ptype" .
          "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT&Sel=$TthingId" .
          "&Name=" . base64_encode($ProjTypes[$Ptype]['Name'] . ": $Name" ) . "&L=$L&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .
          $ProjTypes[$Ptype]['Name'] . ": $Name $Place; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button> Click to confirm </form><p>";
      } else {
        $T2 = Get_Thing($TthingId2);
        $L = max($T1L,$T2['Level'] );

        $pc = Proj_Costs($L);
        $Name = $T['Name'] . " and " . $T2['Name'];

        echo "<form method=post><button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=$Ptype" .
        "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT&Sel=$TthingId&Sel2=$TthingId2" .
        "&Name=" . base64_encode($ProjTypes[$Ptype]['Name'] . ": $Name" ) . "&L=$L&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .
        $ProjTypes[$Ptype]['Name'] . ": $Name $Place; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button> Click to confirm </form><p>";

      }
      dotail();
    }
  }

  function Show_Research($Name,$Link,$Cost,$Prog,$Desc) {
    echo "<tr><td><button class=projtype type=submit formaction=$Link>$Name</button><td>$Cost<td>$Prog" .
    "<td><div class=ProjDesc>" . ParseText($Desc) . "</div>";
  }


  $Homes = Get_ProjectHomes($Fid);
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
    $Offices = Get_Offices($World['id']);
  }

  echo "<h1>New " . ($DistTypes[$D['Type']]['Name']??'Construction') . " Project on " . $PH['Name'] . "</h1>";

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
        echo "<table class=ProjTab border><tr><td>Project<td>Cost<td>Progress<br>Needed<td>Description";
        $DTs = Get_DistrictTypes();
        $DNames = [];
        foreach ($DTs as $DTz) {
          if (($D['Number']??0) > $PlanCon) continue;
          if ($DTz['Gate'] && eval("return " . $DTz['Gate'] . ";" )) {
            $DNames[$DTz['id']] = $DTz['Name'];

            $Lvl = 0;
            foreach ($HDists[$Hi] as $D) {
              if ($D['Type'] == $DTz['id']) {
                $Lvl = $D['Number']-$D['Delta'];
                break;
              }
            }

            // TODO bug if you already have that level in the pipeline - Add check to Turns Ready
            $etxt = '';
            if ($Lvl >= $PlanCon ) { // $DTz['MaxNum'])) {
              if (!$GM) continue;
              $etxt = "Not allowed (GM only):";
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

            echo "<tr><td><button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=" . $PTi['Construction'] .
                  "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT&Sel=" . $DTz['id'] .
                  "&Name=" . base64_encode("Build " . $DTz['Name'] . " District $Lvl$Place") . "&L=$Lvl&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .
                  "Build " . $DTz['Name'] . " District $Lvl </button><td>" . $pc[1] . " <td> " . $pc[0] . "<td>$etxt";

          }
        }

        echo "</table><p>";

        if (Feature('Orgs') && count($Offices) < $PlanCon) {
          echo "<h2>Build Offices</h2>";
          $Lvl = count($Offices)+1;
          $pc = Proj_Costs($Lvl);
          $Orgs = Gen_Get_Cond('Organisations',"Whose=$Fid AND OfficeCount!=0");
          $Tstart = 0;
          if (count($Offices)< count($Orgs)) {
            foreach ($Orgs as $OrgId=>$Org) {
              foreach ($Offices as $O) if ($O['Organisation'] == $OrgId) continue 2;
              if (!$Tstart++) echo "<table class=ProjTab border><tr><td>Project<td>Cost<td>Progress<br>Needed<td>Description";

              echo "<tr><td><button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=" . $PTi['Construction'] .
                    "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT&Sel=-$OrgId" .
                    "&Name=" . base64_encode("Build " . $Org['Name'] . " Office $Place") .
                    "&L=$Lvl&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .
                    "Build " . $Org['Name'] . " Office</button><td>" . $pc[1] . " <td> " . $pc[0] .
                    "<td><div class=ProjDesc>" . ParseText($Org['Description']) . "</div>";
            }
          }
          if ($Tstart) echo "</table><p>";

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
            "&Name=" . base64_encode("Build NNEEWW Office $Place") .
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

      $FactTechs = Get_Faction_Techs($Fid, $Turn);
      $Techs = Get_TechsByCore($Fid);

    echo "<h2>Research Planetary Construction</h2><p>";
      echo "<table class=ProjTab border><tr><td>Project<td>Cost<td>Progress<br>Needed<td>Description";
      $OldPc = Has_Tech($Fid,3);
      $Lvl = $OldPc+1;
      $pc = Proj_Costs($Lvl);
      echo "<tr><td><button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=" . $PTi['Research Planetary Construction'] .
                "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT&Sel=3" .
                "&Name=" . base64_encode("Research Planetary Construction $Lvl$Place"). "&L=$Lvl&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .
                "Research Planetary Construction $Lvl" .
                "</button><td>" . $pc[1] . " <td> " . $pc[0] . "<td><div class=ProjDesc>" . ParseText($Techs[3]['Description']) . "</div>";
      echo "</table><p>";

      $Tstart = 0;
      foreach ($Techs as $T) {
        if ($T['Cat'] == 0 || (isset($FactTechs[$T['id']]) && $FactTechs[$T['id']]['Level'])) continue;
        if (!isset($FactTechs[$T['PreReqTech']])) continue;
        if ($T['PreReqTech']!=3) continue;
        if ( ($FactTechs[$T['PreReqTech']]['Level']<$T['PreReqLevel'] ) ) continue;

        $Lvl = $T['PreReqLevel'];
        $pc = Proj_Costs($Lvl);
        if (!$Tstart++) {
          echo "<h2>Research Supplimental Planetary Construction Technology</h2>";
          echo "<table class=ProjTab border><tr><td>Project<td>Cost<td>Progress<br>Needed<td>Description";
        }

        echo "<tr><td><button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=" .
                $PTi['Research Supplemental Planetary Construction Tech'] .
                "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT&Sel=" . $T['id'] .
                "&Name=" . base64_encode("Research " . $T['Name'] . $Place) . "&L=$Lvl&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .
                "Research " . $T['Name'] .
                "</button><td>" . $pc[1] . " <td> " . $pc[0] . "<td><div class=ProjDesc>" . ParseText($T['Description']) . "</div>";
      }
      if ($Tstart) echo "</table><p>";


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

    echo "<h2>Research Core Technology</h2><table class=ProjTab border><th>Project<th>Cost<th>Progress<br>Needed<th>Description";
      $FactTechs = Get_Faction_Techs($Fid, $Turn);
      $CTs = Get_CoreTechsByName();
      foreach ($CTs as $TT) {
        if (isset($FactTechs[$TT['id']]['Level'])) {
          $Lvl = $FactTechs[$TT['id']]['Level']+1;
        } else {
          $Lvl = 1;
        }
        $pc = Proj_Costs($Lvl);

        Show_Research($TT['Name'],"ProjDisp.php?ACTION=NEW&id=$Fid&p=" .
                $PTi['Research Core Technology'] . "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT&Sel=" . $TT['id'] .
                "&Name=" . base64_encode("Research " . $TT['Name'] . " $Lvl$Place"). "&L=$Lvl&C=" . $pc[1] . "&PN=" . $pc[0],
          $pc[1],$pc[0],$TT['Description']);

        }
        echo "</table>";

    echo "<h2>Research Supplimental Technology</h2><table class=ProjTab border><th>Project<th>Cost<th>Progress<br>Needed<th>Description";
      $Techs = Get_TechsByCore($Fid);
      foreach ($Techs as $T) {
        if ($T['Cat'] == 0 || (isset($FactTechs[$T['id']]) && $FactTechs[$T['id']]['Level'])) continue;
        if (!isset($FactTechs[$T['PreReqTech']]) ) continue;
        if ( ($FactTechs[$T['PreReqTech']]['Level']<$T['PreReqLevel'] ) ) continue;
        if ($T['PreReqTech2'] && ((! isset($FactTechs[$T['PreReqTech2']])) || $FactTechs[$T['PreReqTech2']]== 0)) continue;
        if ($T['PreReqTech3'] && ((! isset($FactTechs[$T['PreReqTech3']])) || $FactTechs[$T['PreReqTech3']]== 0)) continue;
        $Lvl = $T['PreReqLevel'];
        $pc = Proj_Costs($Lvl);
        Show_Research($T['Name'],"ProjDisp.php?ACTION=NEW&id=$Fid&p=" . $PTi['Research Supplemental Technology'] .
                "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT&Sel=" . $T['id'] .
                "&Name=" . base64_encode("Research " . $T['Name'] . $Place) . "&L=$Lvl&C=" . $pc[1] . "&PN=" . $pc[0],
          $pc[1],$pc[0],$T['Description']);
       }
      echo "</table>";

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
      echo "</form><form method=post action='ProjDisp.php?ACTION=NEW&id=$Fid&p=" . $PTi['Analyse'] . "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT'>";
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



      $FactTechs = Get_Faction_Techs($Fid, $Turn);
      $Techs = Get_TechsByCore($Fid);
      echo "<h2>Research Ship Construction</h2><p>";
        echo "<table class=ProjTab border><tr><td>Project<td>Cost<td>Progress<br>Needed<td>Description";
        $OldPc = Has_Tech($Fid,7);
        $Lvl = $OldPc+1;
        $pc = Proj_Costs($Lvl);
        echo "<tr><td><button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=" . $PTi['Research Ship Construction'] .
                "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT&Sel=7" .
                "&Name=" . base64_encode("Research Ship Construction $Lvl$Place"). "&L=$Lvl&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .
                "Research Ship Construction Level $Lvl" .
                "</button><td>" . $pc[1] . " <td> " . $pc[0] . "<td><div class=ProjDesc>" . ParseText($Techs[7]['Description']) . "</div>";

        echo "</table><p>";

      $Tstart = 0;
      $FactTechs = Get_Faction_Techs($Fid, $Turn);
      $Techs = Get_TechsByCore($Fid);
      foreach ($Techs as $T) {
        if ($T['Cat'] == 0 || (isset($FactTechs[$T['id']]) && $FactTechs[$T['id']]['Level'])) continue;
        if (!isset($FactTechs[$T['PreReqTech']])) continue;
        if ($T['PreReqTech']!=7) continue;
        if ( ($FactTechs[$T['PreReqTech']]['Level']<$T['PreReqLevel'] ) ) continue;

        if (!$Tstart++) {
          echo "<h2>Research Supplimental Ship Technology</h2>";
          echo "<table class=ProjTab border><tr><td>Project<td>Cost<td>Progress<br>Needed<td>Description";
        }

        $Lvl = $T['PreReqLevel'];
        $pc = Proj_Costs($Lvl);

        echo "<tr><td><button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=" . $PTi['Research Supplemental ship Tech'] .
                "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT&Sel=" . $T['id'] .
                "&Name=" . base64_encode("Research " . $T['Name'] . $Place) . "&L=$Lvl&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .
                "Research " . $T['Name'] .
                "</button><td>" . $pc[1] . " <td> " . $pc[0] . "<td><div class=ProjDesc>" . ParseText($T['Description']) . "</div>";
      }
      if ($Tstart) echo "</table><p>";

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
            echo "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=" . $PTi['Grow District'] .
                  "&t=$Turn&Hi=$Hi&Di=$Di&Sel=$DTp&DT=$DT" .
                  "&Name=" . base64_encode("Build " . $DTs[$DTp]['Name'] . " District $Lvl$Place") . "&L=$Lvl&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .
                  "Build " . $DTs[$DTp]['Name'] . " District $Lvl; $Place; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";

          }
      }

      // Refit/Repair
      $HSys = $Homes[$Hi]['SystemId'];
      $HLoc = $Homes[$Hi]['WithinSysLoc'];
      $MaxLvl = Has_Tech($Fid,'Ship Construction');
      $TTs = Get_ThingTypes();
      $Things = Get_Things_Cond($Fid," SystemId=$HSys AND Level <= $MaxLvl AND BuildState=" . BS_COMPLETE );
      $RepShips = $RefitShips = [];
      $Level1 = 0;
      $RepCount = $RefitCount = 0;

      $ModTypes = Get_ModuleTypes();
      foreach ($ModTypes as &$Mt) {
        if ($Mt['Leveled']& 1) $Mt['Target'] = Calc_TechLevel($Fid,$Mt['id']);
      }

      foreach ($Things as $T) {
        $Tid = $T['id'];
        if ((($TTs[$T['Type']]['Properties'] & THING_HAS_SHIPMODULES) ) && !( $TTs[$T['Type']]['Prop2'] & THING_ALWAYS_OTHER) ) {
          if ($T['CurHealth'] < $T['OrigHealth']) {
            $RepShips[$T['id']] = $T['Name'] . " - level " . $T['Level'];
            $RepCount++;
          }

          $Modules = Get_Modules($Tid);
          foreach ($Modules as $M) {
            if ((($ModTypes[$M['Type']]['Leveled']??0) & 1) && ($M['Level'] < ($ModTypes[$M['Type']]['Target']??0))) {
              $RefitShips[$T['id']] = $T['Name'] . " - level " . $T['Level'];
              $RefitCount++;
            }
          }
        }
      }

      $Factions = Get_Factions();
      $FFdata = Get_FactionFactionsCarry($Fid);
      foreach($FFdata as $FC) {
        if ($FC['Props'] & 0xf000) {
          $OThings = Get_Things_Cond($FC['FactionId1']," SystemId=$HSys AND Level <= $MaxLvl AND BuildState=" . BS_COMPLETE );
          foreach ($OThings as $T) {
            $Tid = $T['id'];
            if ((($TTs[$T['Type']]['Properties'] & THING_HAS_SHIPMODULES)) && !( $TTs[$T['Type']]['Prop2'] & THING_ALWAYS_OTHER) ) {
              if ($T['CurHealth'] < $T['OrigHealth']) {
                $RepShips[$T['id']] = $T['Name'] . " - level " . $T['Level'];
                $RepCount++;
              }

              $Modules = Get_Modules($Tid);
              foreach ($Modules as $M) {
                if ((($ModTypes[$M['Type']]['Leveled']??0) & 1) && ($M['Level'] < ($ModTypes[$M['Type']]['Target']??0))) {
                  $RefitShips[$T['id']] = $T['Name'] . " - level " . $T['Level'];
                  $RefitCount++;
                }
              }
            }
          }
        }
      }

//      var_dump($RepShips,$RefitShips);

      if ($RefitCount) {
        echo "<h2>Refit and Repair Ship</h2>";

        echo "To be selectable the ship must be idle: no movement or instructions<p>";

        foreach ($RefitShips as $tid=>$Name) {
          $T = Get_Thing($tid);
          $pc = Proj_Costs($T['Level']-1);

          echo "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=" . $PTi['Refit and Repair'] .
            "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT&Sel=$tid" .
            "&Name=" . base64_encode("Refit and Repair " . $Name ) . "&L=1&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .
            "Refit and Repair $Name $Place; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";
        }
      }
      if ($RepCount) {
        echo "<h2>Repair Ship(s)</h2>";
        echo "To be selectable the ship(s) must be idle: no movement or instructions<p>";
        if ($RepCount == 1) {
          foreach ($RepShips as $tid=>$Name) {

            $T = Get_Thing($tid);
            $pc = Proj_Costs(max(0,$T['Level']-2));

            echo "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=" . $PTi['Repair Ship(s)'] .
              "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT&Sel=$tid" .
              "&Name=" . base64_encode("Repair " . $Name ) . "&L=1&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .
              "Repair $Name $Place; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";
          }

        } else {
          echo "</form><form method=post action='ProjNew.php?ACTION=Service&id=$Fid&p=" . $PTi['Repair Ship(s)'] .
            "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT&Pl=" . base64_encode($Place) . "'>";
          echo "You may select up to 2 ships - costs and progress needed are worked out after selection. " .
            "(For PCs and Linux hold down Ctrl to select 2nd ship)<p>";
          echo "Select the ship(s) to repair: " . fm_select($RepShips, $_REQUEST, 'Sel', 0, ' multiple ','Sels[]') ;
          echo "<button class=projtype type=submit>Repair</button><form>";
        }
      }

      break;

  case 'Military':
      echo "<h2>Train a $ARMY</h2>";
//      echo "Not yet<p>";
      echo "This action is to build an already designed $ARMY.  If you want a new design please go to <a href=ThingPlan.php>The Thing Planning Tool</a> first.<p>\n";
      echo "<button class=projtype type=submit formaction='ProjNew.php?ACTION=NEWARMY&id=$Fid&p=" . $PTi["Train $ARMY"] .
           "&t=$Turn&Hi=$Hi&Di=$Di$pl&DT=$DT'>Train a new $ARMY$Place</button><p>";

      $FactTechs = Get_Faction_Techs($Fid);
      $Techs = Get_TechsByCore($Fid);

      echo "<h2>Research " . Feature('MilTech') . "</h2><p>";
      echo "<table class=ProjTab border><tr><td>Project<td>Cost<td>Progress<br>Needed<td>Description";
      $OldPc = Has_Tech($Fid,8);
        $Lvl = $OldPc+1;
        $pc = Proj_Costs($Lvl);
        echo "<tr><td><button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=" .
                $PTi['Research ' . Feature('MilTech')] .
                "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT&Sel=8" .
                "&Name=" . base64_encode("Research " . Feature('MilTech') . " on $Lvl$Place") . "&L=$Lvl&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .
                "Research " . Feature('MilTech') . " $Lvl" .
                "</button><td>" . $pc[1] . " <td> " . $pc[0] . "<td><div class=ProjDesc>" . ParseText($Techs[8]['Description']) . "</div>";
        echo "</table><p>";

      $Tstart = 0;

      foreach ($Techs as $T) {
        if ($T['Cat'] == 0 ||  (isset($FactTechs[$T['id']]) && $FactTechs[$T['id']]['Level'])) continue;
        if (!isset($FactTechs[$T['PreReqTech']])) continue;
        if ($T['PreReqTech']!=8) continue;
        if ( ($FactTechs[$T['PreReqTech']]['Level']<$T['PreReqLevel'] ) ) continue;

        $Lvl = $T['PreReqLevel'];
        $pc = Proj_Costs($Lvl);

        if (!$Tstart++) {
          echo "<h2>Research Supplimental Ship Technology</h2>";
          echo "<table class=ProjTab border><tr><td>Project<td>Cost<td>Progress<br>Needed<td>Description";
        }

        echo "<tr><td><button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=" . $PTi["Research Supplemental $ARMY Tech"] .
                "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT&Sel=" . $T['id'] .
                "&Name=" . base64_encode("Research " . $T['Name'] .  $Place) . "&L=$Lvl&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .
                "Research " . $T['Name'] .
                "</button><td>" . $pc[1] . " <td> " . $pc[0] . "<td><div class=ProjDesc>" . ParseText($T['Description']) . "</div>";
      }

      if ($Tstart) echo "</table><p>";

      $HSys = $Homes[$Hi]['SystemId'];
      $HLoc = $Homes[$Hi]['WithinSysLoc'];
      $MaxLvl = Has_Tech($Fid,'	Military Theory');
      $TTs = Get_ThingTypes();
      $Things = Get_Things_Cond($Fid," SystemId=$HSys AND Level <= $MaxLvl AND BuildState=" . BS_COMPLETE );
      $RepArmy = $RefitArmy = [];
      $Level1 = 0;
      $RepCount = $RefitCount = 0;

      $ModTypes = Get_ModuleTypes();
      foreach ($ModTypes as &$Mt) {
        if ($Mt['Leveled']& 1) $Mt['Target'] = Calc_TechLevel($Fid,$Mt['id']);
      }

      foreach ($Things as $T) {
        $Tid = $T['id'];
        if ((($TTs[$T['Type']]['Properties'] & THING_HAS_ARMYMODULES)) && !( $TTs[$T['Type']]['Prop2'] & THING_ALWAYS_OTHER) ) {
          if ($T['CurHealth'] < $T['OrigHealth']) {
            $RepArmy[$T['id']] = $T['Name'] . " - level " . $T['Level'];
            $RepCount++;
          }

          $Modules = Get_Modules($Tid);
          foreach ($Modules as $M) {
            if ((($ModTypes[$M['Type']]['Leveled']??0) & 1) && ($M['Level'] < ($ModTypes[$M['Type']]['Target']??0))) {
              $RefitArmy[$T['id']] = $T['Name'] . " - level " . $T['Level'];
              $RefitCount++;
            }
          }
        }
      }

      $Factions = Get_Factions();
      $FFdata = Get_FactionFactionsCarry($Fid);
      foreach($FFdata as $FC) {
        if ($FC['Props'] & 0xf000) {
          $OThings = Get_Things_Cond($FC['FactionId1']," SystemId=$HSys AND Level <= $MaxLvl AND BuildState=" . BS_COMPLETE );
          foreach ($OThings as $T) {
            $Tid = $T['id'];
            if ((($TTs[$T['Type']]['Properties'] & THING_HAS_ARMYMODULES)) && !( $TTs[$T['Type']]['Prop2'] & THING_ALWAYS_OTHER) ) {
              if ($T['CurHealth'] < $T['OrigHealth']) {
                $RepArmy[$T['id']] = $T['Name'] . " - level " . $T['Level'];
                $RepCount++;
              }

              $Modules = Get_Modules($Tid);
              foreach ($Modules as $M) {
                if ((($ModTypes[$M['Type']]['Leveled']??0) & 1) && ($M['Level'] < ($ModTypes[$M['Type']]['Target']??0))) {
                  $RefitArmy[$T['id']] = $T['Name'] . " - level " . $T['Level'];
                  $RefitCount++;
                }
              }
            }
          }
        }
      }

      if ($RefitCount) {
        echo "<h2>Re-equip and Reinforce $ARMIES</h2>";

        echo "To be selectable the $ARMY must be idle: no movement or instructions<p>";

        foreach ($RefitArmy as $tid=>$Name) {
          $T = Get_Thing($tid);
          $pc = Proj_Costs($T['Level']-1);

          echo "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=" . $PTi['Re-equip and Reinforce'] .
            "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT&Sel=$tid" .
            "&Name=" . base64_encode("Re-equip and Reinforce " . $Name ) . "&L=1&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .
            "Re-equip $Name $Place; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";
        }
      }
      if ($RepCount) {
        echo "<h2>Reinforce $ARMIES</h2>";
        echo "To be selectable the $ARMIES must be idle: no movement or instructions<p>";
        if ($RepCount == 1) {
          foreach ($RepArmy as $tid=>$Name) {

            $T = Get_Thing($tid);
            $pc = Proj_Costs(max(0,$T['Level']-2));

            echo "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=" . $PTi['Reinforce Detachment(s)'] .
            "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT&Sel=$tid" .
            "&Name=" . base64_encode("Reinforce " . $Name ) . "&L=1&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .
            "Reinforce $Name $Place; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";
          }

        } else {
          echo "</form><form method=post action='ProjNew.php?ACTION=Service&id=$Fid&p=" . $PTi['Reinforce Detachment(s)'] .
          "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT&Pl=" . base64_encode($Place) . "'>";
          echo "You may select up to 2 $ARMIES - costs and progress needed are worked out after selection. " .
            "(For PCs and Linux hold down Ctrl to select 2nd $ARMY)<p>";
          echo "Select the $ARMIES to Reinforce: " . fm_select($RepArmy, $_REQUEST, 'Sel', 0, ' multiple ','Sels[]') ;
          echo "<button class=projtype type=submit>Refit</button><form>";
        }
      }

      if (Has_Trait($Fid,"I Don't Want To Die") && $RefitCount) {
        echo "To be selectable the $ARMY must be idle: no movement or instructions<p>";

        foreach ($RefitArmy as $tid=>$Name) {
          $T = Get_Thing($tid);
          $pc = Proj_Costs($T['Level']-1);

          echo "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=" . $PTi['Re-equip Detachment'] .
          "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT&Sel=$tid" .
          "&Name=" . base64_encode("Re-equip " . $Name ) . "&L=1&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .
          "Re-equip $Name $Place; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";
        }

      }

      break;
 /*
      echo "<h2>Re-equip and Reinforce $ARMY</h2>"; // THIS MUST be AFTER the simple buttons as the form gets lost
      echo "You can only do this to $ARMIES on the same planet.<p>";
      $HSys = $Homes[$Hi]['SystemId'];
      $HLoc = $Homes[$Hi]['WithinSysLoc'];
      $TTs = Get_ThingTypes();
      $Things = Get_Things_Cond($Fid," SystemId=$HSys AND BuildState=" . BS_COMPLETE . " AND Instruction=0 AND LinkId=0");
      $RepShips = [];
      $Level1 = 0;
      $Count = 0;
      foreach ($Things as $T) {
        if (($TTs[$T['Type']]['Properties'] & THING_HAS_ARMYMODULES) && !($TTs[$T['Type']]['Prop2'] & THING_ALWAYS_OTHER) ) {
          if ($T['WithinSysLoc'] == $HLoc ) {
            $RepShips[$T['id']] = $T['Name'] . " - level " . $T['Level'];
            if ($T['Level'] == 1) $Level1++;
            $Count++;
          }
        }
      }


      $pc = Proj_Costs(1);
      if ($Count) {
        echo "To be selectable $ARMIES must be idle: no movement or instructions<p>";
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
        $Things = Get_Things_Cond($Fid," SystemId=$HSys AND BuildState=" . BS_COMPLETE);
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
*/
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
    $HSys = $Homes[$Hi]['SystemId'];
    $HLoc = $Homes[$Hi]['WithinSysLoc'];
    $MaxLvl = Has_Tech($Fid,'Ship Construction');
    $TTs = Get_ThingTypes();
    $Things = Get_Things_Cond($Fid," SystemId=$HSys AND Level <= $MaxLvl AND BuildState=" . BS_COMPLETE );
    $RepShips = $RefitShips = [];
    $Level1 = 0;
    $RepCount = $RefitCount = 0;

    $ModTypes = Get_ModuleTypes();
    foreach ($ModTypes as &$Mt) {
      if ($Mt['Leveled']& 1) $Mt['Target'] = Calc_TechLevel($Fid,$Mt['id']);
    }

    foreach ($Things as $T) {
      $Tid = $T['id'];
      if ((($TTs[$T['Type']]['Properties'] & THING_HAS_SHIPMODULES) ) && !( $TTs[$T['Type']]['Prop2'] & THING_ALWAYS_OTHER) ) {
        if ($T['CurHealth'] < $T['OrigHealth']) {
          $RepShips[$T['id']] = $T['Name'] . " - level " . $T['Level'];
          $RepCount++;
        }
/*
        $Modules = Get_Modules($Tid);
        foreach ($Modules as $M) {
          if ((($ModTypes[$M['Type']]['Leveled']??0) & 1) && ($M['Level'] < ($ModTypes[$M['Type']]['Target']??0))) {
            $RefitShips[$T['id']] = $T['Name'] . " - level " . $T['Level'];
            $RefitCount++;
          }
        }*/
      }
    }

    $Factions = Get_Factions();
    $FFdata = Get_FactionFactionsCarry($Fid);
    foreach($FFdata as $FC) {
      if ($FC['Props'] & 0xf000) {
        $OThings = Get_Things_Cond($FC['FactionId1']," SystemId=$HSys AND Level <= $MaxLvl AND BuildState=" . BS_COMPLETE );
        foreach ($OThings as $T) {
          $Tid = $T['id'];
          if ((($TTs[$T['Type']]['Properties'] & THING_HAS_SHIPMODULES)) && !( $TTs[$T['Type']]['Prop2'] & THING_ALWAYS_OTHER) ) {
            if ($T['CurHealth'] < $T['OrigHealth']) {
              $RepShips[$T['id']] = $T['Name'] . " - level " . $T['Level'];
              $RepCount++;
            }
/*
            $Modules = Get_Modules($Tid);
            foreach ($Modules as $M) {
              if ((($ModTypes[$M['Type']]['Leveled']??0) & 1) && ($M['Level'] < ($ModTypes[$M['Type']]['Target']??0))) {
                $RefitShips[$T['id']] = $T['Name'] . " - level " . $T['Level'];
                $RefitCount++;
              }
            }*/
          }
        }
      }
    }

    //      var_dump($RepShips,$RefitShips);

    if ($RefitCount) {
      echo "<h2>Refit and Repair Ship</h2>";

      echo "To be selectable the ship must be idle: no movement or instructions<p>";

      foreach ($RefitShips as $tid=>$Name) {
        $T = Get_Thing($tid);
        $pc = Proj_Costs($T['Level']-1);

        echo "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=" . $PTi['Refit and Repair'] .
        "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT&Sel=$tid" .
        "&Name=" . base64_encode("Refit and Repair " . $Name ) . "&L=1&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .
        "Refit and Repair $Name $Place; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";
      }
    }
    if ($RepCount) {
      echo "<h2>Repair Ship(s)</h2>";
      echo "To be selectable the ship(s) must be idle: no movement or instructions<p>";
      if ($RepCount == 1) {
        foreach ($RepShips as $tid=>$Name) {

          $T = Get_Thing($tid);
          $pc = Proj_Costs(max(0,$T['Level']-2));

          echo "<button class=projtype type=submit formaction='ProjDisp.php?ACTION=NEW&id=$Fid&p=" . $PTi['Repair Ship(s)'] .
          "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT&Sel=$tid" .
          "&Name=" . base64_encode("Repair " . $Name ) . "&L=1&C=" .$pc[1] . "&PN=" . $pc[0] ."'>" .
          "Refit and Repair $Name $Place; Cost " . $pc[1] . " Needs " . $pc[0] . " progress.</button><p>";
        }

      } else {
        echo "</form><form method=post action='ProjNew.php?ACTION=Service&id=$Fid&p=" . $PTi['Repair Ship(s)'] .
        "&t=$Turn&Hi=$Hi&Di=$Di&DT=$DT&Pl=" . base64_encode($Place) . "'>";
        echo "You may select up to 2 ships - costs and progress needed are worked out after selection. " .
          "(For PCs and Linux hold down Ctrl to select 2nd ship)<p>";
        echo "Select the ship(s) to repair: " . fm_select($RepShips, $_REQUEST, 'Sel', 0, ' multiple ','Sels[]') ;
        echo "<button class=projtype type=submit>Refit and Repair</button><form>";
      }
    }

    break;


  case 'Training Camp':
      echo "<h2>Re-equip and Reinforce $ARMY</h2>";
      echo "You can only do this to armies on the same planet.<p>";
      $HSys = $Homes[$Hi]['SystemId'];
      $HLoc = $Homes[$Hi]['WithinSysLoc'];
      $TTs = Get_ThingTypes();
      $Things = Get_Things_Cond($Fid," SystemId=$HSys AND BuildState=" . BS_COMPLETE);
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
