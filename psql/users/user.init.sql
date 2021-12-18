CREATE TABLE tk_users_primary (
    id INT(12) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(32) NOT NULL,
    created_at VARCHAR(32) NOT NULL,
    activated_at VARCHAR(32) NOT NULL,
    email VARCHAR(64) NOT NULL,
    role VARCHAR(32) NOT NULL,
    permissions VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    username VARCHAR(32) NOT NULL,
    status VARCHAR(32) NOT NULL
);
