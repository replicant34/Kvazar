-- Step-by-step fix for Orders table AUTO_INCREMENT issue
-- Run these commands one by one and check for errors

-- Step 1: Check current table structure
-- DESCRIBE Orders;

-- Step 2: Check existing primary key
-- SHOW CREATE TABLE Orders;

-- Step 3: Drop existing primary key (this might fail if there are foreign keys)
-- We need to check for foreign key constraints first
SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    CONSTRAINT_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE REFERENCED_TABLE_NAME = 'Orders' 
  AND REFERENCED_COLUMN_NAME = 'Order_id';

-- If there are foreign keys, we need to drop them first, then recreate them
-- Let's identify them:

-- Step 4: Drop foreign key constraints that reference Orders(Order_id)
-- (Run these only if Step 3 shows foreign keys exist)

-- Example commands (replace with actual constraint names from Step 3):
-- ALTER TABLE Extra_service DROP FOREIGN KEY fk_extra_service_order;
-- ALTER TABLE Points DROP FOREIGN KEY fk_points_order;
-- ALTER TABLE Contact_list DROP FOREIGN KEY fk_contact_list_point;
-- ALTER TABLE Attached_order_files DROP FOREIGN KEY fk_attached_files_order;

-- Step 5: Now drop the primary key
ALTER TABLE Orders DROP PRIMARY KEY;

-- Step 6: Modify the Order_id column to be AUTO_INCREMENT PRIMARY KEY
ALTER TABLE Orders MODIFY COLUMN Order_id INT AUTO_INCREMENT PRIMARY KEY;

-- Step 7: Set AUTO_INCREMENT start value
-- Check if there are existing records first
SELECT COUNT(*) as record_count, MAX(Order_id) as max_id FROM Orders;

-- Set AUTO_INCREMENT to start after the highest existing ID (or 1 if no records)
-- Replace '1' with (max_id + 1) if there are existing records
ALTER TABLE Orders AUTO_INCREMENT = 1;

-- Step 8: Recreate foreign key constraints (only if we dropped them in Step 4)
-- Replace constraint names with the actual ones from your database

-- For Extra_service table:
-- ALTER TABLE Extra_service 
-- ADD CONSTRAINT fk_extra_service_order 
-- FOREIGN KEY (Order_id) REFERENCES Orders(Order_id) ON DELETE CASCADE;

-- For Points table:
-- ALTER TABLE Points 
-- ADD CONSTRAINT fk_points_order 
-- FOREIGN KEY (Order_id) REFERENCES Orders(Order_id) ON DELETE CASCADE;

-- For Attached_order_files table:
-- ALTER TABLE Attached_order_files 
-- ADD CONSTRAINT fk_attached_files_order 
-- FOREIGN KEY (Order_id) REFERENCES Orders(Order_id) ON DELETE CASCADE;

-- Step 9: Verify the fix
DESCRIBE Orders;
SHOW CREATE TABLE Orders; 