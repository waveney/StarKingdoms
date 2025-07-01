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
  $HomeArmyLogistics = ($Faction['HomeWorld'] >0 && Has_PTraitW($Faction['HomeWorld'],'Universal Hunting Ground'));
  $HasOwnGalaxy = (Has_Tech($Fid,'Own the Galaxy'));
  $FactionHome = 0;
  if ($HasHomeLogistics || $HomeArmyLogistics) {
    $Home = $Faction['HomeWorld'];
    if ($Home > 0) {
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

  $Things = Get_Things_Cond($Fid," Instruction!=0 AND Progress=0 AND InstCost>0 ");

  $DeepSpace = 0;
  foreach($Things as $T) {
    $DeepSpace += $T['InstCost'];
  }

  if ($DeepSpace) {
    echo "Starting " . count($Things) . " Constructions, costing " . Credit() . $DeepSpace . "<p>";
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

?>
