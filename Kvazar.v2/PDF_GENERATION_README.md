# Order PDF Generation System

## Overview
This system automatically generates PDF documents for each order submitted through the order form, stores them in the filesystem, and records their information in the database.

## Components Created

### 1. Database Table: `Order_files`
**File:** `create_order_files_table.sql`

**Purpose:** Stores information about generated PDF files for each order status.

**Columns:**
- `File_id` (Primary Key) - Auto-incrementing file identifier
- `Order_id` - Reference to the order
- `Status` - Order status (e.g., 'created', 'pending', 'completed')
- `File_path` - Relative path to the stored PDF file
- `File_name` - Original filename of the PDF
- `File_size` - File size in bytes
- `File_type` - MIME type (application/pdf)
- `Created_at` - Timestamp when record was created
- `Updated_at` - Timestamp when record was last updated

**Setup:** Run the SQL script in your database to create the table.

### 2. PDF Generator Class: `OrderPDFGenerator`
**File:** `assets/generate_order_pdf.php`

**Purpose:** Generates professional PDF documents from order data using TCPDF library.

**Features:**
- Professional header with "KVAZAR LOGISTICS" branding
- Structured sections matching the preview modal layout
- Russian language support (UTF-8 encoding)
- Proper date formatting for Russian dates (DD.MM.YYYY)
- Contract number with date formatting ("774697 от 01.12.2020")
- Route points with visual progression
- Extra services table
- Cost breakdown with highlighted total
- Temperature regime (only shown if applicable)
- Supporting documents list
- Additional notes

### 3. Integration with Order Submission
**File:** `assets/submit_order.php` (modified)

**Integration Points:**
- Automatically generates PDF after successful order creation
- Stores PDF file in `uploads/order_files/` directory
- Records file information in `Order_files` table
- Handles errors gracefully (order still succeeds if PDF fails)

### 4. Directory Structure
```
uploads/
├── order_files/          # PDF files generated for orders
├── supporting_files/     # User-uploaded documents
├── contracts/           # Contract files
└── temp/               # Temporary upload files
```

## Installation Steps

### 1. Create Database Table
```sql
-- Run this in your MySQL database
source create_order_files_table.sql;
```

### 2. Verify Directory Permissions
```bash
# Ensure uploads/order_files exists and is writable
chmod 755 uploads/order_files
```

### 3. Test PDF Generation
```bash
# Run the test script
php test_pdf_generation.php
```

## How It Works

### Order Submission Flow
1. User fills out order form and clicks "Preview"
2. User reviews order details in preview modal
3. User clicks "Confirm and Create Order"
4. Order data is saved to database
5. **PDF is automatically generated** with order details
6. PDF is saved to `uploads/order_files/order_{ID}_{timestamp}.pdf`
7. File information is recorded in `Order_files` table with status "created"
8. Success response includes PDF generation status

### PDF Content Structure
1. **Header:** KVAZAR LOGISTICS branding
2. **Title:** "ДЕТАЛИ ЗАКАЗА" (Order Details)
3. **Sections:**
   - Основная информация (Basic Information)
   - Информация о грузе (Cargo Information)
   - Температурный режим (Temperature Regime) - if applicable
   - Маршрут (Route) - with visual progression
   - Стоимость (Cost) - with highlighted total
   - Дополнительные услуги (Extra Services) - if applicable
   - Дополнительные примечания (Additional Notes) - if applicable

### File Naming Convention
**Format:** `order_{ORDER_ID}_{YYYY-MM-DD_HH-mm-ss}.pdf`

**Example:** `order_12345_2025-01-14_15-30-45.pdf`

## API Response Format

### Successful Order Creation with PDF
```json
{
    "success": true,
    "message": "Order created successfully",
    "order_id": 12345,
    "order_number": "ORD-2025-001",
    "pdf_generated": true,
    "pdf_filename": "order_12345_2025-01-14_15-30-45.pdf"
}
```

### Order Created but PDF Failed
```json
{
    "success": true,
    "message": "Order created successfully (PDF generation failed)",
    "order_id": 12345,
    "order_number": "ORD-2025-001",
    "pdf_generated": false,
    "pdf_error": "Error message details"
}
```

## Database Queries

### Get All PDFs for an Order
```sql
SELECT * FROM Order_files 
WHERE Order_id = 12345 
ORDER BY Created_at DESC;
```

### Get PDFs by Status
```sql
SELECT * FROM Order_files 
WHERE Status = 'created' 
ORDER BY Created_at DESC;
```

### Get Latest PDF for Each Order
```sql
SELECT o.*, 
       (SELECT File_path FROM Order_files 
        WHERE Order_id = o.Order_id 
        ORDER BY Created_at DESC LIMIT 1) as latest_pdf
FROM Orders o;
```

## Error Handling

The system is designed to be fault-tolerant:
- **Order creation always succeeds** even if PDF generation fails
- PDF generation errors are logged but don't affect order workflow
- File permissions issues are handled gracefully
- Invalid data is skipped rather than causing failures

## Security Considerations

1. **File Storage:** PDFs are stored outside web root for security
2. **Access Control:** Only authenticated users can generate PDFs
3. **File Validation:** Generated files are validated before storage
4. **SQL Injection:** All database operations use prepared statements

## Troubleshooting

### Common Issues

1. **PDF Generation Fails**
   - Check TCPDF library installation
   - Verify directory permissions for `uploads/order_files/`
   - Check PHP memory_limit and max_execution_time

2. **Database Errors**
   - Ensure `Order_files` table exists
   - Check database connection settings
   - Verify user has INSERT permissions

3. **File Storage Issues**
   - Check directory exists and is writable
   - Verify disk space availability
   - Check file system permissions

### Testing
Run `test_pdf_generation.php` to verify:
- TCPDF library works
- Directory permissions are correct
- PDF generation functions properly

## Future Enhancements

Potential improvements:
1. **Status Updates:** Generate new PDFs when order status changes
2. **Email Integration:** Automatically email PDFs to clients
3. **Template Customization:** Allow different PDF templates per client
4. **Digital Signatures:** Add digital signing capabilities
5. **Multilingual Support:** Generate PDFs in different languages 