USE `pickleball_db`;

ALTER TABLE `bookings`
    ADD COLUMN `membership_type` VARCHAR(50) NOT NULL DEFAULT 'standard'
    AFTER `credit_batch_id`;
