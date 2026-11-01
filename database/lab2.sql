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
INSERT INTO categories(id,name,description) VALUES
 (1,'Primary care','Everyday consultations and preventive care.'),
 (2,'Dental care','Thoughtful care for your smile.'),
 (3,'Specialist care','Focused conversations about specific health needs.'),
 (4,'Family health','Care for children and families.')
ON DUPLICATE KEY UPDATE id=id;

-- Sample demonstration accounts; passwords are stored only as password_hash values.
INSERT INTO users (id,full_name,email,role,password_hash) VALUES (1,'Clinic Administrator','admin@wellnest.example','admin','$2y$10$z0HZ4iFQC./JLPure/hFbON/uDNdk8YNFreDtL.MiELDZ/jrq5wPG') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO users (id,full_name,email,role,password_hash) VALUES (2,'Alex Nguyen','patient@wellnest.example','patient','$2y$10$RPT4h1wIRqJkfpzba6559OOIg9QUJRvWwdtQ5tLPfvgJbpGFLf0HW') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO users (id,full_name,email,role,password_hash) VALUES (3,'Jamie Tran','jamie@example.com','patient','$2y$10$dHZL0dxlmkn6B8CvLL2rCOb7VJBJa.Y6qatdjWdfwgAfcp4/ZFHk6') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO users (id,full_name,email,role,password_hash) VALUES (4,'Taylor Le','taylor@example.com','patient','$2y$10$eOEAZjKVkQcpfjkUEVliVOCIcNS.7ynPrDGsvHexd4lYsn3V13gOy') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO services (id,category_id,name,price,duration,image,alt,tag,summary,description,includes_json,prepare) VALUES (1,1,'General Consultation',250000,30,'general-care.jpg','Healthcare professional wearing a stethoscope and holding a mobile device','A great place to start','A little clarity, a thoughtful conversation, and a care plan made around you.','Make time for a conversation about your everyday health. This illustrative consultation brings together a review of your concerns, a general assessment, and time to discuss the next steps with a primary care clinician.','["A conversation about your health concerns", "Review of your health history", "General clinical assessment", "A personalized summary of next steps"]','Bring a list of any current medicines and previous health records you would like to discuss.') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO services (id,category_id,name,price,duration,image,alt,tag,summary,description,includes_json,prepare) VALUES (2,2,'Dental Check-up',350000,45,'dental-care.jpg','Dentist carrying out an examination in a bright dental clinic','For a healthier smile','Give your smile some attention with a gentle, thorough dental examination.','A routine dental visit is a chance to talk through your oral health and understand your options. This example service includes a dental examination and a discussion of oral care. Additional procedures or imaging are quoted separately.','["Teeth and gum examination", "Review of oral health concerns", "Discussion of daily oral care", "A written overview of recommended next steps"]','Bring details of any previous dental work and a list of medicines you take.') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO services (id,category_id,name,price,duration,image,alt,tag,summary,description,includes_json,prepare) VALUES (3,3,'Heart Health Consultation',500000,45,'heart-care.jpg','Electrocardiogram chart beside a stethoscope and medical instruments','Listen to your heart','Understand your heart health with a focused consultation and clear next steps.','This example cardiology consultation provides time to discuss cardiovascular health history, concerns, and existing results. A clinician can discuss whether further assessment is appropriate. Diagnostic tests are not included in the consultation price.','["Cardiovascular health history review", "Blood pressure and pulse assessment", "Discussion of existing test results", "An outline of potential next steps"]','Bring any previous ECGs, test results, and a current medicine list.') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO services (id,category_id,name,price,duration,image,alt,tag,summary,description,includes_json,prepare) VALUES (4,3,'Skin & Dermatology',400000,30,'skin-care.jpg','Skin care specialist performing a close facial skin assessment','Feel good in your skin','Talk through your skin concerns and explore a routine that works for you.','An illustrative dermatology consultation for discussing skin concerns, reviewing your current routine, and understanding possible next steps. The consultation fee covers the assessment and discussion; products and treatments are quoted separately.','["Discussion of skin concerns and history", "Skin assessment", "Review of your current skin care routine", "An individualized follow-up outline"]','Bring a list of skin care products and medicines you currently use.') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO services (id,category_id,name,price,duration,image,alt,tag,summary,description,includes_json,prepare) VALUES (5,4,'Child Wellness Visit',300000,40,'child-care.jpg','Baby wearing sunglasses in a colorful swim ring, illustrating child wellness','Care for growing lives','A welcoming space for little ones, big questions, and every growing milestone.','This example family health visit offers parents and guardians time to discuss a child’s growth, everyday health, and development. A parent or legal guardian should attend. Vaccines and additional tests are not included in the displayed price.','["Discussion with a parent or guardian", "Growth and development review", "General wellness assessment", "Time for family questions"]','Bring the child’s health and vaccination records. A parent or legal guardian must attend.') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO services (id,category_id,name,price,duration,image,alt,tag,summary,description,includes_json,prepare) VALUES (6,1,'Preventive Health Review',450000,60,'health-screening.jpg','Clinician reviewing medical information at a health consultation','Make room for prevention','Take a thoughtful look at your health today and plan for the days ahead.','An example preventive consultation to review your health history and discuss screening options relevant to your circumstances. Laboratory tests and imaging are not part of this fixed consultation price and would be discussed separately.','["Review of health and family history", "Basic wellness measurements", "Discussion of screening options", "A preventive care summary"]','Bring previous screening results if available. No fasting is required for this consultation.') ON DUPLICATE KEY UPDATE id=id;

-- Illustrative appointment requests for the demonstration dashboards.
INSERT INTO appointment_requests (id,reference,request_key,user_id,service_id,service_name,full_name,email,preferred_date,preferred_time,quoted_price,status) VALUES (1,'WN-DEMO-001','sample-request-1',2,1,'General Consultation','Alex Nguyen','patient@wellnest.example', DATE_ADD(CURDATE(), INTERVAL 2 DAY), '09:00',250000,'requested') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO appointment_requests (id,reference,request_key,user_id,service_id,service_name,full_name,email,preferred_date,preferred_time,quoted_price,status) VALUES (2,'WN-DEMO-002','sample-request-2',3,2,'Dental Check-up','Jamie Tran','jamie@example.com', DATE_ADD(CURDATE(), INTERVAL 3 DAY), '10:00',350000,'confirmed') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO appointment_requests (id,reference,request_key,user_id,service_id,service_name,full_name,email,preferred_date,preferred_time,quoted_price,status) VALUES (3,'WN-DEMO-003','sample-request-3',4,3,'Heart Health Consultation','Taylor Le','taylor@example.com', DATE_ADD(CURDATE(), INTERVAL 4 DAY), '14:00',500000,'requested') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO appointment_requests (id,reference,request_key,user_id,service_id,service_name,full_name,email,preferred_date,preferred_time,quoted_price,status) VALUES (4,'WN-DEMO-004','sample-request-4',2,6,'Preventive Health Review','Alex Nguyen','patient@wellnest.example', DATE_ADD(CURDATE(), INTERVAL -3 DAY), '11:00',450000,'completed') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO appointment_requests (id,reference,request_key,user_id,service_id,service_name,full_name,email,preferred_date,preferred_time,quoted_price,status) VALUES (5,'WN-DEMO-005','sample-request-5',3,4,'Skin & Dermatology','Jamie Tran','jamie@example.com', DATE_ADD(CURDATE(), INTERVAL -2 DAY), '15:00',400000,'cancelled') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO audit_logs(id,user_id,action,entity,entity_id,description) VALUES (1,1,'setup','database',NULL,'Initialized the Medical Clinic Lab 2 demonstration dataset.') ON DUPLICATE KEY UPDATE id=id;
