-- ============================================================
--  School Management System - Database Schema
--  File: school_system.sql
--  Description: Complete database schema with sample data
-- ============================================================

CREATE DATABASE IF NOT EXISTS school_system
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE school_system;

-- ============================================================
-- TABLE: admin
-- Stores admin login credentials
-- ============================================================
CREATE TABLE IF NOT EXISTS admin (
    id          INT(11)         NOT NULL AUTO_INCREMENT,
    username    VARCHAR(100)    NOT NULL UNIQUE,
    password    VARCHAR(255)    NOT NULL,           -- Stored as bcrypt hash
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default admin account
-- Username: admin | Password: admin123 (bcrypt hashed)
INSERT INTO admin (username, password) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');


-- ============================================================
-- TABLE: classes
-- Stores class/grade information
-- ============================================================
CREATE TABLE IF NOT EXISTS classes (
    id          INT(11)         NOT NULL AUTO_INCREMENT,
    name        VARCHAR(100)    NOT NULL,           -- e.g. "Class 1", "Grade 5"
    section     VARCHAR(10)     DEFAULT NULL,       -- e.g. "A", "B" (optional)
    fee         DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample classes
INSERT INTO classes (name, section, fee) VALUES
('Class 1',  'A', 2000.00),
('Class 1',  'B', 2000.00),
('Class 2',  'A', 2200.00),
('Class 3',  'A', 2400.00),
('Class 4',  'A', 2600.00),
('Class 5',  'A', 2800.00),
('Class 6',  'A', 3000.00),
('Class 7',  'A', 3200.00),
('Class 8',  'A', 3500.00);


-- ============================================================
-- TABLE: students
-- Stores student personal and academic information
-- ============================================================
CREATE TABLE IF NOT EXISTS students (
    id              INT(11)         NOT NULL AUTO_INCREMENT,
    name            VARCHAR(150)    NOT NULL,
    father_name     VARCHAR(150)    NOT NULL,
    cnic            VARCHAR(20)     DEFAULT NULL,   -- CNIC or B-Form number
    contact         VARCHAR(20)     DEFAULT NULL,
    address         TEXT            DEFAULT NULL,
    class_id        INT(11)         NOT NULL,
    admission_date  DATE            NOT NULL,
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_student_class
        FOREIGN KEY (class_id)
        REFERENCES classes(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample students
INSERT INTO students (name, father_name, cnic, contact, address, class_id, admission_date) VALUES
('Ahmed Ali',       'Ali Hassan',       '42101-1234567-1', '0300-1234567', 'House 12, Block 5, Karachi',    1, '2024-04-01'),
('Sara Khan',       'Imran Khan',       '42101-2345678-2', '0301-2345678', 'Flat 3B, Gulshan, Karachi',     1, '2024-04-01'),
('Usman Tariq',     'Tariq Mehmood',    '42101-3456789-3', '0302-3456789', 'Street 7, Saddar, Karachi',     2, '2024-04-02'),
('Fatima Noor',     'Noor Ahmed',       '42101-4567890-4', '0303-4567890', 'Plot 22, North Nazimabad',      3, '2024-04-03'),
('Bilal Hussain',   'Hussain Shah',     '42101-5678901-5', '0304-5678901', 'House 5, Liaquatabad, Karachi', 4, '2024-04-05'),
('Ayesha Siddiqui', 'Siddiqui Rafiq',   '42101-6789012-6', '0305-6789012', 'Street 9, PECHS, Karachi',      5, '2024-04-07'),
('Zain Malik',      'Malik Farrukh',    '42101-7890123-7', '0306-7890123', 'House 44, DHA Phase 2',         6, '2024-04-08'),
('Hina Baig',       'Baig Anwar',       '42101-8901234-8', '0307-8901234', 'Flat 11, Clifton Block 4',      7, '2024-04-10'),
('Omar Farooq',     'Farooq Azam',      '42101-9012345-9', '0308-9012345', 'House 3, Gulistan-e-Johar',     8, '2024-04-12'),
('Maryam Iqbal',    'Iqbal Saeed',      '42101-0123456-0', '0309-0123456', 'Block 14, Federal B Area',      9, '2024-04-15');


-- ============================================================
-- TABLE: staff
-- Stores staff/teacher information
-- ============================================================
CREATE TABLE IF NOT EXISTS staff (
    id          INT(11)         NOT NULL AUTO_INCREMENT,
    name        VARCHAR(150)    NOT NULL,
    role        ENUM('Teacher', 'Admin', 'Accountant', 'Peon', 'Other')
                                NOT NULL DEFAULT 'Teacher',
    salary      DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    contact     VARCHAR(20)     DEFAULT NULL,
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample staff
INSERT INTO staff (name, role, salary, contact) VALUES
('Mr. Khalid Raza',     'Teacher',      35000.00, '0311-1112222'),
('Ms. Sana Mirza',      'Teacher',      32000.00, '0312-2223333'),
('Mr. Asif Jamali',     'Teacher',      30000.00, '0313-3334444'),
('Ms. Rubina Qureshi',  'Teacher',      30000.00, '0314-4445555'),
('Mr. Tahir Nawaz',     'Admin',        40000.00, '0315-5556666'),
('Ms. Nadia Ansari',    'Accountant',   38000.00, '0316-6667777'),
('Mr. Saleem Butt',     'Peon',         18000.00, '0317-7778888');


-- ============================================================
-- TABLE: fees
-- Tracks monthly fee payments per student
-- ============================================================
CREATE TABLE IF NOT EXISTS fees (
    id              INT(11)         NOT NULL AUTO_INCREMENT,
    student_id      INT(11)         NOT NULL,
    amount          DECIMAL(10,2)   NOT NULL,
    month           VARCHAR(20)     NOT NULL,       -- e.g. "April 2025"
    status          ENUM('Paid', 'Unpaid')
                                    NOT NULL DEFAULT 'Unpaid',
    payment_date    DATE            DEFAULT NULL,   -- NULL if unpaid
    remarks         VARCHAR(255)    DEFAULT NULL,
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_fee_student
        FOREIGN KEY (student_id)
        REFERENCES students(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample fee records
INSERT INTO fees (student_id, amount, month, status, payment_date) VALUES
(1,  2000.00, 'April 2025',   'Paid',   '2025-04-05'),
(1,  2000.00, 'May 2025',     'Unpaid', NULL),
(2,  2000.00, 'April 2025',   'Paid',   '2025-04-06'),
(2,  2000.00, 'May 2025',     'Paid',   '2025-05-03'),
(3,  2200.00, 'April 2025',   'Paid',   '2025-04-07'),
(3,  2200.00, 'May 2025',     'Unpaid', NULL),
(4,  2400.00, 'April 2025',   'Unpaid', NULL),
(5,  2600.00, 'April 2025',   'Paid',   '2025-04-10'),
(6,  2800.00, 'April 2025',   'Paid',   '2025-04-11'),
(7,  3000.00, 'April 2025',   'Unpaid', NULL),
(8,  3200.00, 'April 2025',   'Paid',   '2025-04-14'),
(9,  3500.00, 'April 2025',   'Paid',   '2025-04-16'),
(10, 3500.00, 'April 2025',   'Unpaid', NULL);


-- ============================================================
-- TABLE: expenses
-- Tracks school operational expenses
-- ============================================================
CREATE TABLE IF NOT EXISTS expenses (
    id          INT(11)         NOT NULL AUTO_INCREMENT,
    title       VARCHAR(200)    NOT NULL,
    amount      DECIMAL(10,2)   NOT NULL,
    date        DATE            NOT NULL,
    description TEXT            DEFAULT NULL,
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample expenses
INSERT INTO expenses (title, amount, date, description) VALUES
('Electricity Bill',        4500.00,  '2025-04-02',  'Monthly electricity bill for April'),
('Water Bill',              1200.00,  '2025-04-02',  'Monthly water charges'),
('Stationery Purchase',     3800.00,  '2025-04-05',  'Pens, registers, and other stationery'),
('Cleaning Supplies',       1500.00,  '2025-04-07',  'Mop, detergent, and supplies'),
('Internet Bill',           2500.00,  '2025-04-10',  'Monthly internet package'),
('Maintenance / Repair',    6000.00,  '2025-04-15',  'Classroom furniture repair'),
('Printing & Photocopying', 2200.00,  '2025-04-20',  'Exam papers printing'),
('Generator Fuel',          3500.00,  '2025-04-22',  'Diesel for backup generator'),
('Electricity Bill',        4700.00,  '2025-05-02',  'Monthly electricity bill for May'),
('Stationery Purchase',     2100.00,  '2025-05-08',  'Monthly stationery restock');


-- ============================================================
-- TABLE: timetable
-- Stores class schedules per teacher and day
-- ============================================================
CREATE TABLE IF NOT EXISTS timetable (
    id          INT(11)         NOT NULL AUTO_INCREMENT,
    class_id    INT(11)         NOT NULL,
    teacher_id  INT(11)         NOT NULL,
    day         ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday')
                                NOT NULL,
    time_slot   VARCHAR(50)     NOT NULL,           -- e.g. "08:00 AM - 09:00 AM"
    subject     VARCHAR(100)    DEFAULT NULL,       -- e.g. "Mathematics"
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_tt_class
        FOREIGN KEY (class_id)
        REFERENCES classes(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_tt_teacher
        FOREIGN KEY (teacher_id)
        REFERENCES staff(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample timetable entries
INSERT INTO timetable (class_id, teacher_id, day, time_slot, subject) VALUES
(1, 1, 'Monday',    '08:00 AM - 09:00 AM', 'Mathematics'),
(1, 2, 'Monday',    '09:00 AM - 10:00 AM', 'English'),
(1, 3, 'Monday',    '10:30 AM - 11:30 AM', 'Urdu'),
(2, 1, 'Tuesday',   '08:00 AM - 09:00 AM', 'Mathematics'),
(2, 2, 'Tuesday',   '09:00 AM - 10:00 AM', 'Science'),
(3, 4, 'Wednesday', '08:00 AM - 09:00 AM', 'Islamiat'),
(3, 1, 'Wednesday', '09:00 AM - 10:00 AM', 'Mathematics'),
(4, 2, 'Thursday',  '08:00 AM - 09:00 AM', 'English'),
(4, 3, 'Thursday',  '09:00 AM - 10:00 AM', 'Urdu'),
(5, 4, 'Friday',    '08:00 AM - 09:00 AM', 'Islamiat'),
(5, 1, 'Friday',    '09:00 AM - 10:00 AM', 'Mathematics');


-- ============================================================
-- USEFUL VIEWS (optional but helpful)
-- ============================================================

-- View: Student with class name
CREATE OR REPLACE VIEW view_students AS
SELECT
    s.id,
    s.name,
    s.father_name,
    s.cnic,
    s.contact,
    s.address,
    s.admission_date,
    c.name    AS class_name,
    c.section AS class_section,
    c.fee     AS class_fee
FROM students s
JOIN classes c ON s.class_id = c.id;


-- View: Fee records with student and class info
CREATE OR REPLACE VIEW view_fees AS
SELECT
    f.id,
    s.name          AS student_name,
    c.name          AS class_name,
    c.section       AS class_section,
    f.amount,
    f.month,
    f.status,
    f.payment_date,
    f.remarks
FROM fees f
JOIN students s ON f.student_id = s.id
JOIN classes  c ON s.class_id   = c.id;


-- View: Timetable with class and teacher names
CREATE OR REPLACE VIEW view_timetable AS
SELECT
    t.id,
    c.name      AS class_name,
    c.section   AS class_section,
    st.name     AS teacher_name,
    t.day,
    t.time_slot,
    t.subject
FROM timetable t
JOIN classes c  ON t.class_id   = c.id
JOIN staff   st ON t.teacher_id = st.id;


-- ============================================================
-- END OF SCHEMA
-- ============================================================
