CREATE DATABASE IF NOT EXISTS sales_tracking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sales_tracking;

CREATE TABLE IF NOT EXISTS sellers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    referral_code VARCHAR(50) NOT NULL UNIQUE,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    commission_rate DECIMAL(5,2) NOT NULL DEFAULT 10.00,
    commission_percent DECIMAL(5,2) NOT NULL DEFAULT 10.00,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS seller_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seller_id INT NOT NULL,
    link_code VARCHAR(50) NOT NULL UNIQUE,
    link_label VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_seller_links_seller FOREIGN KEY (seller_id) REFERENCES sellers(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_code VARCHAR(50) NOT NULL UNIQUE,
    buyer_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    buyer_email VARCHAR(150) DEFAULT NULL,
    product VARCHAR(100) NOT NULL,
    price INT NOT NULL,
    seller_id INT DEFAULT NULL,
    seller_link_code VARCHAR(50) DEFAULT NULL,
    commission_amount INT NOT NULL DEFAULT 0,
    payment_method VARCHAR(30) NOT NULL DEFAULT 'Not selected',
    payment_proof VARCHAR(255) DEFAULT NULL,
    status ENUM('pending','paid','failed','rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT IGNORE INTO sellers (id, name, referral_code, username, password_hash, commission_rate, commission_percent)
VALUES
    (1, 'Default Seller', 'seller_default_001', 'seller1', '$2y$10$8A6JY85AszQ2YzY0w6uEUe8jO9fZ5iPGfQ4rYhhtYAn7q4Qn8NVc2', 10.00, 10.00);
