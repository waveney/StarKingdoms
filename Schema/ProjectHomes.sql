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
  `Props` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_bin;
