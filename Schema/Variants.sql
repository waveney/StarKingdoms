CREATE TABLE `Variants` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Name` text COLLATE utf8mb4_general_ci NOT NULL,
  `NotBy` int NOT NULL,
  `Props` int NOT NULL,
  `Firepower` int NOT NULL,
  `Evasion` int NOT NULL,
  `TargetEvasion` int NOT NULL,
  `EvasionType` tinyint NOT NULL,
  `FireType` tinyint NOT NULL,
  `TargetType` tinyint NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
