CREATE TABLE tk_users_verf (
    id INT(12) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(32) NOT NULL,
    verf_key VARCHAR(64) NOT NULL,
    verf_for VARCHAR(32) NOT NULL,
    valid_until VARCHAR(32) NOT NULL,
    is_completed TINYINT(1) NOT NULL
);
