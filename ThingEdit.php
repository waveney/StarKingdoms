<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("ThingShow.php");
  
function New_Thing(&$t) {
  global $BuildState;
  $ttn = Thing_Type_Names();
  $FactNames = Get_Faction_Names();
  $Fact_Colours = Get_Faction_Colours();
  $Systems = Get_SystemRefs();
  if (!isset($t['Whose'])) $t['Whose'] = 0;

  echo "<h1>Create Thing:</h1>";
  echo "<form method=post action=ThingEdit.php>";
  echo "<table><tr><td>Type:<td>" . fm_select($ttn,$t,'Type');
  echo "<tr>" . fm_text("Name",$t,'Name');
  echo "<tr>" . fm_number("Level",$t,'Level');
  echo "<tr>" . fm_radio('Whose',$FactNames ,$t,'Whose','',1,'colspan=6','',$Fact_Colours,0); 
  echo "<tr><td>System:<td>" . fm_select($Systems,$t,'SystemId');
  echo "<tr><td>BuildState:<td>" . fm_select($BuildState,$t,'BuildState');
  echo "<tr><td><td><input type=submit name=ACTION value=Create>\n";
  echo "</table></form>";
  dotail();
}

  $Force = (isset($_REQUEST['FORCE'])?1:0);
  
  if (Access('GM')) {
    A_Check('GM');
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
      $t = ['Level'=>1, 'BuildState'=>3, 'LinkId'=>0];
      New_Thing($t);
      break;
       
    case 'Create' : // Old code
      if (!isset($_POST['SystemId'])) {
        echo "No System Given";
        New_Thing($_POST);
      }
      $_POST['GameId'] = $GAMEID;
      $_POST['NewSystemId'] = $_POST['SystemId'];
      $tid = Insert_db_post('Things',$t);
      $t['id'] = $tid;
       
      if ($t['Type'] == 6) { // Outpost
        $N = Get_System($t['SystemId']);
        $N['Control'] = $t['Whose'];
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
      $t = Get_Thing($tid);
      Check_MyThing($t,$Fid);
      $Wid = $_REQUEST['Wid'];
      $World = Get_World($Wid);
      $Home = Get_ProjectHome($World['Home']);
      $t['SystemId'] = $Home['SystemId'];
      $t['WithinSysLoc'] = $Home['WithinSysLoc'];
      $t['BuildState'] = 3;
      Put_Thing($t);
      break;
      
    case 'Select_Thing' :
      $tid = $_REQUEST['id'];
      $t = Get_Thing($tid);
      Check_MyThing($t,$Fid);
      $Thing = $_REQUEST['Thing'];
      $Host = Get_Thing($Thing);
      $t['LinkId'] = -1;
      $t['SystemId'] = $Thing;
      $t['BuildState'] = 3;
      Put_Thing($t);
      break;
     
    case 'DELETE' :
      $tid = $_REQUEST['id'];
      $t = Get_Thing($tid);
      Check_MyThing($t,$Fid);
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
      $t = Get_Thing($tid);
      Check_MyThing($t,$Fid);
      $t = Thing_Duplicate($_REQUEST['id']);
      $tid = $t['id'];
      break;
       
    case 'GM Refit' :
      $tid = $_REQUEST['id'];
      $t = Get_Thing($tid);
      Calc_Scanners($t);
      RefitRepair($t);
      break;     
     
    case 'GM Recalc' :
      $tid = $_REQUEST['id'];
      $t = Get_Thing($tid);
      Calc_Scanners($t);
      RefitRepair($t,1,1);
      break;     
     
    case 'Destroy Thing (Leave debris)':
      $tid = $_REQUEST['id'];
      $t = Get_Thing($tid);
      Check_MyThing($t,$Fid);
      $t = Get_Thing($_REQUEST['id']);
      $t['BuildState'] = 4;  // Ex
      Put_Thing($t);
      break;

    case 'Remove Thing (No debris)':
      $tid = $_REQUEST['id'];
      $t = Get_Thing($tid);
      Check_MyThing($t,$Fid);
      db_delete('Things',$_REQUEST['id']);
      echo "<h1>Deleted</h1>";
      echo "<h2><a href=PThingList.php>Back to Thing list</a></h2>";
      dotail();
      break;

    case 'Warp Out':
      $tid = $_REQUEST['id'];
      $t = Get_Thing($tid);
      Check_MyThing($t,$Fid);
      $Gates = Get_Things_Cond($t['Whose'],' Type=15'); // Warp Gates
      if ($Gates) {
        if (isset($Gates[1])) { // Multiple Gates
          $GLocs = [];
          foreach ($Gates as $G) {
            $N = Get_System($G['SystemId']);
            $GLocs[$G['id']] = $N['Ref'];
          }
          echo "<h2>Please Choose which gate:</h2>";
          echo "<form method=post action=ThingEdit.php>";
          echo fm_hidden('id', $t['id']);
          echo fm_select($GLocs,$_REQUEST,'G');
          echo "<input type=submit name=ACTION value='Select Gate'>";
          echo "</form>";
          dotail();
        } else {
          $t['SystemId'] = $Gates[0]['SystemId'];
          $t['WithinSysLoc'] = $Gates[0]['WithinSysLoc'];
          $t['CurHealth'] = $t['Link_id'] = 0;
          Put_Thing($t);
          break;
        }
      } else {
        echo "<h2 class=Err>No Warp Gates...</h2>\n";
      }
      break;
    
    case 'Cancel Move':
      $tid = $_REQUEST['id'];
      $t = Get_Thing($tid);
      Check_MyThing($t,$Fid);
      $t['LinkId'] = 0;
      Put_Thing($t);
      break;            
       
    case 'Select Gate':
      $tid = $_REQUEST['id'];
      $t = Get_Thing($tid);
      Check_MyThing($t,$Fid);
      $Gate = Get_Thing($_REQUEST['G']);
      $t['SystemId'] = $Gate['SystemId'];
      $t['WithinSysLoc'] = $Gate['WithinSysLoc'];
      $t['CurHealth'] = $t['Link_id'] = 0;
      Put_Thing($t);
      break;

    case 'UnloadAll' : // Not called 
      // Need list of Worlds in location  - if only one select it - 
      $tid = $_REQUEST['id'];
      $t = Get_Thing($tid);
      Check_MyThing($t,$Fid);
      $Homes = Gen_Get_Cond('ProjectHomes', "SystemId=" . $t['SystemId']);
      if (!Homes) { 
      
      } else if (count($Homes) == 1) {
      
      } else {
       
      }  
     
    case 'Load Now':
// echo "<p>Here";
      $tid = $_REQUEST['id'];
      $t = Get_Thing($tid);
      Check_MyThing($t,$Fid);
      $Hid = $_REQUEST['BoardPlace'];
      if (!$Hid || ! ($H = Get_Thing($Hid))) {
        echo "<h2 class=Err>Board What?</h2>\n";
        break;
      }
      $t['LinkId'] = -1;
      $t['SystemId'] = $Hid;
//var_dump($t); exit;
      Put_Thing($t);
      break;
     
    case 'Load on Turn':
      $tid = $_REQUEST['id'];
      $t = Get_Thing($tid);
      Check_MyThing($t,$Fid);
      $Hid = $_REQUEST['BoardPlace'];
      if (!$Hid || ! ($H = Get_Thing($Hid))) {
        echo "<h2 class=Err>Board What?</h2>\n";
        break;
      }
      $t['LinkId'] = -2;
      $t['NewSystemId'] = $Hid;
      Put_Thing($t);
      break;
    
    case 'Unload Now': 
      $tid = $_REQUEST['id'];
      $t = Get_Thing($tid);
      $H = Get_Thing($t['SystemId']);
      Check_MyThing($t,$Fid,$H);
      if ($H['LinkId'] < 0) {
        $t['LinkId'] = -1;
        $t['SystemId'] = $H['SystemId'];
        $OH = Get_Thing($H['SystemId']);
        Put_Thing($t);
        echo "<h2>No longer embeded within " . $H['Name'] . " but still on board " . $OH['Name'] . "</h2>\n";
        break;
      }
      $t['LinkId'] = 0;
      $t['SystemId'] = $H['SystemId'];
      $t['WithinSysLoc'] = $H['WithinSysLoc'];
      $N = Get_System($H['SystemId']);
      $Syslocs = Within_Sys_Locs($N,0,0,0,1);
      Put_Thing($t);
      echo "<h2>Where should it be unloaded to?</h2>\n";
      echo "<form method=post action=ThingEdit.php>";
      echo fm_hidden('id',$t['id']);
      echo fm_select($Syslocs,$t,'WithinSysLoc');
      echo "<input type=submit name=ACTION value='Select Destination'>";
      dotail();
      
    case 'Select Destination':
      $tid = $_REQUEST['id'];
      $t = Get_Thing($tid);
      Check_MyThing($t,$Fid);
      Put_Thing($t);      
      break;
      
    case 'Unload on Turn':
      $tid = $_REQUEST['id'];
      $t = Get_Thing($tid);
 //     Check_MyThing($t,$Fid);  // TODO make this work with transports
      $Lid = $t['LinkId'];
      $t['NewLocation'] = 0; // For now
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
      $t['LinkId'] = $Lid;
      Put_Thing($t);

      
      $HostId = (($Lid == -3) ? $t['SystemId'] : $t['NewSystemId']);
      $H = Get_Thing($HostId);
      
      if ($H['LinkId'] > 0) {
        $Dest = $H['NewSystemId'];
      } else if ($H['LinkId'] == 0) {
        $Dest = $H['SystemId'];
      } else {
        // Very complicated skip for now
        break;
      }

      echo "<h2>Unload to where?</h2>\n";
      echo "<form method=post action=ThingEdit.php>";
      echo fm_hidden('id',$t['id']);


      $N = Get_System($Dest);
      $Syslocs = Within_Sys_Locs($N,0,0,0,1);
      echo fm_select($Syslocs,$t,'NewLocation');
      echo "<input type=submit name=ACTION value='Select Final Destination'>";
      dotail();
      
    case 'Select Final Destination':
      $tid = $_REQUEST['id'];
      $t = Get_Thing($tid);
      Check_MyThing($t,$Fid);
      $t['NewLocation'] = $_REQUEST['NewLocation'];
      Put_Thing($t);      
      break;
      
    case 'Cancel Load':
      $tid = $_REQUEST['id'];
      $t = Get_Thing($tid);
      Check_MyThing($t,$Fid);
      $t['LinkId'] = 0;
      Put_Thing($t);      
      break;      
    
    case 'Cancel Unload':
      $tid = $_REQUEST['id'];
      $t = Get_Thing($tid);
      Check_MyThing($t,$Fid);
      $t['LinkId'] = -1;
      Put_Thing($t);      
      break;      
    
    case 'Cancel Load and Unload':
      $tid = $_REQUEST['id'];
      $t = Get_Thing($tid);
      Check_MyThing($t,$Fid);
      $t['LinkId'] = 0;
      Put_Thing($t);      
      break;
      
    case 'None' :
    default: 
      break;
    }
  } else {
    foreach($_REQUEST as $RK=>$RV) {  // From the host not the thing itself
      if (preg_match('/ACT(\d*)/',$RK,$mtch)?true:false) {
        $Hid = $mtch[1];
        $H = Get_Thing($Hid);
        $t = Get_Thing($_REQUEST['id']); // What it is unloading from - gives sys

        switch ($RV) {
          case 'Unload Now':

            if ($t['LinkId'] >= 0) {
              $Sys = $t['SystemId'];
              $N = Get_System($Sys);
              $tt = $t;
            } else {
              $tt = Get_Thing($t['SystemId']);
              $Sys = $tt['SystemId'];
              $N = Get_System($Sys);
            } 
          
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

            if ($t['LinkId'] > 0) {
              $Sys = $t['NewSystemId'];
              $N = Get_System($Sys);
              $tt = $t;
            } else if ($t['LinkId'] == 0) {
              $Sys = $t['SystemId'];
              $N = Get_System($Sys);
            } else if ($t['LinkId'] == -1) {
              $tt = Get_Thing($t['SystemId']);
              $Sys = $tt['SystemId'];
              $N = Get_System($Sys);
            } // TODO Other cases are COMPLICATED

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
            break;
            
        }
        break;
      }
    }
  }
  
  if (isset($t)) {
    $tid = $t['id'];
  } else if (isset($_REQUEST['id'])) {
    $tid = $_REQUEST['id'];
    $t = Get_Thing($tid);
  } else {
    echo "<h2>No Thing Requested</h2>";
    dotail();
  }

  Check_MyThing($t,$Fid);
  echo "<br>";

  if ($Force) {
    $GM = 0;
    $Fid = $t['Whose'];
  } else {
    $GM = Access('GM');
  }

  if ($GM) echo "<h2>GM: <a href=ThingEdit.php?id=$tid&FORCE>This page in Player Mode</a></h2>";  
  if (empty($t) || ($t['Whose'] != $Fid && !$GM)) {
     echo "<h2 class=Err>Sorry that thing is not yours</2>";
  } else {
    Show_Thing($t,$Force);
  }
  if (($GM && !empty($tid)) || ($t['BuildState'] == 0)) echo "<br><p><br><p><h2><a href=ThingEdit.php?ACTION=DELETE&id=$tid>Delete Thing</a></h2>";

  
  dotail();
  
?>
  
  
  
  
  
  
  
  
  
  
  
