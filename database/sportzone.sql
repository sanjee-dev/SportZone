-- SportZone database (MySQL 5.7+ / MariaDB 10.3+, InnoDB, utf8mb4)
-- Import through phpMyAdmin. No admin password is stored here.
-- Create the first admin with: php cli/create_admin.php "admin" "admin@example.com"
-- Sample products use placeholder brands and no photos; replace them from the admin panel.

CREATE DATABASE IF NOT EXISTS sportzone CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sportzone;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS admin_users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE admin_users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(120) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('super_admin','product_manager','order_manager','customer_support') NOT NULL DEFAULT 'customer_support',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  slug VARCHAR(120) NOT NULL UNIQUE,
  description TEXT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE products (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id INT UNSIGNED NULL,
  name VARCHAR(150) NOT NULL,
  slug VARCHAR(180) NOT NULL UNIQUE,
  sku VARCHAR(50) NOT NULL UNIQUE,
  brand VARCHAR(80) NOT NULL DEFAULT '',
  description TEXT NULL,
  price DECIMAL(10,2) NOT NULL,
  discount_price DECIMAL(10,2) NULL,
  stock INT UNSIGNED NOT NULL DEFAULT 0,
  image VARCHAR(255) NULL,
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_products_category (category_id),
  INDEX idx_products_flags (is_active, is_featured),
  CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO categories (id, name, slug, description) VALUES
(1, 'Football', 'football', 'Balls, gloves, shin guards and training gear'),
(2, 'Cricket', 'cricket', 'Bats, balls, helmets and protective pads'),
(3, 'Badminton', 'badminton', 'Rackets, shuttlecocks and accessories'),
(4, 'Basketball', 'basketball', 'Indoor and outdoor basketballs'),
(5, 'Tennis', 'tennis', 'Rackets, balls and grips'),
(6, 'Gym & Fitness', 'gym-fitness', 'Training equipment for home and gym'),
(7, 'Sports Shoes', 'sports-shoes', 'Running, training and court footwear'),
(8, 'Sportswear', 'sportswear', 'Performance tops, shorts and tracksuits');

INSERT INTO products (category_id, name, slug, sku, brand, description, price, discount_price, stock, is_featured, is_active) VALUES
(1, 'Professional Football', 'professional-football-fb-pro-001', 'FB-PRO-001', 'SportZone Pro', 'Match-grade football with a durable, water-resistant surface.', 3500.00, 2990.00, 25, 1, 1),
(1, 'Training Football', 'training-football-fb-trn-002', 'FB-TRN-002', 'SportZone Train', 'Hard-wearing training ball for everyday practice.', 1800.00, NULL, 40, 0, 1),
(2, 'English Willow Cricket Bat', 'english-willow-cricket-bat-ck-bat-001', 'CK-BAT-001', 'SportZone Pro', 'Grade-2 English willow bat with a balanced profile.', 12500.00, 10990.00, 8, 1, 1),
(2, 'Cricket Tennis Ball Pack', 'cricket-tennis-ball-pack-ck-bal-002', 'CK-BAL-002', 'SportZone Train', 'Pack of six tennis-style cricket balls for practice.', 2400.00, NULL, 3, 0, 1),
(3, 'Professional Badminton Racket', 'professional-badminton-racket-bd-rkt-001', 'BD-RKT-001', 'SportZone Pro', 'Lightweight graphite frame for fast, controlled play.', 9800.00, 8450.00, 12, 1, 1),
(3, 'Shuttlecock Pack (12)', 'shuttlecock-pack-12-bd-sht-002', 'BD-SHT-002', 'SportZone Essentials', 'Twelve feather shuttlecocks, tournament speed.', 1500.00, NULL, 0, 0, 1),
(4, 'Indoor Basketball', 'indoor-basketball-bb-ind-001', 'BB-IND-001', 'SportZone Pro', 'Composite leather indoor basketball with a deep channel design.', 4200.00, NULL, 20, 0, 1),
(6, 'Yoga Mat', 'yoga-mat-gf-ymt-001', 'GF-YMT-001', 'SportZone Train', '6 mm non-slip mat for yoga and floor workouts.', 2200.00, 1890.00, 30, 1, 1),
(6, 'Resistance Bands Set', 'resistance-bands-set-gf-rbd-002', 'GF-RBD-002', 'SportZone Train', 'Set of five bands with graded resistance levels.', 1600.00, NULL, 50, 0, 1),
(7, 'Running Shoes', 'running-shoes-ss-run-001', 'SS-RUN-001', 'SportZone Run', 'Cushioned road-running shoe with breathable mesh upper.', 7900.00, 6990.00, 15, 1, 1),
(8, 'Sports T-Shirt', 'sports-t-shirt-sw-tsh-001', 'SW-TSH-001', 'SportZone Essentials', 'Quick-dry training tee with a relaxed fit.', 1200.00, NULL, 60, 0, 1);
