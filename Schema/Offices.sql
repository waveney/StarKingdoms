CREATE TABLE `Offices` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Type` int NOT NULL,
  `SubType` int NOT NULL,
  `World` int NOT NULL,
  `Whose` int NOT NULL,
  `Notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Number` int NOT NULL,
  `GameId` int NOT NULL,
  `Concealment` int NOT NULL,
  `Organisation` int NOT NULL,
  `Name` text COLLATE utf8mb4_general_ci NOT NULL,
  `OrgType` int NOT NULL,
  `OrgType2` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
