CREATE TABLE `FollowUp` (
  `id` int NOT NULL AUTO_INCREMENT,
  `GameId` int NOT NULL,
  `Turn` int NOT NULL,
  `FactionId` int NOT NULL,
  `State` int NOT NULL,
  `ActionNeeded` text CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  `Notes` text CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_bin;
