<?php
  include_once("sk.php");
  include_once("GetPut.php");

  A_Check('GM');


  global $GAME,$GAMEID;

  if (isset($_REQUEST['ACTION'])) {
    switch ($_REQUEST['ACTION']) {
      case 'Show':
        $Only = $Insta = [];
        foreach ($_REQUEST as $r => $v) {
          if (str_contains($r,'Conceal')) $Only[] = $v;
          if (str_contains($r,'Insta')) $Insta[] = $v;
        }

//        var_dump($Only,$Insta);
        if ($Only) $_REQUEST['ONLY'] = implode(',',$Only);
        if ($Insta) $_REQUEST['INSTA'] = implode(',',$Insta);
        $_REQUEST['Hex'] = 'Hex';
        $_REQUEST['Links'] = (($_REQUEST['ShowLinks']??'')=='on'?1:0);
//        var_dump($_REQUEST); exit;
        include("MapFull.php"); // Shouldn't return
    }
  }

  dostaffhead("Select Map");

  echo "Select the properties you want and click Show.<p>If you don't select any Concealment/Instability levels it will show all<p>";

  echo "<form method=post Action=MapSelect.php?ACTION=Show>";
  $C = $S = $I = [];
  $S['Conceal'] = $S['Insta'] = -1;
  $S['ShowLinks'] = 0;
  for ($i=0; $i<7; $i++) $C[$i] = "C$i";
  echo fm_radio("Concealment",$C,$S,'Conceal','',0,'','',0,1) . "<p>";

  for ($i=0; $i<20; $i++) $I[$i] = "I$i";
  echo fm_radio("Instability",$I,$S,'Insta','',0,'','',0,1) . "<p>";

  echo fm_checkbox('Show Link Names',$S,'ShowLinks') . "<p>";

  echo fm_submit('ACTION','Show');
  dotail();

