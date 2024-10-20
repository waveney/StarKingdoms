CREATE TABLE `FactionSocialP` (
  `id` int NOT NULL AUTO_INCREMENT,
  `FactionId` int NOT NULL,
  `World` int NOT NULL,
  `SocP` int NOT NULL,
  `Value` int NOT NULL,
  `GameId` int NOT NULL,
  `Turn` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
