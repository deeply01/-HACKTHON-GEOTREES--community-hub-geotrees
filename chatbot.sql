-- ============================================
-- CHATBOT DATABASE SCHEMA
-- ============================================

-- 1. Chat Messages Table (Enhanced version of your existing table)
CREATE TABLE IF NOT EXISTS `chat_messages` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `sender` enum('user','bot','admin') NOT NULL DEFAULT 'user',
    `message` text NOT NULL,
    `message_type` enum('text','image','file','system') DEFAULT 'text',
    `is_read` tinyint(1) DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_sender` (`sender`),
    KEY `idx_created_at` (`created_at`),
    KEY `idx_user_created` (`user_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Bot Configuration Table
CREATE TABLE IF NOT EXISTS `bot_config` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `config_key` varchar(100) NOT NULL UNIQUE,
    `config_value` text,
    `config_type` enum('string','boolean','integer','json') DEFAULT 'string',
    `description` varchar(255) DEFAULT NULL,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_config_key` (`config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Users Table (Optional - for storing user information)
CREATE TABLE IF NOT EXISTS `chat_users` (
    `user_id` int(11) NOT NULL AUTO_INCREMENT,
    `username` varchar(100) DEFAULT NULL,
    `email` varchar(255) DEFAULT NULL,
    `full_name` varchar(255) DEFAULT NULL,
    `phone` varchar(20) DEFAULT NULL,
    `status` enum('active','inactive','blocked') DEFAULT 'active',
    `last_seen` timestamp NULL DEFAULT NULL,
    `user_agent` text,
    `ip_address` varchar(45) DEFAULT NULL,
    `session_id` varchar(255) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`),
    KEY `idx_email` (`email`),
    KEY `idx_status` (`status`),
    KEY `idx_last_seen` (`last_seen`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Bot Responses/Templates Table
CREATE TABLE IF NOT EXISTS `bot_responses` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `trigger_keyword` varchar(255) NOT NULL,
    `response_text` text NOT NULL,
    `response_type` enum('exact','contains','regex','fallback') DEFAULT 'contains',
    `priority` int(11) DEFAULT 0,
    `is_active` tinyint(1) DEFAULT 1,
    `usage_count` int(11) DEFAULT 0,
    `created_by` int(11) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_trigger` (`trigger_keyword`),
    KEY `idx_active` (`is_active`),
    KEY `idx_priority` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Conversation Sessions Table
CREATE TABLE IF NOT EXISTS `chat_sessions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `session_token` varchar(255) NOT NULL,
    `start_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `end_time` timestamp NULL DEFAULT NULL,
    `status` enum('active','ended','transferred') DEFAULT 'active',
    `total_messages` int(11) DEFAULT 0,
    `bot_responses` int(11) DEFAULT 0,
    `admin_responses` int(11) DEFAULT 0,
    `satisfaction_rating` tinyint(1) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_session_token` (`session_token`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Admin Users Table
CREATE TABLE IF NOT EXISTS `admin_users` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `username` varchar(100) NOT NULL UNIQUE,
    `email` varchar(255) NOT NULL UNIQUE,
    `password_hash` varchar(255) NOT NULL,
    `full_name` varchar(255) DEFAULT NULL,
    `role` enum('admin','moderator','viewer') DEFAULT 'admin',
    `is_active` tinyint(1) DEFAULT 1,
    `last_login` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_username` (`username`),
    UNIQUE KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Chat Analytics Table
CREATE TABLE IF NOT EXISTS `chat_analytics` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `date` date NOT NULL,
    `total_conversations` int(11) DEFAULT 0,
    `new_users` int(11) DEFAULT 0,
    `total_messages` int(11) DEFAULT 0,
    `bot_messages` int(11) DEFAULT 0,
    `admin_messages` int(11) DEFAULT 0,
    `avg_response_time` decimal(5,2) DEFAULT NULL,
    `satisfaction_avg` decimal(3,2) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- INSERT DEFAULT BOT CONFIGURATION
-- ============================================

INSERT INTO `bot_config` (`config_key`, `config_value`, `config_type`, `description`) VALUES
('bot_name', 'ChatBot Assistant', 'string', 'Display name for the chatbot'),
('bot_greeting', 'Hello! 👋 How can I help you today?', 'string', 'Default greeting message'),
('auto_reply_enabled', '1', 'boolean', 'Enable automatic bot responses'),
('response_delay', '2', 'integer', 'Response delay in seconds'),
('max_message_length', '1000', 'integer', 'Maximum message length allowed'),
('office_hours_start', '09:00', 'string', 'Support office hours start time'),
('office_hours_end', '17:00', 'string', 'Support office hours end time'),
('offline_message', 'Thanks for your message! Our team will get back to you soon.', 'string', 'Message shown when offline'),
('transfer_to_human_keywords', '["human", "agent", "representative", "speak to someone"]', 'json', 'Keywords that trigger human transfer'),
('fallback_response', 'I\'m sorry, I didn\'t understand that. Could you please rephrase your question?', 'string', 'Default response when bot doesn\'t understand');

-- ============================================
-- INSERT SAMPLE BOT RESPONSES
-- ============================================

INSERT INTO `bot_responses` (`trigger_keyword`, `response_text`, `response_type`, `priority`) VALUES
('hello', 'Hello! 👋 Welcome! How can I assist you today?', 'contains', 10),
('hi', 'Hi there! 😊 What can I help you with?', 'contains', 10),
('help', 'I\'m here to help! You can ask me about:\n• Product information\n• Support issues\n• General questions\n\nWhat would you like to know?', 'contains', 8),
('thanks', 'You\'re welcome! 😊 Is there anything else I can help you with?', 'contains', 5),
('thank you', 'You\'re very welcome! Feel free to ask if you need anything else.', 'contains', 5),
('bye', 'Goodbye! 👋 Have a great day! Feel free to come back anytime.', 'contains', 7),
('goodbye', 'Take care! 😊 Don\'t hesitate to reach out if you need help later.', 'contains', 7),
('hours', 'Our support hours are Monday to Friday, 9:00 AM to 5:00 PM. Outside these hours, you can still send messages and we\'ll respond as soon as possible!', 'contains', 6),
('contact', 'You can reach us through:\n📧 Email: support@example.com\n📞 Phone: +1 (555) 123-4567\n💬 Or continue chatting here!', 'contains', 6),
('human', 'I\'ll connect you with a human agent right away! Please hold on for a moment.', 'contains', 9),
('agent', 'Let me transfer you to one of our team members. They\'ll be with you shortly!', 'contains', 9),
('*', 'I\'m sorry, I didn\'t quite understand that. Could you please rephrase your question? You can also type "help" to see what I can assist you with!', 'fallback', 1);

-- ============================================
-- CREATE SAMPLE ADMIN USER (Password: admin123)
-- ============================================

INSERT INTO `admin_users` (`username`, `email`, `password_hash`, `full_name`, `role`) VALUES
('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin');

-- ============================================
-- CREATE INDEXES FOR BETTER PERFORMANCE
-- ============================================

-- Additional indexes for chat_messages if not already created
ALTER TABLE `chat_messages` 
ADD INDEX `idx_user_sender` (`user_id`, `sender`),
ADD INDEX `idx_read_status` (`is_read`),
ADD INDEX `idx_message_type` (`message_type`);

-- Foreign key constraints (optional, uncomment if you want strict referential integrity)
/*
ALTER TABLE `chat_messages` 
ADD CONSTRAINT `fk_chat_messages_user` 
FOREIGN KEY (`user_id`) REFERENCES `chat_users`(`user_id`) ON DELETE CASCADE;

ALTER TABLE `chat_sessions` 
ADD CONSTRAINT `fk_chat_sessions_user` 
FOREIGN KEY (`user_id`) REFERENCES `chat_users`(`user_id`) ON DELETE CASCADE;
*/

-- ============================================
-- SAMPLE QUERIES FOR TESTING
-- ============================================

-- Get bot configuration
-- SELECT * FROM bot_config WHERE is_active = 1;

-- Get conversation statistics
-- SELECT 
--     COUNT(DISTINCT user_id) as total_users,
--     COUNT(*) as total_messages,
--     SUM(CASE WHEN sender = 'bot' THEN 1 ELSE 0 END) as bot_messages,
--     SUM(CASE WHEN sender = 'user' THEN 1 ELSE 0 END) as user_messages
-- FROM chat_messages 
-- WHERE DATE(created_at) = CURDATE();

-- Get active conversations
-- SELECT 
--     user_id,
--     COUNT(*) as message_count,
--     MAX(created_at) as last_message,
--     MIN(created_at) as first_message
-- FROM chat_messages 
-- GROUP BY user_id 
-- HAVING MAX(created_at) > DATE_SUB(NOW(), INTERVAL 24 HOUR)
-- ORDER BY last_message DESC;