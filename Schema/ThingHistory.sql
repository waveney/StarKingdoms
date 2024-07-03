CREATE TABLE `ThingHistory` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ThingId` int NOT NULL,
  `TurnNum` int NOT NULL,
  `Text` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
