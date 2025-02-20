<?php
// Lib of Thing related stuff
include_once("sk.php");
include_once("GetPut.php");
include_once("PlayerLib.php");
include_once("OrgLib.php");




function Show_Thing(&$T,$Force=0) {
  include_once("ProjLib.php");
  global $BuildState,$GAME,$GAMEID,$FACTION;
  global $Project_Status,$Advance;
  global $ModFormulaes,$ModValues,$Fields,$Tech_Cats,$CivMil,$BuildState,$ThingInstrs,$ThingInclrs, $InstrMsg, $ValidMines;
  global $Currencies,$InstrNotBy,$NOTBY;

  $ThingInclrs = ['white','lightgreen','lightpink','lightblue','lightyellow','bisque','#99ffcc','#b3b3ff',
                 'lightgreen','lightpink','lightblue','lightyellow','bisque','#99ffcc','#b3b3ff',
                 'lightgreen','lightpink','lightblue','lightyellow','bisque','#99ffcc','#b3b3ff'];

  $Tid = $T['id'];

  if ($Force) {
    $GM = 0;
  } else {
    $GM = Access('GM');
  }

  $Fid = $T['Whose'];
  $ttn = Thing_Type_Names();
  $ntt = array_flip($ttn);
  $FactNames = Get_Faction_Names();
  $Fact_Colours = Get_Faction_Colours();
  $ThingProps = Thing_Type_Props();
  $tprops = ($ThingProps[$T['Type']]??0);
  $N = Get_System($T['SystemId']);
  $Syslocs = Within_Sys_Locs($N);
  $LinkTypes = Get_LinkLevels();
  if ($Fid) $Faction = Get_Faction($Fid);
  $MTs = Get_ModuleTypes();
  $MNs = NamesList($MTs);
  $NamesMod = array_flip($MNs);
  $IsaTeam = ($tprops & THING_ISA_TEAM);
  if ($IsaTeam) {
    $Org = Gen_Get('Organisations',$T['Dist1']);
  }
  $Varies = Gen_Get_All_Game('Variants');

  if ($T['SystemId'] == $T['NewSystemId'] || $T['NewSystemId'] == 0) {
    $NewSyslocs = $Syslocs;
  } elseif ($T['NewSystemId']) {
    $NN = Get_System($T['NewSystemId']);
    $FS = Get_FactionSystemFS($Fid,$T['NewSystemId']);
    if ($FS['ScanLevel'] >= 0) {
      $NewSyslocs = Within_Sys_Locs($NN);
    } else {
      $NewSyslocs = [];
    }
  } else {
    $NewSyslocs = [];
  }

//  echo "B1 " . $T['CurHealth'] . "<p>";
  $Systems = Get_SystemRefs();
  $T['MaxModules'] = Max_Modules($T);
  if  (($tprops & THING_HAS_MODULES) && ($T['PrisonerOf'] == 0)) [$T['OrigHealth'],$T['ShieldPoints']] = Calc_Health($T,1);

  if (($T['BuildState'] == 3) && ($tprops & THING_CAN_MOVE) && ($T['CurHealth'] > 0) && ($T['LinkId']>=0) ) { // Complete Only
    $res = Moves_4_Thing($T,$Force, ($tprops & (THING_HAS_GADGETS | THING_CAN_BETRANSPORTED)), $N);
//var_dump($res);exit;
    [$Links, $SelLinks, $SelCols ] = $res;

  } else {
    $Links = $SelLinks = $SelCols = [];
  }

//var_dump($SelLinks);exit;
/*
  if ($Links && $T['LinkId'] >= 0 && !isset($Links[$T['LinkId']]['Level'])) {
    var_dump($T); echo "<p>"; var_dump($Links); echo "<p>"; // OK
  }
*/

  if ((($T['LinkId'] ?? 0) > 0) && (!isset($Links[$T['LinkId']]))) {
    $T['LinkId'] = 0;
    Put_Thing($T);
  }
  $MovesValid = 1;
  if (Has_Trait($Fid,'Star-Crossed')) {
    if (IsPrime($GAME['Turn'])) {
      $MovesValid = 0;
      echo "You are Star Crossed - No movement this turn<p>";
    }
  }

  echo "<form method=post id=mainform enctype='multipart/form-data' action=ThingEdit.php>";

  $ll = ($T['LinkId']>0 ? $Links[$T['LinkId']]['Level'] : 0);
  $LOWho = GameFeature('LinkOwner',0);
  if ($Links && ($T['LinkId']>0) && ($LinkTypes[$ll]['Cost'] > 0) && $LOWho && $LOWho != $T['Whose']) {
    if ($tprops & THING_HAS_GADGETS) {
      $Lc = $LinkTypes[$ll]['AgentCost'];
    } else {
      $Lc = $LinkTypes[$ll]['Cost']*$T['Level'];
    }

//    $Lc = $LinkTypes[$ll][($tprops & THING_HAS_GADGETS) ? 'AgentCost':'Cost']*$T['Level'];
    if ($T['LinkPay']==0 || $T['LinkCost'] < $Lc) {
      $LOwner = Get_Faction($LOWho);
      echo "<h2>You are taking a <span style='color:" . $LinkTypes[$ll]['Colour'] . "'>" . $LinkTypes[$ll]['Name'] .
          "</span> link do you need to pay " . credit() . "$Lc to " . $LOwner['Name'] . " for this? ";

      echo fm_hidden('LinkCost', $Lc) . fm_submit("ACTION",'Pay on Turn',0);
      echo "</h2>\n";
    }
    $T['LinkCost'] = $Lc;
    Put_Thing($T);
  }

  if ($T['BuildState'] == 0) echo "Note the Tech level of this will be recorded when it is built<br>";

  echo "<div class=tablecont><table width=90% border class=SideTable>\n";
  Register_AutoUpdate('Thing',$Tid);
  echo fm_hidden('id',$Tid) . fm_submit("ACTION",'Refresh',0,'hidden');

  if ($GM) {
    echo "<tr class=NotSide><td class=NotSide>Id: $Tid<td class=NotSide>Game: $GAMEID - " . $GAME['Name'];
    echo fm_number('Seen Mask',$T,'SeenTypeMask','class=NotSide','class=NotSide');
    if ($T['BluePrint']<0) echo fm_text1('Gated On',$T,'GatedOn',2,'class=NotSide');
//    if (feature('HiddenControl')) echo "<td colspan=2>Hidden Control: " . fm_select($FactNames,$T,'HiddenControl');
    echo "<tr><td>Type:<td>" . fm_select($ttn,$T,'Type',1);
    if (($tprops & THING_HAS_LEVELS) || ($tprops & THING_CAN_BE_ADVANCED)) echo fm_number1("Level",$T,'Level');
    if ($tprops & THING_HAS_VARIANTS) {
      $varlist = NamesList($Varies);
      echo "<td>Varient: " . fm_select($varlist, $T, 'Variant',1);
    }
    echo fm_number1('Priority',$T,'Priority');
    if ($tprops & THING_NEEDS_PLANET)
      echo fm_number1('Planet',$T,'Dist1') . ($T['Dist1']? "<td><a href=PlanEdit.php?id=" . $T['Dist1'] . ">Visit</a>":'');
    if ($tprops & THING_HAS_AGE) echo fm_number1('Age',$T,'Dist1','','min=0 max=1000') . " optional";
//    if (Access('God') echo fm_number1('Turn Moved',$T,
  } else {
    echo "<tr><td>Type:<td>" . ( (($tprops & THING_CAN_BE_ADVANCED) && $T['Level']>1)? $Advance[$T['Level']] : '' ) . $ttn[$T['Type']];
    if ($tprops & THING_HAS_LEVELS) echo "<td>Level: " . $T['Level'];
    if (($tprops & THING_HAS_VARIANTS) && $T['Variant']) echo "<td>Variant: " . $Varies[$T['Variant']]['Name'];
    echo fm_number('Priority',$T,'Priority');
  }
  if ($tprops & THING_HAS_AGE) echo fm_number1('Age',$T,'Dist1','','min=0 max=1000') . " optional";
  echo "<tr>" . fm_text('Name',$T,'Name',2);

  echo "<td rowspan=4 colspan=4><table><tr>";
    echo fm_DragonDrop(1,'Image','Thing',$Tid,$T,1,'',1,'','Thing');
  echo "</table>";

  $ChangeBox = 0;
  if (Feature('BluePrints') && ($tprops & THING_HAS_BLUEPRINTS) && ($T['BluePrint']??0) && !$GM ){
    echo "<tr><td>Class:<td>" . $T['Class'];
    if ($T['HiddenClass']) echo "<td>Actually: " . $T['HiddenClass'];
  } else if ($IsaTeam) {
    echo "<tr><td>Organisation:<td>" . $Org['Name'];
  } else if (!$GM) {
    echo "<tr>" . fm_text('Class',$T,'Class',2);
  } else {
    echo "<tr>" . fm_text('Class',$T,'Class');
    echo fm_text1('Hidden Class',$T,'HiddenClass');
  }
// WHERE IS IT AND MOVEMENT

/*
  if ($T['PrisonerOf']) {
    echo "<tr><td>Prisoner";
  } else {
  */
    echo "<tr><td>Build State:<td>" . ($GM? fm_select($BuildState,$T,'BuildState') : $BuildState[$T['BuildState']]);
    if (isset($T['BuildState']) && $T['BuildState'] <= 1) {
      if ($GM) echo fm_number1('Build Project',$T,'ProjectId');
      if ($T['ProjectId']) {
        $Proj = Get_Project($T['ProjectId']);
        echo "<tr><td>See <a href=ProjEdit.php?id=" . $T['ProjectId'] . ">Project</a>";
        echo "<tr><td>Status: " . $Project_Status[$Proj['Status']];
        if ($Proj['TurnStart']) echo " Start Turn: " . $Proj['TurnStart'];
        if ($Proj['TurnEnd']) echo " End Turn: " . $Proj['TurnEnd'];
      }
    }
    if (($T['BuildState'] == 2) || ($T['BuildState'] == 3) && ($tprops & THING_HAS_BLUEPRINTS)) {
      if ($GM && Feature('BluePrints')) {
        echo fm_number1('Blue Print',$T,'BluePrint','','min=-100 max=10000') ;
        BlueShow($T,$GM);
      }
    }
// Note Special meaning of -ve LinkId numbers:
// -1 On Board - SytemId = ThingId of Host
// -2 Boarding on Turn NewSystemId = ThingId of Host
// -3 Unboarding on turn NewWithinSys = Target within
// -4 Board and unboard see -2,-3 for extra fields
// -5 Not Used
// -6 Direct Move to NewSystemId, NewWithinSys without links
// -7 Follow
// -8 In Branch

      $Lid = ($T['LinkId'] ?? 0);
      if ($tprops & THING_MOVES_DIRECTLY) {
          if ($GM) {
            echo "<tr><td>System:<td>" . fm_select($Systems,$T,'SystemId',1,' onchange=SetSysLoc("SystemId","WithinNow","WithinSysLoc")');
            if ($T['BuildState'] < 2) {
              echo "<td>";
              BlueShow($T,$GM);
            } else if ($T['SystemId']??0) {
              echo "<td id=WithinNow>" . fm_select($Syslocs,$T,'WithinSysLoc');
            } else {
              echo "<td id=WithinNow>";
            }
          } elseif ($T['PrisonerOf']) {
            echo "<tr><td>Currently a Prisoner";
          } else {
            echo "<tr><td>Current System:<td>" . $N['Ref'] . "<td>" . $Syslocs[$T['WithinSysLoc']];
          }
        $T['LinkId'] = -6;
        echo "<tr><td>New System:<td>" . fm_select($Systems,$T,'NewSystemId',1,' onchange=SetSysLoc("NewSystemId","WithinNew","NewLocation")');
        if ($T['BuildState'] < 2) {
          echo "<td>";
          BlueShow($T,$GM);
        } else if ($T['SystemId']??0) {
          echo "<td id=WithinNew>" . fm_select($Syslocs,$T,'NewLocation');
        } else {
          echo "<td id=WithinNew>";
        }
      // TODO NewLocation
      } else {
  // if ($GM) echo "Lid:$Lid SystemId:" . $T['SystemId']; // TEST CODE DELIBARATELY STILL BEING USED - GM ONLY
        if ($Lid<0 && Access('God') ) {
  //        echo fm_number0("<br>Lid",$T,'LinkId') . fm_number1("SysId",$T,'SystemId');
        }
        if ($Lid >= 0 || $Lid == LINK_BOARDING || $Lid == LINK_LOAD_AND_UNLOAD) { // Insystem
          if ($GM) {
            echo "<tr><td>System:<td>" . fm_select($Systems,$T,'SystemId',1);
            echo "<td>";
            if ($T['BuildState']> 2 ) {
              if ($T['Retreat']) {
                $Retreats = ['','From Nebula','From Combat'];
                echo "Retreat:" . fm_select($Retreats,$T,'Retreat');
              } else {
                echo fm_select($Syslocs,$T,'WithinSysLoc');
              }
            } else {
              BlueShow($T,$GM);
            }
          } elseif ($T['PrisonerOf']) {
            echo "<tr><td class=Err>Currently a Prisoner";
          } else {
            echo "<tr><td>Current System:<td>" . (empty($N)? 'Unknown' : $N['Ref']) . "<td>";
            if ($T['BuildState']> 2 ) {
              $Conflict = 0;
              $Conf = Gen_Select("SELECT W.* FROM ProjectHomes PH, Worlds W WHERE PH.SystemId=" . $T['SystemId'] .
                " AND W.Home=PH.id AND W.Conflict=1");
              if ($Conf) $Conflict = $Conf[0]['Conflict'];

              if ($Conflict || ($tprops & THING_ISA_TEAM)) {
                echo ($Syslocs[$T['WithinSysLoc'] ?? 0] ?? 'Deep Space');
              } else {
                echo fm_select($Syslocs,$T,'WithinSysLoc');
                $ChangeBox = 1;
              }
            } else {
              BlueShow($T,$GM);
            }
          }
        } else if ($Lid == LINK_FOLLOW ) {
          $Fol = Get_Thing($T['NewSystemId']);
          $LastWhose = 0;
          $tting = SeeThing($Fol,$LastWhose,7,$Fid,0,0,0);
          echo "<tr><td colspan=3>Following: " . "<span style='background:" . $Fact_Colours[$Fol['Whose']] . "'>" .
               SeeThing($Fol,$LastWhose,7,$Fid,0,0,0) . "</span>";
          echo fm_submit("ACTION",'Cancel Follow',0);
        } else if ($Lid == LINK_INBRANCH ) {
          echo "<tr><td>Within the Branch";
          if ($GM) echo "<td><a href=BranchEdit.php?id=" . $T['ProjectId'] . ">Branch</a>";
        } else { // On Board
          $Host = Get_Thing($T['SystemId']);
          if ($Host) {
            echo "<tr><td colspan=3>In: " . $Host['Name'];
            $Conflict = 0;
            $Conf = Gen_Select("SELECT W.* FROM ProjectHomes PH, Worlds W WHERE PH.SystemId=" . $T['SystemId'] .
                    " AND W.Home=PH.id AND W.Conflict=1");
            if ($Conf) $Conflict = $Conf[0]['Conflict'];

            if ($Host['LinkId']>0 && $Host['TargetKnown'] == 0) {
              echo " You don't know where you are going to unload afterwards<br>";
              if ($GM) {
                if ($Lid == -1 || $Lid == -2) echo fm_submit("ACTION",'Unload After Move',0);
                echo fm_submit("ACTION",'Unload Now',0);
              } else if ($Fid == $Host['Whose'] || $Host['Whose'] == $Faction['id'] ) {
                echo fm_submit("ACTION",'Unload Now',0);
              }
            } else {
              if ($Lid == -1 || $Lid == -2) if ($Fid == $Host['Whose'] || $Host['Whose'] == $Faction['id'] )
                echo fm_submit("ACTION",'Unload After Move',0);
                if ($GM || (!$Conflict && ($Fid == $Host['Whose'] || $Host['Whose'] == $Faction['id'] ))) {
                echo fm_submit("ACTION",'Unload Now',0);
              } else {
                echo " - Only the transport owner can unload you";
              }
              echo "<br>Note: To unload AFTER moving, please put the movement order in to the system for the " .
                "transport before the Unload After Move order.<br>\n";
            }
          } else {
            echo "<tr><td colspan=3>In limbo... (Richard can fix)";
            if (Access('God')) $T['SystemId'] = $T['LinkId'] = 0;
          }
      }
      if ($Lid == -2 || $Lid == -4) {
        $Host = Get_Thing($T['NewSystemId']);
        echo "<tr><td colspan=3>Loading on to: <b>" . $Host['Name'] . "</b> on the turn";
      }

      if ($Lid == -3 || $Lid == -4) {
        if ($Lid == -4) {
          echo " and then unloading to ";
          $HostId = $T['NewSystemId'];
        } else {
          echo "Unloading to ";
          $HostId = $T['SystemId'];
        }
        $Host = Get_Thing($HostId);
        if ($Host['LinkId'] > 0) {
          $Dest = $Host['NewSystemId'];
        } else if ($Host['LinkId'] == 0) {
          $Dest = $Host['NewSystemId'];
        } else {
          $HostId = $Host['SystemId'];
          $H = Get_Thing($HostId);
          $Hlid = $H['LinkId'];
          if ($Hlid > 0) {
            $Dest = $H['NewSystemId'];
          } else if ($Hlid == 0) {
            $Dest = $H['SystemId'];
          } else {
            $Dest = 0;
          }
        }

        if ($Dest) {
          $DN = Get_System($Dest);
          $FS = Get_FactionSystemFS($Fid,$Dest);
// var_dump($FS,$Fid,$Dest);

          if ($FS['ScanLevel'] >= 0 ) {
            $finallocs = Within_Sys_Locs($DN);
            if (!isset($finallocs[$T['NewLocation']]) ) {
              $T['NewLocation'] = 0;
              Put_Thing($T);
            }
            echo "<b>" . $DN['Ref'] . " - " . $finallocs[$T['NewLocation']] . "</b> ";
          } else {
            echo "<b>Unknown location" . ($GM?":$Dest":"") . "</b> ";
          }
        } else {
          echo "<b>Unknown location" . ($GM?":$Dest":"") . "</b> ";
        }
      }


      $NeedOr = 0;
      if ((($tprops & THING_CAN_MOVE)) && ($Lid >= 0) && ($T['BuildState']>1) && $T['SystemId']) {

        if (($T['BuildState'] == 2) || ($T['CurHealth'] == 0) || (empty($SelLinks) && !$ChangeBox) ) { // Shakedown or just warped out
          echo "<tr><td colspan=3>This is unable to use links, it can move within the system.<br>Where in the system should it go? " .
                fm_select($Syslocs,$T,'WithinSysLoc');
        } else {
          if (($T['Instruction'] != 0) && ($T['Instruction'] != 5) && ($T['Instruction'] != 21) ) {
            echo "<tr><td class=Err>Warning Busy doing:<td>" . $ThingInstrs[abs($T['Instruction'])] . "<td class=Err>Moving will cancel";
          }

          if ($GM) {
            if ($Lid == LINK_FOLLOW) { // Never used...
              echo "<tr><td>Following:<td>";
              $Fol = $T['NewSystemId'];
              $FThing = Get_Thing($Fol);
              echo "<a href=ThingEdit.php?id=$Fol>$Fol</a><td>" . $FThing['Name'] . " (" . $FactNames[$FThing['Whose']] . " )";
            } else {
              echo "<tr><td>Taking Link:<td>" . fm_select($SelLinks,$T,'LinkId',0," style=color:" . $SelCols[$T['LinkId']] ,'',0,$SelCols);
              if ($ll>0 && $LinkTypes[$ll]['Cost'] && $LOWho && $LOWho != $T['Whose'] ) {
                echo fm_checkbox('Pay',$T,'LinkPay') . " " . Credit() . $T['LinkCost'];
              }
              echo "<br>Update this normally";
              if ($Lid > 0) {
                echo "<td>To:  " . fm_select($NewSyslocs,$T,'NewLocation');
              } else {
                echo "<td>To:  " . fm_select($Syslocs,$T,'NewLocation');
              }
            }
          } elseif ($T['PrisonerOf']) {
            // No Info provided
          } else if ($Lid == LINK_FOLLOW) {
            echo "<tr><td>Following:<td>";
            $Fol = $T['NewSystemId'];
            $FThing = Get_Thing($Fol);
            echo "<a href=ThingEdit.php?id=$Fol>$Fol</a><td>" . $FThing['Name'] . " (" . $FactNames[$FThing['Whose']] . " )";
          } else if (!$MovesValid) {
            echo "<tr><td>Star Crossed<td>No Movement<td>";
          } else {
            echo "<tr><td>Taking Link:<td>" . fm_select($SelLinks,$T,'LinkId',0," style=color:" . $SelCols[$T['LinkId']] ,'',0,$SelCols);
            if ($ll && $LinkTypes[$ll]['Cost'] && $LOWho && $LOWho != $T['Whose'] ) {
              echo fm_checkbox('Pay',$T,'LinkPay') . " " . Credit() . $T['LinkCost'];
            }
            if ($Lid > 0 && !strpos($SelLinks[$Lid],'?')) {
              echo "<td>To:  " . fm_select($NewSyslocs,$T,'NewLocation');
            } else {
              echo "<td>Move to:  " . fm_select($Syslocs,$T,'NewLocation');
            }
          }

        }
        if (($Lid > 0) && $MovesValid) {
          echo fm_submit("ACTION",'Cancel Move',0);
          $NeedOr = 1;
        }
        if (Feature('Follow') && $MovesValid && ($tprops & THING_CAN_MOVE )) {
          global $db;
          $ThisSys = $T['SystemId'];
          $Eyes = EyesInSystem($Fid,$ThisSys,$Tid);
          if ($Eyes) {
            $OtherShips = $db->query("SELECT t.* FROM Things t, ThingTypes tt WHERE t.GameId=$GAMEID AND ".
                           "t.type=tt.id AND (tt.Properties&0x100)!=0 AND t.SystemId=$ThisSys AND Whose!=$Fid");
            if ($OtherShips) {
              $List = [];
              $Colrs = [];
              $LastWhose = 0;
              while ($Thing = $OtherShips->fetch_array()) {
                $Ttxt = SeeThing($Thing,$LastWhose,$Eyes,$Fid,0,0,0);
                if ($Ttxt) {
                  $List[$Thing['id']] = $Ttxt;
                  $Colrs[$Thing['id']] = $Fact_Colours[$Thing['Whose']];
                }
              }
              if ($List) {
                echo "<td>Or Follow:<td colspan=2>";
                echo fm_select($List,$T,'FollowId',blank:1,Raw:1,BGColour:$Colrs);
//            echo fm_radio('',$List,$_REQUEST,'ToFollow',tabs:0,colours:$Colrs, extra4:' onchange=this.form.submit()');
              } else {
                echo "<td>Nothing to follow";
              }
            }
          }
        }

      }
//var_dump("Here",$Lid,$tprops);
      if (($Lid == 0) && (($tprops & THING_CAN_BETRANSPORTED)) && (($T['PrisonerOf'] == 0) || ($T['PrisonerOf'] == ($FACTION['id']??0)))) {
        $XPorts = Get_AllThingsAt($T['SystemId']);
// var_dump($XPorts);
        $NeedCargo = ($tprops & THING_NEEDS_CARGOSPACE);
        $TList = [];
        $FF = Get_FactionFactionsCarry($Fid);
        foreach($XPorts as $X) {
          if ($X['BuildState'] < 2 || $X['BuildState'] > 3) continue;
          if ($NeedCargo && ($X['CargoSpace']==0)) continue; // No Cargo
          if (($ThingProps[$X['Type']]??0) & THING_CANT_HAVENAMED) continue;
          if (($X['Whose'] == $Fid) || (($T['PrisonerOf'] == ($FACTION['id']??0) ) && (($X['Whose'] == ($FACTION['id']??0))))) {
            // Full through
          } else {
            $Carry = (empty($FF[$X['Whose']])? 0 : $FF[$X['Whose']]['Props']);
            if (!$NeedCargo) $Carry >>= 4;
            if (($Carry&15) < 2) continue; // Don't carry Another
          }

          if ($NeedCargo) {
            $Mods = Get_Modules($X['id']);
            $CargoSpace = ($Mods[$NamesMod['Cargo Space']]['Number']??0);
            $CryoSpace = ($Mods[$NamesMod['Cryo Pods']]['Number']??0)*2;

            $OnBoard = Get_Things_Cond(0,"((LinkId=-1 OR LinkId=-3) AND SystemId=" . $X['id'] . ")");
            foreach($OnBoard as $OB) if ($ThingProps[$OB['Type']] & THING_NEEDS_CARGOSPACE) {
              $Need = $OB['Level'];
              if ($CryoSpace && ($ThingProps[$OB['Type']] & THING_HAS_ARMYMODULES)) {
                $CryoSpace -= $Need;
                if ($CryoSpace >= 0) {
                  $Need = 0;
                } else {
                  $Need -= $CryoSpace;
                }
              }
              if ($Need && $CargoSpace) {
                $CargoSpace -= $Need;
                if ($CargoSpace >= 0) {
                  $Need = 0;
                } else {
                  $Need -= $CargoSpace;
                }
              }
            }
            $Space = $CargoSpace + (($tprops & THING_HAS_ARMYMODULES)?$CryoSpace:0);
            if ($Space < $T['Level']) continue;
          }
          $TList[$X['id']] = $X['Name'];
        }

        if ($TList) {
          echo "<tr><td colspan=3>" . ($NeedOr?" <b>Or</b> ":'') . "Board: " . fm_select($TList,$T,'BoardPlace') . fm_submit("ACTION",'Load on Turn',0);
          $Conflict = 0; // HOW?
          $Conf = Gen_Select("SELECT W.* FROM ProjectHomes PH, Worlds W WHERE PH.SystemId=" . $T['SystemId'] . " AND W.Home=PH.id AND W.Conflict=1");
          if ($Conf) $Conflict = $Conf[0]['Conflict'];
          if ($GM && $Conflict) echo " <b>Conflict</b> ";
          if ($GM || !$Conflict) echo fm_submit("ACTION",'Load Now',0);
        } else {
          echo "<tr><td>No Transport Avail";
        }
      } else if (($Lid < -1) && (($tprops & THING_CAN_BETRANSPORTED))) {

        if ($Lid == LINK_BOARDING)
          if ($GM || $Fid == $Host['Whose'] || $Host['Whose'] == $FACTION['id'] ) echo fm_submit("ACTION",'Unload on Turn',0);
        if ($Lid == LINK_BOARDING) {
          echo fm_submit("ACTION",'Unload on Turn',0);
        } else if ($Lid == LINK_UNLOAD) {
          echo fm_submit("ACTION",'Cancel Load',0);
        } else if ($Lid == LINK_LOAD_AND_UNLOAD) {
          echo fm_submit("ACTION",'Cancel Load and Unload',0);
        }
      }
    }


  if ($GM) echo "<tr>" . fm_radio('Whose',$FactNames ,$T,'Whose','',1,'colspan=6','',$Fact_Colours,0);
  if  ($tprops & THING_HAS_GADGETS) echo "<tr>" . fm_textarea("Gadgets",$T,'Gadgets',8,3);
  echo "\n<tr>";
  if  (feature('Orders') && ($tprops & THING_HAS_LEVELS)) echo fm_text("Orders",$T,'Orders',2);
  if ($GM) {
    echo "<td>Prisoner of: " . fm_select($FactNames,$T,'PrisonerOf');
    echo "<td colspan=2>Hidden Control: " . fm_select($FactNames,$T,'HiddenControl');
    echo "<td>" . fm_checkbox('Hide Owner',$T,'HideOwner');

    if (Access('God')) echo "<tr><td>GOD!: " . fm_number0('SystemId',$T,'SystemId') . fm_number1('WithinSysLoc',$T,'WithinSysLoc') .
      fm_number1('LinkId',$T,'LinkId') .
      fm_number1('NewSystemId',$T,'NewSystemId') . fm_number1('NewLocation',$T,'NewLocation') . fm_Number1('LastMoved',$T,'LastMoved');

  }
  echo "<tr>" . fm_textarea("Description\n(For others)",$T,'Description',8,2);
  echo "<tr>" . fm_textarea('Notes',$T,'Notes',8,2);
  if (Feature('NamedCrew')) echo "<tr>" . fm_textarea('Named Crew',$T,'NamedCrew',8,2);

  $Have = Get_Things_Cond(0," (LinkId=-1 OR LinkId=-3) AND SystemId=$Tid ");
  $Having = Get_Things_Cond(0," (LinkId=-2 OR LinkId=-4) AND NewSystemId=$Tid ");
  $Slots = Feature('ModuleSlots');

  if ($Have || $Having) {
    echo "<tr><td>Carrying:<td colspan=6>Note: To Unload after moving PLEASE put the move order in for the transport first.<br>";

//var_dump($Have,$Having);

    $Conflict = 0;
    $Conf = Gen_Select("SELECT W.* FROM ProjectHomes PH, Worlds W WHERE PH.SystemId=" . $T['SystemId'] . " AND W.Home=PH.id AND W.Conflict=1");
    if ($Conf) $Conflict = $Conf[0]['Conflict'];

    $NewRef = (empty($T['NewSystemId']) ? ($N['Ref']??'???') : (Get_System($T['NewSystemId'])['Ref'])??'???');

    $CargoUsed = $CryoUsed = 0; // Adds troops up as Cryo - checked later if it has

    if ($Have) foreach ($Have as $H) {
      $Hid = $H['id'];
      $hprops = $ThingProps[$H['Type']];
      echo "<a href=ThingEdit.php?id=$Hid>" . $H['Name'] . "</a> a " . (($hprops & THING_HAS_LEVELS)? "Level " . $H['Level'] : "") . " " . $ttn[$H['Type']];
      if ($GM && $Conflict) echo " <b>Conflict</b> ";
      if ($GM || !$Conflict ) echo fm_submit("ACT$Hid",'Unload Now',0);
      echo " to: " . ($N['Ref']??'???') . " - " . fm_select($Syslocs,$H,'WithinSysLoc',0,'',"WithinSysLoc:$Hid");
      if ($H['LinkId'] == -3) {
        echo " - Unloading on Turn to <b>$NewRef</b>, " . $NewSyslocs[$H['NewLocation']];
        echo fm_submit("ACT$Hid",'Cancel Unload',0);
      } else if ($T['NewSystemId'] && $T['TargetKnown']) {
        echo fm_submit("ACT$Hid",'Unload on Turn',0);
        echo " to: $NewRef - " . fm_select($NewSyslocs,$H,'NewLocation',0,'',"NewLocation:$Hid");
      }
      echo "<br>";
      if ($hprops & THING_NEEDS_CARGOSPACE) {
        if ($hprops & THING_HAS_ARMYMODULES) {
          $CryoUsed += $H['Level'];
        } else {
          $CargoUsed += $H['Level'];
        }
      }
    }

    if ($Having) foreach ($Having as $H) {
      echo "Loading on turn:<br>";

      $Hid = $H['id'];
      $hprops = $ThingProps[$H['Type']];
      echo "<a href=ThingEdit.php?id=$Hid>" . $H['Name'] . "</a> a " . (($hprops & THING_HAS_LEVELS)? "Level " . $H['Level'] : "") . " " . $ttn[$H['Type']];
      echo " to: " . $N['Ref'] . " - " . fm_select($Syslocs,$H,'WithinSysLoc',0,'',"WithinSysLoc:$Hid");
      if ($H['LinkId'] == -4) {
        echo " - Unloading on Turn";
      } else if ($T['NewSystemId'] && $T['TargetKnown']) {
        echo fm_submit("ACT$Hid",'Unload on Turn',0);
        echo " to: $NewRef - " . fm_select($NewSyslocs,$H,'NewLocation',0,'',"NewLocation:$Hid");
      }
      echo "<br>";
      if ($hprops & THING_NEEDS_CARGOSPACE) {
        if ($hprops & THING_HAS_ARMYMODULES) {
          $CryoUsed += $H['Level'];
        } else {
          $CargoUsed += $H['Level'];
        }
      }
    }
    $Mods = Get_Modules($Tid);

    $CargoSpace = ($Mods[$NamesMod['Cargo Space']]['Number']??0);
    $CryoSpace = ($Mods[$NamesMod['Cryo Pods']]['Number']??0)*2;

    if ($CryoSpace && $CryoUsed){
      $CryoSpace -= $CryoUsed;
      if ($CryoSpace < 0) {
        $CargoUsed -= $CryoSpace;
      }
    }
    if ($CargoSpace && $CargoUsed) $CargoSpace -= $CargoUsed;
    if ($CargoSpace < 0 ) echo "<span class=Err>This needs more cargo space, short by " . (-$CargoSpace) . "</span>\n";
//    echo "<a href=ThingEdit.php?ACTION=UnloadAll>Unload All</a>";
  }

  if ($GM) echo "<tr>" . fm_textarea('GM Notes',$T,'GM_Notes',8,2,'class=NotSide');

  if ($GM || empty($T['PrisonerOf'])) {
    $Hist = $RevHist = '';
//    $History = preg_split("/\n/",($T['History'] ?? ''));
//    $RevHist = implode("\n",array_reverse($History));

    $NewHist = Gen_Get_Cond('ThingHistory',"ThingId=$Tid ORDER BY id DESC");
    if ($NewHist) foreach($NewHist as $NH) {
      $Hist .= "Turn#" . $NH['TurnNum'] . " " . $NH['Text'] . "\n";
    }

    if (isset($_REQUEST['EDHISTORY'])) {
      echo "<tr>" . fm_textarea('History',$T,'History',8,2,'','','',($GM?'':'Readonly')); // NO LONGER WORKS
    } else {
//    echo "<tr>" . fm_textarea('History',$T,'History',8,2,'','','',($GM?'':'Readonly'));
      echo "<tr><td>History:<td colspan=8><textarea rows=2>$Hist$RevHist</textarea>";
    }
  }

  $Branches = Gen_Get_Cond('Branches', "HostType=3 AND HostId=$Tid");
  if ($Branches) {
    $BTypes = Get_BranchTypes();
    $OrgTypes = Get_OrgTypes();
    $BBL = [];
    foreach ($Branches as $bid=>$B) {
      $BB = 0;
      $Org = Gen_Get('Organisations',$B['Organisation']);
      $style = "style='background:" . $OrgTypes[$Org['OrgType']]['Colour'] . "'";
      if ($BTypes[$B['Type']]['Props'] & BRANCH_HIDDEN) $BB+=1;
      if ($B['Whose'] == $Fid) $BB+=2;
      if ($GM) $BB +=4;

      switch ($BB) {
        case 0:
        case 2:
        case 3:
          $BBL[] = $B['Name'] . " A " . $BTypes[$B['Type']]['Name'] . " of <span $style>" . $Org['Name'] .
            " ( " . $OrgTypes[$Org['OrgType']]['Name'] . ")</span>";
          break;
        case 1:
          continue 2;
        case 4:
        case 5:
        case 6:
        case 7:
          $BBL[] = "<a href=BranchEdit.php?id=$bid>" . $B['Name'] . " A " . $BTypes[$B['Type']]['Name'] . " of </a><span $style>" . $Org['Name'] .
            " ( " . $OrgTypes[$Org['OrgType']]['Name'] . ")</span>";
          break;
      }
    }
    if ($BBL) {
      echo "<tr><td>Branches<td colspan=6>" . implode("<br>",$BBL );
    }
  }


  if ($tprops & THING_HAS_2_FACTIONS) echo "<tr>" . fm_radio('Other Faction',$FactNames ,$T,'OtherFaction','',1,'colspan=6','',$Fact_Colours,0);
  if  ($tprops & (THING_HAS_MODULES | THING_HAS_ARMYMODULES | THING_HAS_HEALTH)) {
    if ($GM) {
      echo "<tr>" . fm_number1('Orig Health',$T,'OrigHealth','','min=0 max=10000');

      if (($T['CurHealth'] > 0) || ($T['BuildState'] <2)) {
        echo fm_number1('Cur Health',$T,'CurHealth','','min=0 max=10000');
      } else {
        echo fm_number1('Cur Health',$T,'CurHealth',' class=Err ','min=0 max=10000');
      }
      echo fm_number1('Shield Points',$T,'ShieldPoints', '', 'min=0 max=10000 class=Num3 ');
      echo fm_number1('Current Shield',$T,'CurShield', '', 'min=0 max=10000 class=Num3 ');
    } else {
      echo "<tr><td>Original Health: " . $T['OrigHealth'];
      if ($T['BuildState'] == 2 || $T['BuildState'] == 3) {
        if ($T['CurHealth'] > 0) {
          echo "<td>Current Health: " . $T['CurHealth'];
        } else {
          echo "<td class=Err>Current Health: <b>0</b>";
        }
      }
      if ($T['ShieldPoints']) echo "<td>Shield Points: " . $T['ShieldPoints'];
    }

    $Resc =0;
    $BD = Calc_Damage($T,$Resc);
    Calc_Evasion($T);
    if ($Resc) {
      echo "<td>Basic Damage: $BD - There are other weapons";
    } else {
      echo "<td>Damage: $BD";
    }
    if ($GM){
      echo fm_number1('Evasion',$T,'Evasion','','min=0 max=10000');
    } else {
      echo "<td>Evasion: " . max(0,min($T['Evasion'],80));
    }
  }
  if ($tprops & THING_HAS_DISTRICTS) {
    $DTs = Get_DistrictTypeNames();
    $Dists = Get_DistrictsT($Tid);

    $NumDists = count($Dists);
    $dc=0;
    $totdisc = 0;

    if ($GM) {
      if ($NumDists) echo "<tr><td rowspan=" . ceil(($NumDists+2)/2) . ">Districts:";

      foreach ($Dists as $D) {
        $did = $D['id'];
        if (($dc++)%2 == 0)  echo "<tr>";
        echo "<td>" . fm_Select($DTs, $D , 'Type', 1,'',"DistrictType-$did") . fm_number1('', $D,'Number', '','',"DistrictNumber-$did");
        $totdisc += $D['Number'];
      };


      echo "<tr><td>Add District Type<td>" . fm_Select($DTs, NULL , 'Number', 1,'',"DistrictTypeAdd-$Tid");
      echo fm_number("Max Districts",$T,'MaxDistricts');
      echo fm_number(($T['ProjHome']?"<a href=ProjHomes.php?id=" . $T['ProjHome'] . ">Project Home</a>":"Project Home"),$T,'ProjHome');

      if (!isset($T['MaxDistricts'])) $T['MaxDistricts'] = 0;
      if ($totdisc > $T['MaxDistricts'] && $T['MaxDistricts']>0) echo "<td class=Err>TOO MANY DISTRICTS\n";
    } else {
      if ($NumDists) echo "<tr><td rowspan=" . ceil(($NumDists+4)/4) . ">Districts:";

      foreach ($Dists as $D) {
        $did = $D['id'];
        if (($dc++)%4 == 0)  echo "<tr>";
        echo "<td>" . $DTs[$D['Type']] . ": " . $D['Number'];
        $totdisc += $D['Number'];
      };

      if (($dc++)%4 == 0)  echo "<tr>";
      echo "<td>Max Districts: " . $T['MaxDistricts'];
      if (($dc++)%4 == 0)  echo "<tr>";
      if ($T['ProjHome']) echo "<td><a href=ProjDisp.php?H=" . $T['ProjHome'] . ">Projects here</a>";
    }
  }

  if ($tprops & THING_HAS_MODULES) {
    $MTNs = Get_Valid_Modules($T);
    $Mods = Get_Modules($Tid);
    if (($Blue = ($T['BluePrint']>0))) {
      $BMods = Get_Modules($T['BluePrint']);
    }
    $FlexUsed = 0;
    $FlexAvailD = ($Blue?Get_ModulesType($Tid,'Flexible'):0);
    $FlexAvail = ($FlexAvailD?$FlexAvailD['Number']:0);

    $Mt = [];
    foreach ($MTNs as $Mi=>$Mn) {
      $Lvl = Calc_TechLevel($Fid,$Mi);
      $Mt[$Mi] = $Lvl;
    }

//    var_dump($Mt);
    $NumMods = count($Mods);
    $dc=0;
    $totmodc = 0;
    $BadMods = 0;
//    $T['Sensors'] = $T['SensorLevel'] = $T['NebSensors'] = 0;

    if ($GM) { // TODO Allow for setting module levels
      if ($NumMods) echo "<tr><td rowspan=" . ceil(($NumMods+2)/2) . ">Modules:";

      foreach ($Mods as $D) {
        $did = $D['id'];
        if (($dc++)%2 == 0)  echo "<tr>";
        echo "<td>" . (isset($MTNs[$D['Type']])? fm_Select($MTNs, $D , 'Type', 1,'',"ModuleType-$did") : "<span class=red>INV:" .
                      fm_Select($MNs, $D , 'Type', 1,'',"ModuleType-$did") . "</span>" )
                      . (($MTs[$D['Type']]['Leveled']&1) ? fm_number1('Level', $D,'Level', '', ' class=Num3 ',"ModuleLevel-$did") .
                        (($D['Level']<$Mt[$D['Type']])? "<span class=green>(" . $Mt[$D['Type']] . ")</span>":'') :'<td>'). ' # '
                    . fm_number0('', $D,'Number', '',' class=Num3 ',"ModuleNumber-$did")
                    . (($Blue && !empty($BMods[$D['Type']]))? "<span class=Blue>(" . $BMods[$D['Type']]['Number'] . ')</span> ' :'')
                    . "<button id=ModuleRemove-$did onclick=AutoInput('ModuleRemove-$did')>R</button>";

        if ( $Blue) {
          $FlexUsed += $D['Number'];
          if (isset($D['Type']) && isset($BMods[$D['Type']])) $FlexUsed -= ($BMods[$D['Type']]['Number']??0);
        }
        if ($D['Number'] < 0) echo " <b>Inactive</b>";
        if (!isset($MTNs[$D['Type']])) $BadMods += $D['Number'];
        if (($MTs[$D['Type']]['Leveled']&8) == 0 ) $totmodc += $D['Number'] * ($Slots?$MTs[$D['Type']]['SpaceUsed']:1);

/*        if ($D['Type'] == 4) {
          $T['Sensors'] = $D['Number'];
          $T['SensorLevel'] = $D['Level'];
        } else if ($D['Type'] == 9) $T['NebSensors'] = $D['Number'];*/
      }
      echo "<tr><td>Add Module Type<td>" . fm_Select($MTNs, NULL , 'Number', 1,'',"ModuleTypeAdd-$Tid");
      echo fm_number1("Max Modules",$T,'MaxModules','',' class=Num3 ');
/*      if ($tprops & (THING_HAS_CIVSHIPMODS | THING_HAS_MILSHIPMODS)) {
//        echo fm_number1("Deep Space",$T,'HasDeepSpace');
        echo fm_number1("Cargo Space",$T,'CargoSpace');
      }*/
      //var_dump($FlexUsed,$FlexAvail)
      if ($totmodc > $T['MaxModules']) {
        echo "<td class=Err>($totmodc) TOO MANY MODULES\n";
      } elseif ($BadMods) {
        echo "<td class=Err>$BadMods INVALID MODULES\n";
      } else if ($T['BluePrint'] > 0 && ($FlexAvail != $FlexUsed)){
        echo "<td class=Err>$FlexAvail Flex slots avail, $FlexUsed Flex used.\n";
      } else {
        echo "<td>Module space used: $totmodc";
      }
      if ($tprops & THING_HAS_SHIPMODULES ) {
        echo "<td>Speed: " . ceil(sprintf('%0.3g',$T['Speed']));
      } else if ($tprops & THING_HAS_ARMYMODULES ) {
        echo "<td>Mobility: " . ceil(sprintf('%0.3g',$T['Mobility']));
      }

    } else { // Player Mode
      if ($NumMods) echo "<tr><td rowspan=" . ceil(($NumMods+4)/4) . ">Modules:";

      foreach ($Mods as $D) {
//        if ($D['Number'] == 0) continue;
        $did = $D['id'];
        if (($dc++)%4 == 0)  echo "<tr>";
        echo "<td><b>" . abs($D['Number']). "</b> of ";
        if (isset($MTNs[$D['Type']])) {
          echo $MTNs[$D['Type']] . (($MTs[$D['Type']]['Leveled']&1)? (" (Level " . $D['Level'] . ") ") :"") ;
          if ($D['Level']<$Mt[$D['Type']]) echo "<span class=green>(" . $Mt[$D['Type']] . ")</span>";
          switch ($MTs[$D['Type']]['Name']) {
            case 'Cargo Space':
              echo " - Capacity: " . $T['Level'];
              break;
            case 'Sublight Engines':
              echo " - Speed: " . ceil(sprintf('%0.3g',$T['Speed']));
              break;
            default:
          }
          if ($D['Number'] <0) echo " <b>Inactive</b>";
        } else {
          $M = $MTs[$D['Type']];
          if ($M['BasedOn']) {
            if ($l = Has_Tech($T['Whose'],$M['BasedOn'])) {
              echo "<span class=err>Invalid</span> " . $M['Name'] . ' Modules ' . (($T['Leveled']&1)? (" (Level " . $D['Level'] . ") ") :"") ;
            } else {
              echo '<span class=err>Unknown</span> ' . $M['Name'] . ' Modules' . (($T['Leveled']&1)? (" (Level " . $D['Level'] . ") ") :"") ;
            }
          }
        }

        $CLvl = Calc_TechLevel($Fid,$D['Type']);
        if (($T['Leveled']&1) && ($CLvl < $D['Level']) && ($T['BuildState'] != 0 )) {
          echo ". <span class=Blue> Note you have Level: $CLvl </span>";
        }
        if (!isset($MTNs[$D['Type']])) $BadMods += $D['Number'];
        if (($MTs[$D['Type']]['Leveled']&8) == 0 ) $totmodc += $D['Number'] * ($Slots?$MTs[$D['Type']]['SpaceUsed']:1);
        };

      echo "<tr>";
      if ($totmodc > $T['MaxModules']) {
        echo "<td>Max Modules: " . $T['MaxModules'];
//      echo fm_number1("Deep Space",$T,'HasDeepSpace');
        if ($totmodc > $T['MaxModules']) {
          echo "<td class=Err>TOO MANY MODULES\n";
        } elseif ($BadMods) {
          echo "<td class=Err>$BadMods INVALID MODULES\n";
        } else {
          echo "<td>Module space used: $totmodc";
        }
      }
      if ($tprops & THING_HAS_SHIPMODULES ) {
        echo "<td>Speed: " . ceil(sprintf('%0.3g',$T['Speed']));
      } else if ($tprops & THING_HAS_ARMYMODULES ) {
        echo "<td>Mobility: " . ceil(sprintf('%0.3g',$T['Mobility']));
      }
      echo "<td>Stability: " . ceil($T['Stability']);

      if ($T['BuildFlags'] & BUILD_FLAG1) echo "<td style=background:lightpink>Cret-Chath";
    }

 // TODO
 // Max modules, current count, what e
  }

  if ($GM && ($tprops & (THING_HAS_CIVSHIPMODS | THING_HAS_ARMYMODULES))) {
    echo "<tr>" . fm_number1('Sensors',$T,'Sensors','','min=0 max=99') . fm_number1(' Level',$T,'SensorLevel','','min=0 max=20') .
         "<td>Neb Sensors: " . fm_checkbox('', $T,'NebSensors') . fm_number1('Stability',$T,'Stability','','min=0 max=1000');
    if (($T['BuildFlags'] & BUILD_FLAG1 ) || ($Fid && Has_Tech($Fid,'Cret-Chath Engineering'))) {
      echo "<td>" . fm_checkflagbox('Cret-Chath',$T,'BuildFlags',BUILD_FLAG1);
    }
  }
  $SpecOrders = []; $SpecCount = 0;
  $HasDeep = $HasPlanet = $HasMinesweep = $HasSalvage = $HasTerraform = $EngCorpLevel = 0;
  if ($tprops & THING_HAS_MODULES) {
    foreach ($Mods as $M) {
      $MName = $MTs[$M['Type']]['Name'];
      switch ($MName) {

      case 'Deep Space Construction':
        $HasDeep += $M['Number'] * $M['Level'];
        break;

      case 'Minesweepers':
        $HasMinesweep += $M['Number'] * $M['Level'];
        break;

      case 'Salvage Rigs':
      case 'Advanced Salvage Rig':
        $HasSalvage += $M['Number'] * $M['Level'];
        break;

      case 'Orbital Terraforming':
        $HasTerraform  += $M['Number'] * $M['Level'];
        break;

      case 'Engineering Corps' :
        $HasPlanet  += $M['Number'];
        $EngCorpLevel = $M['Level'];


      default:
      }
    }
  }

// var_dump($HasDeep,$HasMinesweep,$HasSalvage);
  $TTNames = Thing_Types_From_Names();
  $Moving = ($T['LinkId'] > 0);

  if (($T['BuildState'] == 2 || $T['BuildState'] == 3) && empty($T['PrisonerOf'])) foreach ($ThingInstrs as $i=>$Ins) {

    if (($InstrNotBy[$i] & $NOTBY) !=0) continue; // Not in this game

//  echo "Checking: $Ins<br>";
    switch ($Ins) {
    case 'None': // None
      break;


    case 'Colonise': // Colonise
//      if ((($Moving || $tprops & THING_HAS_CIVSHIPMODS) == 0 ) ) continue 2;
      if (!$HasPlanet || ($T['LinkId'] <0)) continue 2;
      $FS = Get_FactionSystemFS($Fid,$T['SystemId']);
      if (($FS['PlanetScan']??0)<1 ) continue 2;
      $PlTs = Get_PlanetTypes();
      $Ps = Get_Planets($N['id']);
      $Hab_dome = Has_Tech($Fid,'Habitation Domes');
      $Hab_Climate = Has_Tech($Fid,'Climate Control');
      $HabPs = [];
      foreach($Ps as $P) {
        if (!$PlTs[$P['Type']]['Hospitable']) continue;
        if (Get_DistrictsP($P['id'])) continue; // Someone already there
        if (($P['Type'] == $Faction['Biosphere']) || ($P['Type'] == $Faction['Biosphere2']) || ($P['Type'] == $Faction['Biosphere3'])) {
          $HabPs[$P['id']] = [$P['Name'],$P['Type'],0];
        } else if ($P['Type'] == 4 ) {
          $HabPs[$P['id']] = [$P['Name'],$P['Type'],-2];
        } else {
          $HabPs[$P['id']] = [$P['Name'],$P['Type'],-1];
        }
      }
      if (empty($HabPs)) continue 2;
      break;

    case 'Voluntary Warp Home': // Warp Home
      if (!Feature('WarpOut') || (( $tprops & THING_HAS_SHIPMODULES) == 0 ) ||
         ($T['CurHealth'] == 0) || (($tprops & THING_CAN_MOVE) == 0) || ($T['BuildState'] != 3) ) continue 2;
      $Gates = Gates_Avail($T['Whose']);

      if (empty($Gates)) {
        continue 2;
      }
      break;

    case 'Decommision': // Dissasemble

      if ($Moving || ($tprops & THING_HAS_SHIPMODULES) == 0 ) continue 2;
      // If home - yours or friendly - does not need a shipyard - just affects payout
      // Is there a Home here with a shipyard
      $Loc = $T['SystemId'];
      $Homes = Gen_Get_Cond('ProjectHomes', "SystemId=$Loc");

      if (!$Homes) continue 2;
      foreach ($Homes as $H) {
        if ($H['Whose'] == $Fid) break 2; // Mine
        $OFid = $H['Whose'];
        $FF = Gen_Get_Cond1('FactionFaction', "FactionId1=$Fid AND Factionid2=$OFid");
        if ($FF && ($FF['Relationship'] > 5)) break 2;
      }

      /* Old Code
      foreach ($Homes as $H) {
        $LDists = Get_DistrictsH($H['id']);
        if (isset($LDists[3])) break 2; // FOund a Shipyard
      }
      if (isset($N['id']) && Get_Things_Cond($Fid,"Type=" . $TTNames['Orbital Repair Yards'] . " AND SystemId=" . $N['id'] .
        " AND BuildState=3")) break;
        // Orbital Shipyard*/

      continue 2;

    case 'Disband': // Dissasemble
      if ($Moving || ($tprops & THING_HAS_ARMYMODULES) == 0 ) continue 2;
      // Is there a Home here with a Military
      $Loc = $T['SystemId'];
      $Homes = Gen_Get_Cond('ProjectHomes', "SystemId=$Loc");

      if (!$Homes) continue 2;
      foreach ($Homes as $H) {
        if ($H['Whose'] == $Fid) break 2; // Mine
        $OFid = $H['Whose'];
        $FF = Gen_Get_Cond1('FactionFaction', "FactionId1=$Fid AND Factionid2=$OFid");
        if ($FF && ($FF['Relationship'] > 5)) break 2;
      }
      /* OLD CODE
      $Homes = Gen_Get_Cond('ProjectHomes', "SystemId=$Loc AND Whose=$Fid");
      foreach ($Homes as $H) {
        $LDists = Get_DistrictsH($H['id']);
        if (isset($LDists[2])) break 2; // FOund a Military District
      }*/
      continue 2;

    case 'Analyse Anomaly': // Analyse
      if (($T['Sensors'] == 0) || empty($N) ) continue 2;
      $Anoms = Gen_Get_Cond('Anomalies',"SystemId=" . $T['SystemId']);
      if ($Anoms) {
        foreach ($Anoms as $Aid=>$A) {
          $FA = Gen_Get_Cond1('FactionAnomaly',"AnomalyId=$Aid AND FactionId=$Fid");
          if (empty($FA['id']) ) continue;
          if ($FA['State'] == 0 || $FA['State'] == 3) continue;
          if ($FA['Progress'] >= $A['AnomalyLevel']) continue; // Done
          $LocGr = intdiv($A['WithinSysLoc'],100);
          if (($A['WithinSysLoc'] == 3) || ($LocGr == 2) || ($LocGr ==4)) { // Ground
            if ($tprops & THING_HAS_ARMYMODULES) break 2; // Valid
          } else { // Space
            if ($tprops & THING_HAS_SHIPMODULES) break 2; // Valid
          }
        }
      }
      continue 2; // NOt yet

    case 'Establish Embassy': // Establish Embassy
      if (!Get_ModulesType($Tid,'Diplomatic Facilities') || empty($N) ) continue 2;  // Check if have Embassy & at homeworld
      if (!isset($N['id'])) continue 2;
      if (Get_Things_Cond($Fid,"Type=" . $TTNames['Embassy'] . " AND SystemId=" . $N['id'] . " AND BuildState=3")) continue 2; // Already have one
      $Facts = Get_Factions();
      foreach ($Facts as $F) {
        if ($F['id'] == $T['Whose'] || $F['HomeWorld'] == 0) continue 3;
        $W = Get_World($F['HomeWorld']);
        if ($W) {
          $H = Get_ProjectHome($W['Home']);
          if ($H && ($H['SystemId'] == $T['SystemId'])) {
            $T['OtherFaction'] = $F['id'];
            break 2;
          }
        }
      }
      continue 2;

    case 'Make Outpost': // Make Outpost
      if ($Moving || empty($N) || !$HasDeep || ($N['Control'] > 0 && $N['Control'] != $Fid) || empty($N) ) continue 2;

      if (Get_Things_Cond($Fid,"Type=" . $TTNames['Outpost'] . " AND SystemId=" . $N['id'] . " AND BuildState=3")) continue 2; // Already have one
      break;

    case 'Make Asteroid Mine':
      if ($Moving || !$HasDeep || !Has_Tech($Fid,'Asteroid Mining') || empty($N) ) continue 2;
      $Asts = [];
      $Ps = Get_Planets($N['id']);
      foreach ($Ps as $P) if ($P['Type'] == 3) $Asts[]= $P;
      if (empty($Asts)) continue 2;

      $Exist = Get_Things_Cond(0,"Type=" . $TTNames['Asteroid Mine'] . " AND SystemId=" . $N['id'] . " AND BuildState=3");
      if ($Exist || count($Asts) <= count($Exist)) continue 2;
      break 2;

    case 'Make Advanced Asteroid Mine':
      if ($Moving || !$HasDeep || !Has_Tech($Fid,'Advanced Asteroid Mining') || empty($N) ) continue 2;
      $Asts = [];
      $Ps = Get_Planets($N['id']);
      foreach ($Ps as $P) if ($P['Type'] == 3) $Asts[]= $P;
      if (empty($Asts)) continue 2;

      $Exist = Get_Things_Cond(0,"Type=" . $TTNames['Asteroid Mine'] . " AND SystemId=" . $N['id'] . " AND BuildState=3");
      if ($Exist || count($Asts) <= count($Exist)) continue 2;
      break 2;

    case 'Make Minefield':
      if ($Moving || !$HasDeep || !Has_Tech($Fid,'Mine Layers') || empty($N) ) continue 2;
      if (Get_Things_Cond(0,"Type=" . $TTNames['Minefield'] . " AND SystemId=" . $N['id'] .
        " AND BuildState=3 AND WithinSysLoc=" . $T['WithinSysLoc'])) continue 2;
      break;

    case 'Make Orbital Repair Yard':
      if ($Moving || !$HasDeep || !Has_Tech($Fid,'Orbital Repair Yards') || empty($N) ) continue 2;
      if (Get_Things_Cond(0,"Type=" . $TTNames['Orbital Repair Yards'] . " AND SystemId=" . $N['id'] . " AND BuildState=3")) continue 2; // Already have one
      // Is there a world there
      $phs = Gen_Get_Cond('ProjectHomes',"SystemId=" . $N['id']);
      if (isset($phs[0])) break;
      // or a planetary mine
      $PMines = Get_Things_Cond(0,"Type=" . $TTNames['Planet Mine'] . " AND SystemId=" . $N['id'] . " AND BuildState=3");
      if (isset($PMines[0])) break;
      continue 2; // Not valid here

    case 'Build Space Station':
      if ($Moving || !$HasDeep || empty($N) ) continue 2;
      if (!Has_Tech($Fid,'Space Stations')) continue 2;
      if (Get_Things_Cond(0,"Type=" . $TTNames['Space Station'] . " AND SystemId=" . $N['id'] . " AND BuildState=3")) continue 2; // Already have one
      // Is there a world there
      $phs = Gen_Get_Cond('ProjectHomes',"SystemId=" . $N['id']);
      if (isset($phs[0])) break;
      // or a planetary mine
      $PMines = Get_Things_Cond(0,"Type=" . $TTNames['Planet Mine'] . " AND SystemId=" . $N['id'] . " AND BuildState=3");
      if (isset($PMines[0])) break;
      continue 2; // Not valid here

    case 'Expand Space Station':
      if ($Moving || !$HasDeep || !Has_Tech($Fid,'Space Stations') || empty($N) ) continue 2;//
      if (!(Get_Things_Cond($Fid,"Type=" . $TTNames['Space Station'] . " AND SystemId=" . $N['id'] . " AND BuildState=3"))) continue 2; // Don't have one
      break;

    case 'Make Deep Space Sensor':
      if ($Moving || !$HasDeep || !Has_Tech($Fid,'Remote Sensors') || empty($N) ) continue 2;
//      if (Get_Things_Cond(0,"Type=" . $TTNames['Deep Space Sensor'] . " AND SystemId=" . $N['id'] . " AND BuildState=3")) continue 2; // Already have one
      break;

    case 'Build Stargate':
      if ($Moving || ($HasDeep < 10) || !Has_Tech($Fid,'Stargate Construction')) continue 2;
      break;

    case 'Dismantle Stargate':
      if ($Moving || !$HasDeep || !Has_Tech($Fid,'Stargate Construction')) continue 2;
      if (empty($Links)) continue 2;
      break;

    case 'Make Planet Mine':
      if ($Moving || !$HasDeep || !Has_Tech($Fid,'Signal Jammer') || empty($N) ) continue 2;
      $PMines = Get_Things_Cond(0,"Type=" . $TTNames['Planet Mine'] . " AND SystemId=" . $N['id'] . " AND BuildState=3");
      $Ps = Get_Planets($N['id']);
      $idx = $found = 0;
      foreach ($Ps as $P) {
        $idx++;
        if ($P['Minerals'] == 0) continue;
        foreach($PMines as $PM) {
          if ($PM['WithinSysLoc'] == $idx+200) continue 4; // Already have mine on this planet
          $found = 1;
          break;
        }
        $found = 1;
      }
      if (!$found) continue 2; // No valid planets
      break;

    case 'Construct Command Relay Station':
      if ($Moving || !$HasDeep || !Has_Tech($Fid,'Signal Jammer') || empty($N) ) continue 2;
      if (Get_Things_Cond(0,"Type=" . $TTNames['Command Relay Station'] . " AND SystemId=" . $N['id'] . " AND BuildState=3")) continue 2; // Already have one
      break;

    case 'Repair Command Node': // Not coded yet
//      continue 2;
      if ($Moving || !$HasDeep || !Has_Tech($Fid,'Signal Jammer') || empty($N) ) continue 2;

      $Betas = Get_Things_Cond($Fid,"Type=" . $TTNames['Beta Node'] . " AND SystemId=" . $N['id'] . " AND BuildState=3");

      if (!$Betas) continue 2;

      $PMines = Get_Things_Cond($Fid,"Type=" . $TTNames['Planet Mine'] . " AND BuildState=3");
      $CRelay = Get_Things_Cond($Fid,"Type=" . $TTNames['Command Relay Station'] . " AND BuildState=3");

//      if ($GM) echo "Betas: " . count($Betas) . " PMines: " . count($PMines) . " Relays: " . count($CRelay) . "<br>";

      if (count($Betas) >= (count($PMines)+1)) continue 2;
      if (count($Betas) >= (count($CRelay)+1)) continue 2;

      break;

    case 'Build Planetary Mine': // Zabanian special
      if ((($Moving || $tprops & THING_HAS_CIVSHIPMODS) == 0 ) ) continue 2;
      if (!Get_ModulesType($Tid,'Advanced Salvage Rig ')) continue 2; // WRONG
      break;

    case 'Transfer':
      break;

    case 'Make Something': // Generic GM special for weird DSC projects
      if ($Moving || !$HasDeep ) continue 2;
      break;

    case 'Make Warpgate': // Warp gate through DSC
      if ($Moving || !$HasDeep || !Has_Tech($Fid,'Construct Warp Gate using DSC')) continue 2;
      break;

    case 'Retire' :
      if ($tprops & THING_HAS_GADGETS) break;
      continue 2;

    case 'End Support' :
      if ($tprops & THING_NEEDS_SUPPORT) continue 2;
      break;

    case 'Build Advanced Minefield' :
      if ($Moving || !$HasDeep || !Has_Tech($Fid,'Advanced Minefields') || empty($N) ) continue 2;
      if (Get_Things_Cond(0,"Type=" . $TTNames['Minefield'] . " AND SystemId=" . $N['id'] .
        " AND BuildState=3 AND WithinSysLoc=" . $T['WithinSysLoc'])) continue 2;
      break;

    case 'Clear Minefield':
      if ($Moving || !$HasDeep || !$HasMinesweep || empty($N) ) continue 2;
      if (! Get_Things_Cond(0,"Type=" . $TTNames['Minefield'] . " AND SystemId=" . $N['id'] .
        " AND BuildState=3 AND WithinSysLoc=" . $T['WithinSysLoc'])) continue 2;
      break;

    case 'Make Advanced Deep Space Sensor':
      if ($Moving || !$HasDeep || !Has_Tech($Fid,'Advanced Remote Sensors') || empty($N) ) continue 2;
      break;

    case 'Salvage':
      if ($Moving || !$HasSalvage) continue 2;
      break;

    case 'Terraform':
      if ($Moving || !$HasTerraform) continue 2;
      break;

    case 'Stop Support':
      if ($tprops & THING_NEEDS_SUPPORT) break;
      continue 2;

    case 'Link Repair':
      if ($Moving || !$HasDeep || !Has_Tech($Fid,'Stargate Construction')) continue 2;
      break;

    case 'Collaborative Space Construction':
    case 'Collaborative DSC':
      if ($Moving || !$HasDeep || empty($N) ) continue 2;
      // if has colab || other faction at same loc has colab
      $HasColab = [];
      $Facts = Get_Factions();
      foreach($Facts as $OF)  $HasColab[$OF['id']] = Has_Tech($OF['id'],'Collaborative Construction');
      $IHaveCollab = !(empty($HasColab[$Fid]));
      $OtherThings = Get_AllThingsAt($T['SystemId']);
      $OtherList = [];
      foreach($OtherThings as $OT) {
        if (($OT['Whose'] == $Fid) || $IHaveCollab || $HasColab[$OT['Whose']]) {
          if ($ThingProps[$OT['Type']] & THING_HAS_MODULES) {
            $OMods = Get_ModulesType($OT['id'],'Deep Space Construction');
            if (empty($OMods) || $OT['id'] == $T['id']) continue;
            $OtherList[$OT['id']] = $OT['Name'];
          }
        }
      }
      //var_dump($OtherList);
      if (empty($OtherList)) continue 2; // Nothing to collaborate with
      break;

    case 'Collaborative Planetary Construction':
      if ($Moving || !$HasPlanet|| empty($N) ) continue 2;
      // if has colab || other faction at same loc has colab
      $HasColab = [];
      $Facts = Get_Factions();
      foreach($Facts as $OF)  $HasColab[$OF['id']] = Has_Tech($OF['id'],'Collaborative Construction');
      $IHaveCollab = !(empty($HasColab[$Fid]));
      $OtherThings = Get_AllThingsAt($T['SystemId']);
      $OtherList = [];
      foreach($OtherThings as $OT) {
        if ($OT['Whose'] != 0) {
          if (($OT['Whose'] == $Fid) || $IHaveCollab || $HasColab[$OT['Whose']]) {
            if ( ($ThingProps[$OT['Type']] & THING_HAS_MODULES)) {
              $OMods = Get_ModulesType($OT['id'],'Engineering Corps');
              if (empty($OMods) || $OT['id'] == $T['id']) continue;
              $OtherList[$OT['id']] = $OT['Name'];
            }
          }
        }
      }
      //var_dump($OtherList);
      if (empty($OtherList)) continue 2; // Nothing to collaborate with
      break;

    case 'Space Survey':
      if ($Moving || (($tprops & THING_HAS_SHIPMODULES) ==0) || ($T['Sensors']==0) ) continue 2;
      break;

    case 'Planetary Survey':
      if ($Moving || (($tprops & THING_HAS_ARMYMODULES) ==0) || ($T['Sensors']==0) ) continue 2;
      break;

    default:
      continue 2;

    }
    $SpecOrders[$i] = $Ins;
    $SpecCount++;
  }

  if ($SpecCount>1) {
    $PTs=Get_ProjectTypes();
    $PTNs = [];
    foreach($PTs as $PT) $PTNs[$PT['Name']] = $PT;
// var_dump($PTNs);

    $ProgShow = $Cost = $Acts = 0;
    echo "<tr>" . fm_radio('Special Instructions', $SpecOrders,$T,'Instruction','',1,' colspan=6 ','',$ThingInclrs);

    if ($Moving && $HasDeep) echo " Note <span class=Red>Moving</span>, so no DSC instructions are shown";
    switch ($ThingInstrs[abs($T['Instruction'])]) {
    case 'None': // None
      $T['ProjectId'] = $T['Dist1'] = $T['Dist2'] = 0;
      break;

    case 'Colonise': // Colonise
      if ($T['LinkId']<0) break;
      $PlTs = Get_PlanetTypes();
      $Ps = Get_Planets($N['id']);

      $HabPs = [];
      foreach($Ps as $P) {
        if (!$PlTs[$P['Type']]['Hospitable']) continue;
        if (Get_DistrictsP($P['id'])) continue; // Someone already there
        if (($P['Type'] == $Faction['Biosphere']) || ($P['Type'] == $Faction['Biosphere2']) || ($P['Type'] == $Faction['Biosphere3'])) {
          $HabPs[$P['id']] = [$P['Name'],$P['Type'],0];
        } else if ($P['Type'] == 4 ) {
          $HabPs[$P['id']] = [$P['Name'],$P['Type'],(Has_Tech($Fid,'Habitation Domes')?1.5:3)];
        } else {
          $HabPs[$P['id']] = [$P['Name'],$P['Type'],(Has_Tech($Fid,'Climate Control')?0.5:1)];
        }
      }

      if (empty($HabPs)) {
        echo "<tr><td><td colspan=6 class=Err>There are no colonisable planets here\n";
        break;
      }
      $NumPs = count($HabPs);
      if ($NumPs == 1) {
        foreach ($HabPs as $Plid=>$data) {
          $P = $Ps[$Plid];
          $ConLevel = Feature('BaseColonise',10) + $P['ColonyTweak'];
          $ConLevel = ceil($ConLevel*(1+$data[2]));
          $Prog = $HasPlanet * $EngCorpLevel;

          echo "<tr><td><td colspan=6>Colonising: " . $P['Name'] . " a " . $PlTs[$P['Type']]['Name'] . ($PlTs[$P['Type']]['Append']?' Planet':'') .
             " will take $ConLevel actions. This " . $ttn[$T['Type']] . " will do $Prog actions per turn."; // TODO Moons
          $T['Spare1'] = $Plid;

          break;
        }
      } else {
        echo "<tr><td><td colspan=6>";
        $Cols = $Plans = [];
        $i = 1;
        foreach ($HabPs as $Plid=>$data) {
          $P = $Ps[$Plid];
          $ConLevel = Feature('BaseColonise',10) + $P['ColonyTweak'];
          $ConLevel = ceil($ConLevel*(1+$data[2]));
          $Prog = $HasPlanet * $EngCorpLevel;
          $Plans[$Plid] = $P['Name'] . " a " . $PlTs[$P['Type']]['Name'] . ($PlTs[$P['Type']]['Append']?'Planet':'') .
             " will take $ConLevel actions. This " . $ttn[$T['Type']] . " will do $Prog actions per turn."; // TODO Moons
          $Cols[$Plid] = $ThingInclrs[$i++];

        }
//    echo "<tr>" . fm_radio('Special Instructions', $SpecOrders,$T,'Instruction','',1,' colspan=6 ','',$ThingInclrs) . " under development don't use yet";
        echo fm_radio('Colonising',$Plans,$T,'Spare1','',0,'','',$Cols);
      }
      /* Old Code
      if ($T['Spare1']) $Acts = $HabPs[$T['Spare1']][2];
      $PrimeMods = [];
      $DTs = Get_DistrictTypes();
      foreach ($DTs as $D) if ($D['Props'] &1) $PrimeMods[$D['id']] = $D['Name'];
      echo "<br>District to Establish:" . fm_select($PrimeMods,$T,'Dist1');
      if (Get_ModulesType($Tid,'Self Repairing Robot Armour')) echo "<br>Second District (must be different):" .fm_select($PrimeMods,$T,'Dist2'); // TODO WRONG
      */

      $Acts = $ConLevel;
      $T['Dist2'] = $Prog;  // Spare1 = Planet, Dist2 = Prog/turn
      $ProgShow = 1;
      break;

    case 'Voluntary Warp Home': // Warp Home
    // Count Warp gates, if 0 impossible, if 1 select gate, if many show list
      $Gates = Gates_Avail($T['Whose']);
//var_dump($Gates); echo "<p>";
      if (empty($Gates)) {
        // Should be removed above
        echo "<br class=Err>No gates to warp home";
      } else if (count($Gates) == 1) {
        $T['Dist1'] = $Gates[0]['id'];
      } else {
        $Gatelist = [];
        foreach ($Gates as $G) {
          if ($G['Whose'] == $T['Whose']) {
            $Gatelist[$G['id']] = "System: " . $Systems[$G['SystemId']];
          } else {
            $Gatelist[$G['id']] = "System: " . $Systems[$G['SystemId']] . " - " . $FactNames[$G['Whose']];
          }
        }
//var_dump($Gatelist);
        echo "<br>System to Warp to: " . fm_select($Gatelist,$T,'Dist1');
      }
      break;

    case 'Retire':
    case 'Decommision': // Dissasemble
    case 'Disband': // Disband
      $Acts = $Cost = 0;
      break;

    case 'Establish Embassy': // Establish Embassy
      echo "<br>" . fm_text0("Name of Embassy",$T,'MakeName') . " with " . $FactNames[$T['OtherFaction']];
      break;

    case 'Make Outpost': // Make Outpost
      echo "<br>" . fm_text0("Name of Outpost",$T,'MakeName');
      $Acts = $PTNs['Build Outpost']['CompTarget'];
      $ProgShow = 2;
      break;

    case 'Analyse Anomaly': // Analyse
      if ($T['ProjectId'] != 0) {
        $Anom = Get_Anomaly($T['ProjectId']);
        if (!$Anom || ($Anom['SystemId'] != $T['SystemId'])) {
          $T['ProjectId'] = 0;
        }
      }

      if ($T['ProjectId'] == 0) { // Pid == AnomId
        $Anoms = Gen_Get_Cond('Anomalies',"SystemId=" . $T['SystemId']);
        $Alist = [];
        $Al = 0;
        foreach($Anoms as $A) {
          $Aid = $A['id'];

          $FA = Gen_Get_Cond1('FactionAnomaly',"AnomalyId=$Aid AND FactionId=$Fid");
          if (empty($FA['id'])) continue;
          if ($FA['Progress'] < $A['AnomalyLevel']) {
            $LocGr = intdiv($A['WithinSysLoc'],100);
 //           echo "<tr><td>"; var_dump($tprops,$LocGr);
            if (($A['WithinSysLoc'] == 3) || ($LocGr == 2) || ($LocGr ==4)) { // Ground
              if (($tprops & THING_HAS_ARMYMODULES) == 0) continue;
 //             echo "Ground";
            } else { // Space
              if (($tprops & THING_HAS_SHIPMODULES) == 0) continue;
  //            echo "Space";

            }
            $Alist[$Aid] = $A['Name'] . " - takes " . $A['AnomalyLevel'] . " Sensor Points to Analyse.\n";
            $LastA = $A['Name'];
            $Al = $Aid;
          }
        }
        if (count($Alist) == 1) {
          $T['ProjectId'] = $Al;
          echo "<br>Analysing: " . $LastA;
        } else if (count($Alist) == 0) {
          echo "No known Anomaly Here.";
          $T['Instruction'] = 0;
          break;
        } else {
          echo "Select Anomaly: " . fm_select($Alist,$T,'ProjectId');
        }

      } else {
        $Aid = $Anom['id'];
        $FA = Gen_Get_Cond1('FactionAnomaly',"FactionId=$Fid AND AnomalyId=$Aid");
        echo "<br>Analysing: " . $Anom['Name'];
        $ProgShow = 2;
        $Acts = $Anom['AnomalyLevel'];
        $Cost = -1;
        $T['Progress'] = $FA['Progress'];
      }
      break;

    case 'Make Asteroid Mine':
      echo "<br>" . fm_text0("Name of Asteroid Mine",$T,'MakeName');
      $Acts = $PTNs['Build Asteroid Mining Facility']['CompTarget'];
      $ProgShow = 2;
      break;

    case 'Make Advanced Asteroid Mine':
      echo "<br>" . fm_text0("Name of Advanced Asteroid Mine",$T,'MakeName');
      $Acts = $PTNs['Build Advanced Asteroid Mining Facility']['CompTarget'];
      $ProgShow = 2;
      break;

    case 'Make Orbital Repair Yard':
      echo "<br>" . fm_text0("Name of Orbital Repair Yard",$T,'MakeName');
      $Acts = $PTNs['Build Orbital Shipyard']['CompTarget'];
      $ProgShow = 2;
      break;

    case 'Make Minefield':
      echo "<tr><td><td colspan=6>Make sure you move to the location within the system you want to mine.  ";
      $WSL = $T['WithinSysLoc'];
      if ($T['NewSystemId'] == 0 && $T['NewLocation'] != 0) $WSL = $T['NewLocation'];
      $LocT = intdiv($WSL,100);
      if ($ValidMines[$LocT] == 0 ) echo "<span class=Err> the current location is unsuitable.</span>";
      if (Get_Things_Cond(0,"Type=" . $TTNames['Minefield'] . " AND SystemId=" . $N['id'] . " AND BuildState=3 AND WithinSysLoc=" . $T['WithinSysLoc'])) {
        echo "<span class=Err>  There is already one here.</span>";
      }
      echo "<br>" . fm_text0("Name of Minefield",$T,'MakeName');
      $Acts = $PTNs['Build Minefield']['CompTarget'];
      $ProgShow = 2;
      break;

    case 'Make Advanced Minefield':
      echo "<tr><td><td colspan=6>Make sure you move to the location within the system you want to mine.  ";
      $WSL = $T['WithinSysLoc'];
      if ($T['NewSystemId'] == 0 && $T['NewLocation'] != 0) $WSL = $T['NewLocation'];
      $LocT = intdiv($WSL,100);

      if ($ValidMines[$LocT] == 0 ) echo "<span class=Err> the current location is unsuitable.</span>";
      if (Get_Things_Cond(0,"Type=" . $TTNames['Minefield'] . " AND SystemId=" . $N['id'] . " AND BuildState=3 AND WithinSysLoc=" . $T['WithinSysLoc'])) {
        echo "<span class=Err>  There is already one here.</span>";
      }
      echo "<br>" . fm_text0("Name of Minefield",$T,'MakeName');
      $Acts = $PTNs['Build Advanced Minefield']['CompTarget'];
      $ProgShow = 2;
      break;

    case 'Build Space Station':
      $PrimeMods = [];
      $DTs = Get_DistrictTypes();
      $MaxDeep = Has_Tech($Fid,'Deep Space Construction')*2;
      foreach ($DTs as $D) if (($D['Props'] & 16) && ((eval("return " . $D['Gate'] . ";" )))) $PrimeMods[$D['id']] = $D['Name'];
      echo "<tr><td><td colspan=6>" . (($GM || $T['Progress'] == 0)? fm_number0('How many districts:',$T,'Dist1',''," max=$MaxDeep "): ("Districts: " . $T['Dist1']));
      echo " First District:" . fm_select($PrimeMods,$T,'Dist2',1);
      echo "<br>" . fm_text0("Name of Space Station",$T,'MakeName');
      $Acts = - $PTNs['Build Space Station']['CompTarget']*$T['Dist1'];
      $ProgShow = 1;
      break;

    case 'Expand Space Station':
      $MaxDeep = Has_Tech($Fid,'Deep Space Construction')*2;

      $SS = (Get_Things_Cond($Fid,"Type=" . $TTNames['Space Station'] . " AND SystemId=" . $N['id'] . " AND BuildState=3"))[0];

      if (empty($SS)) {
        echo "Tell Richard something hs gone wrong with this<p>";
        break;
      }

      $MaxDeep = $MaxDeep - $T['MaxDistricts'];
      echo "<tr><td colspan=6>" .
           (($GM || $T['Progress'] == 0)? fm_number0('By how many districts:',$T,'Dist1',''," max=$MaxDeep "): ("<td>Adding: " . $T['Dist1'] . " disticts"));
      $ProgShow = 1;
      if ($T['Dist1']) {
        $Acts = - $PTNs['Extend Space Station']['CompTarget']*$T['Dist1'];
      }
      break;

    case 'Make Deep Space Sensor':
      echo "<br>" . fm_text0("Name of Remote Sensor",$T,'MakeName');
      $Acts = $PTNs['Deep Space Sensors']['CompTarget'];
      break;

    case 'Build Stargate':
      echo "<br>Select link level and destination, then refresh.  To link off the map, GMs need to create a new node first.<br>";
      $LinkLevels = Get_LinkLevels();
      $LLMenu = [];
      $LLCols = [];
      foreach ($LinkLevels as $LL) {
        $LLMenu[$LL['Level']] = "Level: " . $LL['Level'] . " - " . $LL['Name'];
        $LLCols[$LL['Level']] =  $LL['Colour'];
      }
      $LLMenu[0] = '';
      $LLCols[0] = 'Black';
      if (empty($LLCols[$T['Dist1']])) $T['Dist1'] = 0;
//var_dump($T['Dist1'],$LLMenu,$LLCols);
      echo fm_select($LLMenu,$T,'Dist1',0," style=color:" . $LLCols[$T['Dist1']],'',0,$LLCols ) . " to: " . fm_select($Systems,$T,'Dist2');
      $factor = 1;
      if (empty($T['Dist2'])) $T['Dist2'] = 1;
//      if (Has_Trait($Who,"Grow Modules") && ($T['Dist2'] != 1)) {
//        echo "<br>" . fm_number('Strength',$T,'Dist2');
// WORK IN PROGRESS

      $ProgShow = 1;
      if ($T['Dist1']) {
        $LL = $LinkLevels[$T['Dist1']];
        $LinkRes = GameFeature('LinkResource',0);
        if ($LinkRes) {
          AddCurrencies();
          $Cur = 0;
          foreach ($Currencies as $Ci => $C) if ( $C == $LinkRes) $Cur = $Ci;
          if ( $T['Progress'] == 0) {
            if ($Faction["Currency" . (6 - $Cur)] >= $LL['MakeCost']) {
              echo "Will use " . $LL['MakeCost'] . " $LinkRes<br>";
            } else {
              echo "<span class=Err>Warning needs more $LinkRes than you have </span>";
            }
          }
          $Acts = $LL['MakeCost']*2;
        }
      } else {
        $Acts = 0;
      }

      // Needs a lot of work
      break;

    case 'Dismantle Stargate':
      echo "<br>Link to dismantle: " . fm_select($SelLinks,$T,'Dist1',0," style=color:" . $SelCols[$T['Dist1']] ,'',0,$SelCols) . " Refresh after selecting.";
      $ProgShow = 1;
      $LLevel = $Links[$T['Dist1']]['Level'];
      $Acts = $PTNs['Dismantle Stargate']['CompTarget']*$LLevel;

      break;

    case 'Make Planet Mine':
      // TODO should have code to select Planet
      echo "<br>";
      $ProgShow = 1;
      $Acts = $PTNs['Build Planet Mine']['CompTarget'];
      break;

    case 'Construct Command Relay Station':
      $ProgShow = 1;
      $Acts = $PTNs['Construct Command Relay Station']['CompTarget'];
      break;

    case 'Repair Command Node': // Not coded yet
      $PrimeMods = [];
      $DTs = Get_DistrictTypes();
      foreach ($DTs as $D) if ($D['Props'] &1) $PrimeMods[$D['id']] = $D['Name'];
      echo "<br>District to Establish:" . fm_select($PrimeMods,$T,'Dist1');
      $ProgShow = 2;
      $Acts = $PTNs['Repair Command Node']['CompTarget'];
      break;

    case 'Build Planetary Mine': // Zabanian special
      $PlTs = Get_PlanetTypes();
      $Ps = Get_Planets($N['id']);
      $Hab_dome = Has_Tech($Fid,'Habitation Domes');
      $HabPs = [];
      foreach($Ps as $P) {
        if (!$PlTs[$P['Type']]['Hospitable']) continue;
        if (Get_DistrictsP($P['id'])) continue; // Someone already there
        if (($P['Type'] == $FACTION['Biosphere']) || ($P['Type'] == $FACTION['Biosphere2']) || ($P['Type'] == $FACTION['Biosphere3'])) {
          $HabPs[$P['id']] = [$P['Name'],$P['Type'],3];
        }
        if ($P['Type'] == 4 ) {
          if (!$Hab_dome) continue;
          $HabPs[$P['id']] = [$P['Name'],$P['Type'],10];
        } else {
          $HabPs[$P['id']] = [$P['Name'],$P['Type'],6];
        }
      }

      if (empty($HabPs)) {
        echo "<tr><td><td colspan=6 class=Err>There are no mineable planets here\n";
        break;
      }
      $NumPs = count($HabPs);
      if ($NumPs == 1) {
        foreach ($HabPs as $Plid=>$data) {
          $P = $Ps[$Plid];
          $Acts = $data[2];
          echo "<tr><td><td colspan=6>Mining: " . $P['Name'] . " a " . $PlTs[$P['Type']]['Name'] . ($PlTs[$P['Type']]['Append']?' Planet':'') .
             " will take " . $data[2] . " actions"; // TODO Moons
          $T['Spare1'] = $Plid;
          break;
        }
      } else {
        echo "<tr><td><td colspan=6>";
        $Cols = $Plans = [];
        $i = 1;
        foreach ($HabPs as $Plid=>$data) {
          $P = $Ps[$Plid];
          $Plans[$Plid] = $P['Name'] . " a " . $PlTs[$P['Type']]['Name'] . ($PlTs[$P['Type']]['Append']?'Planet':'') . " will take " . $data[2] . " actions"; // TODO Moons
          $Cols[$Plid] = $ThingInclrs[$i++];
        }
        echo fm_radio('Mining:',$Plans,$T,'Spare1','',0,'','',$Cols);
      }
      if ($T['Spare1']) $Acts = $HabPs[$T['Spare1']][2];
      $ProgShow = 1;
      $Cost = -1;
      break;

    case 'Transfer':
      $Facts = Get_FactionFactions($Fid);

      $FactList = [];
      $FactList[-1] = "Other";
      if (!empty($Facts)) foreach ($Facts as $Fi=>$F) {
        $FactList[$Fi] = $FactNames[$Fi];
      }
      echo "<br>Transfer control to: " . fm_select($FactList,$T,'Dist1');
      $ProgShow = 0;
      $Cost = -1;
      if ($T['Dist1']) {
        echo fm_submit("ACTION",'Transfer Now',0);
      }
      break;

    case 'Make Something':  // Generic making AKA Analyse for academic
      echo "<br>These projects will be defined by a GM<br>";
      echo fm_text0("Name project - meaningfull to you and the GM",$T,'MakeName',2);
      echo fm_number0('Actions Needed',$T,'ActionsNeeded');
      $Acts = $T['ActionsNeeded'];
      if (empty($T['MakeName']) || $T['ActionsNeeded'] == 0) {
        echo "<span class=Err>These MUST be filled in</span>";
      }
      $ProgShow = 3;
      break;

    case 'Make Warpgate':
      echo "<br>" . fm_text0("Name of Warpgate",$T,'MakeName');
      $Acts = $PTNs['Build Warpgate using DSC']['CompTarget'];
      $ProgShow = 2;
      break;

    case 'Stop Support':
      echo "Support for this will end at the beginning of the next turn.";
      break;

    case 'Clear Minefield':
      $Acts = $PTNs['Clear Minefield']['CompTarget'];
      $ProgShow = 2;
      break;

    case 'Make Advanced Remote Sensors':
      echo "<br>" . fm_text0("Name of Advanced Remote Sensor",$T,'MakeName');
      $Acts = $PTNs['Advanced Deep Space Sensors']['CompTarget'];
      break;

    case 'Salvage':
      $Acts = 1;
      $Cost = -1;
      break;

    case 'Terraform':
    // World - Planet or Moon, Current size, target type
      break;

    case 'Link Repair':
      if (empty($T['Dist2'])) $T['Dist2'] = 1;
      $factor = 1;
      echo "<br>Link to Repair: " . fm_select($SelLinks,$T,'Dist1',0," style=color:" . $SelCols[$T['Dist1']] ,'',0,$SelCols) . " <b>Refresh</b> after selecting.";
      if (Has_Trait($Fid,"Grow Modules") && $T['Dist1']) {
        echo fm_number0('  Strength',$T,'Dist2') . " <b>Refresh</b> after selecting. ";
        $Link = Get_Link($T['Dist1']);
        $LinkLevels = Get_LinkLevels();
        $LL = $LinkLevels[$Link['Level']];
        $LinkRes = GameFeature('LinkResource',0);
        if ($LinkRes && $Link['Level'] != $T['Dist2']) {
          AddCurrencies();
          $Cur = 0;
          foreach ($Currencies as $Ci => $C) if ( $C == $LinkRes) $Cur = $Ci;
          if ( $T['Progress'] == 0) {
            if ($Faction["Currency" . (6 - $Cur)] >= ($xtra = $LL['MakeCost']*($T['Dist2'] - $Link['Weight']))) {
              echo "Will use " . $xtra . " $LinkRes<br>";
            } else {
              echo "<span class=Err>Warning needs more $LinkRes than you have </span>";
            }
          }
        }
      } else {
        $factor = 2;
      }
      $ProgShow = 1;
      $LLevel = ($T['Dist1'] ?$Links[$T['Dist1']]['Level']:1);
      $Acts = $PTNs['Link Repair']['CompTarget']*$LLevel*$T['Dist2']*$factor;
      break;

    case 'Collaborative Space Construction':
    case 'Collaborative DSC':
      $DList = [0=>''] + $OtherList;
      //var_dump($DList);
      echo "<br>Select ship to collaborate with.  If they are not doing anything, nothing happens. " . fm_select($DList,$T,'Dist1');
      if ($T['Dist1']) {
        echo "<button type=submit formaction=ThingEdit.php?ACTION=Takeover&id=$Tid>Takeover Lead Construction</button>";
      }
      break;

    case 'Collaborative Planetary Construction':
      $DList = [0=>''] + $OtherList;
      //var_dump($DList);
      echo "<br>Select detachment to collaborate with.  If they are not doing anything, nothing happens. " . fm_select($DList,$T,'Dist1');
      if ($T['Dist1']) {
        echo "<button type=submit formaction=ThingEdit.php?ACTION=Takeover&id=$Tid>Takeover Lead Construction</button>";
      }
      break;

    case 'Space Survey':
      break;

    case 'Planetary Survey':
      break;


    default:
      break;
    }
    $T['ActionsNeeded'] = $Acts;
    if ($Cost < 0) {
      $T['InstCost'] = $Cost = 0;
    } else {
      $T['InstCost'] = $Cost = $Acts*Feature('ConstructionCost',10);
    }
    if (Has_Trait($Fid,'Built for Construction and Logistics') && ($Cost>200)) {
      $T['InstCost'] = $Cost = (200+($Cost-200)/2);
    }
    if ($ProgShow) {
      if ($ProgShow >= 2) echo "<tr><td><td colspan=6>";
      if ($ProgShow == 3) { // Generic DSC instruction
        if ($GM) {
          echo fm_number0('Progress',$T,'Progress') ;
        } else {
          echo " Progress " . $T['Progress'];
        }
      } else if ($GM) {
        echo fm_number0('Progress',$T,'Progress') . " / " . fm_number0('Actions Needed',$T,'ActionsNeeded');
      } else {
        echo " Progress " . $T['Progress'] . ' / ' . $T['ActionsNeeded'];
      }
      if ($Cost>0 && ($T['Progress'] == 0)) {
        echo " - Will cost " . Credit() . ($GM?fm_number0('',$T,'InstCost'):$T['InstCost']) . " this turn";
      }
      if ($T['Instruction'] != $T['CurInst']) {
        $T['CurInst'] = $T['Instruction'];
        $T['Progress'] = 0;
      }
    }
  } else { // No Valid instructions
    $T['CurInst'] = $T['Instruction'] = $T['Progress'] = 0;
  }

  if (Access('God') && !$IsaTeam) {
    echo "<tr><td>GOD - Instruction:<td>" . fm_select($ThingInstrs,$T,'Instruction') . "<td>Stored: "  . fm_select($ThingInstrs,$T,'CurInst');
    echo fm_number1('Dist1',$T,'Dist1') . fm_number1('Dist2',$T,'Dist2') . fm_number1('Spare1',$T,'Spare1');
    if (($T['ProjectId'] ?? 0) && $T['BuildState'] > 1) echo "<tr>" . fm_number('Prog/Anom Id',$T,'ProjectId') . "<td colspan=2>Reminder progress from Anomaly itself";
    echo "<tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea>";
  }

  if ($IsaTeam) {
    $Ops = Get_OpTypes();
    $Oper = Get_Operation($T['ProjectId']);
    echo "<tr><td>Current Operation:<td colspan=3>" . ($Ops[$Oper['Type']]['Name']??'Unknown');
  }
  echo "</table></div>\n";
  echo fm_submit("ACTION","Refresh",0);
  if ($GM) {
    echo fm_submit("ACTION",'GM Refit',0);
    if ($tprops & THING_LEAVES_DEBRIS) echo fm_submit("ACTION",'Destroy Thing (Leave debris)',0);
    echo fm_submit("ACTION",'Remove Thing (No debris)',0);
    if (Feature('WarpOut') && $tprops & THING_CAN_MOVE) echo fm_submit("ACTION",'Warp Out',0);
    if ($T['CurShield']) {
      echo fm_number0(" Do",$T,'Damage', '',' class=Num3 ') . fm_submit("ACTION","Damage",0);
      echo fm_number0(" Do",$T,'HullDamage', '',' class=Num3 ') . fm_submit("ACTION","Hull Damage",0);
    } else {
      echo fm_number0(" Do",$T,'Damage', '',' class=Num3 ') . fm_submit("ACTION","Damage",0);
    }
    if (($T['PrisonerOf'] ?? 0)) echo fm_submit("ACTION","Disarm",0);
    if (($tprops & THING_CAN_MOVE) && (($Faction['TurnState']??0) == 3)) echo fm_submit("ACTION",'Retreat');
  }
  if (!$GM && ($tprops & THING_CAN_BE_CREATED)) echo fm_submit("ACTION","Delete",0);
  if ($GM) {
    echo "<h2>";
    if (empty($Fid)){
      echo "<a href=ThingList.php>Back to Thing list</a> ";
    } else {
      echo "<a href=PThingList.php?id=$Fid>Back to Thing list</a>";
    }

    echo " &nbsp; " . fm_submit("ACTION","Duplicate",0) . fm_submit("ACTION","GM Recalc",0);
    if ($HasSalvage) echo fm_submit("ACTION","Salvage",0);
    if (($ttn[$T['Type']]??0) == 'Outpost') echo fm_submit("Action",'Add Branch',0,"formaction=BranchEdit.php?T=$Tid");
    echo "</h2>";
  } else if (!empty($Fid)) {
    echo "<h2><a href=PThingList.php?id=$Fid>Back to Thing list</a></h2>";
  }
  Put_Thing($T);
}

?>
