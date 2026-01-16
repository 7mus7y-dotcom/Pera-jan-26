(function () {
  const form = document.querySelector('.hero-search-lite');
  if (!form) return;

  function setPillActive(pill, isActive) {
    if (!pill) return;
    pill.classList.toggle('pill--active', !!isActive);
  }

  function clearRowActives(row) {
    if (!row) return;
    row.querySelectorAll('.filter-pill').forEach(p => p.classList.remove('pill--active'));
  }

  function syncCheckboxPills(row) {
    if (!row) return;

    row.querySelectorAll('input[type="checkbox"]').forEach(cb => {
      const pill = cb.closest('.filter-pill');
      setPillActive(pill, cb.checked);
    });

    const anyBtn = row.querySelector('[data-clear-group]');
    if (anyBtn) {
      const anyActive = row.querySelectorAll('input[type="checkbox"]:checked').length === 0;
      setPillActive(anyBtn, anyActive);
    }
  }

  function syncRadioPills(row) {
    if (!row) return;

    row.querySelectorAll('input[type="radio"]').forEach(r => {
      const pill = r.closest('.filter-pill');
      setPillActive(pill, r.checked);
    });
  }

  // Initial sync on load
  form.querySelectorAll('.filter-pill-row').forEach(row => {
    if (row.querySelector('input[type="checkbox"]')) syncCheckboxPills(row);
    if (row.querySelector('input[type="radio"]')) syncRadioPills(row);
  });

  // Update pill visuals when inputs change (labels, keyboard, etc.)
  form.addEventListener('change', function (e) {
    const input = e.target;
    if (!(input instanceof HTMLInputElement)) return;

    const row = input.closest('.filter-pill-row');
    if (!row) return;

    if (input.type === 'checkbox') syncCheckboxPills(row);
    if (input.type === 'radio') syncRadioPills(row);
  });

  // Handle Any buttons + Budget pills
  form.addEventListener('click', function (e) {

    // 1) Any / Clear buttons
    const clearBtn = e.target.closest('[data-clear-group]');
    if (clearBtn) {
      const group = clearBtn.getAttribute('data-clear-group');
      if (!group) return;

      form.querySelectorAll(`input[type="checkbox"][name="${group}[]"]`).forEach(cb => { cb.checked = false; });

      const row = clearBtn.closest('.filter-pill-row');
      syncCheckboxPills(row);
      return;
    }

    // 2) Budget preset pills
    const budgetPill = e.target.closest('[data-budget]');
    if (budgetPill) {
      const minEl = form.querySelector('#hero-min-price');
      const maxEl = form.querySelector('#hero-max-price');
      if (!minEl || !maxEl) return;

      const val = budgetPill.getAttribute('data-budget') || '';
      let min = '', max = '';

      if (val.length) {
        const parts = val.split(',');
        min = (parts[0] || '').trim();
        max = (parts[1] || '').trim();
      }

      minEl.value = min;
      maxEl.value = max;

      const row = budgetPill.closest('.filter-pill-row');
      clearRowActives(row);
      budgetPill.classList.add('pill--active');
    }
  });

})();
