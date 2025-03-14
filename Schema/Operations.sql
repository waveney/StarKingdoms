CREATE TABLE `Operations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Whose` int NOT NULL,
  `Type` int NOT NULL,
  `Para1` int NOT NULL,
  `Para2` int NOT NULL,
  `SystemId` int NOT NULL,
  `Progress` int NOT NULL,
  `Status` int NOT NULL,
  `GameId` int NOT NULL,
  `Name` text COLLATE utf8mb4_general_ci NOT NULL,
  `TurnStart` int NOT NULL,
  `TurnEnd` int NOT NULL,
  `ProgNeeded` int NOT NULL,
  `Level` int NOT NULL,
  `Costs` int NOT NULL,
  `LastUpdate` int NOT NULL,
  `GMOverride` int NOT NULL,
  `GMLock` int NOT NULL,
  `FreeRushes` int NOT NULL,
  `Notes` text COLLATE utf8mb4_general_ci NOT NULL,
  `ThingId` int NOT NULL,
  `OrgId` int NOT NULL,
  `Description` text COLLATE utf8mb4_general_ci NOT NULL,
  `TurnState` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
