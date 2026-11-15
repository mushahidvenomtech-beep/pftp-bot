-- ===================================================================
-- PFTP Website AI Chatbot - Database Schema
-- Charset: utf8mb4, Collation: utf8mb4_unicode_ci
-- ===================================================================

CREATE DATABASE IF NOT EXISTS `pftp_chatbot`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `pftp_chatbot`;

-- Disable foreign key checks during creation
SET FOREIGN_KEY_CHECKS = 0;

-- -------------------------------------------------------------------
-- 1. Visitors Table
-- -------------------------------------------------------------------
DROP TABLE IF EXISTS `visitors`;
CREATE TABLE `visitors` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(50) DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_visitors_email` (`email`),
  INDEX `idx_visitors_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------------
-- 2. Conversations Table
-- -------------------------------------------------------------------
DROP TABLE IF EXISTS `conversations`;
CREATE TABLE `conversations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `visitor_id` INT UNSIGNED NOT NULL,
  `session_token` VARCHAR(64) NOT NULL UNIQUE,
  `status` ENUM('active', 'closed') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_conversations_token` (`session_token`),
  INDEX `idx_conversations_visitor` (`visitor_id`),
  INDEX `idx_conversations_status` (`status`),
  CONSTRAINT `fk_conversations_visitor` FOREIGN KEY (`visitor_id`)
    REFERENCES `visitors` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------------
-- 3. Messages Table
-- -------------------------------------------------------------------
DROP TABLE IF EXISTS `messages`;
CREATE TABLE `messages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `conversation_id` INT UNSIGNED NOT NULL,
  `sender_type` ENUM('visitor', 'bot', 'admin') NOT NULL,
  `message` TEXT NOT NULL,
  `matched_kb_id` INT UNSIGNED DEFAULT NULL,
  `is_fallback` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_messages_conversation` (`conversation_id`),
  INDEX `idx_messages_sender` (`sender_type`),
  INDEX `idx_messages_matched_kb` (`matched_kb_id`),
  INDEX `idx_messages_created_at` (`created_at`),
  CONSTRAINT `fk_messages_conversation` FOREIGN KEY (`conversation_id`)
    REFERENCES `conversations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------------
-- 4. Knowledge Base Table
-- -------------------------------------------------------------------
DROP TABLE IF EXISTS `knowledge_base`;
CREATE TABLE `knowledge_base` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category` VARCHAR(100) NOT NULL DEFAULT 'General',
  `intent_id` VARCHAR(100) DEFAULT NULL,
  `language` VARCHAR(20) NOT NULL DEFAULT 'en', -- en, ur, roman_ur
  `question` TEXT NOT NULL,
  `question_variations` TEXT DEFAULT NULL,
  `answer` TEXT NOT NULL,
  `keywords` TEXT DEFAULT NULL,
  `possible_careers` TEXT DEFAULT NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `views_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_kb_category` (`category`),
  INDEX `idx_kb_intent_id` (`intent_id`),
  INDEX `idx_kb_language` (`language`),
  INDEX `idx_kb_status` (`status`),
  FULLTEXT KEY `ft_kb_search` (`question`, `answer`, `keywords`, `question_variations`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------------
-- 5. Chatbot Settings Table
-- -------------------------------------------------------------------
DROP TABLE IF EXISTS `chatbot_settings`;
CREATE TABLE `chatbot_settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT DEFAULT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------------
-- 6. Administrators Table
-- -------------------------------------------------------------------
DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` VARCHAR(50) NOT NULL DEFAULT 'admin',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_admin_username` (`username`),
  UNIQUE KEY `idx_admin_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------------
-- 7. Unanswered Queries Table
-- -------------------------------------------------------------------
DROP TABLE IF EXISTS `unanswered_queries`;
CREATE TABLE `unanswered_queries` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `conversation_id` INT UNSIGNED DEFAULT NULL,
  `visitor_id` INT UNSIGNED DEFAULT NULL,
  `query_text` TEXT NOT NULL,
  `status` ENUM('pending', 'resolved', 'ignored') NOT NULL DEFAULT 'pending',
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_unanswered_status` (`status`),
  INDEX `idx_unanswered_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------------
-- 8. Conversation Context & Memory Table
-- -------------------------------------------------------------------
DROP TABLE IF EXISTS `conversation_context`;
CREATE TABLE `conversation_context` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `conversation_id` INT UNSIGNED NOT NULL,
  `current_topic` VARCHAR(100) DEFAULT NULL,
  `last_mentioned_course` VARCHAR(100) DEFAULT NULL,
  `user_education_status` VARCHAR(100) DEFAULT NULL,
  `last_intent` VARCHAR(100) DEFAULT NULL,
  `pending_clarification` VARCHAR(255) DEFAULT NULL,
  `context_data` JSON DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_context_conv_id` (`conversation_id`),
  INDEX `idx_context_topic` (`current_topic`),
  INDEX `idx_context_course` (`last_mentioned_course`),
  CONSTRAINT `fk_context_conversation` FOREIGN KEY (`conversation_id`)
    REFERENCES `conversations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- -------------------------------------------------------------------
-- Seed Default Settings
-- -------------------------------------------------------------------
INSERT INTO `chatbot_settings` (`setting_key`, `setting_value`) VALUES
('bot_name', 'PFTP Course Advisor'),
('bot_title', 'Student Counselling & Admissions'),
('bot_status', 'online'),
('theme_color', '#07613F'),
('welcome_message', 'Hello! 👋 Welcome to PFTP (Professional Freelancing Training Program). I am your Student Advisor. How can I help you?'),
('fallback_response', 'I''m sorry, I couldn''t find an accurate answer to that question. Please rephrase your question or leave your query so our team can assist you.'),
('require_registration', '1'),
('rate_limit_per_minute', '12'),
('office_hours', 'Monday to Saturday, 9:00 AM - 6:00 PM (PKT)'),
('support_phone', '+92 300 1234567'),
('support_email', 'counselling@pftp.com.pk'),
('disclaimer_text', 'PFTP provides skill-based training. Course outcomes, jobs, earnings, admissions and monetization are not guaranteed.')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

-- -------------------------------------------------------------------
-- Seed Default Administrator
-- Username: admin
-- Password: AdminPassword123!
-- -------------------------------------------------------------------
INSERT INTO `admins` (`username`, `email`, `password_hash`, `role`) VALUES
('admin', 'admin@pftp.com.pk', '$2y$12$mnTQD3.NYf1CwS.8HFbkp.v73mUuIWWWTyOYPTkC5/3EsC6fh9ggS', 'super_admin')
ON DUPLICATE KEY UPDATE `password_hash` = VALUES(`password_hash`);

