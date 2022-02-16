<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("PlayerLib.php");
  
  dostaffhead("Move Things",["js/dropzone.js","css/dropzone.css" ]);

  global $FACTION, $GAME, $GAMEID;
  $Fid = $FACTION['id'];
  
  echo "<h1>Economy for this coming turn</h1>\n";
  
  echo "<h2>Current Credits: " . $FACTION['Credits'] . "</h2>";
  
  echo "<h2>Projected Expentiture</h2>";
  
  Current Credits
  
  Projected expenditure on projects and rushing

  Payments (to players & arachni)

  Expected remaining
  
  echo "<h2>Projected Income</h2>\n";

  $TTypes = Get_ThingTypes();
  
    $Worlds = Get_Worlds($Fid);
    $EconVal = 0;
    $OutPosts = $AstMines = $Embassies = $OtherEmbs = 0;
    foreach ($Worlds as $W) {
      $H = Get_ProjectHome($W['Home']);
      $PH = Project_Home_Thing($H);
      $Name = $PH['Name'];
      $ECon = $H['Economy'] = Recalc_Economic_Rating($H,$W,$Fid);
      
      echo "$Name: Economic Value: " $ECon . "<br>";
      if ($H['Devastation']) {
        $ECon = $ECon - $H['Devastation'];
        echo "It has devastation reducing it to: " $ECon . "<br>\n";
      }
      if ($H['EconomyFactor'] < 100) {
        $ECon = ceil($ECon*$H['EconomyFactor']/100);
        echo "It also is operating at only " $H['EconomyFactor'] . "% so you have a rating of $ECon<br>\n";
      } else if ($H['EconomyFactor'] > 100) {
        $ECon = ceil($ECon*$H['EconomyFactor']/100);
        echo "It is currently operating at " . $H['EconomyFactor'] . "% so you have a rating of $ECon<br>\n";
      }
      $EconVal += $ECon;
    }
    echo "<p>";
    $Things = Get_Things($Fid);
    foreach ($Things as $T) {
      if (empty($TTypes[$T['Type']])) continue;
      switch ($TTypes[$T['Type']]['Name']) {
      case "Outpost":
        $OutPosts ++;
        break;
      
      case "Asteroid Mine":
        $AstMines ++;
        break;
      
      case "Embassy":
        $Embassies ++;
        break;
      
      default:
        continue 2;
      }
    }

    $OtherTs = Get_Things_Cond(0,"Type=17 AND OtherFaction=$Fid");
    foreach($OtherTs as $OT) {
      $OtherEmbs++;
    }
    
    if ($OutPosts) {
      echo "Plus $OutPosts Outposts worth 1 each<br>\n";
      $EconVal += $OutPosts;
    }
    if ($AstMines) {
      $AstVal = Has_Tech($Fid,'Deep Space Construction');
      if (Has_Tech($Fid,'Advanced Asteroid Mining')) $AstVal*=2;
      echo "Plus $AstMines Asteroid Mines worth $AstVal each<br>\n";
      $EconVal += $AstMines*$AstVal;
    }
    if ($Embassies) {
      echo "Plus $Embassies of your Embassies worth 1 each<br>\n";
      $EconVal += $Embassies;    
    }
    if ($OtherEmbs) {
      echo "Plus $OtherEmbs of other Factions Embassies worth 1 each<br>\n";
      $EconVal += $OtherEmbs;    
    }
    
    echo "<p>";
    $Logistics = [0,0,0]; // Ship, Army, Intelligence  
    foreach ($Things as $T) {
      if (empty($T['Type'])) continue;
      $Props = $TTypes[$T['Type']]['Properties'];
      if ($T['BuildState'] == 2 || $T['BuildState'] == 3) {
        if ($Props & THING_HAS_ARMYMODULES) $Logistics[1] += $T['Level'];
        if ($Props & THING_HAS_GADGETS) $Logistics[2] += $T['Level'];
        if ($Props & ( THING_HAS_MILSHIPMODS | THING_HAS_CIVSHIPMODS)) $Logistics[0] += $T['Level'];
      };
    }

  
    $LogAvail = LogsticalSupport($Fid);
    $LogCats = ['Ships','Armies','Agents'];
    
    foreach ($LogCats as $i => $n) {
      if ($Logistics[$i]) {
        $pen = min(0,$LogAvail[$i]-$Logistics[$i]);
        if ($pen < 0) {
          $EconVal += $pen;
          $EccTxt .= "Logistical penalty of $pen for <br>$n\n";
        }
      }
    }
    
    echo "<p>Total Economy is $EconVal worth " . $EconVal*10 . "<p>\n";

  logistics
  
  Waffle change
  
  Total Income  
  
  

  
  Expected End credits
 
  
  dotail();
  
?>https://www.bbc.co.uk/news/uk-wales-60393814
