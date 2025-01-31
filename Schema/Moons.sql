CREATE TABLE `Moons` (
  `id` int NOT NULL AUTO_INCREMENT,
  `PlanetId` int NOT NULL,
  `Name` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `ShortName` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `Radius` double NOT NULL,
  `Gravity` double NOT NULL,
  `Period` double NOT NULL,
  `OrbitalRadius` double NOT NULL,
  `Type` int NOT NULL DEFAULT '0',
  `Control` int NOT NULL DEFAULT '0',
  `HistoricalControl` int NOT NULL,
  `Minerals` int NOT NULL DEFAULT '0',
  `Description` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `Image` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `ProjHome` int NOT NULL DEFAULT '0',
  `Attributes` int NOT NULL,
  `Mined` int NOT NULL,
  `GameId` int NOT NULL,
  `Trait1` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Trait1Desc` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Trait1Auto` int NOT NULL,
  `Trait1Conceal` int NOT NULL,
  `ColonyTweak` int NOT NULL,
  `Trait2` text COLLATE utf8mb4_general_ci NOT NULL,
  `Trait2Desc` text COLLATE utf8mb4_general_ci NOT NULL,
  `Trait2Auto` int NOT NULL,
  `Trait2Conceal` int NOT NULL,
  `Trait3` text COLLATE utf8mb4_general_ci NOT NULL,
  `Trait3Desc` text COLLATE utf8mb4_general_ci NOT NULL,
  `Trait3Auto` int NOT NULL,
  `Trait3Conceal` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
