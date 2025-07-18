DROP TABLE IF EXISTS `glpi_plugin_taskrules_rules`;
CREATE TABLE IF NOT EXISTS `glpi_plugin_taskrules_rules` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `tasktemplates_id` int UNSIGNED NOT NULL DEFAULT '0',
  `active` tinyint NOT NULL DEFAULT '0',
  `preventcreation` tinyint NOT NULL DEFAULT '0',
  `preventchange` tinyint NOT NULL DEFAULT '0',
  `trigger_creation` tinyint NOT NULL DEFAULT '0',
  `trigger_update` tinyint NOT NULL DEFAULT '0',
  `state_action` int UNSIGNED NOT NULL DEFAULT '0',
  `states_id` int UNSIGNED NOT NULL DEFAULT '0',
  `location_action` int UNSIGNED NOT NULL DEFAULT '0',
  `locations_id` int UNSIGNED NOT NULL DEFAULT '0',
  `contact_action` int UNSIGNED NOT NULL DEFAULT '0',
  `user_action` int UNSIGNED NOT NULL DEFAULT '0',
  `group_action` int UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `tasktemplates_id` (`tasktemplates_id`),
  KEY `states_id` (`states_id`),
  KEY `locations_id` (`locations_id`)
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
