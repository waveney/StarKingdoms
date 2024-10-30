<?php

include_once("sk.php");
include_once("GetPut.php");
include_once("ThingLib.php");
A_Check('GM');

//  var_dump($_REQUEST);echo"<p>";
global $NOTBY,$SETNOT,$GAMEID;

dostaffhead("Manage Tracked Resources Types");
echo "This is to managed other than the standard credit, currencies and science points<p>";

echo "Props: 4 lower bits: 0 =Normal, 1= TrackTable 1<p>";
if (isset($_REQUEST['ACTION'])) {
  switch ($_REQUEST['ACTION']) {
    case 'Add':
      $Res = ['Name'=>$_REQUEST['Name'], 'Props'=>$_REQUEST['Props'], 'GameId'=>$GAMEID];
      Gen_Put('ResourceTypes',$Res);
      break;
  }
}


$ResTypes = Gen_Get_All('ResourceTypes');
$coln = 0;

echo "<form method=post>";
Register_AutoUpdate('Generic', 0);
echo "<div class=tablecont><table id=indextable border>\n";
echo "<thead><tr>";
echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Index</a>\n";
echo "<th><a href=javascript:SortTable(" . $coln++ . ",'T')>Name</a>\n";
echo "<th><a href=javascript:SortTable(" . $coln++ . ",'N')>Props</a>\n";

echo "</thead><tbody>";
if ($ResTypes) foreach($ResTypes as $i=>$Res) {
  echo "<tr><td>$i" . fm_text1('',$Res,'Name',2,'',"ResourceTypes:Name:$i");
  echo fm_number1('',$Res,'Props',2,'',"ResourceTypes:Props:$i");
}
$es = [];
$NewResNames = NamesList($ResTypes);
Cancel_AutoUpdate();
echo "<tr><td><td colspan=2>New Track";
echo "<tr><td>" . fm_text1('',$es,'Name',2) . fm_number1('',$es,'Props') . fm_submit('ACTION','Add');

if (Access('God')) echo "<tr><td class=NotSide>Debug<td colspan=5 class=NotSide><textarea id=Debug></textarea>";

dotail();

