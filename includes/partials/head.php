<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EliteDrive</title>
    <?php $extraCss = $extraCss ?? []; ?>
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
        <div class="logo"><a href="<?= baseUrl('/') ?>" class="headline-md" style="color:var(--color-primary)">EliteDrive</a></div>
        <nav class="site-nav">
            <a href="<?= baseUrl('/fleet/search.php') ?>">Fleet</a>
            <?php if (currentUser()): ?>
                <?php if (currentUser()['is_admin']): ?>
                    <a href="<?= baseUrl('/admin/vehicle_approvals.php') ?>">Admin</a>
                <?php endif; ?>
                <?php if (currentUser()['is_owner']): ?>
                    <a href="<?= baseUrl('/owner/dashboard.php') ?>">Owner</a>
                <?php endif; ?>
                <?php if (currentUser()['is_driver']): ?>
                    <a href="<?= baseUrl('/driver/dashboard.php') ?>">Driver</a>
                <?php endif; ?>
                <a href="<?= baseUrl('/borrower/my_bookings.php') ?>">My Bookings</a>
                <a href="<?= baseUrl('/logout.php') ?>">Logout</a>
            <?php else: ?>
                <a href="<?= baseUrl('/login.php') ?>">Log In</a>
                <a href="<?= baseUrl('/register.php') ?>" class="btn btn-primary">Sign Up</a>
            <?php endif; ?>
        </nav>
    </header>
    <main>
