CREATE TABLE `OrgActions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Name` text COLLATE utf8mb4_general_ci NOT NULL,
  `Office` int NOT NULL,
  `Props` int NOT NULL,
  `NotBy` int NOT NULL,
  `Description` text COLLATE utf8mb4_general_ci NOT NULL,
  `Gate` text COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
