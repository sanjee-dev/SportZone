-- Add customer accounts, persistent carts, and cash-on-delivery orders.
-- Safe to import on an existing SportZone database; does not drop existing data.
USE sportzone;

CREATE TABLE IF NOT EXISTS customers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  phone VARCHAR(30) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  delivery_name VARCHAR(120) NULL,
  delivery_phone VARCHAR(30) NULL,
  address_line_1 VARCHAR(190) NULL,
  address_line_2 VARCHAR(190) NULL,
  city VARCHAR(100) NULL,
  region VARCHAR(100) NULL,
  postal_code VARCHAR(30) NULL,
  country VARCHAR(100) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS customer_cart_items (
  customer_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  quantity INT UNSIGNED NOT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (customer_id, product_id),
  CONSTRAINT fk_cart_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  CONSTRAINT fk_cart_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS customer_orders (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_number VARCHAR(32) NOT NULL UNIQUE,
  customer_id INT UNSIGNED NOT NULL,
  customer_name VARCHAR(120) NOT NULL,
  customer_email VARCHAR(190) NOT NULL,
  customer_phone VARCHAR(30) NOT NULL,
  delivery_name VARCHAR(120) NOT NULL,
  delivery_phone VARCHAR(30) NOT NULL,
  address_line_1 VARCHAR(190) NOT NULL,
  address_line_2 VARCHAR(190) NULL,
  city VARCHAR(100) NOT NULL,
  region VARCHAR(100) NULL,
  postal_code VARCHAR(30) NULL,
  country VARCHAR(100) NOT NULL,
  subtotal DECIMAL(10,2) NOT NULL,
  delivery_fee DECIMAL(10,2) NOT NULL,
  total DECIMAL(10,2) NOT NULL,
  payment_method ENUM('cash_on_delivery') NOT NULL DEFAULT 'cash_on_delivery',
  payment_status ENUM('unpaid','paid') NOT NULL DEFAULT 'unpaid',
  status ENUM('pending','confirmed','shipped','delivered') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_customer_orders_customer_date (customer_id, created_at),
  INDEX idx_customer_orders_status_date (status, created_at),
  CONSTRAINT fk_order_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS customer_order_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NULL,
  product_name VARCHAR(150) NOT NULL,
  sku VARCHAR(50) NOT NULL,
  unit_price DECIMAL(10,2) NOT NULL,
  quantity INT UNSIGNED NOT NULL,
  line_total DECIMAL(10,2) NOT NULL,
  CONSTRAINT fk_order_item_order FOREIGN KEY (order_id) REFERENCES customer_orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_order_item_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
  INDEX idx_customer_order_items_order (order_id)
) ENGINE=InnoDB;
