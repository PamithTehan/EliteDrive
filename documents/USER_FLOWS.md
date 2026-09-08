# EliteDrive — User Flows

Companion to `PROJECT_CONTEXT.md`. Screen references assume the EliteDrive visual system (Navy/Electric-Blue/Slate, Inter, soft rounding) already used on the landing page.

---

## 1. Vehicle Owner Flows

### 1.1 Onboarding & Verification
1. Sign up (email/phone) → select "List a vehicle" intent.
2. Complete identity verification: government ID upload.
3. **Branch:** "Will you ever drive your own vehicles for bookings?"
   - Yes → prompted to upload driving license now (see §3 rule table) → `pending` until Admin verifies.
   - No → skipped, can add later if arrangement changes.
4. Admin reviews identity (and license, if submitted) → account marked `verified` or `rejected` (with reason).
5. Owner dashboard unlocked.

### 1.2 List a Vehicle
1. Dashboard → "Add Vehicle."
2. Enter details: make/model, photos, fleet category (**Premium / Luxury / Budget / Offroad**), daily rate, location, availability calendar, insurance doc, registration doc.
3. Submit → listing status = `pending_review`.
4. Admin approves/rejects (with reason) → status = `approved` (goes live) or `rejected` (owner edits & resubmits).

### 1.3 Manage Bookings
1. Incoming booking request appears in Owner dashboard with borrower details + chosen driver arrangement.
2. If arrangement = **Owner drives**, system checks Owner's license is `verified`; if not verified/expired, Owner is blocked from accepting until resolved.
3. Owner accepts/declines request.
4. On completion: releases payout (minus commission), can leave a review of the borrower.

### 1.4 Payouts
1. Dashboard → Earnings tab: per-trip breakdown, pending vs. released.
2. Owner sets/edits payout account.
3. Payout auto-released N days after trip completion (or on-demand, per platform policy).

---

## 2. Vehicle Borrower Flows

### 2.1 Signup
1. Sign up → basic profile (no license required yet — only required if they intend to self-drive).

### 2.2 Search & Filter Fleet
1. Landing page search bar (pickup/return dates, car class) → results grid.
2. Filter by fleet category chip (Premium / Luxury / Budget / Offroad), price, seats, transmission.
3. Select a vehicle → detail page (photos, specs, owner rating, trust badges).

### 2.3 Choose Driver Arrangement (key decision point)
On the booking screen, borrower picks one:
- **"I'll drive myself"** → system checks borrower has a `verified` license.
  - If none on file → prompted to upload now → booking held at `pending_verification` until Admin approves.
- **"Provide the owner as driver"** (if the owner offers chauffeur service for that listing) → system checks Owner's license is `verified`. No license needed from borrower.
- **"Assign a hired driver"** → borrower does not need a license. The borrower can either select a verified driver from the pool immediately or skip this step. If skipped, the booking enters `pending_assignment` state, where an Admin will assign a driver later.

### 2.4 Confirm & Pay
1. Review trip summary, price breakdown, cancellation policy.
2. Select payment method: Online (Stripe) or "Pay for Headquarters".
3. Payment execution:
   - If Online: Booking status → `Confirmed` (after successful payment and driver verification).
   - If Pay for Headquarters: Booking status stays `pending_payment` until Admin manually confirms the payment via the Ongoing Bookings tab.
   - If the booking was placed into `pending_assignment`, the payment step is deferred until the Admin assigns a driver and calculates the final fee.

### 2.5 Trip
1. Pickup: borrower (or driver) checks in at the designated pickup hub (CMB, HRI, or Colombo HQ).
2. Trip runs; in-app support access throughout (per "24/7 Support" trust feature).
3. Drop-off: Vehicle is returned to the selected return hub; both sides confirm condition/mileage.
4. Booking → `Completed`.

### 2.6 Post-Trip
1. Borrower rates vehicle, owner, and driver (if hired) separately.
2. Receipt & invoice available in dashboard.
3. Disputes (damage, no-show, mismatch) can be raised via the dashboard modal → routed to Admin for review. Once raised, the booking status moves to `disputed`.

---

## 3. Vehicle Driver Flows (separate third-party driver account)

### 3.1 Mandatory Onboarding
1. Sign up as "Driver."
2. **Mandatory**: upload driving license + relevant permit (e.g., commercial/for-hire permit if required by region).
3. Admin verifies license → `verified` (drivers cannot be assigned to any trip while `pending` or `rejected`).
4. Optional: background check step depending on platform policy.

### 3.2 Availability & Assignment
1. Driver sets availability windows, service area, vehicle categories they're comfortable with.
2. When a borrower selects "Assign a hired driver," system surfaces eligible, verified, available drivers.
3. Driver receives assignment request → accepts/declines.

### 3.3 Trip Execution
1. Driver reports to pickup location, verifies borrower identity/booking code.
2. Executes trip; app tracks trip status.
3. Confirms drop-off, logs any incidents.

### 3.4 Earnings & Reviews
1. Dashboard shows per-trip fee, pending/released payouts.
2. Receives rating from borrower (and indirectly reflects on owner's listing reputation).

---

## 4. Admin Flows

### 4.1 Identity & License Verification Queue
1. Central queue of pending: Owner IDs, Owner licenses (if self-driving), Borrower licenses (if self-driving), Driver licenses (mandatory), vehicle registration/insurance docs.
2. Admin opens each item → approve or reject with reason → notifies the user.
3. Expiry tracking: flagged licenses re-enter the queue near expiry.

### 4.2 Vehicle Listing Approval
1. Queue of `pending_review` vehicles.
2. Admin checks photos, documents, category correctness (Premium/Luxury/Budget/Offroad) → approve (goes live) or reject (with notes back to Owner).

### 4.3 Dispute Resolution & Ongoing Bookings
1. Ticket queue (damage claims, no-shows, payment disputes, driver conduct).
2. Admin reviews trip data, messages, evidence → issues resolution. When resolved, the booking status is updated to `resolved`.
3. **Ongoing Bookings**: Admins monitor all active/upcoming bookings. If a user selected "Pay for Headquarters", the Admin can manually **Confirm** (update status to `confirmed`) once payment is received, or **Reject** the booking if the user fails to pay or verify (logs reason to `rejection_logs`).

### 4.4 Fleet & Category Management
1. Manage the four fixed categories (naming, badge styling, featured placement on landing page "Our Premium Fleet" section).
2. Re-categorize a vehicle if misclassified.

### 4.5 Platform Oversight
1. Analytics: bookings, revenue, commission, active users per role, verification turnaround time.
2. User account management: suspend/reinstate accounts across all four role types.

---

## 5. Combined Scenario Flows (Owner / Driver / Borrower Overlap)

These are the four end-to-end paths a booking can take, driven entirely by the "who is driving" decision in §2.3.

**Scenario A — Owner drives (chauffeur rental)**
`Borrower browses → selects vehicle → picks "Owner drives" → system verifies Owner's license → Owner accepts request → payment → trip (Owner drives) → completion → borrower reviews Owner as both host and driver.`

**Scenario B — Borrower self-drives (classic rental)**
`Borrower browses → selects vehicle → picks "I'll drive" → uploads/confirms own license → Admin verification if new → Owner accepts → payment → trip (Borrower drives) → completion → borrower reviews Owner/vehicle only (no separate driver).`

**Scenario C — Separate hired Driver, Owner not present**
`Borrower browses → selects vehicle → picks "Assign a hired driver" → system lists verified Drivers → Borrower selects one → Driver accepts → payment (includes driver fee) → trip (hired Driver drives) → completion → borrower reviews vehicle/Owner AND Driver separately.`

**Scenario D — Owner also happens to be Driver-certified but chooses not to drive this trip**
Same as Scenario B or C; the Owner's own driver certification is irrelevant to this specific booking — it only matters for bookings where they are explicitly selected as the driver (Scenario A).

### Decision Logic Summary (for engineering handoff)
```
On booking creation:
  driverArrangement = one of { OWNER, SELF, HIRED }

  if driverArrangement == OWNER:
      require Owner.license.status == VERIFIED
  elif driverArrangement == SELF:
      require Borrower.license.status == VERIFIED
  elif driverArrangement == HIRED:
      require assignedDriver.license.status == VERIFIED
      # Borrower and Owner license status irrelevant here

  booking.status = CONFIRMED only if the relevant check above passes
```

---

## 6. Suggested Screen List (mapped to flows above)
- Landing page (exists)
- Sign up / role selection (Owner / Borrower / Driver)
- Document upload & verification status screen
- Owner dashboard: My Vehicles, Bookings, Earnings
- Vehicle listing form (multi-step, includes fleet category picker)
- Fleet search & filter results (extends existing "Our Premium Fleet" grid)
- Vehicle detail + booking flow with driver-arrangement selector
- Driver dashboard: Availability, Assignments, Earnings
- Admin console: Verification Queue, Listing Approval, Disputes, Analytics, Category Management
- Trip-in-progress screen (status, support access)
- Post-trip review screen (separate ratings for vehicle/Owner and Driver if hired)
