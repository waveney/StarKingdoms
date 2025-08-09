<?php
include_once("sk.php");
include_once("GetPut.php");
include_once("ThingLib.php");
include_once("PlayerLib.php");
include_once("SystemLib.php");
include_once("ProjLib.php");
include_once("OrgLib.php");

global $FACTION,$ADDALL,$GAME,$ARMY,$GAMEID;

// Display matrix of possible images, select to set, return to selected page

//   echo "<td><a href=ImageSelect.php?t=Planet&i=$Pid&P='Bodies/$Pfx'&R=PlanEdit>Select New Image</a>";

$Type = $_REQUEST['t']??0;
$id = $_REQUEST['i']??0;
$ImgPfx = $_REQUEST['P']??'';
$ReturnPage = $_REQUEST['R']??'';
$fld = $_REQUEST['d']??'Image';

dostaffhead("Select Image");

if (!$Type || !$id || !$ImgPfx || !$ReturnPage) Error_Page("Image Select called with duff data");
$Recall = "t=$Type&i=$id&P=$ImgPfx&R=$ReturnPage&d=$fld";

if (isset($_REQUEST['ACTION'])) {
  switch ($_REQUEST['ACTION']) {
    case 'Select':
      $Thing = Gen_Get($Type,$id);
      $Thing['Image'] = $_REQUEST['s'];
      Gen_Put($Type,$Thing);
      echo "<h2>Image Updated</h2>";
      break;
  }
}

$Thing = Gen_Get($Type,$id);
if (!$Thing) {
  echo "<h1>Could not find $Type: $id</h1>";
  dotail();
}

echo "<h2>Current Image:</h2>";
echo "<img src=" . $Thing['Image'] . " height=200><p>";

// var_dump($ImgPfx);
$files = glob("$ImgPfx*");
$ImgC = -1;
echo "<table border>";
foreach ($files as $file) {
  if ((++$ImgC)%3 == 0) echo "<tr>";
  echo "<td><a href=SelectImage.php?ACTION=Select&$Recall&s=$file><img src=$file height=200></a>";
}
echo "</table><P><h1><a href=$ReturnPage.php?i=$id>Return</a></h1>";

dotail();