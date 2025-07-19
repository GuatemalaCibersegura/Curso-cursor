# Car Wash Client Platform Control System

A complete web-based car wash management system built with PHP and MySQL. This system provides comprehensive client management, service tracking, reporting, and user administration features.

## 🚀 Features

### Core Modules

1. **Login System**
   - Secure authentication with bcrypt password hashing
   - Session management
   - Role-based access control (Admin/Staff)
   - CSRF protection

2. **Client Registration Module**
   - Add, edit, and manage client information
   - Fields: Name, contact number, email, vehicle type, license plate
   - Search and filter functionality
   - Client service history tracking

3. **Service Entry Module**
   - Record new car wash services
   - Multiple service types: Basic, Deluxe, Premium, Full Detail
   - Automatic pricing with manual override capability
   - Service notes and timestamps
   - Client lookup and selection

4. **Reports Module**
   - Daily income reports
   - Weekly income reports
   - Service type analysis
   - Top clients report
   - Visual charts and statistics
   - Print-friendly reports

5. **User Management (Admin Only)**
   - Add, edit, and delete system users
   - Role assignment
   - Password management

6. **Profile Management**
   - Users can update their personal information
   - Password change functionality
   - Activity statistics

### Security Features

- Prepared statements to prevent SQL injection
- CSRF token protection
- Password hashing with bcrypt
- Input sanitization and validation
- Session security
- Role-based access control
- Activity logging

### User Interface

- Responsive Bootstrap 5 design
- Modern and intuitive interface
- Mobile-friendly layout
- Real-time form validation
- Success/error messaging
- Print-friendly reports

## 📋 Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Modern web browser

## 🛠️ Installation

### 1. Database Setup

1. Create a MySQL database:
```sql
CREATE DATABASE carwash_system;
```

2. Import the database structure and sample data:
```bash
mysql -u your_username -p carwash_system < database.sql
```

### 2. Configuration

1. Update the database configuration in `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'carwash_system');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

### 3. File Permissions

Ensure the `logs/` directory is writable:
```bash
chmod 755 logs/
```

### 4. Web Server Configuration

#### Apache
Create or update `.htaccess` file:
```apache
RewriteEngine On
DirectoryIndex index.php
```

#### Nginx
Add to your server block:
```nginx
index index.php;
location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
    fastcgi_index index.php;
    include fastcgi_params;
}
```

## 🔑 Default Login Credentials

After importing the database, you can use these default accounts:

**Administrator:**
- Username: `admin`
- Password: `admin123`

**Staff Member:**
- Username: `staff1`
- Password: `admin123`

**⚠️ Important:** Change these default passwords immediately after installation!

## 📁 Project Structure

```
carwash-system/
├── config/
│   └── database.php          # Database configuration
├── includes/
│   ├── functions.php         # Common functions
│   ├── header.php           # HTML header template
│   └── footer.php           # HTML footer template
├── logs/
│   └── activity.log         # System activity logs
├── database.sql             # Database schema and sample data
├── index.php               # Main entry point
├── login.php               # Login page
├── logout.php              # Logout handler
├── dashboard.php           # Main dashboard
├── clients.php             # Client management
├── services.php            # Service management
├── reports.php             # Reports and analytics
├── users.php               # User management (admin only)
├── profile.php             # User profile management
└── README.md               # This file
```

## 🎯 Usage Guide

### Getting Started

1. Access the system through your web browser
2. Log in with the provided credentials
3. Change default passwords in the profile section
4. Start by adding clients through the Client Management module
5. Record services through the Service Entry module
6. Generate reports to track business performance

### Client Management

- **Add Client:** Navigate to Clients → Add New Client
- **Search:** Use the search bar to find clients by name, license plate, or contact
- **Edit:** Click the edit button next to any client
- **View Services:** Click the services button to see a client's service history

### Service Entry

- **New Service:** Go to Services → Add New Service
- **Select Client:** Choose from the dropdown or add a new client first
- **Service Types:** Choose from Basic ($15), Deluxe ($25), Premium ($35), or Full Detail ($50)
- **Custom Pricing:** Modify the cost if needed
- **Notes:** Add any additional service notes

### Reports

- **Daily Reports:** View services and income for specific days
- **Weekly Reports:** Analyze weekly performance trends
- **Service Types:** See which services are most popular
- **Top Clients:** Identify your best customers

### User Management (Admin Only)

- **Add Users:** Create new staff or admin accounts
- **Edit Users:** Update user information and roles
- **Delete Users:** Remove inactive users (cannot delete yourself)

## 🔧 Customization

### Service Types and Pricing

Edit the `$service_pricing` array in `services.php`:
```php
$service_pricing = [
    'basic' => 15.00,
    'deluxe' => 25.00,
    'premium' => 35.00,
    'full_detail' => 50.00
];
```

### Adding New Vehicle Types

Update the vehicle type options in `clients.php`:
```php
<option value="NewType">New Vehicle Type</option>
```

### Styling

The system uses Bootstrap 5. You can customize the appearance by:
- Modifying the CSS in `includes/header.php`
- Adding custom stylesheets
- Updating Bootstrap variables

## 📊 Database Schema

### Users Table
- `id` - Primary key
- `username` - Unique username
- `password` - Hashed password
- `role` - admin/staff
- `full_name` - Display name
- `email` - Email address
- `created_at` - Registration timestamp

### Clients Table
- `id` - Primary key
- `name` - Client full name
- `contact_number` - Phone number
- `email` - Email address
- `vehicle_type` - Type of vehicle
- `license_plate` - Unique license plate
- `created_at` - Registration timestamp

### Services Table
- `id` - Primary key
- `client_id` - Foreign key to clients
- `service_type` - Type of service
- `cost` - Service cost
- `service_date` - Date of service
- `service_time` - Time of service
- `notes` - Additional notes
- `created_at` - Record timestamp

## 🔒 Security Considerations

1. **Change Default Passwords:** Immediately update default login credentials
2. **Database Security:** Use strong database passwords and limit user privileges
3. **File Permissions:** Ensure proper file and directory permissions
4. **HTTPS:** Use SSL/TLS encryption in production
5. **Regular Updates:** Keep PHP and MySQL updated
6. **Backup:** Implement regular database backups

## 🐛 Troubleshooting

### Common Issues

**Database Connection Failed:**
- Check database credentials in `config/database.php`
- Ensure MySQL service is running
- Verify database exists

**Permission Denied:**
- Check file permissions on the `logs/` directory
- Ensure web server has read access to PHP files

**Login Issues:**
- Clear browser cookies and sessions
- Check if database contains user records
- Verify password hashing is working

**Blank Pages:**
- Enable PHP error reporting
- Check web server error logs
- Verify all required PHP extensions are installed

## 📝 License

This project is open source and available under the MIT License.

## 🤝 Contributing

Contributions are welcome! Please feel free to submit pull requests or open issues for bugs and feature requests.

## 📞 Support

For support and questions, please create an issue in the project repository.

---

**Car Wash Client Platform Control System** - A comprehensive solution for managing your car wash business efficiently.