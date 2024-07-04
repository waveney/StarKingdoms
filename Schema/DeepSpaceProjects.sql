CREATE TABLE `DeepSpaceProjects` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Name` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Level` int NOT NULL DEFAULT '1',
  `Gate` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `ConstRating` int NOT NULL DEFAULT '1',
  `Public` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
