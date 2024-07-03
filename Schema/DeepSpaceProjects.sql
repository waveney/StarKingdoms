CREATE TABLE `DeepSpaceProjects` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Name` text CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
  `Level` int NOT NULL DEFAULT '1',
  `Gate` text CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
  `ConstRating` int NOT NULL DEFAULT '1',
  `Public` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_bin;
