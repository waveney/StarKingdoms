CREATE TABLE `PlanetTypes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Name` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Hospitable` int NOT NULL DEFAULT '0',
  `MoonFactor` double NOT NULL DEFAULT '1',
  `Append` int NOT NULL DEFAULT '1',
  `NotBy` int NOT NULL,
  `ImgPrefix` text COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
