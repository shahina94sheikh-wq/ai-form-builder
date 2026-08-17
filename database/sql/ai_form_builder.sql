-- AI Form Builder - MySQL schema + demo seed
-- Generated from the project's Laravel migrations and seeders.
-- Target: MySQL 8.0+

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `failed_jobs`;
DROP TABLE IF EXISTS `job_batches`;
DROP TABLE IF EXISTS `jobs`;
DROP TABLE IF EXISTS `cache_locks`;
DROP TABLE IF EXISTS `cache`;
DROP TABLE IF EXISTS `sessions`;
DROP TABLE IF EXISTS `submissions`;
DROP TABLE IF EXISTS `form_imports`;
DROP TABLE IF EXISTS `form_versions`;
DROP TABLE IF EXISTS `ai_generations`;
DROP TABLE IF EXISTS `forms`;
DROP TABLE IF EXISTS `password_reset_tokens`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `payload` longtext NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`),
  CONSTRAINT `sessions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `forms` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `schema` json NOT NULL,
  `settings` json DEFAULT NULL,
  `status` enum('draft','published','archived') NOT NULL DEFAULT 'draft',
  `ai_generated` tinyint(1) NOT NULL DEFAULT '0',
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `forms_slug_unique` (`slug`),
  KEY `forms_user_id_status_index` (`user_id`,`status`),
  KEY `forms_created_at_index` (`created_at`),
  KEY `forms_ai_generated_index` (`ai_generated`),
  CONSTRAINT `forms_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `form_versions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `form_id` bigint unsigned NOT NULL,
  `version` int unsigned NOT NULL,
  `schema` json NOT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `form_versions_form_id_version_unique` (`form_id`,`version`),
  KEY `form_versions_form_id_created_at_index` (`form_id`,`created_at`),
  CONSTRAINT `form_versions_form_id_foreign` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `form_versions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `submissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `form_id` bigint unsigned NOT NULL,
  `data` json NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `submissions_form_id_created_at_index` (`form_id`,`created_at`),
  CONSTRAINT `submissions_form_id_foreign` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ai_generations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `form_id` bigint unsigned DEFAULT NULL,
  `prompt` text NOT NULL,
  `mode` varchar(255) NOT NULL DEFAULT 'create',
  `status` enum('queued','processing','completed','failed') NOT NULL DEFAULT 'queued',
  `model` varchar(255) DEFAULT NULL,
  `input_tokens` int unsigned DEFAULT NULL,
  `output_tokens` int unsigned DEFAULT NULL,
  `result_schema` json DEFAULT NULL,
  `latency_ms` int unsigned DEFAULT NULL,
  `schema` json DEFAULT NULL,
  `error` text,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ai_generations_form_id_status_index` (`form_id`,`status`),
  CONSTRAINT `ai_generations_form_id_foreign` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `form_imports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `filename` varchar(255) NOT NULL,
  `disk` varchar(255) NOT NULL DEFAULT 'local',
  `type` enum('docx','xlsx') NOT NULL,
  `status` enum('uploaded','processing','preview','completed','failed') NOT NULL DEFAULT 'uploaded',
  `parsed_data` json DEFAULT NULL,
  `schema` json DEFAULT NULL,
  `errors` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `form_imports_user_id_status_index` (`user_id`,`status`),
  CONSTRAINT `form_imports_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` smallint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`(191),`queue`(191),`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Demo user from DatabaseSeeder / UserFactory.
-- Password: password
INSERT INTO `users` (`id`,`name`,`email`,`email_verified_at`,`password`,`remember_token`,`created_at`,`updated_at`)
VALUES (1,'Test User','test@example.com',CURRENT_TIMESTAMP,'$2y$12$BjKE32B.vIv2G7kcwoZXMO31Kl0sZ98Ypj7RaHG78fdaM3w733ImG','demo-token',CURRENT_TIMESTAMP,CURRENT_TIMESTAMP);

-- Demo form based on FormSeeder.
INSERT INTO `forms`
(`id`,`user_id`,`title`,`slug`,`schema`,`settings`,`status`,`ai_generated`,`published_at`,`created_at`,`updated_at`)
VALUES
(1,NULL,'Internship Application','internship-application-X92KQ',
'{"version":"1.0","title":"Internship Application","description":"Please complete this internship application form.","settings":{"success_message":"Thank you for your application."},"sections":[{"id":"section_personal","title":"Personal Information","fields":[{"id":"field_full_name","key":"full_name","type":"text","label":"Full Name","placeholder":"Enter your full name","help":"","default":"","required":true,"validation":[]},{"id":"field_email","key":"email","type":"email","label":"Email Address","placeholder":"Enter your email address","help":"","default":"","required":true,"validation":[]},{"id":"field_phone","key":"phone","type":"phone","label":"Phone Number","placeholder":"Enter your phone number","help":"","default":"","required":false,"validation":{"max":30}}]},{"id":"section_education","title":"Education","fields":[{"id":"field_education","key":"education","type":"textarea","label":"Education History","placeholder":"Enter your education details","help":"Include degree, institution and graduation year.","default":"","required":true,"validation":{"min":1,"max":1000}}]},{"id":"section_skills","title":"Skills","fields":[{"id":"field_skills","key":"skills","type":"checkbox","label":"Skills","placeholder":"","help":"Select your skills.","default":[],"required":true,"options":[{"label":"PHP","value":"php"},{"label":"Laravel","value":"laravel"},{"label":"JavaScript","value":"javascript"},{"label":"React","value":"react"},{"label":"MySQL","value":"mysql"}],"validation":[]}]},{"id":"section_resume","title":"Resume","fields":[{"id":"field_resume","key":"resume","type":"file","label":"Resume","placeholder":"","help":"Upload your latest resume.","default":"","required":true,"validation":{"max":10240,"file_types":["pdf","doc","docx"]}}]}]}',
'{"success_message":"Thank you for your application."}',
'published',0,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP);

-- Initial version snapshot for the seeded demo form.
INSERT INTO `form_versions` (`id`,`form_id`,`version`,`schema`,`created_by`,`created_at`,`updated_at`)
SELECT 1,1,1,`schema`,1,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP FROM `forms` WHERE `id`=1;

SET FOREIGN_KEY_CHECKS=1;

-- Demo login
-- Email: test@example.com
-- Password: password
