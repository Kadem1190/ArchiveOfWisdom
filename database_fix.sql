-- Add registration_status column to users table if it doesn't exist
ALTER TABLE users ADD COLUMN IF NOT EXISTS registration_status ENUM('pending', 'approved') NOT NULL DEFAULT 'pending';

-- Update existing users to have approved status
UPDATE users SET registration_status = 'approved' WHERE registration_status IS NULL OR registration_status = '';

-- Fix foreign key constraint for book deletion
-- First, check if the constraint exists and drop it
SET @constraint_name = (
    SELECT CONSTRAINT_NAME 
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_NAME = 'borrowing_history' 
    AND REFERENCED_TABLE_NAME = 'books' 
    AND CONSTRAINT_SCHEMA = DATABASE()
    LIMIT 1
);

SET @drop_fk_sql = IF(@constraint_name IS NOT NULL, 
                      CONCAT('ALTER TABLE borrowing_history DROP FOREIGN KEY ', @constraint_name), 
                      'SELECT 1');
PREPARE stmt FROM @drop_fk_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add the constraint back with ON DELETE SET NULL
ALTER TABLE borrowing_history 
ADD CONSTRAINT borrowing_history_book_fk 
FOREIGN KEY (book_id) REFERENCES books(book_id) 
ON DELETE SET NULL;

-- Create logs table if it doesn't exist
CREATE TABLE IF NOT EXISTS logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    table_name VARCHAR(50) NULL,
    operation VARCHAR(20) NULL,
    record_id INT NULL,
    user_id INT NULL,
    details TEXT NOT NULL,
    log_time DATETIME NOT NULL,
    INDEX (user_id),
    INDEX (table_name, operation)
);
