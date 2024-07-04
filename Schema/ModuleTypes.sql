CREATE TABLE `ModuleTypes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Name` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `Description` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `CivMil` int NOT NULL,
  `BasedOn` int NOT NULL,
  `SpaceUsed` int NOT NULL DEFAULT '1',
  `Notes` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `FireOrder` int NOT NULL DEFAULT '5',
  `FireRate` int NOT NULL DEFAULT '1',
  `Formula` int DEFAULT '0',
  `MinShipLevel` int NOT NULL DEFAULT '0',
  `DefWep` int NOT NULL DEFAULT '0',
  `Leveled` int NOT NULL,
  `NotBy` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
