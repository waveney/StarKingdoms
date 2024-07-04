CREATE TABLE `GamePlayers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `GameId` int NOT NULL,
  `PlayerId` int NOT NULL,
  `Type` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
