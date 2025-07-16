-- Create Order Status History table for audit trail
CREATE TABLE IF NOT EXISTS Order_status_history (
    History_id INT AUTO_INCREMENT PRIMARY KEY,
    Order_id INT NOT NULL,
    Previous_status INT,
    New_status INT NOT NULL,
    Changed_by INT NOT NULL,
    Change_reason TEXT,
    Change_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign key constraints
    FOREIGN KEY (Order_id) REFERENCES Orders(Order_id) ON DELETE CASCADE,
    FOREIGN KEY (Previous_status) REFERENCES list_order_status(Status_id),
    FOREIGN KEY (New_status) REFERENCES list_order_status(Status_id),
    FOREIGN KEY (Changed_by) REFERENCES Users(User_id),
    
    -- Indexes for better performance
    INDEX idx_order_status_history_order_id (Order_id),
    INDEX idx_order_status_history_change_date (Change_date),
    INDEX idx_order_status_history_changed_by (Changed_by)
);

-- Insert initial status history for existing orders (optional migration)
INSERT INTO Order_status_history (Order_id, Previous_status, New_status, Changed_by, Change_reason, Change_date)
SELECT 
    Order_id,
    NULL as Previous_status,
    Status as New_status,
    1 as Changed_by, -- Default system user
    'Initial status from migration' as Change_reason,
    COALESCE(Created_at, NOW()) as Change_date
FROM Orders 
WHERE Order_id NOT IN (SELECT DISTINCT Order_id FROM Order_status_history); 