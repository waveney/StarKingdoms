CREATE TABLE `Resources` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Type` int NOT NULL,
  `Whose` int NOT NULL,
  `Value` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
