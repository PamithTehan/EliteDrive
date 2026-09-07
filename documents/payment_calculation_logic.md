# Payment Calculation Logic

The payment calculation logic for the EliteDrive platform uses a 6-hour block system with a built-in 1-hour grace period, rather than a strict 24-hour daily cycle. This ensures borrowers are billed fairly for partial days.

## 1. Daily Rates
The **Effective Daily Rate** is the sum of:
1. **Vehicle Daily Rate**: The base price set for the specific vehicle class/model.
2. **Driver Daily Fee**: If a professional chauffeur or the vehicle owner is hired, their daily fee is added. If the borrower is self-driving, this fee is `LKR 0.00`.

*Formula:*
`Effective Daily Rate = Vehicle Daily Rate + Driver Daily Fee`

## 2. Chargeable Blocks
The Effective Daily Rate is divided into 4 equal segments, creating **6-hour chargeable blocks**.

*Formula:*
`Block Rate = Effective Daily Rate / 4`

## 3. Rental Duration & The Grace Period
When calculating the duration between the `pickup_date` and `return_date`:
1. **Calculate Total Minutes**: Find the exact difference in minutes.
2. **Apply Grace Period**: We provide a complimentary 1-hour (60 minutes) grace period on the return time. 
   - 60 minutes are subtracted from the total rental minutes to find the "Billable Minutes" (minimum of 1 minute).
   - If the total duration is over 60 minutes, the grace period is effectively applied, waiving the first hour of what would otherwise be a new block.

## 4. Final Calculation
The system converts the billable minutes back into hours, and calculates the total billable blocks:
1. **Full Days**: Every 24 hours equals 4 blocks.
2. **Remaining Hours**: Any remaining hours are divided by 6 and rounded up to determine the remaining blocks.
3. **Total Price**: 
   `Total Price = (Full Days * Effective Daily Rate) + (Remaining Blocks * Block Rate)`

### Example Scenario
- **Vehicle Rate:** LKR 10,000/day
- **Driver Fee:** LKR 0 (Self-drive)
- **Effective Daily Rate:** LKR 10,000/day
- **Block Rate:** LKR 2,500 per 6-hour block

**Duration:** 25 hours.
- *Without Grace Period:* 25 hours = 1 full day (24 hours) + 1 extra hour. The extra hour would trigger a new 6-hour block (LKR 2,500). Total = LKR 12,500.
- *With Grace Period:* 25 hours = 1500 minutes. Subtract 60 minutes = 1440 billable minutes (exactly 24 hours). The extra hour is waived. Total = LKR 10,000.
