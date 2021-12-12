<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("SystemLib.php");
  
  A_Check('GM');

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
  $Know = Get_Factions4Link($Lid);
  
  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
    case 'Toggle' :
      $Fid = $_REQUEST['F'];
      if (isset($Know[$Fid])) {
        $FL = $Know[$Fid];
        $FL['Known'] = ($FL['Known']?0:1);
        $Know[$Fid] = $FL;
        Put_FactionLink($FL);      
      } else {
        $FL = ['LinkId'=>$Lid, 'FactionId'=>$Fid, 'Known'=>1 ];
        Put_FactionLink($FL);
      }
      $Know = Get_Factions4Link($Lid);
      break;
    }
  }
      
  echo "<form method=post id=mainform enctype='multipart/form-data' action=LinkEdit.php>";
  echo "<div class=tablecont><table width=90% border class=SideTable>\n";
  Register_AutoUpdate('Link',$Lid);
  
  echo "<tr><td>Id: $Lid<td>Game: $GAMEID<td>" . fm_number('Level',$L,'Level');
  echo "<tr>" . fm_text('From',$L,'System1Ref') . "<td>" . NameFind($N1) . fm_text('To',$L,'System2Ref') . "<td>" . NameFind($N2);
  echo "<tr><td><b>Known by</b>";
  
  foreach ($Factions as $F) {
    echo "<tr><td>" . $F['Name'] . "<td><a href=LinkEdit.php?ACTION=Toggle&L=$Lid&F=" . $F['id'] . ">" . (isset($Know[$F['id']]) && $Know[$F['id']]['Known']?"Yes " . NameFind($Know):"No") . "</a>";
    echo "\n";
  }
  
  echo "</table><div>";
  
  echo "<h2><a href=SysEdit.php?N=" . $N1['id'] . ">Goto to System $Ref1</a>, <a href=SysEdit.php?N=" . $N2['id'] . ">Goto to System $Ref2</a></h2>";
  
  dotail();
?>
