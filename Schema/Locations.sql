CREATE TABLE `Locations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `SystemId` int NOT NULL,
  `WithinSys` int NOT NULL,
  `LocType` int NOT NULL,
  `LocId` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
