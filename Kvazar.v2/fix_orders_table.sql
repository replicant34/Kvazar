-- Fix Orders table AUTO_INCREMENT issue
-- This script ensures the Order_id field is properly configured as AUTO_INCREMENT PRIMARY KEY

-- First, let's check the current structure and fix it
-- Note: Run each section carefully and check for errors

-- 1. Check current table structure (run this first to see current state)
-- DESCRIBE Orders;

-- 2. If Order_id exists but is not AUTO_INCREMENT, fix it
-- First, check if there are any existing records and their max ID
-- SELECT MAX(Order_id) as max_id FROM Orders;

-- 3. Fix the Order_id field to be AUTO_INCREMENT PRIMARY KEY
-- If the table exists but Order_id is not AUTO_INCREMENT:

-- Step 3a: Drop existing primary key if it exists (might fail if no PK exists, that's OK)
-- ALTER TABLE Orders DROP PRIMARY KEY;

-- Step 3b: Modify Order_id to be AUTO_INCREMENT PRIMARY KEY
ALTER TABLE Orders MODIFY COLUMN Order_id INT AUTO_INCREMENT PRIMARY KEY;

-- Step 3c: Set AUTO_INCREMENT start value based on existing data
-- If there are existing records, set it to max_id + 1
-- If no records exist, start from 1
-- Replace XXX with (max_id + 1) from step 2, or use 1 if no records exist
ALTER TABLE Orders AUTO_INCREMENT = 1;

-- 4. Ensure the Order_id column exists if table was created without it
-- (Only run this if DESCRIBE Orders shows no Order_id column)
-- ALTER TABLE Orders ADD COLUMN Order_id INT AUTO_INCREMENT PRIMARY KEY FIRST;

-- 5. Alternative: If the table needs to be recreated completely, use this:
-- (ONLY use this if the above fixes don't work and you can lose existing data)
/*
-- Backup existing data first!
CREATE TABLE Orders_backup AS SELECT * FROM Orders;

-- Drop and recreate table
DROP TABLE IF EXISTS Orders;

CREATE TABLE Orders (
    Order_id INT AUTO_INCREMENT PRIMARY KEY,
    display_order_number VARCHAR(50) UNIQUE NOT NULL,
    User_id INT,
    Client_id INT,
    Contractor INT,
    Order_date DATE,
    Shipping_type INT,
    Vehicle_type INT,
    Weight DECIMAL(10,3),
    Weight_unit VARCHAR(10) DEFAULT 'тонн',
    Volume DECIMAL(10,3),
    Cargo_type VARCHAR(255),
    Min_temperature DECIMAL(5,2),
    Max_temperature DECIMAL(5,2),
    Temperature_record VARCHAR(255),
    Loading_type INT,
    Packing_type INT,
    Quantity INT,
    Size VARCHAR(100),
    Cargo_price DECIMAL(10,2),
    Rate DECIMAL(10,2),
    Insurance_price DECIMAL(10,2),
    Rate_2 DECIMAL(10,2),
    Hours INT,
    Extra_hours INT,
    Total_price_vehicle DECIMAL(10,2),
    Total_price_extra_service DECIMAL(10,2),
    Contract_id INT,
    Order_total DECIMAL(10,2),
    Currency_id INT,
    Note_id INT,
    Status INT DEFAULT 1,
    Created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign key constraints
    FOREIGN KEY (User_id) REFERENCES Users(User_id),
    FOREIGN KEY (Client_id) REFERENCES Clients(Client_id),
    FOREIGN KEY (Contractor) REFERENCES Contractors(Contractors_id),
    FOREIGN KEY (Contract_id) REFERENCES Client_contracts(Contract_id),
    FOREIGN KEY (Currency_id) REFERENCES list_currency(Currency_id),
    FOREIGN KEY (Status) REFERENCES list_order_status(Status_id),
    FOREIGN KEY (Note_id) REFERENCES Order_notes(Note_id)
);

-- Restore data from backup (modify column names as needed)
-- INSERT INTO Orders SELECT * FROM Orders_backup;

-- Drop backup table
-- DROP TABLE Orders_backup;
*/

-- 6. Verify the fix worked
-- DESCRIBE Orders;
-- SHOW CREATE TABLE Orders; 