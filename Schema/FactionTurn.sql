CREATE TABLE `FactionTurn` (
  `id` int NOT NULL AUTO_INCREMENT,
  `FactionId` int NOT NULL,
  `Turn` int NOT NULL,
  `IncomeText` text CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
  `IncomeAmount` int NOT NULL,
  `TurnLink` int NOT NULL,
  `Notes` text CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
  `EndCredits` int NOT NULL,
  `Outposts` int NOT NULL,
  `AstMines` int NOT NULL,
  `Embassies` int NOT NULL,
  `ForeignEmbassies` int NOT NULL,
  `ShipLogistics` int NOT NULL,
  `ArmyLogistics` int NOT NULL,
  `AgentLogistics` int NOT NULL,
  `Economy` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_bin;
