-- RC Campus Connect — shared database.                         DO NOT EDIT.
-- Import this FIRST into an empty database named rc_campus, then import your module's database.sql.
-- All sample people are fictional. Every sample account's password is: Pass@123

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS audit_log, notifications, students, employees, courses, programmes, holidays, academic_sessions, departments, users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(40)  NOT NULL UNIQUE,
  full_name     VARCHAR(100) NOT NULL,
  email         VARCHAR(120) NOT NULL UNIQUE,
  mobile        CHAR(10)     NULL,
  password_hash VARCHAR(255) NOT NULL,
  role          VARCHAR(20)  NOT NULL COMMENT 'admin, principal, hod, faculty, staff, accounts, librarian, warden, it_admin, student, applicant',
  status        ENUM('active','blocked') NOT NULL DEFAULT 'active',
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE departments (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code            VARCHAR(10)  NOT NULL UNIQUE,
  name            VARCHAR(100) NOT NULL,
  type            ENUM('academic','administrative') NOT NULL DEFAULT 'academic',
  hod_employee_id INT UNSIGNED NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE academic_sessions (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  label      VARCHAR(20) NOT NULL UNIQUE,
  start_date DATE NOT NULL,
  end_date   DATE NOT NULL,
  is_current TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE holidays (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  holiday_date DATE NOT NULL UNIQUE,
  title        VARCHAR(100) NOT NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE programmes (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code           VARCHAR(20)  NOT NULL UNIQUE,
  name           VARCHAR(120) NOT NULL,
  department_id  INT UNSIGNED NOT NULL,
  duration_years TINYINT UNSIGNED NOT NULL DEFAULT 4,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (department_id) REFERENCES departments(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE courses (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code          VARCHAR(20)  NOT NULL UNIQUE,
  title         VARCHAR(150) NOT NULL,
  type          ENUM('DSC','DSE','GE','SEC','VAC','AEC') NOT NULL,
  department_id INT UNSIGNED NOT NULL,
  lecture       TINYINT UNSIGNED NOT NULL DEFAULT 3,
  tutorial      TINYINT UNSIGNED NOT NULL DEFAULT 0,
  practical     TINYINT UNSIGNED NOT NULL DEFAULT 1,
  credits       TINYINT UNSIGNED NOT NULL DEFAULT 4,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (department_id) REFERENCES departments(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE employees (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id         INT UNSIGNED NULL UNIQUE,
  emp_code        VARCHAR(15)  NOT NULL UNIQUE,
  full_name       VARCHAR(100) NOT NULL,
  designation     VARCHAR(60)  NOT NULL,
  department_id   INT UNSIGNED NOT NULL,
  type            ENUM('teaching','non-teaching') NOT NULL,
  gender          ENUM('M','F','O') NOT NULL,
  dob             DATE NOT NULL,
  date_of_joining DATE NOT NULL,
  email           VARCHAR(120) NOT NULL,
  mobile          CHAR(10) NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (department_id) REFERENCES departments(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE students (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id        INT UNSIGNED NULL UNIQUE,
  enrollment_no  VARCHAR(15)  NOT NULL UNIQUE,
  full_name      VARCHAR(100) NOT NULL,
  dob            DATE NOT NULL,
  gender         ENUM('M','F','O') NOT NULL,
  category       ENUM('UR','OBC','SC','ST','EWS') NOT NULL,
  programme_id   INT UNSIGNED NOT NULL,
  semester       TINYINT UNSIGNED NOT NULL,
  admission_year SMALLINT UNSIGNED NOT NULL,
  email          VARCHAR(120) NOT NULL,
  mobile         CHAR(10) NULL,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (programme_id) REFERENCES programmes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notifications (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NOT NULL,
  module_id  VARCHAR(5)   NOT NULL,
  title      VARCHAR(200) NOT NULL,
  body       TEXT NULL,
  link       VARCHAR(255) NULL,
  is_read    TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (user_id, is_read),
  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_log (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NULL,
  module_id  VARCHAR(5)  NOT NULL,
  action     VARCHAR(40) NOT NULL,
  entity     VARCHAR(60) NULL,
  entity_id  INT UNSIGNED NULL,
  details    VARCHAR(255) NULL,
  ip         VARCHAR(45) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (module_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE departments ADD FOREIGN KEY (hod_employee_id) REFERENCES employees(id);


-- ---------------- sample data ----------------

INSERT INTO `users` (id, username, full_name, email, mobile, password_hash, role, status) VALUES
  (1, 'admin', 'Portal Administrator', 'admin@rc.example.in', '9158813998', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'admin', 'active'),
  (2, 'principal', 'Prof. Pooja Nair', 'pooja.nair@rc.example.in', '9455681518', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'principal', 'active'),
  (3, 'ananya.sethi', 'Dr. Ananya Sethi', 'ananya.sethi@rc.example.in', '9761698716', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'hod', 'active'),
  (4, 'muskan.joshi', 'Muskan Joshi', 'muskan.joshi@rc.example.in', '9978809320', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'hod', 'active'),
  (5, 'khushi.yadav', 'Dr. Khushi Yadav', 'khushi.yadav@rc.example.in', '9317784864', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'hod', 'active'),
  (6, 'divya.bansal', 'Dr. Divya Bansal', 'divya.bansal@rc.example.in', '9400297875', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'hod', 'active'),
  (7, 'divya.arora', 'Dr. Divya Arora', 'divya.arora@rc.example.in', '9045717847', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'hod', 'active'),
  (8, 'khushi.mishra', 'Dr. Khushi Mishra', 'khushi.mishra@rc.example.in', '9627010798', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'hod', 'active'),
  (9, 'tarun.verma', 'Tarun Verma', 'tarun.verma@rc.example.in', '9452059972', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'hod', 'active'),
  (10, 'ishita.pandey', 'Dr. Ishita Pandey', 'ishita.pandey@rc.example.in', '9294866283', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'hod', 'active'),
  (11, 'bhavya.sharma', 'Prof. Bhavya Sharma', 'bhavya.sharma@rc.example.in', '9087547517', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'faculty', 'active'),
  (12, 'harsh.rana', 'Prof. Harsh Rana', 'harsh.rana@rc.example.in', '9579318603', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'faculty', 'active'),
  (13, 'manav.negi', 'Prof. Manav Negi', 'manav.negi@rc.example.in', '9370283822', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'faculty', 'active'),
  (14, 'ishita.nair', 'Dr. Ishita Nair', 'ishita.nair@rc.example.in', '9524776472', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'faculty', 'active'),
  (15, 'kavya.bansal', 'Dr. Kavya Bansal', 'kavya.bansal@rc.example.in', '9175682052', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'faculty', 'active'),
  (16, 'tarun.sethi', 'Dr. Tarun Sethi', 'tarun.sethi@rc.example.in', '9939494821', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'faculty', 'active'),
  (17, 'varun.dubey', 'Varun Dubey', 'varun.dubey@rc.example.in', '9185491452', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'faculty', 'active'),
  (18, 'divya.sharma', 'Dr. Divya Sharma', 'divya.sharma@rc.example.in', '9304118955', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'faculty', 'active'),
  (19, 'yuvraj.malhotra', 'Dr. Yuvraj Malhotra', 'yuvraj.malhotra@rc.example.in', '9976158464', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'faculty', 'active'),
  (20, 'pranav.sethi', 'Prof. Pranav Sethi', 'pranav.sethi@rc.example.in', '9541465908', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'faculty', 'active'),
  (21, 'tarun.singh', 'Dr. Tarun Singh', 'tarun.singh@rc.example.in', '9285940692', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'faculty', 'active'),
  (22, 'riya.yadav', 'Prof. Riya Yadav', 'riya.yadav@rc.example.in', '9280886423', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'faculty', 'active'),
  (23, 'aditi.yadav', 'Aditi Yadav', 'aditi.yadav@rc.example.in', '9912808458', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'staff', 'active'),
  (24, 'karan.saxena', 'Karan Saxena', 'karan.saxena@rc.example.in', '9038265384', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'staff', 'active'),
  (25, 'neha.saxena', 'Neha Saxena', 'neha.saxena@rc.example.in', '9943262705', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'staff', 'active'),
  (26, 'aditya.kapoor', 'Aditya Kapoor', 'aditya.kapoor@rc.example.in', '9584210839', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'staff', 'active'),
  (27, 'kunal.singh', 'Kunal Singh', 'kunal.singh@rc.example.in', '9126584409', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'staff', 'active'),
  (28, 'bhavya.yadav', 'Bhavya Yadav', 'bhavya.yadav@rc.example.in', '9630976582', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'staff', 'active'),
  (29, 'rohan.sharma', 'Rohan Sharma', 'rohan.sharma@rc.example.in', '9180515438', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'accounts', 'active'),
  (30, 'khushi.singh', 'Khushi Singh', 'khushi.singh@rc.example.in', '9164941577', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'accounts', 'active'),
  (31, 'shreya.mishra', 'Shreya Mishra', 'shreya.mishra@rc.example.in', '9111744325', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'librarian', 'active'),
  (32, 'charu.malhotra', 'Charu Malhotra', 'charu.malhotra@rc.example.in', '9237355696', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'librarian', 'active'),
  (33, 'harsh.joshi', 'Dr. Harsh Joshi', 'harsh.joshi@rc.example.in', '9154742843', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'warden', 'active'),
  (34, 'shreya.rawat', 'Shreya Rawat', 'shreya.rawat@rc.example.in', '9660856517', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'it_admin', 'active'),
  (35, 'harsh.mishra', 'Harsh Mishra', 'harsh.mishra@rc.example.in', '9506204842', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'it_admin', 'active'),
  (36, 'khushi.malhotra', 'Khushi Malhotra', 'khushi.malhotra@rc.example.in', '9003145037', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'staff', 'active'),
  (37, 'ritesh.sharma', 'Ritesh Sharma', 'ritesh.sharma@rc.example.in', '9125373924', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'staff', 'active'),
  (38, 'arjun.sharma26', 'Arjun Sharma', 'arjun.sharma26@students.rc.example.in', '9460288375', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'student', 'active'),
  (39, 'saanvi.saxena26', 'Saanvi Saxena', 'saanvi.saxena26@students.rc.example.in', '9540720754', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'student', 'active'),
  (40, 'muskan.dubey26', 'Muskan Dubey', 'muskan.dubey26@students.rc.example.in', '9643906188', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'student', 'active'),
  (41, 'kunal.tiwari26', 'Kunal Tiwari', 'kunal.tiwari26@students.rc.example.in', '9957555218', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'student', 'active'),
  (42, 'kavya.rana26', 'Kavya Rana', 'kavya.rana26@students.rc.example.in', '9307336882', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'student', 'active'),
  (43, 'meera.arora26', 'Meera Arora', 'meera.arora26@students.rc.example.in', '9556898329', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'student', 'active'),
  (44, 'kabir.khanna26', 'Kabir Khanna', 'kabir.khanna26@students.rc.example.in', '9900442248', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'student', 'active'),
  (45, 'nandini.mehta26', 'Nandini Mehta', 'nandini.mehta26@students.rc.example.in', '9022697804', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'student', 'active'),
  (46, 'manav.khanna26', 'Manav Khanna', 'manav.khanna26@students.rc.example.in', '9625280700', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'student', 'active'),
  (47, 'aditi.sharma26', 'Aditi Sharma', 'aditi.sharma26@students.rc.example.in', '9283166323', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'student', 'active'),
  (48, 'varun.mehta26', 'Varun Mehta', 'varun.mehta26@students.rc.example.in', '9866753478', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'student', 'active'),
  (49, 'ishaan.singh26', 'Ishaan Singh', 'ishaan.singh26@students.rc.example.in', '9159212363', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'student', 'active'),
  (50, 'mohit.kapoor25', 'Mohit Kapoor', 'mohit.kapoor25@students.rc.example.in', '9560787457', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'student', 'active'),
  (51, 'varun.rawat25', 'Varun Rawat', 'varun.rawat25@students.rc.example.in', '9699085417', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'student', 'active'),
  (52, 'aditya.bhatia25', 'Aditya Bhatia', 'aditya.bhatia25@students.rc.example.in', '9018791697', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'student', 'active'),
  (53, 'kabir.malhotra25', 'Kabir Malhotra', 'kabir.malhotra25@students.rc.example.in', '9201612840', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'student', 'active'),
  (54, 'siddharth.sethi25', 'Siddharth Sethi', 'siddharth.sethi25@students.rc.example.in', '9247357363', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'student', 'active'),
  (55, 'diya.saxena25', 'Diya Saxena', 'diya.saxena25@students.rc.example.in', '9392731979', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'student', 'active'),
  (56, 'dev.bansal25', 'Dev Bansal', 'dev.bansal25@students.rc.example.in', '9608724385', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'student', 'active'),
  (57, 'varun.rana25', 'Varun Rana', 'varun.rana25@students.rc.example.in', '9828195391', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'student', 'active'),
  (58, 'kavya.singh25', 'Kavya Singh', 'kavya.singh25@students.rc.example.in', '9660330428', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'student', 'active'),
  (59, 'nandini.verma25', 'Nandini Verma', 'nandini.verma25@students.rc.example.in', '9381780578', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'student', 'active'),
  (60, 'diya.kapoor25', 'Diya Kapoor', 'diya.kapoor25@students.rc.example.in', '9121430490', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'student', 'active'),
  (61, 'ritesh.negi25', 'Ritesh Negi', 'ritesh.negi25@students.rc.example.in', '9898370853', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'student', 'active'),
  (62, 'karan.verma24', 'Karan Verma', 'karan.verma24@students.rc.example.in', '9066817922', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'student', 'active'),
  (63, 'kunal.malhotra24', 'Kunal Malhotra', 'kunal.malhotra24@students.rc.example.in', '9642710512', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'student', 'active'),
  (64, 'dev.sethi24', 'Dev Sethi', 'dev.sethi24@students.rc.example.in', '9252781206', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'student', 'active'),
  (65, 'shreya.malhotra24', 'Shreya Malhotra', 'shreya.malhotra24@students.rc.example.in', '9851904316', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'student', 'active'),
  (66, 'pranav.dubey24', 'Pranav Dubey', 'pranav.dubey24@students.rc.example.in', '9800527913', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'student', 'active'),
  (67, 'kavya.gupta24', 'Kavya Gupta', 'kavya.gupta24@students.rc.example.in', '9209842640', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'student', 'active'),
  (68, 'yuvraj.negi24', 'Yuvraj Negi', 'yuvraj.negi24@students.rc.example.in', '9079113571', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'student', 'active'),
  (69, 'tanvi.joshi24', 'Tanvi Joshi', 'tanvi.joshi24@students.rc.example.in', '9796745219', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'student', 'active'),
  (70, 'riya.sethi24', 'Riya Sethi', 'riya.sethi24@students.rc.example.in', '9521362958', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'student', 'active'),
  (71, 'neha.arora24', 'Neha Arora', 'neha.arora24@students.rc.example.in', '9645732840', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'student', 'active'),
  (72, 'nandini.pandey24', 'Nandini Pandey', 'nandini.pandey24@students.rc.example.in', '9927670940', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'student', 'active'),
  (73, 'divya.chauhan24', 'Divya Chauhan', 'divya.chauhan24@students.rc.example.in', '9884985568', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'student', 'active'),
  (74, 'charu.rana23', 'Charu Rana', 'charu.rana23@students.rc.example.in', '9538965013', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'student', 'active'),
  (75, 'khushi.mehta23', 'Khushi Mehta', 'khushi.mehta23@students.rc.example.in', '9683508111', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'student', 'active'),
  (76, 'vidhi.mishra23', 'Vidhi Mishra', 'vidhi.mishra23@students.rc.example.in', '9816354011', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'student', 'active'),
  (77, 'divya.singh23', 'Divya Singh', 'divya.singh23@students.rc.example.in', '9138759211', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'student', 'active'),
  (78, 'gauri.singh23', 'Gauri Singh', 'gauri.singh23@students.rc.example.in', '9862107617', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'student', 'active'),
  (79, 'bhavya.dubey23', 'Bhavya Dubey', 'bhavya.dubey23@students.rc.example.in', '9106261488', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'student', 'active'),
  (80, 'nandini.sharma23', 'Nandini Sharma', 'nandini.sharma23@students.rc.example.in', '9299446135', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'student', 'active'),
  (81, 'aditi.pandey23', 'Aditi Pandey', 'aditi.pandey23@students.rc.example.in', '9994398286', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'student', 'active'),
  (82, 'muskan.rawat23', 'Muskan Rawat', 'muskan.rawat23@students.rc.example.in', '9464397258', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'student', 'active'),
  (83, 'tarun.chauhan23', 'Tarun Chauhan', 'tarun.chauhan23@students.rc.example.in', '9039955156', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'student', 'active'),
  (84, 'vidhi.nair23', 'Vidhi Nair', 'vidhi.nair23@students.rc.example.in', '9156795965', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'student', 'active'),
  (85, 'bhavya.joshi23', 'Bhavya Joshi', 'bhavya.joshi23@students.rc.example.in', '9705670904', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'student', 'active'),
  (86, 'applicant1', 'Siddharth Pandey', 'siddharth.pandey@mail.example.in', '9464403301', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'applicant', 'active'),
  (87, 'applicant2', 'Rohan Verma', 'rohan.verma@mail.example.in', '9598719889', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'applicant', 'active'),
  (88, 'applicant3', 'Pranav Yadav', 'pranav.yadav@mail.example.in', '9163111196', '$2y$10$3zS4iKEH6jIUG2w6nf.IbOLj5a2iCaLFO3Vh6x/8Qu73HYGs1zIc6', 'applicant', 'active');

INSERT INTO `departments` (id, code, name, type) VALUES
  (1, 'CS', 'Computer Science', 'academic'),
  (2, 'COM', 'Commerce', 'academic'),
  (3, 'ECO', 'Economics', 'academic'),
  (4, 'ENG', 'English', 'academic'),
  (5, 'HIN', 'Hindi', 'academic'),
  (6, 'MAT', 'Mathematics', 'academic'),
  (7, 'PSY', 'Applied Psychology', 'academic'),
  (8, 'POL', 'Political Science', 'academic'),
  (9, 'STA', 'Statistics', 'academic'),
  (10, 'ADM', 'Administration', 'administrative'),
  (11, 'ACC', 'Accounts', 'administrative'),
  (12, 'LIB', 'Library', 'administrative'),
  (13, 'ITC', 'IT Cell', 'administrative'),
  (14, 'HST', 'Hostel', 'administrative'),
  (15, 'SPT', 'Sports', 'administrative');

INSERT INTO `academic_sessions` (id, label, start_date, end_date, is_current) VALUES
  (1, '2025-26 (Even)', '2026-01-02', '2026-05-31', 0),
  (2, '2026-27 (Odd)', '2026-08-01', '2026-12-31', 1),
  (3, '2026-27 (Even)', '2027-01-04', '2027-05-31', 0);

INSERT INTO `holidays` (holiday_date, title) VALUES
  ('2026-08-15', 'Independence Day'),
  ('2026-10-02', 'Gandhi Jayanti'),
  ('2026-12-25', 'Christmas'),
  ('2027-01-26', 'Republic Day');

INSERT INTO `programmes` (id, code, name, department_id, duration_years) VALUES
  (1, 'BSC-CS', 'B.Sc. (Hons) Computer Science', 1, 4),
  (2, 'BCOM-H', 'B.Com (Hons)', 2, 4),
  (3, 'BCOM-P', 'B.Com (Programme)', 2, 4),
  (4, 'BA-ECO', 'B.A. (Hons) Economics', 3, 4),
  (5, 'BA-ENG', 'B.A. (Hons) English', 4, 4),
  (6, 'BSC-MAT', 'B.Sc. (Hons) Mathematics', 6, 4),
  (7, 'BA-PSY', 'B.A. (Hons) Applied Psychology', 7, 4),
  (8, 'BA-POL', 'B.A. (Hons) Political Science', 8, 4),
  (9, 'BSC-STA', 'B.Sc. (Hons) Statistics', 9, 4);

INSERT INTO `courses` (id, code, title, type, department_id, lecture, tutorial, practical, credits) VALUES
  (1, 'DSC-CS-01', 'Programming using Python', 'DSC', 1, 3, 0, 1, 4),
  (2, 'DSC-CS-02', 'Computer System Architecture', 'DSC', 1, 3, 0, 1, 4),
  (3, 'DSC-CS-05', 'Data Structures', 'DSC', 1, 3, 0, 1, 4),
  (4, 'DSC-CS-13', 'Operating Systems', 'DSC', 1, 3, 0, 1, 4),
  (5, 'DSE-CS-07', 'Web Design and Development', 'DSE', 1, 3, 0, 1, 4),
  (6, 'DSE-CS-12', 'Machine Learning', 'DSE', 1, 3, 0, 1, 4),
  (7, 'GE-1a', 'Programming using Python', 'GE', 1, 3, 0, 1, 4),
  (8, 'GE-5b', 'Advanced Web Programming', 'GE', 1, 3, 0, 1, 4),
  (9, 'DSC-COM-01', 'Financial Accounting', 'DSC', 2, 3, 1, 0, 4),
  (10, 'DSC-COM-02', 'Business Laws', 'DSC', 2, 3, 1, 0, 4),
  (11, 'DSC-COM-09', 'Income Tax Law and Practice', 'DSC', 2, 3, 1, 0, 4),
  (12, 'GE-COM-1', 'Fundamentals of Marketing', 'GE', 2, 3, 1, 0, 4),
  (13, 'DSC-ECO-01', 'Introductory Microeconomics', 'DSC', 3, 3, 1, 0, 4),
  (14, 'DSC-ECO-04', 'Intermediate Macroeconomics', 'DSC', 3, 3, 1, 0, 4),
  (15, 'DSC-ENG-01', 'Indian Classical Literature', 'DSC', 4, 3, 1, 0, 4),
  (16, 'DSC-MAT-01', 'Algebra', 'DSC', 6, 3, 1, 0, 4),
  (17, 'DSC-MAT-02', 'Elementary Real Analysis', 'DSC', 6, 3, 1, 0, 4),
  (18, 'DSC-PSY-01', 'Introduction to Psychology', 'DSC', 7, 3, 0, 1, 4),
  (19, 'DSC-POL-01', 'Understanding Political Theory', 'DSC', 8, 3, 1, 0, 4),
  (20, 'DSC-STA-01', 'Descriptive Statistics', 'DSC', 9, 3, 0, 1, 4),
  (21, 'SEC-01', 'Data Analysis using Spreadsheets', 'SEC', 1, 1, 0, 1, 2),
  (22, 'VAC-01', 'Digital Empowerment', 'VAC', 1, 1, 0, 1, 2),
  (23, 'VAC-02', 'Constitutional Values and Fundamental Duties', 'VAC', 8, 2, 0, 0, 2),
  (24, 'AEC-01', 'Environmental Science: Theory into Practice', 'AEC', 10, 2, 0, 0, 2);

INSERT INTO `employees` (id, user_id, emp_code, full_name, designation, department_id, type, gender, dob, date_of_joining, email, mobile) VALUES
  (1, 2, 'RC-T-0001', 'Prof. Pooja Nair', 'Principal', 10, 'teaching', 'F', '1985-05-10', '2011-03-21', 'pooja.nair@rc.example.in', '9455681518'),
  (2, 3, 'RC-T-0002', 'Dr. Ananya Sethi', 'Associate Professor', 1, 'teaching', 'F', '1961-09-26', '2019-10-16', 'ananya.sethi@rc.example.in', '9761698716'),
  (3, 4, 'RC-T-0003', 'Muskan Joshi', 'Associate Professor', 2, 'teaching', 'F', '1990-09-22', '2016-12-20', 'muskan.joshi@rc.example.in', '9978809320'),
  (4, 5, 'RC-T-0004', 'Dr. Khushi Yadav', 'Associate Professor', 3, 'teaching', 'F', '1985-11-17', '2017-08-26', 'khushi.yadav@rc.example.in', '9317784864'),
  (5, 6, 'RC-T-0005', 'Dr. Divya Bansal', 'Associate Professor', 4, 'teaching', 'F', '1974-05-23', '2010-08-17', 'divya.bansal@rc.example.in', '9400297875'),
  (6, 7, 'RC-T-0006', 'Dr. Divya Arora', 'Associate Professor', 6, 'teaching', 'F', '1973-07-27', '2018-08-03', 'divya.arora@rc.example.in', '9045717847'),
  (7, 8, 'RC-T-0007', 'Dr. Khushi Mishra', 'Associate Professor', 7, 'teaching', 'F', '1994-02-20', '2020-04-08', 'khushi.mishra@rc.example.in', '9627010798'),
  (8, 9, 'RC-T-0008', 'Tarun Verma', 'Associate Professor', 8, 'teaching', 'M', '1985-04-16', '2013-03-20', 'tarun.verma@rc.example.in', '9452059972'),
  (9, 10, 'RC-T-0009', 'Dr. Ishita Pandey', 'Associate Professor', 9, 'teaching', 'F', '1980-01-04', '2007-02-02', 'ishita.pandey@rc.example.in', '9294866283'),
  (10, 11, 'RC-T-0010', 'Prof. Bhavya Sharma', 'Professor', 1, 'teaching', 'F', '1982-10-20', '2020-03-05', 'bhavya.sharma@rc.example.in', '9087547517'),
  (11, 12, 'RC-T-0011', 'Prof. Harsh Rana', 'Professor', 1, 'teaching', 'M', '1988-03-02', '2015-09-27', 'harsh.rana@rc.example.in', '9579318603'),
  (12, 13, 'RC-T-0012', 'Prof. Manav Negi', 'Professor', 1, 'teaching', 'M', '1989-03-13', '2015-07-18', 'manav.negi@rc.example.in', '9370283822'),
  (13, 14, 'RC-T-0013', 'Dr. Ishita Nair', 'Assistant Professor', 2, 'teaching', 'F', '1961-06-17', '2010-08-04', 'ishita.nair@rc.example.in', '9524776472'),
  (14, 15, 'RC-T-0014', 'Dr. Kavya Bansal', 'Assistant Professor', 2, 'teaching', 'F', '1979-09-22', '2013-09-07', 'kavya.bansal@rc.example.in', '9175682052'),
  (15, 16, 'RC-T-0015', 'Dr. Tarun Sethi', 'Assistant Professor', 3, 'teaching', 'M', '1993-05-04', '2019-03-04', 'tarun.sethi@rc.example.in', '9939494821'),
  (16, 17, 'RC-T-0016', 'Varun Dubey', 'Assistant Professor', 4, 'teaching', 'M', '1976-05-03', '2002-07-28', 'varun.dubey@rc.example.in', '9185491452'),
  (17, 18, 'RC-T-0017', 'Dr. Divya Sharma', 'Associate Professor', 5, 'teaching', 'F', '1992-08-20', '2018-11-23', 'divya.sharma@rc.example.in', '9304118955'),
  (18, 19, 'RC-T-0018', 'Dr. Yuvraj Malhotra', 'Assistant Professor', 6, 'teaching', 'M', '1994-06-02', '2020-12-14', 'yuvraj.malhotra@rc.example.in', '9976158464'),
  (19, 20, 'RC-T-0019', 'Prof. Pranav Sethi', 'Professor', 7, 'teaching', 'M', '1978-04-18', '2004-12-04', 'pranav.sethi@rc.example.in', '9541465908'),
  (20, 21, 'RC-T-0020', 'Dr. Tarun Singh', 'Assistant Professor', 9, 'teaching', 'M', '1979-07-13', '2007-04-25', 'tarun.singh@rc.example.in', '9285940692'),
  (21, 22, 'RC-T-0021', 'Prof. Riya Yadav', 'Professor', 8, 'teaching', 'F', '1968-01-09', '2012-08-14', 'riya.yadav@rc.example.in', '9280886423'),
  (22, 23, 'RC-N-0001', 'Aditi Yadav', 'Administrative Officer', 10, 'non-teaching', 'F', '1966-12-11', '1996-11-27', 'aditi.yadav@rc.example.in', '9912808458'),
  (23, 24, 'RC-N-0002', 'Karan Saxena', 'Section Officer', 10, 'non-teaching', 'M', '1978-04-12', '2006-08-21', 'karan.saxena@rc.example.in', '9038265384'),
  (24, 25, 'RC-N-0003', 'Neha Saxena', 'Senior Assistant', 10, 'non-teaching', 'F', '1994-09-23', '2020-01-21', 'neha.saxena@rc.example.in', '9943262705'),
  (25, 26, 'RC-N-0004', 'Aditya Kapoor', 'Store Keeper', 10, 'non-teaching', 'M', '1977-10-02', '2007-07-18', 'aditya.kapoor@rc.example.in', '9584210839'),
  (26, 27, 'RC-N-0005', 'Kunal Singh', 'Security Supervisor', 10, 'non-teaching', 'M', '1989-04-06', '2023-05-10', 'kunal.singh@rc.example.in', '9126584409'),
  (27, 28, 'RC-N-0006', 'Bhavya Yadav', 'Driver', 10, 'non-teaching', 'F', '1991-09-01', '2017-06-18', 'bhavya.yadav@rc.example.in', '9630976582'),
  (28, 29, 'RC-N-0007', 'Rohan Sharma', 'Accounts Officer', 11, 'non-teaching', 'M', '1972-10-20', '2002-03-04', 'rohan.sharma@rc.example.in', '9180515438'),
  (29, 30, 'RC-N-0008', 'Khushi Singh', 'Junior Accountant', 11, 'non-teaching', 'F', '1971-09-17', '2002-06-22', 'khushi.singh@rc.example.in', '9164941577'),
  (30, 31, 'RC-N-0009', 'Shreya Mishra', 'Librarian', 12, 'non-teaching', 'F', '1981-09-18', '2007-06-24', 'shreya.mishra@rc.example.in', '9111744325'),
  (31, 32, 'RC-N-0010', 'Charu Malhotra', 'Library Attendant', 12, 'non-teaching', 'F', '1974-03-15', '2018-10-22', 'charu.malhotra@rc.example.in', '9237355696'),
  (32, 33, 'RC-T-0022', 'Dr. Harsh Joshi', 'Hostel Warden', 14, 'teaching', 'M', '1978-03-18', '2019-02-09', 'harsh.joshi@rc.example.in', '9154742843'),
  (33, 34, 'RC-N-0011', 'Shreya Rawat', 'System Administrator', 13, 'non-teaching', 'F', '1989-11-27', '2016-09-17', 'shreya.rawat@rc.example.in', '9660856517'),
  (34, 35, 'RC-N-0012', 'Harsh Mishra', 'Lab Assistant', 13, 'non-teaching', 'M', '1978-10-21', '2014-07-27', 'harsh.mishra@rc.example.in', '9506204842'),
  (35, 36, 'RC-N-0013', 'Khushi Malhotra', 'Sports Officer', 15, 'non-teaching', 'F', '1984-05-20', '2015-06-03', 'khushi.malhotra@rc.example.in', '9003145037'),
  (36, 37, 'RC-N-0014', 'Ritesh Sharma', 'Medical Officer', 10, 'non-teaching', 'M', '1970-10-14', '2017-09-22', 'ritesh.sharma@rc.example.in', '9125373924');

INSERT INTO `students` (id, user_id, enrollment_no, full_name, dob, gender, category, programme_id, semester, admission_year, email, mobile) VALUES
  (1, 38, '26/CS/001', 'Arjun Sharma', '2008-06-12', 'M', 'OBC', 1, 1, 2026, 'arjun.sharma26@students.rc.example.in', '9460288375'),
  (2, 39, '26/COM/001', 'Saanvi Saxena', '2008-08-07', 'F', 'OBC', 2, 1, 2026, 'saanvi.saxena26@students.rc.example.in', '9540720754'),
  (3, 40, '26/COM/002', 'Muskan Dubey', '2008-05-16', 'F', 'EWS', 3, 1, 2026, 'muskan.dubey26@students.rc.example.in', '9643906188'),
  (4, 41, '26/ECO/001', 'Kunal Tiwari', '2008-03-25', 'M', 'UR', 4, 1, 2026, 'kunal.tiwari26@students.rc.example.in', '9957555218'),
  (5, 42, '26/ENG/001', 'Kavya Rana', '2008-09-07', 'F', 'UR', 5, 1, 2026, 'kavya.rana26@students.rc.example.in', '9307336882'),
  (6, 43, '26/MAT/001', 'Meera Arora', '2008-09-23', 'F', 'UR', 6, 1, 2026, 'meera.arora26@students.rc.example.in', '9556898329'),
  (7, 44, '26/PSY/001', 'Kabir Khanna', '2008-12-12', 'M', 'UR', 7, 1, 2026, 'kabir.khanna26@students.rc.example.in', '9900442248'),
  (8, 45, '26/POL/001', 'Nandini Mehta', '2008-04-17', 'F', 'OBC', 8, 1, 2026, 'nandini.mehta26@students.rc.example.in', '9022697804'),
  (9, 46, '26/STA/001', 'Manav Khanna', '2008-02-12', 'M', 'EWS', 9, 1, 2026, 'manav.khanna26@students.rc.example.in', '9625280700'),
  (10, 47, '26/CS/002', 'Aditi Sharma', '2008-04-22', 'F', 'SC', 1, 1, 2026, 'aditi.sharma26@students.rc.example.in', '9283166323'),
  (11, 48, '26/COM/003', 'Varun Mehta', '2008-10-16', 'M', 'UR', 2, 1, 2026, 'varun.mehta26@students.rc.example.in', '9866753478'),
  (12, 49, '26/COM/004', 'Ishaan Singh', '2008-07-01', 'M', 'ST', 3, 1, 2026, 'ishaan.singh26@students.rc.example.in', '9159212363'),
  (13, 50, '25/ECO/001', 'Mohit Kapoor', '2007-03-04', 'M', 'UR', 4, 3, 2025, 'mohit.kapoor25@students.rc.example.in', '9560787457'),
  (14, 51, '25/ENG/001', 'Varun Rawat', '2007-08-12', 'M', 'ST', 5, 3, 2025, 'varun.rawat25@students.rc.example.in', '9699085417'),
  (15, 52, '25/MAT/001', 'Aditya Bhatia', '2007-10-16', 'M', 'ST', 6, 3, 2025, 'aditya.bhatia25@students.rc.example.in', '9018791697'),
  (16, 53, '25/PSY/001', 'Kabir Malhotra', '2007-01-24', 'M', 'UR', 7, 3, 2025, 'kabir.malhotra25@students.rc.example.in', '9201612840'),
  (17, 54, '25/POL/001', 'Siddharth Sethi', '2007-09-28', 'M', 'OBC', 8, 3, 2025, 'siddharth.sethi25@students.rc.example.in', '9247357363'),
  (18, 55, '25/STA/001', 'Diya Saxena', '2007-07-16', 'F', 'SC', 9, 3, 2025, 'diya.saxena25@students.rc.example.in', '9392731979'),
  (19, 56, '25/CS/001', 'Dev Bansal', '2007-05-25', 'M', 'OBC', 1, 3, 2025, 'dev.bansal25@students.rc.example.in', '9608724385'),
  (20, 57, '25/COM/001', 'Varun Rana', '2007-09-06', 'M', 'SC', 2, 3, 2025, 'varun.rana25@students.rc.example.in', '9828195391'),
  (21, 58, '25/COM/002', 'Kavya Singh', '2007-05-12', 'F', 'UR', 3, 3, 2025, 'kavya.singh25@students.rc.example.in', '9660330428'),
  (22, 59, '25/ECO/002', 'Nandini Verma', '2007-11-02', 'F', 'UR', 4, 3, 2025, 'nandini.verma25@students.rc.example.in', '9381780578'),
  (23, 60, '25/ENG/002', 'Diya Kapoor', '2007-08-09', 'F', 'UR', 5, 3, 2025, 'diya.kapoor25@students.rc.example.in', '9121430490'),
  (24, 61, '25/MAT/002', 'Ritesh Negi', '2007-09-13', 'M', 'SC', 6, 3, 2025, 'ritesh.negi25@students.rc.example.in', '9898370853'),
  (25, 62, '24/PSY/001', 'Karan Verma', '2006-04-06', 'M', 'ST', 7, 5, 2024, 'karan.verma24@students.rc.example.in', '9066817922'),
  (26, 63, '24/POL/001', 'Kunal Malhotra', '2006-10-28', 'M', 'SC', 8, 5, 2024, 'kunal.malhotra24@students.rc.example.in', '9642710512'),
  (27, 64, '24/STA/001', 'Dev Sethi', '2006-02-23', 'M', 'SC', 9, 5, 2024, 'dev.sethi24@students.rc.example.in', '9252781206'),
  (28, 65, '24/CS/001', 'Shreya Malhotra', '2006-04-10', 'F', 'EWS', 1, 5, 2024, 'shreya.malhotra24@students.rc.example.in', '9851904316'),
  (29, 66, '24/COM/001', 'Pranav Dubey', '2006-11-16', 'M', 'UR', 2, 5, 2024, 'pranav.dubey24@students.rc.example.in', '9800527913'),
  (30, 67, '24/COM/002', 'Kavya Gupta', '2006-08-20', 'F', 'OBC', 3, 5, 2024, 'kavya.gupta24@students.rc.example.in', '9209842640'),
  (31, 68, '24/ECO/001', 'Yuvraj Negi', '2006-07-09', 'M', 'SC', 4, 5, 2024, 'yuvraj.negi24@students.rc.example.in', '9079113571'),
  (32, 69, '24/ENG/001', 'Tanvi Joshi', '2006-09-01', 'F', 'UR', 5, 5, 2024, 'tanvi.joshi24@students.rc.example.in', '9796745219'),
  (33, 70, '24/MAT/001', 'Riya Sethi', '2006-07-07', 'F', 'EWS', 6, 5, 2024, 'riya.sethi24@students.rc.example.in', '9521362958'),
  (34, 71, '24/PSY/002', 'Neha Arora', '2006-12-20', 'F', 'UR', 7, 5, 2024, 'neha.arora24@students.rc.example.in', '9645732840'),
  (35, 72, '24/POL/002', 'Nandini Pandey', '2006-10-17', 'F', 'OBC', 8, 5, 2024, 'nandini.pandey24@students.rc.example.in', '9927670940'),
  (36, 73, '24/STA/002', 'Divya Chauhan', '2006-04-01', 'F', 'OBC', 9, 5, 2024, 'divya.chauhan24@students.rc.example.in', '9884985568'),
  (37, 74, '23/CS/001', 'Charu Rana', '2005-04-06', 'F', 'SC', 1, 7, 2023, 'charu.rana23@students.rc.example.in', '9538965013'),
  (38, 75, '23/COM/001', 'Khushi Mehta', '2005-04-13', 'F', 'UR', 2, 7, 2023, 'khushi.mehta23@students.rc.example.in', '9683508111'),
  (39, 76, '23/COM/002', 'Vidhi Mishra', '2005-06-22', 'F', 'UR', 3, 7, 2023, 'vidhi.mishra23@students.rc.example.in', '9816354011'),
  (40, 77, '23/ECO/001', 'Divya Singh', '2005-06-08', 'F', 'UR', 4, 7, 2023, 'divya.singh23@students.rc.example.in', '9138759211'),
  (41, 78, '23/ENG/001', 'Gauri Singh', '2005-08-02', 'F', 'SC', 5, 7, 2023, 'gauri.singh23@students.rc.example.in', '9862107617'),
  (42, 79, '23/MAT/001', 'Bhavya Dubey', '2005-04-14', 'F', 'UR', 6, 7, 2023, 'bhavya.dubey23@students.rc.example.in', '9106261488'),
  (43, 80, '23/PSY/001', 'Nandini Sharma', '2005-02-14', 'F', 'UR', 7, 7, 2023, 'nandini.sharma23@students.rc.example.in', '9299446135'),
  (44, 81, '23/POL/001', 'Aditi Pandey', '2005-05-02', 'F', 'ST', 8, 7, 2023, 'aditi.pandey23@students.rc.example.in', '9994398286'),
  (45, 82, '23/STA/001', 'Muskan Rawat', '2005-05-09', 'F', 'OBC', 9, 7, 2023, 'muskan.rawat23@students.rc.example.in', '9464397258'),
  (46, 83, '23/CS/002', 'Tarun Chauhan', '2005-11-14', 'M', 'EWS', 1, 7, 2023, 'tarun.chauhan23@students.rc.example.in', '9039955156'),
  (47, 84, '23/COM/003', 'Vidhi Nair', '2005-01-25', 'F', 'OBC', 2, 7, 2023, 'vidhi.nair23@students.rc.example.in', '9156795965'),
  (48, 85, '23/COM/004', 'Bhavya Joshi', '2005-09-18', 'F', 'UR', 3, 7, 2023, 'bhavya.joshi23@students.rc.example.in', '9705670904');

UPDATE departments SET hod_employee_id = 1 WHERE code = 'ADM';
UPDATE departments SET hod_employee_id = 3 WHERE code = 'COM';
UPDATE departments SET hod_employee_id = 2 WHERE code = 'CS';
UPDATE departments SET hod_employee_id = 4 WHERE code = 'ECO';
UPDATE departments SET hod_employee_id = 5 WHERE code = 'ENG';
UPDATE departments SET hod_employee_id = 6 WHERE code = 'MAT';
UPDATE departments SET hod_employee_id = 8 WHERE code = 'POL';
UPDATE departments SET hod_employee_id = 7 WHERE code = 'PSY';
UPDATE departments SET hod_employee_id = 9 WHERE code = 'STA';

INSERT INTO notifications (user_id, module_id, title, link) VALUES (1, 'P00', 'Welcome to RC Campus Connect', NULL);
