<?php

// Scan all plantets/moon's/Thing's for districts and deep space construction

  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("PlayerLib.php");
  include_once("SystemLib.php");
  include_once("ProjLib.php");
  include_once("OrgLib.php");

  global $FACTION,$ARMY;

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

  $OpCosts = Feature('OperationCosts');
  $OpRushs = Feature('OperationRushes');

  $OpTypes = Get_OpTypes();
  $OrgTypes = Get_OrgTypes();

  dostaffhead("New Operations for faction");

//  var_dump($_REQUEST);

  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
      default:
      break;

    }
  }




  echo "<h1>New Operation</h1>";

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

  $Turn = $_REQUEST['t'];

  $OpTypes = Gen_Get_Cond('OrgActions',"Office=$OffType");
  $Stage = ($_REQUEST['Stage']??0);
  $op = ($_REQUEST['op']??0);
  $Wh = ($_REQUEST['W']??0);
  $TechId = $_REQUEST['Te']??0;
  $TechLevel = $_REQUEST['TL']??0;
  $SP = $_REQUEST['SP']??0;


  echo "<form method=post action=ProjNew.php>";
 // echo fm_hidden('t',$Turn) . fm_hidden('O',$OrgId) . fm_hidden('Stage',$Stage+1) . fm_hidden('p',$op) . fm_hidden('W',$Wh);

  switch ($Stage) {
    case 0: //Select Op Type
      echo "<h2>Select Operation:</h2>";
 //     var_dump($OpTypes);
      foreach ($OpTypes as $opi=>$OP) {
        if ($OP['Gate'] && !eval("return " . $OP['Gate'] . ";" )) continue;
        echo "<button class=projtype type=submit formaction='OpsNew.php?t=$Turn&O=$OrgId&Stage=1&op=$opi'>" . $OP['Name'] . "</button> " .
             $OP['Description'] . "<br>\n";
      }

      echo "<p>\n";
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
      foreach ($WRefs as $Wi=>$Ref) {
        echo "<button class=projtype type=submit formaction='OpsNew.php?t=$Turn&O=$OrgId&Stage=2&op=$op&W=$Wi'>$Ref</button> \n";
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
        $TTYpes = Get_ThingTypes();
        $TTNames = NamesList($TTYpes);
        $OutPs = Get_Things_Cond($Fid,"Type=" . $TTNames['Outpost'] . " AND SystemId=$Wh AND BuildState=3");
        if ($OutPs) {
          if (count($OutPs >1)) {
            echo "<h2 class=Err>There are multiple Outposts there - let the GM's know...</h2>";
            GMLog4Later("There are multiple Outposts in " . $TSys['Ref']);
            break;
          }
          if (($OpTypes[$op]['Props'] & OPER_CREATE_OUTPOST)) {
            $Tid = $OutPs[0]['id'];
            $EBs = Gen_Get_Cond('Branches', " HostType=3 AND HostId=$Tid");

            $MaxB = HasTech($OutPs[0]['Whose'],'Offworld Construction');
            foreach ($EBs as $B) if ($B['Props'] & BRANCH_NOSPACE) $MaxB--;

            if ($MaxB >= $EBs) {
              echo "The Outpost is full";
              break;
            }
          }

          if ($OpTypes[$op]['Props'] & OPER_BRANCH) {
            $AllReady = Gen_Get_Cond('Branches'," HostType=3 AND HostId=$Tid AND OrgId=$OrgId" );
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
          $AllReady = Gen_Get_Cond('Branches'," HostType=1 AND HostId=$Plan AND OrgId=$OrgId" );
          if ($AllReady) {
            $P = Get_Planet($Plan);
            echo "There is already a branch of " . $Org['Name'] . " on " . $P['Name'] . " in " . System_Name($TSys,$Fid) . "</h2>\n";
            break;
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

      if ($OpTypes[$op]['Props'] & OPER_TECH) {
        $With = $TSys['Control'];
        $FactTechs = Get_Faction_Techs($With);
        $MyTechs = Get_Faction_Techs($Fid);

        echo "<h2>Select Technology to Share with: " . $Facts[$With]['Name'] . "</h2>\n";
        $CTs = Get_CoreTechsByName();

        foreach ($CTs as $TT) {
          $Tid = $TT['id'];
          if ($MyTechs[$Tid] > $FactTechs[$Tid]) {
            $Lvl = $FactTechs[$Tid]+1;
            echo "<button class=projtype type=submit formaction='OpsNew.php?t=$Turn&O=$OrgId&Stage=5&op=$op&W=$Wh&T=$Tid&TL=$Lvl'>" .
                 $TT['Name'] . " at level $Lvl</button> \n";
          }
        }

        foreach ($MyTechs as $T) {
          if ($T['Cat'] == 0 || !isset($FactTechs[$T['id']]) ) continue;
          if (!isset($FactTechs[$T['PreReqTech']]) ) continue;
          $Tid = $T['id'];
          $Lvl = $T['PreReqLevel'];
          if ($Lvl < 1) continue;
          echo "<button class=projtype type=submit formaction='OpsNew.php?t=$Turn&O=$OrgId&Stage=5&op=$op&W=$Wh&Te=$Tid&TL=$Lvl'>" .
               $TT['Name'] . " at level $Lvl</button> \n";
        }
        break;

      } else if ($OpTypes[$op]['Props'] & OPER_SOCP) {
        if ($OpTypes[$op]['Props'] & OPER_SOCPTARGET) { // Target SocP
          $With = $TSys['Control'];
          $World = WorldFromSystem($Wh);
          $SocPs = Get_SocialPs($World);
          if ($SocPs) {
            echo "<h2>Please Select the Social Principle you are hitting:</h2>";
            foreach ($SocPs as $Si=>$SPr) {
              echo "<button class=projtype type=submit formaction='OpsNew.php?t=$Turn&O=$OrgId&Stage=2&op=$op&W=$Wh&SP=$Si'>" .
                   $SPr['Principle'] . "</button> \n";

            }
          }
          // Need to remove those not visable as too low

        } else { // Your SocP - no actions at this stage (I think)

        }

      }
      // Drop through

    case 5: // Complete /Restart/ etc
      $TSys = Get_System($Wh);
      $TFid = $TSys['Control'];
      $Level = 0;
      $Name =  $OpTypes[$op]['Name'] . " in " . System_Name($TSys,$Fid) ;

      if (!isset($Head)) {
        echo "<h2>Selected: " . $OpTypes[$op]['Name'] . " in " . System_Name($TSys,$Fid) . "</h2>\n";
        if ($TechId) {
          $Tech = Get_Tech($TechId);
          echo "Tech:" . $Tech['Name'];
          $Name .= "Tech: " . $Tech['Name'] ;
          if ($Tech['Cat'] == 0) {
            echo " at Level $TechLevel<p>";
            $Name .= " L$TechLevel";
          }
          $Level = $TechLevel;
        }
        if ($SP) {
          $SocP = Get_SocialP($SP);
          echo "Principle:" . $SocP['Principle'];
          $Level = $SocP['Value'];
          $Name .= " Principle: " . $SocP['Principle'];

        }
      }

      $Mod = ($OpTypes[$op]['Props'] & OPER_LEVEL);
//      var_dump($Mod,$Level);
      if ($Mod >4) {
        if ($Mod &4) $Mod = $Level;
        if ($Mod &8) $Mod = $Level*2;
      }

      $BaseLevel = Op_Level($OrgId,$Wh);

      echo "This operation is at a level of $BaseLevel from distance.  ";
      if ($Mod) {
        $BaseLevel += $Mod;

        echo "With modifiers of +$Mod making the operation level $BaseLevel.<p>";
      }

      $ProgNeed = Proj_Costs($BaseLevel)[0];

      echo "This operation will be at level $BaseLevel (Needing $ProgNeed progress).  You currently do " . $Org['OfficeCount'] . " progress per turn.<p>";

      echo "<button class=projtype type=submit " .
           "formaction='OpsDisp.php?ACTION=NEW&t=$Turn&O=$OrgId&op=$op&W=$Wh&SP=$SP&Te=$TechId&TL=$TechLevel&N=" .
           base64_encode("$Name") . "&L=$BaseLevel&PN=$ProgNeed'>$Name</button> \n";



  }
  echo "<h2><a href=OpsNew.php?t=$Turn&O=$OrgId>Back to start</a> , <a href=OrgDisp.php?id=$Fid>Cancel</a></h2>\n";
  dotail();

