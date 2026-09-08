# API Documentation

The EliteDrive backend handles asynchronous requests through a collection of PHP endpoints located in `public/api/`. These endpoints expect standard form submissions or JSON payloads and typically return JSON responses with a `success` boolean and an optional `message` or `error`.

## 1. Bookings (`/api/bookings/`)

### `POST /api/bookings/create.php`
Creates a new booking and places it in `pending_payment` (or `pending_verification` if driver's license check is needed, or `pending_assignment` if a hired driver is requested but none is specified).
- **Parameters:**
  - `vehicle_id` (int)
  - `pickup_date`, `return_date` (datetime strings)
  - `pickup_location`, `return_location` (string)
  - `driver_arrangement` (enum: 'self', 'hired', 'owner')
  - `assigned_driver_id` (int, optional)

### `POST /api/bookings/pay.php`
Initiates a Stripe Checkout session for a booking in `pending_payment` status.
- **Parameters:**
  - `booking_id` (int)

### `POST /api/bookings/assign_driver.php`
Allows an administrator to assign a driver from the unassigned pool to a specific booking.
- **Parameters:**
  - `booking_id` (int)
  - `driver_id` (int)

### `GET /api/bookings/unassigned.php`
Fetches a list of active bookings that currently require a hired driver but do not have one assigned (e.g. `pending_assignment`).

### `POST /api/bookings/dispute.php`
Allows a borrower to submit a dispute for an active or completed booking.
- **Parameters:**
  - `booking_id` (int)
  - `reason` (string)
  - `details` (string)
  - `preferred_contact_method` (string: email/phone)
  - `contact_info` (string)

## 2. Drivers (`/api/drivers/`)

### `GET /api/drivers/available.php`
Fetches a list of drivers available for a specific date range and vehicle transmission type. Excludes the current user (if they are a driver) to prevent them from hiring themselves.
- **Parameters:**
  - `start`, `end` (datetime strings)
  - `transmission` (string)

## 3. Vehicles (`/api/vehicles/`)
Handles vehicle lifecycle (e.g., approval, rejection, suspension).

## 4. Licenses (`/api/licenses/`)
Handles license verification workflows (approve, reject).

## 5. Administration & System Management
Other endpoints in `admin`, `payments`, `disputes`, and `reviews` follow a similar CRUD pattern, usually requiring the `requireAdmin()` check from `includes/auth.php`.
