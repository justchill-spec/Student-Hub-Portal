USE studenthub_db;

ALTER TABLE users
    ADD COLUMN role ENUM('student', 'admin') NOT NULL DEFAULT 'student' AFTER gender;

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
