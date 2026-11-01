-- Wellnest Medical Clinic — LAB 2, MySQL 5.7+/MariaDB 10.4+, utf8mb4.
-- Import with phpMyAdmin or: mysql -u root < database/lab2.sql
-- Idempotent sample inserts preserve existing application edits.
CREATE DATABASE IF NOT EXISTS wellnest_lab2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE wellnest_lab2;
SET NAMES utf8mb4;
SET time_zone = '+07:00';
CREATE TABLE IF NOT EXISTS users (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 full_name VARCHAR(100) NOT NULL,
 email VARCHAR(190) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 role ENUM('admin','patient') NOT NULL DEFAULT 'patient',
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS categories (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(60) NOT NULL UNIQUE,
 description VARCHAR(240) NOT NULL
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS services (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 category_id INT UNSIGNED NOT NULL,
 name VARCHAR(100) NOT NULL,
 price INT UNSIGNED NOT NULL,
 duration SMALLINT UNSIGNED NOT NULL,
 image VARCHAR(100) NOT NULL,
 alt VARCHAR(240) NOT NULL,
 tag VARCHAR(80) NOT NULL,
 summary VARCHAR(240) NOT NULL,
 description TEXT NOT NULL,
 includes_json TEXT NOT NULL,
 prepare TEXT NOT NULL,
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 version INT UNSIGNED NOT NULL DEFAULT 1,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT fk_services_category FOREIGN KEY(category_id) REFERENCES categories(id) ON DELETE RESTRICT,
 CONSTRAINT ck_service_price CHECK(price BETWEEN 1 AND 99999999),
 CONSTRAINT ck_service_duration CHECK(duration BETWEEN 5 AND 240),
 CONSTRAINT ck_service_active CHECK(is_active IN(0,1)),
 INDEX idx_service_active(is_active,category_id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS appointment_requests (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 reference VARCHAR(24) NOT NULL UNIQUE,
 request_key VARCHAR(64) NOT NULL UNIQUE,
 user_id INT UNSIGNED NOT NULL,
 service_id INT UNSIGNED NOT NULL,
 service_name VARCHAR(100) NOT NULL,
 full_name VARCHAR(100) NOT NULL,
 email VARCHAR(190) NOT NULL,
 preferred_date DATE NOT NULL,
 preferred_time TIME NOT NULL,
 quoted_price INT UNSIGNED NOT NULL,
 status ENUM('requested','confirmed','completed','cancelled') NOT NULL DEFAULT 'requested',
 version INT UNSIGNED NOT NULL DEFAULT 1,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT fk_appointment_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE RESTRICT,
 CONSTRAINT fk_appointment_service FOREIGN KEY(service_id) REFERENCES services(id) ON DELETE RESTRICT,
 CONSTRAINT ck_appointment_price CHECK(quoted_price>0),
 INDEX idx_appointment_patient(user_id,preferred_date),
 INDEX idx_appointment_status(status,preferred_date)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS audit_logs (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id INT UNSIGNED NULL,
 action VARCHAR(50) NOT NULL,
 entity VARCHAR(50) NOT NULL,
 entity_id INT UNSIGNED NULL,
 description VARCHAR(255) NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_audit_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS login_attempts (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 identifier CHAR(64) NOT NULL,
 attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_login_rate(identifier,attempted_at)
) ENGINE=InnoDB;
