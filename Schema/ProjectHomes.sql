CREATE TABLE `ProjectHomes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ThingType` int NOT NULL,
  `ThingId` int NOT NULL,
  `Whose` int NOT NULL DEFAULT '0',
  `Economy` int NOT NULL,
  `SystemId` int NOT NULL,
  `WithinSysLoc` int NOT NULL,
  `Devastation` int NOT NULL,
  `EconomyFactor` int NOT NULL DEFAULT '100',
  `EconomyMod` int NOT NULL,
  `Props` int NOT NULL,
  `GameId` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
