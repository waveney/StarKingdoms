<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("ThingShow.php");
  
function New_Thing(&$T) {
  global $BuildState;
  $ttn = Thing_Type_Names();
  $FactNames = Get_Faction_Names();
  $Fact_Colours = Get_Faction_Colours();
  $Systems = Get_SystemRefs();
  if (!isset($T['Whose'])) $T['Whose'] = 0;

  echo "<h1>Create Thing:</h1>";
  echo "<form method=post action=ThingEdit.php>";
  echo "<table><tr><td>Type:<td>" . fm_select($ttn,$T,'Type');
  echo "<tr>" . fm_text("Name",$T,'Name');
  echo "<tr>" . fm_number("Level",$T,'Level');
  echo "<tr>" . fm_radio('Whose',$FactNames ,$T,'Whose','',1,'colspan=6','',$Fact_Colours,0); 
  echo "<tr><td>System:<td>" . fm_select($Systems,$T,'SystemId');
  echo "<tr><td>BuildState:<td>" . fm_select($BuildState,$T,'BuildState');
  echo "<tr><td><td><input type=submit name=ACTION value=Create>\n";
  echo "</table></form>";
  dotail();
}

  $Force = (isset($_REQUEST['FORCE'])?1:0);
  $GM = Access('GM');
  if ($GM) {
    $Fid = 0;
    if (!empty($FACTION)) $Fid = $FACTION['id'];
  } else {
    $Fid = $FACTION['id'];
  }


  dostaffhead("Edit and Create Things",["js/dropzone.js","css/dropzone.css" ]);

  global $db, $GAME, $GAMEID,$BuildState;
  
// START HERE
//  var_dump($_REQUEST);

  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
    case 'NEW' :
      $T = ['Level'=>1, 'BuildState'=>3, 'LinkId'=>0];
      New_Thing($T);
      break;
       
    case 'Create' : // Old code
      if (!isset($_POST['SystemId'])) {
        echo "No System Given";
        New_Thing($_POST);
      }
      $_POST['GameId'] = $GAMEID;
      $_POST['NewSystemId'] = $_POST['SystemId'];
      $tid = Insert_db_post('Things',$T);
      $T['id'] = $tid;
       
      if ($T['Type'] == 6) { // Outpost
        $N = Get_System($T['SystemId']);
        $N['Control'] = $T['Whose'];
        Put_System($N);
        echo "<h2>System Control Updated</h2>\n";
      }
      break;
      
    case 'CREATE' : // Named Chars
      echo "<h2>Choose a world to start on:</h2>\n";
      $Worlds = Get_Worlds($Fid);
      $PlanetTypes = Get_PlanetTypes();
      $TTypes = Get_ThingTypes();
      echo "<form method=post action=ThingEdit.php>";
      echo fm_hidden('id',$_REQUEST['id']);

//      echo "<table border>";
      foreach ($Worlds as $W) {

        switch ($W['ThingType']) {
        case 1: //Planet
          $P = Get_Planet($W['ThingId']);
          $type = $PlanetTypes[$P['Type']]['Name'];
          if ($PlanetTypes[$P['Type']]['Append']) $type .= " Planet";
          $Name = $P['Name'];
          $Sys = Get_System($P['SystemId']);
          break;
        
        case 2: /// Moon
          $M = Get_Moon($W['ThingId']);
          $type = $PlanetTypes[$M['Type']]['Name'];
          if ($PlanetTypes[$M['Type']]['Append']) $type .= " Moon";
          $Name = $M['Name'];
          $Sys = Get_System($P['SystemId']);
          break;
    
        case 3: // Thing
          $T = Get_Thing($W['ThingId']);
          $type = $TTypes[$T['Type']]['Name'];
          $Name = $T['Name'];
          $Sys = Get_System($T['SystemId']);
          break;
        }
    
        $H = Get_ProjectHome($W['Home']);
        
        echo "<button class=projtype type=submit formaction=ThingEdit.php?ACTION=Select_World&Wid=" . $W['id'] . ">$Name</button><p>\n";
        
      }
      
      echo "<h2>Or a Thing to start in:</h2>\n";
      $Things = Get_Things($Fid);
      $TTypes = Get_ThingTypes();
      echo "<table border>";
      foreach($Things as $T) {
        if ( empty($T['Name']) || $T['BuildState'] <1 || $T['BuildState'] > 3 || ( $TTypes[$T['Type']]['Properties'] & THING_CANT_HAVENAMED) ) continue;
        echo "<button class=projtype type=submit formaction=ThingEdit.php?ACTION=Select_Thing&Thing=" . $T['id'] . ">" . $T['Name'] . "</button><p>\n";
      }  
      echo "</form>\n";
      dotail();
      
    case 'Select_World' :
      $tid = $_REQUEST['id'];
      $T = Get_Thing($tid);
      Check_MyThing($T,$Fid);
      $Wid = $_REQUEST['Wid'];
      $World = Get_World($Wid);
      $Home = Get_ProjectHome($World['Home']);
      $T['SystemId'] = $Home['SystemId'];
      $T['WithinSysLoc'] = $Home['WithinSysLoc'];
      $T['BuildState'] = 3;
      Put_Thing($T);
      break;
      
    case 'Select_Thing' :
      $tid = $_REQUEST['id'];
      $T = Get_Thing($tid);
      Check_MyThing($T,$Fid);
      $Thing = $_REQUEST['Thing'];
      $Host = Get_Thing($Thing);
      $T['LinkId'] = -1;
      $T['SystemId'] = $Thing;
      $T['BuildState'] = 3;
      Put_Thing($T);
      break;
     
    case 'DELETE' :
      $tid = $_REQUEST['id'];
      $T = Get_Thing($tid);
      Check_MyThing($T,$Fid);
      $Discs = Get_DistrictsT($tid);
      if ($Discs) {
        foreach ($Discs as $D) {
          db_delete('Districts',$D['id']);
        }
      }
      $Mods = Get_DistrictsT($tid);
      if ($Mods) {
        foreach ($Mods as $M) {
          db_delete('Modules',$M['id']);
        }
      }

      db_delete('Things',$tid);

      echo "<h1>Deleted</h1>";
      echo "<h2><a href=PThingList.php>Back to Thing list</a></h2>";
      dotail();
       
    case 'Duplicate' :
      $tid = $_REQUEST['id'];
      $T = Get_Thing($tid);
      Check_MyThing($T,$Fid);
      $T = Thing_Duplicate($_REQUEST['id']);
      $tid = $T['id'];
      break;
       
    case 'GM Refit' :
      $tid = $_REQUEST['id'];
      $T = Get_Thing($tid);
      Calc_Scanners($T);
      RefitRepair($T);
      break;     
     
    case 'GM Recalc' :
      $tid = $_REQUEST['id'];
      $T = Get_Thing($tid);
      Calc_Scanners($T);
      RefitRepair($T,1,1);
      break;     
     
    case 'Destroy Thing (Leave debris)':
      $tid = $_REQUEST['id'];
      $T = Get_Thing($tid);
      Check_MyThing($T,$Fid);
      $T = Get_Thing($_REQUEST['id']);
      $T['Control'] = 0;
      $T['BuildState'] = 4;  // Ex
      Put_Thing($T);
      break;

    case 'Delete':
    case 'Remove Thing (No debris)':
      $tid = $_REQUEST['id'];
      $T = Get_Thing($tid);
      Check_MyThing($T,$Fid);
      db_delete('Things',$_REQUEST['id']);
      echo "<h1>Deleted</h1>";
      echo "<h2><a href=PThingList.php>Back to Thing list</a></h2>";
      dotail();
      break;

    case 'Warp Out':
      $tid = $_REQUEST['id'];
      $T = Get_Thing($tid);
      Check_MyThing($T,$Fid);
      $Gates = Gates_Avail($Fid);
      if ($Gates) {
        if (isset($Gates[1])) { // Multiple Gates
          $GLocs = [];
          foreach ($Gates as $G) {
            $N = Get_System($G['SystemId']);
            $GLocs[$G['id']] = $N['Ref'];
          }
          echo "<h2>Please Choose which gate:</h2>";
          echo "<form method=post action=ThingEdit.php>";
          echo fm_hidden('id', $T['id']);
          echo fm_select($GLocs,$_REQUEST,'G');
          echo "<input type=submit name=ACTION value='Select Gate'>";
          echo "</form>";
          dotail();
        } else {
          $T['NewSystemId'] = $T['SystemId'] = $Gates[0]['SystemId'];
          $T['WithinSysLoc'] = $Gates[0]['WithinSysLoc'];
          $T['CurHealth'] = $T['LinkId'] = 0;
          $T['TargetKnown'] = 1;
          $T['History'] .= "Warped out to " . $T['SystemId'] . " reduced to 0 health\n";
          Put_Thing($T);
          db_delete_cond('ScansDue','ThingId=$tid AND Turn=' . $GAME['Turn']);
          break;
        }
      } else {
        echo "<h2 class=Err>No Warp Gates...</h2>\n";
      }
      break;
    
    case 'Cancel Move':
      $tid = $_REQUEST['id'];
      $T = Get_Thing($tid);
      Check_MyThing($T,$Fid);
      $T['LinkId'] = 0;
      Put_Thing($T);
      break;            
       
    case 'Select Gate':
      $tid = $_REQUEST['id'];
      $T = Get_Thing($tid);
      Check_MyThing($T,$Fid);
      $Gate = Get_Thing($_REQUEST['G']);
      $T['NewSystemId'] = $T['SystemId'] = $Gate['SystemId'];
      $T['WithinSysLoc'] = $Gate['WithinSysLoc'];
      $T['CurHealth'] = $T['LinkId'] = 0;
          $T['TargetKnown'] = 1;
      db_delete_cond('ScansDue',"ThingId=$tid AND Turn=" . $GAME['Turn']);
      Put_Thing($T);
      break;

    case 'UnloadAll' : // Not called 
      // Need list of Worlds in location  - if only one select it - 
      $tid = $_REQUEST['id'];
      $T = Get_Thing($tid);
      Check_MyThing($T,$Fid);
      $Homes = Gen_Get_Cond('ProjectHomes', "SystemId=" . $T['SystemId']);
      if (!Homes) { 
      
      } else if (count($Homes) == 1) {
      
      } else {
       
      }  
     
    case 'Load Now':
// echo "<p>Here";
      $tid = $_REQUEST['id'];
      $T = Get_Thing($tid);
      Check_MyThing($T,$Fid);
      $Hid = $_REQUEST['BoardPlace'];
      if (!$Hid || ! ($H = Get_Thing($Hid))) {
        echo "<h2 class=Err>Board What?</h2>\n";
        break;
      }
      $T['LinkId'] = -1;
      $T['SystemId'] = $Hid;
//var_dump($T); exit;
      Put_Thing($T);
      break;
     
    case 'Load on Turn':
      $tid = $_REQUEST['id'];
      $T = Get_Thing($tid);
      Check_MyThing($T,$Fid);
      $Hid = $_REQUEST['BoardPlace'];
      if (!$Hid || ! ($H = Get_Thing($Hid))) {
        echo "<h2 class=Err>Board What?</h2>\n";
        break;
      }
      $T['LinkId'] = -2;
      $T['NewSystemId'] = $Hid;
      Put_Thing($T);
      break;
    
    case 'Unload Now': 
      $tid = $_REQUEST['id'];
      $T = Get_Thing($tid);
      $H = Get_Thing($T['SystemId']);
      Check_MyThing($T,$Fid,$H);
      if ($H['LinkId'] < 0) {
        $T['LinkId'] = -1;
        $T['SystemId'] = $H['SystemId'];
        $OH = Get_Thing($H['SystemId']);
        Put_Thing($T);
        echo "<h2>No longer embeded within " . $H['Name'] . " but still on board " . $OH['Name'] . "</h2>\n";
        break;
      }
      $T['LinkId'] = 0;
      $T['SystemId'] = $H['SystemId'];
      $T['WithinSysLoc'] = $H['WithinSysLoc'];
      $N = Get_System($H['SystemId']);
      $Syslocs = Within_Sys_Locs($N,0,0,0,1);
      Put_Thing($T);
      echo "<h2>Where should it be unloaded to?</h2>\n";
      echo "<form method=post action=ThingEdit.php>";
      echo fm_hidden('id',$T['id']);
      echo fm_select($Syslocs,$T,'WithinSysLoc');
      echo "<input type=submit name=ACTION value='Select Destination'>";
      dotail();
      
    case 'Select Destination':
      $tid = $_REQUEST['id'];
      $T = Get_Thing($tid);
      Check_MyThing($T,$Fid);
      $T['WithinSysLoc'] = $_REQUEST['WithinSysLoc'];
      Put_Thing($T);      
      break;
      
    case 'Unload After Move':
    case 'Unload on Turn':
      $tid = $_REQUEST['id'];
      $T = Get_Thing($tid);
 //     Check_MyThing($T,$Fid);  // TODO make this work with transports
      $Lid = $T['LinkId'];
      $T['NewLocation'] = 0; // For now
      if ($Lid > 0) {
        echo "<h2 class=Err>Err?  This is moving itself...</h2>";
        break;
      } else if ($Lid == 0) {
        echo "<h2 class=Err>Err? Not on board ...</h2>";
        break;
      } else if ($Lid == -1 || $Lid == -3) {
        $Lid = -3;
      } else if ($Lid == -2 || $Lid == -4) {
        $Lid = -4;
      }
      $T['LinkId'] = $Lid;
      Put_Thing($T);

      
      $HostId = (($Lid == -3 || $Lid == -1) ? $T['SystemId'] : $T['NewSystemId']);
      $H = Get_Thing($HostId);
      
      if ($H['LinkId'] > 0) {
        $Dest = $H['NewSystemId'];
      } else if ($H['LinkId'] == 0) {
        $Dest = $H['SystemId'];
      } else if ($H['LinkId'] < 0) {
        echo "This has never been coded for yet - tell Richard: $tid $Lid $HostId";
        break;
      }

      $N = Get_System($Dest);
      echo "<h2>Unload to where? in " . $N['Ref'] . "</h2>\n";
      echo "<form method=post action=ThingEdit.php>";
      echo fm_hidden('id',$tid);


      $Syslocs = Within_Sys_Locs($N,0,0,0,1);
      echo fm_select($Syslocs,$T,'NewLocation');
      echo "<input type=submit name=ACTION value='Select Final Destination'>";
      echo "<p>\n<input type=submit name=ACTION value='Cancel'>\n";
      dotail();
      
    case 'Select Final Destination':
      $tid = $_REQUEST['id'];
      $T = Get_Thing($tid);
      Check_MyThing($T,$Fid);
      $T['NewLocation'] = $_REQUEST['NewLocation'];
      Put_Thing($T);      
      break;
      
    case 'Cancel Load':
      $tid = $_REQUEST['id'];
      $T = Get_Thing($tid);
      Check_MyThing($T,$Fid);
      $T['LinkId'] = 0;
      Put_Thing($T);      
      break;      
    
    case 'Cancel Unload':
      $tid = $_REQUEST['id'];
      $T = Get_Thing($tid);
      Check_MyThing($T,$Fid);
      $T['LinkId'] = -1;
      Put_Thing($T);      
      break;      
    
    case 'Cancel Load and Unload':
      $tid = $_REQUEST['id'];
      $T = Get_Thing($tid);
      Check_MyThing($T,$Fid);
      $T['LinkId'] = 0;
      Put_Thing($T);      
      break;
      
    case 'Pay on Turn':
      $tid = $_REQUEST['id'];
      $T = Get_Thing($tid);
      Check_MyThing($T,$Fid);
      $T['LinkCost'] = $_REQUEST['LinkCost'];
      $T['LinkPay'] = 1;
      Put_Thing($T);      
      break;
      
    case 'Transfer Now':
      $Factions = Get_Factions();
      $tid = $_REQUEST['id'];
      $T = Get_Thing($tid);
      Check_MyThing($T,$Fid);
      $OldWho = $T['Whose'];
      $T['Whose'] = $T['Dist1'];
      $T['Instruction'] = 0;
      $T['LinkId'] = 0;
      $T['LinkPay'] = 0;
      $T['LinkCost'] = 0;
      $T['Progress'] = 0;
      $T['ActionsNeeded'] = 0;
      $T['Dist1'] = 0;
      $T['Dist2'] = 0;
      $T['CurInst'] = 0;
      $T['MakeName'] ='';

      Put_Thing($T);
      echo "<h2>" . $T['Name'] . " has now been transfered to " . $Factions[$T['Whose']]['Name'] . "</h2>";

      if ($TTypes[$T['Type']] & THING_HAS_CONTROL) {
        Control_Propogate($T['SystemId'],$T['Whose']);
      }
      
      if (TTypes[$T['Type']] & THING_CAN_DO_PROJECTS) {
        Recalc_Project_Homes(0,1); //Should be silent
        Recalc_Worlds(1);  // Should be silent
      }

      dotail();
      break;
      
    case 'Damage':
      $tid = $_REQUEST['id'];
      $T = Get_Thing($tid);
      Check_MyThing($T,$Fid);
      $Dam = $_REQUEST['Damage'];
      $T['CurHealth'] = min(max(0,$T['CurHealth'] - $Dam), $T['OrigHealth']);
      echo "<h2>" . $T['Name'] . " has received $Dam dammage</h2>";
      Put_Thing($T);
      break;
      
    
    case 'None' :
    default: 
      break;
    }
  } else {
    foreach($_REQUEST as $RK=>$RV) {  // From the host not the thing itself
      if (preg_match('/ACT(\d*)/',$RK,$mtch)?true:false) {
        $Hid = $mtch[1];
        $H = Get_Thing($Hid); // What is being unloaded
        $Tid = $_REQUEST['id'];
        $T = Get_Thing($Tid); // What it is unloading from - gives sys

        if ($T['LinkId'] > 0) {
          $Sys = $T['NewSystemId'];
          $N = Get_System($Sys);
          $tt = $T;
        } else if ($T['LinkId'] == 0) {
          $Sys = $T['SystemId'];
          $N = Get_System($Sys);
        } else if ($T['LinkId'] == -1) {
          $tt = Get_Thing($T['SystemId']);
          $Sys = $tt['NewSystemId'];
          $N = Get_System($Sys);
        } else if ($T['LinkId'] == -2) { // Load on Turn
          $tt = Get_Thing($T['NewSystemId']);
          $Sys = ($tt['LinkId'] > 0 ? $tt['NewSystemId'] : $tt['SystemId']);
          $N = Get_System($Sys);
        } else if ($T['LinkId'] == -3) { // already unloading on Turn
          $tt = Get_Thing($T['NewSystemId']);
          $Sys = $tt['NewSystemId'];
          $N = Get_System($Sys);
        } else if ($T['LinkId'] == -4) { // Load and unloading on Turn
          $tt = Get_Thing($T['NewSystemId']);
          $Sys = $tt['NewSystemId'];
          $N = Get_System($Sys);
        } else { // -5 = Direct
          $Sys = $T['NewSystemId'];
          $N = Get_System($Sys);
          $tt = $T;          
        }

        switch ($RV) {
          case 'Unload Now':
          
            $wsysloc = $_REQUEST["WithinSysLoc:$Hid"];
            $Syslocs = Within_Sys_Locs($N,0,0,0,1);
            $newloc = (isset($Syslocs[$wsysloc])? $Syslocs[$wsysloc] : 'Deep Space');
            if ((preg_match('/Hospitable/',$newloc,$mtch)?true:false) || isset($_REQUEST['YES_SPACE'])) {
              $H['LinkId'] = 0;
              $H['SystemId'] = $Sys;
              $H['WithinSysLoc'] = $wsysloc;
              Put_Thing($H);
              
              if (isset($_REQUEST['YES_SPACE'])) {
                $Who = empty($FACTION['Name'])? "A GM " : $FACTION['Name'];
                GMLog4Later("$Who has unloaded <a href=ThingEdit.php?id=$Hid>" . $H['Name'] . " in " . $N['Ref'] . " to $newloc"); 
              }
              
            } else {
              echo "<h2>Do your really mean to unload to $newloc?</h2>";
              echo "<form method=post action=ThingEdit.php>";
              foreach($_REQUEST as $RI=>$RV) echo fm_hidden($RI,$RV);
              echo "<input type=submit name=YES_SPACE value='Yes Space Them!'></form>\n";
              dotail();
            }
            $i = 1; // stop weird error
            break;
                
          case 'Unload on Turn':
            $wsysloc = $_REQUEST["NewLocation:$Hid"];
            $Syslocs = Within_Sys_Locs($N,0,0,0,1);
            if ($wsysloc) {
              $newloc = (isset($Syslocs[$wsysloc])? $Syslocs[$wsysloc] : 'Deep Space');
              if ((preg_match('/Hospitable/',$newloc,$mtch)?true:false) || isset($_REQUEST['YES_SPACE'])) {
                $Lid = $H['LinkId'];
                if ($Lid == -1 || $Lid == -3) {
                  $H['LinkId'] = -3;
                } else if ($Lid == -2 || $Lid == -4) {
                  $H['LinkId'] = -4;
                }
                $H['NewLocation'] = $wsysloc;
                Put_Thing($H);
              } else {
                echo "<h2>Do your really mean to unload to $newloc?</h2>";
                echo "<form method=post action=ThingEdit.php>";
                foreach($_REQUEST as $RI=>$RV) echo fm_hidden($RI,$RV);
                echo "<input type=submit name=YES_SPACE value='Yes Space Them!'></form>\n";
                dotail();
              }
              break;
            
            } else {
              echo "<h2>Please select where to unload " . $H['Name'] . "<h2>";
              echo "<form method=post action=ThingEdit.php>";
              foreach($_REQUEST as $RI=>$RV) if ($RV != "NewLocation:$Hid") echo fm_hidden($RI,$RV);
              echo fm_select($Syslocs,$H,"NewLocation:$Hid");
              echo "<input type=submit name=Ignored value='Select'></form>\n";
              dotail();
            }
          break;
          
        case 'Cancel Unload':
          if ($H['LinkId'] == -3) {
            $H['LinkId'] = -1;
          } else if ($H['LinkId'] == -4) {
            $H['LinkId'] = -2;
          }
          Put_Thing($H);
          break;  
        }
      }
    }
  }
  
  if (isset($T)) {
    $tid = $T['id'];
  } else if (isset($_REQUEST['id'])) {
    $tid = $_REQUEST['id'];
    $T = Get_Thing($tid);
  } else {
    echo "<h2>No Thing Requested</h2>";
    dotail();
  }

  Check_MyThing($T,$Fid);
  echo "<br>";

  if ($Force) {
    $GM = 0;
    $Fid = $T['Whose'];
  }

  if ($GM) {
    echo "<h2>GM: <a href=ThingEdit.php?id=$tid&FORCE>This page in Player Mode</a>" . (Access('God')?", <a href=ThingEdit.php?id=$tid&EDHISTORY>Edit History</a>":"") . "</h2>";  
  }
  if (empty($T)) {
    echo "<h2 class=Err>Sorry that thing is not found</2>";  
  } elseif ($T['Whose'] != $Fid && !$GM) {
    echo "<h2 class=Err>Sorry that thing is not yours</2>";
  } else {
    Show_Thing($T,$Force);
  }
  if (($GM && !empty($tid)) || ($T['BuildState'] == 0)) echo "<br><p><br><p><h2><a href=ThingEdit.php?ACTION=DELETE&id=$tid>Delete Thing</a></h2>";

  
  dotail();
  
?>
  
  
  
  
  
  
  
  
  
  
  
