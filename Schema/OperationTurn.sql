CREATE TABLE `OperationTurn` (
  `id` int NOT NULL AUTO_INCREMENT,
  `OperationId` int NOT NULL,
  `TurnNumber` int NOT NULL,
  `Rush` int NOT NULL,
  `Bonus` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
