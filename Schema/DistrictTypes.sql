CREATE TABLE `DistrictTypes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Name` text CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
  `Props` int NOT NULL DEFAULT '0',
  `SpaceUsed` int NOT NULL DEFAULT '1',
  `Notes` text CHARACTER SET latin1 COLLATE latin1_bin,
  `BasedOn` int NOT NULL DEFAULT '0',
  `Gate` text CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
  `MaxNum` int NOT NULL DEFAULT '1000000',
  `NotBy` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_bin;
