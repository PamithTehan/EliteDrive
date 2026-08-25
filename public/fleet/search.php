<?php
require __DIR__ . '/../../includes/db.php';
require __DIR__ . '/../../includes/auth.php';
require __DIR__ . '/../../includes/functions.php';

$extraCss = ['fleet-search'];
require __DIR__ . '/../../includes/partials/head.php';
?>

<div class="fleet-hero">
    <div class="container">
        <h1 class="headline-xl">Our Premium Fleet</h1>
        <p class="body-lg" style="color: var(--color-secondary);">Find the perfect vehicle for your next journey.</p>
    </div>
</div>

<div class="container grid grid-3" style="margin-top: var(--space-lg); margin-bottom: var(--space-lg);">
    <aside style="grid-column: 1 / 2;">
        <div class="filter-sidebar stack-md">
            <h3 class="headline-md">Filters</h3>
            
            <div class="filter-group">
                <div class="filter-group-title">Category</div>
                <label class="filter-option"><input type="radio" name="category" value="" checked> All</label>
                <label class="filter-option"><input type="radio" name="category" value="Premium"> Premium</label>
                <label class="filter-option"><input type="radio" name="category" value="Luxury"> Luxury</label>
                <label class="filter-option"><input type="radio" name="category" value="Budget"> Budget</label>
                <label class="filter-option"><input type="radio" name="category" value="Offroad"> Offroad</label>
            </div>
            
            <div class="filter-group">
                <div class="filter-group-title">Max Daily Rate ($)</div>
                <input type="range" id="price-range" min="50" max="1000" step="50" value="1000" style="width:100%;">
                <div style="display:flex; justify-content:space-between; font-size:12px; color:var(--color-secondary);">
                    <span>$50</span>
                    <span id="price-display">$1000</span>
                </div>
            </div>
        </div>
    </aside>
    
    <main style="grid-column: 2 / 4;">
        <div class="vehicle-grid" id="results-grid">
            <!-- Results loaded via JS -->
            <div style="grid-column: 1 / -1; text-align: center; padding: var(--space-lg);">
                <p class="body-md">Loading vehicles...</p>
            </div>
        </div>
    </main>
</div>

<script>
    const categoryRadios = document.querySelectorAll('input[name="category"]');
    const priceRange = document.getElementById('price-range');
    const priceDisplay = document.getElementById('price-display');
    const resultsGrid = document.getElementById('results-grid');

    async function loadVehicles() {
        const category = document.querySelector('input[name="category"]:checked').value;
        const maxPrice = priceRange.value;
        
        try {
            const url = `<?= baseUrl('/api/vehicles/search.php') ?>?category=${encodeURIComponent(category)}&max_price=${encodeURIComponent(maxPrice)}`;
            const res = await fetch(url);
            if (!res.ok) {
                throw new Error(`HTTP error! status: ${res.status}`);
            }
            const vehicles = await res.json();
            
            if (vehicles.length === 0) {
                resultsGrid.innerHTML = `
                    <div style="grid-column: 1 / -1; text-align: center; padding: var(--space-lg); background: #fff; border-radius: var(--radius-lg); border: 1px solid var(--color-outline);">
                        <p class="body-lg" style="color:var(--color-secondary);">No vehicles found matching your criteria.</p>
                    </div>`;
                return;
            }
            
            resultsGrid.innerHTML = vehicles.map(v => `
                <div class="card">
                    <div class="card-media">
                        <!-- Image placeholder -->
                        <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; color:#ccc; overflow: hidden;">
                            ${v.photo_path ? `<img src="${escapeHtml(v.photo_path)}" style="width:100%; height:100%; object-fit:cover;" alt="Vehicle">` : '[No Image]'}
                        </div>
                    </div>
                    <div class="card-body">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom: var(--space-sm);">
                            <h3 class="headline-md">${escapeHtml(v.make)} ${escapeHtml(v.model)}</h3>
                            <span class="badge badge-${v.category.toLowerCase()}">${escapeHtml(v.category)}</span>
                        </div>
                        <div class="card-price" style="margin-bottom: var(--space-md);">$${escapeHtml(v.daily_rate)}<span style="font-size:14px; font-weight:normal; color:var(--color-secondary);">/day</span></div>
                        <a href="<?= baseUrl('/fleet/detail.php') ?>?id=${v.id}" class="btn btn-primary" style="width:100%;">View Details</a>
                    </div>
                </div>
            `).join('');
            
        } catch (e) {
            console.error("Fetch error:", e);
            resultsGrid.innerHTML = `
                <div style="grid-column: 1 / -1; text-align: center; padding: var(--space-lg); background: #ffebee; border-radius: var(--radius-lg); border: 1px solid #ffcdd2; color: #c62828;">
                    <p class="body-lg">Error loading vehicles. Please check the server connection and try again.</p>
                    <p class="body-sm" style="margin-top: 8px;">Details: ${escapeHtml(e.message)}</p>
                </div>`;
        }
    }

    function escapeHtml(str) {
        if (!str) return '';
        return str.toString().replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    categoryRadios.forEach(r => r.addEventListener('change', loadVehicles));
    priceRange.addEventListener('input', (e) => {
        priceDisplay.textContent = '$' + e.target.value;
    });
    priceRange.addEventListener('change', loadVehicles);
    
    // Initial load
    loadVehicles();
</script>

<?php require __DIR__ . '/../../includes/partials/footer.php'; ?>
