    </main>
    <footer class="site-footer">
        <div class="container grid grid-4">
            <div style="grid-column: span 1;">
                <h3 class="headline-md" style="margin-bottom: 8px; color: var(--color-primary);">EliteDrive</h3>
                <p class="body-sm" style="color: var(--color-secondary); line-height: 1.6; max-width: 280px;">The premier luxury & performance car rental marketplace. Uncompromising quality, transparent pricing, and 24/7 concierge mobility.</p>
            </div>
            <div>
                <h4 class="label-md" style="color: var(--color-primary); margin-bottom: 12px;">Explore</h4>
                <ul class="stack-xs" style="list-style:none; padding:0; margin:0;">
                    <li><a href="<?= baseUrl('/fleet/search.php') ?>" style="color:var(--color-secondary); text-decoration:none; font-size:14px;">Browse Fleet</a></li>
                    <li><a href="<?= baseUrl('/about.php') ?>" style="color:var(--color-secondary); text-decoration:none; font-size:14px;">About Us</a></li>
                    <li><a href="<?= baseUrl('/contact.php') ?>" style="color:var(--color-secondary); text-decoration:none; font-size:14px;">Concierge Contact</a></li>
                </ul>
            </div>
            <div>
                <h4 class="label-md" style="color: var(--color-primary); margin-bottom: 12px;">Support & FAQs</h4>
                <ul class="stack-xs" style="list-style:none; padding:0; margin:0;">
                    <li><a href="<?= baseUrl('/faqs.php') ?>" style="color:var(--color-secondary); text-decoration:none; font-size:14px;">Frequently Asked Questions</a></li>
                    <li><a href="<?= baseUrl('/contact.php') ?>" style="color:var(--color-secondary); text-decoration:none; font-size:14px;">Emergency Roadside</a></li>
                    <li><a href="<?= baseUrl('/faqs.php') ?>" style="color:var(--color-secondary); text-decoration:none; font-size:14px;">Rental Policies</a></li>
                </ul>
            </div>
            <div>
                <h4 class="label-md" style="color: var(--color-primary); margin-bottom: 12px;">Headquarters</h4>
                <p class="body-sm" style="color: var(--color-secondary); line-height: 1.5; margin-bottom: 8px;">450 Park Avenue South, Penthouse<br>New York, NY 10022</p>
                <p class="body-sm" style="color: var(--color-primary); font-weight: 500;">+1 (800) ELITE-DRV</p>
            </div>
        </div>
        <div class="container" style="border-top: 1px solid var(--color-outline); margin-top: 32px; padding-top: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <p class="body-sm" style="color: var(--color-secondary); margin:0;">&copy; <?= date('Y') ?> EliteDrive Technologies Inc. All rights reserved.</p>
            <div style="display: flex; gap: 16px;">
                <span class="body-sm" style="color: var(--color-secondary);">Executive Fleet Experience</span>
            </div>
        </div>
    </footer>
</body>
</html>
