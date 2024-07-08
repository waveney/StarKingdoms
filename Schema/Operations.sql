CREATE TABLE `Operations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Whose` int NOT NULL,
  `Type` int NOT NULL,
  `Para1` int NOT NULL,
  `Para2` int NOT NULL,
  `SystemId` int NOT NULL,
  `Progress` int NOT NULL,
  `State` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
