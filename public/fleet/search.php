<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

$extraCss = ['fleet-search'];
require_once __DIR__ . '/../../includes/partials/head.php';

// If parameters were passed via GET (e.g. from homepage), we'll read them in JS.
?>

<div style="background-color: #f8fafc;">
    <div class="fleet-hero">
        <div class="container">
            <h1>Our Fleet</h1>
            <p>Experience high-performance precision. Browse our curated collection of luxury vehicles, performance sports cars, and high-efficiency electrics.</p>
        </div>
    </div>

    <div class="container grid" style="grid-template-columns: 280px 1fr; gap: 32px; padding-bottom: 60px;">
        <aside>
            <div class="filter-sidebar">
                <div class="filter-header">
                    <h3>Filters</h3>
                    <span class="material-symbols-outlined">filter_list</span>
                </div>
                
                <form id="filter-form" onsubmit="event.preventDefault(); loadVehicles();">
                    
                    <div class="filter-group">
                        <div class="filter-group-title">Search Vehicle</div>
                        <div class="search-input-box">
                            <input type="text" id="filter-q" placeholder="e.g. Model S, Porsche...">
                            <span class="material-symbols-outlined">search</span>
                        </div>
                    </div>
                    
                    <div class="filter-group">
                        <div class="filter-group-title">Availability</div>
                        <div style="margin-bottom: 8px;">
                            <label style="font-size:11px; color:var(--color-secondary);">Pick-up</label>
                            <input type="date" id="filter-pickup" style="width:100%; padding:8px; border:1px solid var(--color-outline); border-radius:4px; font-size:13px;" min="<?= date('Y-m-d') ?>">
                        </div>
                        <div>
                            <label style="font-size:11px; color:var(--color-secondary);">Return</label>
                            <input type="date" id="filter-return" style="width:100%; padding:8px; border:1px solid var(--color-outline); border-radius:4px; font-size:13px;" min="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                    
                    <div class="filter-group">
                        <div class="filter-group-title">Category</div>
                        <label class="checkbox-label">
                            <input type="checkbox" name="category" value="Electric"> Electric
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="category" value="Luxury"> Luxury
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="category" value="Offroad"> Off-road
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="category" value="Budget"> Budget
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="category" value="Premium"> Premium
                        </label>
                    </div>
                    
                    <div class="filter-group">
                        <div class="filter-group-title">Transmission</div>
                        <div class="toggle-group">
                            <label>
                                <input type="radio" name="transmission" value="auto" checked>
                                <span class="toggle-btn">Auto</span>
                            </label>
                            <label>
                                <input type="radio" name="transmission" value="manual">
                                <span class="toggle-btn">Manual</span>
                            </label>
                        </div>
                    </div>
                    
                    <button type="submit" class="apply-btn">Apply Filters</button>
                    <button type="button" class="clear-btn" onclick="clearFilters()">Clear all filters</button>
                </form>
            </div>
        </aside>
        
        <main>
            <div class="vehicle-grid" id="results-grid">
                <!-- Results loaded via JS -->
                <div style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                    <p>Loading vehicles...</p>
                </div>
            </div>
            
            <div class="pagination">
                <div class="page-btn outline"><span class="material-symbols-outlined" style="font-size:18px;">chevron_left</span></div>
                <div class="page-btn active">1</div>
                <div class="page-btn">2</div>
                <div class="page-btn">3</div>
                <div class="page-btn outline"><span class="material-symbols-outlined" style="font-size:18px;">chevron_right</span></div>
            </div>
        </main>
    </div>
</div>

<div class="trust-bar">
    <div class="container grid grid-4">
        <div class="trust-item">
            <span class="material-symbols-outlined">verified_user</span>
            <div class="trust-text">
                <h4>Fully Insured</h4>
                <p>Comprehensive coverage included</p>
            </div>
        </div>
        <div class="trust-item">
            <span class="material-symbols-outlined">support_agent</span>
            <div class="trust-text">
                <h4>24/7 Support</h4>
                <p>Round-the-clock roadside help</p>
            </div>
        </div>
        <div class="trust-item">
            <span class="material-symbols-outlined">star</span>
            <div class="trust-text">
                <h4>Premium Fleet</h4>
                <p>Vehicles under 2 years old</p>
            </div>
        </div>
        <div class="trust-item">
            <span class="material-symbols-outlined">cancel</span>
            <div class="trust-text">
                <h4>Free Cancellation</h4>
                <p>Flexible booking policies</p>
            </div>
        </div>
    </div>
</div>

<script>
    const resultsGrid = document.getElementById('results-grid');

    function clearFilters() {
        document.getElementById('filter-q').value = '';
        document.getElementById('filter-pickup').value = '';
        document.getElementById('filter-return').value = '';
        document.querySelectorAll('input[name="category"]').forEach(cb => cb.checked = false);
        document.querySelector('input[name="transmission"][value="auto"]').checked = true;
        loadVehicles();
    }

    async function loadVehicles() {
        const q = document.getElementById('filter-q').value;
        const pickupDate = document.getElementById('filter-pickup').value;
        const returnDate = document.getElementById('filter-return').value;
        
        // Get all checked categories
        const checkedCategories = Array.from(document.querySelectorAll('input[name="category"]:checked')).map(cb => cb.value);
        const categoryParam = checkedCategories.join(',');
        
        try {
            let url = `<?= baseUrl('/api/vehicles/search.php') ?>?`;
            const params = new URLSearchParams();
            if (q) params.append('q', q);
            if (categoryParam) params.append('category', categoryParam);
            if (pickupDate && returnDate) {
                params.append('pickup_date', pickupDate);
                params.append('return_date', returnDate);
            }
            
            const res = await fetch(url + params.toString());
            if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
            const vehicles = await res.json();
            
            if (vehicles.error) {
                throw new Error(vehicles.error);
            }
            
            if (!Array.isArray(vehicles) || vehicles.length === 0) {
                resultsGrid.innerHTML = `
                    <div style="grid-column: 1 / -1; text-align: center; padding: 40px; background: white; border-radius: var(--radius-lg); border: 1px solid var(--color-outline);">
                        <p style="color:var(--color-secondary);">No vehicles found matching your criteria.</p>
                    </div>`;
                return;
            }
            
            resultsGrid.innerHTML = vehicles.map(v => {
                const img = v.photo_path ? (v.photo_path.startsWith('http') ? v.photo_path : `<?= baseUrl('/') ?>${v.photo_path}`) : '';
                const imgHtml = img ? `<img src="${escapeHtml(img)}" alt="Vehicle">` : `<div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; color:#ccc;">[No Image]</div>`;
                
                return `
                <div class="fleet-card">
                    <div class="fleet-card-image">
                        <div class="fleet-badge">${escapeHtml(v.category)}</div>
                        ${imgHtml}
                    </div>
                    <div class="fleet-card-body">
                        <div class="fleet-title-row">
                            <div class="fleet-title-info">
                                <h3>${escapeHtml(v.make)} ${escapeHtml(v.model)}</h3>
                                <p>Premium ${escapeHtml(v.category)}</p>
                            </div>
                            <div class="fleet-price">
                                <div class="amount">$${escapeHtml(v.daily_rate)}</div>
                                <div class="period">per day</div>
                            </div>
                        </div>
                        <div class="fleet-specs">
                            <div class="spec-item">
                                <span class="material-symbols-outlined">speed</span>
                                <span>450km Range</span>
                            </div>
                            <div class="spec-item">
                                <span class="material-symbols-outlined">group</span>
                                <span>5 Seats</span>
                            </div>
                            <div class="spec-item">
                                <span class="material-symbols-outlined">settings</span>
                                <span>Automatic</span>
                            </div>
                        </div>
                        <div class="fleet-actions">
                            <a href="<?= baseUrl('/fleet/detail.php') ?>?id=${v.id}" class="btn-dark">View Details</a>
                        </div>
                    </div>
                </div>
                `;
            }).join('');
            
        } catch (e) {
            console.error("Fetch error:", e);
            resultsGrid.innerHTML = `
                <div style="grid-column: 1 / -1; text-align: center; padding: 40px; background: #ffebee; border-radius: var(--radius-lg); border: 1px solid #ffcdd2; color: #c62828;">
                    <p>Error loading vehicles. Please check the server connection and try again.</p>
                </div>`;
        }
    }

    function escapeHtml(str) {
        if (!str) return '';
        return str.toString().replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    // Auto-populate from URL if present (from homepage)
    window.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('pickup_date')) document.getElementById('filter-pickup').value = urlParams.get('pickup_date');
        if (urlParams.has('return_date')) document.getElementById('filter-return').value = urlParams.get('return_date');
        if (urlParams.has('category')) {
            const cat = urlParams.get('category');
            const cb = document.querySelector(`input[name="category"][value="${cat}"]`);
            if (cb) cb.checked = true;
        }
        loadVehicles();
    });
</script>

<?php require_once __DIR__ . '/../../includes/partials/footer.php'; ?>
