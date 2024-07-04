CREATE TABLE `Turns` (
  `id` int NOT NULL AUTO_INCREMENT,
  `GameId` int NOT NULL,
  `TurnNumber` int NOT NULL DEFAULT '0',
  `Progress` bigint NOT NULL DEFAULT '0',
  `ActivityLog` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `DateCompleted` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
