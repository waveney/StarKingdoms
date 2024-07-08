CREATE TABLE `Links` (
  `id` int NOT NULL AUTO_INCREMENT,
  `GameId` int NOT NULL,
  `System1Ref` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `System2Ref` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Level` int NOT NULL,
  `FakeOrig` int DEFAULT NULL,
  `Knowledge` int DEFAULT NULL,
  `Notes` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `UseCount` int NOT NULL,
  `MinedA` int NOT NULL,
  `MinedB` int NOT NULL,
  `Status` int NOT NULL,
  `Weight` int NOT NULL DEFAULT '1',
  `Whose` int NOT NULL,
  `Instability` int NOT NULL,
  `Concealment` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
