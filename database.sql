CREATE DATABASE IF NOT EXISTS `User listing`;
USE `User listing`;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(100) NOT NULL,
  email_id VARCHAR(100) NOT NULL UNIQUE,
  gender ENUM('M', 'F', 'O') NOT NULL,
  password VARCHAR(255) NOT NULL,
  status TINYINT(1) NOT NULL DEFAULT 1,
  profile_picture VARCHAR(255) NULL,
  is_deleted TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Run these migrations if the users table already existed before this CRUD code:
ALTER TABLE users MODIFY COLUMN password VARCHAR(255) NOT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_picture VARCHAR(255) NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_deleted TINYINT(1) NOT NULL DEFAULT 0;
