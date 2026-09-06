# Local Deployment Guide

This guide walks you through setting up the EliteDrive Vehicle Rental System on your local machine for development and testing.

## Prerequisites
- **Web Server**: Apache or Nginx (XAMPP/WAMP/MAMP recommended for local development).
- **PHP**: PHP 8.0 or higher.
- **Database**: MySQL 8.0+ or MariaDB.
- **Tools**: Git, a Code Editor (VS Code, PhpStorm), and a web browser.

## Step-by-Step Setup

### 1. Clone the Repository
Clone the project into your local web server's document root (e.g., `C:\xampp\htdocs\Vehical_rental_system`).
```bash
git clone <repository_url> Vehical_rental_system
cd Vehical_rental_system
```

### 2. Configure the Database
1. Open your MySQL interface (e.g., phpMyAdmin, MySQL Workbench).
2. Create a new database named `elitedrive` (or your preferred name).
3. Import the `database_schema.sql` file located in the root directory to create the required tables.
4. *(Optional)* Import the `seed_data.sql` or `seed_50_vehicles_10_drivers.sql` file to populate the database with dummy vehicles, users, and drivers.

### 3. Setup Configuration
1. Navigate to the `config/` directory.
2. Follow the instructions in [CONFIG_SETUP.md](CONFIG_SETUP.md) to create your `config.php` file and connect your database.

### 4. Configure Local Server URL
Ensure the `base_url` parameter in your `config.php` accurately reflects your local environment path.
Example for XAMPP:
```php
'base_url' => 'http://localhost/Vehical_rental_system/public',
```

### 5. Start the Server and Test
1. Start Apache and MySQL services through your local web server control panel.
2. Open your web browser and navigate to the base URL configured in Step 4.
3. You should see the EliteDrive homepage.

## Troubleshooting
- **Database Connection Error**: Double-check `config.php` for typos in the database username, password, or host port (some MySQL setups use `3306` while others use `3307`).
- **404 Errors on Links**: Ensure your `base_url` correctly points to the `public/` directory and doesn't contain a trailing slash.
- **Permission Errors (Images)**: Ensure the `storage/` directory is writable if you test file uploads.
