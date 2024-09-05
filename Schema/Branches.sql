CREATE TABLE `Branches` (
  `id` int NOT NULL AUTO_INCREMENT,
  `HostType` int NOT NULL,
  `HostId` int NOT NULL,
  `Whose` int NOT NULL,
  `Type` int NOT NULL,
  `GameId` int NOT NULL,
  `Name` text COLLATE utf8mb4_general_ci NOT NULL,
  `Organisation` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
