USE `pickleball_db`;

ALTER TABLE `users`
    ADD COLUMN `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending'
    AFTER `role`;

UPDATE `users`
SET `status` = 'approved'
WHERE `status` = 'pending';
