# EliteDrive — Implementation Guide (PHP + MySQL + HTML/CSS/JS only)

Constraint acknowledged: **no frameworks, no Composer packages, no build tools** — plain procedural/lightly-OOP PHP, native `mysqli`/`PDO`, hand-written HTML/CSS, and vanilla JavaScript (`fetch`, no jQuery/React needed). This guide gives you a buildable structure, not just theory.

Companion files: `database_schema.sql` (run this first), `PROJECT_CONTEXT.md`, `USER_FLOWS.md`.

---

## 1. Architecture Pattern

Since no framework is allowed, use a simple **"Front Controller-lite" + page-per-feature** hybrid:

- **Server-rendered pages** (`.php` files) for full page loads: login, dashboards, listing forms, admin console.
- **JSON API endpoints** (`.php` files under `/api/`) for anything dynamic on the page: live search filtering, driver-arrangement selector, admin approve/reject buttons, license upload status polling.
- Shared PHP includes for DB connection, auth/session checks, and reusable functions — no MVC framework, just disciplined file organization.

```
Browser (HTML/CSS/JS)
   │  form POST / page GET            │ fetch() → JSON
   ▼                                   ▼
/public/*.php (renders HTML)      /api/*.php (returns JSON)
   │                                   │
   └──────────────► /includes/*.php ◄──┘   (db.php, auth.php, functions.php)
                          │
                          ▼
                    MySQL (elitedrive)
```

---

## 2. Folder Structure

```
elitedrive/
├── config/
│   └── config.php              # DB credentials, base URL, upload paths (NOT in web root ideally)
├── includes/
│   ├── db.php                  # PDO connection (singleton)
│   ├── auth.php                # login check, role check, session helpers
│   ├── functions.php           # helpers: sanitize(), redirect(), flash(), csrf_token()
│   └── upload.php              # secure file upload handler
├── public/                     # <-- web root points here
│   ├── index.php               # landing page (reuses existing design)
│   ├── login.php
│   ├── register.php
│   ├── logout.php
│   ├── fleet/
│   │   ├── search.php           # fleet search & filter page
│   │   └── detail.php?id=       # vehicle detail + booking form
│   ├── owner/
│   │   ├── dashboard.php
│   │   ├── vehicle_form.php     # add/edit vehicle
│   │   └── bookings.php
│   ├── driver/
│   │   ├── dashboard.php
│   │   └── assignments.php
│   ├── borrower/
│   │   ├── my_bookings.php
│   │   └── booking_confirm.php
│   ├── admin/
│   │   ├── verification_queue.php
│   │   ├── vehicle_approvals.php
│   │   ├── disputes.php
│   │   └── categories.php
│   ├── assets/
│   │   ├── css/
│   │   │   ├── base/
│   │   │   │   ├── tokens.css        # CSS variables from DESIGN.md
│   │   │   │   ├── reset.css         # minimal reset/normalize
│   │   │   │   └── typography.css    # headline/body/label scale
│   │   │   ├── layout/
│   │   │   │   ├── grid.css          # container, columns, spacing utilities
│   │   │   │   └── header-footer.css # nav bar + site footer
│   │   │   ├── components/
│   │   │   │   ├── buttons.css
│   │   │   │   ├── forms.css         # inputs, search form, file upload
│   │   │   │   ├── cards.css         # vehicle listing / dashboard cards
│   │   │   │   ├── badges.css        # fleet category tags, status pills
│   │   │   │   ├── tables.css        # admin queues
│   │   │   │   └── alerts.css        # flash messages, trust badges
│   │   │   └── pages/
│   │   │       ├── landing.css       # hero-specific overrides
│   │   │       ├── auth.css          # login/register
│   │   │       ├── fleet-search.css
│   │   │       └── dashboard.css     # owner/driver/admin shells
│   │   └── js/
│   │       ├── api.js           # tiny fetch() wrapper
│   │       ├── booking-form.js  # driver-arrangement logic
│   │       └── admin-queue.js
│   └── api/
│       ├── bookings/
│       │   ├── create.php
│       │   ├── update_status.php
│       │   └── list.php
│       ├── vehicles/
│       │   ├── search.php
│       │   └── approve.php
│       ├── licenses/
│       │   ├── upload.php
│       │   └── verify.php
│       └── drivers/
│           └── available.php
└── storage/                    # OUTSIDE web root — licenses, IDs, insurance docs
    ├── licenses/
    ├── ids/
    └── vehicle_docs/
```

> **Important:** `storage/` must sit **outside** the publicly served directory (or blocked via server config) — driving licenses and IDs are sensitive documents and must never be directly URL-accessible. Serve them only through an authenticated PHP script that checks the requester's permission before streaming the file.

---

## 3. Database Connection (`includes/db.php`)

```php
<?php
function getDb(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $cfg = require __DIR__ . '/../config/config.php';
        $dsn = "mysql:host={$cfg['db_host']};dbname={$cfg['db_name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $cfg['db_user'], $cfg['db_pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false, // real prepared statements
        ]);
    }
    return $pdo;
}
```

**Rule for the whole project: every query uses prepared statements. Never concatenate user input into SQL.**

```php
$stmt = getDb()->prepare('SELECT * FROM vehicles WHERE category_id = ? AND status = "approved"');
$stmt->execute([$categoryId]);
$vehicles = $stmt->fetchAll();
```

---

## 4. Authentication & Sessions (`includes/auth.php`)

```php
<?php
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    // 'cookie_secure' => true, // enable once served over HTTPS
]);

function currentUser(): ?array {
    return $_SESSION['user'] ?? null;
}

function requireLogin(): void {
    if (!currentUser()) {
        header('Location: /login.php');
        exit;
    }
}

function requireRole(string $role): void {
    requireLogin();
    $u = currentUser();
    $map = ['admin' => 'is_admin', 'owner' => 'is_owner', 'driver' => 'is_driver', 'borrower' => 'is_borrower'];
    if (empty($u[$map[$role]])) {
        http_response_code(403);
        exit('Forbidden: requires ' . $role . ' role.');
    }
}

function loginUser(array $userRow): void {
    session_regenerate_id(true); // prevent session fixation
    $_SESSION['user'] = $userRow; // omit password_hash from this array
}
```

**Password handling:**

```php
// Registration
$hash = password_hash($plainPassword, PASSWORD_BCRYPT);

// Login
if (password_verify($plainPassword, $row['password_hash'])) {
    unset($row['password_hash']);
    loginUser($row);
}
```

**CSRF protection** (needed since forms are plain HTML posts):

```php
// functions.php
function csrfToken(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrfCheck(string $token): bool {
    return hash_equals($_SESSION['csrf'] ?? '', $token);
}
```
Every form includes `<input type="hidden" name="csrf" value="<?= csrfToken() ?>">` and every POST handler calls `csrfCheck($_POST['csrf'])` before doing anything.

---

## 5. Core Business Rule in Code: The License Check

This is the rule from `PROJECT_CONTEXT.md` §3 — implement it as **one shared function** so it's enforced identically everywhere (booking creation, owner accept, admin override):

```php
// includes/functions.php

/**
 * Resolves which user must hold a verified license for a booking,
 * and whether that requirement is currently satisfied.
 */
function resolveLicenseRequirement(array $booking, PDO $db): array {
    $arrangement = $booking['driver_arrangement']; // 'owner' | 'self' | 'hired'

    $userIdToCheck = match ($arrangement) {
        'owner' => getVehicleOwnerId($booking['vehicle_id'], $db),
        'self'  => $booking['borrower_id'],
        'hired' => $booking['assigned_driver_id'],
    };

    $stmt = $db->prepare(
        'SELECT status, expiry_date FROM driving_licenses
         WHERE user_id = ? ORDER BY created_at DESC LIMIT 1'
    );
    $stmt->execute([$userIdToCheck]);
    $license = $stmt->fetch();

    $satisfied = $license
        && $license['status'] === 'verified'
        && strtotime($license['expiry_date']) > time();

    return [
        'user_id_required'   => $userIdToCheck,
        'satisfied'          => $satisfied,
        'license_status'     => $license['status'] ?? 'missing',
    ];
}
```

Booking creation endpoint (`/api/bookings/create.php`) uses it before ever setting status to `confirmed`:

```php
<?php
require __DIR__ . '/../../includes/db.php';
require __DIR__ . '/../../includes/auth.php';
require __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');
requireLogin();

if (!csrfCheck($_POST['csrf'] ?? '')) {
    http_response_code(419);
    echo json_encode(['error' => 'Invalid session token, please refresh.']);
    exit;
}

$db = getDb();
$user = currentUser();

$booking = [
    'vehicle_id'         => (int) $_POST['vehicle_id'],
    'borrower_id'        => $user['id'],
    'driver_arrangement' => $_POST['driver_arrangement'], // owner|self|hired
    'assigned_driver_id' => $_POST['driver_id'] ?? null,
    'pickup_date'        => $_POST['pickup_date'],
    'return_date'        => $_POST['return_date'],
    'pickup_location'    => $_POST['pickup_location'],
];

$check = resolveLicenseRequirement($booking, $db);

$status = $check['satisfied'] ? 'confirmed' : 'pending_verification';

$stmt = $db->prepare(
    'INSERT INTO bookings
     (vehicle_id, borrower_id, driver_arrangement, assigned_driver_id,
      pickup_date, return_date, pickup_location, total_price, status)
     VALUES (?,?,?,?,?,?,?,?,?)'
);
$stmt->execute([
    $booking['vehicle_id'], $booking['borrower_id'], $booking['driver_arrangement'],
    $booking['assigned_driver_id'], $booking['pickup_date'], $booking['return_date'],
    $booking['pickup_location'], calculatePrice($booking, $db), $status,
]);

echo json_encode([
    'booking_id' => $db->lastInsertId(),
    'status'     => $status,
    'license_status' => $check['license_status'],
    'message'    => $status === 'confirmed'
        ? 'Booking confirmed.'
        : 'Booking pending — the required driving license is not yet verified.',
]);
```

This single function is the enforcement point for the whole rule table in §3 of `PROJECT_CONTEXT.md` — no arrangement-specific branching duplicated elsewhere.

---

## 6. Secure Document Upload (`includes/upload.php`)

Used for: driving licenses, ID documents, vehicle registration/insurance.

```php
<?php
function handleSecureUpload(array $file, string $subfolder): string {
    $allowedMime = ['image/jpeg', 'image/png', 'application/pdf'];
    $maxBytes = 5 * 1024 * 1024; // 5MB

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload failed.');
    }
    if ($file['size'] > $maxBytes) {
        throw new RuntimeException('File too large.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!in_array($mime, $allowedMime, true)) {
        throw new RuntimeException('Unsupported file type.');
    }

    $ext = $mime === 'application/pdf' ? 'pdf' : ($mime === 'image/png' ? 'png' : 'jpg');
    $filename = bin2hex(random_bytes(16)) . '.' . $ext;

    $storageRoot = __DIR__ . '/../storage/' . $subfolder; // OUTSIDE web root
    if (!is_dir($storageRoot)) mkdir($storageRoot, 0750, true);

    $destination = $storageRoot . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Could not save file.');
    }

    return $subfolder . '/' . $filename; // store this relative path in DB
}
```

Serving a stored document back (only to authorized viewers — the owner of the doc or an Admin):

```php
// public/document.php?type=license&id=42
requireLogin();
$row = fetchLicenseById($_GET['id']); // includes user_id
$isOwnerOfDoc = $row['user_id'] === currentUser()['id'];
$isAdmin = !empty(currentUser()['is_admin']);
if (!$isOwnerOfDoc && !$isAdmin) { http_response_code(403); exit; }

$path = __DIR__ . '/../storage/' . $row['document_path'];
$mime = mime_content_type($path);
header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="document"');
readfile($path);
```

---

## 7. Admin Verification Queue (backend + frontend pattern)

**Backend list endpoint** (`/api/licenses/pending.php`):
```php
requireRole('admin');
$rows = getDb()->query(
    "SELECT dl.id, u.full_name, dl.license_number, dl.expiry_date, dl.document_path
     FROM driving_licenses dl JOIN users u ON u.id = dl.user_id
     WHERE dl.status = 'pending'"
)->fetchAll();
echo json_encode($rows);
```

**Backend approve/reject** (`/api/licenses/verify.php`):
```php
requireRole('admin');
if (!csrfCheck($_POST['csrf'] ?? '')) { http_response_code(419); exit; }

$id = (int) $_POST['license_id'];
$decision = $_POST['decision']; // 'verified' | 'rejected'
$reason = $_POST['reason'] ?? null;

$stmt = getDb()->prepare(
    'UPDATE driving_licenses SET status=?, reviewed_by=?, reviewed_at=NOW(), rejection_reason=? WHERE id=?'
);
$stmt->execute([$decision, currentUser()['id'], $reason, $id]);
echo json_encode(['ok' => true]);
```

**Frontend** (`admin/verification_queue.php` renders a table shell; `assets/js/admin-queue.js` fills it):
```javascript
// assets/js/admin-queue.js
async function loadQueue() {
  const res = await fetch('/api/licenses/pending.php');
  const rows = await res.json();
  const tbody = document.querySelector('#queue-body');
  tbody.innerHTML = rows.map(r => `
    <tr data-id="${r.id}">
      <td>${escapeHtml(r.full_name)}</td>
      <td>${escapeHtml(r.license_number)}</td>
      <td>${r.expiry_date}</td>
      <td><a href="/document.php?type=license&id=${r.id}" target="_blank">View</a></td>
      <td>
        <button class="btn-approve" data-id="${r.id}">Approve</button>
        <button class="btn-reject" data-id="${r.id}">Reject</button>
      </td>
    </tr>`).join('');
}

document.addEventListener('click', async (e) => {
  if (e.target.matches('.btn-approve, .btn-reject')) {
    const decision = e.target.matches('.btn-approve') ? 'verified' : 'rejected';
    const body = new URLSearchParams({
      license_id: e.target.dataset.id,
      decision,
      csrf: document.querySelector('meta[name="csrf"]').content,
    });
    await fetch('/api/licenses/verify.php', { method: 'POST', body });
    loadQueue(); // refresh
  }
});

function escapeHtml(str) {
  return str.replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

loadQueue();
```
Same pattern (list endpoint + action endpoint + small JS controller) is reused for: vehicle approvals, dispute resolution, driver-hire selection, fleet search filtering.

---

## 8. Frontend Guide

### 8.1 CSS architecture — split by responsibility, not one giant file

No build tool is allowed (no Sass, no PostCSS, no bundler), so the split has to work with **plain `<link>` tags** — see the `assets/css/` tree in §2. The rule of thumb: each file owns one concern, and every file after `tokens.css` consumes variables rather than hard-coding values.

**Load order matters** (each layer can rely on the one before it):
`tokens → reset → typography → layout → components → page-specific`

Rather than repeating 8 `<link>` tags on every PHP page, centralize them in one shared partial:

```php
<!-- includes/partials/head.php -->
<?php $extraCss = $extraCss ?? []; // page sets this before including the partial ?>
<link rel="stylesheet" href="/assets/css/base/tokens.css">
<link rel="stylesheet" href="/assets/css/base/reset.css">
<link rel="stylesheet" href="/assets/css/base/typography.css">
<link rel="stylesheet" href="/assets/css/layout/grid.css">
<link rel="stylesheet" href="/assets/css/layout/header-footer.css">
<link rel="stylesheet" href="/assets/css/components/buttons.css">
<link rel="stylesheet" href="/assets/css/components/forms.css">
<link rel="stylesheet" href="/assets/css/components/cards.css">
<link rel="stylesheet" href="/assets/css/components/badges.css">
<link rel="stylesheet" href="/assets/css/components/alerts.css">
<?php foreach ($extraCss as $file): ?>
  <link rel="stylesheet" href="/assets/css/pages/<?= htmlspecialchars($file) ?>.css">
<?php endforeach; ?>
```

Each page just sets `$extraCss` before including the partial:
```php
<?php
$extraCss = ['fleet-search']; // loads pages/fleet-search.css on top of the shared set
require __DIR__ . '/../includes/partials/head.php';
?>
```
This keeps every page's HTML `<head>` identical and DRY, while still letting a specific page (e.g. the admin dashboard) pull in only the extra CSS it actually needs — no page ships styles it doesn't use, and no single file becomes a dumping ground.

**`base/tokens.css`** — CSS variables translated from `DESIGN.md`, the only file allowed to contain raw hex/px values:
```css
:root {
  --color-surface: #f7f9fb;
  --color-on-surface: #191c1e;
  --color-primary: #0f172a;        /* navy */
  --color-on-primary: #ffffff;
  --color-secondary: #334155;      /* slate */
  --color-accent: #3b82f6;         /* electric blue CTA */
  --color-outline: #e2e8f0;
  --color-error: #ba1a1a;
  --color-success: #1a7f37;
  --radius-sm: 4px;
  --radius-md: 8px;
  --radius-lg: 12px;
  --radius-full: 9999px;
  --font-family: 'Inter', sans-serif;
  --space-sm: 8px;
  --space-md: 16px;
  --space-lg: 32px;
  --container-max: 1280px;
  --margin-desktop: 64px;
  --margin-mobile: 20px;
}
```

**`base/reset.css`** — minimal, not a full normalize:
```css
*, *::before, *::after { box-sizing: border-box; }
body, h1, h2, h3, p, figure { margin: 0; }
img, picture { max-width: 100%; display: block; }
button, input, select { font: inherit; }
a { color: inherit; text-decoration: none; }
```

**`base/typography.css`** — maps directly to the `typography` block in `DESIGN.md`:
```css
body { font-family: var(--font-family); color: var(--color-on-surface); }
.headline-xl { font-size: 48px; font-weight: 700; line-height: 56px; letter-spacing: -0.02em; }
.headline-lg { font-size: 32px; font-weight: 600; line-height: 40px; letter-spacing: -0.01em; }
.headline-md { font-size: 20px; font-weight: 600; line-height: 28px; }
.body-lg { font-size: 18px; line-height: 28px; }
.body-md { font-size: 16px; line-height: 24px; }
.label-md { font-size: 14px; font-weight: 500; line-height: 20px; letter-spacing: 0.05em; text-transform: uppercase; }
.label-sm { font-size: 12px; font-weight: 600; line-height: 16px; }

@media (max-width: 768px) {
  .headline-lg { font-size: 24px; line-height: 32px; }
}
```

**`layout/grid.css`** — container + spacing utilities only, no component styling:
```css
.container { max-width: var(--container-max); margin-inline: auto; padding-inline: var(--margin-mobile); }
@media (min-width: 1024px) { .container { padding-inline: var(--margin-desktop); } }
.grid { display: grid; gap: var(--space-lg); }
.grid-2 { grid-template-columns: repeat(2, 1fr); }
.grid-3 { grid-template-columns: repeat(3, 1fr); }
@media (max-width: 768px) { .grid-2, .grid-3 { grid-template-columns: 1fr; } }
.stack-sm > * + * { margin-top: var(--space-sm); }
.stack-md > * + * { margin-top: var(--space-md); }
.stack-lg > * + * { margin-top: var(--space-lg); }
```

**`layout/header-footer.css`** — the nav bar and footer seen on the landing page:
```css
.site-header { display: flex; align-items: center; justify-content: space-between; padding: var(--space-md) var(--margin-desktop); background: #fff; }
.site-nav a { margin-inline: var(--space-md); color: var(--color-secondary); }
.site-nav a.active { color: var(--color-primary); border-bottom: 2px solid var(--color-primary); }
.site-footer { background: var(--color-surface-container, #f2f4f6); padding: var(--space-lg) var(--margin-desktop); }
```

**`components/buttons.css`**:
```css
.btn { display: inline-flex; align-items: center; justify-content: center; padding: 12px 20px; border-radius: var(--radius-sm); font-weight: 600; cursor: pointer; border: 1px solid transparent; }
.btn-primary { background: var(--color-accent); color: #fff; }
.btn-primary:hover { filter: brightness(0.95); }
.btn-secondary { background: transparent; border-color: var(--color-primary); color: var(--color-primary); }
.btn-ghost { background: transparent; color: var(--color-secondary); }
.btn:disabled { opacity: 0.5; cursor: not-allowed; }
```

**`components/forms.css`** — inputs, the hero search form, file upload dropzones:
```css
.input, select.input { width: 100%; padding: 10px 12px; border: 1px solid var(--color-outline); border-radius: var(--radius-sm); background: #fff; }
.input:focus { outline: 2px solid var(--color-accent); outline-offset: 1px; }
.form-group { margin-bottom: var(--space-md); }
.form-label { display: block; font-size: 14px; color: var(--color-secondary); margin-bottom: 4px; }
.search-panel { background: #fff; border-radius: var(--radius-md); padding: var(--space-md); display: flex; gap: var(--space-md); box-shadow: 0 4px 20px rgba(15,23,42,0.05); }
.file-drop { border: 1px dashed var(--color-outline); border-radius: var(--radius-md); padding: var(--space-md); text-align: center; }
```

**`components/cards.css`** — vehicle listing cards + generic dashboard cards:
```css
.card { background: #fff; border: 1px solid var(--color-outline); border-radius: var(--radius-lg); overflow: hidden; }
.card:hover { box-shadow: 0 4px 20px rgba(15,23,42,0.05); }
.card-media { aspect-ratio: 4/3; background: var(--color-surface); }
.card-body { padding: var(--space-md); }
.card-price { font-size: 20px; font-weight: 600; color: var(--color-primary); }
.card-specs { display: flex; gap: var(--space-md); color: var(--color-secondary); font-size: 12px; }
```

**`components/badges.css`** — the 4 fleet-category tags + status pills:
```css
.badge { display: inline-block; padding: 4px 10px; border-radius: var(--radius-full); font-size: 12px; font-weight: 600; }
.badge-premium { background: #e0e7ff; color: #1e3a8a; }
.badge-luxury  { background: #001a42; color: #fff; }
.badge-budget  { background: #dcfce7; color: #14532d; }
.badge-offroad { background: #fef3c7; color: #78350f; }
.badge-status-verified  { background: #dcfce7; color: #14532d; }
.badge-status-pending   { background: #fef9c3; color: #713f12; }
.badge-status-rejected  { background: #fee2e2; color: #7f1d1d; }
```

**`components/tables.css`** — admin verification/approval queues:
```css
.table { width: 100%; border-collapse: collapse; }
.table th { text-align: left; font-size: 12px; text-transform: uppercase; color: var(--color-secondary); padding: 8px 12px; border-bottom: 1px solid var(--color-outline); }
.table td { padding: 12px; border-bottom: 1px solid #f1f5f9; }
.table tr:hover td { background: var(--color-surface); }
```

**`components/alerts.css`** — flash messages + trust badges ("24/7 Support" style icons):
```css
.alert { padding: var(--space-sm) var(--space-md); border-radius: var(--radius-sm); margin-bottom: var(--space-md); }
.alert-success { background: #dcfce7; color: #14532d; }
.alert-error   { background: #fee2e2; color: #7f1d1d; }
.trust-badge { display: flex; align-items: center; gap: var(--space-sm); }
```

**`pages/*.css`** — only overrides/layout truly specific to one screen (e.g. `pages/landing.css` holds just the hero background-image treatment and the overlapping search panel positioning; `pages/dashboard.css` holds the sidebar layout for owner/driver/admin shells). Nothing generic belongs here — if two pages need it, promote it to `components/`.

### 8.2 Page-to-flow mapping
| Screen (PHP file) | Flow reference |
|---|---|
| `public/index.php` | existing landing page |
| `public/register.php` | Owner/Borrower/Driver signup — role checkboxes |
| `public/fleet/search.php` | §2.2 Borrower search/filter |
| `public/fleet/detail.php` | §2.3 driver-arrangement selector (JS toggles which sub-form shows) |
| `public/owner/vehicle_form.php` | §1.2 vehicle listing, category picker |
| `public/admin/verification_queue.php` | §4.1 Admin verification |
| `public/admin/vehicle_approvals.php` | §4.2 |
| `public/driver/assignments.php` | §3.2–3.3 |

### 8.3 Driver-arrangement selector (client-side UX for §2.3)
```html
<fieldset>
  <legend>Who will drive?</legend>
  <label><input type="radio" name="driver_arrangement" value="self" checked> I'll drive myself</label>
  <label><input type="radio" name="driver_arrangement" value="owner"> Owner drives (chauffeur)</label>
  <label><input type="radio" name="driver_arrangement" value="hired"> Assign a hired driver</label>
</fieldset>

<div id="license-upload-block" style="display:none;">
  <p>A valid driving license is required for this option.</p>
  <input type="file" name="license_doc" accept="image/*,application/pdf">
</div>

<div id="hired-driver-block" style="display:none;">
  <select name="driver_id" id="driver-select"></select>
</div>
```
```javascript
// assets/js/booking-form.js
const radios = document.querySelectorAll('[name="driver_arrangement"]');
radios.forEach(r => r.addEventListener('change', onArrangementChange));

function onArrangementChange(e) {
  const val = e.target.value;
  document.querySelector('#license-upload-block').style.display =
    (val === 'self' || val === 'owner') ? 'block' : 'none';
  document.querySelector('#hired-driver-block').style.display =
    (val === 'hired') ? 'block' : 'none';
  if (val === 'hired') loadAvailableDrivers();
}

async function loadAvailableDrivers() {
  const vehicleId = document.querySelector('[name="vehicle_id"]').value;
  const res = await fetch(`/api/drivers/available.php?vehicle_id=${vehicleId}`);
  const drivers = await res.json();
  const select = document.querySelector('#driver-select');
  select.innerHTML = drivers.map(d => `<option value="${d.id}">${d.full_name} ★${d.rating}</option>`).join('');
}
```
Server still re-validates the license requirement regardless of what the client sends — the JS above is UX convenience only, **never** the source of truth (see §5).

---

## 9. Security Checklist (mandatory before launch)

- [ ] All SQL via prepared statements (PDO), never string interpolation.
- [ ] All output to HTML escaped with `htmlspecialchars()` (server) / the `escapeHtml()` helper (client) to prevent XSS.
- [ ] CSRF token on every state-changing form/request.
- [ ] Passwords hashed with `password_hash()` (bcrypt), never stored plain or MD5/SHA1.
- [ ] `storage/` (licenses, IDs, insurance docs) outside web root or blocked via `.htaccess`/`Nginx deny`.
- [ ] File uploads validated by real MIME sniffing (`finfo`), not just file extension.
- [ ] Session cookies: `HttpOnly`, `SameSite=Lax`, `Secure` once on HTTPS; `session_regenerate_id()` on login.
- [ ] Role checks (`requireRole()`) on every admin/owner/driver-only PHP file and API endpoint — never rely on hidden UI alone.
- [ ] Rate-limit login attempts (simple DB counter or session-based lockout) to slow brute force.
- [ ] Validate dates/prices/IDs server-side even though HTML5 `required`/`type="date"` helps client-side.
- [ ] License/insurance expiry dates checked against `NOW()` at booking time, not just at initial upload.

---

## 10. Phased Build Plan

1. **Phase 1 — Foundation**: run `database_schema.sql`; build `db.php`, `auth.php`, `functions.php`; registration/login/logout; CSS tokens file.
2. **Phase 2 — Owner & Fleet**: vehicle listing form + photo upload; admin vehicle approval queue; public fleet search/detail pages (extend existing landing page grid).
3. **Phase 3 — Licenses & Booking Core**: license upload UI; `resolveLicenseRequirement()`; booking creation API; driver-arrangement selector; admin license verification queue.
4. **Phase 4 — Driver Marketplace**: driver signup/onboarding; availability table; `/api/drivers/available.php`; driver assignment acceptance flow.
5. **Phase 5 — Money & Trust**: payments (can start as manual/mock "mark as paid" for MVP), payouts, reviews, disputes queue.
6. **Phase 6 — Hardening & Polish**: run through the §9 security checklist, add pagination to admin queues, add expiry-reminder cron (simple PHP script run via server cron hitting `licenses/expiry_check.php`), responsive CSS pass against DESIGN.md's mobile breakpoints.

---

## 11. Suggested `config/config.php` shape
```php
<?php
return [
    'db_host' => 'localhost',
    'db_name' => 'elitedrive',
    'db_user' => 'elitedrive_user',
    'db_pass' => 'change_me',
    'base_url' => 'https://yourdomain.example',
    'commission_pct' => 15.00,
];
```
Keep this file outside version control (`.gitignore`) and, ideally, outside the web-servable directory alongside `storage/`.
