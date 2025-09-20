<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  A_Check('GM');

//  var_dump($_REQUEST);echo"<p>";
  global $NOTBY,$SETNOT;
  dostaffhead("Manage Operation Types");

  $AllG = 0;
  if (isset($_REQUEST['AllGames'])) {
    // Button for cur game
    // Show current NotBy Mask
    echo "<div class=floatright><h2>Showing All Games - Switch to <a href=OpTypes.php>Current Game</a></h2></div>";
    echo "The current NotBy Mask is : $SETNOT<p>\n";
    $AllG = 1;
  } else {
    echo "<div class=floatright><h2>Showing current game -  Switch to <a href=OpTypes.php?AllGames>All Games</a></h2></div>";
  }

  $DTs=Get_OpTypes($AllG);
  $OTs = Get_OrgTypes($AllG);
  $OTNs = [];
  foreach ($OTs as $i=>$OT) $OTNs[$i] = $OT['Name'];
//  $Techs = Get_Techs(0,$AllG);
//  $TechNames = Tech_Names($Techs);

  if (UpdateMany('OrgActions','Put_OpType',$DTs,1,'','','Name','RemoveMe','Props','',':'))  $DTs=Get_OpTypes($AllG);

  $coln = 0;

//  echo "Category 1=Academic,2=Ship Yard,4=Miltary,8=Intelligence,16=Construction, 32=Deep Space<p>";
  echo "Props: 4 lower bits: 0 = Operation is at Level, 1 = +1, 2 = +2, (3=+3), 4=+X 8 = +2X.  10 = Tech Select, 20 = SocPrin, 40=Outpost, " .
       "80=Outpost Create, 100=New Branch, 200=Hidden, 400=SocialP of Target, 800=Target Wormhole, 1000=Inhabited W, 2000=Money Q, 4000=Desc Needed, " .
       "8000=Anomaly at Target, 10000=Sci Points, 20000=All Orgs, 40000=Level Mod, 80000=Colonists, 100000=Not Friends, 200000=Always 1 Turn<p>";
  echo "Team Props: 1=hidden, 2=in space, 4=ground<p>";
  echo "Do NOT change the Op names - code depends on them<p>";

  echo "<form method=post>";
  Register_AutoUpdate('OrgActions', 0); // Not compatible with Hex
  if ($AllG) echo fm_hidden('AllGames',1);
  echo "<div class=tablecont><table id=indextable border>\n";
  echo "<thead><tr>";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Index</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Name</a>\n";
  if ($AllG) echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>NotBy</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Organisation</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Props</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Gate</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Team Props</a>\n";
  echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Description</a>\n";

  echo "</thead><tbody>";
  if ($DTs) foreach($DTs as $D) {
    $i = $Did = $D['id'];
    echo "<tr><td>$i";
    echo fm_text1("",$D,'Name',1,'','',"Name:$i");
    echo fm_notby($D,$i,$AllG,':');
    echo "<td>" . fm_select($OTNs,$D,'Office',1,'',"Office:$i");
    echo fm_hex1("",$D,'Props','','',"Props:$i");
    echo fm_text1("",$D,'Gate',1,'','',"Gate:$i");
    echo fm_number1("",$D,'TeamProps','','',"TeamProps:$i");
    echo "<td>" . fm_basictextarea($D,'Description',3,3,'',"Description:$i");
  }
  $D = [];
  echo "<tr><td><td><input type=text name='Name:0' >";
  echo fm_hidden('NotBy:0',$SETNOT);
  if ($AllG) echo "<td>$SETNOT";

  echo "<td>" . fm_select($OTNs,$D,'Office',1,'',"Office:0");
  echo fm_number1("",$D,'Props','','',"Props:0");
  echo fm_text1("",$D,'Gate',1,'','',"Gate:0");
  echo fm_number1("",$D,'TeamProps','','',"TeamProps:0");
  echo "<td>" . fm_basictextarea($D,'Description',3,3,'',"Description:0");
  if (Access('God')) echo "<tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea>";
  echo "</table></div>\n";
  echo "<input type=submit name=Update value=Update>\n";
  echo "</form></div>";

  dotail();

