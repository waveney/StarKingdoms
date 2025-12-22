<?php

// Generic Images used for things without Images

  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("PlayerLib.php");
  include_once("ProjLib.php");

  global $FACTION,$GAME,$ARMY,$GAMEID,$PlayerState;
  A_Check('Player');
  $Fid = 0;

  if (Access('Player')) {
    if (!$FACTION) {
      if (!Access('GM')) {
        Error_Page("Sorry you need to be a GM or a Player to access this");
      } else {
      }
    } else {
      $Fid = $FACTION['id'];
      $Faction = &$FACTION;
    }
  }
  if ($Fid == 0 && Access('GM') ) {
    if (isset( $_REQUEST['F'])) {
      $Fid = $_REQUEST['F'];
    } else if (isset( $_REQUEST['f'])) {
      $Fid = $_REQUEST['f'];
    }
    if (isset($Fid)) $FACTION = $Faction = Get_Faction($Fid);
  }
//  prof_flag("Fid");

  $TTypes = Get_ThingTypes();
  $GenIms = Gen_Get_Cond('GenericImages',"FactionId=$Fid");
  $FTTypes = [];
  foreach ($GenIms as $TT) $FTTypes[$TT['Type']] = $TT;

  dostaffhead("Generic Images",["js/ProjectTools.js", "js/dropzone.js","css/dropzone.css" ]);


  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
      case 'Image':
        $TTi = $_REQUEST['i'];

        echo "<h1>Generic Image For:" . $TTypes[$TTi]['Name'] . "</h1>";
        $GImg = Gen_Get_Cond1('GenericImages', "FactionId=$Fid AND Type=$TTi");
        if (!$GImg) {
          $GImg = ['FactionId'=>$Fid,'Type'=>$TTi,'Image'=>''];
          Gen_Put('GenericImages',$GImg);
        }
        $Gi = $GImg['id'];
        echo "<table><tr><td rowspan=4 colspan=4><table><tr>";
        echo fm_DragonDrop(1,'Image','GenericImage',$Gi,$GImg,0,'',1,'','GenericImage');
        echo "</table>";

        fm_submit('Return','Return');
        dotail();

      case 'Delete':

      case 'Use':


    }
  }
//  prof_flag("b4head");


  echo "<h1>Generic Images</h1>If set, these will be used for things of the appropriate type without an image<p>";

  echo "<h2>Instructions</h2>" ; //
  //<ol><li>Upload/replace image THEN" .
  //  "<li>Click Propogate Image - this will make all existing things of that type that either have no image or the generic image have that image</ol>";

  $TTypesUsed = Gen_Select_Ordered("SELECT tt.* FROM ThingTypes tt LEFT JOIN Things t ON t.Type=tt.id WHERE t.Whose=$Fid");

  echo "<form method=post action=GenercImages.php>";

  TableStart();
  TableHead('Type','T');
  TableHead('Image','T');
  TableHead('Actions','T');

  foreach ($TTypesUsed as $Ti=>$TT) {
    echo "<tr><td>" . $TTypes[$Ti]['Name'];
    if (isset($FTTypes[$Ti])) {
      echo "<td><img src=" . $FTTypes[$Ti]['Image'] . " hieght=100><td><a href=GenericImages.php?ACTION=Image&i=$Ti>Replace Image<br>";
 //     echo "<a href=GenericImages.php?ACTION=Delete&i=$Ti>Delete Image<br>";
 //     echo "<a href=GenericImages.php?ACTION=Use&i=$Ti>Propogate Image";
    } else {
      echo "<td>No Image Yet<td><a href=GenericImages.php?ACTION=Image&i=$Ti>Upload Image<br>";
 //     echo "<a href=GenericImages.php?ACTION=Use&i=$Ti>Propogate Image";
    }
  }

  TableEnd();
  dotail();

