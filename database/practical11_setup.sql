USE studenthub_db;

SET @course_column_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'students'
      AND COLUMN_NAME = 'course'
);
SET @course_column_migration = IF(
    @course_column_exists = 0,
    'ALTER TABLE students ADD COLUMN course VARCHAR(50) NOT NULL DEFAULT ''BTech Computer Engineering'' AFTER semester',
    'SELECT 1'
);
PREPARE course_column_statement FROM @course_column_migration;
EXECUTE course_column_statement;
DEALLOCATE PREPARE course_column_statement;

INSERT IGNORE INTO students
    (student_id, student_code, full_name, department_id, semester, course, email, phone, cgpa, status)
VALUES
    (17, 'STU017', 'Neel Shah', 1, 2, 'BTech Computer Engineering', 'neel.shah@studenthub.edu', '9000000017', 8.36, 'Active'),
    (18, 'STU018', 'Aisha Khan', 2, 6, 'BSc IT', 'aisha.khan@studenthub.edu', '9000000018', 9.08, 'Active'),
    (19, 'STU019', 'Dev Malhotra', 3, 4, 'BTech Mechanical Engineering', 'dev.malhotra@studenthub.edu', '9000000019', 7.92, 'Active'),
    (20, 'STU020', 'Kavya Reddy', 4, 8, 'BTech Civil Engineering', 'kavya.reddy@studenthub.edu', '9000000020', 8.71, 'Active');
