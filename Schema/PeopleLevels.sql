CREATE TABLE `PeopleLevels` (
  `Level` int NOT NULL,
  `Name` text CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  `AccessKey` text CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  `Notes` text CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  PRIMARY KEY (`Level`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
