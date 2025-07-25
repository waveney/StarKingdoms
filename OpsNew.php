<?php

// Scan all plantets/moon's/Thing's for districts and deep space construction

  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("PlayerLib.php");
  include_once("SystemLib.php");
  include_once("ProjLib.php");
  include_once("OrgLib.php");

  global $FACTION,$ARMY,$GAMEID, $NOTBY,$Fields;

  if (Access('Player')) {
    if (!$FACTION) {
      if (!Access('GM') ) Error_Page("Sorry you need to be a GM or a Player to access this");
    } else {
      $Fid = $FACTION['id'];
      $Faction = &$FACTION;
      if (!Access('GM') && $Faction['TurnState'] > 2) Player_Page();
    }
  }

  dostaffhead("New Operations for faction");

  if (Access('GM') ) {
    A_Check('GM');
    if (isset( $_REQUEST['F'])) {
      $Fid = $_REQUEST['F'];
    } else if (isset( $_REQUEST['f'])) {
      $Fid = $_REQUEST['f'];
    } else if (isset( $_REQUEST['id'])) {
      $Fid = $_REQUEST['id'];
    }
    if (isset($Fid)) {
      $Faction = Get_Faction($Fid);
    } else {
      echo "<h2 class=Err>No Faction Selected</h2>";
      dotail();
    }
  }

  $OpCosts = Feature('OperationCosts');
  $OpRushs = Feature('OperationRushes');

  $OpTypes = Get_OpTypes();
  $OrgTypes = Get_OrgTypes();


//  var_dump($_REQUEST);

  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
      default:
      break;

    }
  }


//  echo "This is a short term bodge, A better way should follow...<p>";

  $Orgs = Gen_Get_Cond('Organisations',"( Whose=$Fid AND OfficeCount>0) ");

  $Homes = Get_ProjectHomes($Fid);
  $DistTypes = Get_DistrictTypes();
  $ProjTypes = Get_ProjectTypes();
  $OrgTypes = Get_OrgTypes();
  $Facts = Get_Factions();

  $PTi = [];
  foreach ($ProjTypes as $PT) $PTi[$PT['Name']] = $PT['id'];

  $ThingTypes = Get_ThingTypes();
  $GM = Access('GM');

  $OrgId = $_REQUEST['O'];
  $Org = Gen_Get('Organisations',$OrgId);
  $OffType = $Org['OrgType'];
  echo "<h1>New Operation for: " . $Org['Name'] . " - a " . $OrgTypes[$OffType]['Name'];
  if ($Org['OrgType2']) echo " / " . $OrgTypes[$Org['OrgType2']]['Name'];
  echo " org</h1>";

  $Turn = $_REQUEST['t'];

  if (($OrgTypes[$Org['OrgType']]['Props'] & ORG_ALLOPS) || ($Org['OrgType2'] && ($OrgTypes[$Org['OrgType2']]['Props'] & ORG_ALLOPS)) ) {
    $OpTypes = Gen_Get_Cond('OrgActions',"(NotBy&$NOTBY)=0 ORDER BY Name");
  } else {
    $OpTypes = Gen_Get_Cond('OrgActions',"(NotBy&$NOTBY)=0 AND ( Office=$OffType OR Office=" . $Org['OrgType2'] .
      " OR ((Props&" . OPER_ALLORGS . ")!=0))  ORDER BY Name");
  }

  if (($OrgTypes[$Org['OrgType']]['Props'] & ORG_NO_BRANCHES) || ($Org['OrgType2'] && ($OrgTypes[$Org['OrgType2']]['Props'] & ORG_NO_BRANCHES)) ) {
    foreach ($OpTypes as $i=>$Op) if ($Op['Props'] & OPER_BRANCH) unset($OpTypes[$i]);
  }
  $Stage = ($_REQUEST['Stage']??0);
  $op = ($_REQUEST['op']??0);
  $Wh = ($_REQUEST['W']??0);
  $TechId = $_REQUEST['Te']??0;
  $P2 = $TechLevel = $_REQUEST['P2']??0;
  $P1 = $SP = $_REQUEST['SP']??0;
  $Desc = $_REQUEST['Description']??'';
  $TTYpes = Get_ThingTypes();
  $TTNames = array_flip(NamesList($TTYpes));
  $BTypes = Get_BranchTypes();

 // var_dump($Stage,$P1,$P2,$SP,$Wh,$Desc);
  echo "<form method=post action=OpsNew.php>";
 // echo fm_hidden('t',$Turn) . fm_hidden('O',$OrgId) . fm_hidden('Stage',$Stage+1) . fm_hidden('p',$op) . fm_hidden('W',$Wh);

  switch ($Stage) {
    case 0: //Select Op Type
      echo "<h2>Select Operation:</h2>";
      echo "<table border><tr><th>Operation<th>Level<th>Description";
 //     var_dump($OpTypes);
      $PostIt = 0;
      foreach ($OpTypes as $opi=>$OP) {
        if ($OP['Name'] == 'Post It') {
          $PostIt = $opi;
          continue;
        }
        if ($OP['Gate'] && !eval("return " . $OP['Gate'] . ";" )) continue;
        $Ltxt = "Level";
        if ($OP['Props']&3) $Ltxt .= "+" . ($OP['Props']&3);
        if (($OP['Props']& 0Xc)) {
          $Ltxt .= "+" . ((($OP['Props']&15)>>2)>1?(($OP['Props']&15)>>2):'') . "X";
        }
        echo "<tr><td><button class=projtype type=submit formaction='OpsNew.php?t=$Turn&O=$OrgId&Stage=1&op=$opi'>" . $OP['Name'] .
          "</button><br>";

        echo "<td>$Ltxt<td>" . $OP['Description'];
      }

      echo "</table><p>\n";

      echo "</form><h2>Or an Operational Post it Note</h2>";
      echo "Block off some time for an operation you can't yet do.<p>\n";
      echo "<form method=post action=OpsDisp.php?ACTION=NEW&id=$Fid&op=$PostIt&t=$Turn&O=$OrgId>";
      echo fm_number0('Level',$_REQUEST,'L') . fm_text0("Message",$_REQUEST,'Name',2);
      echo "<button class=postit type=submit>Post it</button>";
      echo "</form><p>";

      break;

    case 1: // Where
      echo "<h2>Selected: " . $OpTypes[$op]['Name'] . "</h2>\n" . $OpTypes[$op]['Description'] . "<p>";
      $SRefs = Get_SystemRefs();
      $FSs = Gen_Get_Cond('FactionSystem', "FactionId=$Fid");
      $WRefs = [];
      foreach ($FSs as $FS) {
        $WRefs[$FS['SystemId']] = $SRefs[$FS['SystemId']];
      }
      asort($WRefs);

      echo "<h2>Select the Target System</h2>";
      echo "For an operation through a wormhole, that is the system with the known end of the wormhole.<p>";
      $WRC = 0;
      foreach ($WRefs as $Wi=>$Ref) {
        echo "<button class=projtype type=submit formaction='OpsNew.php?t=$Turn&O=$OrgId&Stage=2&op=$op&W=$Wi'>$Ref</button> \n";
        if ((++$WRC%15) == 0) echo "<br>";
      }
      break;

    case 2: // Planet or Outpost
      $TSys = Get_System($Wh);
      $TFid = $TSys['Control'];
      $Head = 1;
      // If Needs existing out post and none reject
      // if adds to outpost and outpost at max reject

      echo "<h2>Selected: " . $OpTypes[$op]['Name'] . " in " . System_Name($TSys,$Fid) . "</h2>\n";

      if ($OpTypes[$op]['Props'] & OPER_OUTPOST) {
        $OutPs = Get_Things_Cond(0,"Type=" . $TTNames['Outpost'] . " AND SystemId=$Wh AND BuildState=" . BS_COMPLETE);
        if ($OutPs) {
          $popped = 0;
          if (count($OutPs) >1) {
            echo "<h2 class=Err>There are multiple Outposts there - let the GM's know...</h2>";
            GMLog4Later("There are multiple Outposts in " . $TSys['Ref']);
            break;
          }
          if (($OpTypes[$op]['Props'] & OPER_CREATE_OUTPOST)) {
            $OutP = array_pop($OutPs);
            $popped = 1;
            $Tid = $OutP['id'];
            $EBs = Gen_Get_Cond('Branches', " HostType=3 AND HostId=$Tid");
            $Used = 0;
            $MaxB = Has_Tech($OutP['Whose'],'Offworld Construction');
            foreach ($EBs as $B) if (($BTypes[$B['Type']]['Props'] & BRANCH_NOSPACE)==0) $Used++;
//            var_dump($MaxB, $Used, $OutP['Whose'],$EBs,count($EBs));
            if ($MaxB <= $Used) {
              echo "The Outpost is full<p>";
              break;
            }
          }

          if (!$popped) {
            $OutP = array_pop($OutPs);
            $popped = 0;
          }
          $Tid = $OutP['id'];
          if ($OpTypes[$op]['Props'] & OPER_BRANCH) {
            $AllReady = Gen_Get_Cond('Branches'," HostType=3 AND HostId=$Tid AND Organisation=$OrgId" );
            if ($AllReady) {
              echo "There is already a branch of " . $Org['Name'] . " at that oupost.<p>";
              break;
            }
          }
        } else if (!($OpTypes[$op]['Props'] & OPER_CREATE_OUTPOST)) { // No out post and can't create
          echo "There is not currently an Outpost there, this operation can't create one.<p>";
          break;
        }
      } else if ($OpTypes[$op]['Props'] & OPER_BRANCH) {
        $Plan = HabPlanetFromSystem($Wh);
        if ($Plan) {
          $AllReady = Gen_Get_Cond('Branches'," HostType=1 AND HostId=$Plan AND Organisation=$OrgId" );
          if ($AllReady) {
            $P = Get_Planet($Plan);
            echo "There is already a branch of " . $Org['Name'] . " on " . $P['Name'] . " in " . System_Name($TSys,$Fid) . "</h2>\n";
            break;
          }

          if ($OpTypes[$op]['Props'] & OPER_SCIPOINTS) {
            if (Get_DistrictsP($Plan)) { // It has a space age civ

              echo "<h2>Select Type of Science Points to Collect</h2>";
              for ($i=1;$i<4;$i++) {
                echo "<button class=projtype type=submit formaction='OpsNew.php?t=$Turn&O=$OrgId&Stage=5&op=$op&W=$Wh&SP=$i'>" .
                   $Fields[$i-1] . "</button>\n";
              }
              break;

            } else {
              $SP = 3;
              // Xenology only
            }
          }

        } else {
          echo "There is no planet in " . System_Name($TSys,$Fid) . " that can support a Branch.<p>\n";
          break;
        }

      }
      // Drop through


    case 4: // Secondary Questions

      $TSys = Get_System($Wh);
      $TFid = $TSys['Control'];
      if (!$Head) echo "<h2>Selected: " . $OpTypes[$op]['Name'] . " in " . System_Name($TSys,$Fid) . "</h2>\n";
      $Head = 1;
      if ($Wh) {
        $Wid = WorldFromSystem($Wh);
//        var_dump("Wid",$Wid);
      }
      $Drop = 0;

      if ($OpTypes[$op]['Props'] & OPER_TECH) {
        $With = $TSys['Control'];
        if (!$With) {
          echo "<h2>Nobody there to share with...</h2>";
          break;
        }
        $Techs = Get_Techs();
        $FactTechs = Get_Faction_Techs($With);
        $MyTechs = Get_Faction_Techs($Fid);

        echo "<h2>Select Technology to Share with: " . ($Facts[$With]['Name']??"<h2 class=err>NOBODY</h2>") . "</h2>\n";
        $CTs = Get_CoreTechsByName();
        $Shown = 0;

        foreach ($CTs as $TT) {
          $Tid = $TT['id'];
          if ($MyTechs[$Tid]['Level'] > $FactTechs[$Tid]['Level']) {
            $Lvl = $FactTechs[$Tid]['Level']+1;
            echo "<button class=projtype type=submit formaction='OpsNew.php?t=$Turn&O=$OrgId&Stage=5&op=$op&W=$Wh&Te=$Tid&P2=$Lvl'>" .
                 $TT['Name'] . " at level $Lvl</button> \n";
            $Shown = 1;
          }
        }

        foreach ($MyTechs as $Tid=>$T) {
          if (($Techs[$T['Tech_Id']]['Cat']??0) == 0 || isset($FactTechs[$Tid]) ) continue;
          if (!isset($FactTechs[$Techs[$Tid]['PreReqTech']]) ) continue;
          $Tec = $Techs[$Tid];
          $Lvl = $Tec['PreReqLevel'];
          if ($Lvl < 1) continue;
          echo "<button class=projtype type=submit formaction='OpsNew.php?t=$Turn&O=$OrgId&Stage=5&op=$op&W=$Wh&Te=$Tid&P2=$Lvl'>" .
               $Tec['Name'] . " at level $Lvl</button> \n";
          $Shown = 1;
        }

        if (!$Shown) echo "<h2>There are no Techs you know that could be shared with " . ($Facts[$With]['Name']??"<h2 class=err>NOBODY</h2>") . "</h2>\n";
        break;
      }

      if ($OpTypes[$op]['Props'] & OPER_SOCP) { // Burn Heretics
        $Wid = WorldFromSystem($Wh,$Fid);
        $World = Get_World($Wid);
        $SP = $Org['SocialPrinciple'];
        $SocPs = Get_SocialPs($Wid);

        //  var_dump($World,$Wid,$Fid);
        if ($World['FactionId'] != $Fid) { // Not own World, find if branch
          $Brs = Gen_Get_Cond('Branches',"Whose=$Fid AND HostType=" . $World['ThingType'] . " AND HostId=" . $World['ThingId']);
          if (!$Brs) {
            echo "<h2 class=Err>You don't know what the social principles are for that world</h2>";
            break;
          }
        }

        if ($OpTypes[$op]['Props'] & OPER_SOCPTARGET) { // Target SocP for Burn Heretics
          echo "<h2>Please Select the Social Principle you are hitting:</h2>";
          foreach ($SocPs as $Si=>$SPr) {
            $SP = $SPr['Principle'];
            $Prin = Gen_Get('SocialPrinciples', $SP);
            echo "<button class=projtype type=submit formaction='OpsNew.php?t=$Turn&O=$OrgId&Stage=5&op=$op&W=$Wh&SP=$SP'>" .
            $Prin['Principle'] . " Currently has adherance of " . $SPr['Value'] . "</button><br>\n";
          }
          break;
        }
      }


      if (($OpTypes[$op]['Props'] & OPER_WORMHOLE)) {
        $Ref = $TSys['Ref'];
        $FS = Get_FactionSystemFS($Fid,$Wh);

        $Ls = Get_Links($Ref);
        echo "<h2>Select the wormhole to explore:</h2>";
        echo "<h2>There are " . Feature('LinkRefText','Stargate') . "s to:</h2>\n";
        //    $GM = Access('GM');

        foreach ($Ls as $Lid=>$L) {
//          $OSysRef = ($L['System1Ref']==$Ref? $L['System2Ref']:$L['System1Ref']);
//          $ON = Get_SystemR($OSysRef);
          if (LinkVis($Fid,$Lid,$Wh)) {
            echo "<button class=projtype type=submit formaction='OpsNew.php?t=$Turn&O=$OrgId&Stage=5&op=$op&W=$Wh&SP=$Lid'>" .
            $L['Name'] . "</button> \n";
          }
        }
        break;
      }

      if (($OpTypes[$op]['Props'] & OPER_MONEY)) {
        $Wid = WorldFromSystem($Wh,$Fid);
        $To1 = $To2 = 0;
        if ($Wid) {
          $World = Get_World($Wid);
          $To1 = $World['FactionId'];
        }

        $OutPs = Get_Things_Cond(0,"Type=" . $TTNames['Outpost'] . " AND SystemId=$Wh AND BuildState=" . BS_COMPLETE);
        if ($OutPs) {
          if (count($OutPs) >1) {
            echo "<h2 class=Err>There are multiple Outposts there - let the GM's know...</h2>";
            GMLog4Later("There are multiple Outposts in " . $TSys['Ref']);
            break;
          }
          $OutP = array_pop($OutPs);
          $To2 = $OutP['Whose'];
        }

        if (($To1==0) && ($To2==0)) {
          echo "<h2>There is no one there to transfer resources to</h2>";
          break;
        }

        $DOP2 = 1;
        if ($To1 == $To2) $To2 = 0; // No choice
        if ($To1 && $To2) {
          echo "<h2>Who to?</h2>";
          $RList = [$To1 =>$Facts[$To1]['Name'], $To2 =>$Facts[$To2]['Name']];
          echo fm_radio('Select', $RList,$_REQUEST,'P2') . "<p>";
          $DOP2 = 0;
        } else if ($To1) {
          echo "Transfer to " . $Facts[$To1]['Name'];
          $P2 = $To1;
        } else {
          echo "Transfer to " . $Facts[$To2]['Name'];
          $P2 = $To2;
        }

        echo "<h2>How many Credits?</h2>";
        echo fm_hidden('Stage',5) . fm_hidden('op',$op) . fm_hidden('W',$Wh) . fm_hidden('O',$OrgId) .fm_hidden('t',$Turn);
        if ($DOP2) echo fm_hidden('P2',$P2);
        echo fm_number('',$_REQUEST,'SP','','min=0 max=' . $Facts[$Fid]['Credits'] );
        echo "<button class=projtype type=submit>Send Money</button>";
        break;
      }

      if (($OpTypes[$op]['Props'] & OPER_ANOMALY)) {
        // Look for anomalies at target, if any, are they not analysed, if so record list, if list empty - err msg, if one select, if many give choice
        $Anoms = Gen_Get_Cond('Anomalies',"GameId=$GAMEID AND SystemId=$Wh");
        if ($Anoms) {
          echo "<h2>Anomalies</h2>";
          $AnomList = [];
          foreach($Anoms as $A) {
            $Aid = $A['id'];
            $FA = Gen_Get_Cond1('FactionAnomaly',"AnomalyId=$Aid AND FactionId=$Fid");
            if ($FA && (($FA['State'] ==1) || ($FA['State'] ==2))) $AnomList[$Aid] = $A['Name'];
          }
          if (empty($AnomList)) {
            echo "<h2>There are no known anomalies there</h2>";
            break;
          } else if (count($AnomList) == 1) {
            $SP = $Aid; // Then drop through
          } else {
            echo "<h2>Please select which Anomaly?</h2>";
            foreach($AnomList as $Aid=>$AL) {
              echo "<button class=projtype type=submit formaction='OpsNew.php?t=$Turn&O=$OrgId&Stage=5&op=$op&W=$Wh&SP=$Aid'>$AL</button> \n";
            }
          }
        } else {
          echo "<h2>There are no known anomalies there</h2>";
          break;
        }
      }

      // COmpound questions
      if (($OpTypes[$op]['Props'] & (OPER_LEVELMOD | OPER_DESC))) {
        echo fm_hidden('Stage',5) . fm_hidden('op',$op) . fm_hidden('W',$Wh) . fm_hidden('O',$OrgId) .fm_hidden('t',$Turn);

        if ($OpTypes[$op]['Props'] & OPER_LEVELMOD) {
          echo fm_number('Level Modifier',$_REQUEST,'SP','','min=0');
          echo " As specified by the GMs.<p>";
        }

       if (($OpTypes[$op]['Props'] & OPER_DESC)) {
          echo "<h2>Describe what is being done - this Operation is not automated</h2>";
          echo fm_text('',$_REQUEST,'Description',6);
       }
      echo "<button class=projtype type=submit>Proceed</button>";
      break;
      }
      // else drop through

    case 5: // Complete /Restart/ etc
      $TSys = Get_System($Wh);
      $TFid = $TSys['Control'];
      $Level = 0;
      $Name =  $OpTypes[$op]['Name'] . " in " . System_Name($TSys,$Fid) ;

      if (!isset($Head)) {
        echo "<h2>Selected: " . $OpTypes[$op]['Name'] . " in " . System_Name($TSys,$Fid) . "</h2>\n";
      }
      if ($TechId) {
        $Tech = Get_Tech($TechId);
        echo "Tech: " . $Tech['Name'] . "<p>";
        $Name .= " Tech: " . $Tech['Name'] ;
        if ($Tech['Cat'] == 0) {
          echo " at Level $TechLevel<p>";
          $Name .= " L$TechLevel";
        }
        $Level = $TechLevel-1;
      }

      $AMod = $Mod = 0;
      if ($OpTypes[$op]['Props'] & OPER_SOCP) {
        $SocP = Get_SocialP($SP);
        echo "Principle:" . $SocP['Principle'] . "<p>";
        $Wid = WorldFromSystem($Wh,$Fid);
        $CurVal = Gen_Get_Cond1('SocPsWorlds',"Principle=$SP AND World=$Wid");
        $P2 = $CurVal['id'];
        $TechLevel = $Level = ($CurVal['Value']??0);
        $Name .= " Principle: " . $SocP['Principle'];
      }
      if ($OpTypes[$op]['Props'] & OPER_MONEY) {
        if (!$P2) {
          echo "<h2 class=Err>No Faction selected to recieve the funds</h2>";
          break;
        }
        echo "Amount: " . Credit() . $SP . " to " . $Facts[$P2]['Name'] . "<p>";
        $Name .= " " . $SP . " Credits to " . $Facts[$P2]['Name'];
      }
      if (($OpTypes[$op]['Props'] & OPER_ANOMALY)) {
        $Anom = Gen_Get('Anomalies',$SP);
        $Name .= " - " . $Anom['Name'];
      }
      if (($OpTypes[$op]['Props'] & OPER_WORMHOLE)) {
        $L = Get_Link($SP);
        echo "Wormhole: " . $L['Name'] . "<p>";
        $Name .= " Wormhole: " . $L['Name'];
      }
      if (($OpTypes[$op]['Props'] & OPER_SCIPOINTS)) { // Science Points
        $Name .= " " . $Fields[$SP-1];
      }
      if (($OpTypes[$op]['Props'] & OPER_LEVELMOD)) {
        $AMod = $SP;
      }

      if (($OpTypes[$op]['Props'] & OPER_DESC)) {
        $Name .= " $Desc";
      }

//      var_dump($OpTypes[$op]['Props'],$Name);
      if (($OpTypes[$op]['Props'] & OPER_LEVELMOD) == 0) $Mod = ($OpTypes[$op]['Props'] & OPER_LEVEL);
 //     var_dump($Mod,$Level);
      if ($Mod >= 4) {
        $Mod = ($Mod&3) + $Level*($Mod>>2);
      }
      $Mod += $AMod;

      $BaseLevel = Op_Level($OrgId,$Wh);
      if ($BaseLevel<0) {
        echo "<h2 class=Err>No path found to one of your worlds or branches</h2>";
        break;
      }
      if (Has_Trait($Fid,'IMPSEC') && strstr($OpTypes[$op]['Name'],'Recon')) $Mod--;

      if (Has_Trait($Fid,'Friends in All Places') && (($OpTypes[$op]['Props'] & OPER_NOT_FRIENDS) == 0)) {
        $Wid = WorldFromSystem($Wh,$Fid);
        $World = Get_World($Wid);
        $SocPs = Get_SocialPs($Wid);
        $CC = Gen_Get_Cond1('SocialPrinciples',"Principle='Confluence'");
        if ($CC) {
          $Confl = $CC['id'];
          foreach($SocPs as $S) if ($S['Principle'] == $Confl) { $Mod--; break; }
        }
      }

      echo "This operation is at a level of $BaseLevel from distance.  ";
      if ($Mod) {
        $BaseLevel += $Mod;
        $BaseLevel = max(1,$BaseLevel);
        echo "With modifiers of +$Mod making the operation level $BaseLevel.<p>";
      }
      $BaseLevel = max(1,$BaseLevel);

      $ProgNeed = Oper_Costs($BaseLevel)[0];

      echo "This operation will be at level $BaseLevel (Needing $ProgNeed progress).  You currently do " . $Org['OfficeCount'] . " progress per turn.<p>";

      echo "<button class=projtype type=submit " .
           "formaction='OpsDisp.php?ACTION=NEW&t=$Turn&O=$OrgId&op=$op&W=$Wh&SP=$SP&Te=$TechId&P2=$P2&N=" .
           base64_encode("$Name") . "&L=$BaseLevel&PN=$ProgNeed&Desc=" . base64_encode("$Desc") . "'>$Name</button> \n";

      echo "Click to confirm<p>";

  }
  echo "<p><h2><a href=OpsNew.php?t=$Turn&O=$OrgId>Back to start</a> , <a href=OpsDisp.php?id=$Fid>Cancel</a></h2>\n";
  dotail();

