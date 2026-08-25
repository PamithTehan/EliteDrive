<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/functions.php';

$extraCss = ['landing'];
require __DIR__ . '/../includes/partials/head.php';
?>

<style>
/* Temporary inline styles for landing page specific elements - could be moved to assets/css/pages/landing.css */
.hero-section {
    background: linear-gradient(135deg, var(--color-surface), #f8fafc);
    padding: 120px 0;
    text-align: center;
    border-bottom: 1px solid var(--color-outline);
}
.hero-title {
    font-size: 3.5rem;
    font-weight: 800;
    line-height: 1.2;
    margin-bottom: var(--space-md);
    color: var(--color-primary);
}
.hero-subtitle {
    font-size: 1.25rem;
    color: var(--color-secondary);
    max-width: 600px;
    margin: 0 auto var(--space-lg);
}
.features-section {
    padding: 80px 0;
}
.feature-card {
    text-align: center;
    padding: var(--space-lg);
}
.feature-icon {
    font-size: 3rem;
    margin-bottom: var(--space-md);
    color: var(--color-accent);
}
</style>

<div class="hero-section">
    <div class="container">
        <h1 class="hero-title">The Premium Vehicle<br>Rental Experience</h1>
        <p class="hero-subtitle">Discover and book the perfect car for your next journey, or list your own vehicle and start earning today.</p>
        <div style="display: flex; gap: var(--space-md); justify-content: center;">
            <a href="<?= baseUrl('/fleet/search.php') ?>" class="btn btn-primary" style="font-size: 1.1rem; padding: 12px 24px;">Browse Fleet</a>
            <?php if (!currentUser()): ?>
                <a href="<?= baseUrl('/register.php') ?>" class="btn btn-ghost" style="font-size: 1.1rem; padding: 12px 24px; border: 1px solid var(--color-outline);">List Your Car</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="features-section container">
    <div class="grid grid-3">
        <div class="feature-card">
            <div class="feature-icon">🚘</div>
            <h3 class="headline-md" style="margin-bottom: var(--space-sm);">Premium Fleet</h3>
            <p class="body-md" style="color:var(--color-secondary);">Access an exclusive selection of luxury, premium, and capable off-road vehicles.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">👔</div>
            <h3 class="headline-md" style="margin-bottom: var(--space-sm);">Flexible Drivers</h3>
            <p class="body-md" style="color:var(--color-secondary);">Drive it yourself, hire a professional chauffeur, or have the owner drive you.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">🔒</div>
            <h3 class="headline-md" style="margin-bottom: var(--space-sm);">Trusted & Secure</h3>
            <p class="body-md" style="color:var(--color-secondary);">Verified identities, seamless digital payments, and strict administrative oversight.</p>
        </div>
    </div>
</div>

<div style="background: var(--color-primary); color: white; padding: 80px 0; text-align: center;">
    <div class="container">
        <h2 class="headline-lg" style="color: white; margin-bottom: var(--space-md);">Ready to hit the road?</h2>
        <a href="<?= baseUrl('/fleet/search.php') ?>" class="btn" style="background: white; color: var(--color-primary); padding: 12px 32px; font-weight: bold; font-size: 1.1rem;">Find a Vehicle</a>
    </div>
</div>

<?php require __DIR__ . '/../includes/partials/footer.php'; ?>
