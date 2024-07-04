CREATE TABLE `FactionTechs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Faction_Id` int NOT NULL,
  `Tech_Id` int NOT NULL,
  `Level` int NOT NULL,
  `Notes` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `GM_Notes` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `StartTurn` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
