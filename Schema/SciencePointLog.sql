CREATE TABLE `SciencePointLog` (
  `id` int NOT NULL AUTO_INCREMENT,
  `GameId` int NOT NULL,
  `Turn` int NOT NULL,
  `FactionId` int NOT NULL,
  `Type` int NOT NULL,
  `Number` int NOT NULL,
  `Note` text COLLATE utf8mb4_general_ci NOT NULL,
  `StartVal` int NOT NULL,
  `EndVal` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
