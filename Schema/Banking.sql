CREATE TABLE `Banking` (
  `id` int NOT NULL AUTO_INCREMENT,
  `FactionId` int NOT NULL,
  `Recipient` int NOT NULL,
  `Amount` int NOT NULL,
  `YourRef` text CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
  `Name` text CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
  `StartTurn` int NOT NULL,
  `EndTurn` int NOT NULL,
  `What` int NOT NULL,
  `DoneTurn` int NOT NULL,
  `DecayRate` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_bin;
