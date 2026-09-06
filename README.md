# EliteDrive Vehicle Rental System

EliteDrive is a premium vehicle rental platform designed to provide a luxury automotive experience. It features flexible billing models, specialized driver arrangements, and a clean, responsive web interface.

## Documentation

Comprehensive documentation for this project is available in the `documents/` directory:

### Setup & Deployment
- **[Local Deployment Guide](documents/local_deployment.md)**: Step-by-step instructions for installing and running the system on a local development server.
- **[Configuration Setup](documents/CONFIG_SETUP.md)**: Instructions for configuring the `config.php` file and connecting to the database.

### System Architecture & Logic
- **[Database Schema](documents/DATABASE_SCHEMA.md)**: Entity-relationship details and enum status breakdowns for all core tables.
- **[API Documentation](documents/API_DOCUMENTATION.md)**: Details on the asynchronous JSON endpoints used for bookings, payments, and driver assignments.
- **[Payment Calculation Logic](documents/payment_calculation_logic.md)**: Details on the 6-hour block billing system, effective daily rates, and the 1-hour grace period implementation.
- **[Design Guidelines](documents/DESIGN.md)**: Overview of the aesthetic rules, typography, UI patterns, and styling conventions used across the platform.
- **[Project Context](documents/PROJECT_CONTEXT.md)**: High-level overview of the EliteDrive vision, brand identity, and system boundaries.
- **[Implementation Guide](documents/IMPLEMENTATION_GUIDE.md)**: Technical overview of the architecture and coding conventions.
- **[User Flows](documents/USER_FLOWS.md)**: Detailed journeys mapping how different users interact with the system.
- **[Users](documents/users.md)**: Overview of the different user roles (Borrowers, Vehicle Owners, Drivers, Administrators) and their permissions.

## Key Features
- **Flexible Billing:** Unique 6-hour charge block calculation with an automatic grace period, ensuring users only pay for what they use.
- **Driver Arrangements:** Borrowers can opt to self-drive, hire a professional chauffeur, or request the vehicle owner to drive.
- **Role-Based Access:** Dedicated dashboards for Administrators, Borrowers, Drivers, and Owners.
- **Dynamic Hub Selection:** Support for varied pickup and return locations across Sri Lanka (CMB Katunayaka, HRI Mattala, EliteDrive Colombo HQ).

## Tech Stack
- **Frontend:** HTML5, Vanilla CSS (Custom Design System), JavaScript
- **Backend:** PHP 8+ (Vanilla, No frameworks)
- **Database:** MySQL/MariaDB

## License
*Academic purposes only.*
