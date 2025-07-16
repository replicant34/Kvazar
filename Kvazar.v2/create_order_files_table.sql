-- Create table to store file paths for each order status
CREATE TABLE Order_files (
    File_id INT AUTO_INCREMENT PRIMARY KEY,
    Order_id INT NOT NULL,
    Status VARCHAR(50) NOT NULL,
    File_path VARCHAR(500) NOT NULL,
    File_name VARCHAR(255) NULL,
    File_size BIGINT NULL,
    File_type VARCHAR(100) NULL,
    Created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Add foreign key constraint (uncomment if Orders table exists)
    -- FOREIGN KEY (Order_id) REFERENCES Orders(Order_id) ON DELETE CASCADE,
    
    -- Add index for better performance on common queries
    INDEX idx_order_id (Order_id),
    INDEX idx_status (Status),
    INDEX idx_order_status (Order_id, Status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add some sample status values as reference (optional)
-- Common order statuses might include:
-- 'pending', 'confirmed', 'in_progress', 'loading', 'in_transit', 'unloading', 'completed', 'cancelled' 