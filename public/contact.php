<?php
/**
 * File: contact.php
 * Purpose: Public contact/inquiry form
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$extraCss = ['contact'];

$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (empty($fullName) || empty($email) || empty($subject) || empty($message)) {
        $errorMessage = 'Please fill out all required fields.';
    } else {
        try {
            $stmt = getDb()->prepare("INSERT INTO inquiries (full_name, email, subject, message) VALUES (?, ?, ?, ?)");
            $stmt->execute([$fullName, $email, $subject, $message]);
            $successMessage = 'Your inquiry has been submitted successfully. Our team will get back to you shortly.';
        } catch (PDOException $e) {
            $errorMessage = 'An error occurred while submitting your inquiry. Please try again later.';
        }
    }
}

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
                            <p class="body-md" style="font-weight: 500;">contactus@elitedrive.com</p>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon"><span class="material-symbols-outlined">call</span></div>
                        <div>
                            <p class="label-sm" style="color:var(--color-secondary); letter-spacing:1px; text-transform:uppercase; margin-bottom:4px;">Phone Support</p>
                            <p class="body-md" style="font-weight: 500;">+94 11 2 345678</p>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon"><span class="material-symbols-outlined">location_on</span></div>
                        <div>
                            <p class="label-sm" style="color:var(--color-secondary); letter-spacing:1px; text-transform:uppercase; margin-bottom:4px;">Global HQ</p>
                            <p class="body-md" style="font-weight: 500; line-height: 1.4;">EliteDrive Headquarters<br>Colombo, Sri Lanka</p>
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
                <?php if ($successMessage): ?>
                    <div class="alert alert-success" style="margin-bottom: 20px;"><?= escapeHtml($successMessage) ?></div>
                <?php endif; ?>
                <?php if ($errorMessage): ?>
                    <div class="alert alert-danger" style="margin-bottom: 20px;"><?= escapeHtml($errorMessage) ?></div>
                <?php endif; ?>
                
                <form action="<?= baseUrl('/contact.php') ?>" method="POST">
                    <div class="grid grid-2" style="gap: 16px; margin-bottom: 20px;">
                        <div>
                            <label class="label-sm">Full Name</label>
                            <input type="text" name="full_name" class="contact-input" placeholder="Johnathan Sterling" required>
                        </div>
                        <div>
                            <label class="label-sm">Email Address</label>
                            <input type="email" name="email" class="contact-input" placeholder="john@example.com" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="label-sm">Subject</label>
                        <select name="subject" class="contact-input" required>
                            <option value="">Select a subject...</option>
                            <option value="fleet" selected>Corporate Fleet Inquiry</option>
                            <option value="support">Active Rental Support</option>
                            <option value="payment">Payment Inquiry</option>
                            <option value="vehicle">Vehicle Inquiry</option>
                            <option value="driver">Driver Inquiry</option>
                            <option value="other">Other Inquiry</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="label-sm">Message</label>
                        <textarea name="message" class="contact-input" rows="5" placeholder="How can our concierge team assist you today?" required></textarea>
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
        <div>
            <h2 class="headline-md" style="margin-bottom: var(--space-md);">Headquarters Location</h2>
            <div class="map-section">
                <img src="<?= baseUrl('/assets/images/kobu-agency-FyvE6XPs5gk-unsplash.jpg') ?>" class="map-bg" alt="Map">
                <div class="map-pin">
                    <span class="material-symbols-outlined">location_on</span>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/partials/footer.php'; ?>
