CREATE TABLE `Projects` (
  `id` int NOT NULL AUTO_INCREMENT,
  `FactionId` int NOT NULL DEFAULT '0',
  `Type` int NOT NULL,
  `Level` int NOT NULL DEFAULT '1',
  `Home` int NOT NULL,
  `Progress` int NOT NULL DEFAULT '0',
  `Status` int NOT NULL DEFAULT '0',
  `TurnStart` int NOT NULL DEFAULT '0',
  `TurnEnd` int NOT NULL DEFAULT '0',
  `Notes` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `ThingId` int NOT NULL DEFAULT '0',
  `ThingId2` int NOT NULL,
  `ThingType` int NOT NULL DEFAULT '0',
  `Name` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `Costs` int NOT NULL DEFAULT '0',
  `ProgNeeded` int NOT NULL DEFAULT '0',
  `LastUpdate` int NOT NULL,
  `GMOverride` int NOT NULL,
  `DType` int NOT NULL,
  `FreeRushes` int NOT NULL,
  `GMLock` int NOT NULL,
  `GameId` int NOT NULL,
  `OrgName` text COLLATE utf8mb4_general_ci NOT NULL,
  `OrgDesc` text COLLATE utf8mb4_general_ci NOT NULL,
  `OrgSP` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
