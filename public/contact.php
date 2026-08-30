<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$extraCss = ['contact'];
require_once __DIR__ . '/../includes/partials/head.php';
?>

<div class="contact-page">
    <div class="container">
        
        <div class="contact-header">
            <h1 class="headline-xl" style="margin-bottom: 16px;">Get in Touch</h1>
            <p class="body-lg" style="color: var(--color-secondary); max-width: 600px;">
                Experience the pinnacle of luxury car rentals. Our concierge team is ready to assist you with bespoke travel arrangements and fleet inquiries.
            </p>
        </div>

        <div class="grid grid-2" style="gap: 24px; align-items: start;">
            
            <!-- Left Column: Contact Info -->
            <div>
                <div class="contact-card" style="margin-bottom: 24px;">
                    <h2 class="headline-sm" style="margin-bottom: 24px;">Contact Information</h2>
                    
                    <div class="info-item">
                        <div class="info-icon"><span class="material-symbols-outlined">mail</span></div>
                        <div>
                            <p class="label-sm" style="color:var(--color-secondary); letter-spacing:1px; text-transform:uppercase; margin-bottom:4px;">Email Us</p>
                            <p class="body-md" style="font-weight: 500;">concierge@elitedrive.luxury</p>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon"><span class="material-symbols-outlined">call</span></div>
                        <div>
                            <p class="label-sm" style="color:var(--color-secondary); letter-spacing:1px; text-transform:uppercase; margin-bottom:4px;">Phone Support</p>
                            <p class="body-md" style="font-weight: 500;">+1 (800) ELITE-DRV</p>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon"><span class="material-symbols-outlined">location_on</span></div>
                        <div>
                            <p class="label-sm" style="color:var(--color-secondary); letter-spacing:1px; text-transform:uppercase; margin-bottom:4px;">Global HQ</p>
                            <p class="body-md" style="font-weight: 500; line-height: 1.4;">450 Park Avenue South, Penthouse<br>New York, NY 10022</p>
                        </div>
                    </div>
                </div>

                <div class="emergency-card">
                    <div class="emergency-icon"><span class="material-symbols-outlined" style="font-size:20px;">emergency</span></div>
                    <div>
                        <h3 class="headline-sm" style="margin-bottom: 8px;">Emergency Roadside</h3>
                        <p class="body-sm" style="color: #9ca3af; line-height: 1.5;">Available 24/7 for active rentals. Dial <strong>9-1-1</strong> for medical emergencies, or <strong>#DRIVE-SOS</strong> for mechanical support.</p>
                    </div>
                </div>

                <div style="margin-top: 32px;">
                    <p class="label-sm" style="color:var(--color-secondary); letter-spacing:1px; text-transform:uppercase;">Follow The Journey</p>
                    <div class="social-icons">
                        <a href="#" class="social-btn"><span class="material-symbols-outlined" style="font-size: 20px;">language</span></a>
                        <a href="#" class="social-btn"><span class="material-symbols-outlined" style="font-size: 20px;">camera</span></a>
                        <a href="#" class="social-btn"><span class="material-symbols-outlined" style="font-size: 20px;">movie</span></a>
                    </div>
                </div>
            </div>

            <!-- Right Column: Form -->
            <div class="contact-card">
                <form action="#" method="POST" onsubmit="event.preventDefault(); alert('Message sent!');">
                    <div class="grid grid-2" style="gap: 16px; margin-bottom: 20px;">
                        <div>
                            <label class="label-sm">Full Name</label>
                            <input type="text" class="contact-input" placeholder="Johnathan Sterling" required>
                        </div>
                        <div>
                            <label class="label-sm">Email Address</label>
                            <input type="email" class="contact-input" placeholder="john@example.com" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="label-sm">Subject</label>
                        <select class="contact-input" required>
                            <option value="">Select a subject...</option>
                            <option value="fleet" selected>Corporate Fleet Inquiry</option>
                            <option value="support">Active Rental Support</option>
                            <option value="other">Other Inquiry</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="label-sm">Message</label>
                        <textarea class="contact-input" rows="5" placeholder="How can our concierge team assist you today?" required></textarea>
                    </div>
                    
                    <div style="display:flex; align-items:flex-start; gap:8px; margin-bottom: 24px;">
                        <input type="checkbox" id="privacy" required style="margin-top:4px;">
                        <label for="privacy" class="body-sm" style="color:var(--color-secondary);">I agree to the <a href="#" style="color:var(--color-text); text-decoration:underline;">Privacy Policy</a> and data terms.</label>
                    </div>
                    
                    <button type="submit" class="btn" style="width:100%; background:black; color:white; border:none; padding:16px; font-weight:500; display:flex; align-items:center; justify-content:center; gap:8px;">
                        Send Message <span class="material-symbols-outlined" style="font-size:20px;">send</span>
                    </button>
                </form>
            </div>
            
        </div>

        <!-- Map Section -->
        <div class="map-section">
            <img src="https://images.unsplash.com/photo-1496442226666-8d4d0e62e6e9?q=80&w=2070&auto=format&fit=crop" class="map-bg" alt="New York Skyline">
            <div class="map-pin">
                <span class="material-symbols-outlined">location_on</span>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/partials/footer.php'; ?>
