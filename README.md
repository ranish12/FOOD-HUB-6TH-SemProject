# Food Hub - Restaurant Management System

Food Hub is a comprehensive web-based restaurant management system that enables restaurants to manage their menu, orders, and customer interactions efficiently. The system includes both customer-facing and administrative interfaces.

## Features

### Customer Features
- **Menu Browsing**: Browse through categorized menu items with images and descriptions
- **Shopping Cart**: Add items to cart with quantity selection
- **User Accounts**: Register and login to track orders and save preferences
- **Order Management**: Place and track orders
- **Cart Counter**: Real-time cart item counter that works for both logged-in and non-logged-in users
- **Responsive Design**: Mobile-friendly interface for better user experience

### Admin Features
- **Dashboard**: Overview of sales and order statistics
- **Menu Management**: Add, edit, and delete menu items with images
- **Category Management**: Organize menu items into categories
- **Order Processing**: View and manage customer orders
- **Content Management**: Update About Us and Contact information
- **User Management**: Manage customer accounts and admin access

## Technical Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache Web Server
- XAMPP (recommended for local development)
- Modern web browser (Chrome, Firefox, Safari, Edge)

## Installation

1. Install XAMPP on your system
2. Clone this repository to your XAMPP's htdocs folder:
   ```bash
   cd /path/to/xampp/htdocs
   git clone [repository-url] food-hub
   ```
3. Import the database:
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create a new database named 'food_hub'
   - Import the SQL file from `config/database.sql`

4. Configure the database connection:
   - Open `config/database.php`
   - Update the database credentials if needed

5. Set up file permissions:
   - Ensure the `assets/images/menu` and `assets/images/about` directories are writable
   - On Unix-based systems:
     ```bash
     chmod 755 assets/images/menu
     chmod 755 assets/images/about
     ```

## Project Structure

```
food-hub/
├── admin/              # Admin panel files
├── customer/           # Customer-facing files
├── assets/            
│   └── images/        # Image storage
├── config/            # Configuration files
├── includes/          # Shared includes
└── payment/           # Payment processing
```

## Usage

1. Start your XAMPP server (Apache and MySQL)
2. Access the customer interface: `http://localhost/food-hub/customer/`
3. Access the admin panel: `http://localhost/food-hub/admin/`
   - Default admin credentials:
     - Username: admin@foodhub.com
     - Password: admin123

## Security Features

- Password hashing for user accounts
- Session-based authentication
- SQL injection prevention
- XSS protection
- CSRF protection for forms
- Secure file upload handling

## Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## contributors
Pabitra – User side 
Ranish – Admin side


