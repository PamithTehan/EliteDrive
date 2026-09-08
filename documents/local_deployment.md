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
4. *(Optional)* Import the `test_seed.sql` file to populate the database with dummy vehicles, users, and drivers.

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
- **Permission Errors (Images)**: Ensure the `public/assets/uploads/` directory is writable if you test file uploads.
- **Password Hash Issues**: If you need to manually update a user's password hash in the database and face SQL Safe Mode restrictions:
  1. **Generate in PHP / CLI**:
     ```bash
     php -r "echo password_hash('Password123!', PASSWORD_BCRYPT) . PHP_EOL;"
     ```
  2. **Option 1: Disable Safe Mode for the Session**:
     Temporarily disable `SQL_SAFE_UPDATES` before running the update:
     ```sql
     USE elitedrive;
     SET SQL_SAFE_UPDATES = 0;
     UPDATE users SET password_hash = '$2y$10$e8Tlgm/d1n7118Riq4tKC0Q0G4r6iK0xVq8iP0H3J4r05.eOcM6d2';
     SET SQL_SAFE_UPDATES = 1;
     ```
  3. **Option 2: Add a Primary Key WHERE Clause**:
     Bypass the safe update warning without altering session flags by referencing the primary key `id`:
     ```sql
     USE elitedrive;
     UPDATE users SET password_hash = '$2y$10$e8Tlgm/d1n7118Riq4tKC0Q0G4r6iK0xVq8iP0H3J4r05.eOcM6d2' WHERE id > 0;
     ```
