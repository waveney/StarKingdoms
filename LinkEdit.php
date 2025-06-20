<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("SystemLib.php");

  A_Check('GM');
  $LinkType = Feature('LinkMethod');

  dostaffhead("Edit Links",["js/dropzone.js","css/dropzone.css" ]);

  global $db, $GAME, $GAMEID;

//  var_dump($_REQUEST);
  if (isset($_REQUEST['L'])) {
    $Lid = $_REQUEST['L'];
  } else if (isset($_REQUEST['id'])) {
    $Lid = $_REQUEST['id'];
  } else {

    echo "<h2>No Systems Requested</h2>";
    dotail();
  }

  echo "<h1>Edit a Link</h1>";

  $L = Get_Link($Lid);
  $Ref1 = $L['System1Ref'];
  $Ref2 = $L['System2Ref'];
  $N1 = Get_SystemR($Ref1);
  $N2 = Get_SystemR($Ref2);

  $Factions = Get_Factions();

//var_dump($Know);

  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
    case 'Toggle' :
      $Fid = $_REQUEST['F'];
      $FLK = Gen_Get_Cond1('FactionLinkKnown',"FactionId=$Fid AND LinkId=$Lid");
      if (isset($FLK['id'])) {
        $FLK['Used'] = 1-$FLK['Used'];
        Gen_Put('FactionLinkKnown',$FLK);
      } else {
        $FLK = ['LinkId'=>$Lid, 'FactionId'=>$Fid, 'Used'=>1 ];
        Gen_Put('FactionLinkKnown',$FLK);
      }
      break;
    }
  }

  echo "<form method=post id=mainform enctype='multipart/form-data' action=LinkEdit.php>";
  echo "The Next Turn Mod does not include any Wormhole Stabilisers, the Current Mod does.<p>";
  echo "<div class=tablecont><table width=90% border class=SideTable>\n";
  Register_AutoUpdate('Links',$Lid);

  echo "<tr><td>Id: $Lid<td>Game: $GAMEID " . fm_text('Name',$L,'Name');
  echo "<tr>" . fm_number('Concealment',$L,'Concealment') . fm_number('Instablity',$L,'Instability');
  echo "<tr>" . fm_number('Current Mod',$L,'ThisTurnMod') . fm_number('Next Turn Mod',$L,'NextTurnMod');
  echo "<tr>" . fm_text('From',$L,'System1Ref') . "<td>" . NameFind($N1) . fm_text('To',$L,'System2Ref') . "<td>" . NameFind($N2);
  echo "<tr><td><b>Known by</b>";

  foreach ($Factions as $Fid=>$F) {
    $FLK = Gen_Get_Cond1('FactionLinkKnown',"FactionId=$Fid AND LinkId=$Lid");

    echo "<tr><td>" . $F['Name'] . "<td><a href=LinkEdit.php?ACTION=Toggle&L=$Lid&F=$Fid>" . (($FLK['Used']??0)?'Used':'No'); "</a>";
    echo "\n";
  }

  echo "</table><div>";

  echo "<h2><a href=SysEdit.php?N=" . $N1['id'] . ">Goto to System $Ref1</a>, <a href=SysEdit.php?N=" . $N2['id'] . ">Goto to System $Ref2</a></h2>";

  dotail();
?>
