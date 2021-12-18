CREATE TABLE tk_users_address (
    id INT(12) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(32) NOT NULL,
    address_label VARCHAR(64),
    address_number INT(8),
    address_line_1 VARCHAR(64),
    address_line_2 VARCHAR(64),
    barangay VARCHAR(64),
    city VARCHAR(64),
    town VARCHAR(64),
    provice VARCHAR(64),
    state VARCHAR(64),
    region VARCHAR(64),
    zipcode VARCHAR(18),
    address_group VARCHAR(64),
    address_type VARCHAR(64)
);
