<?php

// Library of get/puts for common resources

// Factions

function Get_Faction($id) {
  global $db;
  $res = $db->query("SELECT * FROM Factions WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Put_Faction(&$now) {
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_Faction($e);
    return Update_db('Factions',$Cur,$now);
  } else {
    return $now['id'] = Insert_db ('Factions', $now );
  }
}

function Get_Factions($Force=0,$AllG=0) {
  global $db,$GAMEID;
  static $F;
  if ($F && !$Force) return $F;
  $res = $db->query("SELECT * FROM Factions " . ($AllG?'':"WHERE GameId=$GAMEID ") . " ORDER BY id ");
  $F = [];
  if ($res) {
    while ($ans = $res->fetch_assoc()) { $F[$ans['id']] = $ans; }
    }
  return $F;
}

function Get_Faction_Names($None=1) {
  global $db,$GAMEID;
  $res = $db->query("SELECT * FROM Factions WHERE GameId=$GAMEID ORDER BY id ");
  $F = [];
  if ($None) $F[0] = "None";
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
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
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
  $res = $db->query("SELECT * FROM Systems WHERE GameId=$GAMEID AND Ref='$sysref'");
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

function Get_SystemsById() {
  global $db,$GAMEID;
  $res = $db->query("SELECT * FROM Systems WHERE GameId=$GAMEID ORDER BY Ref");
  $Systms = [];
  if ($res) {
    while ($ans = $res->fetch_assoc()) { $Systms[$ans['id']] = $ans; }
  }
  return $Systms;
}

function Get_SystemRefs() {
  global $db,$GAMEID;
  $res = $db->query("SELECT id,Ref FROM Systems WHERE GameId=$GAMEID ORDER BY Ref");
  $Systms = [];
  if ($res) {
    while ($ans = $res->fetch_assoc()) { $Systms[$ans['id']] = $ans['Ref']; }
    }
  return $Systms;
}

// Planets

function Get_Planet($id) {
  global $db;
  $res = $db->query("SELECT * FROM Planets WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Put_Planet(&$now) {
  global $GAMEID;
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_Planet($e);
    return Update_db('Planets',$Cur,$now);
  } else {
    $now['GameId'] = $GAMEID;
    return $now['id'] = Insert_db ('Planets', $now );
  }
}

function Get_Planets($sysid) {
  global $db;
  $res = $db->query("SELECT * FROM Planets WHERE SystemId=$sysid ORDER BY OrbitalRadius");
  $Planets = [];
  if ($res) {
    while ($ans = $res->fetch_assoc()) { $Planets[$ans['id']] = $ans; };
    }
  return $Planets;
}

/// Links

function Get_Link($id) {
  global $db;
  $res = $db->query("SELECT * FROM Links WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Put_Link(&$now) {
  global $db,$GAMEID;
  if (empty($now['Level'])) $now['Level'] = ($now['Concealment'] ?? 1);
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_Link($e);
    return Update_db('Links',$Cur,$now);
  } else {
    $now['GameId']=$GAMEID;
    return $now['id'] = Insert_db ('Links', $now );
  }

}

function Get_LinksGame($Extra='') {
  global $db,$GAMEID;
  $res = $db->query("SELECT * FROM Links WHERE GameId=$GAMEID $Extra");
  $links = [];
  if ($res) {
    while ($ans = $res->fetch_assoc()) { $links[$ans['id']] = $ans; };
    }
  return $links;
}

function Get_Links($sysref) {
  global $db,$GAMEID;
  $res = $db->query("SELECT * FROM Links WHERE GameId=$GAMEID AND (System1Ref='$sysref' OR System2Ref='$sysref')");
  $links = [];
  if ($res) {
    while ($ans = $res->fetch_assoc()) { $links[$ans['id']] = $ans; };
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

// Link knnowledge
/*
function Get_FactionLink($id) {
  global $db;
  $res = $db->query("SELECT * FROM FactionLink WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Get_FactionLinkFL($Fact, $Lid) {
  global $db;
  $res = $db->query("SELECT * FROM FactionLink WHERE FactionId=$Fact AND LinkId=$Lid");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return ['FactionId'=>$Fact, 'LinkId'=>$Lid, 'Known'=>0, 'NebScanned'=>0];
}

function Put_FactionLink(&$now) {
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_FactionLink($e);
    return Update_db('FactionLink',$Cur,$now);
  } else {
    return $now['id'] = Insert_db ('FactionLink', $now );
  }
}*/

/*
function Get_Factions4Link($Lid) {
  global $db;
  $FL = [];
  $res = $db->query("SELECT * FROM FactionLink WHERE LinkId=$Lid");
  if ($res) while ($ans = $res->fetch_assoc()) $FL[$ans['FactionId']] = $ans;
  return $FL;
} */

function Get_Factions4Link($Lid) {
  $L = Get_Link($Lid);
  $Sys1 = Get_SystemR($L['System1Ref']);
  $S1id = $Sys1['id'];
  $Sys2 = Get_SystemR($L['System2Ref']);
  $S2id = $Sys2['id'];

  $Facts = Get_Factions();
  $FL = [];
  foreach ($Facts as $Fid=>$F) {
    if (Has_Tech($Fid,'Know All Links')) {
      $FL[$Fid] = 'Used';
      continue;
    }
    $FLK = Gen_Get_Cond1('FactionLinkKnown',"FactionId=$Fid AND LinkId=$Lid");
    if ($FLK['Used']??0) {
      $FL[$Fid] = 'Used';
      continue;
    }
    $FS1 = Get_FactionSystemFS($Fid,$S1id);
    $FS2 = Get_FactionSystemFS($Fid,$S2id);

    if ($L['Concealment'] == 0) {
      $FL[$Fid] = 'Both';
      continue;
    }

    $K = 0;
    if (($FS1['SpaceScan']??0) >= $L['Concealment']) $K+=1;
    if (($FS2['SpaceScan']??0) >= $L['Concealment']) $K+=2;
    if ($K) $FL[$Fid] = ['','From ' . $L['System1Ref'], 'From ' . $L['System2Ref'],'Both'][$K];
  }
  return $FL;
}
// FactionsSystems

function Get_FactionSystem($id) {
  global $db;
  $res = $db->query("SELECT * FROM FactionSystem WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Get_FactionSystemFS($Fact, $id) {
  global $db;
  $res = $db->query("SELECT * FROM FactionSystem WHERE FactionId=$Fact AND SystemId=$id");
  if ($res)  if ($ans=$res->fetch_assoc()) return $ans;
  return ['FactionId'=>$Fact, 'SystemId'=>$id, 'ScanLevel'=>-1000, 'PlanetScan'=>-1, 'SpaceScan'=>-1];
}

function Get_FactionSystemFRef($Fact, $ref) { // DUFF CODE TODO no ref value
  global $db;
  $res = $db->query("SELECT * FROM FactionSystem WHERE FactionId=$Fact AND Ref='$ref'");
  if ($res)  if ($ans=$res->fetch_assoc()) return $ans;
  $N = Get_SystemR($ref);
  if ($N) return ['FactionId'=>$Fact, 'SystemId'=>$N['id']];
  echo "Unknown System Reference $ref<p>";
}

function Get_FactionSystemsF($Fact) {
  global $db;
  $Fs = [];
  $res = $db->query("SELECT * FROM FactionSystem WHERE FactionId=$Fact");
  if ($res) while ($ans = $res->fetch_assoc()) $Fs[$ans['SystemId']] = $ans;
  return $Fs;
}

function Get_LinksUsed($Fact) {
  global $db;
  $Ls = [];
  $res = $db->query("SELECT * FROM FactionLinkKnown WHERE FactionId=$Fact AND Used!=0");
  if ($res) while ($ans = $res->fetch_assoc()) $Ls[$ans['LinkId']] = 1;
  return $Ls;
}

function Put_FactionSystem(&$now) {
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_FactionSystem($e);
    return Update_db('FactionSystem',$Cur,$now);
  } else {
    return $now['id'] = Insert_db ('FactionSystem', $now );
  }
}

function Get_Factions4Sys($id) {
  global $db;
  $FL = [];
  $res = $db->query("SELECT * FROM FactionSystem WHERE SystemId=$id");
  if ($res) while ($ans = $res->fetch_assoc()) $FL[$ans['FactionId']] = $ans;
  return $FL;
}


// Faction Planet

function Get_FactionPlanet($id) {
  global $db;
  $res = $db->query("SELECT * FROM FactionPlanet WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Get_FactionPlanetFS($Fact, $id) {
  global $db;
  $res = $db->query("SELECT * FROM FactionPlanet WHERE FactionId=$Fact AND Planet=$id");
  if ($res) while ($ans = $res->fetch_assoc()) return $ans;
  return ['FactionId'=>$Fact, 'Planet'=>$id];
}

function Put_FactionPlanet(&$now) {
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_FactionPlanet($e);

    return Update_db('FactionPlanet',$Cur,$now);
  } else {
    return $now['id'] = Insert_db ('FactionPlanet', $now );
  }
}


function Get_Factions4Plan($id) {
  global $db;
  $FL = [];
  $res = $db->query("SELECT * FROM FactionPlanet WHERE PlanetId=$id");
  if ($res) while ($ans = $res->fetch_assoc()) $FL[$ans['FactionId']] = $ans;
  return $FL;
}

// ??

function Get_LinkLevel($id) {
  global $db;
  $res = $db->query("SELECT * FROM LinkLevel WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Put_LinkLevel(&$now) {
  $e=$now['id'];
  $Cur = Get_LinkLevel($e);
  return Update_db('LinkLevel',$Cur,$now);
}

function Get_LinkLevels($AllG=0) {
  global $db,$GAMEID,$NOTBY;
  $Lvls = [];
  $res = $db->query("SELECT * FROM LinkLevel " . ($AllG?'':"WHERE (NotBy&$NOTBY)=0 ") . " ORDER BY Level");
  if ($res) while ($ans = $res->fetch_assoc()) $Lvls[$AllG?$ans['id']:$ans['Level']] = $ans;
  return $Lvls;
}

function Get_LinkInstaLevel($id) {
  global $db;
  $res = $db->query("SELECT * FROM LinkInstaLevel WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Put_LinkInstaLevel(&$now) {
  $e=$now['id'];
  $Cur = Get_LinkInstaLevel($e);
  return Update_db('LinkInstaLevel',$Cur,$now);
}

function Get_LinkInstaLevels($AllG=0) {
  global $db,$GAMEID,$NOTBY;
  $Lvls = [];
  $res = $db->query("SELECT * FROM LinkInstaLevel " . ($AllG?'':"WHERE (NotBy&$NOTBY)=0 ") . " ORDER BY Instability");
  if ($res) while ($ans = $res->fetch_assoc()) $Lvls[$AllG?$ans['id']:$ans['Instability']] = $ans;
  return $Lvls;
}

// District Types

function Get_DistrictType($id) {
  global $db;
  $res = $db->query("SELECT * FROM DistrictTypes WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Put_DistrictType(&$now) {
  $e=$now['id'];
  $Cur = Get_DistrictType($e);
  return Update_db('DistrictTypes',$Cur,$now);
}

function Get_DistrictTypes($All=0) {
  global $db,$NOTBY;
  $Ts = [];
  if ($All) {
    $res = $db->query("SELECT * FROM DistrictTypes");
  } else {
    $res = $db->query("SELECT * FROM DistrictTypes WHERE (NotBy&$NOTBY)=0");
  }
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[$ans['id']] = $ans;
  return $Ts;
}

function Get_DistrictTypeNames($All=0) {
  global $db,$NOTBY;
  $Ts = [];
  if ($All) {
    $res = $db->query("SELECT * FROM DistrictTypes");
  } else {
    $res = $db->query("SELECT * FROM DistrictTypes WHERE (NotBy&$NOTBY)=0");
  }
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[$ans['id']] = $ans['Name'];
  return $Ts;
}

// Districts

function Get_District($id) {
  global $db;
  $res = $db->query("SELECT * FROM Districts WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Put_District(&$now) {
  global $db,$GAMEID,$GAME;
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_District($e);
    return Update_db('Districts',$Cur,$now);
  } else {
    $now['GameId'] = $GAMEID;
    return $now['id'] = Insert_db ('Districts', $now );
  }
}

function Get_DistrictsP($Pid,$Cvl=0,$Turn=0) {
  global $db,$GAMEID,$GAME;
  $Ts = [];
//  $res = $db->query("SELECT * FROM Districts WHERE HostType=1 AND HostId=$Pid AND TurnStart<=" . ($Turn? $Turn : $GAME['Turn']) . "ORDER BY Type, TurnStart");
  $res = $db->query("SELECT * FROM Districts WHERE HostType=1 AND HostId=$Pid ORDER BY Type");
  if ($res) while ($ans = $res->fetch_assoc()) {
    if ($ans['Delta'] && $Cvl==0) $ans['Number'] += $ans['Delta'];
    $Ts[$ans['Type']] = $ans;
  }
  return $Ts;
}

function Get_DistrictsM($Mid,$Cvl=0,$Turn=0) {
  global $db,$GAMEID,$GAME;
  $Ts = [];
//  $res = $db->query("SELECT * FROM Districts WHERE HostType=2 AND HostId=$Mid AND TurnStart<=" . ($Turn? $Turn : $GAME['Turn']) . "ORDER BY Type, TurnStart");
  $res = $db->query("SELECT * FROM Districts WHERE HostType=2 AND HostId=$Mid ORDER BY Type");
  if ($res) while ($ans = $res->fetch_assoc()) {
    if ($ans['Delta'] && $Cvl==0) $ans['Number'] += $ans['Delta'];
    $Ts[$ans['Type']] = $ans;
  }
  return $Ts;
}

function Get_DistrictsT($Tid,$Cvl=0,$Turn=0) {
  global $db,$GAMEID,$GAME;
  $Ts = [];
//  $res = $db->query("SELECT * FROM Districts WHERE HostType=3 AND HostId=$Tid AND TurnStart<=" . ($Turn? $Turn : $GAME['Turn']) . "ORDER BY Type, TurnStart");
  $res = $db->query("SELECT * FROM Districts WHERE HostType=3 AND HostId=$Tid ORDER BY Type");
  if ($res) while ($ans = $res->fetch_assoc()) {
    if ($ans['Delta'] && $Cvl==0) $ans['Number'] += $ans['Delta'];
    $Ts[$ans['Type']] = $ans;
  }
  return $Ts;
}

function Get_DistrictsH($Hid,$Cvl=0,$Turn=0) {
  global $db,$GAMEID,$GAME;
  $Ts = [];
/*  $res = $db->query("SELECT D.* FROM Districts D, ProjectHomes H WHERE D.HostType=H.ThingType AND H.id=$Hid AND D.HostId=H.ThingId " .
                    " AND TurnStart<=" . ($Turn? $Turn : $GAME['Turn']) . "ORDER BY Type, TurnStart");*/
  $res = $db->query("SELECT D.* FROM Districts D, ProjectHomes H WHERE D.HostType=H.ThingType AND H.id=$Hid AND D.HostId=H.ThingId " .
                    "ORDER BY Type");
  if ($res) while ($ans = $res->fetch_assoc()) {
    if ($ans['Delta'] && $Cvl==0) $ans['Number'] += $ans['Delta'];
    $Ts[$ans['Type']] = $ans;
  }
  return $Ts;
}

function Get_DistrictsAll($Turn=0) {
  global $db,$GAMEID;
  $Ts = [];
  $res = $db->query("SELECT * FROM Districts WHERE GameId=$GAMEID ORDER BY HostId, Type, TurnStart");
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[] = $ans;
  return $Ts;
}


//  Planet Types

function Get_PlanetType($id) {
  global $db;
  $res = $db->query("SELECT * FROM PlanetTypes WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Put_PlanetType(&$now) {
  $e=$now['id'];
  $Cur = Get_PlanetType($e);
  return Update_db('PlanetTypes',$Cur,$now);
}

function Get_PlanetTypes($AllG=0) {
  global $db,$NOTBY;
  $Ts = [];
  if ($AllG) {
    $res = $db->query("SELECT * FROM PlanetTypes");
  } else {
    $res = $db->query("SELECT * FROM PlanetTypes WHERE (NotBy&$NOTBY)=0");
  }
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[$ans['id']] = $ans;
  return $Ts;
}

function Get_PlanetTypeNames($AllG=0) {
  global $db,$NOTBY;
  $Ts = [];
  if ($AllG) {
    $res = $db->query("SELECT * FROM PlanetTypes");
  } else {
    $res = $db->query("SELECT * FROM PlanetTypes WHERE (NotBy&$NOTBY)=0");
  }
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[$ans['id']] = $ans['Name'];
  return $Ts;
}

//  Moons

function Get_Moon($id) {
  global $db;
  $res = $db->query("SELECT * FROM Moons WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Put_Moon(&$now) {
  global $db,$GAMEID;
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_Moon($e);
    return Update_db('Moons',$Cur,$now);
  } else {
    $now['GameId'] = $GAMEID;
    return $now['id'] = Insert_db ('Moons', $now );
  }
}


function Get_Moons($Pid) {
  global $db,$GAMEID;
  $Ms = [];
  $res = $db->query("SELECT * FROM Moons WHERE PlanetId=$Pid ORDER BY OrbitalRadius");
  if ($res) while ($ans = $res->fetch_assoc()) $Ms[] = $ans;
  return $Ms;
}

//

function Get_Thing($id) {
  global $db;
  $res = $db->query("SELECT * FROM Things WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Put_Thing(&$now) {
  global $db,$GAMEID;
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_Thing($e);
    return Update_db('Things',$Cur,$now);
  } else {
    $now['GameId'] = $GAMEID;
    $now['LastMoved'] = -1;
    return $now['id'] = Insert_db ('Things', $now );
  }
}

function Get_Things($Fact,$type=0) {
  global $db,$GAMEID;
  if (!is_numeric($type)) {
    $TTypes = Get_ThingTypes();
    $type = (array_flip(NamesList($TTypes))[$type]??0);
  }
  $Ts = [];
  $res = $db->query("SELECT * FROM Things WHERE GameId=$GAMEID " . ($Fact?"AND Whose=$Fact ":'') . ($type?" AND Type=$type":""));
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[$ans['id']] = $ans;
  return $Ts;
}

function Get_AllThings() {
  global $db,$GAMEID;
  $Ts = [];
  $res = $db->query("SELECT * FROM Things WHERE GameId=$GAMEID ORDER BY Whose");
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[$ans['id']] = $ans;
  return $Ts;
}

function Get_AllThingsAt($id) {
  global $db,$GAMEID;
  $Ts = [];
  $res = $db->query("SELECT * FROM Things WHERE GameId=$GAMEID AND SystemId=$id AND ( LinkId>=0 OR LinkId=-6) ORDER By Whose,Type");
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[] = $ans;
  return $Ts;
}

function Get_ThingsSys($Sid,$type=0,$Fid=0) {
  global $db,$GAMEID;
  $Ts = [];
  $res = $db->query("SELECT * FROM Things WHERE GameId=$GAMEID AND SystemId=$Sid AND  ( LinkId>=0 OR LinkId=-6)  " . ($type?" AND Type=$type":"") .
         ($Fid?" AND Whose=$Fid":""));
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[] = $ans;
  return $Ts;
}

function Get_Things_Cond($Fact=0,$Cond='') {
  global $db,$GAMEID;
  $Ts = [];
  $res = $db->query("SELECT * FROM Things WHERE GameId=$GAMEID " . ($Fact? " AND Whose=$Fact ":'') . ($Cond? " AND $Cond ":''));
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[] = $ans;
  return $Ts;
}

function Get_Things_Cond_Ordered($Fact=0,$Cond='') {
  global $db,$GAMEID;
  $Ts = [];
  $res = $db->query("SELECT * FROM Things WHERE GameId=$GAMEID " . ($Fact? " AND Whose=$Fact ":'') . ($Cond? " AND $Cond ":''));
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[$ans['id']] = $ans;
  return $Ts;
}


// Modules

function Get_Module($id) {
  global $db;
  $res = $db->query("SELECT * FROM Modules WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Put_Module(&$now) {
  global $db,$GAMEID;
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_Module($e);
    return Update_db('Modules',$Cur,$now);
  } else {
    return $now['id'] = Insert_db ('Modules', $now );
  }
}

function Get_Modules($Thing) {
  global $db,$GAMEID;
  $Ms = [];
  $res = $db->query("SELECT * FROM Modules WHERE ThingId=$Thing");
  if ($res) while ($ans = $res->fetch_assoc()) $Ms[$ans['Type']] = $ans;
  return $Ms;
}

function Get_ModulesType($Thing,$Type) {
  global $db,$GAMEID;
  static $MTNames = [];
  $Ms = [];
  if (!is_numeric($Type) ){
    if (empty($MTNames )) $MTNames = Gen_Get_Names_Flip('ModuleTypes');
    $Type = ($MTNames[$Type]??0);
  }
  $res = $db->query("SELECT * FROM Modules WHERE ThingId=$Thing AND Type=$Type LIMIT 1");
  if ($res) $Ms = $res->fetch_assoc();
  return $Ms;
}

// Anomalies

function Get_Anomaly($id) {
  global $db;
  $res = $db->query("SELECT * FROM Anomalies WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Put_Anomaly(&$now) {
  global $db,$GAMEID;
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_Anomaly($e);
    return Update_db('Anomalies',$Cur,$now);
  } else {
    return $now['id'] = Insert_db ('Anomalies', $now );
  }
}

// Module Types

function Get_ModuleType($id) {
  global $db;
  $res = $db->query("SELECT * FROM ModuleTypes WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Put_ModuleType(&$now) {
  global $db,$GAMEID;
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_ModuleType($e);
    return Update_db('ModuleTypes',$Cur,$now);
  } else {
    return $now['id'] = Insert_db ('ModuleTypes', $now );
  }
}

function Get_ModuleTypes($All=0) {
  global $db,$NOTBY;
  if ($All) {
    $res = $db->query("SELECT * FROM ModuleTypes ORDER BY id");
  } else {
    $res = $db->query("SELECT * FROM ModuleTypes WHERE (NotBy&$NOTBY)=0 ORDER BY id");
  }
  $Ms = [];
  if ($res) while ($ans = $res->fetch_assoc()) $Ms[$ans['id']] = $ans;
  return $Ms;
}

// Module Formulae Types

function Get_ModFormula($id) {
  global $db;
  $res = $db->query("SELECT * FROM ModFormulae WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Put_ModFormula(&$now) {
  global $db,$GAMEID;
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_ModFormula($e);
    return Update_db('ModFormulae',$Cur,$now);
  } else {
    return $now['id'] = Insert_db ('ModFormulae', $now );
  }
}

function Get_ModFormulaes() {
  global $db,$GAMEID;
  $Ms = [];
  $res = $db->query("SELECT * FROM ModFormulae");
  if ($res) while ($ans = $res->fetch_assoc()) $Ms[] = $ans;
  return $Ms;
}

// Technologies

function Get_Tech($id) {
  global $db;
  $res = $db->query("SELECT * FROM Technologies WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Put_Tech(&$now) {
  global $db,$GAMEID;
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_Tech($e);
    return Update_db('Technologies',$Cur,$now);
  } else {
    return $now['id'] = Insert_db ('Technologies', $now );
  }
}

function Get_Techs($Fact=0,$AllG=0) {
  global $db,$GAMEID,$NOTBY;  // $Fact not used now
  $Ms = [];
  $res = $db->query("SELECT * FROM Technologies " . ($AllG?'':"WHERE (NotBy&$NOTBY)=0 ") . " ORDER BY Cat,id");
  if ($res) while ($ans = $res->fetch_assoc()) $Ms[$ans['id']] = $ans;
  return $Ms;
}

function Get_TechsByCore($Fact=0, $All=0, $AllG=0) {
  global $db,$NOTBY;
  $Ms = [];
  if ($Fact == 0 || $All || $AllG ) {
    if ($AllG) {
      $res = $db->query("SELECT * FROM Technologies ORDER BY PreReqTech, PreReqLevel, Name");
    } else {
      $res = $db->query("SELECT * FROM Technologies WHERE (NotBy&$NOTBY)=0 ORDER BY PreReqTech, PreReqLevel, Name");
    }
  } else {
    $res = $db->query("SELECT DISTINCT t.* FROM Technologies t, FactionTechs ft WHERE ((NotBy&$NOTBY)=0) AND (" .
           "(t.Cat<2 OR (t.Cat=2 AND ft.Faction_Id=$Fact AND ft.Tech_Id=t.id))) ORDER BY t.PreReqTech, t.PreReqLevel, t.Name");
  }
  if ($res) while ($ans = $res->fetch_assoc()) $Ms[$ans['id']] = $ans;
  return $Ms;
}

function Get_CoreTechs($AllG=0) {
  global $db,$NOTBY;
  $Ms = [];
  $res = $db->query("SELECT * FROM Technologies WHERE Cat=0 " . ($AllG?'':"AND (NotBy&$NOTBY)=0")  . " ORDER BY id");
  if ($res) while ($ans = $res->fetch_assoc()) $Ms[$ans['id']] = $ans;
  return $Ms;
}

function Get_CoreTechsByName($AllG=0) {
  global $db,$NOTBY;
  $Ms = [];
  $res = $db->query("SELECT * FROM Technologies WHERE Cat=0 " . ($AllG?'':"AND (NotBy&$NOTBY)=0")  . " ORDER BY Name");
  if ($res) while ($ans = $res->fetch_assoc()) $Ms[$ans['id']] = $ans;
  return $Ms;
}

// Faction Techs

function Get_Faction_Tech($id) {
  global $db;
  $res = $db->query("SELECT * FROM FactionTechs WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Get_Faction_TechFT($Fid,$Tid, $Turn=0) {
  global $db;
  $res = $db->query("SELECT * FROM FactionTechs WHERE Faction_Id=$Fid AND Tech_Id=$Tid");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return ['Tech_Id'=>$Tid, 'Faction_Id'=> $Fid, 'Level' => 0];
}

function Put_Faction_Tech(&$now) {
  global $db,$GAMEID;
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_Faction_Tech($e);
    return Update_db('FactionTechs',$Cur,$now);
  } else {
    return $now['id'] = Insert_db ('FactionTechs', $now );
  }
}

function Get_Faction_Techs($Fact,$Turn=0) {
  global $db,$GAMEID;
  $Ms = [];
  if ($Turn == 0) {
   $res = $db->query("SELECT * FROM  FactionTechs WHERE Faction_Id=$Fact");
    if ($res) while ($ans = $res->fetch_assoc()) $Ms[$ans['Tech_Id']] = $ans;
    return $Ms;
  }
  $res = $db->query("SELECT * FROM  FactionTechs WHERE Faction_Id=$Fact AND StartTurn<=$Turn ");
  if ($res) while ($ans = $res->fetch_assoc()) $Ms[$ans['Tech_Id']] = $ans;

/*
  foreach ($Ms as $M) {
    if ($M['Tech_Id'] < 100) {
      $tid = $M['Tech_Id'];
      $res = $db->query("SELECT * FROM  FactionTechLevels WHERE Faction_Id=$Fact AND Tech_Id=$tid AND StartTurn<=$Turn ORDER BY StartTurn");
      if ($res) while ($ans = $res->fetch_assoc()) $Ms[$tid]['Level'] = $ans['Level'];
    }
  }
*/
  return $Ms;
}// . ($Turn? " AND StartTurn>=$Turn " : ""


// Thing Types

function Get_ThingType($id) {
  global $db,$NOTBY;
  $res = $db->query("SELECT * FROM ThingTypes WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Put_ThingType(&$now) {
  global $db,$GAMEID;
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_ThingType($e);
    return Update_db('ThingTypes',$Cur,$now);
  } else {
    return $now['id'] = Insert_db ('ThingTypes', $now );
  }
}

function Get_ThingTypes($AllG=0) {
  global $db,$NOTBY;
  $Ts = [];
  $res = $db->query("SELECT * FROM ThingTypes " . ($AllG?'':"WHERE (NotBy&$NOTBY)=0") );
  if ($res) while ($ans = $res->fetch_assoc()) {
    $Ts[$ans['id']] = $ans;
  }
  return $Ts;
}

function Has_Tech($fid,$name) {
  global $db,$GAME;
  if (empty($fid)) {
    if (Feature('ReportEmptyHasTech') && Access('God') && !isset($_REQUEST['FORCE'])) {
//      var_dump($fid,$name); //OK
//      debug_print_backtrace();
    }
    return 0;
  }
  if (is_numeric($name)) {
    if ($name == 0) return 1;
    $Tech = Get_Tech($name);
  } else {
    $res = $db->query("SELECT * FROM Technologies WHERE Name='$name'");
    if (!$res || !($Tech = $res->fetch_assoc())) return 0;
    $name= $Tech['id'];
  }
  if (empty($Tech)) {
    if ($name != 0) echo "Has_Tech called with impossible tech $name";
    return 0;
  }
  if ($Tech['Cat'] ==0) {
    $lvl = 0;

    $res = $db->query("SELECT Level FROM  FactionTechs WHERE Faction_Id=$fid AND Tech_Id=$name ");
    if ($res && ($ans = $res->fetch_assoc())) $lvl = $ans['Level'];
 //   if ($name== 145)  var_dump($lvl);
    return $lvl;
  }

  // Supp Tech
  $res = $db->query("SELECT Level FROM  FactionTechs WHERE Faction_Id=$fid AND Tech_Id=$name ");

  if (!$res || !($ans = $res->fetch_assoc())) {/*var_dump(2,$fid,$name);*/ return 0;} // Don'y have it
  if ($ans['Level'] == 0) {/*var_dump(4,$fid,$name);*/ return 0;}

  $lvl = 0;
  $Based = $Tech['PreReqTech'];

  if ($Based) {
    $res = $db->query("SELECT Level FROM  FactionTechs WHERE Faction_Id=$fid AND Tech_Id=$Based ");
    if ($res && ($ans = $res->fetch_assoc())) $lvl = $ans['Level'];

    return $lvl;
  }
  return $ans['Level'];

}

function Has_GTrait(&$Data,$Name) {
  if (empty($Data)) return false;
  if ($Data['Trait1'] == $Name || $Data['Trait2'] == $Name || $Data['Trait3'] == $Name) return true;
  return false;
}

// Get Game in sk.php

function Put_Game($now) {
  global $db,$GAMEID;
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_Game($e);
    return Update_db('Games',$Cur,$now);
  } else {
    return $now['id'] = Insert_db ('Games', $now );
  }
}

// Deep Space Projects

function Get_DeepSpace($id) {
  global $db;
  $res = $db->query("SELECT * FROM DeepSpaceProjects WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Put_DeepSpace(&$now) {
  global $db,$GAMEID;
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_DeepSpace($e);
    return Update_db('DeepSpaceProjects',$Cur,$now);
  } else {
    return $now['id'] = Insert_db ('DeepSpaceProjects', $now );
  }
}

function Get_DeepSpaces() {
  global $db,$GAMEID;
  $Ms = [];
  $res = $db->query("SELECT * FROM  DeepSpaceProjects ");
  if ($res) while ($ans = $res->fetch_assoc()) $Ms[$ans['id']] = $ans;
  return $Ms;
}

function Get_FactionMoon($id) {
  global $db;
  $res = $db->query("SELECT * FROM FactionMoon WHERE id=$id");
  if ($res) return $res->fetch_assoc();
}

function Get_FactionMoonFS($Fact, $id) {
  global $db;
  $res = $db->query("SELECT * FROM FactionMoon WHERE FactionId=$Fact AND Moon=$id");
  if ($res) return $ans = $res->fetch_assoc();
  return ['FactionId'=>$Fact, 'Moon'=>$id];
}

function Put_FactionMoon(&$now) {
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_FactionMoon($e);
    return Update_db('FactionMoon',$Cur,$now);
  } else {
    return $now['id'] = Insert_db ('FactionMoon', $now );
  }
}

/// Turns

function Get_Turn($id) {
  global $db,$GAME,$GAMEID;
  $res = $db->query("SELECT * FROM Turns WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return ['GameId'=>$GAMEID, 'Progress'=>0, 'ActivityLog'=>'', 'TurnNumber'=>$GAME['Turn']];
}

function Get_TurnNumber($id=0) {
  global $db,$GAME,$GAMEID;
  if ($id ==0) $id =$GAME['Turn'];
  $res = $db->query("SELECT * FROM Turns WHERE GameId=$GAMEID AND TurnNumber=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return ['GameId'=>$GAMEID, 'Progress'=>0, 'ActivityLog'=>'', 'TurnNumber'=>$GAME['Turn'] ];
}


function Put_Turn(&$now) {
  global $db,$GAMEID;
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_Turn($e);
    return Update_db('Turns',$Cur,$now);
  } else {
    return $now['id'] = Insert_db ('Turns', $now );
  }
}

// Projects

function Get_Project($id) {
  global $db;
  $res = $db->query("SELECT * FROM Projects WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Get_ProjectAt($Home,$DT,$Start) {
  global $db;
  $res = $db->query("SELECT p.* FROM Projects p, ProjectTypes t WHERE p.Home=$Home AND p.TurnStart=$Start AND t.id=p.Type AND DType=$DT") ;
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}


function Put_Project(&$now) {
  global $db,$GAMEID;
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_Project($e);
    return Update_db('Projects',$Cur,$now);
  } else {
    $now['GameId'] = $GAMEID;
    return $now['id'] = Insert_db ('Projects', $now );
  }
}

function Get_Projects($home) {
  global $db;
  $Ts = [];
  $res = $db->query("SELECT * FROM Projects WHERE Home=$home ORDER BY TurnStart");
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[] = $ans;
  return $Ts;
}

function Get_Projects_Cond($Cond) {
  global $db,$GAMEID;
  $Ts = [];
// var_dump($Cond);
  $res = $db->query("SELECT * FROM Projects WHERE GameId=$GAMEID AND $Cond ORDER BY FactionId, TurnStart");
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[] = $ans;
  return $Ts;
}


// Project Types
function Get_ProjectType($id) {
  global $db,$NOTBY;
  $res = $db->query("SELECT * FROM ProjectTypes WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Get_ProjectTypes($AllG=0) {
  global $db,$NOTBY;
  $Ts = [];
  $res = $db->query("SELECT * FROM ProjectTypes " . ($AllG?'':"WHERE (NotBy&$NOTBY)=0") );
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[$ans['id']] = $ans;
  return $Ts;
}

function Put_ProjectType(&$now) {
  global $db,$GAMEID;
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_ProjectType($e);
    return Update_db('ProjectTypes',$Cur,$now);
  } else {
    return $now['id'] = Insert_db ('ProjectTypes', $now );
  }
}

// Org Types
function Get_OrgType($id) {
  global $db,$NOTBY;
  $res = $db->query("SELECT * FROM OfficeTypes WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Get_OrgTypes($AllG=0) {
  global $db,$NOTBY;
  $Ts = [];
  $res = $db->query("SELECT * FROM OfficeTypes " . ($AllG?'':"WHERE (NotBy&$NOTBY)=0") );
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[$ans['id']] = $ans;
  return $Ts;
}

function Put_OrgType(&$now) {
  global $db,$GAMEID;
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_OrgType($e);
    return Update_db('OfficeTypes',$Cur,$now);
  } else {
    return $now['id'] = Insert_db ('OfficeTypes', $now );
  }
}

// Operations
function Get_Operation($id) {
  global $db,$NOTBY;
  $res = $db->query("SELECT * FROM Operations WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Get_OperationAt($OrgId,$Start) {
  global $db;
  $res = $db->query("SELECT * FROM Operations WHERE OrgId=$OrgId AND TurnStart=$Start ") ;
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Get_Operations($Fid=0) {
  global $db,$NOTBY,$GAMEID;
  $Ts = [];
  $res = $db->query("SELECT * FROM Operations " . ($Fid? "WHERE Whose=$Fid " : "WHERE GameId=$GAMEID") );
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[$ans['id']] = $ans;
  return $Ts;
}

function Put_Operation(&$now) {
  global $db,$GAMEID;
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_Operation($e);
    return Update_db('Operations',$Cur,$now);
  } else {
    return $now['id'] = Insert_db ('Operations', $now );
  }
}

// Org Operations
function Get_OpType($id) {
  global $db,$NOTBY;
  $res = $db->query("SELECT * FROM OrgActions WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Get_OpTypes($AllG=0) {
  global $db,$NOTBY;
  $Ts = [];
  $res = $db->query("SELECT * FROM OrgActions " . ($AllG?'':"WHERE (NotBy&$NOTBY)=0") . " ORDER BY Office, Name");
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[$ans['id']] = $ans;
  return $Ts;
}

function Put_OpType(&$now) {
  global $db,$GAMEID;
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_OpType($e);
    return Update_db('OrgActions',$Cur,$now);
  } else {
    return $now['id'] = Insert_db ('OrgActions', $now );
  }
}

// Branch Types
function Get_BranchType($id) {
  global $db,$NOTBY;
  $res = $db->query("SELECT * FROM BranchTypes WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Get_BranchTypes($AllG=0) {
  global $db,$NOTBY;
  $Ts = [];
  $res = $db->query("SELECT * FROM BranchTypes " . ($AllG?'':"WHERE (NotBy&$NOTBY)=0") );
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[$ans['id']] = $ans;
  return $Ts;
}

function Put_BranchType(&$now) {
  global $db,$GAMEID;
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_BranchType($e);
    return Update_db('BranchTypes',$Cur,$now);
  } else {
    return $now['id'] = Insert_db ('OrgActions', $now );
  }
}

// Offices
function Get_Office($id) {
  global $db,$NOTBY;
  $res = $db->query("SELECT * FROM OrgActions WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Get_Offices($World=0,$xtra='') {
  global $db,$NOTBY,$GAMEID;
  $Ts = [];
  $res = $db->query("SELECT * FROM Offices WHERE " . ($World?"World=$World":"GameId=$GAMEID") . $xtra);
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[$ans['id']] = $ans;
  return $Ts;
}

function Put_Office(&$now) {
  global $db,$GAMEID;
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_Office($e);
    return Update_db('Offices',$Cur,$now);
  } else {
    $now['GameId'] = $GAMEID;
    return $now['id'] = Insert_db ('Offices', $now );
  }
}

// Social Principles
function Get_SocialP($id) {
  global $db,$NOTBY;
  $res = $db->query("SELECT * FROM SocialPrinciples WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Get_SocialPs($World,$xtra = '') {
  global $db,$NOTBY,$GAMEID;
  $Ts = [];
  $res = $db->query("SELECT * FROM SocPsWorlds WHERE World=$World" . $xtra);
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[$ans['id']] = $ans;
  return $Ts;
}

function Put_SocialP(&$now) {
  global $db,$GAMEID;
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_SocialP($e);
    return Update_db('SocialPrinciples',$Cur,$now);
  } else {
    $now['GameId'] = $GAMEID;
    return $now['id'] = Insert_db ('SocialPrinciples', $now );
  }
}

// Project Homes

function Get_ProjectHome($id) {
  global $db;
  $res = $db->query("SELECT * FROM ProjectHomes WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Get_ProjectHomes($who=0) {
  global $db,$GAMEID;
  $Ts = [];
  $res = $db->query("SELECT * FROM ProjectHomes WHERE GameId=$GAMEID " . ($who?" AND Whose=$who":""));
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[$ans['id']] = $ans;
  return $Ts;
}

function Put_ProjectHome(&$now) {
  global $db,$GAMEID;
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_ProjectHome($e);
    return Update_db('ProjectHomes',$Cur,$now);
  } else {
    $now['GameId'] = $GAMEID;
    return $now['id'] = Insert_db ('ProjectHomes', $now );
  }
}

// Project Turns

function Get_ProjectTurn($id) {
  global $db;
  $res = $db->query("SELECT * FROM ProjectTurn WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Get_ProjectTurnPT($pid,$Turn) {
  global $db;
  $res = $db->query("SELECT * FROM ProjectTurn WHERE ProjectId=$pid AND TurnNumber=$Turn");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return ['ProjectId'=>$pid, 'TurnNumber'=>$Turn ];
}

function Get_ProjectTurns($Proj) {
  global $db,$GAMEID;
  $Ts = [];
  $res = $db->query("SELECT * FROM ProjectTurn WHERE ProjectId=$Proj ORDER BY TurnNumber");
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[] = $ans;
  return $Ts;
}

function Put_ProjectTurn(&$now) {
  global $db,$GAMEID;
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_ProjectTurn($e);
    return Update_db('ProjectTurn',$Cur,$now);
  } else {
    return $now['id'] = Insert_db ('ProjectTurn', $now );
  }
}

// People

function Get_Person($id) {
  global $db;
  $res = $db->query("SELECT * FROM People WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Get_People() {
  global $db,$GAMEID;
  $Ts = [];
  $res = $db->query("SELECT * FROM People ");
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[$ans['id']] = $ans;
  return $Ts;
}

// Faction Faction Knowledge

function Get_FactionFaction($id) {
  global $db;
  $res = $db->query("SELECT * FROM FactionFaction WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Get_FactionFactions($who) {
  global $db,$GAMEID;
  $Ts = [];
  $res = $db->query("SELECT * FROM FactionFaction WHERE FactionId1=$who");
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[$ans['FactionId2']] = $ans;
  return $Ts;
}

function Get_FactionFactionFF($who,$with) {
  global $db,$GAMEID;
  $res = $db->query("SELECT * FROM FactionFaction WHERE FactionId1=$who AND FactionId2=$with");
  if ($res) while ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Get_FactionFactionsCarry($who) {
  global $db,$GAMEID;
  $Ts = [];
  $res = $db->query("SELECT * FROM FactionFaction WHERE FactionId2=$who");
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[$ans['FactionId1']] = $ans;
  return $Ts;
}

function Put_FactionFaction(&$now) {
  global $db,$GAMEID;
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_FactionFaction($e);
    return Update_db('FactionFaction',$Cur,$now);
  } else {
    $now['GameId'] = $GAMEID;
    return $now['id'] = Insert_db ('FactionFaction', $now );
  }
}

// Credit Log

function Get_CreditLog($id) {
  global $db;
  $res = $db->query("SELECT * FROM CreditLog WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Put_CreditLog(&$now) {
  global $db,$GAMEID;
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_CreditLog($e);
    return Update_db('CreditLog',$Cur,$now);
  } else {
    return $now['id'] = Insert_db ('CreditLog', $now );
  }
}

function Get_CreditLogs($who,$From=0,$To=10000) {
  global $db,$GAMEID;
  $Ts = [];
  $res = $db->query("SELECT * FROM CreditLog WHERE Whose=$who AND Turn>=$From AND Turn<=$To ORDER BY id DESC");
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[] = $ans;
  return $Ts;
}

function Get_FactionTurn($id) {
  global $db;
  $res = $db->query("SELECT * FROM FactionTurn WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Get_FactionTurnFT($Fid,$Turn) {
  global $db;
  $res = $db->query("SELECT * FROM FactionTurn WHERE FactionId=$Fid AND Turn=$Turn");
  if ($res && ($ans = $res->fetch_assoc())) return $ans;
  return ['FactionId'=>$Fid,'Turn'=>$Turn];
}

function Put_FactionTurn(&$now) {
  global $db,$GAMEID;
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_FactionTurn($e);
    return Update_db('FactionTurn',$Cur,$now);
  } else {
    return $now['id'] = Insert_db ('FactionTurn', $now );
  }
}


// Banking
function Get_Banking($id) {
  global $db;
  $res = $db->query("SELECT * FROM Banking WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Get_BankingFT($Fid,$Turn) {
  global $db,$GAMEID;
  $Ts = [];
  $res = $db->query("SELECT * FROM Banking WHERE GameId=$GAMEID AND " . ($Fid? " FactionId=$Fid AND ": "") .
    "(StartTurn=$Turn OR ( StartTurn<$Turn AND EndTurn >= $Turn ))");
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[] = $ans;
  return $Ts;
}

function Put_Banking(&$now) {
  global $db,$GAMEID;
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_Banking($e);
    return Update_db('Banking',$Cur,$now);
  } else {
    return $now['id'] = Insert_db ('Banking', $now );
  }
}


// Worlds
function Get_World($id) {
  global $db;
  $res = $db->query("SELECT * FROM Worlds WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Put_World(&$now) {
  global $db,$GAMEID;
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_World($e);
    return Update_db('Worlds',$Cur,$now);
  } else {
    $now['GameId'] = $GAMEID;
    return $now['id'] = Insert_db ('Worlds', $now );
  }
}

function Get_Worlds($Fid=0) {
  global $db,$GAMEID;
  $Ts = [];
  $res = $db->query("SELECT * FROM Worlds WHERE GameId=$GAMEID " . ($Fid?" AND FactionId=$Fid ":" ") . " ORDER By FactionId, RelOrder DESC");
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[$ans['id']] = $ans;
  return $Ts;
}


// Instruction - Can't use gen for UpdateMany
function Get_Instruction($id) {
  global $db;
  $res = $db->query("SELECT * FROM Instructions WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Put_Instruction(&$now) {
  global $db,$GAMEID;
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_Instruction($e);
    return Update_db('Instructions',$Cur,$now);
  } else {
    return $now['id'] = Insert_db ('Instructions', $now );
  }
}

function Get_Instructions() {
  global $db;
  $Ts = [];
  $res = $db->query("SELECT * FROM Instructions");
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[$ans['id']] = $ans;
  return $Ts;
}


// Future Tech
function Get_FutureTech($Fid,$tid,$lvl) {
  global $db;
  $res = $db->query("SELECT * FROM FutureTechLevels WHERE FactionId=$Fid AND Tech_Id=$tid AND Level=$lvl");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return ['FactionId'=>$Fid,'Tech_Id'=>$tid,'Level'=>$lvl];
}

function Put_FutureTech(&$now) {
  global $db,$GAMEID;
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_FutureTech($e);
    return Update_db('FutureTechLevels',$Cur,$now);
  } else {
    return $now['id'] = Insert_db ('FutureTechLevels', $now );
  }
}

// General Table operations


function GMLog4Later($text) {
  $rec['What'] = $text;
  Gen_Put('GMLog4Later',$rec);
}






