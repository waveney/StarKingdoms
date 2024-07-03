CREATE TABLE `ModFormulae` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Name` text CHARACTER SET latin1 COLLATE latin1_bin,
  `BaseTech` int NOT NULL DEFAULT '0',
  `Num1x` int NOT NULL DEFAULT '0',
  `Num2x` int NOT NULL DEFAULT '0',
  `Num3x` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_bin;
