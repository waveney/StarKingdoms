CREATE TABLE `LinkLevel` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Level` int NOT NULL,
  `Colour` text CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  `Notes` text CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  `GameId` int NOT NULL,
  `Name` text CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  `Cost` int NOT NULL,
  `AgentCost` int NOT NULL,
  `MakeCost` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
