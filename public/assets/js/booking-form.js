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
  const vehicleIdInput = document.querySelector('[name="vehicle_id"]');
  if (!vehicleIdInput) return;
  const vehicleId = vehicleIdInput.value;
  const pickupDate = document.querySelector('[name="pickup_date"]')?.value || '';
  const returnDate = document.querySelector('[name="return_date"]')?.value || '';
  
  try {
      const baseUrl = window.appBaseUrl || '';
      let url = `${baseUrl}/api/drivers/available.php?vehicle_id=${vehicleId}`;
      if (pickupDate && returnDate) {
          url += `&pickup_date=${encodeURIComponent(pickupDate)}&return_date=${encodeURIComponent(returnDate)}`;
      }
      
      const res = await fetch(url);
      const drivers = await res.json();
      const select = document.querySelector('#driver-select');
      
      if (!select) return;

      if (!drivers || drivers.length === 0) {
          select.innerHTML = '<option value="">No compatible drivers available for these dates</option>';
          return;
      }
      
      let options = '<option value="">Let admin assign a driver</option>';
      options += drivers.map(d => {
          const fee = d.daily_fee ? `$${parseFloat(d.daily_fee).toFixed(2)}/day` : '';
          const trans = d.transmission_preference ? ` (${d.transmission_preference})` : '';
          const label = `${escapeHtml(d.full_name)}${fee ? ' - ' + fee : ''}${trans}`;
          return `<option value="${d.id}">${label}</option>`;
      }).join('');
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
