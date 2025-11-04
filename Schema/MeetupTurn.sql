CREATE TABLE `MeetupTurn` (
  `id` int NOT NULL AUTO_INCREMENT,
  `GameId` int NOT NULL,
  `SystemId` int NOT NULL,
  `Ground` int NOT NULL,
  `Space` int NOT NULL,
  `Turn` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
