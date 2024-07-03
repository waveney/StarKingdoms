CREATE TABLE `PlanetTypes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Name` text CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
  `Hospitable` int NOT NULL DEFAULT '0',
  `MoonFactor` double NOT NULL DEFAULT '1',
  `Append` int NOT NULL DEFAULT '1',
  `NotBy` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_bin;
