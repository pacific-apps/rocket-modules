CREATE TABLE tk_users_profile (
    id INT(12) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(32) NOT NULL,
    first_name VARCHAR(64) NOT NULL,
    last_name VARCHAR(64) NOT NULL,
    middle_name VARCHAR(64),
    suffix VARCHAR(32),
    profile_photo TEXT,
    birth_date VARCHAR(32),
    gender VARCHAR(32)
);
