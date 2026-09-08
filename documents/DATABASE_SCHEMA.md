# Database Schema Documentation

This document outlines the database structure for the EliteDrive Vehicle Rental System (`elitedrive` database). The database relies heavily on foreign key constraints for referential integrity (cascading deletes) and uses `ENUM` fields to manage state.

## 1. `users` Table
The core table containing all registered individuals on the platform. Roles are determined by boolean flags rather than a single role column, allowing a single user to be multiple things (e.g., an Owner and a Driver).

- `id`: INT (Primary Key)
- `full_name`: VARCHAR
- `email`: VARCHAR (Unique)
- `password_hash`: VARCHAR
- `contact_number`: VARCHAR
- `is_admin`, `is_owner`, `is_borrower`, `is_driver`: TINYINT (Booleans)

## 2. `drivers` Table
Extends the `users` table for users marked as `is_driver = 1`. 

- `user_id`: INT (Primary Key, Foreign Key to `users.id`)
- `daily_fee`: DECIMAL (The daily charge added to bookings when this driver is hired)
- `transmission_preference`: ENUM ('Manual', 'Auto', 'Both')
- `driving_preference`: ENUM ('own_vehicles', 'any_vehicle')

## 3. `driving_licenses` & Image/PDF Tables
Stores driver's licenses uploaded by users for platform verification.

**`driving_licenses`**
- `status`: ENUM ('pending', 'verified', 'rejected', 'expired') - *Checked during booking.*
- `upload_format`: ENUM ('pdf', 'image')
- `reviewed_by`: INT (Foreign Key to Admin who reviewed it)

**`driving_license_pdfs` / `driving_license_images`**
- Stores file paths to the uploaded documents, linked to `driving_licenses.id`.

## 4. `vehicles` & `vehicle_photos` Tables
Stores the fleet available on the platform.

**`vehicles`**
- `owner_id`: INT (Foreign Key to `users.id`)
- `category`: SET ('Premium', 'Luxury', 'Budget', 'Offroad', 'Electric')
- `daily_rate`: DECIMAL (Base price per day/4 blocks)
- `transmission`: ENUM ('Auto', 'Manual')
- `status`: ENUM ('draft', 'pending_review', 'approved', 'rejected', 'suspended')

**`vehicle_photos`**
- Links multiple photos to a vehicle, with a boolean `is_primary` flag to indicate the thumbnail.

## 5. `bookings` Table
The central transactional table connecting borrowers, vehicles, and (optionally) drivers.

- `vehicle_id`, `borrower_id`, `assigned_driver_id`: Foreign Keys
- `driver_arrangement`: ENUM ('owner', 'self', 'hired')
- `pickup_date`, `return_date`: DATETIME (Used for 6-hour block calculation)
- `pickup_location`, `return_location`: VARCHAR (Standardized hubs: CMB Katunayaka, HRI Mattala, EliteDrive HQ)
- `total_price`: DECIMAL (Calculated via functions.php)
- `status`: ENUM ('pending_assignment', 'pending_payment', 'pending_verification', 'confirmed', 'active', 'completed', 'reviewed', 'rejected', 'cancelled', 'disputed', 'resolved')

## 6. `payments` Table
Records Stripe payment intents and transactions associated with a booking.

- `booking_id`, `user_id`: Foreign Keys
- `stripe_session_id`, `stripe_payment_intent_id`: VARCHAR (Unique identifiers from Stripe)
- `amount`: DECIMAL
- `currency`: CHAR(3) (Default 'LKR')
- `status`: ENUM ('pending', 'completed', 'failed', 'refunded')

## 7. `reviews` Table
Allows users to review different entities within a booking (e.g., the vehicle, the driver, the owner, or the platform).

- `target_type`: ENUM ('vehicle', 'owner', 'driver', 'platform')
- `target_id`: INT (The ID of the entity being reviewed)
- `rating`: INT (1-5)

## 8. `rejection_logs` Table
Maintains an audit trail of admin rejections across the platform.

- `entity_type`: ENUM ('license', 'vehicle', 'booking', 'other')
- `entity_id`: INT
- `reason`: TEXT
- `rejected_by`: INT (Admin ID)

## 9. `inquiries` Table
Stores contact form submissions.

- `full_name`, `email`, `subject`, `message`: Submitted by user
- `status`: ENUM ('pending', 'responded', 'closed')
- `admin_response`: TEXT (Admin's reply)

## 10. `disputes` Table
Tracks disputes submitted by borrowers for a booking.

- `booking_id`, `user_id`: Foreign Keys
- `reason`, `details`: TEXT (Reason and full description of the dispute)
- `preferred_contact_method`: ENUM ('email', 'phone')
- `contact_info`: VARCHAR
- `status`: ENUM ('open', 'resolved')
- `admin_notes`: TEXT
