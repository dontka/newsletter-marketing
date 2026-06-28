-- Database schema for newsletter system

CREATE TABLE IF NOT EXISTS subscribers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  name VARCHAR(255) DEFAULT NULL,
  token VARCHAR(64) NOT NULL,
  status ENUM('pending', 'active', 'unsubscribed', 'bounced') NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL,
  confirmed_at DATETIME DEFAULT NULL,
  unsubscribed_at DATETIME DEFAULT NULL,
  INDEX ix_subscribers_status (status),
  INDEX ix_subscribers_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  afiazone_id VARCHAR(255) NOT NULL UNIQUE,
  email VARCHAR(255) NOT NULL,
  name VARCHAR(255) DEFAULT NULL,
  role ENUM('admin', 'editor', 'viewer') NOT NULL DEFAULT 'admin',
  access_token VARCHAR(255) DEFAULT NULL,
  token_expires_at DATETIME DEFAULT NULL,
  last_login DATETIME DEFAULT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME DEFAULT NULL,
  INDEX ix_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS newsletters (
  id INT AUTO_INCREMENT PRIMARY KEY,
  subject VARCHAR(255) NOT NULL,
  content TEXT NOT NULL,
  plain_text TEXT DEFAULT NULL,
  created_by VARCHAR(100) NOT NULL,
  created_at DATETIME NOT NULL,
  scheduled_at DATETIME DEFAULT NULL,
  status ENUM('draft','scheduled','sending','sent','cancelled') NOT NULL DEFAULT 'draft',
  campaign_type VARCHAR(50) NOT NULL DEFAULT 'announcement',
  audience VARCHAR(50) NOT NULL DEFAULT 'all',
  tracking_enabled TINYINT(1) NOT NULL DEFAULT 1,
  INDEX ix_newsletters_status (status),
  INDEX ix_newsletters_scheduled_at (scheduled_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET @dbname = DATABASE();
SET @tablename = 'newsletters';
SET @columnname = 'campaign_type';
SET @stmt = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) = 0,
  'ALTER TABLE newsletters ADD COLUMN campaign_type VARCHAR(50) NOT NULL DEFAULT "announcement"',
  'SELECT "campaign_type already exists"'
);
PREPARE stmt FROM @stmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @columnname = 'audience';
SET @stmt = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) = 0,
  'ALTER TABLE newsletters ADD COLUMN audience VARCHAR(50) NOT NULL DEFAULT "all"',
  'SELECT "audience already exists"'
);
PREPARE stmt FROM @stmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @columnname = 'tracking_enabled';
SET @stmt = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) = 0,
  'ALTER TABLE newsletters ADD COLUMN tracking_enabled TINYINT(1) NOT NULL DEFAULT 1',
  'SELECT "tracking_enabled already exists"'
);
PREPARE stmt FROM @stmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS send_jobs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  newsletter_id INT NOT NULL,
  subscriber_id INT NOT NULL,
  status ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
  attempts INT NOT NULL DEFAULT 0,
  last_error TEXT DEFAULT NULL,
  sent_at DATETIME DEFAULT NULL,
  FOREIGN KEY (newsletter_id) REFERENCES newsletters(id) ON DELETE CASCADE,
  FOREIGN KEY (subscriber_id) REFERENCES subscribers(id) ON DELETE CASCADE,
  INDEX ix_send_jobs_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  send_job_id INT DEFAULT NULL,
  type ENUM('open','click') NOT NULL,
  meta JSON DEFAULT NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (send_job_id) REFERENCES send_jobs(id) ON DELETE CASCADE,
  INDEX ix_events_type (type),
  INDEX ix_events_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
