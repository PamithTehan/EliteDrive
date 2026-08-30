<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Fetch top 2 premium vehicles for the "Our Selection" grid
$db = getDb();
$stmt = $db->query("
    SELECT v.id, v.make, v.model, v.category, v.daily_rate, p.photo_path 
    FROM vehicles v 
    LEFT JOIN vehicle_photos p ON v.id = p.vehicle_id AND p.is_primary = 1 
    WHERE v.status = 'approved' 
    ORDER BY v.created_at DESC 
    LIMIT 2
");
$featuredVehicles = $stmt->fetchAll();

$extraCss = ['home'];
require_once __DIR__ . '/../includes/partials/head.php';
?>

<div class="home-hero">
    <div class="container">
        <h1>Find Your Perfect Drive</h1>
        <p>Experience the ultimate in performance and luxury. From electric efficiency to high-octane excitement, discover a fleet tailored for the modern explorer.</p>
        
        <div class="search-widget">
            <form action="<?= baseUrl('/fleet/search.php') ?>" method="GET" style="margin:0;">
                <div class="search-widget-row">
                    <div class="search-field">
                        <label>Pick-up Location</label>
                        <div class="search-input-wrapper">
                            <span class="material-symbols-outlined">location_on</span>
                            <input type="text" placeholder="City or Airport">
                        </div>
                    </div>
                    <div class="search-field">
                        <label>Pick-up Date</label>
                        <div class="search-input-wrapper">
                            <span class="material-symbols-outlined">calendar_today</span>
                            <input type="date">
                        </div>
                    </div>
                    <div class="search-field">
                        <label>Return Date</label>
                        <div class="search-input-wrapper">
                            <span class="material-symbols-outlined">calendar_today</span>
                            <input type="date">
                        </div>
                    </div>
                    <div class="search-field">
                        <label>Car Class</label>
                        <div class="search-input-wrapper">
                            <span class="material-symbols-outlined">directions_car</span>
                            <select name="category">
                                <option value="">All Classes</option>
                                <option value="Premium">Premium</option>
                                <option value="Luxury">Luxury</option>
                                <option value="Electric">Electric</option>
                            </select>
                        </div>
                    </div>
                </div>
                <button type="submit" class="search-btn" style="margin-top: 16px;">
                    Search Fleet <span class="material-symbols-outlined">arrow_forward</span>
                </button>
            </form>
        </div>
    </div>
    <div class="hero-footer-text">EXPLORE LUXURY RENTALS</div>
</div>

<div class="features-banner">
    <div class="container grid grid-3">
        <div class="feature-item">
            <div class="feature-icon-circle">
                <span class="material-symbols-outlined">public</span>
            </div>
            <div class="feature-text">
                <h4>Global Reach</h4>
                <p>Over 500+ locations worldwide.</p>
            </div>
        </div>
        <div class="feature-item">
            <div class="feature-icon-circle">
                <span class="material-symbols-outlined">support_agent</span>
            </div>
            <div class="feature-text">
                <h4>24/7 Support</h4>
                <p>Dedicated assistance whenever you need it.</p>
            </div>
        </div>
        <div class="feature-item">
            <div class="feature-icon-circle">
                <span class="material-symbols-outlined">verified_user</span>
            </div>
            <div class="feature-text">
                <h4>Premium Insurance</h4>
                <p>Comprehensive coverage for peace of mind.</p>
            </div>
        </div>
    </div>
</div>

<div class="fleet-section container">
    <div class="fleet-header">
        <div>
            <div class="section-label">Our Selection</div>
            <h2>Our Premium Fleet</h2>
        </div>
        <a href="<?= baseUrl('/fleet/search.php') ?>" class="view-all-link">
            View All Vehicles <span class="material-symbols-outlined">chevron_right</span>
        </a>
    </div>

    <div class="grid grid-2">
        <?php foreach ($featuredVehicles as $v): ?>
            <div class="fleet-card">
                <div class="fleet-card-image">
                    <div class="fleet-badge"><?= escapeHtml($v['category']) ?></div>
                    <?php if (!empty($v['photo_path'])): ?>
                        <?php $imgUrl = strpos($v['photo_path'], 'http') === 0 ? $v['photo_path'] : baseUrl($v['photo_path']); ?>
                        <img src="<?= escapeHtml($imgUrl) ?>" alt="Vehicle">
                    <?php else: ?>
                        <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; color:#ccc;">[No Image]</div>
                    <?php endif; ?>
                </div>
                <div class="fleet-card-body">
                    <div class="fleet-title-row">
                        <h3><?= escapeHtml($v['make'] . ' ' . $v['model']) ?></h3>
                        <div class="fleet-price">
                            <div class="amount">$<?= escapeHtml($v['daily_rate']) ?></div>
                            <div class="period">/day</div>
                        </div>
                    </div>
                    <div class="fleet-specs">
                        <div class="spec-item"><span class="material-symbols-outlined" style="font-size:16px;">local_gas_station</span> <?= escapeHtml($v['category']) ?></div>
                        <div class="spec-item"><span class="material-symbols-outlined" style="font-size:16px;">group</span> <?= escapeHtml($v['seating_capacity'] ?? '5') ?> Seats</div>
                        <div class="spec-item"><span class="material-symbols-outlined" style="font-size:16px;">settings</span> <?= escapeHtml($v['transmission'] ?? 'Auto') ?></div>
                    </div>
                    <div class="fleet-actions">
                        <a href="<?= baseUrl('/fleet/detail.php?id=' . $v['id']) ?>" class="btn-outline">Details</a>
                        <a href="<?= baseUrl('/fleet/detail.php?id=' . $v['id']) ?>" class="btn-dark">Reserve Now</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="newsletter-section">
    <!-- Faint background graphic representation (optional overlay) -->
    <div style="position:absolute; right:-5%; top:-20%; font-size:400px; color:rgba(255,255,255,0.02); line-height:1; pointer-events:none;">
        <span class="material-symbols-outlined" style="font-size:inherit;">directions_car</span>
    </div>
    
    <div class="container newsletter-content">
        <div class="newsletter-text">
            <h2>Join the Elite Drive Club</h2>
            <p>Get exclusive access to our newest luxury additions and special membership rates for corporate accounts.</p>
        </div>
        <div class="newsletter-form">
            <input type="email" placeholder="Enter your email">
            <button type="button" onclick="alert('Subscribed!')">Subscribe Now</button>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/partials/footer.php'; ?>
