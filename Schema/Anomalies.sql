CREATE TABLE `Anomalies` (
  `id` int NOT NULL AUTO_INCREMENT,
  `GameId` int NOT NULL,
  `SystemId` int NOT NULL,
  `Description` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `ScanLevel` int NOT NULL DEFAULT '1',
  `AnomalyLevel` int NOT NULL DEFAULT '1',
  `Name` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Reward` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `Notes` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `Comments` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Who` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Complete` int NOT NULL,
  `StoryLevel` int NOT NULL,
  `OtherReq` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Properties` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
