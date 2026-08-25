<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EliteDrive</title>
    <?php $extraCss = $extraCss ?? []; ?>
    <link rel="stylesheet" href="/assets/css/base/tokens.css">
    <link rel="stylesheet" href="/assets/css/base/reset.css">
    <link rel="stylesheet" href="/assets/css/base/typography.css">
    <link rel="stylesheet" href="/assets/css/layout/grid.css">
    <link rel="stylesheet" href="/assets/css/layout/header-footer.css">
    <link rel="stylesheet" href="/assets/css/components/buttons.css">
    <link rel="stylesheet" href="/assets/css/components/forms.css">
    <link rel="stylesheet" href="/assets/css/components/cards.css">
    <link rel="stylesheet" href="/assets/css/components/badges.css">
    <link rel="stylesheet" href="/assets/css/components/tables.css">
    <link rel="stylesheet" href="/assets/css/components/alerts.css">
    <?php foreach ($extraCss as $file): ?>
        <link rel="stylesheet" href="/assets/css/pages/<?= htmlspecialchars($file) ?>.css">
    <?php endforeach; ?>
</head>
<body>
    <header class="site-header">
        <div class="logo"><a href="/" class="headline-md" style="color:var(--color-primary)">EliteDrive</a></div>
        <nav class="site-nav">
            <a href="/fleet/search.php">Fleet</a>
            <?php if (currentUser()): ?>
                <?php if (currentUser()['is_admin']): ?>
                    <a href="/admin/vehicle_approvals.php">Admin</a>
                <?php endif; ?>
                <?php if (currentUser()['is_owner']): ?>
                    <a href="/owner/dashboard.php">Owner</a>
                <?php endif; ?>
                <?php if (currentUser()['is_driver']): ?>
                    <a href="/driver/dashboard.php">Driver</a>
                <?php endif; ?>
                <a href="/borrower/my_bookings.php">My Bookings</a>
                <a href="/logout.php">Logout</a>
            <?php else: ?>
                <a href="/login.php">Log In</a>
                <a href="/register.php" class="btn btn-primary">Sign Up</a>
            <?php endif; ?>
        </nav>
    </header>
    <main>
