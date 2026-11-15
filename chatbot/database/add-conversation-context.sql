-- ===================================================================
-- PFTP Website AI Chatbot - Conversation Context & Memory Table
-- ===================================================================

USE `pftp_chatbot`;

CREATE TABLE IF NOT EXISTS `conversation_context` (
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

