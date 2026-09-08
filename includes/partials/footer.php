    </main>
    <style>
        .footer-grid { display: grid; gap: var(--space-lg); grid-template-columns: 2fr 1fr 1fr 1fr; }
        @media (max-width: 1024px) { .footer-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 768px) { .footer-grid { grid-template-columns: 1fr; } }
    </style>
    <footer class="site-footer">
        <div class="container footer-grid">
            <div>
                <h3 class="headline-md" style="margin-bottom: 8px; color: var(--color-primary);">EliteDrive</h3>
                <p class="body-sm" style="color: var(--color-secondary); line-height: 1.6; max-width: 280px;">The premier luxury & performance car rental marketplace. Uncompromising quality, transparent pricing, and 24/7 concierge mobility.</p>
            </div>
            <div>
                <h4 class="label-md" style="color: var(--color-primary); margin-bottom: 12px;">Explore</h4>
                <ul class="stack-xs" style="list-style:none; padding:0; margin:0;">
                    <li><a href="<?= baseUrl('/fleet/search.php') ?>" style="color:var(--color-secondary); text-decoration:none; font-size:14px;">Browse Fleet</a></li>
                    <li><a href="<?= baseUrl('/about.php') ?>" style="color:var(--color-secondary); text-decoration:none; font-size:14px;">About Us</a></li>
                    <li><a href="<?= baseUrl('/contact.php') ?>" style="color:var(--color-secondary); text-decoration:none; font-size:14px;">Contact Us</a></li>
                </ul>
            </div>
            <div>
                <h4 class="label-md" style="color: var(--color-primary); margin-bottom: 12px;">Support & FAQs</h4>
                <ul class="stack-xs" style="list-style:none; padding:0; margin:0;">
                    <li><a href="<?= baseUrl('/faqs.php') ?>" style="color:var(--color-secondary); text-decoration:none; font-size:14px;">Frequently Asked Questions</a></li>
                    <li><a href="<?= baseUrl('/contact.php') ?>" style="color:var(--color-secondary); text-decoration:none; font-size:14px;">Contact Us</a></li>
                </ul>
            </div>
            <div>
                <h4 class="label-md" style="color: var(--color-primary); margin-bottom: 12px;">Headquarters</h4>
                <p class="body-sm" style="color: var(--color-secondary); line-height: 1.5; margin-bottom: 8px;">EliteDrive Headquarters<br>Colombo, Sri Lanka</p>
                <p class="body-sm" style="color: var(--color-primary); font-weight: 500;">+94 11 2 345678</p>
            </div>
        </div>
        <div class="container" style="border-top: 1px solid var(--color-outline); margin-top: 32px; padding-top: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <p class="body-sm" style="color: var(--color-secondary); margin:0;">&copy; <?= date('Y') ?> Pamith Tehan, EliteDrive Technologies Inc. All rights reserved.</p>
            <div style="display: flex; gap: 16px;">
                <span class="body-sm" style="color: var(--color-secondary);">Executive Fleet Experience. Acadamic purpose only.</span>
            </div>
        </div>
    </footer>
</body>
</html>
