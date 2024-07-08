CREATE TABLE `Things` (
  `id` int NOT NULL AUTO_INCREMENT,
  `GameId` int NOT NULL,
  `Type` int NOT NULL DEFAULT '0',
  `SubType` int NOT NULL DEFAULT '0',
  `Level` int NOT NULL DEFAULT '1',
  `SystemId` int NOT NULL,
  `WithinSysLoc` int NOT NULL DEFAULT '1',
  `Name` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `Class` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `Whose` int NOT NULL DEFAULT '0',
  `Description` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `ProjectId` int NOT NULL DEFAULT '0',
  `CurHealth` int NOT NULL DEFAULT '1',
  `OrigHealth` int NOT NULL DEFAULT '1',
  `ShieldPoints` int NOT NULL,
  `CurShield` int NOT NULL,
  `Notes` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `GM_Notes` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `MaxDistricts` int NOT NULL DEFAULT '0',
  `MaxModules` int NOT NULL DEFAULT '0',
  `Image` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `Gadgets` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `BuildState` int NOT NULL DEFAULT '0',
  `NamedCrew` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `History` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `LinkId` int NOT NULL DEFAULT '0',
  `TargetKnown` int NOT NULL,
  `NewSystemId` int NOT NULL DEFAULT '0',
  `NewLocation` int NOT NULL DEFAULT '0',
  `ProjHome` int NOT NULL DEFAULT '0',
  `HasDeepSpace` int NOT NULL DEFAULT '0',
  `Orders` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `DesignValid` int NOT NULL DEFAULT '0',
  `Sensors` int NOT NULL,
  `SensorLevel` int NOT NULL,
  `NebSensors` int NOT NULL,
  `CargoSpace` int NOT NULL,
  `CargoUsed` int NOT NULL,
  `OtherFaction` int NOT NULL,
  `TurnBuilt` int NOT NULL,
  `Peaceful` int NOT NULL DEFAULT '1',
  `Speed` double NOT NULL DEFAULT '1',
  `Instruction` int NOT NULL,
  `Dist1` int NOT NULL,
  `Dist2` int NOT NULL,
  `ActionsNeeded` int NOT NULL,
  `Progress` int NOT NULL,
  `Spare1` int NOT NULL,
  `CurInst` int NOT NULL,
  `MakeName` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `InstCost` int NOT NULL,
  `LinkPay` int NOT NULL,
  `LinkCost` int NOT NULL,
  `SeenTypeMask` int NOT NULL,
  `PrisonerOf` int NOT NULL,
  `Priority` int NOT NULL,
  `LastMoved` int NOT NULL,
  `HiddenControl` int NOT NULL DEFAULT '0',
  `Stability` double NOT NULL,
  `Mobility` double NOT NULL,
  `Evasion` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
