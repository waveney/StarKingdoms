CREATE TABLE `FactionFaction` (
  `id` int NOT NULL AUTO_INCREMENT,
  `FactionId1` int NOT NULL,
  `FactionId2` int NOT NULL,
  `Props` int NOT NULL,
  `GameId` int NOT NULL,
  `Relationship` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
