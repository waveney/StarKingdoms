CREATE TABLE `Links` (
  `id` int NOT NULL AUTO_INCREMENT,
  `GameId` int NOT NULL,
  `System1Ref` text CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  `System2Ref` text CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  `Level` int NOT NULL,
  `FakeOrig` int DEFAULT NULL,
  `Knowledge` int DEFAULT NULL,
  `Notes` text CHARACTER SET latin1 COLLATE latin1_general_ci,
  `UseCount` int NOT NULL,
  `MinedA` int NOT NULL,
  `MinedB` int NOT NULL,
  `Status` int NOT NULL,
  `Weight` int NOT NULL DEFAULT '1',
  `Whose` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
