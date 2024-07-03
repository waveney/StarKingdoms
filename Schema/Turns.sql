CREATE TABLE `Turns` (
  `id` int NOT NULL AUTO_INCREMENT,
  `GameId` int NOT NULL,
  `TurnNumber` int NOT NULL DEFAULT '0',
  `Progress` bigint NOT NULL DEFAULT '0',
  `ActivityLog` text CHARACTER SET latin1 COLLATE latin1_bin,
  `DateCompleted` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_bin;
