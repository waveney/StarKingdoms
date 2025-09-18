CREATE TABLE `ModFormulae` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Name` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `BaseTech` int NOT NULL DEFAULT '0',
  `Num1x` int NOT NULL DEFAULT '0',
  `Num2x` int NOT NULL DEFAULT '0',
  `Num3x` int NOT NULL DEFAULT '0',
  `Num4x` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
