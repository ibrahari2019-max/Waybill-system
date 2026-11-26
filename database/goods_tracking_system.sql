-- Goods Tracking and Transportation Management System
-- Case Study of Funtua Garage, Samaru, Zaria
-- Database Schema and Initial Seed Data

CREATE DATABASE IF NOT EXISTS `goods_tracking_system` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `goods_tracking_system`;

-- Drop tables in reverse order of dependencies if re-importing
DROP TABLE IF EXISTS `delivery_confirmation`;
DROP TABLE IF EXISTS `delivery_status_history`;
DROP TABLE IF EXISTS `waybills`;
DROP TABLE IF EXISTS `consignments`;
DROP TABLE IF EXISTS `vehicles`;
DROP TABLE IF EXISTS `drivers`;
DROP TABLE IF EXISTS `users`;

-- 1. Users Table
CREATE TABLE `users` (
  `user_id` INT NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(100) NOT NULL,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` VARCHAR(30) NOT NULL DEFAULT 'Administrator',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  INDEX `idx_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Drivers Table
CREATE TABLE `drivers` (
  `driver_id` INT NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(30) NOT NULL,
  `address` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`driver_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Vehicles Table
CREATE TABLE `vehicles` (
  `vehicle_id` INT NOT NULL AUTO_INCREMENT,
  `registration_number` VARCHAR(30) NOT NULL UNIQUE,
  `vehicle_type` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`vehicle_id`),
  INDEX `idx_reg_num` (`registration_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Consignments Table
CREATE TABLE `consignments` (
  `consignment_id` INT NOT NULL AUTO_INCREMENT,
  `tracking_number` VARCHAR(50) NOT NULL UNIQUE,
  `sender_name` VARCHAR(100) NOT NULL,
  `sender_contact` VARCHAR(30) NOT NULL,
  `sender_address` VARCHAR(255) NULL,
  `recipient_name` VARCHAR(100) NOT NULL,
  `recipient_contact` VARCHAR(30) NOT NULL,
  `recipient_address` VARCHAR(255) NULL,
  `goods_description` TEXT NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `goods_category` VARCHAR(100) NULL,
  `special_instructions` TEXT NULL,
  `destination` VARCHAR(150) NOT NULL,
  `dispatch_date` DATE NULL,
  `driver_id` INT NULL,
  `vehicle_id` INT NULL,
  `current_status` VARCHAR(30) NOT NULL DEFAULT 'Registered',
  `registered_by` INT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`consignment_id`),
  INDEX `idx_tracking` (`tracking_number`),
  INDEX `idx_status` (`current_status`),
  INDEX `idx_destination` (`destination`),
  INDEX `idx_created_at` (`created_at`),
  CONSTRAINT `fk_consignment_driver` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`driver_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_consignment_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`vehicle_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_consignment_user` FOREIGN KEY (`registered_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Waybills Table
CREATE TABLE `waybills` (
  `waybill_id` INT NOT NULL AUTO_INCREMENT,
  `consignment_id` INT NOT NULL UNIQUE,
  `waybill_number` VARCHAR(50) NOT NULL UNIQUE,
  `issue_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `issued_by` INT NOT NULL,
  PRIMARY KEY (`waybill_id`),
  INDEX `idx_waybill_num` (`waybill_number`),
  CONSTRAINT `fk_waybill_consignment` FOREIGN KEY (`consignment_id`) REFERENCES `consignments` (`consignment_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_waybill_user` FOREIGN KEY (`issued_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Delivery Status History Table
CREATE TABLE `delivery_status_history` (
  `status_id` INT NOT NULL AUTO_INCREMENT,
  `consignment_id` INT NOT NULL,
  `status` VARCHAR(30) NOT NULL,
  `remarks` TEXT NULL,
  `updated_by` INT NOT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`status_id`),
  INDEX `idx_history_consignment` (`consignment_id`),
  INDEX `idx_history_status` (`status`),
  CONSTRAINT `fk_history_consignment` FOREIGN KEY (`consignment_id`) REFERENCES `consignments` (`consignment_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_history_user` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Delivery Confirmation Table
CREATE TABLE `delivery_confirmation` (
  `confirmation_id` INT NOT NULL AUTO_INCREMENT,
  `consignment_id` INT NOT NULL UNIQUE,
  `received_by` VARCHAR(100) NOT NULL,
  `remarks` TEXT NULL,
  `confirmed_by` INT NOT NULL,
  `confirmed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`confirmation_id`),
  INDEX `idx_conf_consignment` (`consignment_id`),
  CONSTRAINT `fk_conf_consignment` FOREIGN KEY (`consignment_id`) REFERENCES `consignments` (`consignment_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_conf_user` FOREIGN KEY (`confirmed_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Initial Seed Data

-- Default Admin User (Password is 'admin')
INSERT INTO `users` (`user_id`, `full_name`, `username`, `password_hash`, `role`, `created_at`) VALUES
(1, 'Garage Administrator', 'admin', '$2y$10$C9D86KSyVwcFZvA4Se8nNewxR1QXAhgrp5lF0.LSp0OMrgmZfzVfC', 'Administrator', NOW());

-- Initial Drivers for Funtua Garage
INSERT INTO `drivers` (`driver_id`, `full_name`, `phone`, `address`, `created_at`) VALUES
(1, 'Malam Ibrahim Danladi', '08031234567', 'Funtua Motor Park, Katsina Road, Funtua', NOW()),
(2, 'Aliyu Sani Garba', '08029876543', 'Samaru Main Market, Zaria', NOW()),
(3, 'Usman Babangida', '08145566778', 'Sabon Gari, Zaria', NOW()),
(4, 'Kabiru Mohammed', '07033445566', 'Kano Road Park, Kaduna', NOW());

-- Initial Vehicles for Funtua Garage
INSERT INTO `vehicles` (`vehicle_id`, `registration_number`, `vehicle_type`, `description`, `created_at`) VALUES
(1, 'KTR-428-XA', 'Canter Truck (3-Tonne)', 'White Mitsubishi Canter with secure canvas cover', NOW()),
(2, 'ZAR-719-AA', 'Toyota Dyna Truck', 'Heavy-duty commercial goods carrier', NOW()),
(3, 'KAD-882-MK', 'Ford Transit Bus (Cargo)', 'Enclosed goods transit van for parcel handling', NOW()),
(4, 'FT-105-KTN', 'Mercedes 911 Lorry', 'Long-distance inter-state haulage vehicle', NOW());

-- Realistic Seed Consignments
INSERT INTO `consignments` (`consignment_id`, `tracking_number`, `sender_name`, `sender_contact`, `sender_address`, `recipient_name`, `recipient_contact`, `recipient_address`, `goods_description`, `quantity`, `goods_category`, `special_instructions`, `destination`, `dispatch_date`, `driver_id`, `vehicle_id`, `current_status`, `registered_by`, `created_at`) VALUES
(1, 'FGSZ-2026-000001', 'Alhaji Bashir Umar', '08031112233', 'Plot 12, Samaru Market Road, Zaria', 'Hajiya Fatima Bello', '08064445566', 'No. 5 Central Market, Funtua', '10 Bags of High-Yield Grains and Agricultural Produce', 10, 'Agricultural Produce', 'Handle with care. Keep dry.', 'Funtua', CURDATE() - INTERVAL 3 DAY, 1, 1, 'Delivered', 1, NOW() - INTERVAL 3 DAY),
(2, 'FGSZ-2026-000002', 'Engr. Sani Abdullahi', '08052223344', 'ABU Site 2, Samaru, Zaria', 'Musa Garba Enterprises', '08097778899', 'Station Road, Kano', '4 Cartons of Electrical Components and Inverter Accessories', 4, 'Electronics & Equipment', 'Fragile. Do not stack heavily.', 'Kano', CURDATE() - INTERVAL 2 DAY, 2, 2, 'Arrived', 1, NOW() - INTERVAL 2 DAY),
(3, 'FGSZ-2026-000003', 'Mrs. Zainab Lawal', '08123334455', 'PZ Area, Zaria', 'Ibrahim Shehu Traders', '08186665544', 'Main Market, Katsina', '15 Bales of Kaduna Cotton and Tailoring Materials', 15, 'Textiles & Apparel', 'Keep away from moisture.', 'Katsina', CURDATE() - INTERVAL 1 DAY, 1, 1, 'In Transit', 1, NOW() - INTERVAL 1 DAY),
(4, 'FGSZ-2026-000004', 'Yakubu Dambo', '07089990011', 'Danfodio Road, Samaru, Zaria', 'Dr. Aminu Salisu', '07012223344', 'Unguwar Rimi, Kaduna', '2 Packages of Research Laboratory Glassware & Documents', 2, 'Fragile Goods', 'Extremely fragile. Handle with utmost care.', 'Kaduna', CURDATE(), 3, 3, 'Dispatched', 1, NOW() - INTERVAL 5 HOUR),
(5, 'FGSZ-2026-000005', 'Hassan Abubakar', '08034567890', 'Bomo Road, Samaru, Zaria', 'Alhaji Kabir Funtua', '08023456789', 'Commercial Layout, Funtua', '6 Cartons of Automobile Spare Parts', 6, 'Spare Parts', 'Heavy items. Verify item count upon arrival.', 'Funtua', CURDATE(), 1, 1, 'Registered', 1, NOW() - INTERVAL 1 HOUR);

-- Waybills for Seed Consignments
INSERT INTO `waybills` (`waybill_id`, `consignment_id`, `waybill_number`, `issue_date`, `issued_by`) VALUES
(1, 1, 'WB-2026-000001', NOW() - INTERVAL 3 DAY, 1),
(2, 2, 'WB-2026-000002', NOW() - INTERVAL 2 DAY, 1),
(3, 3, 'WB-2026-000003', NOW() - INTERVAL 1 DAY, 1),
(4, 4, 'WB-2026-000004', NOW() - INTERVAL 5 HOUR, 1),
(5, 5, 'WB-2026-000005', NOW() - INTERVAL 1 HOUR, 1);

-- Delivery Status History for Seed Consignments
INSERT INTO `delivery_status_history` (`consignment_id`, `status`, `remarks`, `updated_by`, `updated_at`) VALUES
(1, 'Registered', 'Consignment registered at Funtua Garage, Samaru, Zaria.', 1, NOW() - INTERVAL 3 DAY),
(1, 'Dispatched', 'Loaded onto Canter Truck KTR-428-XA. Departed Samaru.', 1, NOW() - INTERVAL 3 DAY + INTERVAL 2 HOUR),
(1, 'In Transit', 'Transit verified through Katsina express corridor.', 1, NOW() - INTERVAL 2 DAY),
(1, 'Arrived', 'Vehicle arrived safely at Funtua central delivery terminal.', 1, NOW() - INTERVAL 2 DAY + INTERVAL 6 HOUR),
(1, 'Delivered', 'Delivered to recipient Hajiya Fatima Bello in excellent condition.', 1, NOW() - INTERVAL 1 DAY),

(2, 'Registered', 'Consignment registered at Samaru depot.', 1, NOW() - INTERVAL 2 DAY),
(2, 'Dispatched', 'Dispatched with driver Aliyu Sani Garba.', 1, NOW() - INTERVAL 2 DAY + INTERVAL 3 HOUR),
(2, 'In Transit', 'Passed Wudil junction enroute Kano.', 1, NOW() - INTERVAL 1 DAY + INTERVAL 4 HOUR),
(2, 'Arrived', 'Consignment arrived at Kano goods depot awaiting collection.', 1, NOW() - INTERVAL 4 HOUR),

(3, 'Registered', 'Bales registered and weighed.', 1, NOW() - INTERVAL 1 DAY),
(3, 'Dispatched', 'Dispatched via Canter Truck KTR-428-XA.', 1, NOW() - INTERVAL 20 HOUR),
(3, 'In Transit', 'Currently in transit towards Katsina.', 1, NOW() - INTERVAL 8 HOUR),

(4, 'Registered', 'Packages inspected and sealed.', 1, NOW() - INTERVAL 5 HOUR),
(4, 'Dispatched', 'Dispatched with driver Usman Babangida.', 1, NOW() - INTERVAL 2 HOUR),

(5, 'Registered', 'Goods registered at counter. Awaiting scheduled vehicle loading.', 1, NOW() - INTERVAL 1 HOUR);

-- Delivery Confirmation for Delivered Seed Consignment
INSERT INTO `delivery_confirmation` (`confirmation_id`, `consignment_id`, `received_by`, `remarks`, `confirmed_by`, `confirmed_at`) VALUES
(1, 1, 'Hajiya Fatima Bello', 'All 10 grain bags received intact without any damage.', 1, NOW() - INTERVAL 1 DAY);
