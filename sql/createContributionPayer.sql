CREATE TABLE `sep_contribution_payer` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `contribution_id` int(10) unsigned DEFAULT NULL,
  `contact_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
