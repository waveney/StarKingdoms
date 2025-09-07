<?php

// If table's index is 'id' it does not need to be listed here
$TableIndexes = array( );

function db_open () {
  global $db,$CONF;
  if (@ $CONF = parse_ini_file("Configuration.ini")) {
    @ $db = new mysqli($CONF['host'],$CONF['user'],$CONF['passwd'],$CONF['dbase']);
  } else {
    @ $db = new mysqli('localhost','sk','1L-D7aiwg1_jHggTPoiHTryuR52fpFAnz','sk');
    $CONF = ['dbase'=>'sk'];
  }
  if (!$db || $db->connect_error ) die ('Could not connect: ' .  $db->connect_error);
}

db_open();

function Gen_Get($Table,$id, $idx='id') {
  global $db;
  $res = $db->query("SELECT * FROM $Table WHERE $idx=$id");
  if ($res) {
    $ans = $res->fetch_assoc();
    if ($ans) return $ans;
  }
  return [];
}

function Gen_Put($Table, &$now, $idx='id',$Mon=0) {
  if (!empty($now[$idx])) {
    $e=$now[$idx];
    $Cur = Gen_Get($Table,$e,$idx);

    if ($Cur) return Update_db($Table,$Cur,$now);
  }
  if ($Mon) { debug_print_backtrace(); var_dump("ERROR New enrty that should be an update", $Table, $now); exit; }
  return $now[$idx] = Insert_db ($Table, $now );
}

function Gen_Put_Debug($Table, &$now, $idx='id',$Mon=1) {
  if (!empty($now[$idx])) {
    $e=$now[$idx];
    $Cur = Gen_Get($Table,$e,$idx);

    if ($Cur) return Update_db($Table,$Cur,$now);
  }
  if ($Mon) { debug_print_backtrace(); var_dump(" New enrty that should be an update", $Table, $now); exit; }
  return $now[$idx] = Insert_db ($Table, $now );
}

function Gen_Get_All($Table, $extra='', $idx='id') {
    global $db;
    $Ts = [];
    $res = $db->query("SELECT * FROM $Table $extra");
    if ($res) while ($ans = $res->fetch_assoc()) $Ts[$ans[$idx]] = $ans;
    return $Ts;
}

function Gen_Get_All_Game($Table, $idx='id') {
  global $db,$NOTBY;
  $Ts = [];
  $res = $db->query("SELECT * FROM $Table WHERE (NotBy=0 OR (NotBy&$NOTBY)!=0)");
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[$ans[$idx]] = $ans;
  return $Ts;
}

function Gen_Get_All_GameId($Table, $idx='id') {
  global $db,$GAME,$GAMEID;
  $Ts = [];
  $res = $db->query("SELECT * FROM $Table WHERE GameId=$GAMEID");
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[$ans[$idx]] = $ans;
  return $Ts;
}

function Gen_Get_Cond($Table,$Cond, $idx='id') {
  global $db;
  $Ts = [];
//    var_dump($Cond);
  $res = $db->query("SELECT * FROM $Table WHERE $Cond");
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[$ans[$idx]] = $ans;
  return $Ts;
}

function Gen_Get_Cond1($Table,$Cond) {
  global $db;
  $res = $db->query("SELECT * FROM $Table WHERE $Cond LIMIT 1");
  if ($res) {
    $ans = $res->fetch_assoc();
    if ($ans) return $ans;
  }
  return [];
}

function Gen_Select($Clause) {
  global $db;
  $Ts = [];
  $res = $db->query($Clause);
  if ($res && is_object($res)) while ($ans = $res->fetch_assoc()) $Ts[] = $ans;
  return $Ts;
}

function Gen_Get_Table($Table, $Cond='',$idx='id') {
  global $db,$GAME,$GAMEID;
  $Ts = [];
  $res = $db->query("SELECT * FROM $Table WHERE GameId=$GAMEID $Cond");
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[$ans[$idx]] = $ans;
  return $Ts;
}

function Gen_Get_Basic_Table($Table, $Cond='',$idx='id') {
  global $db,$GAME,$GAMEID;
  $Ts = [];
  $res = $db->query("SELECT * FROM $Table " . ($Cond? "WHERE $Cond" :""));
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[$ans[$idx]] = $ans;
  return $Ts;
}

function Gen_Get_Names($Table) {
  global $db;
  $Ts = [];
  $res = $db->query("SELECT id,Name FROM $Table");
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[$ans['id']] = $ans['Name'];
  return $Ts;
}

function Gen_Get_Names_Flip($Table) {
  global $db;
  $Ts = [];
  $res = $db->query("SELECT id,Name FROM $Table");
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[$ans['Name']] = $ans['id'];
  return $Ts;
}

function Logg($what) {
  global $db,$USERID;
  $qry = "INSERT INTO LogFile SET Who='$USERID', changed='" . date('d/m/y H:i:s') . "', What='" . addslashes($what) . "'";
  $db->query($qry);
}

function table_fields($table) {
  global $db,$CONF;
  static $tables = array();
  if (isset($tables[$table])) return $tables[$table];

  $qry = "SELECT COLUMN_NAME, DATA_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='" . $CONF['dbase'] ."' AND TABLE_NAME='" . $table . "'";
  $Flds = $db->query($qry);
  while ($Field = $Flds->fetch_array()) {
    $tables[$table][$Field['COLUMN_NAME']] = $Field['DATA_TYPE'];
  }
  return $tables[$table];
}



function Update_db($table,&$old,&$new,$proced=1) {
  global $db;
  global $TableIndexes;
  global $UpdateLog;

  $Flds = table_fields($table);
  $indxname = (isset($TableIndexes[$table])?$TableIndexes[$table]:'id');
//var_dump($indxname,$old);
  $newrec = "UPDATE $table SET ";
  $fcnt = 0;

/*echo "Fields:";
var_dump( $Flds); //OK
echo "<p>$newrec<p>";*/

  foreach ($Flds as $fname=>$ftype) {
    if ($indxname == $fname) { // Skip
    } elseif (isset($new[$fname])) {
      if ($ftype == 'text') {
        $dbform = addslashes($new[$fname]);
      } elseif ($ftype == 'tinyint' || $ftype == 'smallint') {
        $dbform = 0;
        if ($new[$fname]) {
          if ((string)(int)$new[$fname] = $new[$fname]) { $dbform = $new[$fname]; } else { $dbform = 1; }
        }
      } else {
        $dbform = $new[$fname];
      }

//echo "$fname " . $old[$fname] . " $dbform<br>";
      if (!isset($old[$fname]) || $dbform != $old[$fname]) {
        $old[$fname] = $dbform;
        if ($fcnt++ > 0) { $newrec .= " , "; }
        $newrec .= " $fname=" . '"' . $dbform . '"';
      }
    } else {
      if ($ftype == 'tinyint' || $ftype == 'smallint' ) {
//      if ($fname == 'InUse') debug_print_backtrace();
        if ($old[$fname]) {
          $old[$fname] = 0;
            if ($fcnt++ > 0) { $newrec .= " , "; }
          $newrec .= " $fname=0";
        }
      }
    }
  }

/*var_dump($old);
echo "$fcnt<p>";
debug_print_backtrace();
echo "<p>Here: $proced And: $fcnt<p>";*/
  if ($proced && $fcnt) {
    $newrec .= " WHERE $indxname=" . $old[$indxname];
//    var_dump($indxname,$newrec);
    $update = $db->query($newrec);
    $UpdateLog .= $newrec . "\n";
    if ($update) {
//      echo "<h2>$table Updated - $newrec</h2>\n";
//      echo "<h2>$table Updated</h2>\n";
    } else {
      echo "<h2 class=Err>An error occoured: ((($newrec))) " . $db->error . "</h2>";
    }
    return $update;
  }
}

function Update_db_post($table, &$data, $proced=1) {
  return Update_db($table,$data,$_POST,$proced);
}

function Insert_db($table, &$from, &$data=0, $proced=1) {
  global $db;
  global $TableIndexes;
  global $UpdateLog;
  $newrec = "INSERT INTO $table SET ";
  $fcnt = 0;
  $Flds = table_fields($table);
  $indxname = (isset($TableIndexes[$table])?$TableIndexes[$table]:'id');
//echo "<p>Fields: "; var_dump($Flds);
//echo "<p>From>: "; var_dump($from);
  foreach ($Flds as $fname=>$ftype) {
/* echo "<br> $fname $ftype <br>";
if (isset($from[$fname])) echo "T1 (" . $from[$fname] . ") ";
if ($from[$fname] != '') echo "T2 ";
if ($indxname!=$fname) echo "T3 "; */
    if (isset($from[$fname]) && $from[$fname] != '' && $indxname!=$fname ) {
      if ($fcnt++ > 0) { $newrec .= " , "; }
      if ($ftype == 'text' || $ftype == 'mediumtext') {
        $dbform = addslashes($from[$fname]);
        if ($data) $data[$fname] = $dbform;
        $newrec .= " $fname=" . '"' . $dbform . '"';
      } elseif ($ftype == "tinyint" || $ftype == 'smallint') {
        $dbform = 0;
        if ($from[$fname]) {
          if ((string)(int)$from[$fname] = $from[$fname]) { $dbform = $from[$fname]; } else { $dbform = 1; }
        }
        if ($data) $data[$fname] = $dbform;
        $newrec .= " $fname=$dbform ";
      } else {
        if ($data) $data[$fname] = $from[$fname];
        $newrec .= " $fname=$from[$fname] ";
      }
    }
  }
// var_dump($from,$newrec);exit;
  if ($proced) {
    $insert = $db->query($newrec);
    if ($insert) {
      $UpdateLog .= $newrec . "\n";
      $snum = $db->insert_id;
//      echo "<h2>$table New entry - $newrec - $snum</h2>";
//      echo "<h2>$table New entry added</h2>";
      if ($data) $data[$indxname]=$snum;
      $from[$indxname]=$snum;
      return $snum;
    } else {
      echo "<h2 class=Err>An error occoured: ((($newrec))) " . $db->error . "</h2>";
    }
  }
  return 0;
}

function Insert_db_post($table,&$data,$proced=1) {
  $data['Dummy'] = 1;
  return Insert_db($table,$_POST,$data,$proced);
}

function db_delete($table,$entry) {
  global $db,$TableIndexes;
  $indxname = (isset($TableIndexes[$table])?$TableIndexes[$table]:'id');
//echo "DELETE FROM $table WHERE $indxname='$entry'<p>";
  return $db->query("DELETE FROM $table WHERE $indxname='$entry'");
}

function db_delete_cond($table,$cond) {
  global $db;
  return $db->query("DELETE FROM $table WHERE $cond");
}

function db_update($table,$what,$where) {
  global $db;
  return $db->query("UPDATE $table SET $what WHERE $where");
}

function db_get($table,$cond) {
  global $db;
  $res = $db->query("SELECT * FROM $table WHERE $cond");
  if ($res) return $res->fetch_assoc();
  return 0;
}

// Read YEARDATA Data - this is NOT year specific - Get fest name, short name, version everything else is for future

function Get_GAMESYS() {
  global $db,$GAMESYS;
  $res = $db->query("SELECT * FROM MasterData");
  $GAMESYS = $res->fetch_assoc();
  if ($res) return $GAMESYS;
}

global $VERSION;
$GAMESYS = Get_GAMESYS();
$GAME = $GAMESYS['CurGame'];  //$GAME can be overridden
include_once("Version.php");
$GAMESYS['V'] = $VERSION;

$_GameFeatures = $_Features = [];

function Feature($Name,$default='') {  // Return value of feature if set Game data value overrides system value
  global $GAMESYS,$GAME,$_GameFeatures,$_Features;
  if (!$_Features) {
    $_Features = parse_ini_string($GAMESYS['Features']?? '');
    $_GameFeatures = parse_ini_string($GAME['Features'] ?? '');
  }
  return $_GameFeatures[$Name] ?? ($_Features[$Name] ?? $default);
}

function Feature_Reset() {
  global $_GameFeatures,$GAME,$_Features,$GAMESYS;
  $_GameFeatures = parse_ini_string($GAME['GameFeatures'] ?? '');
  $_Features = parse_ini_string($GAMESYS['Features']?? '');
}


function GameFeature($Name,$default='') {
  return Feature($Name,$default);
}

function Capability($Name,$default='') {  // Return value of Capability if set from GAMESYS
  static $Capabilities;
  global $GAMESYS;
  if (!$Capabilities) {
    $Capabilities = [];
    foreach (explode("\n",$GAMESYS['Capabilities']) as $i=>$Cape) {
      $Dat = explode(":",$Cape,3);
      if ($Dat[0])$Capabilities[$Dat[0]] = trim($Dat[1]);
    }
  }
  if (isset($Capabilities[$Name])) return $Capabilities[$Name];
  return $default;
}

function set_ShowGame($last=0) { // Overrides default above if not set by a Y argument
  global $GAME;
}

// Works for simple tables
// Deletes = 0 none, 1=one, 2=many
function UpdateMany($table,$Putfn,&$data,$Deletes=1,$Dateflds='',$Timeflds='',$Mstr='Name',$MstrNot='',$Hexflds='',$MstrChk='',$Sep='') {
  global $TableIndexes,$GAMEID;
  include_once("DateTime.php");
  $Flds = table_fields($table);
  $DateFlds = explode(',',$Dateflds);
  $TimeFlds = explode(',',$Timeflds);
  $HexFlds = explode(',',$Hexflds);
  $indxname = ($TableIndexes[$table]??'id');

// var_dump($Sep,$Mstr,$MstrNot,$Hexflds,$MstrChk);
//return;
  if (isset($_POST['Update'])) {
    $Pfx = '';
    $mtch = [];
    foreach ($_REQUEST as $R=>$V) {
      if (preg_match("/$table:(\w*):(\d*)/",$R,$mtch)){
        $Pfx = "$table:";
        $Sep = ':';
        break;
      } else if (preg_match("/$Mstr$Sep(\d*)/",$R,$mtch)) {
        break;
      }
    }
    if ($data) foreach($data as $t) {
      $i = $t[$indxname];
      if ($i) {
        if (isset($_POST["$Pfx$Mstr$Sep$i"]) && $_POST["$Pfx$Mstr$Sep$i"] == $MstrNot) {
          if ($Deletes) {
            if (!empty($MstrChk) && !isset($_POST["$Pfx$MstrChk$Sep$i"])) continue;
//          echo "Would delete " . $t[$indxname] . "<br>";
              db_delete($table,$t[$indxname]);
            if ($Deletes == 1) return 1;
          }
          continue;
        } else {
          $recpres = 0;
          foreach ($Flds as $fld=>$ftyp) {
            // var_dump($fld,$ftyp);
            if ($fld == $indxname) continue;
            if (in_array($fld,$DateFlds)) {
              $t[$fld] = Date_BestGuess($_POST["$Pfx$fld$Sep$i"]);
              $recpres = 1;
            } else if (in_array($fld,$TimeFlds)) {
              $t[$fld] = Time_BestGuess($_POST["$Pfx$fld$Sep$i"]);
              $recpres = 1;
            } else if (in_array($fld,$HexFlds)) {
              $t[$fld] = hexdec($_POST["$Pfx$fld$Sep$i"]);
              $recpres = 1;
            } else if (isset($_POST["$Pfx$fld$Sep$i"])) {
              $t[$fld] = $_POST["$Pfx$fld$Sep$i"];
              $recpres = 1;
            } else {
              $t[$fld] = 0;
            }
          }
//          var_dump($recpres,$t);exit;
//          return;
          if ($recpres) {
            if ($Putfn) {
              $Putfn($t);
            } else {
              Gen_Put($table,$t);
            }
          }
        }
      }
    }

    if (isset($_POST["$Pfx$Mstr$Sep" . "0"] ) && $_POST["$Pfx$Mstr$Sep" . "0"] != $MstrNot) {
      $t = array();
      foreach ($Flds as $fld=>$ftyp) {
        if ($fld == $indxname) continue;
        $Look = "$Pfx$fld$Sep" . "0";
        if (isset($_POST[$Look])) {
          if (in_array($fld,$DateFlds)) {
            $t[$fld] = Date_BestGuess($_POST[$Look]);
          } else if (in_array($fld,$TimeFlds)) {
            $t[$fld] = Time_BestGuess($_POST[$Look]);
          } else if (in_array($fld,$HexFlds)) {
            $t[$fld] = hexdec($_POST[$Look]);
          } else {
            $t[$fld] = $_POST[$Look];
          }
        }
      }
      $t['GameId'] = $GAMEID;
//      var_dump("$Pfx$fld$Sep" . "0", $HexFlds,$t);
      Insert_db($table,$t);
    } else if (isset($_POST["$Pfx$Mstr$Sep" . "0"] ) && $_POST["$Pfx$Mstr$Sep" . "0"] != $MstrNot) {
      $t = array();
      foreach ($Flds as $fld=>$ftyp) {
        if ($fld == $indxname) continue;
        if (isset($_POST["$Pfx$fld$Sep" . "0"])) {
          if (in_array($fld,$DateFlds)) {
            $t[$fld] = Date_BestGuess($_POST["$Pfx$fld$Sep" . "0"]);
          } else if (in_array($fld,$TimeFlds)) {
            $t[$fld] = Time_BestGuess($_POST["$Pfx$fld$Sep" . "0"]);
          } else if (in_array($fld,$HexFlds)) {
            $t[$fld] = hexdec($_POST["$Pfx$fld$Sep" . "0"]);
          } else {
            $t[$fld] = $_POST["$Pfx$fld$Sep" . "0"];
          }
        }
      }
      $t['GameId'] = $GAMEID;
      //      var_dump($t);
      Insert_db($table,$t);
    }

    return 1;
  }
}

