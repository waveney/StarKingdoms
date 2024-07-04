CREATE TABLE `CreditLog` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Whose` int NOT NULL,
  `StartCredits` int NOT NULL,
  `Amount` int NOT NULL,
  `EndCredits` int NOT NULL,
  `YourRef` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Turn` int NOT NULL,
  `Status` int NOT NULL,
  `FromWho` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
