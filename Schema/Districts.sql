CREATE TABLE `Districts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `HostType` int NOT NULL DEFAULT '0',
  `HostId` int NOT NULL DEFAULT '0',
  `Type` int NOT NULL,
  `Number` int NOT NULL DEFAULT '0',
  `GameId` int NOT NULL,
  `TurnStart` int NOT NULL,
  `Devastation` int NOT NULL,
  `Delta` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
