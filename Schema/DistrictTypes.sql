CREATE TABLE `DistrictTypes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Name` mediumtext COLLATE utf8mb4_general_ci NOT NULL,
  `Props` int NOT NULL DEFAULT '0',
  `SpaceUsed` int NOT NULL DEFAULT '1',
  `Notes` mediumtext COLLATE utf8mb4_general_ci,
  `BasedOn` int NOT NULL DEFAULT '0',
  `Gate` mediumtext COLLATE utf8mb4_general_ci NOT NULL,
  `MaxNum` int NOT NULL DEFAULT '1000000',
  `NotBy` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
