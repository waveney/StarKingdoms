CREATE TABLE `FutureTechLevels` (
  `id` int NOT NULL AUTO_INCREMENT,
  `FactionId` int NOT NULL,
  `Tech_Id` int NOT NULL,
  `Level` int NOT NULL,
  `StartTurn` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
