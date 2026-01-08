-- Update tickets table: remove phone column, add school_email and additional_info

-- Add new columns
ALTER TABLE tickets ADD COLUMN school_email VARCHAR(255) AFTER class_of;
ALTER TABLE tickets ADD COLUMN additional_info VARCHAR(120) AFTER school_email;

-- Remove phone column
ALTER TABLE tickets DROP COLUMN phone;
