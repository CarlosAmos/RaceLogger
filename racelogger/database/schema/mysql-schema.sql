/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `calendar_races`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `calendar_races` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `is_locked` tinyint(1) NOT NULL DEFAULT 0,
  `season_id` bigint(20) unsigned NOT NULL,
  `track_layout_id` bigint(20) unsigned NOT NULL,
  `sprint_race` tinyint(1) NOT NULL DEFAULT 0,
  `round_number` int(11) NOT NULL,
  `gp_name` varchar(255) NOT NULL,
  `race_code` varchar(3) NOT NULL,
  `race_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `point_system_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `calendar_races_season_id_round_number_unique` (`season_id`,`round_number`),
  KEY `calendar_races_track_layout_id_foreign` (`track_layout_id`),
  KEY `calendar_races_season_id_index` (`season_id`),
  KEY `calendar_races_season_id_round_number_index` (`season_id`,`round_number`),
  KEY `calendar_races_point_system_id_foreign` (`point_system_id`),
  CONSTRAINT `calendar_races_point_system_id_foreign` FOREIGN KEY (`point_system_id`) REFERENCES `point_systems` (`id`) ON DELETE SET NULL,
  CONSTRAINT `calendar_races_season_id_foreign` FOREIGN KEY (`season_id`) REFERENCES `seasons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `calendar_races_track_layout_id_foreign` FOREIGN KEY (`track_layout_id`) REFERENCES `track_layouts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `car_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `car_entries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `season_team_entry_id` bigint(20) unsigned NOT NULL,
  `car_model_name` varchar(255) NOT NULL,
  `number` varchar(10) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `car_entries_season_team_entry_id_index` (`season_team_entry_id`),
  CONSTRAINT `car_entries_season_team_entry_id_foreign` FOREIGN KEY (`season_team_entry_id`) REFERENCES `season_team_entries` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `car_models`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `car_models` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `constructor_id` bigint(20) unsigned NOT NULL,
  `engine_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `year` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `car_models_constructor_id_name_unique` (`constructor_id`,`name`),
  KEY `car_models_engine_id_foreign` (`engine_id`),
  CONSTRAINT `car_models_constructor_id_foreign` FOREIGN KEY (`constructor_id`) REFERENCES `constructors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `car_models_engine_id_foreign` FOREIGN KEY (`engine_id`) REFERENCES `engines` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cars`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cars` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entry_class_id` bigint(20) unsigned NOT NULL,
  `car_number` varchar(255) NOT NULL,
  `model` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cars_entry_class_id_foreign` (`entry_class_id`),
  CONSTRAINT `cars_entry_class_id_foreign` FOREIGN KEY (`entry_class_id`) REFERENCES `entry_classes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `constructors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `constructors` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `world_id` bigint(20) unsigned NOT NULL,
  `country_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `color` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `constructors_world_id_foreign` (`world_id`),
  KEY `constructors_country_id_foreign` (`country_id`),
  CONSTRAINT `constructors_country_id_foreign` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE SET NULL,
  CONSTRAINT `constructors_world_id_foreign` FOREIGN KEY (`world_id`) REFERENCES `worlds` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `countries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `countries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `iso_code` varchar(3) DEFAULT NULL,
  `continent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `countries_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `drivers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `drivers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `world_id` bigint(20) unsigned NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `country_id` bigint(20) unsigned NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `drivers_world_id_foreign` (`world_id`),
  KEY `drivers_country_id_foreign` (`country_id`),
  CONSTRAINT `drivers_country_id_foreign` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE CASCADE,
  CONSTRAINT `drivers_world_id_foreign` FOREIGN KEY (`world_id`) REFERENCES `worlds` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `engine_suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `engine_suppliers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `manufacturer` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `engine_suppliers_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `engines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `engines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `world_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `configuration` varchar(255) DEFAULT NULL,
  `capacity` varchar(255) DEFAULT NULL,
  `hybrid` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `engines_world_id_foreign` (`world_id`),
  CONSTRAINT `engines_world_id_foreign` FOREIGN KEY (`world_id`) REFERENCES `worlds` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `entrants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `entrants` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `world_id` bigint(20) unsigned NOT NULL DEFAULT 1,
  `country_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `entrants_world_id_name_unique` (`world_id`,`name`),
  KEY `entrants_country_id_foreign` (`country_id`),
  CONSTRAINT `entrants_country_id_foreign` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE SET NULL,
  CONSTRAINT `entrants_world_id_foreign` FOREIGN KEY (`world_id`) REFERENCES `worlds` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `entries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `constructor_id` bigint(20) unsigned NOT NULL,
  `season_id` bigint(20) unsigned NOT NULL,
  `series_id` bigint(20) unsigned NOT NULL,
  `display_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `entries_constructor_id_foreign` (`constructor_id`),
  KEY `entries_season_id_foreign` (`season_id`),
  KEY `entries_series_id_foreign` (`series_id`),
  CONSTRAINT `entries_constructor_id_foreign` FOREIGN KEY (`constructor_id`) REFERENCES `constructors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `entries_season_id_foreign` FOREIGN KEY (`season_id`) REFERENCES `seasons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `entries_series_id_foreign` FOREIGN KEY (`series_id`) REFERENCES `series` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `entry_car_driver`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `entry_car_driver` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entry_car_id` bigint(20) unsigned NOT NULL,
  `driver_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `entry_car_driver_entry_car_id_driver_id_unique` (`entry_car_id`,`driver_id`),
  KEY `entry_car_driver_driver_id_foreign` (`driver_id`),
  CONSTRAINT `entry_car_driver_driver_id_foreign` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `entry_car_driver_entry_car_id_foreign` FOREIGN KEY (`entry_car_id`) REFERENCES `entry_cars` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `entry_cars`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `entry_cars` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `season_entry_id` bigint(20) unsigned DEFAULT NULL,
  `entry_class_id` bigint(20) unsigned NOT NULL,
  `car_model_id` bigint(20) unsigned NOT NULL,
  `car_number` varchar(255) NOT NULL,
  `livery_name` varchar(255) DEFAULT NULL,
  `chassis_code` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `entry_cars_entry_class_id_foreign` (`entry_class_id`),
  KEY `entry_cars_car_model_id_foreign` (`car_model_id`),
  CONSTRAINT `entry_cars_car_model_id_foreign` FOREIGN KEY (`car_model_id`) REFERENCES `car_models` (`id`) ON DELETE CASCADE,
  CONSTRAINT `entry_cars_entry_class_id_foreign` FOREIGN KEY (`entry_class_id`) REFERENCES `entry_classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `entry_cars_season_entry_id_foreign` FOREIGN KEY (`season_entry_id`) REFERENCES `season_entries` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `entry_classes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `entry_classes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `season_entry_id` bigint(20) unsigned NOT NULL,
  `race_class_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `entry_classes_season_entry_id_race_class_id_unique` (`season_entry_id`,`race_class_id`),
  KEY `entry_classes_race_class_id_foreign` (`race_class_id`),
  CONSTRAINT `entry_classes_race_class_id_foreign` FOREIGN KEY (`race_class_id`) REFERENCES `season_classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `entry_classes_season_entry_id_foreign` FOREIGN KEY (`season_entry_id`) REFERENCES `season_entries` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `event_points_system_overrides`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event_points_system_overrides` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `calendar_race_id` bigint(20) unsigned NOT NULL,
  `points_system_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `event_points_system_overrides_calendar_race_id_foreign` (`calendar_race_id`),
  KEY `event_points_system_overrides_points_system_id_foreign` (`points_system_id`),
  CONSTRAINT `event_points_system_overrides_calendar_race_id_foreign` FOREIGN KEY (`calendar_race_id`) REFERENCES `calendar_races` (`id`) ON DELETE CASCADE,
  CONSTRAINT `event_points_system_overrides_points_system_id_foreign` FOREIGN KEY (`points_system_id`) REFERENCES `points_systems` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lap_record_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lap_record_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `world_id` bigint(20) unsigned NOT NULL,
  `track_layout_id` bigint(20) unsigned NOT NULL,
  `session_type` varchar(255) NOT NULL,
  `driver_id` bigint(20) unsigned DEFAULT NULL,
  `season_id` bigint(20) unsigned DEFAULT NULL,
  `lap_time_ms` int(11) NOT NULL,
  `record_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lap_record_logs_track_layout_id_foreign` (`track_layout_id`),
  KEY `lap_record_logs_driver_id_foreign` (`driver_id`),
  KEY `lap_record_logs_season_id_foreign` (`season_id`),
  KEY `lap_record_logs_world_id_track_layout_id_index` (`world_id`,`track_layout_id`),
  KEY `lap_record_logs_record_date_index` (`record_date`),
  CONSTRAINT `lap_record_logs_driver_id_foreign` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lap_record_logs_season_id_foreign` FOREIGN KEY (`season_id`) REFERENCES `seasons` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lap_record_logs_track_layout_id_foreign` FOREIGN KEY (`track_layout_id`) REFERENCES `track_layouts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lap_record_logs_world_id_foreign` FOREIGN KEY (`world_id`) REFERENCES `worlds` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lap_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lap_records` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `world_id` bigint(20) unsigned NOT NULL,
  `track_layout_id` bigint(20) unsigned NOT NULL,
  `session_type` varchar(255) NOT NULL,
  `driver_id` bigint(20) unsigned DEFAULT NULL,
  `season_id` bigint(20) unsigned DEFAULT NULL,
  `lap_time_ms` int(11) NOT NULL,
  `record_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lap_records_world_id_track_layout_id_session_type_unique` (`world_id`,`track_layout_id`,`session_type`),
  KEY `lap_records_track_layout_id_foreign` (`track_layout_id`),
  KEY `lap_records_driver_id_foreign` (`driver_id`),
  KEY `lap_records_season_id_foreign` (`season_id`),
  KEY `lap_records_world_id_track_layout_id_index` (`world_id`,`track_layout_id`),
  CONSTRAINT `lap_records_driver_id_foreign` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lap_records_season_id_foreign` FOREIGN KEY (`season_id`) REFERENCES `seasons` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lap_records_track_layout_id_foreign` FOREIGN KEY (`track_layout_id`) REFERENCES `track_layouts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lap_records_world_id_foreign` FOREIGN KEY (`world_id`) REFERENCES `worlds` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `point_system_bonus_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `point_system_bonus_rules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `point_system_id` bigint(20) unsigned NOT NULL,
  `type` enum('fastest_lap') NOT NULL,
  `points` int(11) NOT NULL,
  `min_position_required` int(11) DEFAULT NULL,
  `requires_finish` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `point_system_bonus_rules_point_system_id_foreign` (`point_system_id`),
  CONSTRAINT `point_system_bonus_rules_point_system_id_foreign` FOREIGN KEY (`point_system_id`) REFERENCES `point_systems` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `point_system_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `point_system_rules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `point_system_id` bigint(20) unsigned NOT NULL,
  `type` enum('race','qualifying') NOT NULL,
  `position` int(10) unsigned NOT NULL,
  `points` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `point_system_rules_point_system_id_foreign` (`point_system_id`),
  CONSTRAINT `point_system_rules_point_system_id_foreign` FOREIGN KEY (`point_system_id`) REFERENCES `point_systems` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `point_systems`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `point_systems` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `points_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `points_rules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `points_system_id` bigint(20) unsigned NOT NULL,
  `session_type` varchar(255) NOT NULL,
  `position` int(11) NOT NULL,
  `points` decimal(8,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `points_rules_points_system_id_session_type_position_unique` (`points_system_id`,`session_type`,`position`),
  KEY `points_rules_points_system_id_session_type_index` (`points_system_id`,`session_type`),
  CONSTRAINT `points_rules_points_system_id_foreign` FOREIGN KEY (`points_system_id`) REFERENCES `points_systems` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `points_systems`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `points_systems` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `season_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `fastest_lap_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `fastest_lap_points` int(11) DEFAULT NULL,
  `fastest_lap_min_position` int(11) DEFAULT NULL,
  `pole_position_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `pole_position_points` int(11) DEFAULT NULL,
  `quali_bonus_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `points_systems_season_id_foreign` (`season_id`),
  CONSTRAINT `points_systems_season_id_foreign` FOREIGN KEY (`season_id`) REFERENCES `seasons` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `qualifying_results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `qualifying_results` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `qualifying_session_id` bigint(20) unsigned NOT NULL,
  `entry_car_id` bigint(20) unsigned NOT NULL,
  `position` int(11) DEFAULT NULL,
  `best_lap_time_ms` bigint(20) DEFAULT NULL,
  `average_lap_time_ms` bigint(20) DEFAULT NULL,
  `laps_set` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `qualifying_results_qualifying_session_id_entry_car_id_unique` (`qualifying_session_id`,`entry_car_id`),
  KEY `qualifying_results_entry_car_id_foreign` (`entry_car_id`),
  CONSTRAINT `qualifying_results_entry_car_id_foreign` FOREIGN KEY (`entry_car_id`) REFERENCES `entry_cars` (`id`),
  CONSTRAINT `qualifying_results_qualifying_session_id_foreign` FOREIGN KEY (`qualifying_session_id`) REFERENCES `qualifying_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `qualifying_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `qualifying_sessions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `calendar_race_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `session_order` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `qualifying_sessions_calendar_race_id_session_order_unique` (`calendar_race_id`,`session_order`),
  CONSTRAINT `qualifying_sessions_calendar_race_id_foreign` FOREIGN KEY (`calendar_race_id`) REFERENCES `calendar_races` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `race_entry_cars`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `race_entry_cars` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `calendar_race_id` bigint(20) unsigned NOT NULL,
  `entry_car_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `race_entry_cars_calendar_race_id_entry_car_id_unique` (`calendar_race_id`,`entry_car_id`),
  KEY `race_entry_cars_entry_car_id_foreign` (`entry_car_id`),
  CONSTRAINT `race_entry_cars_calendar_race_id_foreign` FOREIGN KEY (`calendar_race_id`) REFERENCES `calendar_races` (`id`) ON DELETE CASCADE,
  CONSTRAINT `race_entry_cars_entry_car_id_foreign` FOREIGN KEY (`entry_car_id`) REFERENCES `entry_cars` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `race_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `race_sessions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `calendar_race_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `session_order` int(11) NOT NULL,
  `is_sprint` tinyint(1) NOT NULL DEFAULT 0,
  `reverse_grid` tinyint(1) NOT NULL DEFAULT 0,
  `reverse_grid_from_position` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `race_sessions_calendar_race_id_foreign` (`calendar_race_id`),
  CONSTRAINT `race_sessions_calendar_race_id_foreign` FOREIGN KEY (`calendar_race_id`) REFERENCES `calendar_races` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `result_drivers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `result_drivers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `result_id` bigint(20) unsigned NOT NULL,
  `driver_id` bigint(20) unsigned NOT NULL,
  `driver_order` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `result_drivers_result_id_driver_id_unique` (`result_id`,`driver_id`),
  KEY `result_drivers_driver_id_foreign` (`driver_id`),
  CONSTRAINT `result_drivers_driver_id_foreign` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`),
  CONSTRAINT `result_drivers_result_id_foreign` FOREIGN KEY (`result_id`) REFERENCES `results` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `results` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `race_session_id` bigint(20) unsigned NOT NULL,
  `entry_car_id` bigint(20) unsigned NOT NULL,
  `position` int(11) DEFAULT NULL,
  `class_position` int(11) DEFAULT NULL,
  `status` enum('finished','dnf','dsq','dns') NOT NULL,
  `gap_to_leader_ms` bigint(20) DEFAULT NULL,
  `gap_laps_down` int(11) DEFAULT NULL,
  `laps_completed` int(11) NOT NULL DEFAULT 0,
  `fastest_lap_time_ms` bigint(20) DEFAULT NULL,
  `fastest_lap` tinyint(1) NOT NULL DEFAULT 0,
  `points_awarded` decimal(8,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `results_calendar_race_id_entry_car_id_unique` (`race_session_id`,`entry_car_id`),
  KEY `results_position_index` (`position`),
  KEY `results_entry_car_id_foreign` (`entry_car_id`),
  CONSTRAINT `results_entry_car_id_foreign` FOREIGN KEY (`entry_car_id`) REFERENCES `entry_cars` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `season_classes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `season_classes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `season_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `season_classes_season_id_name_unique` (`season_id`,`name`),
  CONSTRAINT `season_classes_season_id_foreign` FOREIGN KEY (`season_id`) REFERENCES `seasons` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `season_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `season_entries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entrant_id` bigint(20) unsigned NOT NULL,
  `season_id` bigint(20) unsigned NOT NULL,
  `series_id` bigint(20) unsigned NOT NULL,
  `constructor_id` bigint(20) unsigned NOT NULL,
  `display_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `season_entries_entrant_id_season_id_series_id_unique` (`entrant_id`,`season_id`,`series_id`,`constructor_id`) USING BTREE,
  KEY `season_entries_season_id_foreign` (`season_id`),
  KEY `season_entries_series_id_foreign` (`series_id`),
  KEY `season_entries_constructor_id_foreign` (`constructor_id`),
  CONSTRAINT `season_entries_constructor_id_foreign` FOREIGN KEY (`constructor_id`) REFERENCES `constructors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `season_entries_entrant_id_foreign` FOREIGN KEY (`entrant_id`) REFERENCES `entrants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `season_entries_season_id_foreign` FOREIGN KEY (`season_id`) REFERENCES `seasons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `season_entries_series_id_foreign` FOREIGN KEY (`series_id`) REFERENCES `series` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `season_team_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `season_team_entries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `season_id` bigint(20) unsigned NOT NULL,
  `team_id` bigint(20) unsigned NOT NULL,
  `engine_supplier_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `season_team_entries_season_id_team_id_unique` (`season_id`,`team_id`),
  KEY `season_team_entries_team_id_foreign` (`team_id`),
  KEY `season_team_entries_engine_supplier_id_foreign` (`engine_supplier_id`),
  KEY `season_team_entries_season_id_team_id_index` (`season_id`,`team_id`),
  CONSTRAINT `season_team_entries_engine_supplier_id_foreign` FOREIGN KEY (`engine_supplier_id`) REFERENCES `engine_suppliers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `season_team_entries_season_id_foreign` FOREIGN KEY (`season_id`) REFERENCES `seasons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `season_team_entries_team_id_foreign` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `seasons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `seasons` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `series_id` bigint(20) unsigned NOT NULL,
  `year` year(4) NOT NULL,
  `is_complete` tinyint(1) DEFAULT 0,
  `is_simulated` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `point_system_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `seasons_series_id_year_unique` (`series_id`,`year`),
  KEY `seasons_series_id_index` (`series_id`),
  KEY `seasons_point_system_id_foreign` (`point_system_id`),
  CONSTRAINT `seasons_point_system_id_foreign` FOREIGN KEY (`point_system_id`) REFERENCES `point_systems` (`id`) ON DELETE SET NULL,
  CONSTRAINT `seasons_series_id_foreign` FOREIGN KEY (`series_id`) REFERENCES `series` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `series`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `series` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `world_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `short_name` varchar(255) DEFAULT NULL,
  `is_multiclass` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `series_world_id_foreign` (`world_id`),
  CONSTRAINT `series_world_id_foreign` FOREIGN KEY (`world_id`) REFERENCES `worlds` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `teams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `teams` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `world_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `base_country` varchar(255) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `teams_world_id_name_unique` (`world_id`,`name`),
  CONSTRAINT `teams_world_id_foreign` FOREIGN KEY (`world_id`) REFERENCES `worlds` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `track_layouts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `track_layouts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `track_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `length_km` decimal(5,3) DEFAULT NULL,
  `turn_count` int(11) DEFAULT NULL,
  `active_from` year(4) DEFAULT NULL,
  `active_to` year(4) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `track_layouts_track_id_foreign` (`track_id`),
  CONSTRAINT `track_layouts_track_id_foreign` FOREIGN KEY (`track_id`) REFERENCES `tracks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tracks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tracks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `name_short` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `country_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tracks_name_unique` (`name`),
  KEY `tracks_country_id_foreign` (`country_id`),
  CONSTRAINT `tracks_country_id_foreign` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `worlds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `worlds` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `start_year` year(4) NOT NULL,
  `current_year` year(4) DEFAULT NULL,
  `is_canonical` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'2026_02_17_103112_create_sessions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'2026_02_17_103410_tracks',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'2026_02_17_103418_track_layouts',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2026_02_17_103427_engine_suppliers',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2026_02_17_103432_worlds',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2026_02_17_103436_series',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2026_02_17_103442_seasons',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2026_02_17_103445_teams',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2026_02_17_103449_drivers',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2026_02_17_103513_season_team_entries',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2026_02_17_103528_car_entries',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2026_02_17_103536_calendar_races',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2026_02_17_103551_race_sessions',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2026_02_17_103557_results',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2026_02_17_103603_lap_records',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2026_02_17_103608_lap_record_logs',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2026_02_17_103616_point_systems',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2026_02_17_103623_point_rules',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2026_02_17_103641_event_points_system_overrides',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2026_02_17_114907_add_current_year_to_worlds_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2026_02_17_125340_create_countries_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2026_02_17_125408_update_tracks_add_country_id',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2026_02_18_115355_create_season_classes_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2026_02_18_120945_create_results_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2026_02_18_122308_create_constructors_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2026_02_18_122421_create_entries_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2026_02_18_122424_create_entry_classes_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2026_02_18_122428_create_cars_table',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2026_02_18_124359_add_country_id_to_constructors_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2026_02_19_071549_create_car_models_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2026_02_19_071641_create_entrants_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2026_02_19_071724_create_season_entries_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2026_02_19_071852_create_entry_cars_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2026_02_19_073919_add_display_name_to_season_entries_table',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2026_02_19_080007_remove_constructor_id_from_entrants_table',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2026_02_19_080022_add_constructor_id_to_season_entries_table',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2026_02_19_105140_update_entry_classes_table',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2026_02_19_114238_add_entry_cars_table',13);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2026_02_19_115835_create_engines_table',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2026_02_19_115905_add_engine_id_to_car_models_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2026_02_20_121708_create_entry_cars_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2026_02_20_130951_add_drivers_table',16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2026_02_20_133154_create_entry_car_driver_table',17);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2026_02_21_022036_create_point_systems_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2026_02_21_022050_create_point_system_rules_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2026_02_21_022101_create_point_system_bonus_rules_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2026_02_21_022203_add_point_system_id_to_seasons_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2026_02_21_022236_add_point_system_id_to_calendar_races_table',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2026_02_21_132310_create_results_table',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2026_02_21_132433_create_result_drivers_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2026_02_21_132507_create_qualifying_sessions_table',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52,'2026_02_21_132520_create_qualifying_results_table',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2026_02_21_141817_add_is_locked_to_calendar_races_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2026_02_21_143203_add_eligibility_to_point_system_bonus_rules',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (55,'2026_02_21_151004_create_race_entry_cars_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (56,'2026_02_22_035510_add_session_type_to_results_table',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57,'2026_02_22_041200_add_column_to_results',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (58,'2026_02_22_044858_create_race_sessions_table',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (59,'2026_02_22_050845_remove_session_type_from_results_table',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (60,'2026_02_22_051001_fix_results_entry_car_foreign_key',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (61,'2026_02_22_052130_update_results_table_structure',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (62,'2026_03_08_055831_modify_table_seasons',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (63,'2026_03_14_065121_add_column_to_calendar_races',33);
