<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EliteDrive</title>
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
    <header class="site-header" style="display:flex; justify-content:space-between; align-items:center; padding: 16px 32px; border-bottom: 1px solid var(--color-outline); background: white;">
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
                    <button class="profile-btn" style="width:36px; height:36px; border-radius:50%; background: #dbeafe; border:none; color: #1e40af; display:flex; align-items:center; justify-content:center; cursor:pointer;" onclick="document.getElementById('profileDropdown').classList.toggle('show')">
                        <span class="material-symbols-outlined">person</span>
                    </button>
                    <div id="profileDropdown" class="profile-dropdown" style="display:none; position:absolute; right:0; top: 100%; margin-top:8px; background:white; border: 1px solid var(--color-outline); border-radius:var(--radius-sm); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); width: 200px; z-index: 100;">
                            <?php if (!empty($user['is_admin'])): ?>
                                <li><a href="<?= baseUrl('/admin/vehicle_approvals.php') ?>" style="display:flex; align-items:center; gap:8px; padding:8px 16px; color:var(--color-text); text-decoration:none;"><span class="material-symbols-outlined" style="font-size:18px;">admin_panel_settings</span> Admin Dashboard</a></li>
                            <?php endif; ?>
                            <?php if (!empty($user['is_owner'])): ?>
                                <li><a href="<?= baseUrl('/owner/dashboard.php') ?>" style="display:flex; align-items:center; gap:8px; padding:8px 16px; color:var(--color-text); text-decoration:none;"><span class="material-symbols-outlined" style="font-size:18px;">storefront</span> Owner Dashboard</a></li>
                            <?php endif; ?>
                            <?php if (!empty($user['is_driver'])): ?>
                                <li><a href="<?= baseUrl('/driver/dashboard.php') ?>" style="display:flex; align-items:center; gap:8px; padding:8px 16px; color:var(--color-text); text-decoration:none;"><span class="material-symbols-outlined" style="font-size:18px;">drive_eta</span> Driver Dashboard</a></li>
                            <?php endif; ?>
                            <li><a href="<?= baseUrl('/borrower/my_bookings.php') ?>" style="display:flex; align-items:center; gap:8px; padding:8px 16px; color:var(--color-text); text-decoration:none;"><span class="material-symbols-outlined" style="font-size:18px;">event_note</span> My Bookings</a></li>
                            <li><hr style="border:0; border-top:1px solid var(--color-outline); margin: 4px 0;"></li>
                            <li><a href="<?= baseUrl('/logout.php') ?>" style="display:flex; align-items:center; gap:8px; padding:8px 16px; color:var(--color-text); text-decoration:none;"><span class="material-symbols-outlined" style="font-size:18px;">logout</span> Sign Out</a></li>
                        </ul>
                    </div>
                </div>
                
                <a href="<?= baseUrl('/fleet/search.php') ?>" class="btn" style="background-color: black; color: white; border: none; padding: 10px 20px; border-radius: 8px; text-decoration:none; font-weight:500;">Book Now</a>
            <?php else: ?>
                <a href="<?= baseUrl('/login.php') ?>" style="color:var(--color-text); text-decoration:none; font-weight:500; display:flex; align-items:center; gap:4px;"><span class="material-symbols-outlined" style="font-size:20px;">login</span> Log In</a>
                <a href="<?= baseUrl('/register.php') ?>" class="btn btn-primary" style="padding: 10px 20px; border-radius: 8px; text-decoration:none; font-weight:500; display:flex; align-items:center; gap:4px;"><span class="material-symbols-outlined" style="font-size:20px;">person_add</span> Sign Up</a>
            <?php endif; ?>
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
