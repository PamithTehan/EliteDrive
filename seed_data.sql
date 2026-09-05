-- Seed Data for EliteDrive Vehicle Rental System
-- All users have the password: password123

-- 1. Insert Users with direct role flags
INSERT INTO `users` (`id`, `full_name`, `email`, `password_hash`, `contact_number`, `is_admin`, `is_owner`, `is_borrower`, `is_driver`, `created_at`, `updated_at`) VALUES
(1, 'Admin User', 'admin@elitedrive.com', '$2y$10$Qx/h1C8X/l9fL9Pq0vH/QOf9D0hI00vVfS8b.W0T/5P6QfXk916tW', '555-0100', 1, 0, 0, 0, '2023-10-01 10:00:00', '2023-10-01 10:00:00'),
(2, 'Sarah Jenkins (Owner)', 'sarah@example.com', '$2y$10$Qx/h1C8X/l9fL9Pq0vH/QOf9D0hI00vVfS8b.W0T/5P6QfXk916tW', '555-0101', 0, 1, 1, 0, '2023-10-02 11:30:00', '2023-10-02 11:30:00'),
(3, 'Michael Chang (Owner)', 'michael@example.com', '$2y$10$Qx/h1C8X/l9fL9Pq0vH/QOf9D0hI00vVfS8b.W0T/5P6QfXk916tW', '555-0102', 0, 1, 0, 0, '2023-10-03 09:15:00', '2023-10-03 09:15:00'),
(4, 'David Torres (Driver)', 'david.t@example.com', '$2y$10$Qx/h1C8X/l9fL9Pq0vH/QOf9D0hI00vVfS8b.W0T/5P6QfXk916tW', '555-0103', 0, 0, 0, 1, '2023-10-04 14:20:00', '2023-10-04 14:20:00'),
(5, 'Emma Wilson (Driver)', 'emma.w@example.com', '$2y$10$Qx/h1C8X/l9fL9Pq0vH/QOf9D0hI00vVfS8b.W0T/5P6QfXk916tW', '555-0104', 0, 0, 0, 1, '2023-10-05 16:45:00', '2023-10-05 16:45:00'),
(6, 'James Smith (Borrower)', 'james.smith@example.com', '$2y$10$Qx/h1C8X/l9fL9Pq0vH/QOf9D0hI00vVfS8b.W0T/5P6QfXk916tW', '555-0105', 0, 0, 1, 1, '2023-10-06 08:10:00', '2023-10-06 08:10:00'),
(7, 'Olivia Davis (Borrower)', 'olivia.d@example.com', '$2y$10$Qx/h1C8X/l9fL9Pq0vH/QOf9D0hI00vVfS8b.W0T/5P6QfXk916tW', '555-0106', 0, 0, 1, 0, '2023-10-07 13:25:00', '2023-10-07 13:25:00');

-- 2. Insert Drivers with daily fee and transmission preference
INSERT INTO `drivers` (`user_id`, `daily_fee`, `transmission_preference`, `driving_preference`) VALUES 
(4, 30.00, 'Both', 'any_vehicle'), 
(5, 25.00, 'Auto', 'any_vehicle'),
(6, 22.00, 'Manual', 'any_vehicle');

-- 3. Insert Driving Licenses
INSERT INTO `driving_licenses` (`id`, `user_id`, `license_number`, `expiry_date`, `upload_format`, `status`, `reviewed_by`, `reviewed_at`, `rejection_reason`, `created_at`) VALUES
(1, 4, 'DL-8492019', '2028-12-31', 'image', 'verified', 1, '2023-10-05 10:00:00', NULL, '2023-10-04 15:00:00'),
(2, 5, 'DL-3394821', '2025-06-15', 'image', 'verified', 1, '2023-10-06 10:00:00', NULL, '2023-10-05 17:00:00'),
(3, 6, 'DL-9948211', '2027-01-20', 'pdf', 'pending', NULL, NULL, NULL, '2023-10-06 09:00:00');

INSERT INTO `driving_license_images` (`id`, `license_id`, `front_image_path`, `back_image_path`) VALUES
(1, 1, 'licenses/mock_license_david_front.jpg', 'licenses/mock_license_david_back.jpg'),
(2, 2, 'licenses/mock_license_emma_front.jpg', 'licenses/mock_license_emma_back.jpg');

INSERT INTO `driving_license_pdfs` (`id`, `license_id`, `file_path`) VALUES
(1, 3, 'licenses/mock_license_james.pdf');

-- 4. Insert Vehicles (including Electric category)
INSERT INTO `vehicles` (`id`, `owner_id`, `make`, `model`, `category`, `daily_rate`, `location`, `transmission`, `mileage`, `km_rate`, `yom`, `yor`, `description`, `status`, `rejection_reason`, `created_at`) VALUES
(1, 2, 'Tesla', 'Model S', 'Electric', 150.00, 'Downtown Metro', 'Auto', 15000, 18.00, 2022, 2022, 'Experience the thrill of electric power with this fully loaded Tesla Model S. Features autopilot, premium interior, and free supercharging.', 'approved', NULL, '2023-10-03 10:00:00'),
(2, 2, 'Mercedes-Benz', 'S-Class', 'Luxury', 250.00, 'Westside Suburbs', 'Auto', 22000, 10.50, 2021, 2022, 'The pinnacle of luxury. Perfect for corporate events or weddings. Chauffeur arrangement highly recommended.', 'approved', NULL, '2023-10-03 14:00:00'),
(3, 3, 'Jeep', 'Wrangler Rubicon', 'Offroad', 120.00, 'North Hills', 'Manual', 35000, 8.00, 2020, 2020, 'Trail-ready Jeep Wrangler with 35-inch tires and winch. Take this wherever the road ends.', 'approved', NULL, '2023-10-04 11:00:00'),
(4, 3, 'Toyota', 'Camry', 'Budget', 45.00, 'Airport Transit', 'Auto', 45000, 14.50, 2019, 2020, 'Reliable, clean, and fuel-efficient. Great for getting around the city on a budget.', 'approved', NULL, '2023-10-04 11:30:00'),
(5, 2, 'Porsche', '911 Carrera', 'Premium', 350.00, 'Downtown Metro', 'Auto', 8000, 9.00, 2023, 2023, 'Iconic sports car. Currently awaiting admin approval before it goes live.', 'pending_review', NULL, '2023-10-08 10:00:00');

-- 4.1 Insert Vehicle Photos
INSERT INTO `vehicle_photos` (`id`, `vehicle_id`, `photo_path`, `is_primary`) VALUES
(1, 1, 'https://images.unsplash.com/photo-1560958089-b8a1929cea89?q=80&w=600', 1),
(2, 2, 'https://images.unsplash.com/photo-1618843479313-40f8afb4b4d8?q=80&w=600', 1),
(3, 3, 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?q=80&w=600', 1),
(4, 4, 'https://images.unsplash.com/photo-1621007947382-bb3c3994e3fd?q=80&w=600', 1),
(5, 5, 'https://images.unsplash.com/photo-1503376760367-1582e0e014e7?q=80&w=600', 1);

-- 5. Insert Bookings
INSERT INTO `bookings` (`id`, `vehicle_id`, `borrower_id`, `driver_arrangement`, `assigned_driver_id`, `pickup_date`, `return_date`, `pickup_location`, `total_price`, `status`, `created_at`) VALUES
(1, 4, 6, 'self', NULL, '2023-10-10', '2023-10-12', 'Airport Terminal 2', 90.00, 'completed', '2023-10-08 09:00:00'),
(2, 2, 7, 'hired', 5, '2023-11-15', '2023-11-16', 'Grand Hotel Downtown', 250.00, 'confirmed', '2023-10-09 10:00:00'),
(3, 3, 6, 'self', NULL, '2023-11-20', '2023-11-25', 'North Hills Trailhead', 600.00, 'pending_verification', '2023-10-10 14:00:00'),
(4, 1, 7, 'owner', NULL, '2023-10-15', '2023-10-16', 'Westside Station', 150.00, 'cancelled', '2023-10-11 09:00:00');

-- 6. Insert Reviews
INSERT INTO `reviews` (`id`, `booking_id`, `reviewer_id`, `target_id`, `target_type`, `rating`, `comment`, `created_at`) VALUES
(1, 1, 6, 3, 'owner', 5, 'Car was very clean and Michael was easy to coordinate with. Highly recommend for a cheap rental!', '2023-10-13 09:00:00');

