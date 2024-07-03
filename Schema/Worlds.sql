CREATE TABLE `Worlds` (
  `id` int NOT NULL AUTO_INCREMENT,
  `FactionId` int NOT NULL,
  `Home` int NOT NULL,
  `Minerals` int NOT NULL,
  `RelOrder` int NOT NULL,
  `ThingType` int NOT NULL,
  `ThingId` int NOT NULL,
  `Conflict` int NOT NULL,
  `Blockade` int NOT NULL,
  `Revolt` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_bin;
