<?php

// Library of get/puts for common resources

// Factions

function Get_Faction($id) {
  global $db;
  $res = $db->query("SELECT * FROM Factions WHERE id=$id");
  if ($res) return $res->fetch_assoc();
  return [];
}

function Put_Faction(&$now) {
  $e=$now['id'];
  $Cur = Get_Faction($e);
  return Update_db('Factions',$Cur,$now);
}

// Systems

function Get_System($id) {
  global $db;
  $res = $db->query("SELECT * FROM Systems WHERE id=$id");
  if ($res) return $res->fetch_assoc();
  return [];
}

function Put_System(&$now) {
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_Faction($e);
    return Update_db('Systems',$Cur,$now);
  } else {
    return $now['id'] = Insert_db ('Systems', $now );  
  }
}

function Get_SystemR($sysref) {
  global $db,$GAMEID;
  $res = $db->query("SELECT * FROM Systems WHERE GameId=$GAMEID Ref='$sysref'");
  if ($res) return $res->fetch_assoc();
  return [];
}

function Get_Systems() {
  global $db,$GAMEID;
  $res = $db->query("SELECT * FROM Systems WHERE GameId=$GAMEID ORDER BY Ref");
  $Systms = [];
  if ($res) {
    while ($ans = $res->fetch_assoc()) { $Systms[$ans['Ref']] = $ans; }
    }
  return $Systms;
}

// Planets

function Get_Planet($id) {
  global $db;
  $res = $db->query("SELECT * FROM Planets WHERE id=$id");
  if ($res) return $res->fetch_assoc();
  return [];
}

function Put_Planet(&$now) {
  $e=$now['id'];
  $Cur = Get_Faction($e);
  return Update_db('Planets',$Cur,$now);
}

function Get_Planets($sysid) {
  global $db;
  $res = $db->query("SELECT * FROM Planets WHERE SystemId=$sysid");
  $Planets = [];
  if ($res) {
    while ($res->fetch_assoc()) { $Planets[] = $res; };
    }
  return $Planets;
}

// Links

function Get_Link($id) {
  global $db;
  $res = $db->query("SELECT * FROM Links WHERE id=$id");
  if ($res) return $res->fetch_assoc();
  return [];
}

function Put_Link(&$now) {
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_Faction($e);
    return Update_db('Links',$Cur,$now);
  } else {
    return $now['id'] = Insert_db ('Links', $now );  
  }

}

function Get_Links($sysref) {
  global $db;
  $res = $db->query("SELECT * FROM Links WHERE System1Ref='$sysref' OR System2Ref='$sysref'");
  $links = [];
  if ($res) {
    while ($ans = $res->fetch_assoc()) { $links[] = $ans; };
    }
  return $links;
}

// Messy not right avoid if poss
function Get_LinksAll($sysid) {
  global $db,$GAME;
  $res = $db->query("SELECT * FROM Links WHERE System1=$sysid OR System2=$sysid");
  $links = [];
  if ($res) {
    while ($ans = $res->fetch_assoc()) { $links[] = $ans; };
    }
  return $links;
}


// Link knnowledge

function Get_FactionLink($id) {
  global $db;
  $res = $db->query("SELECT * FROM FactionLink WHERE id=$id");
  if ($res) return $res->fetch_assoc();
  return [];
}

function Get_FactionLinkFL($Fact, $Lid) {
  global $db;
  $res = $db->query("SELECT * FROM FactionLink WHERE FactionId=$Fact AND LinkId=$id");
  if ($res) return $res->fetch_assoc();
  return ['FactionId'=>$Fact, 'LinkId'=>$id];
}

function Put_FactionLink(&$now) {
  if (isset($now[$id])) {
    $e=$now['id'];
    $Cur = Get_Faction($e);
    return Update_db('FactionLink',$Cur,$now);
  } else {
    return $now['id'] = Insert_db ('FactionLink', $now );
  }
}

// FactionsSystems

function Get_FactionSystem($id) {
  global $db;
  $res = $db->query("SELECT * FROM FactionSystem WHERE id=$id");
  if ($res) return $res->fetch_assoc();
  return [];
}

function Get_FactionSystemFS($Fact, $id) {
  global $db;
  $res = $db->query("SELECT * FROM FactionSystem WHERE FactionId=$Fact AND SystemId=$id");
  if ($res) return $res->fetch_assoc();
  return ['FactionId'=>$Fact, 'SystemId'=>$id];
}

function Put_FactionSystem(&$now) {
  if (isset($now[$id])) {
    $e=$now['id'];
    $Cur = Get_FactionSystem($e);
    return Update_db('FactionSystem',$Cur,$now);
  } else {
    return $now['id'] = Insert_db ('FactionSystem', $now );
  }
}

// Faction Planet

function Get_FactionPlanet($id) {
  global $db;
  $res = $db->query("SELECT * FROM FactionPlanet WHERE id=$id");
  if ($res) return $res->fetch_assoc();
}

function Get_FactionPlanetFS($Fact, $id) {
  global $db;
  $res = $db->query("SELECT * FROM FactionSystem WHERE FactionId=$Fact AND Planet=$id");
  if ($res) return $res->fetch_assoc();
  return ['FactionId'=>$Fact, 'Planet'=>$id];
}

function Put_FactionPlanet(&$now) {
  if (isset($now[$id])) {
    $e=$now['id'];
    $Cur = Get_Faction($e);
    return Update_db('FactionPlanet',$Cur,$now);
  } else {
    return $now['id'] = Insert_db ('FactionPlanet', $now );
  }  
}
// ??

function Get_LinkLevels() {
  global $db,$GAMEID;
  $Lvls = [];
  $res = $db->query("SELECT * FROM LinkLevel WHERE GameId=$GAMEID ORDER BY Level");
//  var_dump($res);
  if ($res) while ($ans = $res->fetch_assoc()) $Lvls[$ans['Level']] = $ans;
  return $Lvls;
}

//
function Put_Factionx(&$now) {
  $e=$now['id'];
  $Cur = Get_Faction($e);
  return Update_db('Faction',$Cur,$now);
}
// ??

function Get_Factionxx($id) {
  global $db;
  $res = $db->query("SELECT * FROM Faction WHERE id=$id");
  if ($res) return $res->fetch_assoc();
  return [];
}

function Put_Factionxx(&$now) {
  $e=$now['id'];
  $Cur = Get_Faction($e);
  return Update_db('Faction',$Cur,$now);
}


