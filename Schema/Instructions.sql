CREATE TABLE `Instructions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Name` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Props` int NOT NULL,
  `Actions` int NOT NULL,
  `Cost` int NOT NULL,
  `Gate` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Message` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
