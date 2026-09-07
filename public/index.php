<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Fetch top 2 premium vehicles for the "Our Selection" grid
$db = getDb();
$stmt = $db->query("
    SELECT v.id, v.make, v.model, v.category, v.daily_rate, v.transmission, v.mileage, v.km_rate, v.yom, v.yor, p.photo_path 
    FROM vehicles v 
    LEFT JOIN vehicle_photos p ON v.id = p.vehicle_id AND p.is_primary = 1 
    WHERE v.status = 'approved' 
    ORDER BY v.created_at DESC 
    LIMIT 3
");
$featuredVehicles = $stmt->fetchAll();

$extraCss = ['home', 'fleet-search'];
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
                                <option value="Luxury">Luxury</option>
                                <option value="Premium">Premium</option>
                                <option value="Off-Road">Off-Road</option>
                                <option value="Electric">Electric</option>
                                <option value="Budget">Budget</option>
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
            <h2>Our Fleet</h2>
        </div>
        <a href="<?= baseUrl('/fleet/search.php') ?>" class="view-all-link">
            View All Vehicles <span class="material-symbols-outlined">chevron_right</span>
        </a>
    </div>

    <div class="grid grid-3">
        <?php foreach ($featuredVehicles as $v): ?>
            <div class="fleet-card">
                <div class="fleet-card-image">
                    <?php 
                        $cats = explode(',', $v['category']);
                        foreach($cats as $cat): 
                            if(trim($cat)):
                    ?>
                        <div class="fleet-badge"><?= escapeHtml(trim($cat)) ?></div>
                    <?php 
                            endif;
                        endforeach; 
                    ?>
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
                            <div class="amount">LKR <?= escapeHtml($v['daily_rate']) ?></div>
                            <div class="period">/day</div>
                        </div>
                    </div>
                    <div class="fleet-specs">
                        <?php 
                            $isElectric = $v['category'] === 'Electric';
                            $rateUnit = $isElectric ? 'km/charge' : 'km/l';
                            $rateIcon = $isElectric ? 'electric_car' : 'local_gas_station';
                            $transIcon = $v['transmission'] === 'Manual' ? 'account_tree' : 'settings';
                        ?>
                        <div class="spec-item"><span class="material-symbols-outlined" style="font-size:16px;"><?= $rateIcon ?></span> <?= escapeHtml($v['km_rate']) ?> <?= $rateUnit ?></div>
                        <div class="spec-item"><span class="material-symbols-outlined" style="font-size:16px;">speed</span> <?= escapeHtml($v['mileage']) ?> km</div>
                        <div class="spec-item"><span class="material-symbols-outlined" style="font-size:16px;"><?= $transIcon ?></span> <?= escapeHtml($v['transmission']) ?></div>
                    </div>
                    <div class="fleet-actions">
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
        <div class="coming-soon-badge">
            Coming Soon
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/partials/footer.php'; ?>
