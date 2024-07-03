CREATE TABLE `Modules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ThingId` int NOT NULL,
  `Type` int NOT NULL DEFAULT '0',
  `Number` int NOT NULL DEFAULT '0' COMMENT 'Inactive if negative',
  `Level` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_bin;
