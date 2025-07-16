-- Order System Database Updates
-- Run these queries to update the database schema for the order submission system

-- 1. Add new columns to Orders table
ALTER TABLE Orders 
ADD COLUMN Contract_id INT DEFAULT NULL,
ADD COLUMN Order_total DECIMAL(10,2) DEFAULT NULL,
ADD COLUMN Currency_id INT DEFAULT NULL,
ADD COLUMN Note_id INT DEFAULT NULL,
ADD COLUMN Status INT DEFAULT 1,
ADD COLUMN display_order_number VARCHAR(50) UNIQUE NOT NULL;

-- Add foreign key constraints
ALTER TABLE Orders 
ADD CONSTRAINT fk_orders_contract FOREIGN KEY (Contract_id) REFERENCES Client_contracts(Contract_id),
ADD CONSTRAINT fk_orders_currency FOREIGN KEY (Currency_id) REFERENCES list_currency(Currency_id),
ADD CONSTRAINT fk_orders_status FOREIGN KEY (Status) REFERENCES list_order_status(Status_id),
ADD CONSTRAINT fk_orders_note FOREIGN KEY (Note_id) REFERENCES Order_notes(Note_id);

-- 2. Create list_order_status table
CREATE TABLE IF NOT EXISTS list_order_status (
    Status_id INT AUTO_INCREMENT PRIMARY KEY,
    Status_name VARCHAR(50) NOT NULL,
    Status_color VARCHAR(7) DEFAULT '#007bff',
    Created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Created_by INT,
    FOREIGN KEY (Created_by) REFERENCES Users(User_id)
);

-- Insert default status values
INSERT INTO list_order_status (Status_name, Status_color) VALUES
('Pending', '#ffc107'),
('Confirmed', '#28a745'),
('In Progress', '#007bff'),
('Completed', '#6f42c1'),
('Cancelled', '#dc3545'),
('On Hold', '#fd7e14');

-- 3. Create Order_notes table
CREATE TABLE IF NOT EXISTS Order_notes (
    Note_id INT AUTO_INCREMENT PRIMARY KEY,
    Note_content TEXT NOT NULL,
    Created_by INT NOT NULL,
    Created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (Created_by) REFERENCES Users(User_id)
);

-- 4. Update Documents table name and structure
-- Rename Documents table to Attached_order_files
RENAME TABLE Documents TO Attached_order_files;

-- Add Status column to Attached_order_files
ALTER TABLE Attached_order_files 
ADD COLUMN Status INT DEFAULT 1,
ADD CONSTRAINT fk_attached_files_status FOREIGN KEY (Status) REFERENCES list_order_status(Status_id);

-- 5. Update Orders table temperature columns
-- Drop existing Temperature column and add Min_temperature and Max_temperature
ALTER TABLE Orders 
DROP COLUMN IF EXISTS Temperature,
ADD COLUMN Min_temperature DECIMAL(5,2) DEFAULT NULL,
ADD COLUMN Max_temperature DECIMAL(5,2) DEFAULT NULL;

-- 6. Add Vehicle_type foreign key constraint
-- Assuming list_transport_type table exists with Type_id column
ALTER TABLE Orders 
ADD CONSTRAINT fk_orders_vehicle_type FOREIGN KEY (Vehicle_type) REFERENCES list_transport_type(Type_id);

-- 7. Create Extra_service table if it doesn't exist
CREATE TABLE IF NOT EXISTS Extra_service (
    Service_id INT AUTO_INCREMENT PRIMARY KEY,
    Order_id INT NOT NULL,
    Service_name VARCHAR(255) NOT NULL,
    Service_price DECIMAL(10,2) DEFAULT 0,
    Quantity INT DEFAULT 1,
    Total DECIMAL(10,2) DEFAULT 0,
    Created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (Order_id) REFERENCES Orders(Order_id) ON DELETE CASCADE
);

-- 8. Create Points table if it doesn't exist
CREATE TABLE IF NOT EXISTS Points (
    Point_id INT AUTO_INCREMENT PRIMARY KEY,
    Order_id INT NOT NULL,
    Position INT NOT NULL,
    Action_type ENUM('loading', 'unloading') DEFAULT 'loading',
    Date DATE DEFAULT NULL,
    Time TIME DEFAULT NULL,
    Address_Loading TEXT DEFAULT NULL,
    Company_name VARCHAR(255) DEFAULT NULL,
    Created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (Order_id) REFERENCES Orders(Order_id) ON DELETE CASCADE
);

-- 9. Create Contact_list table if it doesn't exist
CREATE TABLE IF NOT EXISTS Contact_list (
    Contact_id INT AUTO_INCREMENT PRIMARY KEY,
    Point_id INT NOT NULL,
    Name VARCHAR(255) NOT NULL,
    Phone_number VARCHAR(20) NOT NULL,
    Created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (Point_id) REFERENCES Points(Point_id) ON DELETE CASCADE
);

-- 10. Update Attached_order_files table structure
ALTER TABLE Attached_order_files 
MODIFY COLUMN Order_id INT NOT NULL,
ADD CONSTRAINT fk_attached_files_order FOREIGN KEY (Order_id) REFERENCES Orders(Order_id) ON DELETE CASCADE;

-- Add indexes for better performance
CREATE INDEX idx_orders_display_number ON Orders(display_order_number);
CREATE INDEX idx_orders_client ON Orders(Client_id);
CREATE INDEX idx_orders_status ON Orders(Status);
CREATE INDEX idx_orders_date ON Orders(Order_date);
CREATE INDEX idx_points_order ON Points(Order_id);
CREATE INDEX idx_contacts_point ON Contact_list(Point_id);
CREATE INDEX idx_extra_services_order ON Extra_service(Order_id);
CREATE INDEX idx_files_order ON Attached_order_files(Order_id);

-- Optional: Create view for order summary
CREATE OR REPLACE VIEW order_summary AS
SELECT 
    o.Order_id,
    o.display_order_number,
    o.Order_date,
    c.Full_company_name as Client_name,
    s.Status_name,
    s.Status_color,
    o.Order_total,
    curr.Currency_name,
    o.Cargo_type,
    o.Weight,
    o.Weight_unit,
    COUNT(DISTINCT p.Point_id) as Route_points_count,
    COUNT(DISTINCT es.Service_id) as Extra_services_count,
    COUNT(DISTINCT af.File_id) as Attached_files_count
FROM Orders o
LEFT JOIN Clients c ON o.Client_id = c.Client_id
LEFT JOIN list_order_status s ON o.Status = s.Status_id
LEFT JOIN list_currency curr ON o.Currency_id = curr.Currency_id
LEFT JOIN Points p ON o.Order_id = p.Order_id
LEFT JOIN Extra_service es ON o.Order_id = es.Order_id
LEFT JOIN Attached_order_files af ON o.Order_id = af.Order_id
GROUP BY o.Order_id; 