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
                                <input type="radio" name="transmission" value="" checked>
                                <span class="toggle-btn">All</span>
                            </label>
                            <label>
                                <input type="radio" name="transmission" value="auto">
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
            
            <div class="pagination-wrapper" id="pagination-wrapper" style="display: none;">
                <div class="pagination-info-group">
                    <div id="pagination-summary">Showing 0 of 0 vehicles</div>
                    <div class="per-page-selector">
                        <label for="per-page-select">Per page:</label>
                        <select id="per-page-select" class="per-page-dropdown" onchange="changePerPage(this.value)">
                            <option value="6">6</option>
                            <option value="10" selected>10</option>
                            <option value="14">14</option>
                            <option value="all">All</option>
                        </select>
                    </div>
                </div>
                
                <div class="pagination" id="pagination-controls">
                    <!-- Dynamic page buttons -->
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    const resultsGrid = document.getElementById('results-grid');
    const paginationWrapper = document.getElementById('pagination-wrapper');
    const paginationSummary = document.getElementById('pagination-summary');
    const paginationControls = document.getElementById('pagination-controls');

    let allVehicles = [];
    let currentPage = 1;
    let perPage = 10;

    function clearFilters() {
        document.getElementById('filter-q').value = '';
        document.getElementById('filter-pickup').value = '';
        document.getElementById('filter-return').value = '';
        document.querySelectorAll('input[name="category"]').forEach(cb => cb.checked = false);
        const allTransRadio = document.querySelector('input[name="transmission"][value=""]');
        if (allTransRadio) allTransRadio.checked = true;
        loadVehicles();
    }

    async function loadVehicles() {
        const q = document.getElementById('filter-q').value;
        const pickupDate = document.getElementById('filter-pickup').value;
        const returnDate = document.getElementById('filter-return').value;
        const transRadio = document.querySelector('input[name="transmission"]:checked');
        const transmission = transRadio ? transRadio.value : '';
        
        // Get all checked categories
        const checkedCategories = Array.from(document.querySelectorAll('input[name="category"]:checked')).map(cb => cb.value);
        const categoryParam = checkedCategories.join(',');
        
        try {
            let url = `<?= baseUrl('/api/vehicles/search.php') ?>?`;
            const params = new URLSearchParams();
            if (q) params.append('q', q);
            if (categoryParam) params.append('category', categoryParam);
            if (transmission) params.append('transmission', transmission);
            if (pickupDate && returnDate) {
                params.append('pickup_date', pickupDate);
                params.append('return_date', returnDate);
            }
            
            const res = await fetch(url + params.toString());
            if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
            const data = await res.json();
            
            if (data.error) {
                throw new Error(data.error);
            }
            
            allVehicles = Array.isArray(data) ? data : [];
            currentPage = 1;
            renderPagination();
            
        } catch (e) {
            console.error("Fetch error:", e);
            paginationWrapper.style.display = 'none';
            resultsGrid.innerHTML = `
                <div style="grid-column: 1 / -1; text-align: center; padding: 40px; background: #ffebee; border-radius: var(--radius-lg); border: 1px solid #ffcdd2; color: #c62828;">
                    <p>Error loading vehicles. Please check the server connection and try again.</p>
                </div>`;
        }
    }

    function changePerPage(val) {
        perPage = val === 'all' ? 'all' : parseInt(val, 10);
        currentPage = 1;
        renderPagination();
    }

    function goToPage(page) {
        currentPage = page;
        renderPagination();
        
        // Smooth scroll to top of fleet search grid
        const mainElem = document.querySelector('main');
        if (mainElem) {
            mainElem.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    function renderPagination() {
        const totalItems = allVehicles.length;

        if (totalItems === 0) {
            paginationWrapper.style.display = 'none';
            resultsGrid.innerHTML = `
                <div style="grid-column: 1 / -1; text-align: center; padding: 40px; background: white; border-radius: var(--radius-lg); border: 1px solid var(--color-outline);">
                    <p style="color:var(--color-secondary);">No vehicles found matching your criteria.</p>
                </div>`;
            return;
        }

        paginationWrapper.style.display = 'flex';

        const totalPages = perPage === 'all' ? 1 : Math.ceil(totalItems / perPage);
        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        let startIndex = 0;
        let endIndex = totalItems;

        if (perPage !== 'all') {
            startIndex = (currentPage - 1) * perPage;
            endIndex = Math.min(startIndex + perPage, totalItems);
        }

        const visibleVehicles = allVehicles.slice(startIndex, endIndex);

        // Update Summary
        if (perPage === 'all' || totalItems <= perPage) {
            paginationSummary.textContent = `Showing all ${totalItems} vehicle${totalItems === 1 ? '' : 's'}`;
        } else {
            paginationSummary.textContent = `Showing ${startIndex + 1}–${endIndex} of ${totalItems} vehicles`;
        }

        // Render Vehicle Cards
        resultsGrid.innerHTML = visibleVehicles.map(v => {
            const img = v.photo_path || '';
            const imgHtml = img ? `<img src="${escapeHtml(img)}" alt="Vehicle">` : `<div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; color:#ccc;">[No Image]</div>`;
            
            const isElectric = v.category === 'Electric';
            const rateUnit = isElectric ? 'km/charge' : 'km/l';
            const rateIcon = isElectric ? 'electric_car' : 'local_gas_station';
            const transIcon = v.transmission === 'Manual' ? 'account_tree' : 'settings';

            return `
            <div class="fleet-card">
                <div class="fleet-card-image">
                    ${(v.category || '').split(',').map(c => c.trim()).filter(c => c).map(c => `<div class="fleet-badge" style="display:inline-block; margin-right: 4px;">${escapeHtml(c)}</div>`).join('')}
                    ${imgHtml}
                </div>
                <div class="fleet-card-body">
                    <div class="fleet-title-row">
                        <div class="fleet-title-info">
                            <h3>${escapeHtml(v.make)} ${escapeHtml(v.model)}</h3>
                            <p>YOM: ${escapeHtml(v.yom || '')}</p>
                        </div>
                        <div class="fleet-price">
                            <div class="amount">$${escapeHtml(v.daily_rate)}</div>
                            <div class="period">per day</div>
                        </div>
                    </div>
                    <div class="fleet-specs">
                        <div class="spec-item">
                            <span class="material-symbols-outlined">${rateIcon}</span>
                            <span>${escapeHtml(v.km_rate || '')} ${rateUnit}</span>
                        </div>
                        <div class="spec-item">
                            <span class="material-symbols-outlined">speed</span>
                            <span>${escapeHtml(v.mileage || '')} km</span>
                        </div>
                        <div class="spec-item">
                            <span class="material-symbols-outlined">${transIcon}</span>
                            <span>${escapeHtml(v.transmission || '')}</span>
                        </div>
                    </div>
                    <div class="fleet-actions">
                        <a href="<?= baseUrl('/fleet/detail.php') ?>?id=${v.id}" class="btn-dark">Reserve Now</a>
                    </div>
                </div>
            </div>
            `;
        }).join('');

        // Render Pagination Controls
        if (totalPages <= 1) {
            paginationControls.innerHTML = '';
            return;
        }

        let controlsHtml = '';

        // Previous button
        const prevDisabled = currentPage === 1 ? 'disabled' : '';
        controlsHtml += `
            <div class="page-btn ${prevDisabled}" onclick="${currentPage > 1 ? `goToPage(${currentPage - 1})` : ''}" title="Previous Page">
                <span class="material-symbols-outlined" style="font-size:18px;">chevron_left</span>
            </div>
        `;

        // Page buttons with pagination algorithm
        const pages = getPageNumbers(currentPage, totalPages);
        pages.forEach(p => {
            if (p === '...') {
                controlsHtml += `<span class="page-ellipsis">...</span>`;
            } else {
                const isActive = p === currentPage ? 'active' : '';
                controlsHtml += `
                    <div class="page-btn ${isActive}" onclick="goToPage(${p})">${p}</div>
                `;
            }
        });

        // Next button
        const nextDisabled = currentPage === totalPages ? 'disabled' : '';
        controlsHtml += `
            <div class="page-btn ${nextDisabled}" onclick="${currentPage < totalPages ? `goToPage(${currentPage + 1})` : ''}" title="Next Page">
                <span class="material-symbols-outlined" style="font-size:18px;">chevron_right</span>
            </div>
        `;

        paginationControls.innerHTML = controlsHtml;
    }

    function getPageNumbers(current, total) {
        if (total <= 7) {
            return Array.from({ length: total }, (_, i) => i + 1);
        }

        const pages = [];
        if (current <= 4) {
            for (let i = 1; i <= 5; i++) pages.push(i);
            pages.push('...');
            pages.push(total);
        } else if (current >= total - 3) {
            pages.push(1);
            pages.push('...');
            for (let i = total - 4; i <= total; i++) pages.push(i);
        } else {
            pages.push(1);
            pages.push('...');
            for (let i = current - 1; i <= current + 1; i++) pages.push(i);
            pages.push('...');
            pages.push(total);
        }
        return pages;
    }

    function escapeHtml(str) {
        if (!str) return '';
        return str.toString().replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    // Live filtering for Search Vehicle (debounced) and Transmission
    let searchDebounceTimer = null;
    const filterQInput = document.getElementById('filter-q');
    if (filterQInput) {
        filterQInput.addEventListener('input', () => {
            clearTimeout(searchDebounceTimer);
            searchDebounceTimer = setTimeout(() => {
                loadVehicles();
            }, 250);
        });
    }

    document.querySelectorAll('input[name="transmission"]').forEach(radio => {
        radio.addEventListener('change', () => {
            loadVehicles();
        });
    });

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
        if (urlParams.has('transmission')) {
            const trans = urlParams.get('transmission');
            const rb = document.querySelector(`input[name="transmission"][value="${trans}"]`);
            if (rb) rb.checked = true;
        }
        loadVehicles();
    });
</script>

<?php require_once __DIR__ . '/../../includes/partials/footer.php'; ?>
