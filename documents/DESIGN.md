---
name: Executive Fleet Narrative
colors:
  surface: '#f8fafc'
  surface-dim: '#e2e8f0'
  surface-bright: '#ffffff'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f8fafc'
  surface-container: '#f1f5f9'
  surface-container-high: '#e2e8f0'
  surface-container-highest: '#cbd5e1'
  on-surface: '#0f172a'
  on-surface-variant: '#334155'
  inverse-surface: '#0f172a'
  inverse-on-surface: '#f8fafc'
  outline: '#e2e8f0'
  outline-variant: '#cbd5e1'
  primary: '#0f172a'
  on-primary: '#ffffff'
  primary-container: '#1e293b'
  on-primary-container: '#cbd5e1'
  secondary: '#334155'
  on-secondary: '#ffffff'
  accent: '#3b82f6'
  on-accent: '#ffffff'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  success: '#1a7f37'
  on-success: '#ffffff'
  success-container: '#dcfce7'
  on-success-container: '#14532d'
typography:
  headline-xl:
    fontFamily: Inter
    fontSize: 48px
    fontWeight: '700'
    lineHeight: 56px
    letterSpacing: -0.02em
  headline-lg:
    fontFamily: Inter
    fontSize: 32px
    fontWeight: '600'
    lineHeight: 40px
    letterSpacing: -0.01em
  headline-lg-mobile:
    fontFamily: Inter
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
  headline-md:
    fontFamily: Inter
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
  headline-sm:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '600'
    lineHeight: 24px
  body-lg:
    fontFamily: Inter
    fontSize: 18px
    fontWeight: '400'
    lineHeight: 28px
  body-md:
    fontFamily: Inter
    fontSize: 15px
    fontWeight: '400'
    lineHeight: 24px
  body-sm:
    fontFamily: Inter
    fontSize: 13px
    fontWeight: '400'
    lineHeight: 20px
  label-md:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '500'
    lineHeight: 20px
    letterSpacing: 0.05em
    textTransform: uppercase
  label-sm:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '600'
    lineHeight: 16px
    letterSpacing: 0.03em
rounded:
  sm: 4px
  md: 8px
  lg: 12px
  xl: 16px
  full: 9999px
spacing:
  container-max: 1360px
  gutter: 24px
  margin-desktop: 32px
  margin-tablet: 24px
  margin-mobile: 16px
  space-xs: 4px
  space-sm: 8px
  space-md: 16px
  space-lg: 24px
  space-xl: 48px
  space-2xl: 80px
---

# EliteDrive Design System Specification

## 1. Brand Identity & Aesthetic Direction

The **EliteDrive** design system is engineered to provide a high-performance, executive car rental marketplace experience. The brand balances **contemporary minimalism** with **automotive luxury and precision**.

- **Personality**: Authoritative, sleek, trustworthy, and frictionless.
- **Aesthetic Direction**: *Executive Fleet Narrative* — deep midnight navy contrasts against clean off-white surfaces, accented by crisp electric blue calls-to-action and refined slate typography.
- **Visual Rhythm**: Generous yet efficient spacing, clean 1px borders, tonal card surfaces, subtle hover elevations, and responsive typography built on the **Inter** typeface.

---

## 2. Color System

| Token | Hex | Usage |
| :--- | :--- | :--- |
| `--color-primary` | `#0F172A` | Deep Navy: Headings, primary containers, active tab highlights, logo, and core accents. |
| `--color-secondary` | `#334155` | Slate Gray: Secondary body text, icon tints, subtitles, inactive nav links, and metadata. |
| `--color-accent` | `#3B82F6` | Electric Blue: Interactive buttons, focus rings, primary CTAs, active status toggles. |
| `--color-surface` | `#F8FAFC` | Off-White Background: Clean backdrop preventing glare and making white cards stand out. |
| `--color-outline` | `#E2E8F0` | Subtle Border: 1px divider for cards, inputs, tables, and section separators. |
| `--color-success` | `#1A7F37` | Forest Green: Verified driver licenses, confirmed bookings, approved vehicles. |
| `--color-error` | `#BA1A1A` | Deep Crimson: Form validation alerts, rejection notices, cancellation badges. |

---

## 3. Typography System

EliteDrive exclusively utilizes **Inter** via Google Fonts for clean legibility across devices.

- **`headline-xl` (48px / 700 / -0.02em)**: Hero titles on Home, Fleet, About, FAQs, and Contact pages.
- **`headline-lg` (32px / 600 / -0.01em)**: Section headings, modal titles, and major card group titles.
- **`headline-md` (20px / 600)**: Card titles, vehicle make & model names, and widget headers.
- **`headline-sm` (16px / 600)**: Sub-headers, accordion trigger titles, and sidebar section headers.
- **`body-lg` (18px / 400 / 28px line-height)**: Hero lead paragraphs and introductory copy.
- **`body-md` (15px / 400 / 24px line-height)**: Primary reading text, descriptions, and form labels.
- **`body-sm` (13px / 400 / 20px line-height)**: Helper hints, caption text, and table subtext.
- **`label-md` (14px / 500 / uppercase + 0.05em tracking)**: Category tags, trust pill headers, and section overlines.
- **`label-sm` (12px / 600 / 0.03em tracking)**: Vehicle specification tags (Seats, Transmission, Fuel, Km Rate).

---

## 4. Layout, Grid & Screen Margins

To ensure maximum content readability and an expansive visual feel on high-resolution screens without squeezing page content, **screen side margins are optimized and reduced**:

- **Max Container Width (`--container-max`)**: `1360px` (centered via `margin-inline: auto`).
- **Desktop Side Margins (`--margin-desktop`)**: `32px` outer padding (reduced from 64px for a sleeker, wide-screen experience).
- **Tablet Side Margins (`--margin-tablet`)**: `24px` outer padding (`768px – 1024px` viewports).
- **Mobile Side Margins (`--margin-mobile`)**: `16px` outer padding (`< 768px` viewports).
- **Fluid Grid**:
  - `grid-4`: 4 columns desktop → 2 columns tablet → 1 column mobile.
  - `grid-3`: 3 columns desktop → 1 column mobile.
  - `grid-2`: 2 columns desktop → 1 column mobile.
  - Faceted layouts (Fleet & FAQs): `280px` / `300px` fixed-width sidebar + `1fr` flexible content area with a `24px` - `32px` gap.

---

## 5. Elevation, Shapes & Depth

- **Base Surface (Level 0)**: `#F8FAFC` page background.
- **Card Surface (Level 1)**: Pure white `#FFFFFF` surface with `1px solid var(--color-outline)` and `var(--radius-lg) (12px)` or `var(--radius-md) (8px)` corners.
- **Hover & Active (Level 2)**: Subtle ambient drop shadow `box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.08)` and smooth `-2px` Y-axis lift on vehicle cards and interactive elements.
- **Corner Radiuses**:
  - `4px` (`--radius-sm`): Buttons, input fields, badges, and segmented toggles.
  - `8px` (`--radius-md`): Sidebar menus, icon pods, alert cards, and accordion items.
  - `12px` (`--radius-lg`): Large cards, search widgets, modal dialogs, and hero image frames.
  - `9999px` (`--radius-full`): Avatar badges, pill tags, and pagination buttons.

---

## 6. Page-by-Page Architectural & Visual Blueprint

### A. Fleet Search & Catalog (`public/fleet/search.php`)
- **Hero Header**: Clean off-white header with `headline-xl` title and concise fleet description.
- **Faceted Sidebar Filter**:
  - Sticky sidebar container with real-time text query box with integrated search icon.
  - Pick-up and Return date pickers with calendar icons.
  - Multi-select Category filter chips (Electric, Luxury, Off-road, Sedan, SUV, Sports).
  - Radio transmission selector (All, Automatic, Manual) with instant live filtering.
  - Price range slider and reset buttons.
- **Vehicle Grid & Cards**:
  - High-res vehicle image with aspect ratio containment, category badge pill, and daily rate tag.
  - Make & Model in `headline-md`.
  - Specs list with icon pods (Seats, Transmission, Fuel/Electric, Km Rate).
  - "Book Now" (Electric Blue primary) & "View Details" action buttons.
- **Dynamic Pagination**:
  - Per-page dropdown selector (`6`, `10`, `14`, `All`).
  - Active page numbers with rounded pill styling, smart ellipsis (`...`) for large sets, and Prev/Next controls.

### B. About Us (`public/about.php`)
- **Hero Banner**: Full-bleed photographic background with a high-contrast dark gradient overlay (`rgba(0,0,0,0.8) → rgba(0,0,0,0.3)`), showcasing the "Our Identity" overline in `label-md` and headline in white.
- **Brand Story Section**: Dual-column layout pairing corporate story with a framed showroom photograph featuring an overlapping floating **"15+ Years of Excellence"** badge (`#1a1a2e`).
- **Core Values**: 3-column grid of white cards featuring soft blue icon containers (`#e0e7ff`), Material Symbols (`verified`, `support_agent`, `bolt`), and crisp value descriptions.
- **Team Showcase**: Grid of team profiles with subtle grayscale-to-color hover transition (`filter: grayscale(100%) → 0%`).

### C. Frequently Asked Questions (`public/faqs.php`)
- **Hero Header**: Centered white banner with `headline-xl` and subtitle lead paragraph.
- **Interactive Dual-Column Layout**:
  - **Category Tab Sidebar**: Vertical category list (General Booking, Our Fleet, Insurance & Protection, Policies & Requirements) with dark active state (`#000000` or `#0f172a`).
  - **Accordion List**: White card items with clean 1px borders, smooth chevron rotation (`transform: rotate(180deg)`), and animated disclosure of answer paragraphs.

### D. Contact Us & Concierge (`public/contact.php`)
- **Header**: Clear `headline-xl` title with dedicated concierge support lead text.
- **Contact Info & Concierge Card**:
  - Icon-pod list for Email, 24/7 Phone Support, and Global Headquarters in Colombo, Sri Lanka.
  - **Emergency Roadside Banner**: Dark `#111827` alert box with emergency icon and 24/7 dispatch instructions.
  - Social media icon pods with hover state transitions.
- **Contact Form**: Structured 2-column input fields (Name, Email, Subject, Message) with smooth focus borders and primary blue submit button.
- **Location Map Section**: Full-width curved banner with integrated center pin and location tooltip.

### E. Home & Landing Page (`public/index.php`)
- **Hero Section**: Cinematic background hero with dark overlay and bold value proposition.
- **Floating Quick-Booking Widget**: 4-column search bar (Pick-up location, Pick-up date, Return date, Car Class dropdown) with elevated shadow and direct redirect to the Fleet search.
- **Vehicle Category Carousel & Featured Fleet**: Highlighting top luxury and electric additions with dynamic status badges.

### F. User Portal & Management (`profile.php`, `admin/*`, `owner/*`, `driver/*`, `borrower/*`)
- **Account Roles & Preferences**: Interactive multi-role selection (Borrower, Owner, Driver) allowing dynamic profile upgrades.
- **Driver Rates & Transmission Settings**: Dynamically revealed fee slider ($20 - $35/day) and transmission capability selectors (Manual, Auto, Both).
- **Verification Queues & Dashboards**: Consistent 12-column dashboard grids, verified status pills (Verified, Pending, Rejected), and drag-and-drop file upload zones.

---

## 7. Component Library Guidelines

### Buttons (`components/buttons.css`)
- **`.btn-primary`**: Background `#3B82F6`, white text, 4px border radius. Hover: `brightness(0.95)`.
- **`.btn-secondary`**: Transparent background, 1px `#0F172A` border, `#0F172A` text.
- **`.btn-ghost`**: Transparent background, `#334155` text, subtle hover highlight.
- **`.btn-danger`**: Background `#BA1A1A`, white text.

### Badges & Status Pills (`components/badges.css`)
- **Verified / Approved**: Green background `#DCFCE7`, text `#14532D`.
- **Pending / In Review**: Amber background `#FEF3C7`, text `#92400E`.
- **Rejected / Suspended**: Red background `#FEE2E2`, text `#991B1B`.
- **Role Badges**: Distinct soft-colored pills for Admin (Purple), Owner (Emerald), Driver (Blue), Borrower (Slate).

### Forms & Input Controls (`components/forms.css`)
- **Inputs & Selects**: 1px solid `#E2E8F0`, 4px radius, 10px 12px padding, font 15px.
- **Focus State**: `outline: 2px solid #3B82F6; outline-offset: 1px;`.
- **Checkbox & Radio**: Native or styled with `accent-color: #0F172A` or `#3B82F6`.