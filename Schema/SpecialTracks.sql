CREATE TABLE `SpecialTracks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Whose` int NOT NULL,
  `Name` text COLLATE utf8mb4_general_ci NOT NULL,
  `Value` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
