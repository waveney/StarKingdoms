CREATE TABLE `ScansDue` (
  `id` int NOT NULL AUTO_INCREMENT,
  `FactionId` int NOT NULL,
  `Sys` int NOT NULL,
  `Scan` int NOT NULL,
  `Neb` int NOT NULL,
  `Turn` int NOT NULL,
  `ThingId` int NOT NULL,
  `Type` int NOT NULL,
  `GameId` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
