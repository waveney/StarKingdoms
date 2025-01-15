CREATE TABLE `ThingTypes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Name` mediumtext COLLATE utf8mb4_general_ci NOT NULL,
  `Properties` int NOT NULL DEFAULT '0',
  `Prop2` int NOT NULL,
  `Gate` mediumtext COLLATE utf8mb4_general_ci NOT NULL,
  `Eyes` int NOT NULL,
  `SeenBy` int NOT NULL,
  `MaxLvl` int NOT NULL DEFAULT '10',
  `GameId` int NOT NULL,
  `NotBy` int NOT NULL,
  `EvasionMod` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
