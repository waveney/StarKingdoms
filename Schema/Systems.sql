CREATE TABLE `Systems` (
  `GameId` int NOT NULL,
  `Ref` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `GridX` int DEFAULT NULL,
  `GridY` int DEFAULT NULL,
  `Notes` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `id` int NOT NULL AUTO_INCREMENT,
  `Control` int DEFAULT NULL,
  `HistoricalControl` int NOT NULL DEFAULT '0',
  `Name` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `ShortName` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `Nebulae` int DEFAULT NULL,
  `Description` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `Type` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `Radius` double DEFAULT NULL,
  `Mass` double DEFAULT NULL,
  `Temperature` double DEFAULT NULL,
  `Luminosity` double DEFAULT NULL,
  `Image` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `Image2` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `Type2` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `Radius2` double DEFAULT NULL,
  `Mass2` double DEFAULT NULL,
  `Temperature2` double DEFAULT NULL,
  `Luminosity2` double DEFAULT NULL,
  `Distance` double DEFAULT NULL,
  `Period` double DEFAULT NULL,
  `Category` int DEFAULT NULL,
  `StarName` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `StarName2` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Flags` int NOT NULL,
  `Trait1` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Trait1Desc` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Trait1Auto` int NOT NULL,
  `Trait1Conceal` int NOT NULL,
  `HabPlanet` int NOT NULL,
  `Trait2` text COLLATE utf8mb4_general_ci NOT NULL,
  `Trait2Desc` text COLLATE utf8mb4_general_ci NOT NULL,
  `Trait2Auto` int NOT NULL,
  `Trait2Conceal` int NOT NULL,
  `Trait3` text COLLATE utf8mb4_general_ci NOT NULL,
  `Trait3Desc` text COLLATE utf8mb4_general_ci NOT NULL,
  `Trait3Auto` int NOT NULL,
  `Trait3Conceal` int NOT NULL,
  `WorldList` text COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
