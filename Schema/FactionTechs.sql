CREATE TABLE `FactionTechs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Faction_Id` int NOT NULL,
  `Tech_Id` int NOT NULL,
  `Level` int NOT NULL,
  `Notes` text CHARACTER SET latin1 COLLATE latin1_general_ci,
  `GM_Notes` text CHARACTER SET latin1 COLLATE latin1_general_ci,
  `StartTurn` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
