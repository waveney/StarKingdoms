CREATE TABLE `Organisations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Whose` int NOT NULL,
  `Name` text COLLATE utf8mb4_general_ci NOT NULL,
  `Description` text COLLATE utf8mb4_general_ci NOT NULL,
  `OfficeCount` int NOT NULL,
  `OrgType` int NOT NULL,
  `GameId` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
