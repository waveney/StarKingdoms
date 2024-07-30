CREATE TABLE `LinkInstaLevel` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Style` text COLLATE utf8mb4_general_ci NOT NULL,
  `Width` int NOT NULL,
  `Instability` int NOT NULL,
  `NotBy` int NOT NULL,
  `Colour` text COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
