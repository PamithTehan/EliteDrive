<?php
/**
 * File: config.example.php
 * Purpose: Template for environment config
 */
// config/config.example.php
// Copy this file to config.php and update the values with your actual database credentials.

return [
    'db_host' => 'localhost',         // Your database host (e.g., localhost or 127.0.0.1)
    'db_name' => 'elitedrive',        // The name of your database
    'db_user' => 'your_db_username',  // Your database username (e.g., root)
    'db_pass' => 'your_db_password',  // Your database password
    'base_url' => 'http://localhost:3307', // Your local development URL or production domain
    'commission_pct' => 15.00,        // System commission percentage for bookings
];
