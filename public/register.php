<?php
/**
 * File: register.php
 * Purpose: Step 1: Basic user registration
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Location: ' . baseUrl('/login.php?tab=register'));
exit;
