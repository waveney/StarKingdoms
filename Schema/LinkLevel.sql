CREATE TABLE `LinkLevel` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Level` int NOT NULL,
  `Colour` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Notes` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `GameId` int NOT NULL,
  `Name` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Cost` int NOT NULL,
  `AgentCost` int NOT NULL,
  `MakeCost` int NOT NULL,
  `NotBy` int NOT NULL,
  `Width` int NOT NULL,
  `Style` text COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
