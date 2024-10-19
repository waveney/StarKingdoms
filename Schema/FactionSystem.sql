CREATE TABLE `FactionSystem` (
  `id` int NOT NULL AUTO_INCREMENT,
  `FactionId` int NOT NULL,
  `ScanLevel` int DEFAULT '0',
  `Notes` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `SystemId` int NOT NULL,
  `Name` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `ShortName` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `NebScanned` int NOT NULL DEFAULT '0' COMMENT 'Not Used',
  `Xlabel` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `MaxScan` int NOT NULL COMMENT 'Not Used',
  `PassiveScan` int NOT NULL COMMENT 'Not Used\r\n',
  `SpaceScan` int NOT NULL,
  `PlanetScan` int NOT NULL,
  `Star1Name` text COLLATE utf8mb4_general_ci NOT NULL,
  `Star2Name` text COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
