CREATE TABLE `FollowUp` (
  `id` int NOT NULL AUTO_INCREMENT,
  `GameId` int NOT NULL,
  `Turn` int NOT NULL,
  `FactionId` int NOT NULL,
  `State` int NOT NULL,
  `ActionNeeded` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `Notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_bin;
