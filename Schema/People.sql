CREATE TABLE `People` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Name` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Login` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Email` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `AccessLevel` int NOT NULL,
  `AccessKey` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `FactionId` int NOT NULL DEFAULT '0',
  `Password` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `Yale` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `LogUse` int NOT NULL DEFAULT '0',
  `LastGame` int NOT NULL,
  `LastAccess` int NOT NULL,
  `AKA` text COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
