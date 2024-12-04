CREATE TABLE `Factions` (
  `Name` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Player` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Player2` text COLLATE utf8mb4_general_ci NOT NULL,
  `id` int NOT NULL AUTO_INCREMENT,
  `GameId` int NOT NULL,
  `Notes` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `GM_Notes` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `Features` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `MapColour` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `AccessKey` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `LastActive` int DEFAULT NULL,
  `Biosphere` int NOT NULL DEFAULT '0',
  `Biosphere2` int NOT NULL DEFAULT '0',
  `Biosphere3` int NOT NULL DEFAULT '0',
  `Trait1` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `Trait2` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `Trait3` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `Trait1Text` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `Trait2Text` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `Trait3Text` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `Credits` int NOT NULL DEFAULT '0',
  `PhysicsSP` int NOT NULL DEFAULT '0',
  `EngineeringSP` int NOT NULL DEFAULT '0',
  `XenologySP` int NOT NULL DEFAULT '0',
  `Special1` int NOT NULL,
  `Special2` int NOT NULL,
  `Special3` int NOT NULL,
  `TurnState` int NOT NULL DEFAULT '0',
  `NPC` int NOT NULL,
  `Image` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Horizon` int NOT NULL,
  `Trait1Auto` int NOT NULL,
  `Trait2Auto` int NOT NULL,
  `Trait3Auto` int NOT NULL,
  `Adjective` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `HomeWorld` int NOT NULL,
  `Currency1` int NOT NULL,
  `Currency2` int NOT NULL,
  `Currency3` int NOT NULL,
  `MapText` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `ThingType` int NOT NULL,
  `ThingBuild` int NOT NULL,
  `GMThingType` int NOT NULL,
  `GMThingBuild` int NOT NULL,
  `HasPrisoners` int NOT NULL,
  `NoAnomalies` int NOT NULL,
  `Trait1Conceal` int NOT NULL,
  `Trait2Conceal` int NOT NULL,
  `Trait3Conceal` int NOT NULL,
  `FoodType` int NOT NULL,
  `DefaultRelations` int NOT NULL,
  `AlienDescription` text COLLATE utf8mb4_general_ci NOT NULL,
  `ScaleFactor` float NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
