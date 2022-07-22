<?php
// Lib of Thing related stuff
include_once("sk.php");
include_once("GetPut.php");
include_once("vendor/erusev/parsedown/Parsedown.php");
include_once("PlayerLib.php");  




function Show_Thing(&$T,$Force=0) {
  include_once("ProjLib.php");
  global $BuildState,$GAME,$GAMEID,$FACTION;
  global $Project_Status;
  global $ModuleCats,$ModFormulaes,$ModValues,$Fields,$Tech_Cats,$CivMil,$BuildState,$ThingInstrs,$ThingInclrs, $InstrMsg;
  global $Currencies;
  
  $ThingInclrs = ['white','lightgreen','lightpink','lightblue','lightyellow','bisque','#99ffcc','#b3b3ff',
                 'lightgreen','lightpink','lightblue','lightyellow','bisque','#99ffcc','#b3b3ff',
                 'lightgreen','lightpink','lightblue','lightyellow','bisque','#99ffcc','#b3b3ff'];

  $tid = $T['id'];
  
  if ($Force) {
    $GM = 0;
  } else {
    $GM = Access('GM');
  }

  $Fid = $T['Whose'];
  $ttn = Thing_Type_Names();
  $FactNames = Get_Faction_Names();
  $Fact_Colours = Get_Faction_Colours();
  $ThingProps = Thing_Type_Props();
  $tprops = $ThingProps[$T['Type']];
  $N = Get_System($T['SystemId']);
  $Syslocs = Within_Sys_Locs($N);
  $LinkTypes = Get_LinkLevels();
  if ($Fid) $Faction = Get_Faction($Fid);
  
  if ($T['SystemId'] == $T['NewSystemId'] || $T['NewSystemId'] == 0) {
    $NewSyslocs = $Syslocs;
  } elseif ($T['NewSystemId']) {
    $NN = Get_System($T['NewSystemId']);
    $FS = Get_FactionSystemFS($Fid,$T['NewSystemId']);
    if (($FS['ScanLevel'] >= 3) && ($NN['Nebulae'] <= $FS['NebScanned'])) {
      $NewSyslocs = Within_Sys_Locs($NN);
    } else {
      $NewSyslocs = [];    
    }
  } else {
    $NewSyslocs = [];
  }

  $Systems = Get_SystemRefs();
  $T['MaxModules'] = Max_Modules($T);
  if  ($tprops & THING_HAS_MODULES) $T['OrigHealth'] = Calc_Health($T,1);

  if (($T['BuildState'] == 3) && ($tprops & THING_CAN_MOVE) && ($T['CurHealth'] > 0)) { // Complete Only
    $res = Moves_4_Thing($T,$Force, ($tprops & (THING_HAS_GADGETS | THING_CAN_BETRANSPORTED)), $N);
//var_dump($res);exit;
    [$Links, $SelLinks, $SelCols ] = $res;

  } else {
    $Links = $SelLinks = $SelCols = [];
  }

//var_dump($SelLinks);exit;
/*
  if ($Links && $T['LinkId'] >= 0 && !isset($Links[$T['LinkId']]['Level'])) {
    var_dump($T); echo "<p>"; var_dump($Links); echo "<p>";
  }
*/

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
         
      echo fm_hidden('LinkCost', $Lc) . "<input type=submit Name=ACTION value='Pay on Turn'>";
      echo "</h2>\n";
    }
    $T['LinkCost'] = $Lc;
    Put_Thing($T);
  }

//  echo "Note Movement does not yet work for armies moving by ship.<p>\n";
  
  if ($T['BuildState'] == 0) echo "Note the Tech level of this will be recorded when it is built<br>";

  echo "<div class=tablecont><table width=90% border class=SideTable>\n";
  Register_AutoUpdate('Thing',$tid);
  echo fm_hidden('id',$tid);
  echo "<input type=submit name=ACTION value=Refresh hidden>";  
  if ($GM) {
    echo "<tr class=NotSide><td class=NotSide>Id:<td class=NotSide>$tid<td class=NotSide>Game<td class=NotSide>$GAMEID<td class=NotSide>" . $GAME['Name'];
    echo "<tr><td>Type:<td>" . fm_select($ttn,$T,'Type',1); 
    if ($tprops & THING_HAS_LEVELS) echo fm_number("Level",$T,'Level');
//    if (Access('God') echo fm_number1('Turn Moved',$T,
  } else {
    echo "<tr><td>Type:<td>" . $ttn[$T['Type']];
    if ($tprops & THING_HAS_LEVELS) echo "<td>Level: " . $T['Level'];
  }
  echo "<tr>" . fm_text('Name',$T,'Name',2);

  echo "<td rowspan=4 colspan=4><table><tr>";
    echo fm_DragonDrop(1,'Image','Thing',$tid,$T,1,'',1,'','Thing');
  echo "</table>";



  echo "<tr>" . fm_text('Class',$T,'Class',2);

// WHERE IS IT AND MOVEMENT



  echo "<tr><td>Build State:<td>" . ($GM? fm_select($BuildState,$T,'BuildState') : $BuildState[$T['BuildState']]); 
  if (isset($T['BuildState']) && $T['BuildState'] <= 1) {
    if ($GM) echo fm_number('Build Project',$T,'ProjectId');
    if ($T['ProjectId']) {
      $Proj = Get_Project($T['ProjectId']);
      echo "<tr><td>See <a href=ProjEdit.php?id=" . $T['ProjectId'] . ">Project</a>";
      echo "<tr><td>Status: " . $Project_Status[$Proj['Status']];
      if ($Proj['TurnStart']) echo " Start Turn: " . $Proj['TurnStart'];
      if ($Proj['TurnEnd']) echo " End Turn: " . $Proj['TurnEnd'];
    }
  } else if ($T['BuildState'] == 2 || $T['BuildState'] == 3) {
   
// Note Special meaning of -ve LinkId numbers:
// -1 On Board - SytemId = ThingId of Host
// -2 Boarding on Turn NewSystemId = ThingId of Host
// -3 Unboarding on turn NewWithinSys = Target within
// -4 Board and unboard see -2,-3 for extra fields
// -5 Not Used
// -6 Direct Move to NewSystemId, NewWithinSys without links   
   
    $Lid = $T['LinkId'];
    if ($tprops & THING_MOVES_DIRECTLY) {
        if ($GM) {
          echo "<tr><td>System:<td>" . fm_select($Systems,$T,'SystemId',1);
          echo "<td>" . fm_select($Syslocs,$T,'WithinSysLoc');
        } else {
          echo "<tr><td>Current System:<td>" . $N['Ref'] . "<td>" . $Syslocs[$T['WithinSysLoc']];    
        }
      $T['LinkId'] = -6;
      echo "<tr><td>New System:<td>" . fm_select($Systems,$T,'NewSystemId',1);      
    // TODO NewLocation
    } else {
// if ($GM) echo "Lid:$Lid SystemId:" . $T['SystemId']; // TEST CODE DELIBARATELY STILL BEING USED - GM ONLY
      if ($Lid<0 && Access('God') ) {
        echo fm_number0("Lid",$T,'LinkId') . fm_number1("SysId",$T,'SystemId');
      }
      if ($Lid >= 0 || $Lid == -2 || $Lid == -4) { // Insystem
        if ($GM) {
          echo "<tr><td>System:<td>" . fm_select($Systems,$T,'SystemId',1);
          echo "<td>" . fm_select($Syslocs,$T,'WithinSysLoc');
        } else {
          echo "<tr><td>Current System:<td>" . $N['Ref'] . "<td>" . $Syslocs[$T['WithinSysLoc']];    
        }
      } else { // On Board
        $Host = Get_Thing($T['SystemId']);
        echo "<tr><td colspan=3>In: " . $Host['Name'];
        $Conflict = 0; 
        $Conf = Gen_Select("SELECT W.* FROM ProjectHomes PH, Worlds W WHERE PH.SystemId=" . $T['SystemId'] . " AND W.Home=PH.id AND W.Conflict=1");
        if ($Conf) $Conflict = $Conf[0]['Conflict'];

        if ($Host['LinkId']>0 && $Host['TargetKnown'] == 0) {
          echo "You don't know where you are going to unlooad<br>";
          if ($GM) {
            if ($Lid == -1 || $Lid == -2) echo "<input type=submit name=ACTION value='Unload After Move";
            echo "<input type=submit name=ACTION value='Unload Now'>\n";
          }
        } else {
          if ($Lid == -1 || $Lid == -2) if ($Fid == $Host['Whose'] || $Host['Whose'] == $FACTION['id'] ) echo "<input type=submit name=ACTION value='Unload After Move'>\n";
          if ($GM || (!$Conflict && ($Fid == $Host['Whose'] || $Host['Whose'] == $FACTION['id'] ))) { 
            echo "<input type=submit name=ACTION value='Unload Now'>\n";         
          } else {
            echo "Only the transport owner can unload you";
          }
//          echo "<input type=submit name=ACTION value='Unload on Turn'>\n";
//          if ($Conflict) echo " <b>Conflict</b> ";
//          echo "<input type=submit name=ACTION value='Unload Now'>\n";
          echo "<br>Note: To unload AFTER moving, please put the movement order in for the transport before the Unload After Move order.<br>\n";
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
          $HostId = $H['SystemId'];
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
          if (isset($FS['ScanLevel']) && $FS['ScanLevel'] >=3) {
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
      if ((($tprops & THING_CAN_MOVE)) && $Lid >= 0) {

        if (($T['BuildState'] == 2) || ($T['CurHealth'] == 0) || empty($SelLinks) ) { // Shakedown or just warped out
          echo "<tr><td colspan=3>This is unable to use links, it can move within the system.<br>Where in the system should it go? " . fm_select($Syslocs,$T,'WithinSysLoc');
        } else {
          if (($T['Instruction'] > 0) && ($T['Instruction'] != 5) && ($T['Instruction'] != 21) ) {
            echo "<tr><td class=Err>Warning Busy doing:<td>" . $ThingInstrs[$T['Instruction']] . "<td class=Err>Moving will cancel";
          }

          if ($GM) {
            echo "<tr><td>Taking Link:<td>" . fm_select($SelLinks,$T,'LinkId',0," style=color:" . $SelCols[$T['LinkId']] ,'',0,$SelCols);
            if ($ll>0 && $LinkTypes[$ll]['Cost'] && $LOWho && $LOWho != $T['Whose'] ) { 
              echo fm_checkbox('Pay',$T,'LinkPay') . " " . Credit() . $T['LinkCost'];
            }
            echo "<br>Update this normally";
            echo "<td>To:  " . fm_select($NewSyslocs,$T,'NewLocation');
          } else {
            echo "<tr><td>Taking Link:<td>" . fm_select($SelLinks,$T,'LinkId',0," style=color:" . $SelCols[$T['LinkId']] ,'',0,$SelCols);
            if ($ll && $LinkTypes[$ll]['Cost'] && $LOWho && $LOWho != $T['Whose'] ) { 
              echo fm_checkbox('Pay',$T,'LinkPay') . " " . Credit() . $T['LinkCost'];
            }
            if ($Lid > 0 && !strpos($SelLinks[$Lid],'?')) {
              echo "<td>To:  " . fm_select($NewSyslocs,$T,'NewLocation');
            } else {
              echo "<td>Move to:  " . fm_select($NewSyslocs,$T,'NewLocation');          
            }
          }
        }
        if ($Lid > 0) {
          echo "<input type=submit name=ACTION value='Cancel Move'>\n";
          $NeedOr = 1;
        }
      }

      if (($Lid == 0) && (($tprops & THING_CAN_BETRANSPORTED))) { 
        $XPorts = Get_AllThingsAt($T['SystemId']);
        $NeedCargo = ($tprops & THING_NEEDS_CARGOSPACE);
        $TList = [];
        $FF = Get_FactionFactionsCarry($Fid);
        foreach($XPorts as $X) {
          if ($NeedCargo && $X['CargoSpace'] < $T['Level']) continue; // Not big enough
          if ($ThingProps[$X['Type']] & THING_CANT_HAVENAMED) continue;
          if ($X['Whose'] != $Fid) {
            $Carry = (empty($FF[$X['Whose']])? 0 : $FF[$X['Whose']]['Props']);
            if (!$NeedCargo) $Carry >>= 4;
            if (($Carry&15) < 2) continue; // Don't carry Anoth#r8yPwd
          }

          if ($NeedCargo) {
            $OnBoard = Get_Things_Cond(0,"((LinkId=-1 OR LinkId=-3) AND SystemId=" . $X['id']);
            $Used = 0;
            foreach($OnBoard as $OB) if ($ThingProps[$OB['Type']]['Properties'] & THING_NEEDS_CARGOSPACE) $Used += $OB['Level'];
            if ($X['CargoSpace'] < $Used + $T['Level']) continue; // Space is used
          }
          $TList[$X['id']] = $X['Name'];  
        }

        if ($TList) {
          echo "<tr><td colspan=3>" . ($NeedOr?" <b>Or</b> ":'') . "Board: " . fm_select($TList,$T,'BoardPlace') . "<input type=submit name=ACTION value='Load on Turn'>";
          $Conflict = 0; // HOW?
          $Conf = Gen_Select("SELECT W.* FROM ProjectHomes PH, Worlds W WHERE PH.SystemId=" . $T['SystemId'] . " AND W.Home=PH.id AND W.Conflict=1");
          if ($Conf) $Conflict = $Conf[0]['Conflict'];
          if ($GM && $Conflict) echo " <b>Conflict</b> ";
          if ($GM || !$Conflict) echo "<input type=submit name=ACTION value='Load Now'>\n";
        } else {
          echo "<tr><td>No Transport Avail";
        }
      } else if (($Lid < -1) && (($tprops & THING_CAN_BETRANSPORTED))) {
/*
      if ($Lid == -2 || $Lid == -4) {
        $Host = Get_Thing($T['NewSystemId']);
        echo "<tr><td colspan=3>Loading on to: <b>" . $Host['Name'] . "</b> on the turn";
    
        if ($Lid == -4) {
          echo " and then unloading ";
        
          $HostId = $T['NewSytemId'];
          $H = Get_Thing($HostId);        
          $Hlid = $H['LinkId'];
          if ($Hlid > 0) {
            $Dest = $H['NewSystemId'];
          } else if ($Hlid == 0 || $Hlid == -2 || $Hlid == -4) {
            $Dest = $H['SystemId'];
          } else {
            $HostId = $H['SystemId'];
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
            $TN = Get_System($Dest);
            $Locs = Within_Sys_Locs($TN);
            echo "<b>" . $TN['Ref'] . " - " . $finallocs[$T['NewLocation']] . "</b> "; 
          }
        }
//to what

      } else {        
//        $Conflict = 0; 
//        $Conf = Gen_Select("SELECT W.* FROM ProjectHomes PH, Worlds W WHERE PH.SystemId=" . $T['SystemId'] . " AND W.Home=PH.id AND W.Conflict=1");
//        if ($Conf) $Conflict = $Conf[0]['Conflict'];
*/

        if ($Lid == -2)  if ($GM || $Fid == $Host['Whose'] || $Host['Whose'] == $FACTION['id'] ) echo "<input type=submit name=ACTION value='Unload on Turn'>\n";
//      }
     
        if ($Lid == -2) { 
          echo "<input type=submit name=ACTION value='Cancel Load'>\n";
        } else if ($Lid == -3) { 
          echo "<input type=submit name=ACTION value='Cancel Unload'>\n";
        } else if ($Lid == -4) { 
          echo "<input type=submit name=ACTION value='Cancel Load and Unload'>\n";
        }
      }
    }
  }
  
  if ($GM) echo "<tr>" . fm_radio('Whose',$FactNames ,$T,'Whose','',1,'colspan=6','',$Fact_Colours,0); 
  if  ($tprops & THING_HAS_GADGETS) echo "<tr>" . fm_textarea("Gadgets",$T,'Gadgets',8,3);
  if  ($tprops & THING_HAS_LEVELS) echo "\n<tr>" . fm_text("Orders",$T,'Orders',2);
  echo "<tr>" . fm_textarea("Description\n(For others)",$T,'Description',8,2);
  echo "<tr>" . fm_textarea('Notes',$T,'Notes',8,2);
  echo "<tr>" . fm_textarea('Named Crew',$T,'NamedCrew',8,2);
  
  $Have = Get_Things_Cond(0," (LinkId=-1 OR LinkId=-3) AND SystemId=$tid ");
  $Having = Get_Things_Cond(0," (LinkId=-2 OR LinkId=-4) AND NewSystemId=$tid ");
  
  if ($Have || $Having) {
    echo "<tr><td>Carrying:<td colspan=6>Note: To Unload after moving PLEASE put the move order in for the transport first.<br>";

//var_dump($Have,$Having);

    $Conflict = 0; 
    $Conf = Gen_Select("SELECT W.* FROM ProjectHomes PH, Worlds W WHERE PH.SystemId=" . $T['SystemId'] . " AND W.Home=PH.id AND W.Conflict=1");
    if ($Conf) $Conflict = $Conf[0]['Conflict'];
  
    $NewRef = (empty($T['NewSystemId']) ? $N['Ref'] : Get_System($T['NewSystemId'])['Ref']);
    
    $CargoUsed = 0;
 
    if ($Have) foreach ($Have as $H) {
      $Hid = $H['id'];
      $hprops = $ThingProps[$H['Type']];
      echo "<a href=ThingEdit.php?id=$Hid>" . $H['Name'] . "</a> a " . (($hprops & THING_HAS_LEVELS)? "Level " . $H['Level'] : "") . " " . $ttn[$H['Type']]; 
      if ($GM && $Conflict) echo " <b>Conflict</b> ";
      if ($GM || !$Conflict ) echo "<input type=submit name=ACT$Hid value='Unload Now'>\n";
      echo " to: " . $N['Ref'] . " - " . fm_select($Syslocs,$H,'WithinSysLoc',0,'',"WithinSysLoc:$Hid");
      if ($H['LinkId'] == -3) {
        echo " - Unloading on Turn to <b>$NewRef</b>, " . $NewSyslocs[$H['NewLocation']];
        echo "<input type=submit name=ACT$Hid value='Cancel Unload'>";
      } else if ($T['NewSystemId'] && $T['TargetKnown']) {
        echo "<input type=submit name=ACT$Hid value='Unload on Turn'>\n"; 
        echo " to: $NewRef - " . fm_select($NewSyslocs,$H,'NewLocation',0,'',"NewLocation:$Hid");
      }
      echo "<br>";
      if ($hprops & THING_NEEDS_CARGOSPACE) $CargoUsed += $H['Level'];
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
        echo "<input type=submit name=ACT$Hid value='Unload on Turn'>\n"; 
        echo " to: $NewRef - " . fm_select($NewSyslocs,$H,'NewLocation',0,'',"NewLocation:$Hid");
      }
      echo "<br>";
      if ($hprops & THING_NEEDS_CARGOSPACE) $CargoUsed += $H['Level'];
    }
    
    if ($CargoUsed > $T['CargoSpace'] ) echo "<span class=Err>This needs $CargoUsed cargo space, but only has " . $T['CargoSpace'] . "</span>\n";
//    echo "<a href=ThingEdit.php?ACTION=UnloadAll>Unload All</a>";
  }
  
  if ($GM) echo "<tr>" . fm_textarea('GM Notes',$T,'GM_Notes',8,2,'class=NotSide');
  
  $History = preg_split("/\n/",$T['History']);
  $RevHist = implode("\n",array_reverse($History));
  
  if (isset($_REQUEST['EDHISTORY'])) {
    echo "<tr>" . fm_textarea('History',$T,'History',8,2,'','','',($GM?'':'Readonly'));
  } else {
//    echo "<tr>" . fm_textarea('History',$T,'History',8,2,'','','',($GM?'':'Readonly'));
    echo "<tr><td>History:<td colspan=8><textarea rows=2>$RevHist</textarea>";
  }
  if ($tprops & THING_HAS_2_FACTIONS) echo "<tr>" . fm_radio('Other Faction',$FactNames ,$T,'OtherFaction','',1,'colspan=6','',$Fact_Colours,0); 
  if  ($tprops & (THING_HAS_MODULES | THING_HAS_ARMYMODULES)) {
    if ($GM) {
      echo "<tr>" . fm_number('Orig Health',$T,'OrigHealth');
      if ($T['CurHealth'] > 0) {
        echo fm_number('Cur Health',$T,'CurHealth');
      } else {
        echo fm_number('Cur Health',$T,'CurHealth',' class=Err ');      
      }
    } else {
      echo "<tr><td>Original Health: " . $T['OrigHealth'];
      if ($T['BuildState'] == 2 || $T['BuildState'] == 3) {
        if ($T['CurHealth'] > 0) {
          echo "<td>Current Health: " . $T['CurHealth'];
        } else {
          echo "<td class=Err>Current Health: <b>0</b>";
        }
      }
    }
    
    $Resc =0;
    $BD = Calc_Damage($T,$Resc);
    if ($Resc) {
      echo "<td>Basic Damage: $BD<td>There are other weapons";
    } else {
      echo "<td>Damage: $BD";
    }
  }
  if ($tprops & THING_HAS_DISTRICTS) {
    $DTs = Get_DistrictTypeNames();
    $Ds = Get_DistrictsT($tid);

    $NumDists = count($Ds);
    $dc=0;
    $totdisc = 0;

    if ($GM) {
      if ($NumDists) echo "<tr><td rowspan=" . ceil(($NumDists+2)/2) . ">Districts:";
      
      foreach ($Ds as $D) {
        $did = $D['id'];
        if (($dc++)%2 == 0)  echo "<tr>";
        echo "<td>" . fm_Select($DTs, $D , 'Type', 1,'',"DistrictType-$did") . fm_number1('', $D,'Number', '','',"DistrictNumber-$did");
        $totdisc += $D['Number'];      
      };


      echo "<tr><td>Add District Type<td>" . fm_Select($DTs, NULL , 'Number', 1,'',"DistrictTypeAdd-$tid");
      echo fm_number("Max Districts",$T,'MaxDistricts');
      echo fm_number(($T['ProjHome']?"<a href=ProjHomes.php?id=" . $T['ProjHome'] . ">Project Home</a>":"Project Home"),$T,'ProjHome');

      if (!isset($T['MaxDistricts'])) $T['MaxDistricts'] = 0;
      if ($totdisc > $T['MaxDistricts'] && $T['MaxDistricts']>0) echo "<td class=Err>TOO MANY DISTRICTS\n";
    } else {
      if ($NumDists) echo "<tr><td rowspan=" . ceil(($NumDists+4)/4) . ">Districts:";
      
      foreach ($Ds as $D) {
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
//    echo "<tr><td>Modules to be done\n";
    $MTs = Get_ModuleTypes();
    $MNs = [];
    foreach ($MTs as $M) $MNs[$M['id']] = $M['Name']; 

//var_dump($MTs);
//    $MTNs = [];
//    foreach($DTs as $M) $MTNs[$M['id']] = $M['Name'];
    $MTNs = Get_Valid_Modules($T);
    $Ds = Get_Modules($tid);

    $NumMods = count($Ds);
    $dc=0;
    $totmodc = 0;
    $BadMods = 0;
//    $T['Sensors'] = $T['SensorLevel'] = $T['NebSensors'] = 0;
    
    if ($GM) { // TODO Allow for setting module levels 
      if ($NumMods) echo "<tr><td rowspan=" . ceil(($NumMods+2)/2) . ">Modules:";
  
      foreach ($Ds as $D) {
        $did = $D['id'];
        if (($dc++)%2 == 0)  echo "<tr>";
        echo "<td>" . (isset($MTNs[$D['Type']])? fm_Select($MTNs, $D , 'Type', 1,'',"ModuleType-$did") : "<span class=red>INV:" .  fm_Select($MNs, $D , 'Type', 1,'',"ModuleType-$did") . "</span>" )
                    . fm_number1('Level', $D,'Level', '', ' class=Num3 ',"ModuleLevel-$did") . ' # '
                    . fm_number0('', $D,'Number', '',' class=Num3 ',"ModuleNumber-$did") 
                    . "<button id=ModuleRemove-$did onclick=AutoInput('ModuleRemove-$did')>R</button>";
        if (!isset($MTNs[$D['Type']])) $BadMods += $D['Number'];
        $totmodc += $D['Number'] * $MTs[$D['Type']]['SpaceUsed'];
        
        if ($D['Type'] == 4) { 
          $T['Sensors'] = $D['Number'];
          $T['SensorLevel'] = $D['Level'];
        } else if ($D['Type'] == 9) $T['NebSensors'] = $D['Number'];
      }
      echo "<tr><td>Add Module Type<td>" . fm_Select($MTNs, NULL , 'Number', 1,'',"ModuleTypeAdd-$tid");
      echo fm_number1("Max Modules",$T,'MaxModules','',' class=Num3 ');
      if ($tprops & THING_HAS_CIVSHIPMODS) {
        echo fm_number1("Deep Space",$T,'HasDeepSpace');
        echo fm_number1("Cargo Space",$T,'CargoSpace');
      }
      if ($totmodc > $T['MaxModules']) {
        echo "<td class=Err>($totmodc) TOO MANY MODULES\n";
      } elseif ($BadMods) {
        echo "<td class=Err>$BadMods INVALID MODULES\n";
      } else {
        echo "<td>Module space used: $totmodc";
      }
      echo "<td>Speed: " . sprintf('%0.3g',$T['Speed']);
    } else {
      if ($NumMods) echo "<tr><td rowspan=" . ceil(($NumMods+4)/4) . ">Modules:";
  
      foreach ($Ds as $D) {
//        if ($D['Number'] == 0) continue;
        $did = $D['id'];
        if (($dc++)%4 == 0)  echo "<tr>";
        echo "<td><b>" . $D['Number']. "</b> of ";
        if (isset($MTNs[$D['Type']])) {
          echo $MTNs[$D['Type']] . ($T['BuildState']? (" (Level " . $D['Level'] . ") ") :"") ;
          switch ($MTs[$D['Type']]['Name']) {
            case 'Cargo Space':
              echo " Capacity: " . $T['CargoSpace'];
              break;
            case 'Sublight Engines':
              echo " Speed: " . sprintf('%0.3g',$T['Speed']);
              break;            
            default:
          }            
        } else {
          $M = $MTs[$D['Type']];
          if ($l = Has_Tech($T['Whose'],$M['BasedOn'])) {
            echo "<span class=err>Invalid</span> " . $M['Name'] . ' Modules ' . ($T['BuildState']? (" (Level " . $D['Level'] . ") ") :"") ;
          } else {
            echo '<span class=err>Unknown</span> ' . $M['Name'] . ' Modules' . ($T['BuildState']? (" (Level " . $D['Level'] . ") ") :"") ;
          }
        }
                
        $CLvl = Calc_TechLevel($Fid,$D['Type']);
        if ($CLvl < $D['Level'] && $T['BuildState'] != 0 ) {
          echo ". <span class=Blue> Note you have Level: $CLvl </span>";
        }
        if (!isset($MTNs[$D['Type']])) $BadMods += $D['Number'];
        $totmodc += $D['Number'] * $MTs[$D['Type']]['SpaceUsed'];
        };

      if ($totmodc > $T['MaxModules']) {
        echo "<tr><td>Max Modules: " . $T['MaxModules'];
//      echo fm_number1("Deep Space",$T,'HasDeepSpace');
        if ($totmodc > $T['MaxModules']) {
          echo "<td class=Err>TOO MANY MODULES\n";
        } elseif ($BadMods) {
          echo "<td class=Err>$BadMods INVALID MODULES\n";
        } else {
          echo "<td>Module space used: $totmodc";
        }
      }
      echo "<td>Speed: " . sprintf('%0.3g',$T['Speed']);
    }

 // TODO 
 // Max modules, current count, what e
  }
  
  if ($GM && ($tprops & THING_HAS_CIVSHIPMODS)) {
    echo "<tr>" . fm_number('Sensors',$T,'Sensors') . fm_number('Sens Level',$T,'SensorLevel') . fm_number('Neb Sensors', $T,'NebSensors');
  }
  $SpecOrders = []; $SpecCount = 0;
  $HasDeep = Get_ModulesType($tid,3);
  if ($HasDeep) {
    $HasDeep = $HasDeep[0]['Number'] * $HasDeep[0]['Level'];
  } else {
    $HasDeep = 0;
  }
  $TTNames = Thing_Types_From_Names();
  $Moving = ($T['LinkId'] > 0);
  
  if ($T['BuildState'] == 2 || $T['BuildState'] == 3) foreach ($ThingInstrs as $i=>$Ins) {
//  echo "Checking: $Ins<br>";
    switch ($Ins) {
    case 'None': // None
      break;
    

    case 'Colonise': // Colonise
      if ((($Moving || $tprops & THING_HAS_CIVSHIPMODS) == 0 ) ) continue 2;
      if (!Get_ModulesType($tid,10)) continue 2;

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
          if (!$Hab_dome) continue ;
          $HabPs[$P['id']] = [$P['Name'],$P['Type'],10];
        } else {
          $HabPs[$P['id']] = [$P['Name'],$P['Type'],6];
        }
      }
      if (empty($HabPs)) continue 2;
      break;
    
    case 'Voluntary Warp Home': // Warp Home
      if ((($tprops & THING_HAS_SHIPMODULES) == 0 ) || ($T['CurHealth'] == 0) ) continue 2;
      break;
    
    case 'Decommision': // Dissasemble
      if ($Moving || ($tprops & THING_HAS_SHIPMODULES) == 0 ) continue 2;
      // Is there a Home here with a shipyard
      $Loc = $T['SystemId'];
      $Homes = Gen_Get_Cond('ProjectHomes', "SystemId=$Loc AND Whose=$Fid");
      foreach ($Homes as $H) {
        $Ds = Get_DistrictsH($H['id']);
        if (isset($Ds[3])) break 2; // FOund a Shipyard
      }
      if (isset($N['id']) && Get_Things_Cond($Fid,"Type=" . $TTNames['Orbital Repair Yards'] . " AND SystemId=" . $N['id'] . " AND BuildState=3")) break; // Orbital Shipyard     
      continue 2;
    
    case 'Disband': // Dissasemble
      if ($Moving || ($tprops & THING_HAS_ARMYMODULES) == 0 ) continue 2;
      // Is there a Home here with a Military
      $Loc = $T['SystemId'];
      $Homes = Gen_Get_Cond('ProjectHomes', "SystemId=$Loc AND Whose=$Fid");
      foreach ($Homes as $H) {
        $Ds = Get_DistrictsH($H['id']);
        if (isset($Ds[2])) break 2; // FOund a Military District
      }
//      if (isset($N['id']) && Get_Things_Cond($Fid,"Type=" . $TTNames['Orbital Repair Yards'] . " AND SystemId=" . $N['id'] . " AND BuildState=3")) break; // Orbital Shipyard     
      continue 2;
    
    case 'Analyse Anomaly': // Analyse
      if ($T['Sensors'] == 0) continue 2;
      $Anoms = Gen_Get_Cond('Anomalies',"SystemId=" . $T['SystemId']);
      foreach($Anoms as $A) {
        $Aid = $A['id'];
        $FA = Gen_Get_Cond('FactionAnomaly',"AnomalyId=$Aid AND FactionId=$Fid");
        if (empty($FA[0]['id']) ) continue;
        $FA = $FA[0];
        if ($FA['State'] == 0 || $FA['State'] == 3) continue;
        if ($FA['Progress'] < $A['AnomalyLevel']) break 2; // This anomaly can be studied (There may be more than one, but one is enough
      }
      continue 2; // NOt yet
      
    case 'Establish Embassy': // Establish Embassy
      if (!Get_ModulesType($tid,22)) continue 2;  // Check if have Embassy & at homeworld
      if (Get_Things_Cond($Fid,"Type=" . $TTNames['Embassy'] . " AND SystemId=" . $N['id'] . " AND BuildState=3")) continue 2; // Already have one
      $Facts = Get_Factions();
      foreach ($Facts as $F) {
        if ($F['id'] == $T['Whose'] || $F['HomeWorld'] == 0) continue;
        $W = Get_World($F['HomeWorld']);
        $H = Get_ProjectHome($W['Home']);
        if ($H['SystemId'] == $T['SystemId']) {
          $T['OtherFaction'] = $F['id'];
          break 2;
        }
      }
      continue 2;
    
    case 'Make Outpost': // Make Outpost
      if ($Moving || empty($N) || !$HasDeep || ($N['Control'] > 0 && $N['Control'] != $Fid)) continue 2;
      
      if (Get_Things_Cond($Fid,"Type=" . $TTNames['Outpost'] . " AND SystemId=" . $N['id'] . " AND BuildState=3")) continue 2; // Already have one
      break;
      
    case 'Make Asteroid Mine':
      if ($Moving || !$HasDeep || !Has_Tech($Fid,'Asteroid Mining')) continue 2;
      if (Get_Things_Cond(0,"Type=" . $TTNames['Asteroid Mine'] . " AND SystemId=" . $N['id'] . " AND BuildState=3")) continue 2; // Already have one
      $Ps = Get_Planets($N['id']);
      foreach ($Ps as $P) if ($P['Type'] == 3) break 2;
      continue 2; // No field 
    
    case 'Make Advanced Asteroid Mine':
      if ($Moving || !$HasDeep || !Has_Tech($Fid,'Advanced Asteroid Mining')) continue 2;
      if (Get_Things_Cond(0,"Type=" . $TTNames['Asteroid Mine'] . " AND SystemId=" . $N['id'] . " AND BuildState=3")) continue 2; // Already have one
      $Ps = Get_Planets($N['id']);
      foreach ($Ps as $P) if ($P['Type'] == 3) break 2;
      continue 2; // No field 
    
    case 'Make Minefield':
      if ($Moving || !$HasDeep || !Has_Tech($Fid,'Mine Layers')) continue 2;
      break;
      
    case 'Make Orbital Repair Yard':
      if ($Moving || !$HasDeep || !Has_Tech($Fid,'Orbital Repair Yards')) continue 2;
      if (Get_Things_Cond(0,"Type=" . $TTNames['Orbital Repair Yards'] . " AND SystemId=" . $N['id'] . " AND BuildState=3")) continue 2; // Already have one
      break;

    case 'Build Space Station':
      if ($Moving || !$HasDeep) continue 2;
      if (!Has_Tech($Fid,'Space Stations')) continue 2;
      if (Get_Things_Cond(0,"Type=" . $TTNames['Space Station'] . " AND SystemId=" . $N['id'] . " AND BuildState=3")) continue 2; // Already have one
      break;

    case 'Expand Space Station':
      if ($Moving || !$HasDeep || !Has_Tech($Fid,'Space Stations')) continue 2;//
      if (!(Get_Things_Cond($Fid,"Type=" . $TTNames['Space Station'] . " AND SystemId=" . $N['id'] . " AND BuildState=3"))) continue 2; // Don't have one
      break;

    case 'Make Deep Space Sensor':
      if ($Moving || !$HasDeep || !Has_Tech($Fid,'Deep Space Sensors')) continue 2;
      break;

    case 'Build Stargate':
      if ($Moving || ($HasDeep < 20) || !Has_Tech($Fid,'Stargate Construction')) continue 2;
      break;

    case 'Dismantle Stargate':
      if ($Moving || !$HasDeep || !Has_Tech($Fid,'Stargate Construction')) continue 2;
      if (empty($Links)) continue 2;
      break;

    case 'Make Planet Mine':
      if ($Moving || !$HasDeep || !Has_Tech($Fid,'Signal Jammer')) continue 2;
      $PMines = Get_Things_Cond(0,"Type=" . $TTNames['Planet Mine'] . " AND SystemId=" . $N['id'] . " AND BuildState=3");
      $Ps = Get_Planets($N['id']);
      $idx = $found = 0;
      foreach ($Ps as $P) {
        $idx++;
        if ($P['Minerals'] == 0) continue;
        foreach($PMines as $PM) {
          if ($PM['WithinSysLoc'] == $idx+200) continue 2; // Already have mine on this planet
          $found = 1;
          break;
        }
        $found = 1;
      }
      if (!$found) continue 2; // No valid planets
      break;

    case 'Construct Command Relay Station':
      if ($Moving || !$HasDeep || !Has_Tech($Fid,'Signal Jammer')) continue 2;   
      if (Get_Things_Cond(0,"Type=" . $TTNames['Command Relay Station'] . " AND SystemId=" . $N['id'] . " AND BuildState=3")) continue 2; // Already have one
      break;
      
    case 'Repair Command Node': // Not coded yet
//      continue 2;
      if ($Moving || !$HasDeep || !Has_Tech($Fid,'Signal Jammer')) continue 2;

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
      if (!Get_ModulesType($tid,25)) continue 2;
      break;
    
    case 'Transfer':
      break;    

    case 'Make Something': // Generic GM special for weird DSC projects
      if ($Moving || !$HasDeep ) continue 2;   
      break;
      

     default: 
      continue 2;
      
    }
    $SpecOrders[$i] = $Ins;
    $SpecCount++;
  }

/*
$ThingInstrs = ['None','Colonise','Voluntary Warp Home','Decommision','Analyse Anomaly','Establish Embassy','Make Outpost','Make Asteroid Mine','Make Minefield',
                'Make Orbital Repair Yard','Build Space Station','Expand Space Station','Make Deep Space Sensor','Make Advanced Asteroid Mine','Build Stargate',
                'DSC Special'];
*/
  if ($SpecCount>1) { 
    $PTs=Get_ProjectTypes();
    $PTNs = [];
    foreach($PTs as $PT) $PTNs[$PT['Name']] = $PT;
// var_dump($PTNs);
    
    $ProgShow = $Cost = $Acts = 0;
    echo "<tr>" . fm_radio('Special Instructions', $SpecOrders,$T,'Instruction','',1,' colspan=6 ','',$ThingInclrs);// . " under development don't use yet";
    switch ($ThingInstrs[$T['Instruction']]) {
    case 'None': // None
      break;

    case 'Colonise': // Colonise
      $PlTs = Get_PlanetTypes();
      $Ps = Get_Planets($N['id']);
      $Hab_dome = Has_Tech($Fid,'Habitation Domes');
      $HabPs = [];
      foreach($Ps as $P) {
        if (!$PlTs[$P['Type']]['Hospitable']) continue;
        if (Get_DistrictsP($P['id'])) continue; // Someone already there
        if (($P['Type'] == $FACTION['Biosphere']) || ($PH['Type'] == $FACTION['Biosphere2']) || ($PH['Type'] == $FACTION['Biosphere3'])) {
          $HabPs[$P['id']] = [$P['Name'],$P['Type'],3];
        } else if ($P['Type'] == 4 ) {
          if (!$Hab_dome) continue;
          $HabPs[$P['id']] = [$P['Name'],$P['Type'],10];
        } else {
          $HabPs[$P['id']] = [$P['Name'],$P['Type'],6];
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
          $Acts = $data[2];
          echo "<tr><td><td colspan=6>Colonising: " . $P['Name'] . " a " . $PlTs[$P['Type']]['Name'] . ($PlTs[$P['Type']]['Append']?' Planet':'') .
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
//    echo "<tr>" . fm_radio('Special Instructions', $SpecOrders,$T,'Instruction','',1,' colspan=6 ','',$ThingInclrs) . " under development don't use yet";
        echo fm_radio('Colonising',$Plans,$T,'Spare1','',0,'','',$Cols);
      }
      if ($T['Spare1']) $Acts = $HabPs[$T['Spare1']][2];
      $PrimeMods = [];
      $DTs = Get_DistrictTypes();
      foreach ($DTs as $D) if ($D['Props'] &1) $PrimeMods[$D['id']] = $D['Name'];
      echo "<br>District to Establish:" . fm_select($PrimeMods,$T,'Dist1');
      if (Get_ModulesType($tid,27)) echo "<br>Second District (must be different):" .fm_select($PrimeMods,$T,'Dist2');
      $ProgShow = 1;
      $Cost = -1;
      break;
    
    case 'Voluntary Warp Home': // Warp Home
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
        if (!$Anom) {
          $T['ProjectId'] = 0;
        }      
      }
 
      if ($T['ProjectId'] == 0) { // Pid == AnomId
        $Anoms = Gen_Get_Cond('Anomalies',"SystemId=" . $T['SystemId']);
        $Alist = [];
        $Al = 0;
        foreach($Anoms as $A) {
          $Aid = $A['id'];

          $FA = Gen_Get_Cond('FactionAnomaly',"AnomalyId=$Aid AND FactionId=$Fid");
          if (empty($FA[0]['id'])) continue;
          $FA = $FA[0];
          if ($FA['Progress'] < $A['AnomalyLevel']) {
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
        $FA = (Gen_Get_Cond('FactionAnomaly',"FactionId=$Fid AND AnomalyId=$Aid"))[0];
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
      echo "<tr><td><td colspan=6>Make sure you move to the location within the system you want to mine";
      echo "<br>" . fm_text0("Name of Minefield",$T,'MakeName');
      $Acts = $PTNs['Build Minefield']['CompTarget'];
      $ProgShow = 1;
      break;

    case 'Build Space Station':
      $PrimeMods = [];
      $DTs = Get_DistrictTypes();
      $MaxDeep = Has_Tech($Fid,'Deep Space Construction')*2;
      foreach ($DTs as $D) if ($D['Props'] &1) $PrimeMods[$D['id']] = $D['Name'];
      echo "<tr><td><td colspan=6>" . (($GM || $T['Progress'] == 0)? fm_number0('How many districts:',$T,'Dist1',''," max=$MaxDeep "): ("<td>Districts: " . $T['Dist1']));
      echo " First District:" . fm_select($PrimeMods,$T,'Dist2',1);
      echo "<br>" . fm_text0("Name of Space Station",$T,'MakeName');
      $Acts = - $PTNs['Build Space Station']['CompTarget']*$T['Dist1'];
      $ProgShow = 1;      
      break;
 
    case 'Expand Space Station': 
      $MaxDeep = Has_Tech($Fid,'Deep Space Construction')*2;
      
      $SS = (Get_Things_Cond($Fid,"Type=" . $TTNames['Space Station'] . " AND SystemId=" . $N['id'] . " AND BuildState=3"))[0];
      
      if (empty($SS)) {
        echo "Tell Richrd something hs gone wrong with this<p>";
        break;
      }     
      
      $MaxDeep = $MaxDeep - $T['MaxDistricts'];
      echo "<tr><td colspan=6>" . (($GM || $T['Progress'] == 0)? fm_number0('By how many districts:',$T,'Dist1',''," max=$MaxDeep "): ("<td>Adding: " . $T['Dist1'] . " disticts"));
      $ProgShow = 1;
      if ($T['Dist1']) {
        $Acts = - $PTNs['Extend Space Station']['CompTarget']*$T['Dist1'];
      }
      break;

    case 'Make Deep Space Sensor':
      echo "<br>" . fm_text0("Name of Deep Space Sensor",$T,'MakeName');
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
      $ProgShow = 1;
      if ($T['Dist1']) {
        $LL = $LinkLevels[$T['Dist1']];
        $LinkRes = GameFeature('LinkResource',0);
        if ($LinkRes) {
          AddCurrencies();
          $Cur = 0;
          foreach ($Currencies as $Ci => $C) if ( $C == $LinkRes) $Cur = $Ci;
          if ($Faction["Currency" . (6 - $Cur)] >= $LL['MakeCost']) {
            echo "Will use " . $LL['MakeCost'] . " $LinkRes<br>";
          } else {
            echo "<span class=Err>Warning needs more $LinkRes than you have </span>";
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
        if (($P['Type'] == $FACTION['Biosphere']) || ($PH['Type'] == $FACTION['Biosphere2']) || ($PH['Type'] == $FACTION['Biosphere3'])) {
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
        echo "<input type=submit name=ACTION value='Transfer Now'>";
      }
      break;
      
    case 'Make Something':  // Generic making AKA Analyse for academic 
      echo "<br>These projects will be defined by a GM<br>";
      echo fm_text0("Name project - meaningfull to you and the GM:",$T,'MakeName',2);
      echo fm_number0('Actions Needed',$T,'ActionsNeeded');
      $Acts = $T['ActionsNeeded'];
      $ProgShow = 3;
      break;



    default: 
      break;
    }
    $T['ActionsNeeded'] = $Acts;    
    if ($Cost < 0) {
      $T['InstCost'] = $Cost = 0;
    } else {
      $T['InstCost'] = $Cost = $Acts*10;
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
      if ($Cost>0 && ($T['Progress'] == 0)) echo " - Will cost " . Credit() . fm_number0('',$T,'InstCost') . " this turn";
      if ($T['Instruction'] != $T['CurInst']) {
        $T['CurInst'] = $T['Instruction'];
        $T['Progress'] = 0;
      }
    }
  }
  if (Access('God')) { 
    echo "<tr><td>Instruction:<td>" . fm_select($ThingInstrs,$T,'Instruction') . "<td>Stored: "  . fm_select($ThingInstrs,$T,'CurInst');
    echo fm_number1('Dist1',$T,'Dist1') . fm_number1('Dist2',$T,'Dist2') . fm_number1('Spare1',$T,'Spare1');
    if ($T['ProjectId'] && $T['BuildState'] > 1) echo "<tr>" . fm_number('Prog/Anom Id',$T,'ProjectId') . "<td colspan=2>Reminder progress from Anomaly itself";
    echo "<tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea>";  
  }
  echo "</table></div>\n";
  echo "<input type=submit name=ACTION value=Refresh>";
  if ($GM) {
    echo "<input type=submit name=ACTION value='GM Refit'> <input type=submit name=ACTION value='Destroy Thing (Leave debris)'>" .
       " <input type=submit name=ACTION value='Remove Thing (No debris)'>";
    if ($tprops & THING_CAN_MOVE) echo "  <input type=submit name=ACTION value='Warp Out'>\n";
    echo fm_number0(" Do",$T,'Damage', '',' class=Num3 ') . " <input type=submit name=ACTION value=Damage>\n";
  }
  if (!$GM && ($tprops & THING_CAN_BECREATED)) echo "<input type=submit name=ACTION value='Delete'>";
  if ($GM || empty($Fid)) {
    if (Access('God')) {
      echo "<h2><a href=ThingList.php>Back to Thing list</a> &nbsp; <input type=submit name=ACTION value=Duplicate> <input type=submit name=ACTION value='GM Recalc'></h2>";
    }
  } else {
    echo "<h2><a href=PThingList.php?id=$Fid>Back to Thing list</a></h2>";
  }
  Put_Thing($T);
}

?>
