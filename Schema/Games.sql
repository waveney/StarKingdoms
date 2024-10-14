CREATE TABLE `Games` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Name` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Turn` int NOT NULL DEFAULT '0',
  `Notes` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `Features` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `DateCompleted` int NOT NULL,
  `GM1` int NOT NULL,
  `GM2` int NOT NULL,
  `GM3` int NOT NULL,
  `Status` int NOT NULL,
  `CodePrefix` mediumtext COLLATE utf8mb4_general_ci NOT NULL,
  `AccessKey` mediumtext COLLATE utf8mb4_general_ci NOT NULL,
  `Image` text COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
