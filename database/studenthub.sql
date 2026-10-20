CREATE DATABASE IF NOT EXISTS studenthub_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE studenthub_db;

CREATE TABLE IF NOT EXISTS departments (
    department_id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    department_name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS students (
    student_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_code VARCHAR(20) NOT NULL UNIQUE,
    full_name VARCHAR(120) NOT NULL,
    department_id SMALLINT UNSIGNED NOT NULL,
    semester TINYINT UNSIGNED NOT NULL,
    course VARCHAR(50) NOT NULL DEFAULT 'BTech Computer Engineering',
    email VARCHAR(254) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    cgpa DECIMAL(4, 2) NOT NULL,
    status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
    CONSTRAINT chk_students_semester CHECK (semester BETWEEN 1 AND 12),
    CONSTRAINT chk_students_cgpa CHECK (cgpa BETWEEN 0.00 AND 10.00),
    CONSTRAINT fk_students_department
        FOREIGN KEY (department_id) REFERENCES departments (department_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS event_categories (
    category_id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(60) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS events (
    event_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_code VARCHAR(20) NOT NULL UNIQUE,
    title VARCHAR(150) NOT NULL,
    category_id SMALLINT UNSIGNED NOT NULL,
    event_date DATE NOT NULL,
    event_time TIME NOT NULL,
    venue VARCHAR(150) NOT NULL,
    organizer VARCHAR(120) NOT NULL,
    seat_capacity SMALLINT UNSIGNED NOT NULL,
    description TEXT NOT NULL,
    poster_path VARCHAR(255) NULL,
    status ENUM('Draft', 'Published', 'Cancelled') NOT NULL DEFAULT 'Published',
    CONSTRAINT fk_events_category
        FOREIGN KEY (category_id) REFERENCES event_categories (category_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS registrations (
    registration_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    event_id INT UNSIGNED NOT NULL,
    registered_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Registered', 'Cancelled', 'Attended') NOT NULL DEFAULT 'Registered',
    CONSTRAINT uq_registration_student_event UNIQUE (student_id, event_id),
    INDEX idx_registrations_event_status (event_id, status),
    CONSTRAINT fk_registrations_student
        FOREIGN KEY (student_id) REFERENCES students (student_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_registrations_event
        FOREIGN KEY (event_id) REFERENCES events (event_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users (
    user_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(20) NOT NULL,
    full_name VARCHAR(50) NOT NULL,
    email VARCHAR(254) NOT NULL,
    mobile VARCHAR(10) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    course VARCHAR(50) NOT NULL,
    study_year TINYINT UNSIGNED NOT NULL,
    gender ENUM('Male', 'Female', 'Other') NOT NULL,
    role ENUM('student', 'admin') NOT NULL DEFAULT 'student',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_users_username UNIQUE (username),
    CONSTRAINT uq_users_email UNIQUE (email),
    CONSTRAINT chk_users_study_year CHECK (study_year BETWEEN 1 AND 4)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS remember_tokens (
    selector CHAR(24) NOT NULL PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_remember_tokens_user (user_id),
    CONSTRAINT fk_remember_tokens_user
        FOREIGN KEY (user_id) REFERENCES users (user_id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT IGNORE INTO users
    (username, full_name, email, mobile, password_hash, course, study_year, gender, role)
VALUES
    ('ADMIN', 'StudentHub Administrator', 'admin@studenthub.edu', '9999999999',
     '$2y$10$XnnqeLIQkyt/IRcFCY//ieSRMT.1bKQk9VV5ulBshuLamW36tDChe',
     'BCA', 1, 'Other', 'admin'),
    ('STUDEMO01', 'Demo Student', 'student.demo@studenthub.edu', '9876543210',
     '$2y$10$sFrVYf1qKUPTG3OqQyXmSek4cigZy/W4blCAFrizUrexlOkkcljiW',
     'BCA', 2, 'Other', 'student');

INSERT IGNORE INTO departments (department_name) VALUES
    ('Computer Engineering'),
    ('Information Technology'),
    ('Mechanical Engineering'),
    ('Civil Engineering');

INSERT IGNORE INTO event_categories (category_name) VALUES
    ('Technical'),
    ('Workshop'),
    ('Sports'),
    ('Seminar'),
    ('Cultural'),
    ('Academic'),
    ('Community');

INSERT IGNORE INTO students
    (student_id, student_code, full_name, department_id, semester, course, email, phone, cgpa, status)
VALUES
    (1, 'STU001', 'Aarav Sharma', 1, 6, 'BTech Computer Engineering', 'aarav.sharma@studenthub.edu', '9000000001', 9.21, 'Active'),
    (2, 'STU002', 'Diya Patel', 2, 4, 'BSc IT', 'diya.patel@studenthub.edu', '9000000002', 8.76, 'Active'),
    (3, 'STU003', 'Rohan Mehta', 3, 8, 'BTech Mechanical Engineering', 'rohan.mehta@studenthub.edu', '9000000003', 8.42, 'Active'),
    (4, 'STU004', 'Ananya Iyer', 4, 6, 'BTech Civil Engineering', 'ananya.iyer@studenthub.edu', '9000000004', 9.04, 'Active'),
    (5, 'STU005', 'Kabir Singh', 1, 4, 'BTech Computer Engineering', 'kabir.singh@studenthub.edu', '9000000005', 7.98, 'Active'),
    (6, 'STU006', 'Meera Nair', 2, 8, 'BSc IT', 'meera.nair@studenthub.edu', '9000000006', 9.35, 'Active'),
    (7, 'STU007', 'Vihaan Rao', 4, 2, 'BTech Civil Engineering', 'vihaan.rao@studenthub.edu', '9000000007', 8.11, 'Active'),
    (8, 'STU008', 'Ishita Desai', 3, 6, 'BTech Mechanical Engineering', 'ishita.desai@studenthub.edu', '9000000008', 8.89, 'Active'),
    (9, 'STU009', 'Arjun Verma', 1, 8, 'BTech Computer Engineering', 'arjun.verma@studenthub.edu', '9000000009', 9.48, 'Active'),
    (10, 'STU010', 'Sara Khan', 2, 2, 'BSc IT', 'sara.khan@studenthub.edu', '9000000010', 8.63, 'Active'),
    (11, 'STU011', 'Aditya Joshi', 4, 4, 'BTech Civil Engineering', 'aditya.joshi@studenthub.edu', '9000000011', 7.84, 'Active'),
    (12, 'STU012', 'Nisha Kulkarni', 3, 8, 'BTech Mechanical Engineering', 'nisha.kulkarni@studenthub.edu', '9000000012', 9.12, 'Active'),
    (13, 'STU013', 'Yash Gupta', 1, 2, 'BTech Computer Engineering', 'yash.gupta@studenthub.edu', '9000000013', 8.28, 'Active'),
    (14, 'STU014', 'Tara Menon', 2, 6, 'BSc IT', 'tara.menon@studenthub.edu', '9000000014', 9.01, 'Active'),
    (15, 'STU015', 'Manav Shah', 4, 8, 'BTech Civil Engineering', 'manav.shah@studenthub.edu', '9000000015', 8.57, 'Active'),
    (16, 'STU016', 'Priya Das', 3, 4, 'BTech Mechanical Engineering', 'priya.das@studenthub.edu', '9000000016', 8.94, 'Active'),
    (17, 'STU017', 'Neel Shah', 1, 2, 'BTech Computer Engineering', 'neel.shah@studenthub.edu', '9000000017', 8.36, 'Active'),
    (18, 'STU018', 'Aisha Khan', 2, 6, 'BSc IT', 'aisha.khan@studenthub.edu', '9000000018', 9.08, 'Active'),
    (19, 'STU019', 'Dev Malhotra', 3, 4, 'BTech Mechanical Engineering', 'dev.malhotra@studenthub.edu', '9000000019', 7.92, 'Active'),
    (20, 'STU020', 'Kavya Reddy', 4, 8, 'BTech Civil Engineering', 'kavya.reddy@studenthub.edu', '9000000020', 8.71, 'Active');

INSERT IGNORE INTO events
    (event_id, event_code, title, category_id, event_date, event_time, venue, organizer, seat_capacity, description)
VALUES
    (1, 'EVT001', 'Campus Hackathon', 1, '2026-10-05', '09:00:00', 'Innovation Lab', 'Computer Club', 120, 'A one-day coding challenge focused on practical campus solutions.'),
    (2, 'EVT002', 'AWS Cloud Workshop', 2, '2026-10-08', '10:00:00', 'Seminar Hall A', 'Cloud Society', 80, 'Learn cloud deployment, storage, and basic serverless workflows.'),
    (3, 'EVT003', 'RoboWars', 1, '2026-10-12', '11:00:00', 'Main Auditorium', 'Robotics Club', 200, 'Student teams compete with autonomous robots in a friendly arena.'),
    (4, 'EVT004', 'Welcome Sports Meet', 3, '2026-10-15', '08:00:00', 'College Ground', 'Sports Committee', 300, 'An inter-department sports meet with track and team events.'),
    (5, 'EVT005', 'Career Skills Seminar', 4, '2026-10-18', '14:00:00', 'Seminar Hall B', 'Training Cell', 150, 'Industry speakers discuss resumes, interviews, and workplace skills.'),
    (6, 'EVT006', 'Cultural Fest Auditions', 5, '2026-10-21', '13:00:00', 'Activity Room', 'Cultural Council', 100, 'Auditions for music, dance, theatre, and creative performances.'),
    (7, 'EVT007', 'Open Source Day', 1, '2026-10-24', '09:30:00', 'Computer Lab 2', 'Developer Community', 90, 'Discover open source projects and learn how to make your first contribution.'),
    (8, 'EVT008', 'Entrepreneurship Talk', 4, '2026-10-27', '15:00:00', 'Innovation Lab', 'E-Cell', 110, 'Founders share lessons from building technology businesses.'),
    (9, 'EVT009', 'Library Orientation', 6, '2026-10-30', '10:30:00', 'Central Library', 'Library Team', 60, 'Understand library services, digital resources, and borrowing rules.'),
    (10, 'EVT010', 'Green Campus Drive', 7, '2026-11-02', '07:30:00', 'North Gate', 'Eco Club', 180, 'A campus cleanup and tree planting activity for volunteers.'),
    (11, 'EVT011', 'UI Design Masterclass', 2, '2026-11-05', '10:00:00', 'Design Studio', 'Design Club', 70, 'Practice layout, accessibility, and interface prototyping techniques.'),
    (12, 'EVT012', 'Alumni Interaction', 7, '2026-11-08', '16:00:00', 'Main Auditorium', 'Alumni Cell', 220, 'Meet alumni and hear about careers after graduation.'),
    (13, 'EVT013', 'Photography Walk', 5, '2026-11-11', '06:00:00', 'East Gate', 'Arts Club', 50, 'Explore campus architecture and practice outdoor photography.'),
    (14, 'EVT014', 'Health and Wellness Camp', 7, '2026-11-14', '09:00:00', 'Medical Centre', 'Student Welfare Cell', 100, 'Free health checks and sessions on student wellness.'),
    (15, 'EVT015', 'Data Science Meetup', 1, '2026-11-17', '14:30:00', 'Computer Lab 1', 'Data Science Club', 85, 'Discuss data analysis projects, tools, and responsible AI.'),
    (16, 'EVT016', 'Annual Tech Fest', 1, '2026-11-20', '09:00:00', 'Open Air Theatre', 'Student Council', 500, 'A campus-wide celebration of technology, creativity, and teamwork.');

INSERT IGNORE INTO registrations (registration_id, student_id, event_id, status) VALUES
    (1, 1, 1, 'Registered'),
    (2, 2, 2, 'Registered'),
    (3, 3, 3, 'Registered'),
    (4, 4, 4, 'Registered'),
    (5, 5, 5, 'Registered');
