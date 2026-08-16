CREATE DATABASE IF NOT EXISTS quanlybanbooks CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE quanlybanbooks;

CREATE TABLE IF NOT EXISTS security_lab_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(80) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('user','admin') NOT NULL DEFAULT 'user'
);
INSERT IGNORE INTO security_lab_users(id,username,password_hash,role) VALUES
(101,'lab_user_a','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC9v7Jf5V8y1pW0L0mS2','user'),
(102,'lab_user_b','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC9v7Jf5V8y1pW0L0mS2','user'),
(103,'lab_admin','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC9v7Jf5V8y1pW0L0mS2','admin');

CREATE TABLE IF NOT EXISTS security_lab_documents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  owner_id INT NOT NULL,
  title VARCHAR(200) NOT NULL,
  secret_text TEXT NOT NULL,
  FOREIGN KEY(owner_id) REFERENCES security_lab_users(id) ON DELETE CASCADE
);
INSERT IGNORE INTO security_lab_documents(id,owner_id,title,secret_text) VALUES
(201,101,'Tài liệu của User A','SECRET-A: dữ liệu riêng của User A'),
(202,102,'Tài liệu của User B','SECRET-B: dữ liệu riêng của User B'),
(203,103,'Tài liệu Admin','SECRET-ADMIN: dữ liệu quản trị');

CREATE TABLE IF NOT EXISTS security_lab_comments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  body TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(user_id) REFERENCES security_lab_users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS security_lab_audit (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  scenario VARCHAR(80) NOT NULL,
  mode ENUM('vulnerable','hardened') NOT NULL,
  method VARCHAR(10) NOT NULL,
  path VARCHAR(255) NOT NULL,
  status_code INT NOT NULL,
  result VARCHAR(40) NOT NULL,
  notes VARCHAR(500) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
