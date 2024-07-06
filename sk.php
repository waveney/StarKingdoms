<?php

/* Various common code across fest con tools */

  include_once("skdb.php");
  include_once("skfm.php");
  include_once("GetPut.php");

  $BUTTON = 0;

if (isset($_REQUEST['Y'])) $YEAR = $_REQUEST['Y'];
if (isset($_REQUEST['B'])) $BUTTON = ($_REQUEST['B']+1) % 4;

if (isset($YEAR)) {
  if (strlen($YEAR)>10) exit("Invalid Year");
}

$Access_Levels = ['','Participant','Observer','Player','GM','God','SysAdmin','Internal'];
$Access_Type = array_flip($Access_Levels);

$Months = ['','Jan','Feb','Mar','Apr','May','June','July','Aug','Sep','Oct','Nov','Dec'];
$GameStatus = ['Planning','In Setup','Active','Historical'];
$PlayerLevel = ['Player','GM','No Access'];


date_default_timezone_set('GMT');
function Check_Login() {
  global $db,$USER,$USERID,$AccessType,$YEAR,$CALYEAR,$FACTION;
  if (!empty($USER)) return true;
  if (isset($_COOKIE['SKC2'])) {
    $res=$db->query("SELECT * FROM People WHERE Yale='" . $_COOKIE['SKC2'] . "'");
    if ($res) {
      $USER = $res->fetch_assoc();
      $USERID = $USER['id'];
      $db->query("UPDATE People SET LastAccess='" . time() . "' WHERE id=$USERID" );
// Track suspicious things
      if (($USER['LogUse']) && (file_exists("LogFiles"))) {
        $logf = fopen("LogFiles/U$USERID.txt",'a+');
        if ($logf) {
          fwrite($logf,date('d/m H:j:s '));
          fwrite($logf,$_SERVER['PHP_SELF']);
          fwrite($logf,json_encode($_REQUEST));
          if ($_COOKIE) fwrite($logf,json_encode($_COOKIE));
          if ($_FILES) fwrite($logf,json_encode($_FILES));
          fwrite($logf,"\n\n");
        }
      }
      return true;
    }
  }
  include_once("Login.php");
  Login("Please Login to Star Kingdoms");

  return false;
}

function Set_Faction() {
  global $FACTION,$FID,$GAME,$GAMEID,$USERID,$USER,$Access_Type ;
  if (isset($FID)) return;
  Check_Login();

  $FACTION = [];
  $FID = 0;

  // Set Game
  if (isset($_COOKIE['SKG'])) {
    $gid = $_COOKIE['SKG'];
    if ($gid != $GAMEID) {
      Get_Game($gid);
    }
  } else {
    if ($USER['AccessLevel'] >= $Access_Type['God']) return;
    include_once("StarKingdoms.php");
  }

  if (isset($_COOKIE['SKF'])) {
    $FID = $_COOKIE['SKF'];
    $FACTION = Get_Faction($FID);
  } else {
    if ($USER['AccessLevel'] >= $Access_Type['God']) return;
    include_once("StarKingdoms.php");
  }

  $Person = Gen_Get_Cond1('GamePlayers', "GameId=$GAMEID AND PlayerId=$USERID");
  if (empty($Person)) {
    if ($USER['AccessLevel'] < $Access_Type['God']) include_once("StarKingdoms.php");
    $GAME['FactionLevel'] = 0;
  } if ($Person['Type'] == 2) { // No Access
    Error_Page('You do not have access to this game - sorry');
  } else {
    $GAME['FactionLevel'] = ($Person['Type']??0);
  }
}

function Access($level,$subtype=0,$thing=0) { // VERY different from fest code now
  global $Access_Type,$USER,$USERID,$GAMEID,$FACTION,$FID,$GAME;
  Check_Login();
  if ($USER['AccessLevel'] >= $Access_Type['God']) return 1;

  Set_Faction();
//  echo "XXX $level";

  switch ($level) {
  case 'Player':
    return ($FACTION?1:0);

  case 'GM':
    return ($GAME['FactionLevel']??0);

  case 'God':
  case 'SysAdmin':
  case 'Internal':
    return 1;

  default:
    return 0;
  }
}


/*
  If not in session
    If Yale then
      Find User from Yale, start session
      if not found - Login page
    else
      Login page
  endif
  if AccessOK return
  else isuf priv page
*/

function A_Check($level,$subtype=0,$thing=0) {
  if (Access($level,$subtype,$thing)) return;
  Error_Page("Insufficient Privilages");
}

function UserSetPref($pref,$val) {
  global $USER,$USERID;
  Set_Faction();
  if (!$USERID) return; // No user
  $Prefs = $USER['Prefs'];
  if (!($NewPrefs = preg_replace("/$pref\:.*\n/","$pref:$val\n",$Prefs))) $NewPrefs = $Prefs .  "$pref:$val\n";
  $USER['Prefs'] = $NewPrefs;
  Put_User($USER);
}

function UserGetPref($pref) {
  global $USER,$USERID;
  if (!$USER || !isset($USER['Prefs'])) return 0;
  $rslt = [];
  if (preg_match("/$pref\:(.*)\n/",$USER['Prefs'],$rslt)) return trim($rslt[1]);
  return 0;
}


function rand_string($len) {
  $ans= '';
  while($len--) $ans .= chr(rand(65,90));
  return $ans;
}


function Get_User($who,&$newdata=0) {
  global $db;
  static $Save_User;

  if (isset($Save_User[$who])) {
    $ret = $Save_User[$who];
    if ($newdata) $Save_User[$who] = $newdata;
    return $ret;
  }
  $qry = "SELECT * FROM People WHERE Login='$who' OR Email='$who' OR id='$who'";

  $res = $db->query($qry);

  if (!$res || $res->num_rows == 0) return 0;
  $data = $res->fetch_assoc();
  $Save_User[$who] = ($newdata ? $newdata : $data);
  return $data;
}

function Put_User(&$data,$Save_User=0) {
//echo "Put User called: $Save_User<p>";
//var_dump($data);
  if (!$Save_User) $Save_User = Get_User($data['id'],$data);
  return Update_db('People',$Save_User,$data);
}

function Error_Page ($message) {
  global $Access_Type,$USER,$USERID;
  Check_Login();
  if (isset($USER['AccessLevel'])) { $type = $USER['AccessLevel']; } else { $type = 0; }
//  var_dump($USER);
//  echo "$type<p>";
  switch ($type) {
  case $Access_Type['Player'] :
    include_once("Staff.php");
    exit;

  case $Access_Type['GM'] :
  case $Access_Type['SysAdmin'] :
  case $Access_Type['God'] :
    $ErrorMessage = "Something went very wrong... - $message";
    include_once('Staff.php');  // Should be good
    exit;                        // Just in case

  default:
    include_once("Staff.php");
  }
}

function Get_Game($y=0) {
  global $db,$GAME,$GAMESYS,$GAMEID,$NOTBY,$SETNOT;
  if (!$y) $y=$GAMESYS['CurGame'];
  $res = $db->query("SELECT * FROM Games WHERE id='$y'");
  if ($res) {
    $GAME = $res->fetch_assoc();
    $GAMEID = $GAME['id'];
    $SETNOT = (Feature('NotByMask',0)+0);
    $NOTBY = ~$SETNOT;
  } else {
    Error_Page("Game - $y not known");
  }
}

Get_Game();

function First_Sent($stuff) {
  $onefifty=substr($stuff,0,150);
  $m = '';
  return (preg_match('/^(.*?[.!?])\s/s',$onefifty,$m) ? $m[1] : $onefifty);
}

function munge_array(&$thing) {
  if (isset($thing) && is_array($thing)) return $thing;
  return [];
}

function Send_SysAdmin_Email($Subject,&$data=0) {
  include_once("OldCode/Email.php");
  $dat = json_encode($data);
  NewSendEmail(0,0,'richard@wavwebs.com',$Subject,$dat);
}

$head_done = 0;
$AdvancedHeaders = file_exists(dirname(__FILE__) . "/files/Newnavigation.php");

function doextras($extras) {
  global $GAMESYS;
  $V=$GAMESYS['V'];
  if ($extras) foreach ($extras as $e) {
    $suffix=pathinfo($e,PATHINFO_EXTENSION);
    if ($suffix == "js") {
      echo "<script src=$e?V=$V></script>\n";
    } else if ($suffix == 'jsdefer') {
      $e = preg_replace('/jsdefer$/','js',$e);
      echo "<script defer src=$e?V=$V></script>\n";
    } else if ($suffix == "css") {
      echo "<link href=$e?V=$V type=text/css rel=stylesheet>\n";
    }
  }
}

// If Banner is a simple image then treated as a basic banner image with title overlaid otherwise what is passed is used as is
function dohead($title,$extras=[],$Banner='',$BannerOptions=' ') {
  global $head_done,$GAMESYS,$CONF,$AdvancedHeaders, $GAME;

  if ($head_done) return;
  $V=$GAMESYS['V'];
  $pfx="";
  if (isset($CONF['TitlePrefix'])) $pfx = $CONF['TitlePrefix'];
  echo "<html><head>";
  echo "<title>$pfx " . (isset($GAME['Name'])?$GAME['Name']:"Star Kingdoms" )  . " | $title</title>\n";
  if ($AdvancedHeaders) {
    include_once("files/Newheader.php");
  } else {
    include_once("skfiles/header.php");
  }
  if ($extras) doextras($extras);
  echo "</head><body>\n";

  if ($AdvancedHeaders) {
    echo "<div class=contentlim>";
    include_once("files/Newnavigation.php");

    if ($Banner) {
      if ($Banner == 1) {
        echo "<div class=WMFFBanner400><img src=" . $GAMESYS['DefaultPageBanner'] . " class=WMFFBannerDefault>";
        echo "<div class=WMFFBannerText>$title</div>";
        if (!strchr('T',$BannerOptions)) echo "<img src=/images/icons/torn-top.png class=TornTopEdge>";
        echo "</div>";
      } else if (preg_match('/^(https?:\/\/|\/?images\/)/',$Banner)) {
        echo "<div class=WMFFBanner400><img src=$Banner class=WMFFBannerDefault>";
        echo "<div class=WMFFBannerText>$title</div>";
        if (!strstr($BannerOptions,'T')) echo "<img src=/images/icons/torn-top.png class=TornTopEdge>";
        echo "</div>";
      } else {
        echo $Banner;
      }
    } else {
      echo "<div class='NullBanner'></div>";  // Not shure this is needed
    }
  } else {
    echo "<div class=contentlim>";
    include_once("skfiles/navigation.php");
  }
  echo "<div class=mainwrapper><div class=maincontent>";
  $head_done = 1;
}

//  No Banner
function doheadpart($title,$extras=[]) {
  global $head_done,$GAMESYS,$CONF,$AdvancedHeaders,$GAME;
  if ($head_done) return;
  $V=$GAMESYS['V'];
  $pfx="";
  if (isset($CONF['TitlePrefix'])) $pfx = $CONF['TitlePrefix'];
  echo "<html><head>";
  echo "<title>$pfx " . $GAME['Name'] . " | $title</title>\n";
  if ($AdvancedHeaders) {
    include_once("files/Newheader.php");
  } else {
    include_once("skfiles/header.php");
  }

  if ($extras) doextras($extras);
  $head_done = 1;
}

// No Banner
function dostaffhead($title,$extras=[],$bodyextra='') {
  global $head_done,$GAMESYS,$CONF,$AdvancedHeaders,$GAME,$FACTION;

  LogEverything();

  if ($head_done) return;
  $V=$GAMESYS['V'];
  $pfx="";
  if (isset($CONF['TitlePrefix'])) $pfx = $CONF['TitlePrefix'];

  echo "<html><head>";
  echo "<title>$pfx " . $GAME['Name'] . " | $title</title>\n";
  if ($AdvancedHeaders && Feature('NewStyle') && ! UserGetPref('StaffOldFormat')) {
    include_once("files/Newheader.php");
    include_once("skgame.php");
    if ($extras) doextras($extras);
    echo "<meta http-equiv='cache-control' content=no-cache>";
    echo "</head><body>\n";
    include_once("files/Newnavigation.php");
    echo "<div class=content>";
  } else {
    include_once("skfiles/header.php");
    include_once("skgame.php");
    if ($extras) doextras($extras);
    echo "<meta http-equiv='cache-control' content=no-cache>";
    echo "</head><body $bodyextra>\n";

    include_once("skfiles/navigation.php");

    echo "<div class=content>";
  }
  $head_done = 1;

  if (isset($FACTION['TurnState']) && $FACTION['TurnState'] >1 ) {
    if (($FACTION['TurnState'] == 4 ) || (!Access('GM'))) fm_addall('readonly');
  }
}

// No Banner
function dominimalhead($title,$extras=[]) {
  global $head_done,$GAMESYS,$CONF,$AdvancedHeaders,$GAME;
  $V=$GAMESYS['V'];
  $pfx="";
  if (isset($CONF['TitlePrefix'])) $pfx = $CONF['TitlePrefix'];
  echo "<html><head>";
  echo "<title>$pfx " . $GAME['Name'] . " | $title</title>\n";
//  echo "<link href=files/Newstyle.css?V=$V type=text/css rel=stylesheet>";
  echo "<script src=/js/jquery-3.2.1.min.js></script>";
  if ($extras) doextras($extras);
//  if ($GAMESYS['Analytics']) echo "<script>" . $GAMESYS['Analytics'] . "</script>";
  echo "</head><body>\n";
  $head_done = 2;
}

function dotail() {
  global $head_done,$AdvancedHeaders;

  echo "</div>";
  if ($head_done == 1) {
    if ($AdvancedHeaders) {
      include_once("files/Newfooter.php"); // Not minimal head
    } else {
      include_once("skfiles/footer.php"); // Not minimal head
    }
  }
  echo "</body></html>\n";
  exit;
}

function Swap(&$a,&$b) {
  $c = $a;
  $a = $b;
  $b = $c;
}


// Short term log of everything
function LogEverything($Msg='') {
  global $FACTION,$USER,$GAMEID;
  $req_dump = date("D d H:i:s ") . $_SERVER['SCRIPT_FILENAME'] . " " . print_r($_REQUEST, TRUE) . print_r($_COOKIE, TRUE);
  if (isset($FACTION['Name'])) $req_dump .= "Faction: " . $FACTION['Name'] . "\n";
  if (isset($USER['Login'])) $req_dump .= "GM: " . $USER['Login'] . "\n";

  $fp = fopen('cache/' . ($GAMEID??'0') . '/request.log', 'a');
  fwrite($fp, $req_dump);
  fclose($fp);
}

// LogEverything();

?>
