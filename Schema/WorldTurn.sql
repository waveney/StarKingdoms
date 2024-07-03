CREATE TABLE `WorldTurn` (
  `id` int NOT NULL AUTO_INCREMENT,
  `WorldId` int NOT NULL,
  `Turn` int NOT NULL,
  `Commerce` int NOT NULL,
  `Academic` int NOT NULL,
  `Shipyard` int NOT NULL,
  `Military` int NOT NULL,
  `Intelligence` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_bin;
