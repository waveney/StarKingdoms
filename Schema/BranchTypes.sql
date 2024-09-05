CREATE TABLE `BranchTypes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Name` text COLLATE utf8mb4_general_ci NOT NULL,
  `OrgType` int NOT NULL,
  `Props` int NOT NULL,
  `NotBy` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
