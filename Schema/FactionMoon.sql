CREATE TABLE `FactionMoon` (
  `id` int NOT NULL AUTO_INCREMENT,
  `FactionId` int NOT NULL,
  `Moon` int NOT NULL,
  `Name` text CHARACTER SET latin1 COLLATE latin1_general_ci,
  `ShortName` text CHARACTER SET latin1 COLLATE latin1_general_ci,
  `Control` int DEFAULT NULL,
  `Notes` text CHARACTER SET latin1 COLLATE latin1_general_ci,
  `ScanLevel` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
