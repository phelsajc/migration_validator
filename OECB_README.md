# OECB System Implementation

## Overview
The OECB (Order Entry and Clinical Billing) system has been implemented as a Laravel application with MongoDB integration for patient order data management.

## Features

### 1. Dynamic Date Filtering
- The `modifiedat` field is now dynamic and can be filtered by date range
- Default date range is set to the last 7 days
- Date picker with predefined ranges (Today, Yesterday, Last 7 Days, Last 30 Days, This Month, Last Month)

### 2. MongoDB Pipeline
The system uses a comprehensive MongoDB aggregation pipeline that includes:
- Multiple collection lookups (billinggroups, departments, wards, organisations, etc.)
- Patient order item processing
- Status and billing information aggregation
- Dynamic filtering based on date range, billing group, and organization ID

### 3. Data Export
- CSV export functionality for filtered results
- Configurable column headers
- Streaming export for large datasets

## Files Created

### Controller
- `app/Http/Controllers/OECBController.php` - Main controller with index, show, and export methods

### Views
- `resources/views/oecb/index.blade.php` - Main OECB dashboard view

### Routes
- `GET /oecb` - Shows the initial form (oecb.show)
- `POST /oecb` - Processes form and displays results (oecb.index)
- `POST /oecb/export` - Exports data to CSV (oecb.export)

### Navigation
- Added OECB menu item to the sidebar with clipboard icon

## Usage

### 1. Access the OECB Dashboard
Navigate to `/oecb` in your browser or click the "OECB" menu item in the sidebar.

### 2. Filter Data
- **Date Range**: Select a date range using the date picker
- **Billing Group**: Choose from Medicines, Laboratory, Radiology, or Procedures
- **Organization ID**: Enter the specific organization ID to filter by

### 3. View Results
The system will display:
- Order item names and details
- Billing information
- Department and ward details
- Pricing information
- Status and modification dates

### 4. Export Data
Click the "Export to CSV" button to download filtered results as a CSV file.

## Technical Details

### MongoDB Collections Used
- `patientorders` - Main collection for patient orders
- `billinggroups` - Billing group information
- `departments` - Department details
- `wards` - Ward information
- `organisations` - Organization details
- `referencevalues` - Status and other reference data
- `orderitems` - Order item details

### Key Pipeline Features
- **Dynamic Date Filtering**: Uses `$gte` and `$lte` operators with MongoDB UTCDateTime
- **Multiple Lookups**: Joins data from multiple collections
- **Array Processing**: Handles nested arrays in patient order items
- **Pagination**: Built-in pagination support for large datasets
- **Performance**: Uses `allowDiskUse` for large aggregation operations

### Configuration
- Timezone set to Asia/Manila
- Default pagination: 100 items per page
- Export timeout: 600 seconds
- Memory limit: 1GB for exports

## Dependencies
- Laravel Framework
- MongoDB PHP Driver
- Jenssegers MongoDB Package
- AdminLTE for UI components
- DateRangePicker for date selection
- Moment.js for date handling

## Security Notes
- CSRF protection enabled for all forms
- Input validation and sanitization
- MongoDB injection protection through proper BSON object creation

## Performance Considerations
- Aggregation pipeline optimized for large datasets
- Pagination to prevent memory issues
- Streaming export for large data exports
- Configurable execution time limits

## Troubleshooting

### Common Issues
1. **MongoDB Connection**: Ensure MongoDB connection is properly configured in `config/database.php`
2. **Date Format**: Date picker expects MM/DD/YYYY format
3. **Memory Limits**: For large exports, ensure sufficient memory allocation
4. **Timeout Issues**: Adjust `max_execution_time` if needed

### Debug Mode
Enable Laravel debug mode to see detailed error messages and SQL queries.

## Future Enhancements
- Real-time data updates
- Advanced filtering options
- Data visualization charts
- User role-based access control
- Audit logging for data exports
- API endpoints for external integrations
