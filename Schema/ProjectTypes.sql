CREATE TABLE `ProjectTypes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Category` int NOT NULL,
  `Name` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `Description` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `StandardCosts` int NOT NULL DEFAULT '1',
  `Level` int NOT NULL,
  `Cost` int NOT NULL,
  `CompTarget` int NOT NULL,
  `Props` int NOT NULL DEFAULT '1',
  `BasedOn` int NOT NULL DEFAULT '0',
  `NotBy` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
