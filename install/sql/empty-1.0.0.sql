DROP TABLE IF EXISTS `glpi_plugin_taskrules_rules`;
CREATE TABLE IF NOT EXISTS `glpi_plugin_taskrules_rules` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `active` tinyint NOT NULL DEFAULT '0',
  `tasktemplates_id` int UNSIGNED NOT NULL DEFAULT '0',
  `trigger` tinyint NOT NULL DEFAULT '0' COMMENT '1 (add task) 2 (changing from [to do] to [done])',
  `states_id` int UNSIGNED NOT NULL DEFAULT '0',
  `locations_id` int UNSIGNED NOT NULL DEFAULT '0',
  `assetcontact` tinyint NOT NULL DEFAULT '0' COMMENT 'Use contact defined for the asset',
  `contact` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `users_id` int UNSIGNED NOT NULL DEFAULT '0',
  `groups_id` int UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `states_id` (`states_id`),
  KEY `locations_id` (`locations_id`),
  KEY `users_id` (`users_id`),
  KEY `groups_id` (`groups_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_taskrules_conditions`;
CREATE TABLE IF NOT EXISTS `glpi_plugin_taskrules_conditions` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `taskrules_id` int UNSIGNED NOT NULL DEFAULT '0',
  `operator` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `groupoperator` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `itemtype` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `objecttypes_id` int UNSIGNED NOT NULL DEFAULT '0',
  `states_id` int UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `taskrules_id` (`taskrules_id`),
  KEY `objecttypes_id` (`objecttypes_id`),
  KEY `states_id` (`states_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;