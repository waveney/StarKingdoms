CREATE TABLE `MasterData` (
  `id` int NOT NULL AUTO_INCREMENT,
  `CurGame` int NOT NULL,
  `Notes` text CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
  `Features` text CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
  `Capabilities` text CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
  `CurVersion` text COLLATE latin1_bin NOT NULL,
  `VersionDate` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_bin;
