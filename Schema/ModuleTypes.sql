CREATE TABLE `ModuleTypes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Name` text CHARACTER SET latin1 COLLATE latin1_bin,
  `Description` text CHARACTER SET latin1 COLLATE latin1_bin,
  `CivMil` int NOT NULL,
  `BasedOn` int NOT NULL,
  `SpaceUsed` int NOT NULL DEFAULT '1',
  `Notes` text CHARACTER SET latin1 COLLATE latin1_bin,
  `FireOrder` int NOT NULL DEFAULT '5',
  `FireRate` int NOT NULL DEFAULT '1',
  `Formula` int DEFAULT '0',
  `MinShipLevel` int NOT NULL DEFAULT '0',
  `DefWep` int NOT NULL DEFAULT '0',
  `Leveled` int NOT NULL,
  `NotBy` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_bin;
