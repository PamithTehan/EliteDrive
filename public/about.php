<?php
/**
 * File: about.php
 * Purpose: "About Us" static info page
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$db = getDb();
$stmtRev = $db->prepare('
    SELECT r.rating, r.comment, r.created_at, u.full_name as reviewer_name
    FROM reviews r
    JOIN users u ON r.reviewer_id = u.id
    WHERE r.target_type = "platform"
    ORDER BY r.created_at DESC
    LIMIT 10
');
$stmtRev->execute();
$platformReviews = $stmtRev->fetchAll();

$extraCss = ['about'];
require_once __DIR__ . '/../includes/partials/head.php';
?>

<div class="about-hero">
    <div class="container">
        <p class="label-md" style="color:var(--color-secondary); letter-spacing: 2px; text-transform: uppercase;">Our Identity</p>
        <h1 class="headline-xl" style="color:white; max-width: 600px; margin-top: 16px; margin-bottom: 24px;">Redefining the journey with premium performance.</h1>
        <p class="body-lg" style="color:white; max-width: 600px;">Beyond just mobility, we curate an experience defined by precision, luxury, and the relentless pursuit of automotive excellence.</p>
    </div>
</div>

<section class="about-story section-padding">
    <div class="container grid grid-2" style="align-items: center; gap: 48px;">
        <div>
            <h2 class="headline-lg" style="margin-bottom: 24px;">Our Story</h2>
            <p class="body-md" style="margin-bottom: 16px;">Founded on the principle that travel should be as exhilarating as the destination, EliteDrive emerged as a boutique rental agency for those who refuse to compromise on quality.</p>
            <p class="body-md" style="margin-bottom: 16px;">What began as a small collection of performance vehicles has evolved into a nationwide network of premium fleet management, catering to executives, enthusiasts, and travelers who demand the extraordinary.</p>
            <p class="body-md">Our journey is paved with meticulous attention to detail—from the white-glove delivery service to the rigorous multi-point inspections that ensure every vehicle performs at its peak.</p>
        </div>
        <div style="position: relative;">
            <img src="https://images.unsplash.com/photo-1552519507-da3b142c6e3d?q=80&w=1000&auto=format&fit=crop" alt="Showroom" style="width: 100%; border-radius: var(--radius-lg); box-shadow: 0 20px 40px rgba(0,0,0,0.1);">
            <div class="years-badge">
                <h3 class="headline-lg" style="color:white; margin:0;">15+</h3>
                <p class="label-sm" style="color:var(--color-secondary); margin:0;">YEARS OF EXCELLENCE</p>
            </div>
        </div>
    </div>
</section>

<section class="about-values section-padding" style="background-color: var(--color-surface);">
    <div class="container">
        <div style="text-align: center; margin-bottom: 48px;">
            <h2 class="headline-lg">Core Values</h2>
            <p class="body-md" style="color:var(--color-secondary);">The pillars that define the EliteDrive experience.</p>
        </div>
        
        <div class="grid grid-3">
            <div class="card value-card">
                <div class="icon-wrapper"><span class="material-symbols-outlined">verified</span></div>
                <h3 class="headline-md">Uncompromising Quality</h3>
                <p class="body-md" style="color:var(--color-secondary); margin-top:8px;">Every vehicle in our fleet undergoes a rigorous 150-point inspection to ensure absolute safety and performance.</p>
            </div>
            <div class="card value-card">
                <div class="icon-wrapper"><span class="material-symbols-outlined">support_agent</span></div>
                <h3 class="headline-md">Personalized Service</h3>
                <p class="body-md" style="color:var(--color-secondary); margin-top:8px;">We don't just rent cars; we manage your mobility with dedicated concierge support available 24/7.</p>
            </div>
            <div class="card value-card">
                <div class="icon-wrapper"><span class="material-symbols-outlined">bolt</span></div>
                <h3 class="headline-md">Efficiency & Speed</h3>
                <p class="body-md" style="color:var(--color-secondary); margin-top:8px;">Our digital-first booking system and expedited check-in process get you on the road in under five minutes.</p>
            </div>
        </div>
    </div>
        </div>
    </div>
</section>

<?php if (!empty($platformReviews)): ?>
<section class="about-reviews section-padding">
    <div class="container">
        <div style="text-align: center; margin-bottom: 48px;">
            <h2 class="headline-lg">What Our Clients Say</h2>
            <p class="body-md" style="color:var(--color-secondary);">Experiences shared by the EliteDrive community.</p>
        </div>
        <div class="reviews-slider-wrapper" style="position: relative; overflow: hidden; padding: 10px 0;">
            <style>
                .slider-btn { opacity: 0; transition: opacity 0.5s ease; }
                .reviews-slider-wrapper:hover .slider-btn { opacity: 1; }
            </style>
            <div class="reviews-track" id="reviewsTrack" style="display: flex; gap: 24px; will-change: transform;">
                <?php foreach ($platformReviews as $rev): ?>
                    <div class="card" style="min-width: calc((100% - 48px) / 3); max-width: calc((100% - 48px) / 3); flex-shrink: 0; padding: var(--space-lg); border-radius: var(--radius-md); box-sizing: border-box;">
                        <div style="margin-bottom: 12px; color:#f59e0b; font-size: 20px;">
                            <?= str_repeat('★', $rev['rating']) ?><?= str_repeat('☆', 5 - $rev['rating']) ?>
                        </div>
                        <?php if ($rev['comment']): ?>
                            <p class="body-md" style="font-style: italic; margin-bottom: 16px;">"<?= nl2br(escapeHtml($rev['comment'])) ?>"</p>
                        <?php endif; ?>
                        <div>
                            <strong class="body-md"><?= escapeHtml($rev['reviewer_name']) ?></strong>
                            <div class="body-sm" style="color:var(--color-secondary);"><?= escapeHtml(date('M Y', strtotime($rev['created_at']))) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (count($platformReviews) > 3): ?>
            <button id="prevReview" class="btn slider-btn" style="position: absolute; top: 50%; left: 0; transform: translateY(-50%); z-index: 10; padding: 12px; background: var(--color-surface); border: 1px solid var(--color-outline); border-radius: 50%; box-shadow: 0 4px 6px rgba(0,0,0,0.1); font-weight: bold; cursor: pointer;">&lt;</button>
            <button id="nextReview" class="btn slider-btn" style="position: absolute; top: 50%; right: 0; transform: translateY(-50%); z-index: 10; padding: 12px; background: var(--color-surface); border: 1px solid var(--color-outline); border-radius: 50%; box-shadow: 0 4px 6px rgba(0,0,0,0.1); font-weight: bold; cursor: pointer;">&gt;</button>
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const wrapper = document.querySelector('.reviews-slider-wrapper');
                    const track = document.getElementById('reviewsTrack');
                    const prevBtn = document.getElementById('prevReview');
                    const nextBtn = document.getElementById('nextReview');
                    let autoSlideInterval;
                    let isAnimating = false;

                    function goNext() {
                        if (isAnimating) return;
                        isAnimating = true;
                        
                        const slideWidth = track.children[0].offsetWidth + 24; // width + gap
                        track.style.transition = 'transform 0.3s ease-in-out';
                        track.style.transform = `translateX(-${slideWidth}px)`;

                        setTimeout(() => {
                            track.style.transition = 'none';
                            track.appendChild(track.children[0]);
                            track.style.transform = 'translateX(0)';
                            isAnimating = false;
                        }, 300);
                    }

                    function goPrev() {
                        if (isAnimating) return;
                        isAnimating = true;
                        
                        const slideWidth = track.children[0].offsetWidth + 24;
                        track.style.transition = 'none';
                        track.insertBefore(track.lastElementChild, track.children[0]);
                        track.style.transform = `translateX(-${slideWidth}px)`;

                        // Force reflow
                        void track.offsetWidth;

                        track.style.transition = 'transform 0.3s ease-in-out';
                        track.style.transform = 'translateX(0)';
                        
                        setTimeout(() => {
                            isAnimating = false;
                        }, 300);
                    }

                    nextBtn.addEventListener('click', () => {
                        goNext();
                        resetInterval();
                    });

                    prevBtn.addEventListener('click', () => {
                        goPrev();
                        resetInterval();
                    });

                    function startInterval() {
                        autoSlideInterval = setInterval(goNext, 2500);
                    }

                    function stopInterval() {
                        clearInterval(autoSlideInterval);
                    }

                    function resetInterval() {
                        stopInterval();
                        startInterval();
                    }

                    wrapper.addEventListener('mouseenter', stopInterval);
                    wrapper.addEventListener('mouseleave', startInterval);

                    startInterval();
                });
            </script>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="about-team section-padding">
    <div class="container">
        <div style="margin-bottom: 48px;">
            <h2 class="headline-lg">Leadership Team</h2>
            <p class="body-md" style="color:var(--color-secondary);">The visionaries steering our commitment to the ultimate driving experience.</p>
        </div>
        
        <div class="grid grid-4" style="gap: 24px;">
            <!-- Team Member 1 -->
            <div class="team-card">
                <img src="https://images.unsplash.com/photo-1560250097-0b93528c311a?q=80&w=400&auto=format&fit=crop" alt="Julian Sterling" style="width: 100%; aspect-ratio: 3/4; object-fit: cover;">
                <div style="padding: 16px 20px 24px;">
                    <h3 class="headline-sm">Julian Sterling</h3>
                    <p class="label-sm" style="color:var(--color-primary); text-transform: uppercase;">Chief Executive Officer</p>
                </div>
            </div>
            <!-- Team Member 2 -->
            <div class="team-card">
                <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?q=80&w=400&auto=format&fit=crop" alt="Elena Vance" style="width: 100%; aspect-ratio: 3/4; object-fit: cover;">
                <div style="padding: 16px 20px 24px;">
                    <h3 class="headline-sm">Elena Vance</h3>
                    <p class="label-sm" style="color:var(--color-primary); text-transform: uppercase;">Chief Operations Officer</p>
                </div>
            </div>
            <!-- Team Member 3 -->
            <div class="team-card">
                <img src="https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?q=80&w=400&auto=format&fit=crop" alt="Marcus Chen" style="width: 100%; aspect-ratio: 3/4; object-fit: cover;">
                <div style="padding: 16px 20px 24px;">
                    <h3 class="headline-sm">Marcus Chen</h3>
                    <p class="label-sm" style="color:var(--color-primary); text-transform: uppercase;">Director of Fleet Strategy</p>
                </div>
            </div>
            <!-- Team Member 4 -->
            <div class="team-card">
                <img src="https://images.unsplash.com/photo-1580489944761-15a19d654956?q=80&w=400&auto=format&fit=crop" alt="Sophia Moretti" style="width: 100%; aspect-ratio: 3/4; object-fit: cover;">
                <div style="padding: 16px 20px 24px;">
                    <h3 class="headline-sm">Sophia Moretti</h3>
                    <p class="label-sm" style="color:var(--color-primary); text-transform: uppercase;">Head of Client Experience</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="about-cta" style="background-color: black; color: white; padding: 80px 0; text-align: center;">
    <div class="container">
        <h2 class="headline-xl" style="margin-bottom: 32px;">Ready to experience the EliteDrive standard?</h2>
        <div style="display: flex; justify-content: center; gap: 16px;">
            <a href="<?= baseUrl('/fleet/search.php') ?>" class="btn" style="background-color: white; color: black; font-weight: 500; border: none;">Explore the Fleet</a>
            <a href="<?= baseUrl('/contact.php') ?>" class="btn" style="background-color: transparent; border: 1px solid white; color: white; font-weight: 500;">Contact Concierge</a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/partials/footer.php'; ?>
