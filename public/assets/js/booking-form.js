const radios = document.querySelectorAll('[name="driver_arrangement"]');
radios.forEach(r => r.addEventListener('change', onArrangementChange));

function onArrangementChange(e) {
    const val = e.target.value;
    const hiredDriverBlock = document.querySelector('#hired-driver-block');

    if (hiredDriverBlock) {
        hiredDriverBlock.style.display = (val === 'hired') ? 'block' : 'none';
        if (val === 'hired') {
            loadAvailableDrivers();
        }
    }
}

async function loadAvailableDrivers() {
  const vehicleId = document.querySelector('[name="vehicle_id"]').value;
  const pickupDate = document.querySelector('[name="pickup_date"]').value;
  const returnDate = document.querySelector('[name="return_date"]').value;
  
  try {
      const baseUrl = window.appBaseUrl || '';
      let url = `${baseUrl}/api/drivers/available.php?vehicle_id=${vehicleId}`;
      if (pickupDate && returnDate) {
          url += `&pickup_date=${encodeURIComponent(pickupDate)}&return_date=${encodeURIComponent(returnDate)}`;
      }
      
      const res = await fetch(url);
      const drivers = await res.json();
      const select = document.querySelector('#driver-select');
      
      if (drivers.length === 0) {
          select.innerHTML = '<option value="">No drivers available for these dates</option>';
          return;
      }
      
      let options = '<option value="">Let admin assign a driver</option>';
      options += drivers.map(d => `<option value="${d.id}">${escapeHtml(d.full_name)}</option>`).join('');
      select.innerHTML = options;
  } catch (err) {
      console.error('Failed to load drivers', err);
      const select = document.querySelector('#driver-select');
      if (select) select.innerHTML = '<option value="">Error loading drivers</option>';
  }
}

document.querySelector('[name="pickup_date"]')?.addEventListener('change', () => {
    if (document.querySelector('input[name="driver_arrangement"]:checked')?.value === 'hired') {
        loadAvailableDrivers();
    }
});
document.querySelector('[name="return_date"]')?.addEventListener('change', () => {
    if (document.querySelector('input[name="driver_arrangement"]:checked')?.value === 'hired') {
        loadAvailableDrivers();
    }
});

function escapeHtml(str) {
    if (!str) return '';
    return str.toString().replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}
