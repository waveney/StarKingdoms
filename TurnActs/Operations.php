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

  foreach ($Operations as $Oid=>$O) {
    $Fid = $O['Whose'];
    $Otp = $OpTypes[$O['Type']]['Props'];
    $Wh = $O['SystemId'];
    $Sys = Get_System($Wh);
    $TWho = $Sys['Control'];
    $OrgId = $O['OrgId'];

    if ($Otp & OPER_OUTPOST) {
      $OutPs = Get_Things_Cond($Fid,"Type=" . $NamesTTypes['Outpost'] . " AND SystemId=$Wh AND BuildState=3");
      if ($OutPs) {
        if (count($OutPs >1)) {
          GMLog("There are multiple Outposts in " . $Sys['Ref'] . " - Tell Richard");
          exit;
        }
        if (($Otp & OPER_CREATE_OUTPOST)) {
          $Tid = $OutPs[0]['id'];
          $EBs = Gen_Get_Cond('Branches', " HostType=3 AND HostId=$Tid");

          $MaxB = HasTech($OutPs[0]['Whose'],'Offworld Construction');
          foreach ($EBs as $B) if ($B['Props'] & BRANCH_NOSPACE) $MaxB--;

          if ($MaxB >= $EBs) {
            $O['Status'] = 5; // Not Started
            TurnLog($Fid,'Not Starting ' . $O['Name'] . " as the Outpost is full");
            GMLog('Not Starting ' . $O['Name'] . " as the <a href=ThingEdit.php?id=$Tid>Outpost</a> is full");
            Put_Operation($O);
            continue;
          }
        }

        if ($Otp & OPER_BRANCH) {
          $AllReady = Gen_Get_Cond('Branches'," HostType=3 AND HostId=$Tid AND OrgId=$OrgId" );
          if ($AllReady) {
            $O['Status'] = 5; // Not Started
            TurnLog($Fid,'Not Starting ' . $O['Name'] . " as there is already a branch there");
            GMLog('Not Starting ' . $O['Name'] . " as there is already a branch there");
            Put_Operation($O);
            continue;
          }
        }
      } else if (!($Otp & OPER_CREATE_OUTPOST)) { // No out post and can't create
        $O['Status'] = 5; // Not Started
        TurnLog($Fid,'Not Starting ' . $O['Name'] . " There is not currently an Outpost there, this operation can't create one");
        GMLog('Not Starting ' . $O['Name']  . " There is not currently an Outpost there, this operation can't create one");
        Put_Operation($O);
        continue;
      }
    } else if ($Otp & OPER_BRANCH) {
      $Plan = HabPlanetFromSystem($Wh);
      if ($Plan) {
        $AllReady = Gen_Get_Cond('Branches'," HostType=1 AND HostId=$Plan AND OrgId=$OrgId" );
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
        GMLog('Not Starting ' . $O['Name']  . " There is no planet in " . System_Name($Sys,$Fid) . " that can support a Branch" );
        Put_Operation($O);
        continue;
      }
    }

    if (($Otp & OPER_BRANCH) && !($Otp & OPER_HIDDEN ) ){
      if ($NeedColStage2 == 0) {
        GMLog("<form method=post action=TurnActions.php?ACTION=Process&S=Instructions>");
        $NeedColStage2 = 1;
      }
      GMLog($Facts[$Fid]['Name'] . " is seting up a branch of  " . $Orgs[$O['OrgId']]['Name'] .
        " (" . $OrgTypes[$O[$OrgId]]['Name'] . " ) it is controlled by " . ($Facts[$Sys['Control']]['Name']??'Nobody') .
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
        GMLog($Facts[$Fid]['Name'] . ' Not Starting sharing ' . $$Tech['Name'] . " as it is already known by " . $Facts[$TWho]['Name']);
        Put_Operation($O);
        continue;
      }
      $Level = $O['Para2'];
    }

    if ($Otp & OPER_SOCPTARGET) {
      $SocP = Get_SocialP($O['Para1']);
      $Level = $SocP['Value'];
    }


    $Mod = ($Otp & OPER_LEVEL);
    if ($Mod >=4) {
      if ($Mod &4) $Mod = $Level;
      if ($Mod &8) $Mod = $Level*2;
    }

    $BaseLevel = Op_Level($OrgId,$Wh) + $Mod;

    if ($BaseLevel != $O['Level']) {
      $ProgNeed = Proj_Costs($BaseLevel)[0];

      TurnLog($Fid,'WARNING operation ' . $O['Name'] . " with the " . $Orgs[$O['OrgId']]['Name'] . " is actually level $BaseLevel not " .
        $O['Level'] . " and now needs " . $O['ProgNeeded'] . " progress.");

    }
    $O['Status'] = 1;// Started
    TurnLog($Fid,"Operation " . $O['Name'] . " has started for organisation " . $Orgs[$O['OrgId']]['Name']);
    GMLog("Operation " . $O['Name'] . " has started for organisation " . $Orgs[$O['OrgId']]['Name']);
    // Move Team

    $Team = Gen_Get_Cond1('Things', "Whose=$Fid AND Type=" . $NamesTTypes['Team'] . " AND Dist1=$OrgId");

    if (!$Team) {
      $Team = ['Whose'=>$Fid,'Type'=>$NamesTTypes['Team'], 'Dist1'=>$OrgId,'BuildState'=>3,
        'Name'=>("Operations team for " . $Orgs[$OrgId]['Name'])];
      Put_Thing($Team);
      $Orgs[$OrgId]['Team'] = $Team['id'];
      Gen_Put('Organisations',$Orgs[$OrgId]);
    }
    $Team['SystemId'] = $Wh;
    $Team['ProjectId'] = $Oid;
    $Team['WithinSysLoc'] = (($OpTypes[$O['Type']]['TeamProps'] & TEAM_INSPACE)?0:3);
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
      $O['State'] = 1;
      TurnLog($Fid,"Operation " . $O['Name'] . " has started for organisation " . $Orgs[$O['OrgId']]['Name']);

      $Team = Gen_Get_Cond1('Things', "Whose=$Fid AND Type=" . $NamesTTypes['Team'] . " AND Dist1=$OrgId");

      if (!$Team) {
        $Team = ['Whose'=>$Fid,'Type'=>$NamesTTypes['Team'], 'Dist1'=>$OrgId,'BuildState'=>3,
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
      $O['State'] = 5;
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
    $O['Progress'] += $Orgs[$O['OrgId']]['OfficeCount'];
    Put_Operation($O);
    TurnLog($O['FactionId'],$Orgs[$O['OrgId']]['Name'] . " has " . $Orgs[$O['OrgId']]['OfficeCount'] . " Progress.");
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
    $Fid = $O['Whose'];
    $Otp = $OpTypes[$O['Type']]['Props'];
    $Wh = $O['SystemId'];
    $Sys = Get_System($Wh);
    $TWho = $Sys['Control'];
    $Org = Gen_Get('Organisations',$O['OrgId']);

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
          TurnLog($Outpost['Whose'],"A new Forward Operating Base branch for " . $Org['Name'] . " has been set up on the Outpost in  " .
            System_Name($Sys,$Outpost['Whose']) . " by " . $Facts[$Fid]['Name']);
        }
        GMLog("A new Forward Operating Base branch for " . $Org['Name'] . " has been set up on the Outpost in  " .
          System_Name($Sys,$Fid) . " by " . $Facts[$Fid]['Name']);
        break;

      case 'Establish Black Market':
        $World = World_in($O['SystemId'],$Fid);
        New_Branch($Outpost,$NamesBTs['Black Market Trading Station'],$O,$Org);
        TurnLog($Fid,"A new Black Market Trading Station branch for " . $Org['Name'] . " has been set up on the World " . $World['Name'] .
          " in " . System_Name($Sys,$Fid) );
        GMLog("A new Black Market Trading Station branch for " . $Org['Name'] . " has been set up on the World " . $World['Name'] .
          System_Name($Sys,$Fid) . " by " . $Facts[$Fid]['Name']);
        break;

      case 'Establish Trade Hub':
        $World = World_in($O['SystemId'],$Fid);
        New_Branch($World,$NamesBTs['Trading Station'],$O,$Org);
        TurnLog($Fid,"A new Trading Station branch for " . $Org['Name'] . " has been set up on the World " . $World['Name'] .  System_Name($Sys,$Fid) );
        if ($Outpost['Whose'] != $Fid) {
          TurnLog($Outpost['Whose'],"A new Trading Station branch for " . $Org['Name'] . " has been set up on the World " . $World['Name'] .
            System_Name($Sys,$Outpost['Whose']) . " by " . $Facts[$Fid]['Name']);
        }
        GMLog("A new Trading Station branch for " . $Org['Name'] . " has been set up on the World " . $World['Name'] .
          System_Name($Sys,$Fid) . " by " . $Facts[$Fid]['Name']);
        break;

      case 'Establish Safe House':
        $World = World_in($O['SystemId'],$Fid);
        New_Branch($Outpost,$NamesBTs['Safe House'],$O,$Org);
        TurnLog($Fid,"A new Safe House branch for " . $Org['Name'] . " has been set up on the World " . $World['Name'] .
          " in " . System_Name($Sys,$Fid) );
        GMLog("A new Safe House branch for " . $Org['Name'] . " has been set up on the World " . $World['Name'] .
          System_Name($Sys,$Fid) . " by " . $Facts[$Fid]['Name']);
        break;

      case 'Establish Hidden Lodge':
        $World = World_in($O['SystemId'],$Fid);
        New_Branch($Outpost,$NamesBTs['Hidden Lodge'],$O,$Org);
        TurnLog($Fid,"A new Hidden Lodge branch for " . $Org['Name'] . " has been set up on the World " . $World['Name'] .
          " in " . System_Name($Sys,$Fid) );
        GMLog("A new Hidden Lodge branch for " . $Org['Name'] . " has been set up on the World " . $World['Name'] .
          System_Name($Sys,$Fid) . " by " . $Facts[$Fid]['Name']);
        break;

      case 'Establish Lodge':
        $World = World_in($O['SystemId'],$Fid);
        New_Branch($World,$NamesBTs['Trading Station'],$O,$Org);
        TurnLog($Fid,"A new Lodge branch for " . $Org['Name'] . " has been set up on the World " . $World['Name'] .  System_Name($Sys,$Fid) );
        if ($Outpost['Whose'] != $Fid) {
          TurnLog($Outpost['Whose'],"A new Lodge branch for " . $Org['Name'] . " has been set up on the World " . $World['Name'] .
            System_Name($Sys,$Outpost['Whose']) . " by " . $Facts[$Fid]['Name']);
        }
        GMLog("A new Lodge branch for " . $Org['Name'] . " has been set up on the World " . $World['Name'] .
          System_Name($Sys,$Fid) . " by " . $Facts[$Fid]['Name']);
        break;

      case 'Establish Forward Operating Area':
        $World = World_in($O['SystemId'],$Fid);
        New_Branch($World,$NamesBTs['Forward Operating Area'],$O,$Org);
        TurnLog($Fid,"A new Forward Operating Area branch for " . $Org['Name'] . " has been set up on the World " . $World['Name'] .  System_Name($Sys,$Fid) );
        if ($Outpost['Whose'] != $Fid) {
          TurnLog($Outpost['Whose'],"A new Forward Operating Area branch for " . $Org['Name'] . " has been set up on the World " . $World['Name'] .
            System_Name($Sys,$Outpost['Whose']) . " by " . $Facts[$Fid]['Name']);
        }
        GMLog("A new Forward Operating Area branch for " . $Org['Name'] . " has been set up on the World " . $World['Name'] .
          System_Name($Sys,$Fid) . " by " . $Facts[$Fid]['Name']);
        break;


      case 'Establish Research Base': // Type complication
        $World = World_in($O['SystemId'],$Fid);
        New_Branch($World,$O['Para1'],$O,$Org); // Para aligns with Branch type
        TurnLog($Fid,"A new Research Base branch for " . $Org['Name'] . " has been set up on the World " . $World['Name'] .  System_Name($Sys,$Fid) );
        if ($Outpost['Whose'] != $Fid) {
          TurnLog($Outpost['Whose'],"A new Research Base branch for " . $Org['Name'] . " has been set up on the World " . $World['Name'] .
            System_Name($Sys,$Outpost['Whose']) . " by " . $Facts[$Fid]['Name']);
        }
        GMLog("A new Research Base branch for " . $Org['Name'] . " has been set up on the World " . $World['Name'] .
          System_Name($Sys,$Fid) . " by " . $Facts[$Fid]['Name']);
        break;

      case 'Explore Wormhole':
        $L = Get_Link($O['Para1']);
        $N = Get_System($O['SystemId']);
        $OtherRef = (($L['System1Ref']==$N['Ref'])?$L['System2Ref']:$L['System1Ref']);
        $ON = Get_SystemR($OtherRef);
        $ONid = $ON['id'];
        $FS = Get_FactionSystemFS($Fid,$ONid);
        $FS['ScanLevel'] = $FS['SpaceScan'] = Has_Tech($Fid,'Sensors');
        Put_FactionSystem($FS);
        $Scan = ['FactionId'=>$Fid,'Sys'=>$ONid, 'Type'=>1, 'ThingId'=>-1,'GameId'=>$GAMEID, 'Turn'=>$GAME['Turn']];
        Gen_Put('ScansDue',$Scan);
        TurnLog($Fid,"System " . System_Name($ON,$Fid) . " has been explored through wormhole " . $L['Name'] );
        break;

      case 'Survey System':
        $N = Get_System($O['SystemId']);
        $FS = Get_FactionSystemFS($Fid,$O['SystemId']);
        $FS['ScanLevel'] = $FS['SpaceScan'] = Has_Tech($Fid,'Sensors');
        Put_FactionSystem($FS);
        $Scan = ['FactionId'=>$Fid,'Sys'=>$O['SystemId'], 'Type'=>1, 'ThingId'=>-1,'GameId'=>$GAMEID, 'Turn'=>$GAME['Turn']];
        Gen_Put('ScansDue',$Scan);
        TurnLog($Fid,"System " . System_Name($N,$Fid) . " has been Space survied. ");
        break;

      case 'Study Planet':
        $N = Get_System($O['SystemId']);
        $FS = Get_FactionSystemFS($Fid,$O['SystemId']);
        $FS['PlanetScan'] = Has_Tech($Fid,'Sensors');
        Put_FactionSystem($FS);
        $Scan = ['FactionId'=>$Fid,'Sys'=>$O['SystemId'], 'Type'=>2, 'ThingId'=>-1,'GameId'=>$GAMEID, 'Turn'=>$GAME['Turn']];
        Gen_Put('ScansDue',$Scan);
        TurnLog($Fid,"System " . System_Name($N,$Fid) . " has been Planetary survied. ");
        break;



      case 'Share Technology':
      case 'Study Anomaly':
      case 'Outcompete':
      case 'Send Asteroid Mining Expedition':
      case 'Transfer Resources':
      case 'Transfer Resources Ongoing':
      case 'Counter Insurgency':
      case 'Insurgency':
      case 'Advanced Scientific Recon':
      case 'Cultural Recon':
      case 'Fundamental Scientific Recon':
      case 'Military Recon':
      case 'Organisational Recon':
      case 'Planetary Recon':
      case 'Police Crackdown':
      case 'Burn the Heretics':
      case 'Investigate Competition':
      case 'Sponsor Colonists':
      case 'Spread the Word':


      default:
        GMLog("Operation " . $O['Name'] . " has completed, for " . $Facts[$Fid]['Name'] .
        " this is not automated yet.  See <a href=OperEdit.php?id=$Oid>Operation</a>",1);
        FollowUp($Fid,"Operation " . $O['Name'] . " has completed, this is not automated yet.  See <a href=OperEdit.php?id=$Oid>Operation</a>");
    }
    $O['State'] = 4;
    Put_Operation($O);
  }
}