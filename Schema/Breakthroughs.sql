CREATE TABLE `Breakthroughs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `FactionId` int NOT NULL,
  `Game` int NOT NULL,
  `Turn` int NOT NULL,
  `TechId` int NOT NULL,
  `Level` int NOT NULL,
  `Cost` int NOT NULL,
  `Field` int NOT NULL,
  `DoneTurn` int NOT NULL,
  `FieldName` text COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
