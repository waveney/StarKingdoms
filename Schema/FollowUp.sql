CREATE TABLE `FollowUp` (
  `id` int NOT NULL AUTO_INCREMENT,
  `GameId` int NOT NULL,
  `Turn` int NOT NULL,
  `FactionId` int NOT NULL,
  `State` int NOT NULL,
  `ActionNeeded` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Notes` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
