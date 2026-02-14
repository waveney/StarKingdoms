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
    $OpTypes = Gen_Get_Cond('OrgActions',"(NotBy=0 OR (NotBy&$NOTBY)!=0) ORDER BY Name");
  } else {
    $OpTypes = Gen_Get_Cond('OrgActions',"(NotBy=0 OR (NotBy&$NOTBY)!=0) AND ( Office=$OffType OR Office=" . $Org['OrgType2'] .
      " OR ((Props&" . OPER_ALLORGS . ")!=0))  ORDER BY Name");

    if ($Org['ExtraOps']) {
      $AllOps = Get_OpTypes();
      $OpNames = NamesList($AllOps);
      $NamesOps = array_flip($OpNames);
      $xtras = explode(',',$Org['ExtraOps']);

      foreach ($xtras as $Op) {
        trim($Op);
        if (isset($NamesOps[$Op])) {
          $OpTypes[$AllOps[$NamesOps[$Op]]['id']]= $AllOps[$NamesOps[$Op]];
        } else {
          echo "This is a problem with Operation: $Op - tell Richard<p>";
          break;
        }
      }
    }
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
  $Target = $_REQUEST['Target']??0;
  $TargType = $_REQUEST['TType']??0;
  $TTYpes = Get_ThingTypes();
  $TTNames = array_flip(NamesList($TTYpes));
  $BTypes = Get_BranchTypes();
  $ExtraLevels = 0;

  switch ($TargType) {
    case 1: // Planet
      $Body = Get_Planet($Target);
      break;

    case 2: // Moon
      $Body = Get_Moon($Target);
      break;

    case 3: // Thing (Outposts)
      $Body = Get_Thing($Target);
      break;

    default:
      $Body = [];
      break;
  }

  if ($Body['TargetMod']??0) $ExtraLevels = $Body['TargetMod'];


/*  if ($TargetType) {
    if ($Target > 0) {
      $Body = Get_Planet($Target);
      $ThingType =1;
      $ThingId = $Target;
    } else {
      $Body = Get_Moon(-$Target);
      $ThingType = 2;
      $ThingId = -$Target;
    }
    if ($Body['TargetMod']) $ExtraLevels = $Body['TargetMod'];
  } elseif ($Wh) { // Outpost - this is not very satisfactory at the moment
    $ThingType = 3;
    $ThingId = 0;
    $Body = [];
  } else { // Not setup yet
    $ThingType = $ThingId = 0;
    $Body = [];
  }*/

 // var_dump($Stage,$P1,$P2,$SP,$Wh,$Desc);
  echo "<form method=post action=OpsNew.php>";
 // echo fm_hidden('t',$Turn) . fm_hidden('O',$OrgId) . fm_hidden('Stage',$Stage+1) . fm_hidden('p',$op) . fm_hidden('W',$Wh);

  switch ($Stage) {
    case 0: //Select Op Type
      echo "<h2>Select Operation:</h2>";
      echo "<table border><tr><th>Operation<th>Level<th>Hidden<th>Description";
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

        echo "<td>$Ltxt<td>" . (($OP['TeamProps']&1)?'Hidden':'No') . "<td>" . $OP['Description'];
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

      if ($OpTypes[$op]['Props'] & OPER_OWNWORLD) {
        $Worlds = Get_Worlds($Fid);
        foreach ($Worlds as $Wid=>$W) {
          $H = Get_ProjectHome($W['Home']);
          $WRefs[$H['SystemId']] = $SRefs[$H['SystemId']];
        }

      } else {
        $FSs = Gen_Get_Cond('FactionSystem', "FactionId=$Fid");
        $WRefs = [];
        foreach ($FSs as $FS) {
          $WRefs[$FS['SystemId']] = $SRefs[$FS['SystemId']];
        }
        asort($WRefs);
      }

      echo "<h2>Select the Target System</h2>";
      if ($OpTypes[$op]['Props'] & OPER_WORMHOLE) echo "For an operation through a wormhole, that is the system with the known end of the wormhole.<p>";
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
      $SelectTarget = 0;
      $OutPs = Get_Things_Cond(0,"Type=" . $TTNames['Outpost'] . " AND SystemId=$Wh AND BuildState=" . BS_COMPLETE);
      if (count($OutPs) >1) {
        echo "<h2 class=Err>There are multiple Outposts there - let the GM's know...</h2>";
        GMLog4Later("There are multiple Outposts in " . $TSys['Ref']);
        break;
      } else if ($OutPs) {
        $OutP = array_pop($OutPs);
        $Used = 0;
        $MaxB = Has_Tech($OutP['Whose'],'Offworld Construction');
        $EBs = Gen_Get_Cond('Branches', " HostType=3 AND HostId=" . $OutP['id'] );
        foreach ($EBs as $B) if (($BTypes[$B['Type']]['Props'] & BRANCH_NOSPACE)==0) $Used++;
      } else {
        $OutP = 0;
      }
      if (($OpTypes[$op]['Props'] & OPER_OUTPOST) ==0 ) {
        $WLs = WorldsFromSystem($Wh);
        $WList = [];
        $FS = Get_FactionSystemFS($Fid,$Wh);
        if ($WLs) foreach ($WLs as $Wid) {
          if ($Wid > 0) {
            $Body = Get_Planet($Wid);
            if ((($Body['Concealment']??0) > 0) && (($Body['Concealment']??0) > $FS['SpaceScan'])) continue;
          } else {
            $Body = Get_Moon(-$Wid);
            if ((($Body['Concealment']??0) > 0) && (($Body['Concealment']??0) > $FS['PlanetScan'])) continue;
          }
          if (($OpTypes[$op]['Props'] & OPER_OWNWORLD) && ($Body['Control'] != $Fid) ) continue;
          if (($OpTypes[$op]['Props'] & OPER_NOT_OWNWORLD) && ($Body['Control'] == $Fid) ) continue;
          if ($Body['TargetGate'] && !eval("return " . $Body['TargetGate'] . ";" )) continue;
          $WList []= $Wid;
        }
      } else {

      }

//      var_dump($WList,$OutP);
      echo "<h2>Selected : " . $OpTypes[$op]['Name'] . " in " . System_Name($TSys,$Fid) . "</h2>\n";

      if ($OpTypes[$op]['Props'] & OPER_OUTPOST) {
        if ($OutP) {
          if ($OpTypes[$op]['Props'] & OPER_BRANCH) {
            if (($Used >= $MaxB) && (($OpTypes[$op]['Props'] & OPER_HIDDEN)==0)) {
              echo "The Outpost is full: Has $Used, Max $MaxB<p>";
              break;
            }
            $AllReady = Gen_Get_Cond1('Branches'," HostType=3 AND HostId=" . $OutP['id'] . " AND Organisation=$OrgId" );
            if ($AllReady) {
              echo "There is already a branch of " . $Org['Name'] . " at that oupost.<p>";
              break;
            }
          }
          $TargType = 3;
          $Target = $OutP['id'];
        } else if (($OpTypes[$op]['Props'] & OPER_CREATE_OUTPOST)) {
          $TargType = 3;
          $Target = -1; // New Outpost
        } else {
          echo "There is not currently an Outpost there, this operation can't create one.<p>";
          break;
        }
      } else if ($OpTypes[$op]['Props'] & ( OPER_BRANCH | OPER_OWNWORLD | OPER_CIVILISED)) {
        if ($WList) {
          if (count($WList) == 1) {
            $Target = array_pop($WList);
            if ($Target > 0) {
              $TargType = 1;
              $Body = Get_Planet($Target);
            } else {
              $TargType = 2;
              $Target = - $Target;
              $Body = Get_Moon($Target);
            }
          } else {
            $SelectTarget = 1;
          }
        } else {
          if ($OpTypes[$op]['Props'] & OPER_BRANCH) {
            echo "There is no world in " . System_Name($TSys,$Fid) . " that can support a Branch.<p>\n";
          } else {
            echo "There is no world in " . System_Name($TSys,$Fid) . " with a civilisation.<p>\n";
          }
          break;

        }
      } else if ($OpTypes[$op]['Props'] & OPER_OWNEDSPACE) {
        if (!$TFid) {
          echo "System not controlled by any faction<p>";
          break;
        }
        if ($OutP && !$WList) {
          $TargType = 3;
          $Target = $OutP['id'];
        } else if (!$OutP && (count($WList)==1)) {
          $Target = array_pop($WList);
          if ($Target > 0) {
            $TargType = 1;
            $Body = Get_Planet($Target);
          } else {
            $TargType = 2;
            $Target = - $Target;
            $Body = Get_Moon($Target);
          }
        } else {
          if ($OutP) {
            $Match = 0;
            foreach ($WList as $Wid) {
              if ($Wid > 0) {
                $TargType = 1;
                $Body = Get_Planet($Wid);
              } else {
                $TargType = 2;
                $Body = Get_Moon(-$Wid);
              }
              if ($Body['Control'] == $OutP['Whose']) $Match = 1;
            }
            if ($Match == 0) $WList []= -1000000-$OutP['id'];
          }
          if (count($WList) > 1 ) {
            $SelectTarget = 1;
          } else {
            $Target = abs($Wid);
          }
        }
      }

      if ($SelectTarget) {
        echo "<h2>Select the target within " . System_Name($TSys,$Fid) . "</h2>";
        foreach ($WList as $Wid) {
          if ($Wid > 0) {
            $Body = Get_Planet($Wid);
            $TargType = 1;
            $Target = $Wid;
          } else if ($Wid < -1000000) {
            $TargType = 3;
            $Target = -1000000-$Wid;
            $Body = Get_Thing($Target);
            if (!$Body['Name']) $Body['Name'] = 'Outpost';
          } else {
            $Body = Get_Moon(-$Wid);
            $TargType = 2;
            $Target = - $Wid;
          }
          $xtra = '';
          if ($Body['TargetMod']??0) $xtra = " (Level +" . $Body['TargetMod'] . ")";
          echo "<button class=projtype type=submit formaction='OpsNew.php?t=$Turn&O=$OrgId&Stage=3&op=$op&W=$Wh&TType=$TargType&Target=$Target'>" .
          $Body['Name'] . "$xtra</button> \n";
        }
        break;
      }


      /*
      // If Needs existing out post and none reject
      // if adds to outpost and outpost at max reject


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
      } else if ($OpTypes[$op]['Props'] & (OPER_BRANCH | OPER_CIVILISED | OPER_SOCP | OPER_OWNEDSPACE)) {
 echo "Here<p>";
        $WLs = WorldsFromSystem($Wh);
var_dump($WLs);
        if (!$WLs) {
          if ($OpTypes[$op]['Props'] & OPER_BRANCH) {
            echo "There is no world in " . System_Name($TSys,$Fid) . " that can support a Branch.<p>\n";
            break;
          } else {
            if ($OpTypes[$op]['Props'] & OPER_OWNEDSPACE) { // Outpost?
              if ($TFid) {
                $OutP = Get_Things_Cond1(0,"Type=" . $TTNames['Outpost'] . " AND SystemId=$Wh AND BuildState=" . BS_COMPLETE);
                if ($OutP) {
                  $TargType = 3;
                  $Target = $OutP['id'];
                } else {
                  echo "There is no world or Outpost in " . System_Name($TSys,$Fid) . "<p>\n";
                  break;
                }
              } else {
                echo "There is no world or Outpost in " . System_Name($TSys,$Fid) . "<p>\n";
                break;
              }
            } else {
              echo "There is no world in " . System_Name($TSys,$Fid) . " with a civilisation.<p>\n";
              break;
            }
          }
        }

        $WList = [];
        $FS = Get_FactionSystemFS($Fid,$Wh);
var_dump($FS);
        if ($WLs) foreach ($WLs as $Wid) {
          if ($Wid > 0) {
            $Body = Get_Planet($Wid);
            if ((($Body['Concealment']??0) > 0) && (($Body['Concealment']??0) > $FS['SpaceScan'])) continue;
          } else {
            $Body = Get_Moon(-$Wid);
            if ((($Body['Concealment']??0) > 0) && (($Body['Concealment']??0) > $FS['PlanetScan'])) continue;
          }
          if ($Body['TargetGate'] && !eval("return " . $Body['TargetGate'] . ";" )) continue;
          $WList []= $Wid;
        }
 var_dump($WList);
        if (empty($WList)) {
          if ($OpTypes[$op]['Props'] & OPER_BRANCH) {
            echo "There is no world in " . System_Name($TSys,$Fid) . " that can support a Branch.<p>\n";
          } else {
            echo "There is no world in " . System_Name($TSys,$Fid) . " with a civilisation.<p>\n";
          }
          break;
        }

        if (count($WList) == 1) {
          $Target = $WList[0];
          if ($Target > 0) {
            $Body = Get_Planet($Target);
          } else {
            $Body = Get_Moon(-$Target);
          }
        } else {
          echo "<h2>Select the target within " . System_Name($TSys,$Fid) . "</h2>";
          foreach ($WList as $Wid) {
            if ($Wid > 0) {
              $Body = Get_Planet($Wid);
              $TargType = 1;
              $Target = $Wid;
            } else {
              $Body = Get_Moon(-$Wid);
              $TargType = 2;
              $Target = - $Wid;
            }
            $xtra = '';
            if ($Body['TargetMod']) $xtra = " (Level +" . $Body['TargetMod'] . ")";
            echo "<button class=projtype type=submit formaction='OpsNew.php?t=$Turn&O=$OrgId&Stage=3&op=$op&W=$Wh&TType=$TargType&Target=$Target'>" .
              $Body['Name'] . "$xtra</button> \n";
          }
          break;
        } // Deliberate fallthrough

      } else if ($OpTypes[$op]['Props'] & OPER_OWNEDSPACE) {



        if ($TSys['Control']) {
          if ($TFid) {
            $OutPs = Get_Things_Cond(0,"Type=" . $TTNames['Outpost'] . " AND SystemId=$Wh AND BuildState=" . BS_COMPLETE);
            if ($OutPs) {
              $TargType = 3;
              $Target = $OutP[0]['id'];
            } else {
              echo "There is no World or Outpost in " . System_Name($TSys,$Fid) . "<p>\n";
              break;
            }
          } else {
            echo "There is no World or Outpost in " . System_Name($TSys,$Fid) . "<p>\n";
            break;
          }
        }
      }
      */


      // Deliberate fallthrough

    case 3: // Secondary questions

      $Data = "t=$Turn&O=$OrgId&Stage=4&op=$op&W=$Wh&TType=$TargType&Target=$Target";
      if ($OpTypes[$op]['Props'] & OPER_BRANCH) {
        $AllReady = Gen_Get_Cond('Branches'," HostType=$TargType AND HostId=$Target AND Organisation=$OrgId" );
        if ($AllReady) {
          echo "There is already a branch of " . $Org['Name'] . " on " . $Body['Name'] . " in " . System_Name($TSys,$Fid) . "</h2>\n";
          break;
        }
      }

      if ($OpTypes[$op]['Props'] & OPER_SCIPOINTS) {
        if ($Target > 0) {
          $Ds = Get_DistrictsP($Target);
        } else if ($Target < 0) {
          $Ds = Get_DistrictsM($Target);
        } else {
          $Ds = 0;
        }

        if ($Ds) { // It has a space age civ

          echo "<h2>Select Type of Science Points to Collect</h2>";
          for ($i=1;$i<4;$i++) {
            echo "<button class=projtype type=submit formaction='OpsNew.php?$Data&SP=$i'>" .
               $Fields[$i-1] . "</button>\n";
          }
          break;

        } else {
          $SP = 3;
          // Xenology only
        }
      }

      if ($OpTypes[$op]['Props'] & OPER_TECH) {
        $With = $Body['Control']??0;
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
            echo "<button class=projtype type=submit formaction='OpsNew.php?$Data&Te=$Tid&P2=$Lvl'>" .
            $TT['Name'] . " at level $Lvl</button> \n";
            $Shown = 1;
          }
        }


        foreach ($MyTechs as $Tid=>$T) {
          $Tec = $Techs[$Tid];
          if (($Techs[$T['Tech_Id']]['Cat']??0) == 0 || (isset($FactTechs[$Tid]) && ($FactTechs[$Tid]['Level'] > 0)) ) continue;
          if (!isset($FactTechs[$Tec['PreReqTech']]) ) continue;
          if ($FactTechs[$Tec['PreReqTech']]['Level'] < $Tec['PreReqLevel'] ) continue;

          if ($Tec['PreReqTech2'] && ((! isset($FactTechs[$Tec['PreReqTech2']])) || $FactTechs[$Tec['PreReqTech2']]['Level'] == 0)) continue;
          if ($Tec['PreReqTech3'] && ((! isset($FactTechs[$Tec['PreReqTech3']])) || $FactTechs[$Tec['PreReqTech3']]['Level'] == 0)) continue;

          $Lvl = $Tec['PreReqLevel'];
          if ($Lvl < 1) continue;
          echo "<button class=projtype type=submit formaction='OpsNew.php?$Data&Te=$Tid&P2=$Lvl'>" .
          $Tec['Name'] . " at level $Lvl</button> \n";
          $Shown = 1;
        }

        if (!$Shown) echo "<h2>There are no Techs you know that could be shared with " . ($Facts[$With]['Name']??"<h2 class=err>NOBODY</h2>") . "</h2>\n";
        break;
      }

      if ($OpTypes[$op]['Props'] & OPER_SOCP) { // Burn Heretics
        //        var_dump($ThingType,$ThingId);
        $World = Gen_Get_Cond1('Worlds',"ThingType=$TargType AND ThingId=$Target");
        $Wid = ($World['id']??0);
        $SP = $Org['SocialPrinciple'];
        $SocPs = Get_SocialPs($Wid);

        //        var_dump($World,$Wid,$Fid);
        if (($World['FactionId']??0) != $Fid) { // Not own World, find if branch
          $Brs = Gen_Get_Cond('Branches',"Whose=$Fid AND HostType=$TargType AND HostId=$Target");
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
            if ($Prin) echo "<button class=projtype type=submit formaction='OpsNew.php?$Data&SP=$SP'>" .
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
            echo "<button class=projtype type=submit formaction='OpsNew.php?$Data&SP=$Lid'>" .
            $L['Name'] . "</button> \n";
          }
        }
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
              echo "<button class=projtype type=submit formaction='OpsNew.php?$Data&SP=$Aid'>$AL</button> \n";
            }
          }
        } else {
          echo "<h2>There are no known anomalies there</h2>";
          break;
        }
      }

      if (($OpTypes[$op]['Props'] & OPER_TARGETORG)) {
        // Orgs from offices and non hidden branches on target
        $OrgList = [];
        $Orgs = Gen_Get_All_GameId('Organisations');

        $Branches = Gen_Get_Cond('Branches',"HostType=$TargType AND HostId=$Target");
        // var_dump($Branches);
        if ($Branches) {
          $BTs = Gen_Get_All('BranchTypes');
          foreach ($Branches as $B) {
            if (!($BTs[$B['Type']]['Props'] & BRANCH_HIDDEN)) $OrgList[$B['Organisation']] = $Orgs[$B['Organisation']]['Name'];
          }
        }

        $Wid = 0;
        if ($TargType) {
          $PH = Gen_Get_Cond1('ProjectHomes',"ThingType=$TargType AND ThingId=$Target");
          if ($PH) {
            $World = Gen_Get_Cond1('Worlds',"Home=" . $PH['id']);
            if ($World) $Wid = $World['id'];
          }
        }

        if ($Wid) {
          $Offices = Gen_Get_Cond('Offices',"World=$Wid");
          // var_dump($Offices);
          if ($Offices) {
            foreach ($Offices as $O) {
              $OrgList[$O['Organisation']] = $Orgs[$O['Organisation']]['Name'];
            }
          }
        }
        // var_dump($OrgList);
        if ($OrgList) {
          echo "<h2>Select the Organisation to Recon:</h2>";

          foreach ($OrgList as $Oid=>$O) {
            echo "<button class=projtype type=submit formaction='OpsNew.php?$Data&SP=$Oid'>$O</button> \n";
          }
          break;

        } else {
          echo "<h2>There are no known Organisations there</h2>";
          break;
        }
      }
      // Drop through


    case 4: // Tertary Questions

//      var_dump($Target,$TargType);
      $TSys = Get_System($Wh);

      $Name =  $OpTypes[$op]['Name'] . " in " . System_Name($TSys,$Fid) ;
      if (isset($Body['Name'])) $Name .= " at " . $Body['Name'];
      if (empty($Head)) echo "<h2>Selected: $Name</h2>\n";
      $Head = 1;

      $Drop = 0;

      if (($OpTypes[$op]['Props'] & (OPER_LEVELMOD | OPER_DESC))) {
        echo fm_hidden('Stage',5) . fm_hidden('op',$op) . fm_hidden('W',$Wh) . fm_hidden('O',$OrgId) .fm_hidden('t',$Turn) .
        fm_hidden('TType',$TargType) . fm_hidden('Target',$Target);

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

      // COmpound questions

      if (($OpTypes[$op]['Props'] & OPER_MONEY)) {
        $To1 = $Body['Control']??0;
        $To2 = 0;

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
        if ($To1 == $Fid) $To1 = 0;
        if ($To2 == $Fid) $To = 0;
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
        echo fm_hidden('Stage',5) . fm_hidden('op',$op) . fm_hidden('W',$Wh) . fm_hidden('O',$OrgId) . fm_hidden('t',$Turn) .
        fm_hidden('TType',$TargType). fm_hidden('Target',$Target);
        if ($DOP2) echo fm_hidden('P2',$P2);
        echo fm_number('',$_REQUEST,'SP','','min=0 max=' . $Facts[$Fid]['Credits'] );
        echo "<button class=projtype type=submit>Send Money</button>";
        break;
      }

      // else drop through

    case 5: // Complete /Restart/ etc
      $TSys = Get_System($Wh);
      $TFid = $TSys['Control'];
      $Level = 0;
      $Name =  $OpTypes[$op]['Name'] . " in " . System_Name($TSys,$Fid) ;

//      var_dump($Body);
      if (isset($Body['Name'])) $Name .= " at " . $Body['Name'];

      if (!isset($Head)) {
        echo "<h2>Selected: $Name</h2>\n";
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
        $World = WorldFromTarget($TargType,$Target);
        $Wid = $World['id']??0;
        $CurVal = Gen_Get_Cond1('SocPsWorlds',"Principle=$SP AND World=$Wid");
        $P2 = ($CurVal['id']??0);
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
        $Name .= " - " . $Fields[$SP-1];
      }
      if (($OpTypes[$op]['Props'] & OPER_LEVELMOD)) {
        $AMod = $SP;
      }

      if (($OpTypes[$op]['Props'] & OPER_DESC)) {
        $Name .= " - $Desc";
      }


//      var_dump($OpTypes[$op]['Props'],$Name);
      if (($OpTypes[$op]['Props'] & OPER_LEVELMOD) == 0) $Mod = ($OpTypes[$op]['Props'] & OPER_LEVEL);
 //     var_dump($Mod,$Level);
      if ($Mod >= 4) {
        $Mod = ($Mod&3) + $Level*($Mod>>2);
      }
      if($AMod) $Mod += $AMod;
      if ($Body['TargetMod']??0) {
        $ExtraLevels = $Body['TargetMod'];
        $xtra = " (Level +" . $Body['TargetMod'] . ")";
      }

      $BaseLevel = Op_Level($OrgId,$Wh) + $ExtraLevels;
      if ($BaseLevel<0) {
        echo "<h2 class=Err>No path found to one of your worlds or branches</h2>";
        break;
      }
      if (Has_Trait($Fid,'IMPSEC') && strstr($OpTypes[$op]['Name'],'Recon')) $Mod--;

      if (Has_Trait($Fid,'Friends in All Places') && (($OpTypes[$op]['Props'] & OPER_NOT_FRIENDS) == 0)) {
        $World = WorldFromTarget($TargType,$Target);
        $Wid = $World['id']??0;
        $CC = Gen_Get_Cond1('SocialPrinciples',"Principle='Confluence'");
        if ($CC) {
          $Confl = $CC['id'];
          $SPW = Gen_Get_Cond1('SocPsWorlds',"World=$Wid AND Principle=$Confl");
          if ($SPW && $SPW['Suppression']==0) $Mod--;
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
           "formaction='OpsDisp.php?ACTION=NEW&t=$Turn&O=$OrgId&op=$op&W=$Wh&TargType=$TargType&Target=$Target&SP=$SP&Te=$TechId&P2=$P2&N=" .
           base64_encode("$Name") . "&L=$BaseLevel&PN=$ProgNeed&Desc=" . base64_encode("$Desc") . "'>$Name</button> \n";

      echo "Click to confirm<p>";

  }
  echo "<p><h2><a href=OpsNew.php?t=$Turn&O=$OrgId>Back to start</a> , <a href=OpsDisp.php?id=$Fid>Cancel</a></h2>\n";
  dotail();

