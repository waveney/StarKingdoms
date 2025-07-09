CREATE TABLE `FluxCrystalUse` (
  `id` int NOT NULL AUTO_INCREMENT,
  `GameId` int NOT NULL,
  `Turn` int NOT NULL,
  `LinkId` int NOT NULL,
  `FactionId` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
