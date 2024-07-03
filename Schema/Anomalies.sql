CREATE TABLE `Anomalies` (
  `id` int NOT NULL AUTO_INCREMENT,
  `GameId` int NOT NULL,
  `SystemId` int NOT NULL,
  `Description` text CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
  `ScanLevel` int NOT NULL DEFAULT '1',
  `AnomalyLevel` int NOT NULL DEFAULT '1',
  `Name` text CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
  `Reward` text CHARACTER SET latin1 COLLATE latin1_bin,
  `Notes` text CHARACTER SET latin1 COLLATE latin1_bin,
  `Comments` text CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
  `Who` text CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
  `Complete` int NOT NULL,
  `StoryLevel` int NOT NULL,
  `OtherReq` text CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
  `Properties` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_bin;
