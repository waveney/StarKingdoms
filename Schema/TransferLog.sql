CREATE TABLE `TransferLog` (
  `id` int NOT NULL AUTO_INCREMENT,
  `FromFact` int NOT NULL,
  `DestFact` int NOT NULL,
  `SystemId` int NOT NULL,
  `Survey` int NOT NULL,
  `Turn` int NOT NULL,
  `XferWhen` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
