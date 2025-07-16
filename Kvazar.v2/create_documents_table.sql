-- Create Documents table for storing supporting documents
CREATE TABLE IF NOT EXISTS Documents (
    Document_id INT PRIMARY KEY AUTO_INCREMENT,
    Order_id INT,
    File_path VARCHAR(500) NOT NULL,
    Original_filename VARCHAR(255) NOT NULL,
    File_type VARCHAR(50) NOT NULL,
    File_size INT NOT NULL,
    Upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Uploaded_by INT,
    FOREIGN KEY (Order_id) REFERENCES Orders(Order_id) ON DELETE CASCADE,
    FOREIGN KEY (Uploaded_by) REFERENCES Users(User_id) ON DELETE SET NULL,
    INDEX idx_order_id (Order_id),
    INDEX idx_upload_date (Upload_date)
);

-- Add comments to the table and columns
ALTER TABLE Documents COMMENT = 'Stores supporting documents for orders';
ALTER TABLE Documents MODIFY COLUMN Document_id INT COMMENT 'Primary key - unique document identifier';
ALTER TABLE Documents MODIFY COLUMN Order_id INT COMMENT 'Foreign key to Orders table';
ALTER TABLE Documents MODIFY COLUMN File_path VARCHAR(500) NOT NULL COMMENT 'Path to the file in the filesystem';
ALTER TABLE Documents MODIFY COLUMN Original_filename VARCHAR(255) NOT NULL COMMENT 'Original filename as uploaded by user';
ALTER TABLE Documents MODIFY COLUMN File_type VARCHAR(50) NOT NULL COMMENT 'File extension/type (pdf, doc, jpg, etc.)';
ALTER TABLE Documents MODIFY COLUMN File_size INT NOT NULL COMMENT 'File size in bytes';
ALTER TABLE Documents MODIFY COLUMN Upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'When the document was uploaded';
ALTER TABLE Documents MODIFY COLUMN Uploaded_by INT COMMENT 'User ID who uploaded the document'; 