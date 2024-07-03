CREATE TABLE `ThingTurn` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ThingId` int NOT NULL,
  `Turn` int NOT NULL,
  `Action` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_bin;
