CREATE TABLE `Games` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Name` text CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  `Turn` int NOT NULL DEFAULT '0',
  `Notes` text CHARACTER SET latin1 COLLATE latin1_general_ci,
  `Features` text CHARACTER SET latin1 COLLATE latin1_general_ci,
  `DateCompleted` int NOT NULL,
  `GM1` int NOT NULL,
  `GM2` int NOT NULL,
  `GM3` int NOT NULL,
  `Status` int NOT NULL,
  `CodePrefix` text COLLATE latin1_general_ci NOT NULL,
  `AccessKey` text COLLATE latin1_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
