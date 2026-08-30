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
                <div class="faq-tab active">General Booking</div>
                <div class="faq-tab">Our Fleet</div>
                <div class="faq-tab">Insurance & Protection</div>
                <div class="faq-tab">Policies & Requirements</div>
            </div>
        </aside>

        <!-- Main Content (Accordions) -->
        <main>
            
            <div class="faq-accordion-item">
                <div class="faq-accordion-header" onclick="toggleAccordion(this)">
                    <span>How do I book a premium vehicle?</span>
                    <span class="material-symbols-outlined faq-accordion-icon">expand_more</span>
                </div>
                <div class="faq-accordion-content">
                    Booking a vehicle with EliteDrive is simple. Browse our fleet collection, select your desired dates, and complete the checkout process securely. For specialized requests, you can also contact our concierge team directly.
                </div>
            </div>
            
            <div class="faq-accordion-item">
                <div class="faq-accordion-header" onclick="toggleAccordion(this)">
                    <span>What documents are required for rental?</span>
                    <span class="material-symbols-outlined faq-accordion-icon">expand_more</span>
                </div>
                <div class="faq-accordion-content">
                    You must provide a valid driver's license (verified through our platform), a major credit card in your name, and proof of full-coverage insurance. International renters may require additional documentation such as a passport and an International Driving Permit.
                </div>
            </div>
            
            <div class="faq-accordion-item">
                <div class="faq-accordion-header" onclick="toggleAccordion(this)">
                    <span>Can I return the car at a different location?</span>
                    <span class="material-symbols-outlined faq-accordion-icon">expand_more</span>
                </div>
                <div class="faq-accordion-content">
                    Yes, we offer flexible one-way rentals between our major hub locations. Please arrange this during the booking process, as one-way logistics fees may apply depending on the vehicle and distance.
                </div>
            </div>
            
            <div class="faq-accordion-item">
                <div class="faq-accordion-header" onclick="toggleAccordion(this)">
                    <span>What is included in the insurance coverage?</span>
                    <span class="material-symbols-outlined faq-accordion-icon">expand_more</span>
                </div>
                <div class="faq-accordion-content">
                    All rentals include standard liability coverage. We also offer premium protection plans that cover collision damage, theft, and roadside assistance for total peace of mind.
                </div>
            </div>
            
            <div class="faq-accordion-item">
                <div class="faq-accordion-header" onclick="toggleAccordion(this)">
                    <span>Are there age restrictions for luxury cars?</span>
                    <span class="material-symbols-outlined faq-accordion-icon">expand_more</span>
                </div>
                <div class="faq-accordion-content">
                    Yes, due to the high-performance nature of our fleet, the primary renter must be at least 25 years old. Certain ultra-luxury or exotic vehicles may require the renter to be 30 years of age or older.
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

    // Optional: Tab switching logic
    document.querySelectorAll('.faq-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.faq-tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            // In a full app, this would also filter or swap the accordion items displayed.
        });
    });
</script>

<?php require_once __DIR__ . '/../includes/partials/footer.php'; ?>
