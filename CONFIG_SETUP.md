# Configuration Setup Guide

The `config/config.php` file contains sensitive information like database credentials. For security reasons, it is ignored by Git (via `.gitignore`) and should **never** be committed to the repository.

When you or another developer clones this repository, you must create your own `config.php` file to run the application.

## How to set up your config file

1. **Locate the example file:**
   Navigate to the `config` directory. You will find a file named `config.example.php`.

2. **Copy the file:**
   Create a copy of `config.example.php` and name it exactly `config.php` in the same directory.
   
   If you are using the terminal:
   ```bash
   cd config
   cp config.example.php config.php
   ```

3. **Update the credentials:**
   Open the newly created `config/config.php` in your code editor. Update the placeholder values with your actual local (or production) environment settings:

   ```php
   return [
       'db_host' => 'localhost',         // Usually 'localhost' or '127.0.0.1'
       'db_name' => 'elitedrive',        // Your database name
       'db_user' => 'your_db_username',  // e.g., 'root'
       'db_pass' => 'your_db_password',  // e.g., 'root123' or leave empty if no password
       'base_url' => 'http://localhost:3307', // Update port if your server runs differently
       'commission_pct' => 15.00,
   ];
   ```

4. **Verify the connection:**
   Once you have saved your `config.php`, ensure your local database server (e.g., XAMPP, MySQL, MariaDB) is running. Navigate to the site in your browser to verify the connection is successful.

> [!WARNING]  
> If you accidentally commit `config.php`, immediately remove it from the git cache (`git rm --cached config/config.php`), commit the removal, and change your database passwords.
