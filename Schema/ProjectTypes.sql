CREATE TABLE `ProjectTypes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Category` int NOT NULL,
  `Name` text CHARACTER SET latin1 COLLATE latin1_bin,
  `Description` text CHARACTER SET latin1 COLLATE latin1_bin,
  `StandardCosts` int NOT NULL DEFAULT '1',
  `Level` int NOT NULL,
  `Cost` int NOT NULL,
  `CompTarget` int NOT NULL,
  `Props` int NOT NULL DEFAULT '1',
  `BasedOn` int NOT NULL DEFAULT '0',
  `NotBy` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_bin;
