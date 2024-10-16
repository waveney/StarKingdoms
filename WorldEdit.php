<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("ProjLib.php");
  include_once("HomesLib.php");
  include_once("vendor/erusev/parsedown/Parsedown.php");

  global $FACTION,$GAMEID;

  $Parsedown = new Parsedown();
  $Fid = 0;
  $xtra = '';
  $NeedDelta = 0;
  if (Access('Player')) {
    if (!$FACTION) {
      if (!Access('GM') ) Error_Page("Sorry you need to be a GM or a Player to access this");
    } else {
      $Fid = $FACTION['id'];
      $Faction = &$FACTION;

      $NeedDelta = Has_Trait($Fid,'This can be optimised');
    }
  }
  if ($GM = Access('GM') ) {
    A_Check('GM');
    if (isset( $_REQUEST['F'])) {
      $Fid = $_REQUEST['F'];
    } else if (isset( $_REQUEST['f'])) {
      $Fid = $_REQUEST['f'];
    }
    if (isset($Fid)) $Faction = Get_Faction($Fid);
  }

//  CheckFaction('WorldEdit',$Fid);

  dostaffhead("Edit Worlds and Colonies",["js/ProjectTools.js"]);

  $Wid = $_REQUEST['id'];

  if (isset($_REQUEST['ACTION'])) { // Pre Home actions
    switch ($_REQUEST['ACTION']) {
      case 'DELETE':
        db_delete('Worlds',$Wid);
        echo "<h2>Deleted</h2>";
        dotail();
    }
  }

  $W = Get_World($Wid);

// var_dump($W)  ;
  if (!isset($W['id'])) {
    echo "<h2>No World selected</h2>";
    dotail();
  }
  $TTypes = Get_ThingTypes();
  $PlanetTypes = Get_PlanetTypes();
  $Fid = $W['FactionId'];
  $DTs = Get_DistrictTypes();
  $DistTypes = 0;
  $SysId = 0;
  $Facts = Get_Factions();
  $SocPs = [];

  $H = Get_ProjectHome($W['Home']);
//  var_dump($H);

  if (isset($H['id'])) {
    if ($H['ThingType'] == 0 || $H['ThingId'] == 0 ) {
      echo "<h2 class=Err>There is a fault with Project Home " . $H['id'] . " Tell Richard</h2>";

    }

    $Dists = Get_DistrictsH($H['id']);
    $SocPs = Get_SocialPs($W['id']);

// var_dump($Dists);
    switch ($W['ThingType']) {
      case 1: //Planet
        $WH = $P = Get_Planet($W['ThingId']);
        $type = $PlanetTypes[$P['Type']]['Name'];
        if ($PlanetTypes[$P['Type']]['Append']) $type .= " Planet";
        $Name = $P['Name'];
        $SysId = $P['SystemId'];
        break;

      case 2: /// Moon
        $WH = $M = Get_Moon($W['ThingId']);
        $type = $PlanetTypes[$M['Type']]['Name'];
        if ($PlanetTypes[$M['Type']]['Append']) $type .= " Moon";
        $Name = $M['Name'];
        $P = Get_Planet($M['PlanetId']);
        $SysId = $P['SystemId'];
        break;

      case 3: // Thing
        $WH = $T = Get_Thing($W['ThingId']);
        $type = $TTypes[$T['Type']]['Name'];
        $Name = $T['Name'];
        $SysId = $T['SystemId'];
        break;
      default: // Error
        echo "<h2 class=Err>There is a fault with World " . $W['id'] . " Tell Richard</h2>";
        break;
    }

    $FS = Get_FactionSystemFS($Fid,$SysId);
    $PlanetLevel = max(1,($FS['PlanetScan']??1));

    if (isset($_REQUEST['ACTION'])) { // Post home actions
      switch ($_REQUEST['ACTION']) {
       case 'Militia' :
         Update_Militia($W,$Dists);
         echo "<h2>Militia Updated</h2>";
         break;
       case 'XMilitia' : // Transfer to who?
         $FactNames = Get_Faction_Names();
         $Fact_Colours = Get_Faction_Colours();

         echo "<form method=post action=WorldEdit.php?ACTION=XMilitia2>";
         echo fm_hidden('id',$Wid);
         echo  fm_radio('Whose',$FactNames ,$_REQUEST,'Whose','',1,'','',$Fact_Colours,0);
         echo "<br><input type=submit value='Transfer'>";
         dotail();

       case 'XMilitia2' : // Transfer
         Update_Militia($W,$Dists,$_REQUEST['Whose']);
         echo "<h2>Militia Transfered and Updated</h2>";
         dotail();
         break;

       }
    }
    $NumDists = 0;
    foreach ($Dists as $DT) {
      $NumDists += $DT['Number'];
      $DistTypes++;
    }
  } else {
    $NumDists = 0;
  }

  $dc=0;
  $totdisc = 0;

  echo "<h1>Edit World</h1>";
  echo "The only things you can change (currently) is the name, description and relative importance - higher numbers appear first when planning projects.<p>\n";
  Register_AutoUpdate("Worlds",$Wid);
  echo fm_hidden('id', $Wid);
  echo "<table border>";
  if ($GM) echo "<tr><td>Id:<td>$Wid\n";
  if (Access('God')) {
    echo "<tr>" . fm_number('ThingType',$W,'ThingType') . fm_number('ThingId',$W,'ThingId');
  }
  echo "<tr>" . fm_text('Name',$WH,'Name',3,'','',"Name:" . $W['ThingType'] . ":" . $W['ThingId']);
  echo "<tr>" . fm_textarea('Description',$WH,'Description',8,3,'','', "Description:" . $W['ThingType'] . ":" . $W['ThingId']);
  if ($GM) {
    echo "<tr>" . fm_number("Minerals", $W, 'Minerals');
  } else {
     echo "<tr><td>Minerals<td>" . $W['Minerals'];
  }
  echo "<tr>" . fm_number("Relative Importance", $W, 'RelOrder');
/*
  if ($GM) {
    echo "<tr>" . fm_number('Devastation',$W,'Devastation');
    echo "<tr>" . fm_number('Economy Factor
*/
  $NumCom = 0;
  $NumPrime = $Mines = 0; $DeltaSum = 0;
  if ($NumDists) {
    if ($NumDists) echo "<tr><td rowspan=" . ($DistTypes+1) . ">Districts:";
    foreach ($Dists as $D) {
      if ($D['Number'] == 0) continue;
//      var_dump($D);
      echo "<tr><td>" . ($DTs[$D['Type']]['Name'] ?? 'Illegal') . ": " . $D['Number'];
      if ($NeedDelta) {
        echo fm_number1("Delta",$D,'Delta',''," min=-$NeedDelta max=$NeedDelta ","Dist:Delta:" . $D['id']);
        $DeltaSum += $D['Delta'];
      }
      if ( (($DTs[$D['Type']]['Name']??0) == 'Intelligence') && Has_Tech($Fid,'Defensive Intelligence' )) {
              $Agents = Get_Things_Cond($Fid," Type=5 AND Class='Military' AND SystemId=$SysId ORDER BY Level DESC");
              if ($Agents) {
                $Bi = ($Agents[0]['Level']/2);
                echo " ( +$Bi From Defensive Intelligence)";
              }

      }
    }
    if ($NeedDelta && $DeltaSum != 0) {
      echo "<tr><td colspan=3 class=Err>The Deltas do not sum to zero";
    }
  } else {
    echo "<tr><td>No Districts currently\n";
  }

  $OrgTypes = Get_OrgTypes();
  $Orgs = Gen_Get_Cond('Organisations',"GameId=$GAMEID");

  // Offices
  $Offs = Gen_Get_Cond('Offices',"World=$Wid");
  if ($Offs) {
    echo "<tr><td rowspan=" . count($Offs) . ">Offices:";
    $Show = 0;
    foreach ($Offs as $Of) {
      if ($Show++) echo "<tr>";
      echo "<td colspan=4>" . ($Orgs[$Of['Organisation']]['Name']??'Unknown') .
        " ( " . ($OrgTypes[$Orgs[$Of['Organisation']]['OrgType']]['Name']??'Unknown') . " )";
    }
  }



  if ($SocPs) {
    echo "<tr><td rowspan=" . count($SocPs) . ">Social\nPrinciples:";
    $NumSp = 0;
    foreach ($SocPs as $si=>$SP) {
      $Prin = Get_SocialP($SP['Principle']);
//var_dump($Prin,$SP);
      if ($NumSp++) echo "<tr>";
      if ($GM) {
        echo "<td>" . $Prin['Principle'] . "<td>Adherence: " . $SP['Value'];
        echo "<td style='background:" . ($Facts[$Prin['Whose']]['MapColour']??'White') . "'>" . ($Facts[$Prin['Whose']]['Name']??'Unknown');
        echo "<td><a href=SocialEdit.php?Action=Edit&id=" . $SP['Principle'] . ">Change</a>";
        echo "<td colspan=6>" . $Prin['Description'];

      } else { // Player
        echo "<td>" . $Prin['Principle'] . "<td>Adherence: " . $SP['Value'];
        echo "<td colspan=6>" . $Prin['Description'];
      }
    }
  } else {
    echo "<tr><td colsan=2>No Social Principles Currently\n";
  }

  $Branches = Gen_Get_Cond('Branches', "HostType=" . $W['ThingType'] . " AND HostId=" . $W['ThingId'] );
  $BTypes = Get_BranchTypes();

  if ($Branches) {
    $Show = 0;
    if ($GM) {
      $Show = 1;
    } else {
      foreach ($Branches as $bi=>$B) if (($B['Whose']== $Fid) || (($BTypes[$B['Type']]['Props']&1)==0)) { $Show = 1; break;}
    }
    if ($Show) {
      echo "<tr><td>Branches:<td>";
      foreach ($Branches as $bi=>$B) {
 //       var_dump($B);
        if ($GM || ($B['Whose']== $Fid) || (($BTypes[$B['Type']]['Props']&1)==0)) {
          echo "A <b>" . $BTypes[$B['Type']]['Name'] . "</b> of the <b>" . $Orgs[$B['Organisation']]['Name'] .
               "</b> ( " . $OrgTypes[$Orgs[$B['Organisation']]['OrgType']]['Name'] . " ) - " .
               "<span style='background:" . $Facts[$B['Whose']]['MapColour'] . "'>" . $Facts[$B['Whose']]['Name'] . "</span>";
          if (($BTypes[$B['Type']]['Props']&1) != 0) echo " [Hidden]";
          echo "<br>";
        }
      }
    }
  }

  // Traits - need planet survey level

  for($i=1;$i<4;$i++) {
    if ($WH["Trait$i"] && ($WH["Trait$i" . "Conceal"] <= $PlanetLevel)) {
      echo "<tr><td>Trait:<td>" . $P["Trait$i"] . "<td colspan=4>" . $Parsedown->text(stripslashes($P["Trait$i" . "Desc"]));
    }
  }

  $H['Economy'] = Recalc_Economic_Rating($H,$W,$Fid);
  if (isset($H['id'])) Put_ProjectHome($H);

  if (!empty($W['MaxDistricts'])) echo "<td>Max Districts: " . $WH['MaxDistricts'];
  echo "<tr><td>Economy:<td>" . $H['Economy'];
  if ($W['Devastation']??0) {
    if ($W['Devastation'] <= $NumDists) {
      echo "<tr><td>Devastation:<td>" . $H['Devastation'] . " If this ever goes higher than the number of districts ($NumDists), districts will be lost.";
    } else {
      echo "<tr><td class=Err>Devastation:<td class=Err>" . $W['Devastation'] . "  This is higher than the number of districts ($NumDists), districts will be lost.";
    }
  }
//  echo "<tr><td>Home World:<td colspan=4>";

  if (Access('God')) echo "</tbody><tfoot><tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea>";

  echo "</table>";

  if (Access('GM')) {
    echo "<h2><a href=WorldEdit.php?ACTION=Militia&id=$Wid>Update Militia</a>, <a href=WorldEdit.php?ACTION=XMilitia&id=$Wid>Transfer Militia</a>, ";
    if (!isset($H['id'])) {
      echo "No Home! - <a href=WorldEdit.php?ACTION=DELETE&id=$Wid>No Home! Delete?</a>, \n";
    } else {
      echo "<a href=ProjHomes.php?ACTION=EDIT&id=" . $H['id'] .">Goto Project Home</a>";
    }
    echo ", <a href=BranchEdit.php?Action=Add&W=$Wid>Add Branch</a>";
    echo ", <a href=OfficeEdit.php?Action=Add&W=$Wid>Add Office</a>";
    echo ", <a href=SocialEdit.php?Action=Add&W=$Wid>Add Social Principle</a>";
    echo "</h2>\n";
  }

  dotail();
?>
