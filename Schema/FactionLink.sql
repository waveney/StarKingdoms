CREATE TABLE `FactionLink` (
  `id` int NOT NULL AUTO_INCREMENT,
  `LinkId` int NOT NULL,
  `FactionId` int NOT NULL,
  `Known` int DEFAULT NULL,
  `Notes` text CHARACTER SET latin1 COLLATE latin1_general_ci,
  `Name` text CHARACTER SET latin1 COLLATE latin1_general_ci,
  `ShortName` text CHARACTER SET latin1 COLLATE latin1_general_ci,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
