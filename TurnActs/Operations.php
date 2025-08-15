<?php

function StartOperations() {
  global $GAME,$GAMEID;

  $Facts = Get_Factions();
  $OpTypes = Get_OpTypes();
  $Operations = Gen_Get_Cond('Operations', "GameId=$GAMEID AND Status=0 AND TurnStart=" . $GAME['Turn']);
  $OrgTypes = Get_OrgTypes();
  $NeedColStage2 = 0;
  $TTYpes = Get_ThingTypes();
  $TTNames = NamesList($TTYpes);
  $NamesTTypes = array_flip($TTNames);
  $Orgs = Gen_Get_Cond('Organisations',"GameId=$GAMEID");
  $BTs = Get_BranchTypes();

  foreach ($Operations as $Oid=>$O) {
    $Fid = $O['Whose'];
    $Otp = $OpTypes[$O['Type']]['Props'];
    $Wh = $O['SystemId'];
    $Sys = Get_System($Wh);
    $TWho = ($Sys['Control']??0);
    $OrgId = $O['OrgId'];

    if ($Otp & OPER_OUTPOST) {
      $OutPs = Get_Things_Cond(0,"Type=" . $NamesTTypes['Outpost'] . " AND SystemId=$Wh AND BuildState=" . BS_COMPLETE);
      if ($OutPs) {
        $Tid = $OutPs[0]['id'];
        if (count($OutPs) >1) {
          GMLog("There are multiple Outposts in " . $Sys['Ref'] . " - Tell Richard");
          exit;
        }
        if (($Otp & OPER_CREATE_OUTPOST)) {
          $EBs = Gen_Get_Cond('Branches', " HostType=3 AND HostId=$Tid");

          $MaxB = Has_Tech($OutPs[0]['Whose'],'Offworld Construction');
          foreach ($EBs as $B) if ($BTs[$B['Type']]['Props'] & BRANCH_NOSPACE) $MaxB--;

          if ($MaxB >= $EBs) {
            $O['Status'] = 5; // Not Started
            TurnLog($Fid,'Not Starting ' . $O['Name'] . " as the Outpost is full");
            GMLog('Not Starting ' . $O['Name'] . " for " . $Facts[$O['Whose']]['Name'] . " as the <a href=ThingEdit.php?id=$Tid>Outpost</a> is full");
            Put_Operation($O);
            continue;
          }
        }

        if ($Otp & OPER_BRANCH) {
          $AllReady = Gen_Get_Cond('Branches'," HostType=3 AND HostId=$Tid AND Organisation=$OrgId" );
          if ($AllReady) {
            $O['Status'] = 5; // Not Started
            TurnLog($Fid,'Not Starting ' . $O['Name'] . " as there is already a branch there");
            GMLog('Not Starting ' . $O['Name'] . " for " . $Facts[$O['Whose']]['Name'] . " as there is already a branch there");
            Put_Operation($O);
            continue;
          }
        }
      } else if (!($Otp & OPER_CREATE_OUTPOST)) { // No out post and can't create
        $O['Status'] = 5; // Not Started
        TurnLog($Fid,'Not Starting ' . $O['Name'] . " for " . $Facts[$O['Whose']]['Name'] .
          " There is not currently an Outpost there, this operation can't create one");
        GMLog('Not Starting ' . $O['Name']  . " There is not currently an Outpost there, this operation can't create one");
        Put_Operation($O);
        continue;
      }
    } else if ($Otp & OPER_BRANCH) {
      $Plan = HabPlanetFromSystem($Wh);
      if ($Plan) {
        $AllReady = Gen_Get_Cond('Branches'," HostType=1 AND HostId=$Plan AND Organisation=$OrgId" );
        if ($AllReady) {
          $P = Get_Planet($Plan);
          $O['Status'] = 5; // Not Started
          TurnLog($Fid,'Not Starting ' . $O['Name'] . " There is already a branch of " . $Orgs[$OrgId]['Name'] . " on " . $P['Name'] . " in " .
            System_Name($Sys,$Fid) );
          GMLog('Not Starting ' . $O['Name']  . " There is already a branch of " . $Orgs[$OrgId]['Name'] . " on " . $P['Name'] . " in " .
            System_Name($Sys,$Fid) );
          Put_Operation($O);
          continue;
        }
      } else {
        $O['Status'] = 5; // Not Started
        TurnLog($Fid,"There is no planet in " . System_Name($Sys,$Fid) . " that can support a Branch" );
        GMLog('Not Starting ' . $O['Name']  . " for " . $Facts[$O['Whose']]['Name'] . " There is no planet in " . System_Name($Sys,$Fid) .
          " that can support a Branch" );
        Put_Operation($O);
        continue;
      }
    }

    if (($Otp & OPER_BRANCH) && !($Otp & OPER_HIDDEN ) && ($Sys['Control'] != $Fid)){
      if ($NeedColStage2 == 0) {
        GMLog("<form method=post action=TurnActions.php?ACTION=DoStage2&Stage=Operations>");
        $NeedColStage2 = 1;
      }
      GMLog($Facts[$Fid]['Name'] . " is setting up a branch of  " . $Orgs[$O['OrgId']]['Name'] .
        " (" . $OrgTypes[$Orgs[$O['OrgId']]['OrgType']]['Name'] . " ) in " . System_Name($Sys,$Fid) . " it is controlled by " .
        ($Facts[$Sys['Control']]['Name']??'Nobody') .
        " - Allow? " . fm_YesNo("Org$Oid",1, "Reason to reject") . "\n<br>");
    }

    $Level = 0;
    if ($Otp & OPER_TECH ) {

      $Tech = Get_Tech($O['Para1']);
      $Got = Has_Tech($TWho,$O['Para1']);
      if ($Got && $Got>= $O['Para2']) {
        // Target already has tech
        $O['Status'] = 5; // Finished
        TurnLog($Fid,'Not Starting sharing ' . $Tech['Name'] . " as it is already known by " . $Facts[$TWho]['Name']);
        GMLog($Facts[$Fid]['Name'] . ' Not Starting sharing ' . $Tech['Name'] . " as it is already known by " . $Facts[$TWho]['Name']);
        Put_Operation($O);
        continue;
      }
      $Level = max(1,$O['Para2']-1);
    }

    if ($Otp & OPER_SOCPTARGET) {
      $SocP = Get_SocialP($O['Para1']);
      $Level = $SocP['Value'];
    }

    if (($Otp & OPER_LEVELMOD) == 0) { // reclac level
      $Mod = ($Otp & OPER_LEVEL);
      if ($Mod >=4) {
        $Mod = ($Mod&3) + $Level*($Mod>>2);
      }

      if (Has_Trait($Fid,'IMPSEC') && strstr($OpTypes[$O['Type']]['Name'],'Recon')) $Mod--;

      if (Has_Trait($Fid,'Friends in All Places') && (($OpTypes[$O['Type']]['Props'] & OPER_NOT_FRIENDS) == 0)) {
        $Wid = WorldFromSystem($Wh,$Fid);
        $World = Get_World($Wid);
        $SocPs = Get_SocialPs($Wid);
        $CC = Gen_Get_Cond1('SocialPrinciples',"Principle='Confluence'");
        if ($CC) {
          $Confl = $CC['id'];
          foreach($SocPs as $S) if ($S['Principle'] == $Confl) { $Mod--; break; }
        }
      }

      $BaseLevel = Op_Level($OrgId,$Wh) + $Mod + $O['GMLock'];
      $BaseLevel = max(1,$BaseLevel);

      if ($BaseLevel != $O['Level']) {
        $O['ProgNeeded'] = $ProgNeed = Oper_Costs($BaseLevel)[0];

        TurnLog($Fid,'WARNING operation ' . $O['Name'] . " with the " . $Orgs[$O['OrgId']]['Name'] . " is actually level $BaseLevel not " .
          $O['Level'] . " and now needs " . $O['ProgNeeded'] . " progress.");

      }
    }
    $O['Status'] = 1;// Started
    TurnLog($Fid,"Operation " . $O['Name'] . " has started for organisation " . $Orgs[$O['OrgId']]['Name']);
    GMLog("Operation " . $O['Name'] . " has started for organisation " . $Orgs[$O['OrgId']]['Name']);
    // Move Team

    $Team = Gen_Get_Cond1('Things', "Whose=$Fid AND Type=" . $NamesTTypes['Team'] . " AND Dist1=$OrgId");

    if (!$Team) {
      $Team = ['Whose'=>$Fid,'Type'=>$NamesTTypes['Team'], 'Dist1'=>$OrgId,'BuildState'=>BS_COMPLETE,
        'Name'=>("Operations team for " . $Orgs[$OrgId]['Name'])];
      Put_Thing($Team);
      $Orgs[$OrgId]['Team'] = $Team['id'];
      Gen_Put('Organisations',$Orgs[$OrgId]);
    }
    $Team['SystemId'] = $Wh;
    $Team['ProjectId'] = $Oid;
    $Team['WithinSysLoc'] = (($OpTypes[$O['Type']]['TeamProps'] & TEAM_INSPACE)?0:3);
    $Team['Description'] = $O['Name'] . ' ' . $O['Description'];
    Put_Thing($Team);

    Put_Operation($O);
  }
  if ($NeedColStage2) {
    echo "<input type=submit name=Ignore value=Checked>\n";

    dotail();
  }
  return 2;
}

function StartOperationsStage2() {  // Making branches is checked
  global $GAME,$GAMEID;

  $Facts = Get_Factions();
  $OpTypes = Get_OpTypes();
  $Operations = Gen_Get_Cond('Operations', "GameId=$GAMEID AND Status=0 AND TurnStart=" . $GAME['Turn']);
  $OrgTypes = Get_OrgTypes();
  $NeedColStage2 = 0;
  $TTYpes = Get_ThingTypes();
  $TTNames = NamesList($TTYpes);
  $NamesTTypes = array_flip($TTNames);
  $Orgs = Gen_Get_Cond('Organisations',"GameId=$GAMEID");


  foreach ($Operations as $Oid=>$O) {

    if (!isset($_REQUEST["Org$Oid"])) continue;
    $Ans = $_REQUEST["Org$Oid"];

    $Fid = $O['Whose'];
    $Otp = $OpTypes[$O['Type']]['Props'];
    $Wh = $O['SystemId'];
    $Sys = Get_System($Wh);
    $TWho = $Sys['Control'];
    $OrgId = $O['OrgId'];

    if ($Ans == "on") {
      $O['Status'] = 1;
      TurnLog($Fid,"Operation " . $O['Name'] . " has started for organisation " . $Orgs[$O['OrgId']]['Name']);

      $Team = Gen_Get_Cond1('Things', "Whose=$Fid AND Type=" . $NamesTTypes['Team'] . " AND Dist1=$OrgId");

      if (!$Team) {
        $Team = ['Whose'=>$Fid,'Type'=>$NamesTTypes['Team'], 'Dist1'=>$OrgId,'BuildState'=>BS_COMPLETE,
          'Name'=>("Operations team for " . $Orgs[$OrgId]['Name'])];
        Put_Thing($Team);
        $Orgs[$OrgId]['Team'] = $Team['id'];
        Gen_Put('Organisations',$Orgs[$OrgId]);
      }
      $Team['SystemId'] = $Wh;
      $Team['ProjectId'] = $Oid;
      $Team['WithinSysLoc'] = (($OpTypes[$O['Type']]['TeamProps'] & TEAM_INSPACE)?0:3);
      Put_Thing($Team);

    } else {
      TurnLog($Fid, "Operation " . $O['Name'] . "was not started because " . $_REQUEST["ReasonOrg$Oid"]??"Unknown");
      $O['Status'] = 5;
    }
    Put_Operation($O);
  }
  GMLog("<br>All Operations started<p>");
  return 1;
}

function OperationsProgress() {
  global $GAME,$GAMEID;

  $OpTypes = Get_OpTypes();
  $Operations = Gen_Get_Cond('Operations', "GameId=$GAMEID AND Status=1" );
  $Orgs = Gen_Get_Cond('Organisations',"GameId=$GAMEID");
  $Facts = Get_Factions();

  foreach ($Operations as $Oid=>$O) {
    if ($O['TurnState'] > 0) {
      GMLog("Skipping progress on <a href=OperEdit.php?id=$Oid>" . $O['Name'] . " </a> as already Done.");
      continue;
    }
    $O['Progress'] += $Orgs[$O['OrgId']]['OfficeCount'];
    $O['TurnState'] = 1;
    Put_Operation($O);
    TurnLog($O['Whose'],$Orgs[$O['OrgId']]['Name'] . " has " . $Orgs[$O['OrgId']]['OfficeCount'] . " Progress on " . $O['Name']);
  }
  GMLog("All Operations Progressed<p>");
  return 1;
}

function OperationsComplete() {
  global $GAME,$GAMEID;

  $Facts = Get_Factions();
  $OpTypes = Get_OpTypes();
  $Operations = Gen_Get_Cond('Operations', "GameId=$GAMEID AND Status=1 AND Progress>=ProgNeeded");
  $OrgTypes = Get_OrgTypes();
  $TTYpes = Get_ThingTypes();
  $TTNames = NamesList($TTYpes);
  $Orgs = Gen_Get_Cond('Organisations',"GameId=$GAMEID");
  $NameOps = NamesList($OpTypes);
  $OpNames = array_flip($NameOps);
  $BranchTypes = Get_BranchTypes();
  $BTypeNames = NamesList($BranchTypes);
  $NamesBTs = array_flip($BTypeNames);

  foreach ($Operations as $Oid=>$O) {
//    var_dump("Completing",$O);
    $Fid = $O['Whose'];
    $Otp = $OpTypes[$O['Type']]['Props'];
    $Wh = $O['SystemId'];
    $Sys = Get_System($Wh);
    $TWho = $Sys['Control'];
    $Org = Gen_Get('Organisations',$O['OrgId']);
    if ($O['TurnState'] >1) {
      GMLog("Skipping Operation <a href=OperEdit.php?id=$Oid>" . $O['Name'] . " </a> as already completed.");
      continue;
    }

    switch ($NameOps[$O['Type']]) {
      case 'Establish Deep Space Science Facility':
        $Outpost = Outpost_in($O['SystemId'],$Fid);
        New_Branch($Outpost,$NamesBTs['Science Space Base'],$O,$Org);
        TurnLog($Fid,"A new Science Space Base branch for " . $Org['Name'] . " has been set up on the Outpost in " . System_Name($Sys,$Fid) );
        if ($Outpost['Whose'] != $Fid) {
          TurnLog($Outpost['Whose'],"A new Science Space Base branch for " . $Org['Name'] . " has been set up on the Outpost in  " .
            System_Name($Sys,$Outpost['Whose']) . " by " . $Facts[$Fid]['Name']);
        }
        GMLog("A new Science Space Base branch for " . $Org['Name'] . " has been set up on the Outpost in  " .
            System_Name($Sys,$Fid) . " by " . $Facts[$Fid]['Name']);
        break;

      case 'Establish Outpost Defences':
        $Outpost = Outpost_in($O['SystemId'],$Fid);
        New_Branch($Outpost,$NamesBTs['Forward Operating Area'],$O,$Org);
        TurnLog($Fid,"A new Forward Operating Area branch for " . $Org['Name'] . " has been set up on the Outpost in " . System_Name($Sys,$Fid) );
        if ($Outpost['Whose'] != $Fid) {
          TurnLog($Outpost['Whose'],"A new Forward Operating Area branch for " . $Org['Name'] . " has been set up on the Outpost in  " .
            System_Name($Sys,$Outpost['Whose']) . " by " . $Facts[$Fid]['Name']);
        }
        GMLog("A new Forward Operating Area branch for " . $Org['Name'] . " has been set up on the Outpost in  " .
          System_Name($Sys,$Fid) . " by " . $Facts[$Fid]['Name']);
        break;

      case 'Establish Outpost Safe House':
        $Outpost = Outpost_in($O['SystemId'],$Fid,0);
        if ($Outpost) {
          New_Branch($Outpost,$NamesBTs['Safe House'],$O,$Org);
          TurnLog($Fid,"A new Safe House branch for " . $Org['Name'] . " has been set up on the Outpost in " . System_Name($Sys,$Fid) );

          GMLog("A new Safe House branch for " . $Org['Name'] . " has been set up on the Outpost in  " .
            System_Name($Sys,$Fid) . " by " . $Facts[$Fid]['Name']);
        } else {
          TurnLog($Fid,"A new Safe House branch for " . $Org['Name'] . " could not be set up on the Outpost in " . System_Name($Sys,$Fid) .
            " as there isn't one, and this operation can not create one");

          GMLog("A new Safe House branch for " . $Org['Name'] . " could not be set up on the Outpost in " . System_Name($Sys,$Fid) .
            " as there isn't one, and this operation can not create one");
        }
        break;

      case 'Establish Hidden Outpost Lodge':
        $Outpost = Outpost_in($O['SystemId'],$Fid,0);
        if ($Outpost) {
          New_Branch($Outpost,$NamesBTs['Hidden Lodge'],$O,$Org);
          TurnLog($Fid,"A new Hidden Lodge branch for " . $Org['Name'] . " has been set up on the Outpost in " . System_Name($Sys,$Fid) );
          GMLog("A new Hidden Lodge branch for " . $Org['Name'] . " has been set up on the Outpost in  " .
            System_Name($Sys,$Fid) . " by " . $Facts[$Fid]['Name']);
        } else {
          TurnLog($Fid,"A new Hidden Lodge branch for " . $Org['Name'] . " could not be set up on the Outpost in " . System_Name($Sys,$Fid) .
            " as there isn't one, and this operation can not create one");

          GMLog("A newHidden Lodge branch for " . $Org['Name'] . " could not be set up on the Outpost in " . System_Name($Sys,$Fid) .
            " as there isn't one, and this operation can not create one");

        }
        break;

      case 'Establish Outpost Lodge':
        $Outpost = Outpost_in($O['SystemId'],$Fid);
        New_Branch($Outpost,$NamesBTs['Lodge'],$O,$Org);
        TurnLog($Fid,"A new Lodge branch for " . $Org['Name'] . " has been set up on the Outpost in " . System_Name($Sys,$Fid) );
        if ($Outpost['Whose'] != $Fid) {
          TurnLog($Outpost['Whose'],"A new Lodge branch for " . $Org['Name'] . " has been set up on the Outpost in  " .
            System_Name($Sys,$Outpost['Whose']) . " by " . $Facts[$Fid]['Name']);
        }
        GMLog("A new Lodge branch for " . $Org['Name'] . " has been set up on the Outpost in  " .
          System_Name($Sys,$Fid) . " by " . $Facts[$Fid]['Name']);
        break;

      case 'Establish Trading Station':
        $Outpost = Outpost_in($O['SystemId'],$Fid);
        New_Branch($Outpost,$NamesBTs['Trading Station'],$O,$Org);
        TurnLog($Fid,"A new Trading Station branch for " . $Org['Name'] . " has been set up on the Outpost in " . System_Name($Sys,$Fid) );
        if ($Outpost['Whose'] != $Fid) {
          TurnLog($Outpost['Whose'],"A new Trading Station branch for " . $Org['Name'] . " has been set up on the Outpost in  " .
            System_Name($Sys,$Outpost['Whose']) . " by " . $Facts[$Fid]['Name']);
        }
        GMLog("A new Trading Station branch for " . $Org['Name'] . " has been set up on the Outpost in  " .
          System_Name($Sys,$Fid) . " by " . $Facts[$Fid]['Name']);
        break;

      case 'Establish Forward Operating Base':
        $Outpost = Outpost_in($O['SystemId'],$Fid);
        New_Branch($Outpost,$NamesBTs['Forward Operating Base'],$O,$Org);
        TurnLog($Fid,"A new Forward Operating Base branch for " . $Org['Name'] . " has been set up on the Outpost in " . System_Name($Sys,$Fid) );
        if ($Outpost['Whose'] != $Fid) {
          TurnLog($Outpost['Whose'],"A new Forward Operating Base branch for " . $Org['Name'] . " has been set up on the Outpost in " .
            System_Name($Sys,$Outpost['Whose']) . " by " . $Facts[$Fid]['Name']);
        }
        GMLog("A new Forward Operating Base branch for " . $Org['Name'] . " has been set up on the Outpost in  " . ' ' .
          System_Name($Sys,$Fid) . " by " . $Facts[$Fid]['Name']);
        break;

      case 'Establish Black Market':
        $World = World_in($O['SystemId'],$Fid);
        New_Branch($World,$NamesBTs['Black Market Trading Station'],$O,$Org);
        TurnLog($Fid,"A new Black Market Trading Station branch for " . $Org['Name'] . " has been set up on the World " . $World['Name'] .
          " in " . System_Name($Sys,$Fid) );
        GMLog("A new Black Market Trading Station branch for " . $Org['Name'] . " has been set up on the World " . $World['Name'] . ' ' .
          System_Name($Sys,$Fid) . " by " . $Facts[$Fid]['Name']);
        break;

      case 'Establish Trade Hub':
        $World = World_in($O['SystemId'],$Fid);
        New_Branch($World,$NamesBTs['Trading Station'],$O,$Org);
        TurnLog($Fid,"A new Trading Station branch for " . $Org['Name'] . " has been set up on the World " . $World['Name'] . " " .
           System_Name($Sys,$Fid) );
        if ($World['FactionId'] != $Fid) {
          TurnLog($World['FactionId'],"A new Trading Station branch for " . $Org['Name'] . " has been set up on the World " . $World['Name'] .
            System_Name($Sys,$World['FactionId']) . " by " . $Facts[$Fid]['Name']);
        }
        GMLog("A new Trading Station branch for " . $Org['Name'] . " has been set up on the World " . $World['Name'] . ' ' .
          System_Name($Sys,$Fid) . " by " . $Facts[$Fid]['Name']);
        break;

      case 'Establish Safe House':
        $World = World_in($O['SystemId'],$Fid);
        New_Branch($World,$NamesBTs['Safe House'],$O,$Org);
        TurnLog($Fid,"A new Safe House branch for " . $Org['Name'] . " has been set up on the World " . $World['Name'] .
          " in " . System_Name($Sys,$Fid) );
        GMLog("A new Safe House branch for " . $Org['Name'] . " has been set up on the World " . $World['Name'] . ' ' .
          System_Name($Sys,$Fid) . " by " . $Facts[$Fid]['Name']);
        break;

      case 'Establish Hidden Lodge':
        $World = World_in($O['SystemId'],$Fid);
        New_Branch($World,$NamesBTs['Hidden Lodge'],$O,$Org);
        TurnLog($Fid,"A new Hidden Lodge branch for " . $Org['Name'] . " has been set up on the World " . $World['Name'] .
          " in " . System_Name($Sys,$Fid) );
        GMLog("A new Hidden Lodge branch for " . $Org['Name'] . " has been set up on the World " . $World['Name'] . ' ' .
          System_Name($Sys,$Fid) . " by " . $Facts[$Fid]['Name']);
        break;

      case 'Establish Lodge':
        $World = World_in($O['SystemId'],$Fid);
        New_Branch($World,$NamesBTs['Lodge'],$O,$Org);
        TurnLog($Fid,"A new Lodge branch for " . $Org['Name'] . " has been set up on the World " . $World['Name'] . ' ' . System_Name($Sys,$Fid) );
        if ($World['FactionId'] != $Fid) {
          TurnLog($World['FactionId'],"A new Lodge branch for " . $Org['Name'] . " has been set up on the World " . $World['Name'] .
            System_Name($Sys,$World['FactionId']) . " by " . $Facts[$Fid]['Name']);
        }
        GMLog("A new Lodge branch for " . $Org['Name'] . " has been set up on the World " . $World['Name'] . ' ' .
          System_Name($Sys,$Fid) . " by " . $Facts[$Fid]['Name']);
        break;

      case 'Establish Forward Operating Area':
        $World = World_in($O['SystemId'],$Fid);
        New_Branch($World,$NamesBTs['Forward Operating Area'],$O,$Org);
        TurnLog($Fid,"A new Forward Operating Area branch for " . $Org['Name'] . " has been set up on the World " . $World['Name'] . ' ' .
          System_Name($Sys,$Fid) );
        if ($World['FactionId'] != $Fid) {
          TurnLog($World['FactionId'],"A new Forward Operating Area branch for " . $Org['Name'] . " has been set up on the World " . $World['Name'] . ' ' .
            System_Name($Sys,$World['FactionId']) . " by " . $Facts[$Fid]['Name']);
        }
        GMLog("A new Forward Operating Area branch for " . $Org['Name'] . " has been set up on the World " . $World['Name'] . ' ' .
          System_Name($Sys,$Fid) . " by " . $Facts[$Fid]['Name']);
        break;


      case 'Establish Research Base': // Type complication
        $World = World_in($O['SystemId'],$Fid);
        New_Branch($World,$O['Para1'],$O,$Org); // Para aligns with Branch type
        TurnLog($Fid,"A new Research Base branch for " . $Org['Name'] . " has been set up on the World " . $World['Name'] .
          System_Name($Sys,$Fid) );
        if ($World['FactionId'] != $Fid) {
          TurnLog($World['FactionId'],"A new Research Base branch for " . $Org['Name'] . " has been set up on the World " . $World['Name'] . ' ' .
            System_Name($Sys,$World['FactionId']) . " by " . $Facts[$Fid]['Name']);
        }
        GMLog("A new Research Base branch for " . $Org['Name'] . " has been set up on the World " . $World['Name'] . ' ' .
          System_Name($Sys,$Fid) . " by " . $Facts[$Fid]['Name']);
        break;

      case 'Explore Wormhole':
        $L = Get_Link($O['Para1']);
        if (!$L) {
          GMLog("<h2 class=Err>Exploring a Wormhole with no data for Operation $Oid - Call Richard</h2>");
          break;
        }
        $N = Get_System($O['SystemId']);
        $OtherRef = (($L['System1Ref']==$N['Ref'])?$L['System2Ref']:$L['System1Ref']);
        $ON = Get_SystemR($OtherRef);
        $ONid = $ON['id'];
        $FS = Get_FactionSystemFS($Fid,$ONid);
        $FS['ScanLevel'] = $FS['SpaceScan'] = Has_Tech($Fid,'Sensors');
        $xtra = '';
        if ($N['Nebulae']) {
          if (!Has_Tech($Fid,'Nebula Sensors')) {
            $FS['ScanLevel'] = $FS['SpaceScan'] = -1;
            $xtra = "  Unfortunately it is in a Nebula, you can't see anything.";
          }
        }
        Put_FactionSystem($FS);
        $Scan = ['FactionId'=>$Fid,'Sys'=>$ONid, 'Type'=>0, 'ThingId'=>-1,'GameId'=>$GAMEID, 'Turn'=>$GAME['Turn']];
        Gen_Put('ScansDue',$Scan);
        TurnLog($Fid,"System " . System_Name($ON,$Fid) . " has been passively explored through wormhole " . $L['Name'] . $xtra);
        break;

      case 'Survey System':
        $N = Get_System($O['SystemId']);
        $FS = Get_FactionSystemFS($Fid,$O['SystemId']);
        $FS['ScanLevel'] = $FS['SpaceScan'] = Has_Tech($Fid,'Sensors');
        $xtra = '';
        if ($N['Nebulae']) {
          if (!Has_Tech($Fid,'Nebula Sensors')) {
            $FS['ScanLevel'] = $FS['SpaceScan'] = -1;
            $xtra = "  Unfortunately it is in a Nebula, you can't see anything.";
          }
        }
        Put_FactionSystem($FS);
        $Scan = ['FactionId'=>$Fid,'Sys'=>$O['SystemId'], 'Type'=>1, 'ThingId'=>-1,'GameId'=>$GAMEID, 'Turn'=>$GAME['Turn'], 'Scan'=>$FS['ScanLevel']];
        Gen_Put('ScansDue',$Scan);
        TurnLog($Fid,"System " . System_Name($N,$Fid) . " has been Space survied. $xtra");
        break;

      case 'Study Planet':
        $N = Get_System($O['SystemId']);
        $FS = Get_FactionSystemFS($Fid,$O['SystemId']);
        $FS['PlanetScan'] = Has_Tech($Fid,'Sensors');
        $xtra = '';
        if ($N['Nebulae']) {
          if (!Has_Tech($Fid,'Nebula Sensors')) {
            $FS['PlanetScan'] = -1;
            $xtra = "  Unfortunately it is in a Nebula, you can't see anything.";
          }
        }
        Put_FactionSystem($FS);
        $Scan = ['FactionId'=>$Fid,'Sys'=>$O['SystemId'], 'Type'=>2, 'ThingId'=>-1,'GameId'=>$GAMEID, 'Turn'=>$GAME['Turn'],  'Scan'=>$FS['PlanetScan']];
        Gen_Put('ScansDue',$Scan);
        TurnLog($Fid,"System " . System_Name($N,$Fid) . " has been Planetary survied. $xtra");
        break;

      case 'Transfer Resources':
        $To = $O['Para2'];
        $Amount = $O['Para1'];

        if (Spend_Credit($Fid,$Amount,'Transfer to ' . $Facts[$To]['Name'],$To)) {
          TurnLog($Fid,"Transfered " . Credit() . $Amount . " to " . $Facts[$To]['Name']);

          Spend_Credit($To, - $Amount,'Transfer From ' . $Facts[$Fid]['Name'],$Fid);
          TurnLog($To,$Facts[$Fid]['Name'] . " transfered " . Credit() . $Amount . " to you" );
          GMLog('Cash transfer from ' . $Facts[$Fid]['Name']. ' to ' . $Facts[$To]['Name'] . ' of ' . Credit() . $Amount);
        } else {
          TurnLog($Fid,"Failed to transfer " . Credit() . $Amount . " to " . $Facts[$To]['Name'] . " you only have " . $Facts[$Fid]['Credits']);
          TurnLog($To,  $Facts[$Fid]['Name'] . " Failed to transfer " . Credit() . $Amount );
          GMLog('Cash transfer from ' . $Facts[$Fid]['Name']. ' to ' . $Facts[$To]['Name'] . ' of ' . Credit() . $Amount .  ' Bounced');
        }
        break;

      case 'Spread the Word':
        $To = $O['Para2'];
        $Wid = WorldFromSystem($O['SystemId'],$Fid);
        $World = Get_World($Wid);
        $SocP = Gen_Get('SocialPrinciples',$O['Para1']);
        if (!$SocP) {
          GMLog('<b>ERROR</b> Spread the Word ' . $Facts[$Fid]['Name']. ' to ' . $Facts[$To]['Name'] . ' of unknown principle ' . $O['Para1']);
          break;
        }
        $SPW = Gen_Get_Cond1('SocPsWorlds',"Principle=" . $O['Para1'] . " AND World=$Wid");

        if (isset($SPW['Value']) && ($SPW['Value'] > $O['Para2'])) {
          TurnLog($Fid,"Spread the Word of '" . $SocP['Principle']  . "' to " . World_Name_Long($Wid,$Fid) . " failed it is already at " . $SPW['Value']);
          GMLog("Spread the Word of '" . $SocP['Principle']  . "' to " . World_Name($Wid,$Fid) . " by " . $Org['Name'] . " of " .
            $Facts[$Fid]['Name'] . " failed it is already at " . $SPW['Value']);

        } else {
          if ($SPW) {
            $SPW['Value']++;
          } else {
            $SPW = ['Principle'=>$O['Para1'],'World'=>$Wid,'Value'=>1];
          }
          Gen_Put('SocPsWorlds',$SPW);
          TurnLog($Fid,"Spread the Word of '" . $SocP['Principle']  . "' to " . World_Name_Long($Wid,$Fid) . " succeeded it is now " . $SPW['Value']);

          if ($World['FactionId'] != $Fid) {
            Report_SP_Change($Fid,$World);
          }
        }
        break;

      case 'Burn the Heretics':
        $Wid = WorldFromSystem($O['SystemId'],$Fid);
        $World = Get_World($Wid);
        $SocP = Gen_Get('SocialPrinciples',$O['Para1']);
        $SPW = Gen_Get_Cond1('SocPsWorlds',"Principle=" . $O['Para1'] . " AND World=$Wid");

        if ($SPW['Value'] > $O['Para2']) {
          TurnLog($Fid,"Burn the Heretics of '" . $SocP['Principle']  . "' to " . World_Name_Long($Wid,$Fid) . " failed it is already at " .
            $SPW['Value']);
          GMLog("Burn the Heretics of '" . $SocP['Principle']  . "' to " . World_Name_Long($Wid,$Fid) . " by " . $Org['Name'] . " of " .
            $Facts[$Fid]['Name'] . " failed it is already at " . $SPW['Value']);

        } else {
          if ($SPW) {
            $SPW['Value'] = max(0,$SPW['Value']-1);
            if ($SPW['Value']) {
              Gen_Put('SocPsWorlds',$SPW);
              TurnLog($Fid,"Burn the Heretics of '" . $SocP['Principle']  . "' on " . World_Name_Long($Wid,$Fid) . " succeeded it is now " .
                $SPW['Value']);
            } else {
              db_delete('SocPsWorlds',$SPW['id']);
              TurnLog($Fid,"Burn the Heretics '" . $SocP['Principle']  . "' on " . World_Name_Long($Wid,$Fid) . " has been elminated");
            }
            if ($World['FactionId'] != $Fid) {
              Report_SP_Change($Fid,$World);
            }
          } else {
            TurnLog($Fid,"Burn the Heretics of '" . $SocP['Principle']  . "' on " . World_Name_Long($Wid,$Fid) .
              " failed as there was no adherence to it left to reduce." );

          }
        }
        break;

      case 'Study Anomaly':
        $Anom = Gen_Get('Anomalies',$O['Para1']);
        if (($Anom['Complete']??3) ==0) {
          $Aid = $Anom['id']??0;
          $FA = Gen_Get_Cond1('FactionAnomaly',"AnomalyId=$Aid AND FactionId=$Fid");

          if (!$FA) {
            GMLog("Trying to study an anomaly $Aid as an operation - for Operation $Oid - Something has gone wrong call Richard...");
            break;
          }
          $Prog = Has_Tech($Fid,'Sensors')*$Org['OfficeCount'];

          if ($FA['Progress'] < $Anom['AnomalyLevel']) {
            $FA['Progress'] += $Prog;
            TurnLog($Fid,"$Prog progress on analysing anomaly: " . $Anom['Name']);
            Gen_Put('FactionAnomaly',$FA);
          } else {
            TurnLog($Fid,"The anomaly " . $Anom['Name'] . " is already studied");
          }
          AnomalyComplete($Aid,$Fid);
        } else {
          TurnLog($Fid,"The anomaly " . $Anom['Name'] . " can no longer be studied");
        }
        break;

      case 'Sponsor Colonists':
        $Where = $O['SystemId'];
        $N = Get_System($Where);
        $T = Get_Things_Cond1(0,"SystemId=$Where AND Instruction=1");
        if (!$T) {
          TurnLog($Fid,"The operation to sponsor colonists in " . System_Name($N,$Fid) . " cannot find any on going colonisation to help with.");
          GMLog("The operation to sponsor colonists in " . System_Name($N,$Fid) . " by Operation $Oid, " .
            " cannot find any on going colonisation to help with.");
          break;
        }
        $PMod = $Org['OfficeCount'] * Has_Tech($Fid,'Offworld Construction');
        $T['Progress'] += $PMod;
        Put_Thing($T);
        $SocP = Get_SocialP($Org['SocialPrinciple']);
        TurnLog($Fid,"The colonisation by " . $T['Name'] . " has an extra $PMod progress");
        FollowUp($Fid,"When the colonisation in " . $N['Ref'] . " finishes, give it an additional level of the social principle: " . $SocP['Name'] );
        break;

      case 'Share Technology':
        $Tech = Get_Tech($O['Para1']);
        $Tsys = $O['SystemId'];
        $N = Get_System($Tsys);
        $Xfr2 = $N['Control'];

        if ($Xfr2 == 0) {
          TurnLog($Fid, "You tried to share " . $Tech['Name'] . " in system " . System_Name($N) . " but there is nobody in control to recieve it");
          GMLog($Facts[$Fid]['Name'] . " tried to share " . $Tech['Name'] . " in system " . System_Name($N) . " but there is nobody in control to recieve it");
          break;
        }
        $Level = $O['Para2'];
        $XFact = $Facts[$Xfr2];
        $Have = Has_Tech($Xfr2,$Tech['id']);
        //      var_dump($Tech,$XFact);
        if ($Tech['Cat'] == 0) {
          if ($Have >= $Level) {
            TurnLog($Fid, "You tried to share " . $Tech['Name'] . " at level $Level. with " . $XFact['Name'] . " They already know it.");
            TurnLog($Xfr2, $Facts[$Fid]['Name'] . " tried to share " . $Tech['Name'] . " at level $Level.  With you - you already have it at level $Have.");
          } else if ($Have == $Level-1) {
            $CTech = Get_Faction_TechFT($Xfr2 ,$Tech['id']);
            $CTech['Level'] = $Level;
            Put_Faction_Tech($CTech);
            TurnLog($Fid, "Your have shared " . $Tech['Name'] . " at level $Level.  with " . $XFact['Name']);
            TurnLog($Xfr2,  $Facts[$Fid]['Name'] . " has shared " . $Tech['Name'] . " at level $Level.  With you.");
          } else if (0 ) { // Learn lower level option
            $CTech = Get_Faction_TechFT($Xfr2 ,$Tech['id']);
            $CTech['Level'] = $Have+1;
            Put_Faction_Tech($CTech);
            TurnLog($Fid, "You tried to share " . $Tech['Name'] . " at level $Level. with " . $XFact['Name'] .
              " They only had it at level $Have.  They learnt level " . ($Have+1));
            TurnLog($Xfr2,  $Facts[$Fid]['Name'] .  " tried to share " . $Tech['Name'] .
              " at level $Level with you. You only have it at level $Have  so learnt it at level " . ($Have+1));
          } else {
            TurnLog($Fid, "You tried to share " . $Tech['Name'] . " at level $Level. with " . $XFact['Name'] .
              " They only had it at level $Have - they don't understand what you sent");
            TurnLog($Xfr2,  $Facts[$Fid]['Name'] .  " tried to share " . $Tech['Name'] .
              " at level $Level with you. You only have it at level $Have so learnt nothing");
          }
        } else { // Supp techs
          $PRHave = Has_Tech($Xfr2,$Tech['PreReqTech']);
          if ($Have) {
            TurnLog($Fid, "You tried to share " . $Tech['Name'] . " with " . $XFact['Name'] . " They already know it.");
            TurnLog($Xfr2,  $Facts[$Fid]['Name'] . " tried to share " . $Tech['Name'] . " with you - you already have it.");
          } else if ($PRHave >= $Tech['PreReqLevel']) {
            $CTech = Get_Faction_TechFT($Xfr2 ,$Tech['id']);
            $CTech['Level'] = $Level;
            Put_Faction_Tech($CTech);
            TurnLog($Fid, "Your have shared " . $Tech['Name'] . " with " . $XFact['Name']);
            TurnLog($Xfr2,  $Facts[$Fid]['Name'] . " has shared " . $Tech['Name'] . " with you.");
          } else {
            TurnLog($Fid, "You tried to share " . $Tech['Name'] . " with " . $XFact['Name'] . " They don't understand what you sent");
            TurnLog($Xfr2,  $Facts[$Fid]['Name'] .  " tried to share " . $Tech['Name'] . " with you. You don't understand it");
          }
        }
        break;



      case 'Outcompete':
      case 'Send Asteroid Mining Expedition':
      case 'Transfer Resources Ongoing':
      case 'Counter Insurgency':
      case 'Insurgency':
      case 'Advanced Scientific Recon':
      case 'Cultural Recon': // no longer exists
      case 'Fundamental Scientific Recon':
      case 'Wormhole Recon':
      case 'Military Recon':
      case 'Organisational Recon':
      case 'Planetary Recon':
      case 'Police Crackdown':
      case 'Investigate Competition':
      case 'Gather Life':
      case 'Military Recon Through Wormhole':
      case 'Post It':


      default:
        GMLog("Operation " . $O['Name'] . " has completed, for " . $Facts[$Fid]['Name'] .
        " this is not automated yet.  See <a href=OperEdit.php?id=$Oid>Operation</a>",1);
        FollowUp($Fid,"Operation " . $O['Name'] . " has completed, this is not automated yet.  See <a href=OperEdit.php?id=$Oid>Operation</a>");
    }
    $O['TurnState'] = 2;
    $O['Status'] = 2;
    $O['TurnEnd'] = $GAME['Turn'];
//    var_dump($O);
    Put_Operation($O);
  }
  return 1;
}