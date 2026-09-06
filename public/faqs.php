<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$extraCss = ['faqs'];
require_once __DIR__ . '/../includes/partials/head.php';
?>

<div class="faq-hero">
    <div class="container">
        <h1 class="headline-xl" style="margin-bottom: 16px;">Frequently Asked Questions</h1>
        <p>Find answers to common questions about booking, our premium fleet, policies, and insurance to ensure a seamless luxury rental experience.</p>
    </div>
</div>

<div class="faq-content">
    <div class="container grid" style="grid-template-columns: 300px 1fr; gap: 48px; align-items: start;">
        
        <!-- Sidebar -->
        <aside>
            <div class="faq-sidebar">
                <div class="faq-tab active" data-target="general">General Booking</div>
                <div class="faq-tab" data-target="fleet">Our Fleet</div>
                <div class="faq-tab" data-target="insurance">Insurance & Protection</div>
                <div class="faq-tab" data-target="policies">Policies & Requirements</div>
            </div>
        </aside>

        <!-- Main Content (Accordions) -->
        <main id="faq-container">
            
            <div class="faq-accordion-item" data-category="general">
                <div class="faq-accordion-header" onclick="toggleAccordion(this)">
                    <span>How do I book a premium vehicle?</span>
                    <span class="material-symbols-outlined faq-accordion-icon">expand_more</span>
                </div>
                <div class="faq-accordion-content">
                    Booking a vehicle with EliteDrive is simple. Browse our fleet collection, select your desired dates, and complete the checkout process securely. For specialized requests, you can also contact our concierge team directly.
                </div>
            </div>
            
            <div class="faq-accordion-item" data-category="general">
                <div class="faq-accordion-header" onclick="toggleAccordion(this)">
                    <span>How are rental charges calculated?</span>
                    <span class="material-symbols-outlined faq-accordion-icon">expand_more</span>
                </div>
                <div class="faq-accordion-content">
                    We offer a flexible pricing model designed for your convenience. Rental periods are calculated in 6-hour charge blocks. For example, if you rent a car for 24 hours, you are billed for 4 blocks (the standard daily rate). We also provide a complimentary 1-hour grace period on your return time before an additional block is charged!
                </div>
            </div>

            <div class="faq-accordion-item" data-category="general">
                <div class="faq-accordion-header" onclick="toggleAccordion(this)">
                    <span>Where can I pick up and return the vehicle?</span>
                    <span class="material-symbols-outlined faq-accordion-icon">expand_more</span>
                </div>
                <div class="faq-accordion-content">
                    You can pick up and return your vehicle at any of our three primary hubs in Sri Lanka: CMB Airport (Katunayaka), HRI Airport (Mattala), or the EliteDrive Headquarters (Colombo). You can specify different pick-up and return locations during the booking process to suit your itinerary.
                </div>
            </div>
            
            <div class="faq-accordion-item" data-category="general">
                <div class="faq-accordion-header" onclick="toggleAccordion(this)">
                    <span>Do I have to drive the vehicle myself?</span>
                    <span class="material-symbols-outlined faq-accordion-icon">expand_more</span>
                </div>
                <div class="faq-accordion-content">
                    Not at all. EliteDrive offers three driving arrangements: you can choose to drive yourself, hire one of our professional chauffeurs, or even have the vehicle's owner drive you. Simply select your preferred arrangement during the booking process.
                </div>
            </div>
            
            <!-- Fleet -->
            <div class="faq-accordion-item" data-category="fleet" style="display: none;">
                <div class="faq-accordion-header" onclick="toggleAccordion(this)">
                    <span>What types of vehicles do you offer?</span>
                    <span class="material-symbols-outlined faq-accordion-icon">expand_more</span>
                </div>
                <div class="faq-accordion-content">
                    Our EliteDrive fleet consists of carefully curated luxury, premium, electric, and high-end off-road vehicles. Every car undergoes a rigorous quality check before it is handed over to ensure an unparalleled driving experience.
                </div>
            </div>
            <div class="faq-accordion-item" data-category="fleet" style="display: none;">
                <div class="faq-accordion-header" onclick="toggleAccordion(this)">
                    <span>Are the cars maintained regularly?</span>
                    <span class="material-symbols-outlined faq-accordion-icon">expand_more</span>
                </div>
                <div class="faq-accordion-content">
                    Absolutely. All vehicles in our system are mandated to follow strict maintenance schedules. We also inspect every vehicle before and after each rental to ensure mechanical perfection and interior cleanliness.
                </div>
            </div>

            <!-- Insurance & Protection -->
            <div class="faq-accordion-item" data-category="insurance" style="display: none;">
                <div class="faq-accordion-header" onclick="toggleAccordion(this)">
                    <span>What is included in the standard insurance?</span>
                    <span class="material-symbols-outlined faq-accordion-icon">expand_more</span>
                </div>
                <div class="faq-accordion-content">
                    Every booking comes with standard third-party liability coverage in accordance with local regulations. However, you are responsible for damage to the rental vehicle up to the specified excess amount unless you purchase additional protection.
                </div>
            </div>
            <div class="faq-accordion-item" data-category="insurance" style="display: none;">
                <div class="faq-accordion-header" onclick="toggleAccordion(this)">
                    <span>Is roadside assistance provided?</span>
                    <span class="material-symbols-outlined faq-accordion-icon">expand_more</span>
                </div>
                <div class="faq-accordion-content">
                    Yes, we offer 24/7 premium roadside assistance for all our rentals anywhere in Sri Lanka. If you face a mechanical issue, our concierge team will immediately dispatch support or a replacement vehicle.
                </div>
            </div>

            <!-- Policies & Requirements -->
            <div class="faq-accordion-item" data-category="policies" style="display: none;">
                <div class="faq-accordion-header" onclick="toggleAccordion(this)">
                    <span>What documents are required for a self-drive rental?</span>
                    <span class="material-symbols-outlined faq-accordion-icon">expand_more</span>
                </div>
                <div class="faq-accordion-content">
                    If you choose to drive yourself, you must upload a valid driver's license for verification through our platform before booking. You must also present this license upon vehicle pickup.
                </div>
            </div>
            <div class="faq-accordion-item" data-category="policies" style="display: none;">
                <div class="faq-accordion-header" onclick="toggleAccordion(this)">
                    <span>Is there a deposit required?</span>
                    <span class="material-symbols-outlined faq-accordion-icon">expand_more</span>
                </div>
                <div class="faq-accordion-content">
                    Yes, a security deposit is held on your credit card at the time of pickup. The amount depends on the car class and insurance package you select. The hold is released within 3-5 business days after the vehicle is returned without damage.
                </div>
            </div>
            <div class="faq-accordion-item" data-category="policies" style="display: none;">
                <div class="faq-accordion-header" onclick="toggleAccordion(this)">
                    <span>What is the cancellation policy?</span>
                    <span class="material-symbols-outlined faq-accordion-icon">expand_more</span>
                </div>
                <div class="faq-accordion-content">
                    You can cancel your booking for a full refund up to 48 hours before your scheduled pick-up time. Cancellations made within 48 hours may be subject to a one-day rental cancellation fee.
                </div>
            </div>

        </main>
        
    </div>
</div>

<script>
    function toggleAccordion(element) {
        const item = element.parentElement;
        item.classList.toggle('open');
    }

    document.querySelectorAll('.faq-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            // Update active tab
            document.querySelectorAll('.faq-tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            // Filter accordion items
            const targetCategory = this.getAttribute('data-target');
            document.querySelectorAll('.faq-accordion-item').forEach(item => {
                if (item.getAttribute('data-category') === targetCategory) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                    item.classList.remove('open');
                }
            });
        });
    });
</script>

<?php require_once __DIR__ . '/../includes/partials/footer.php'; ?>
