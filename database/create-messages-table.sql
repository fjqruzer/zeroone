-- Create proper messages table for support tickets and inquiries
USE zero_one_labs;

-- Create messages table
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_type ENUM('support', 'inquiry') NOT NULL,
    conversation_id INT NOT NULL,
    sender_id INT NOT NULL,
    sender_type ENUM('client', 'admin') NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_conversation (conversation_type, conversation_id),
    INDEX idx_created_at (created_at)
);

-- Migrate existing support ticket messages to the new messages table
INSERT INTO messages (conversation_type, conversation_id, sender_id, sender_type, message, created_at)
SELECT 
    'support' as conversation_type,
    st.id as conversation_id,
    st.client_id as sender_id,
    'client' as sender_type,
    SUBSTRING_INDEX(st.message, '--- New Message ---', 1) as message,
    st.created_at
FROM support_tickets st
WHERE st.message IS NOT NULL AND st.message != '';

-- Migrate admin responses from support tickets (only if admin exists)
INSERT INTO messages (conversation_type, conversation_id, sender_id, sender_type, message, created_at)
SELECT 
    'support' as conversation_type,
    st.id as conversation_id,
    st.responded_by as sender_id,
    'admin' as sender_type,
    st.admin_response as message,
    st.responded_at
FROM support_tickets st
WHERE st.admin_response IS NOT NULL AND st.admin_response != '' 
AND st.responded_by IS NOT NULL;

-- Migrate existing inquiry messages (only if user exists)
INSERT INTO messages (conversation_type, conversation_id, sender_id, sender_type, message, created_at)
SELECT 
    'inquiry' as conversation_type,
    i.id as conversation_id,
    (SELECT id FROM users WHERE email = i.email LIMIT 1) as sender_id,
    'client' as sender_type,
    i.message as message,
    i.created_at
FROM inquiries i
WHERE i.message IS NOT NULL AND i.message != '' 
AND (SELECT id FROM users WHERE email = i.email LIMIT 1) IS NOT NULL;

-- Migrate admin responses from inquiries (only if admin exists)
INSERT INTO messages (conversation_type, conversation_id, sender_id, sender_type, message, created_at)
SELECT 
    'inquiry' as conversation_type,
    i.id as conversation_id,
    i.responded_by as sender_id,
    'admin' as sender_type,
    i.admin_response as message,
    i.responded_at
FROM inquiries i
WHERE i.admin_response IS NOT NULL AND i.admin_response != '' 
AND i.responded_by IS NOT NULL;

-- Update support_tickets table to remove old message concatenation
ALTER TABLE support_tickets 
MODIFY COLUMN message TEXT COMMENT 'Original client message only - use messages table for conversation';

-- Update inquiries table to remove old message concatenation  
ALTER TABLE inquiries 
MODIFY COLUMN message TEXT COMMENT 'Original client message only - use messages table for conversation'; 