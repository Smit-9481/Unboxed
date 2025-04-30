# Gift Store E-commerce Website

A PHP-based e-commerce website for selling gifts online, featuring an admin panel for product management.

## Features

- User registration and authentication
- Product browsing by categories
- Shopping cart functionality
- Secure checkout process
- Admin panel for product management
- Responsive design using Bootstrap 5
- Image upload for products
- Order management system

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- mod_rewrite enabled (for Apache)
- GD Library for image processing

## Installation

1. Clone the repository to your web server directory:
```bash
git clone https://github.com/yourusername/gift-store.git
```

2. Create a MySQL database and import the database schema:
```bash
mysql -u root -p
CREATE DATABASE gift_store;
exit;
mysql -u root -p gift_store < database/gift_store.sql
```

3. Configure the database connection:
   - Open `config/database.php`
   - Update the database credentials:
```php
$host = 'localhost';
$dbname = 'gift_store';
$username = 'your_username';
$password = 'your_password';
```

4. Set up the uploads directory:
```bash
mkdir uploads
chmod 777 uploads
```

5. Configure your web server:
   - For Apache, ensure mod_rewrite is enabled
   - Set the document root to the project directory
   - Ensure the web server has write permissions for the uploads directory

## Default Admin Account

- Username: admin
- Password: admin123

**Important**: Change the admin password after first login!

## Directory Structure

```
gift-store/
├── admin/              # Admin panel files
├── assets/            # CSS, JavaScript, and images
├── config/            # Configuration files
├── database/          # Database schema
├── uploads/           # Product images
├── index.php          # Main entry point
├── login.php          # User login
├── register.php       # User registration
├── cart.php           # Shopping cart
├── checkout.php       # Checkout process
└── README.md          # This file
```

## Security Considerations

1. Always use HTTPS in production
2. Change default admin credentials
3. Set proper file permissions
4. Keep PHP and dependencies updated
5. Implement rate limiting for login attempts
6. Use prepared statements for all database queries
7. Validate and sanitize all user inputs

## Contributing

1. Fork the repository
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Create a new Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support, please open an issue in the GitHub repository or contact the maintainers. 