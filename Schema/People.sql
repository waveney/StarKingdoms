CREATE TABLE `People` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Name` text CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  `Login` text CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  `Email` text CHARACTER SET latin1 COLLATE latin1_general_ci,
  `AccessLevel` int NOT NULL,
  `AccessKey` text CHARACTER SET latin1 COLLATE latin1_general_ci,
  `FactionId` int NOT NULL DEFAULT '0',
  `Password` text CHARACTER SET latin1 COLLATE latin1_general_ci,
  `Yale` text CHARACTER SET latin1 COLLATE latin1_general_ci,
  `LogUse` int NOT NULL DEFAULT '0',
  `LastGame` int NOT NULL,
  `LastAccess` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
