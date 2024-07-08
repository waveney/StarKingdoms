CREATE TABLE `SocialPrinciples` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Home` int NOT NULL,
  `Principle` text COLLATE utf8mb4_general_ci NOT NULL,
  `Value` int NOT NULL,
  `Automated` int NOT NULL,
  `Props` int NOT NULL,
  `Whose` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
