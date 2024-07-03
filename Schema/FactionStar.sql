CREATE TABLE `FactionStar` (
  `id` int NOT NULL AUTO_INCREMENT,
  `SystemId` int NOT NULL,
  `StarNumber` int NOT NULL,
  `Faction` int NOT NULL,
  `Name` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
