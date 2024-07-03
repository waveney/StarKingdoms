CREATE TABLE `FactionSystem` (
  `id` int NOT NULL AUTO_INCREMENT,
  `FactionId` int NOT NULL,
  `ScanLevel` int DEFAULT '0',
  `Notes` text CHARACTER SET latin1 COLLATE latin1_general_ci,
  `SystemId` int NOT NULL,
  `Name` text CHARACTER SET latin1 COLLATE latin1_general_ci,
  `ShortName` text CHARACTER SET latin1 COLLATE latin1_general_ci,
  `NebScanned` int NOT NULL DEFAULT '0',
  `Xlabel` text CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  `MaxScan` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
