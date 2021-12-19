<?php 
// Set uploaded fields in data
include_once("sk.php");
include_once("skfm.php");
  include_once("GetPut.php");
  include_once("SystemLib.php");

function Archive_Stack($loc,$pth,$id) {
  if (!file_exists($loc)) return;
  $sfx = pathinfo($loc,PATHINFO_EXTENSION);
  $hist = 1;
  while (file_exists("$pth/Old$hist.$id.$sfx")) $hist++;
  rename($loc,"$pth/Old$hist.$id.$sfx");
}

  global $DDdata,$GAMEID;
//********************************* START HERE **************************************************************

$Type = $_REQUEST['Type'];
$id = $_REQUEST['Id'];
$Cat = $_REQUEST['Cat'];
$Mode = (isset($_REQUEST['Mode'])?$_REQUEST['Mode'] :0);
$Class = (isset($_REQUEST['Class'])?$_REQUEST['Class'] :"");
$DDd = $DDdata[$Type];
$Name = $Type;
if (isset($DDd['Name'])) $Name = $DDd['Name'];
$PathCat = $Cat;

switch ($Cat) {
case 'System':
case 'System2':
  $Data = Get_System($id);
  $Put = 'Put_System';
  break;

case 'Planet':
  $Data = Get_Planet($id);
  $Put = 'Put_Planet';
  break;

case 'Thing':
  $Data = Get_Thing($id);
  $Put = 'Put_Thing';
  break;

case 'Moon':
  $Data = Get_Moon($id);
  $Put = 'Put_Moon';
  break;

default:
  echo fm_DragonDrop(0,$Type,$Cat,$id,$Data,$Mode,"Unknown Data Category $Cat",1,'',$Class);
  exit;
}

if (!$Data) { 
  echo fm_DragonDrop(0,$Type,$Cat,$id,$Data,$Mode,"No Data found to update - $Type - $Cat - $Id ",1,'',$Class);
  exit;
}

//TODO paths bellow only work for per year data not fixed eg PA 

// Existing file?
if (isset($DDd['path'])) {
  if ($DDd['path'] == 'images')  {
    $pdir = "images/$GAMEID/$PathCat";
  } else {
    $pdir = $DDd['path'];
  }
  if ($DDd['UseGame'])  $pdir .= "/$GAMEID";
} else {
  $pdir = ($DDd['UseGame']?"$Type/$GAMEID/$Cat":$Type);
}
$path = "$pdir/$id";

$files = glob("$path.*");
if ($files) {
  Archive_Stack($files[0],$pdir,$id );
}

// New file

$target_dir = $pdir;
umask(0);
if (!file_exists($target_dir)) mkdir($target_dir,0775,true);

$suffix = pathinfo($_FILES["Upload"]["name"],PATHINFO_EXTENSION);
$target_file = "$target_dir/$id.$suffix";

if (!move_uploaded_file($_FILES["Upload"]["tmp_name"], $target_file)) {
  echo fm_DragonDrop(0,$Type,$Cat,$id,$Data,'',$Mode,1,"Uploaded file failed to be stored",1,'',$Class);
  exit;
}

if (is_numeric($DDd['SetValue'])) {
  $Data[$Type] = $DDd['SetValue']; //TODO PAspec fix DDd
} elseif ($DDd['SetValue'] == 'URL') {
  $Data[$Type] = "/" . $target_file . "?" . time();
} else {
  $Data[$Type] = $DDd['SetValue'];
}
$Put($Data);

if ($files) {
  $Mess = "The $Name file has been replaced by " . $_FILES["Upload"]["name"];
} else {
  $Mess = $_FILES["Upload"]["name"] . " has been stored as the $Name file";
}
$Mess .= ".  <br>Refresh the page if you wish to change it.";

echo fm_DragonDrop(0,$Type,$Cat,$id,$Data,$Mode,$Mess,1,'',$Class);

