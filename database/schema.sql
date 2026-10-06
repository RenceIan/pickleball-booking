CREATE DATABASE IF NOT EXISTS `pickleball_db`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `pickleball_db`;

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `full_name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `password_resets` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `token_hash` CHAR(64) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `used_at` DATETIME NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_password_resets_token_hash` (`token_hash`),
    KEY `idx_password_resets_user` (`user_id`, `expires_at`),
    CONSTRAINT `fk_password_resets_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `memberships` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `payment_id` INT UNSIGNED NULL,
    `membership_type` VARCHAR(100) NOT NULL DEFAULT 'standard',
    `amount_paid` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `start_date` DATE NULL,
    `expiration_date` DATE NULL,
    `status` ENUM('pending', 'active', 'expired', 'rejected') NOT NULL DEFAULT 'pending',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_memberships_user_status` (`user_id`, `status`),
    CONSTRAINT `fk_memberships_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `payments` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `membership_id` INT UNSIGNED NULL,
    `amount` DECIMAL(10, 2) NOT NULL,
    `reference_number` VARCHAR(100) NOT NULL,
    `payment_date` DATE NOT NULL,
    `proof_image` VARCHAR(255) NOT NULL,
    `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    `submitted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `reviewed_at` TIMESTAMP NULL,
    `reviewed_by` INT UNSIGNED NULL,
    `admin_notes` TEXT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_payments_reference_number` (`reference_number`),
    KEY `idx_payments_status` (`status`),
    CONSTRAINT `fk_payments_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_payments_membership`
        FOREIGN KEY (`membership_id`) REFERENCES `memberships` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_payments_reviewer`
        FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `credit_batches` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `amount` INT UNSIGNED NOT NULL,
    `remaining_amount` INT UNSIGNED NOT NULL,
    `issued_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `expires_at` DATETIME NOT NULL,
    `status` ENUM('active', 'depleted', 'expired') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_credit_batches_valid` (`user_id`, `status`, `expires_at`),
    CONSTRAINT `fk_credit_batches_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `credit_transactions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `credit_batch_id` INT UNSIGNED NULL,
    `amount` INT NOT NULL,
    `transaction_type` ENUM('issued', 'used', 'refunded', 'expired', 'adjusted') NOT NULL,
    `description` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_credit_transactions_user` (`user_id`, `created_at`),
    CONSTRAINT `fk_credit_transactions_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_credit_transactions_batch`
        FOREIGN KEY (`credit_batch_id`) REFERENCES `credit_batches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `bookings` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `booking_date` DATE NOT NULL,
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `credit_used` INT UNSIGNED NOT NULL DEFAULT 1,
    `credit_batch_id` INT UNSIGNED NULL,
    `membership_type` VARCHAR(50) NOT NULL DEFAULT 'standard',
    `status` ENUM('confirmed', 'cancelled', 'completed') NOT NULL DEFAULT 'confirmed',
    `confirmed_slot_key` VARCHAR(40)
        GENERATED ALWAYS AS (
            CASE WHEN `status` = 'confirmed'
                THEN CONCAT(`booking_date`, '-', `start_time`, '-', `end_time`)
                ELSE NULL
            END
        ) STORED,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_bookings_confirmed_slot` (`confirmed_slot_key`),
    KEY `idx_bookings_user_date` (`user_id`, `booking_date`),
    CONSTRAINT `fk_bookings_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_bookings_credit_batch`
        FOREIGN KEY (`credit_batch_id`) REFERENCES `credit_batches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT NOT NULL,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_settings_key` (`setting_key`)
) ENGINE=InnoDB;

INSERT INTO `settings` (`setting_key`, `setting_value`)
VALUES
    ('membership_price', '500'),
    ('credits_included', '10'),
    ('membership_duration_months', '6'),
    ('credit_duration_months', '6'),
    ('booking_cost', '1'),
    ('cancellation_window_minutes', '30'),
    ('booking_time_slots', '09:00-10:00,10:00-11:00,11:00-12:00,12:00-13:00,13:00-14:00,14:00-15:00,15:00-16:00,16:00-17:00,17:00-18:00,18:00-19:00,19:00-20:00,20:00-21:00')
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;
