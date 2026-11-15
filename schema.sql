-- ============================================================
-- Database Template: Payment Gateway & ShopeePay Partner Auto Mutasi
-- DBMS: MySQL 5.7+ / MariaDB 10.3+
-- Charset: utf8mb4 / utf8mb4_general_ci
-- ============================================================

CREATE DATABASE IF NOT EXISTS `orders` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `orders`;

-- ------------------------------------------------------------
-- 1. Table structure for `orders`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` varchar(50) NOT NULL COMMENT 'Unique Order ID (e.g. ORD-XXXXXX)',
  `produk` varchar(255) DEFAULT 'Topup / Pesanan Toko' COMMENT 'Nama produk atau layanan',
  `total_bayar` int(11) NOT NULL DEFAULT 0 COMMENT 'Nominal pembayaran dalam IDR',
  `status` varchar(20) NOT NULL DEFAULT 'pending' COMMENT 'Status: pending, paid, expired, cancelled',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Waktu pembuatan transaksi (WIB)',
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Waktu pembaruan status transaksi',
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_id` (`order_id`),
  KEY `idx_status_created` (`status`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 2. Table structure for `merchant_settings`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `merchant_settings`;
CREATE TABLE `merchant_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key_name` varchar(100) NOT NULL COMMENT 'Kunci konfigurasi merchant',
  `key_value` text DEFAULT NULL COMMENT 'Nilai konfigurasi (token, id toko, nama merchant)',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_name` (`key_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 3. Default Seed Data for `merchant_settings`
-- Ganti dengan kredensial toko Shopee Partner Anda
-- ------------------------------------------------------------
INSERT INTO `merchant_settings` (`key_name`, `key_value`, `updated_at`) VALUES
('shopee_base_url', 'https://api.airpay.co.id', NOW()),
('shopee_client_id', '', NOW()),
('shopee_secret_key', '', NOW()),
('shopee_merchant_ext_id', '', NOW()),
('shopee_store_ext_id', '', NOW()),
('shopee_token', '', NOW()),
('shopee_store_name', 'Zhar store', NOW()),
('shopee_store_id', '24157627', NOW()),
('shopee_merchant_id', '25388964', NOW())
ON DUPLICATE KEY UPDATE `key_value` = VALUES(`key_value`), `updated_at` = NOW();
