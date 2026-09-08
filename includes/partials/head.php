<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EliteDrive</title>
    <link rel="icon" type="image/svg+xml" href="<?= baseUrl('/assets/images/favicon.jpg') ?>">
    <?php $extraCss = $extraCss ?? []; ?>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link rel="stylesheet" href="<?= baseUrl('/assets/css/base/tokens.css') ?>">
    <link rel="stylesheet" href="<?= baseUrl('/assets/css/base/reset.css') ?>">
    <link rel="stylesheet" href="<?= baseUrl('/assets/css/base/typography.css') ?>">
    <link rel="stylesheet" href="<?= baseUrl('/assets/css/layout/grid.css') ?>">
    <link rel="stylesheet" href="<?= baseUrl('/assets/css/layout/header-footer.css') ?>">
    <link rel="stylesheet" href="<?= baseUrl('/assets/css/components/buttons.css') ?>">
    <link rel="stylesheet" href="<?= baseUrl('/assets/css/components/forms.css') ?>">
    <link rel="stylesheet" href="<?= baseUrl('/assets/css/components/cards.css') ?>">
    <link rel="stylesheet" href="<?= baseUrl('/assets/css/components/badges.css') ?>">
    <link rel="stylesheet" href="<?= baseUrl('/assets/css/components/tables.css') ?>">
    <link rel="stylesheet" href="<?= baseUrl('/assets/css/components/alerts.css') ?>">
    <?php foreach ($extraCss as $file): ?>
        <link rel="stylesheet" href="<?= baseUrl('/assets/css/pages/' . htmlspecialchars($file) . '.css') ?>">
    <?php endforeach; ?>
</head>
<body>
    <header class="site-header">
        <div class="container site-header-inner">
            <div class="logo">
                <a href="<?= baseUrl('/') ?>" class="headline-md" style="color:var(--color-text); text-decoration:none;">EliteDrive</a>
            </div>
            
            <nav class="site-nav" style="display:flex; gap: 32px; align-items: center;">
                <a href="<?= baseUrl('/') ?>" style="color:var(--color-secondary); text-decoration:none; font-weight:500; display:flex; align-items:center; gap:4px;"><span class="material-symbols-outlined" style="font-size:20px;">home</span> Home</a>
                <a href="<?= baseUrl('/fleet/search.php') ?>" style="color:var(--color-secondary); text-decoration:none; font-weight:500; display:flex; align-items:center; gap:4px;"><span class="material-symbols-outlined" style="font-size:20px;">directions_car</span> Fleet</a>
                <a href="<?= baseUrl('/about.php') ?>" style="color:var(--color-secondary); text-decoration:none; font-weight:500; display:flex; align-items:center; gap:4px;"><span class="material-symbols-outlined" style="font-size:20px;">info</span> About</a>
                <a href="<?= baseUrl('/contact.php') ?>" style="color:var(--color-secondary); text-decoration:none; font-weight:500; display:flex; align-items:center; gap:4px;"><span class="material-symbols-outlined" style="font-size:20px;">mail</span> Contact Us</a>
                <a href="<?= baseUrl('/faqs.php') ?>" style="color:var(--color-secondary); text-decoration:none; font-weight:500; display:flex; align-items:center; gap:4px;"><span class="material-symbols-outlined" style="font-size:20px;">help</span> FAQs</a>
            </nav>
            
            <div class="nav-actions" style="display:flex; align-items:center; gap: 16px;">
                <?php if ($user = currentUser()): ?>
                    <span class="body-md" style="font-weight: 500;"><?= escapeHtml($user['full_name']) ?></span>
                    
                    <div class="profile-dropdown-container" style="position:relative;">
                        <button class="profile-btn" style="width:38px; height:38px; border-radius:50%; background: #0f172a; border:none; color: white; display:flex; align-items:center; justify-content:center; cursor:pointer; font-weight:600; font-size:14px; box-shadow:0 2px 4px rgba(0,0,0,0.1);" onclick="document.getElementById('profileDropdown').classList.toggle('show')" title="Account Menu">
                            <?php 
                                $uInitials = '';
                                $uParts = explode(' ', trim($user['full_name'] ?? ''));
                                foreach ($uParts as $up) {
                                    if (!empty($up)) $uInitials .= strtoupper($up[0]);
                                    if (strlen($uInitials) >= 2) break;
                                }
                                echo $uInitials ?: '<span class="material-symbols-outlined" style="font-size:20px;">person</span>';
                            ?>
                        </button>
                        <div id="profileDropdown" class="profile-dropdown" style="display:none; position:absolute; right:0; top: 100%; margin-top:8px; background:white; border: 1px solid var(--color-outline); border-radius:var(--radius-md); box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1); width: 230px; z-index: 100; overflow:hidden;">
                            <div style="padding: 12px 16px; background: #f8fafc; border-bottom: 1px solid var(--color-outline);">
                                <div style="font-weight: 600; font-size: 13px; color: var(--color-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= escapeHtml($user['full_name']) ?></div>
                                <div style="font-size: 11px; color: var(--color-secondary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= escapeHtml($user['email']) ?></div>
                            </div>
                            <ul style="list-style:none; margin:0; padding:4px 0;">
                                <li><a href="<?= baseUrl('/profile.php') ?>" style="display:flex; align-items:center; gap:8px; padding:10px 16px; color:var(--color-text); text-decoration:none; font-size:13px; font-weight:500;"><span class="material-symbols-outlined" style="font-size:18px; color:var(--color-secondary);">account_circle</span> Account Settings</a></li>
                                <?php if (!empty($user['is_admin'])): ?>
                                    <li><a href="<?= baseUrl('/admin/vehicle_approvals.php') ?>" style="display:flex; align-items:center; gap:8px; padding:10px 16px; color:var(--color-text); text-decoration:none; font-size:13px; font-weight:500;"><span class="material-symbols-outlined" style="font-size:18px; color:var(--color-secondary);">admin_panel_settings</span> Admin Dashboard</a></li>
                                <?php endif; ?>
                                <?php if (!empty($user['is_owner'])): ?>
                                    <li><a href="<?= baseUrl('/owner/dashboard.php') ?>" style="display:flex; align-items:center; gap:8px; padding:10px 16px; color:var(--color-text); text-decoration:none; font-size:13px; font-weight:500;"><span class="material-symbols-outlined" style="font-size:18px; color:var(--color-secondary);">storefront</span> Owner Dashboard</a></li>
                                <?php endif; ?>
                                <?php if (!empty($user['is_driver'])): ?>
                                    <li><a href="<?= baseUrl('/driver/dashboard.php') ?>" style="display:flex; align-items:center; gap:8px; padding:10px 16px; color:var(--color-text); text-decoration:none; font-size:13px; font-weight:500;"><span class="material-symbols-outlined" style="font-size:18px; color:var(--color-secondary);">drive_eta</span> Driver Dashboard</a></li>
                                <?php endif; ?>
                                <?php if (!empty($user['is_borrower'])): ?>
                                    <li><a href="<?= baseUrl('/borrower/my_bookings.php') ?>" style="display:flex; align-items:center; gap:8px; padding:10px 16px; color:var(--color-text); text-decoration:none; font-size:13px; font-weight:500;"><span class="material-symbols-outlined" style="font-size:18px; color:var(--color-secondary);">event_note</span> My Bookings</a></li>
                                <?php endif; ?>
                                <li><hr style="border:0; border-top:1px solid var(--color-outline); margin: 4px 0;"></li>
                                <li><a href="<?= baseUrl('/logout.php') ?>" style="display:flex; align-items:center; gap:8px; padding:10px 16px; color:#b91c1c; text-decoration:none; font-size:13px; font-weight:500;"><span class="material-symbols-outlined" style="font-size:18px;">logout</span> Sign Out</a></li>
                            </ul>
                        </div>
                    </div>
                    
                    <a href="<?= baseUrl('/fleet/search.php') ?>" class="btn" style="background-color: black; color: white; border: none; padding: 10px 20px; border-radius: 8px; text-decoration:none; font-weight:500;">Book Now</a>
                <?php else: ?>
                    <a href="<?= baseUrl('/login.php') ?>" style="color:var(--color-text); text-decoration:none; font-weight:500; display:flex; align-items:center; gap:4px;"><span class="material-symbols-outlined" style="font-size:20px;">login</span> Log In</a>
                    <a href="<?= baseUrl('/register.php') ?>" class="btn btn-primary" style="padding: 10px 20px; border-radius: 8px; text-decoration:none; font-weight:500; display:flex; align-items:center; gap:4px;"><span class="material-symbols-outlined" style="font-size:20px;">person_add</span> Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <style>
    .profile-dropdown.show { display: block !important; }
    .profile-dropdown a:hover { background-color: var(--color-surface); }
    .site-nav a:hover { color: var(--color-primary) !important; }
    </style>
    <script>
    document.addEventListener('click', function(event) {
      if (!event.target.closest('.profile-dropdown-container')) {
        var dropdowns = document.getElementsByClassName("profile-dropdown");
        for (var i = 0; i < dropdowns.length; i++) {
          var openDropdown = dropdowns[i];
          if (openDropdown.classList.contains('show')) {
            openDropdown.classList.remove('show');
          }
        }
      }
    });
    </script>
    <main>
