CREATE TABLE `Planets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Type` int NOT NULL DEFAULT '1',
  `Size` int DEFAULT NULL,
  `Minerals` int NOT NULL DEFAULT '1',
  `SystemId` int NOT NULL,
  `Description` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `Notes` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `Image` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `OrbitalRadius` double DEFAULT NULL,
  `Period` double DEFAULT NULL,
  `Gravity` double DEFAULT NULL,
  `Radius` double DEFAULT NULL,
  `Name` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `ShortName` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `Moons` int NOT NULL DEFAULT '0',
  `ProjHome` int NOT NULL DEFAULT '0',
  `Control` int NOT NULL,
  `Attributes` int NOT NULL,
  `Mined` int NOT NULL,
  `GameId` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;