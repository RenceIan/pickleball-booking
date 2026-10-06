USE `pickleball_db`;

ALTER TABLE `bookings`
    DROP INDEX `uq_bookings_slot_status`,
    ADD COLUMN `confirmed_slot_key` VARCHAR(40)
        GENERATED ALWAYS AS (
            CASE WHEN `status` = 'confirmed'
                THEN CONCAT(`booking_date`, '-', `start_time`, '-', `end_time`)
                ELSE NULL
            END
        ) STORED,
    ADD UNIQUE KEY `uq_bookings_confirmed_slot` (`confirmed_slot_key`);
