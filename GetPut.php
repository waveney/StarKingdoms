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

function Get_Factions() {
  global $db,$GAMEID;
  $res = $db->query("SELECT * FROM Factions WHERE GameId=$GAMEID ORDER BY id ");
  $F = [];
  if ($res) {
    while ($ans = $res->fetch_assoc()) { $F[$ans['id']] = $ans; }
    }
  return $F;  
}

function Get_Faction_Names() {
  global $db,$GAMEID;
  $res = $db->query("SELECT * FROM Factions WHERE GameId=$GAMEID ORDER BY id ");
  $F = [];
  $F[0] = "None";
  if ($res) {
    while ($ans = $res->fetch_assoc()) { $F[$ans['id']] = $ans['Name']; }
    }
  return $F;  
}

function Get_Faction_Colours() {
  global $db,$GAMEID;
  $res = $db->query("SELECT * FROM Factions WHERE GameId=$GAMEID ORDER BY id ");
  $F = [];
  $F[0] = "white";
  if ($res) {
    while ($ans = $res->fetch_assoc()) { $F[$ans['id']] = $ans['MapColour']; }
    }
  return $F;  
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
    $Cur = Get_System($e);
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
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_Planet($e);
    return Update_db('Planets',$Cur,$now);
  } else {
    return $now['id'] = Insert_db ('Planets', $now );  
  }
}

function Get_Planets($sysid) {
  global $db;
  $res = $db->query("SELECT * FROM Planets WHERE SystemId=$sysid ORDER BY OrbitalRadius");
  $Planets = [];
  if ($res) {
    while ($ans = $res->fetch_assoc()) { $Planets[] = $ans; };
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
//var_dump($now); exit;
    return $now['id'] = Insert_db ('Links', $now );  
  }

}

function Get_Links($sysref) {
  global $db,$GAMEID;
  $res = $db->query("SELECT * FROM Links WHERE GameId=$GAMEID AND (System1Ref='$sysref' OR System2Ref='$sysref')");
  $links = [];
  if ($res) {
    while ($ans = $res->fetch_assoc()) { $links[] = $ans; };
    }
  return $links;
}

function Get_Links1end($sysref) {
  global $db,$GAMEID;
  $res = $db->query("SELECT * FROM Links WHERE GameId=$GAMEID AND System1Ref='$sysref'");
  $links = [];
  if ($res) {
    while ($ans = $res->fetch_assoc()) { $links[] = $ans; };
    }
  return $links;
}

// Messy not right avoid if poss
function Get_LinksAll($sysid) {
  global $db,$GAMEID;
  $res = $db->query("SELECT * FROM Links WHERE GameId=$GAMEID AND (System1=$sysid OR System2=$sysid)");
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
  $res = $db->query("SELECT * FROM FactionLink WHERE FactionId=$Fact AND LinkId=$Lid");
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

// District Types

function Get_DistrictType($id) {
  global $db;
  $res = $db->query("SELECT * FROM DistrictTypes WHERE id=$id");
  if ($res) return $res->fetch_assoc();
  return [];
}

function Put_DistrictType(&$now) {
  $e=$now['id'];
  $Cur = Get_DistrictType($e);
  return Update_db('DistrictTypes',$Cur,$now);
}

function Get_DistrictTypes() {
  global $db,$GAMEID;
  $Ts = [];
  $res = $db->query("SELECT * FROM DistrictTypes");
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[] = $ans;
  return $Ts;
}

function Get_DistrictTypeNames() {
  global $db,$GAMEID;
  $Ts = [];
  $res = $db->query("SELECT * FROM DistrictTypes");
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[$ans['id']] = $ans['Name'];
  return $Ts;
}

// Districts

function Get_District($id) {
  global $db;
  $res = $db->query("SELECT * FROM Districts WHERE id=$id");
  if ($res) return $res->fetch_assoc();
  return [];
}

function Put_District(&$now) {
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_District($e);
    return Update_db('Districts',$Cur,$now);
  } else {
    return $now['id'] = Insert_db ('Districts', $now );
  }  
}

function Get_Districts($Pid) {
  global $db,$GAMEID;
  $Ts = [];
  $res = $db->query("SELECT * FROM Districts WHERE PlanetId=$Pid");
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[] = $ans;
  return $Ts;
}

//  Planet Types

function Get_PlanetType($id) {
  global $db;
  $res = $db->query("SELECT * FROM PlanetTypes WHERE id=$id");
  if ($res) return $res->fetch_assoc();
  return [];
}

function Put_PlanetType(&$now) {
  $e=$now['id'];
  $Cur = Get_PlanetType($e);
  return Update_db('PlanetTypes',$Cur,$now);
}

function Get_PlanetTypes() {
  global $db,$GAMEID;
  $Ts = [];
  $res = $db->query("SELECT * FROM PlanetTypes");
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[$ans['id']] = $ans;
  return $Ts;
}

function Get_PlanetTypeNames() {
  global $db,$GAMEID;
  $Ts = [];
  $res = $db->query("SELECT * FROM PlanetTypes");
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[$ans['id']] = $ans['Name'];
  return $Ts;
}

//  ???

function Get_Factionxxx($id) {
  global $db;
  $res = $db->query("SELECT * FROM Faction WHERE id=$id");
  if ($res) return $res->fetch_assoc();
  return [];
}

function Put_Factionxxx(&$now) {
  $e=$now['id'];
  $Cur = Get_Faction($e);
  return Update_db('Faction',$Cur,$now);
}
function Get_Factionxvx($id) {
  global $db;
  $res = $db->query("SELECT * FROM Faction WHERE id=$id");
  if ($res) return $res->fetch_assoc();
  return [];
}

function Put_Factionxvx(&$now) {
  $e=$now['id'];
  $Cur = Get_Faction($e);
  return Update_db('Faction',$Cur,$now);
}



