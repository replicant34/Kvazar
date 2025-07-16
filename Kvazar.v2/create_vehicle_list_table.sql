-- Create Vehicle_list table
-- Junction table linking Orders to Vehicles (many-to-many relationship)

CREATE TABLE IF NOT EXISTS `Vehicle_list` (
    `Order_id` INT(11) NOT NULL,
    `Vehicle_id` INT(11) NOT NULL,
    `Created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `Updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Composite primary key
    PRIMARY KEY (`Order_id`, `Vehicle_id`),
    
    -- Foreign key constraints
    CONSTRAINT `fk_vehicle_list_order` 
        FOREIGN KEY (`Order_id`) 
        REFERENCES `Orders` (`Order_id`) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
        
    CONSTRAINT `fk_vehicle_list_vehicle` 
        FOREIGN KEY (`Vehicle_id`) 
        REFERENCES `Vehicles` (`Vehicle_id`) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
    
    -- Indexes for performance
    INDEX `idx_vehicle_list_order` (`Order_id`),
    INDEX `idx_vehicle_list_vehicle` (`Vehicle_id`)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add comments for documentation
ALTER TABLE `Vehicle_list` 
COMMENT = 'Junction table linking orders to assigned vehicles';

ALTER TABLE `Vehicle_list` 
MODIFY COLUMN `Order_id` INT(11) NOT NULL COMMENT 'Foreign key reference to Orders table',
MODIFY COLUMN `Vehicle_id` INT(11) NOT NULL COMMENT 'Foreign key reference to Vehicles table',
MODIFY COLUMN `Created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'When the vehicle was assigned to the order',
MODIFY COLUMN `Updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last modification timestamp'; 