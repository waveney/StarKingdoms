CREATE TABLE `LocationSave` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ThingId` int NOT NULL,
  `SystemId` int NOT NULL,
  `Turn` int NOT NULL,
  `BuildState` int NOT NULL,
  `CurHealth` int NOT NULL,
  `Game` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_bin;
