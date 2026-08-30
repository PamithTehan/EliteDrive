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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE borrowers (
    user_id INT PRIMARY KEY,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE owners (
    user_id INT PRIMARY KEY,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE drivers (
    user_id INT PRIMARY KEY,
    driving_preference ENUM('own_vehicles', 'any_vehicle') DEFAULT 'any_vehicle',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE driving_licenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    license_number VARCHAR(100) NOT NULL UNIQUE, -- Added unique constraint
    expiry_date DATE NOT NULL,
    upload_format ENUM('pdf', 'image') NOT NULL DEFAULT 'pdf',
    status ENUM('pending', 'verified', 'rejected', 'expired') DEFAULT 'pending',
    reviewed_by INT NULL,
    reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Added updated_at
    FOREIGN KEY (user_id) REFERENCES drivers(user_id) ON DELETE CASCADE, -- Changed to reference drivers role table
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
    category ENUM('Premium', 'Luxury', 'Budget', 'Offroad') NOT NULL,
    daily_rate DECIMAL(10, 2) NOT NULL,
    location VARCHAR(255) NOT NULL,
    status ENUM('draft', 'pending_review', 'approved', 'suspended') DEFAULT 'draft',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Added updated_at
    FOREIGN KEY (owner_id) REFERENCES owners(user_id) ON DELETE CASCADE, -- Changed to reference owners role table
    INDEX cat_status_idx (category, status) -- Added index for faster filtering
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
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Added updated_at
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
    FOREIGN KEY (borrower_id) REFERENCES borrowers(user_id), -- Changed to reference borrowers role table
    FOREIGN KEY (assigned_driver_id) REFERENCES drivers(user_id), -- Changed to reference drivers role table
    INDEX overlap_idx (vehicle_id, pickup_date, return_date), -- Index to support overlap checks (overlap prevention itself needs app-level logic)
    INDEX status_idx (status) -- Added index for status queries
);

CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    user_id INT NOT NULL,
    stripe_session_id VARCHAR(255) NULL UNIQUE, -- Added unique constraint
    stripe_payment_intent_id VARCHAR(255) NULL UNIQUE, -- Added unique constraint
    amount DECIMAL(10, 2) NOT NULL,
    currency CHAR(3) DEFAULT 'USD', -- Changed to CHAR(3)
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE RESTRICT, -- Changed to RESTRICT to prevent payment history deletion
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
    -- target_id FK to users removed because it's a polymorphic relation (can be vehicle or user). Integrity enforced in app code.
    UNIQUE (booking_id, reviewer_id, target_id) -- Added unique constraint to prevent duplicate reviews
);

CREATE TABLE rejection_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entity_type ENUM('license', 'vehicle', 'booking', 'other') NOT NULL,
    entity_id INT NOT NULL,
    reason TEXT NOT NULL,
    rejected_by INT NULL, -- Changed to nullable
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rejected_by) REFERENCES users(id) ON DELETE SET NULL -- Changed to SET NULL to preserve audit logs
);
