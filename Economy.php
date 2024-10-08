<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("PlayerLib.php");
  include_once("HomesLib.php");

  dostaffhead("Move Things",["js/dropzone.js","css/dropzone.css" ]);

  global $FACTION, $GAME, $GAMEID, $db, $Currencies, $LogistCost;
  AddCurrencies();

  $GM = Access('GM');
  if ($GM) {
    $Fid = 0;
    if (!empty($FACTION)) $Fid = $FACTION['id'];
  } else {
    $Fid = $FACTION['id'];
    if ($FACTION['TurnState'] > 2) Player_Page();
  }

  if (!Access('GM') && $FACTION['TurnState'] > 2) Player_Page();

  $Spend = 0;
  echo "<h1>Economy for this coming turn</h1>\n";

  echo "<h2>Current Credits: &#8373;" . $FACTION['Credits'] . "</h2>";

  echo "<h2>Projected Expentiture</h2>";

  $Facts = Get_Factions();
  $Facts[-1]['Name'] = "Other";
  $Bs = Get_BankingFT($Fid,$GAME['Turn']);
  $LinkTypes = Get_LinkLevels();
  $Faction = $FACTION;

  $HasHomeLogistics = (Has_Tech($Fid,'Simplified Home Logistics') && Access('God'));
  $HomeArmyLogistics = Has_PTraitW($Faction['HomeWorld'],'Universal Hunting Ground	');
  $HasOwnGalaxy = (Has_Tech($Fid,'Own the Galaxy'));
  $FactionHome = 0;
  if ($HasHomeLogistics || $HomeArmyLogistics) {
    $Home = $Faction['HomeWorld'];
    if ($Home) {
      $W = Get_World($Home);
      if ($W) {
        switch ($W['ThingType']) {
          case 1: // Planet
            $P = Get_Planet($W['ThingId']);
            $FactionHome = $P['SystemId'];
            break;
          case 2: // Moon
            $M = Get_Moon($W['ThingId']);
            $P = Get_Planet($W['PlanetId']);
            $FactionHome = $P['SystemId'];
            break;
          case 3: // Things
            $TH = Get_Thing($W['ThingId']);
            $FactionHome = $TH['SystemId'];
            break;
        }
      }
    }
  }

  foreach($Bs as $B) {
    if ($B['What'] == 0) $Spend += $B['Amount'];
    echo "Sending " . ($B['What'] == 0? ( Credit() . $B['Amount']) : ($B['Amount'] . " " . $Currencies[$B['What']] )) .
      " for " . $B['YourRef'] . " to " . $Facts[$B['Recipient']]['Name'] . "<br>";
  }

  echo "<P>";

  $Projects =  Get_Projects_Cond(" FactionId=$Fid AND TurnStart=" . $GAME['Turn'] . " AND Costs!=0 AND Status=0");

  foreach($Projects as $P) {
    $Spend += $P['Costs'];
    echo "Starting " . $P['Name'] . " costs " . Credit() . $P['Costs'] . "<br>";
  }

  echo "<P>";

  $Things = Get_Things_Cond($Fid," Instruction!=0 AND Progress=0 AND InstCost!=0 ");

  $DeepSpace = 0;
  foreach($Things as $T) {
    $DeepSpace += $T['InstCost'];
  }

  if ($DeepSpace) {
    echo "Starting " . count($Things) . " Deep Space Constructions, costing " . Credit() . $DeepSpace . "<p>";
    $Spend += $DeepSpace;
  }

  $res = $db->query("SELECT pt.* FROM ProjectTurn pt, Projects p WHERE pt.TurnNumber=". $GAME['Turn'] .
       " AND pt.ProjectId=p.id AND p.FactionId=$Fid AND pt.Rush>0 AND p.TurnStart<=" . $GAME['Turn'] . " AND p.Status<2");
  if ($res) {
    while ($PT = $res->fetch_assoc()) {
      $P = Get_Project($PT['ProjectId']);
      if (isset($P['FreeRushes']) && $P['FreeRushes']>0) continue;
      $RCost =  (Rush_Cost($Fid,$P['Type'],$P['Home']) * $PT['Rush']);
      if ($RCost > 0 && $P['Status']<2) {
        $Spend += $RCost;
        echo "Spending &#8373; $RCost to rush " . $P['Name'] . " by " . $PT['Rush'] . "<br>";
      }
    }
  }

  $AllLinks = Get_LinksGame();
  $LLevels = Get_LinkLevels();
  $Things = Get_Things_Cond($Fid,"LinkId>0 AND LinkCost>0 AND LinkPay>0");
  $LinkCosts = 0;
  $BTypes = Get_BranchTypes();
  $BTypeNames = NamesList($BTypes);
  $NameBType = array_flip($BTypeNames);

  foreach($Things as $T) {
    if ($LLevels[$AllLinks[$T['LinkId']]['Level']]['Cost'] == 0) continue;
    $LinkCosts += $T['LinkCost'];
  }
  if ($LinkCosts) {
    echo "Using " . count($Things) . " " . Plural($Things,'Link','Link','Links') . " costing " . Credit() . $LinkCosts . "<p>";
    $Spend += $LinkCosts;
  }

  $left = $FACTION['Credits'] - $Spend;
  echo "<h2>Total Expenditure " . Credit() . "$Spend leaving with " . Credit() . (($left < 0)? "<span class=red>$left</span>" : $left) . "</h2>";
  if ($left < 0) {
    echo "Some actions will fail - you need to rethink your turn.<p>The expected figure below assume you have 0 credits at this point.<p>\n";
    $Spend = $FACTION['Credits'];
  }
/*
  Current Credits

  Projected expenditure on projects and rushing

  Payments (to players & arachni)

  Expected remaining
*/
  echo "<h2>Projected Income</h2>\n";

  [$EconVal,$Txt] = Income_Calc($Fid);

  echo $Txt;

  $Final =  $FACTION['Credits'] - $Spend + $EconVal*10;
  echo "<h2>Expected End Credits is &#8373;" . ($Final < 0? "<span class=Red>$Final</span>":$Final) .  "</h2>";

  if ($FACTION['PhysicsSP'] >0 || $FACTION['EngineeringSP'] > 0 || $FACTION['XenologySP'] > 0 ) {
    echo "<h2>You also have these Science points</h2>";
    if ($FACTION['PhysicsSP']) echo "Physics Science Points: " . $FACTION['PhysicsSP'] . "<br>";
    if ($FACTION['EngineeringSP']) echo "Engineering Science Points: " . $FACTION['EngineeringSP'] . "<br>";
    if ($FACTION['XenologySP']) echo "Xenology Science Points: " . $FACTION['XenologySP'] . "<br>";
  }
  echo "<p>";
  if (($Nam = GameFeature('Currency1')) && $FACTION['Currency1']) echo "$Nam: " . $FACTION['Currency1'] . "<br>";
  if (($Nam = GameFeature('Currency2')) && $FACTION['Currency2']) echo "$Nam: " . $FACTION['Currency2'] . "<br>";
  if (($Nam = GameFeature('Currency3')) && $FACTION['Currency3']) echo "$Nam: " . $FACTION['Currency3'] . "<br>";


  dotail();

  /* Old Code
  $TTypes = Get_ThingTypes();

    $Worlds = Get_Worlds($Fid);
    $EconVal = $EmbVal = $OtherEmbVal = $OutPVal = 0;
    $OutPosts = $AstMines = $AstVal = $Embassies = $OtherEmbs = $MineFields = $Branches = 0;
    foreach ($Worlds as $W) {
      $H = Get_ProjectHome($W['Home']);
      if (!$H) continue;
      $PH = Project_Home_Thing($H);
      if (!$PH) continue;

      $Name = $PH['Name'];
      if (!$Name) continue;
      $ECon = $H['Economy'] = Recalc_Economic_Rating($H,$W,$Fid);

      echo "$Name: Economic Value: $ECon ";
      if ($W['Revolt']) {
        $ECon = 0;
        echo ", It is in Revolt no income";
      } else {
        if ($H['Devastation']) {
          $ECon = $ECon - $H['Devastation'];
          echo ", It has devastation reducing it to: $ECon";
        }
        if ($W['Blockade']) { // && $Fid != 9) {
          $ECon /=2 ;
          echo ", It is blockaded income is halved to $ECon";
        }
        if (Has_PTraitW($W['id'],'Thin Atmosphere')) {
          $ECon = max(0,$ECon-1);
          echo " Reduced by 2 due to a Thin Atmosphere leaving you with a rating of $ECon";
        }

        if ($H['EconomyFactor'] < 100) {
          $ECon = ceil($ECon*$H['EconomyFactor']/100);
          echo ", It also is operating at only " . $H['EconomyFactor'] . "% so you have a rating of $ECon";
        } else if ($H['EconomyFactor'] > 100) {
          $ECon = ceil($ECon*$H['EconomyFactor']/100);
          echo ", It is currently operating at " . $H['EconomyFactor'] . "% so you have a rating of $ECon";
        }
      }
      echo "<br>\n";

      $EconVal += $ECon;
    }
    echo "<p>";
    $Things = Get_Things_Cond($Fid,'BuildState=3');
    foreach ($Things as $T) {
      if (empty($TTypes[$T['Type']])) continue;
      switch ($TTypes[$T['Type']]['Name']) {
      case "Outpost":
        $OutPosts ++;
        $OutPVal += $T['Level'];
        break;

      case "Asteroid Mine":
        $AstMines ++;
        if (Feature('AstmineDSC')) {
          $AstVal += $T['Level'];
        } else {
          $Plan = Get_Planet($T['Dist1']);
          $AstVal += (($Plan['Minerals']??0) + ((Has_PTraitP($W['id'],'Rare Mineral Deposits') && Has_Tech($Fid,'Advanced Mineral Extraction'))?3:0))*$T['Level'];
        }
        break;

      case "Embassy":
        $Embassies ++;
        $EmbVal += $T['Level'];
        break;

      case "Minefield":
        $MineFields ++;
        break;

      default:
        continue 2;
      }
    }

    // Trading Branch Effects

    $OtherTs = Get_Things_Cond(0,"Type=17 AND OtherFaction=$Fid AND BuildState=3");
    foreach($OtherTs as $OT) {
      $OtherEmbs++;
      $OtherEmbVal += $OT['Level'];
    }

    if (Feature('OutPostTrade') && $OutPosts) {
      echo "Plus $OutPosts Outposts worth 2 each<br>\n";
      $EconVal += $OutPVal*2;
    }
    if ($AstMines) {
      if (Feature('AstmineDSC')) $AstVal *= Has_Tech($Fid,'Deep Space Construction');
      echo "Plus $AstMines Asteroid Mines worth a total of $AstVal<br>\n";
      $EconVal += $AstVal;
    }
    if ($Embassies) {
      echo "Plus $Embassies of your Embassies worth 1 each<br>\n";
      $EconVal += $EmbVal;
    }
    if ($OtherEmbs) {
      echo "Plus $OtherEmbs of other Factions Embassies worth 1 each<br>\n";
      $EconVal += $OtherEmbVal;
    }

    if ($MineFields) {
      echo "Less $MineFields Minefields which cost 1 each<br>\n";
      $EconVal -= $MineFields;
    }

    echo "<p>";
    $Logistics = [0,0,0]; // Ship, Army, Intelligence
    foreach ($Things as $T) {
      if (empty($T['Type'])) continue;
      $Props = $TTypes[$T['Type']]['Properties'];
      if ($T['BuildState'] == 2 || $T['BuildState'] == 3) {
        if ($HasHomeLogistics && ($T['SystemId'] == $FactionHome)) $T['Level'] /=2;
        if ($HomeArmyLogistics && ($Props & THING_HAS_ARMYMODULES) && ($T['SystemId'] == $FactionHome)) {
          continue; // Nocost
        } else {
          if ($Props & THING_HAS_ARMYMODULES) $Logistics[1] += $LogistCost[$T['Level']];
          if ($Props & THING_HAS_GADGETS) $Logistics[2] += $LogistCost[$T['Level']];
          if ($Props & ( THING_HAS_MILSHIPMODS | THING_HAS_CIVSHIPMODS)) {
            if ($HasOwnGalaxy && str_contains($T['Class'],'Freighter')) {
              $Logistics[0] += $LogistCost[$T['Level']-1];
            } else {
              $Logistics[0] += $LogistCost[$T['Level']];
            }
          }
        }
      };
    }

    foreach($Logistics as &$Log) $Log = floor($Log);

    $LogAvail = LogisticalSupport($Fid);
    $LogCats = ['Ships','Armies','Agents'];

    foreach ($LogCats as $i => $n) {
      if ($Logistics[$i]) {
        $pen = min(0,$LogAvail[$i]-$Logistics[$i]);
        if ($pen < 0) {
          $EconVal += $pen;
          echo "Logistical penalty of $pen for: $n\n";
        }
      }
    }

    echo "<p>Total Economy is $EconVal worth " . Credit() . $EconVal*10 . "<p>\n";

//  echo "Estimated Income is : " . Income_Estimate($Fid);


  dotail();*/

?>
