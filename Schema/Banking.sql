CREATE TABLE `Banking` (
  `id` int NOT NULL AUTO_INCREMENT,
  `FactionId` int NOT NULL,
  `Recipient` int NOT NULL,
  `Amount` int NOT NULL,
  `YourRef` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Name` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `StartTurn` int NOT NULL,
  `EndTurn` int NOT NULL,
  `What` int NOT NULL,
  `DoneTurn` int NOT NULL,
  `DecayRate` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
