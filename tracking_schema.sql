-- Activity Logs Table
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `event_type` varchar(50) NOT NULL,
  `event_data` text DEFAULT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `event_type` (`event_type`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `api_metrics` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `endpoint` varchar(100) NOT NULL,
  `method` varchar(10) NOT NULL,
  `response_time_ms` int(11) DEFAULT NULL,
  `status_code` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `endpoint` (`endpoint`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `page_views` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `page` varchar(100) NOT NULL,
  `referrer` varchar(255) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(50) DEFAULT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `page` (`page`),
  KEY `user_id` (`user_id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; 