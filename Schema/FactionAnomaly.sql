CREATE TABLE `FactionAnomaly` (
  `id` int NOT NULL AUTO_INCREMENT,
  `FactionId` int NOT NULL,
  `State` int NOT NULL,
  `Notes` text CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
  `AnomalyId` int NOT NULL,
  `Progress` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_bin;
