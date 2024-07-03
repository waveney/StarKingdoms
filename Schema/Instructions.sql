CREATE TABLE `Instructions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Name` text CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
  `Props` int NOT NULL,
  `Actions` int NOT NULL,
  `Cost` int NOT NULL,
  `Gate` text CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
  `Message` text CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_bin;
