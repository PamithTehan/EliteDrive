-- database_schema.sql

CREATE DATABASE IF NOT EXISTS elitedrive CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE elitedrive;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    contact_number VARCHAR(50),
    is_admin TINYINT(1) DEFAULT 0,
    is_owner TINYINT(1) DEFAULT 0,
    is_borrower TINYINT(1) DEFAULT 0,
    is_driver TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE drivers (
    user_id INT PRIMARY KEY,
    daily_fee DECIMAL(10, 2) NOT NULL DEFAULT 25.00,
    transmission_preference ENUM('Manual', 'Auto', 'Both') NOT NULL DEFAULT 'Both',
    driving_preference ENUM('own_vehicles', 'any_vehicle') DEFAULT 'any_vehicle',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE driving_licenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    license_number VARCHAR(100) NOT NULL UNIQUE,
    expiry_date DATE NOT NULL,
    upload_format ENUM('pdf', 'image') NOT NULL DEFAULT 'pdf',
    status ENUM('pending', 'verified', 'rejected', 'expired') DEFAULT 'pending',
    reviewed_by INT NULL,
    reviewed_at TIMESTAMP NULL,
    rejection_reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES drivers(user_id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE driving_license_pdfs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    license_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    FOREIGN KEY (license_id) REFERENCES driving_licenses(id) ON DELETE CASCADE
);

CREATE TABLE driving_license_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    license_id INT NOT NULL,
    front_image_path VARCHAR(255) NOT NULL,
    back_image_path VARCHAR(255) NOT NULL,
    FOREIGN KEY (license_id) REFERENCES driving_licenses(id) ON DELETE CASCADE
);

CREATE TABLE vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_id INT NOT NULL,
    make VARCHAR(50) NOT NULL,
    model VARCHAR(50) NOT NULL,
    category ENUM('Premium', 'Luxury', 'Budget', 'Offroad', 'Electric') NOT NULL,
    daily_rate DECIMAL(10, 2) NOT NULL,
    location VARCHAR(255) NOT NULL,
    transmission ENUM('Auto', 'Manual') NOT NULL DEFAULT 'Auto',
    mileage INT NOT NULL DEFAULT 0,
    km_rate DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    yom INT NOT NULL DEFAULT 2020,
    yor INT NOT NULL DEFAULT 2020,
    status ENUM('draft', 'pending_review', 'approved', 'rejected', 'suspended') DEFAULT 'draft',
    rejection_reason TEXT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX cat_status_idx (category, status)
);

CREATE TABLE vehicle_photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    photo_path VARCHAR(255) NOT NULL,
    is_primary TINYINT(1) DEFAULT 0,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
);

CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    borrower_id INT NOT NULL,
    driver_arrangement ENUM('owner', 'self', 'hired') NOT NULL,
    assigned_driver_id INT NULL,
    pickup_date DATETIME NOT NULL,
    return_date DATETIME NOT NULL,
    pickup_location VARCHAR(255) NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    status ENUM('pending_payment', 'pending_verification', 'confirmed', 'active', 'completed', 'reviewed', 'rejected', 'cancelled', 'disputed') DEFAULT 'pending_payment',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
    FOREIGN KEY (borrower_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_driver_id) REFERENCES drivers(user_id),
    INDEX overlap_idx (vehicle_id, pickup_date, return_date),
    INDEX status_idx (status)
);

CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    user_id INT NOT NULL,
    stripe_session_id VARCHAR(255) NULL UNIQUE,
    stripe_payment_intent_id VARCHAR(255) NULL UNIQUE,
    amount DECIMAL(10, 2) NOT NULL,
    currency CHAR(3) DEFAULT 'USD',
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE RESTRICT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    reviewer_id INT NOT NULL,
    target_id INT NOT NULL,
    target_type ENUM('vehicle', 'owner', 'driver') NOT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id),
    FOREIGN KEY (reviewer_id) REFERENCES users(id),
    UNIQUE (booking_id, reviewer_id, target_id)
);

CREATE TABLE rejection_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entity_type ENUM('license', 'vehicle', 'booking', 'other') NOT NULL,
    entity_id INT NOT NULL,
    reason TEXT NOT NULL,
    rejected_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rejected_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE inquiries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    subject VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('pending', 'responded', 'closed') DEFAULT 'pending',
    admin_response TEXT NULL,
    responded_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
