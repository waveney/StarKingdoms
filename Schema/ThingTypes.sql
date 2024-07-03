CREATE TABLE `ThingTypes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Name` text CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
  `Properties` int NOT NULL DEFAULT '0',
  `Gate` text CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
  `Eyes` int NOT NULL,
  `SeenBy` int NOT NULL,
  `MaxLvl` int NOT NULL DEFAULT '10',
  `GameId` int NOT NULL,
  `NotBy` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_bin;
