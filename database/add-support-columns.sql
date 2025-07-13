-- Add missing columns to support_tickets table
USE zero_one_labs;

-- Add responded_by column if it doesn't exist
ALTER TABLE support_tickets 
ADD COLUMN IF NOT EXISTS responded_by INT,
ADD COLUMN IF NOT EXISTS responded_at TIMESTAMP NULL;

-- Add foreign key constraint if it doesn't exist
-- Note: MySQL doesn't support IF NOT EXISTS for foreign keys, so we'll handle this carefully
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
     WHERE TABLE_SCHEMA = 'zero_one_labs' 
     AND TABLE_NAME = 'support_tickets' 
     AND COLUMN_NAME = 'responded_by' 
     AND CONSTRAINT_NAME LIKE '%fk%') = 0,
    'ALTER TABLE support_tickets ADD CONSTRAINT fk_support_responded_by FOREIGN KEY (responded_by) REFERENCES users(id) ON DELETE SET NULL',
    'SELECT "Foreign key already exists" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt; 