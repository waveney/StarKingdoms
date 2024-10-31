CREATE TABLE `RepeatFollow` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Name` text COLLATE utf8mb4_general_ci NOT NULL,
  `Status` int NOT NULL,
  `GameId` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
