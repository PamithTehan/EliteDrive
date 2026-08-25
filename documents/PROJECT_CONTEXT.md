# EliteDrive — Project Context Document

## 1. Product Summary
EliteDrive is a premium car rental marketplace connecting **vehicle owners** who list cars with **borrowers** who rent them, optionally matched with **hired drivers**, all governed by a **platform administrator**. The brand positioning (per `DESIGN.md`) is *authoritative, high-trust, corporate-modern* — navy/slate/electric-blue palette, Inter typography, soft rounded corners, tonal elevation. Every flow below should read as "confident and frictionless," matching that visual language (e.g., verification steps use Trust Badge components; blocked/pending states use the Secondary/Ghost button style rather than alarming red except for true errors).

## 2. User Roles

| Role | Description |
|---|---|
| **Admin** | Platform staff. Verifies identities & documents, approves vehicle listings, manages fleet categories, resolves disputes, oversees payouts/commission, monitors platform health. |
| **Vehicle Owner** | Lists one or more vehicles for rent. Sets pricing, availability, fleet category. May optionally also act as the Driver for their own vehicle's bookings. |
| **Vehicle Driver** | The person physically operating the vehicle during a trip. This is a **function**, not always a distinct account type — it can be fulfilled by the Owner, by the Borrower, or by a separate third-party Driver account. |
| **Vehicle Borrower** | The customer renting a vehicle. May drive it themself, or request a driver be provided. |

### 2.1 Role Composition (critical modeling rule)
"Driver" is not always its own person — the platform must support **three arrangements** per booking:

1. **Owner-as-Driver** — the vehicle owner personally drives the borrower (chauffeur-style rental).
2. **Borrower-as-Driver** — the borrower self-drives (the classic car-rental model).
3. **Separate Driver** — a third-party driver account (not the owner, not the borrower) is assigned to the trip.

A single underlying `User` can hold multiple role capabilities (e.g., an account can be both "Owner" and "Driver-certified"), but each **booking** has exactly one resolved "who is driving" answer, computed from which arrangement was selected.

## 3. Core Business Rule: Mandatory Driving License

> **Whoever will physically drive the vehicle must hold a valid, admin-verified driving license on file — unless that person is not the one driving.**

Concretely:

| Arrangement | Does Borrower need a license? | Does Owner need a license? | Does the Driver need a license? |
|---|---|---|---|
| Owner drives (chauffeur) | No | **Yes, mandatory** | N/A (owner *is* the driver) |
| Borrower self-drives | **Yes, mandatory** | No (not driving) | N/A |
| Separate hired Driver | No (not driving) | No (not driving) | **Yes, mandatory** |

Rules of thumb for engineering this:
- License requirement is attached to **whichever account is tagged as the trip's Driver**, not to a fixed role.
- A booking cannot proceed past "Confirmed" status until the resolved driver's license is `verified` in the system.
- Owners who never intend to drive their own vehicles are not required to hold a license.
- Borrowers who always hire a driver are never required to hold a license.
- License verification is an **Admin-only** action (upload → pending → approved/rejected), not self-certified.

## 4. Fleet Categories
Four fixed categories, each with its own visual badge (per DESIGN.md's "Badges/Tags" component, 12px radius):

| Category | Positioning | Example use case | Typical badge label |
|---|---|---|---|
| **Premium** | Everyday high-end sedans/SUVs, business travel | Tesla Model 3, Audi A6 | `PREMIUM` |
| **Luxury** | Aspirational, high-performance or prestige vehicles | Porsche 911, Range Rover | `LUXURY` |
| **Budget** | Economical, functional, lower daily rate | Compact hatchbacks | `BUDGET` |
| **Offroad** | 4x4 / adventure-capable vehicles | Jeep Wrangler, Land Cruiser | `OFFROAD` |

Each vehicle listing belongs to exactly one category, set by the Owner at listing time and confirmed by Admin during approval.

## 5. Core Entities (conceptual data model)

- **User** — base account: id, name, contact, verification status, role flags (`isOwner`, `isDriverCertified`, `isBorrower`), profile documents.
- **DrivingLicense** — belongs to a User; number, issuing authority, expiry, uploaded image, `status: pending | verified | rejected | expired`.
- **Vehicle** — belongs to an Owner; make/model, photos, fleet category, daily rate, location, availability calendar, `status: draft | pending_review | approved | suspended`.
- **Booking** — links Vehicle + Borrower + resolved Driver (owner/borrower/third-party) + dates + pricing + `driverArrangement: owner | self | hired`.
- **DriverAssignment** — only exists when arrangement = `hired`; links Booking to a third-party Driver account.
- **Payment/Payout** — booking payment from Borrower, commission split, payout to Owner (and Driver, if hired and paid separately).
- **Dispute/SupportTicket** — raised by any party, resolved by Admin.
- **Review** — Borrower rates Vehicle/Owner/Driver post-trip.

## 6. Booking Lifecycle (states)
`Browsing → Requested → Pending Verification (license/documents check) → Confirmed → Active (trip in progress) → Completed → Reviewed`
Alternate branches: `Rejected` (failed verification or owner declines), `Cancelled`, `Disputed`.

## 7. Trust & Safety
- All identity documents (owner ID, driver's license, vehicle registration/insurance) are Admin-verified before a listing or a driving assignment goes live — consistent with the landing page's "Premium Insurance / 24/7 Support" trust messaging.
- Vehicles require Admin approval before appearing in search results.
- Drivers (owner or third-party) require license verification before being assignable to any booking.

## 8. Monetization
- Commission percentage taken by the platform on each completed booking.
- Optional separate driver fee when arrangement = `hired`, paid to the Driver account, also commissioned.

## 9. Design Reference
Visual system: see `DESIGN.md` (colors, typography, spacing, shapes, components already defined). Landing page screenshot (`screen.png`) shows the reference hero, fleet cards, and footer — new screens (dashboards, verification flows, booking flows) should reuse the same Navy/Electric-Blue/Slate palette, Inter type scale, 4–12px rounding, and tonal-elevation card style rather than introducing new visual language.

## 10. Non-Functional Notes
- Document upload & verification needs secure storage + admin review queue.
- License/insurance expiry should be tracked and trigger re-verification reminders.
- Multi-role accounts (e.g., an Owner who is also Driver-certified) need a single login with a role/context switcher in the dashboard.
