(() => {
  'use strict';
  const storageKey = 'wellnest.savedServices.v1';
  let saved = [];
  try {
    const value = JSON.parse(localStorage.getItem(storageKey) || '[]');
    if (Array.isArray(value)) saved = [...new Set(value.filter(id => /^[1-9][0-9]{0,9}$/.test(String(id))).map(String))];
  } catch { /* Storage may be blocked; the current session remains interactive. */ }
  let toastTimer;
  const toast = document.getElementById('feedback-toast');
  function notify(message) {
    if (!toast) return;
    clearTimeout(toastTimer);
    document.getElementById('toast-message').textContent = message;
    toast.hidden = false;
    toastTimer = setTimeout(() => { toast.hidden = true; }, 6500);
  }
  document.getElementById('dismiss-toast')?.addEventListener('click', () => { toast.hidden = true; clearTimeout(toastTimer); });

  const cards = [...document.querySelectorAll('#service-grid [data-service-card]')];
  const search = document.getElementById('service-search');
  let category = 'all';
  let onlySaved = false;
  function filterServices() {
    if (!document.getElementById('service-grid')) return;
    const query = (search?.value || '').trim().toLocaleLowerCase();
    let count = 0;
    cards.forEach(card => {
      const id = card.querySelector('[data-save-service]').dataset.saveService;
      const visible = (category === 'all' || card.dataset.category === category) && card.dataset.search.includes(query) && (!onlySaved || saved.includes(id));
      card.hidden = !visible;
      if (visible) count++;
    });
    document.getElementById('result-count').textContent = count === cards.length && !query && category === 'all' && !onlySaved ? `Showing all ${cards.length} services` : `${count} ${count === 1 ? 'service' : 'services'} found`;
    document.getElementById('empty-state').hidden = count !== 0;
  }
  function syncSavedButtons() {
    document.querySelectorAll('[data-save-service]').forEach(button => {
      const isSaved = saved.includes(button.dataset.saveService);
      button.setAttribute('aria-pressed', String(isSaved));
      button.setAttribute('aria-label', `${isSaved ? 'Unsave' : 'Save'} ${button.dataset.serviceName}`);
      const label = button.querySelector('[data-save-label]');
      if (label) {
        label.textContent = isSaved ? 'Saved to your services' : 'Save this service';
        button.setAttribute('aria-label', `${label.textContent}: ${button.dataset.serviceName}`);
      }
    });
    const count = document.getElementById('saved-count');
    if (count) count.textContent = saved.length;
  }
  document.querySelectorAll('[data-save-service]').forEach(button => {
    button.addEventListener('click', () => {
      const id = button.dataset.saveService;
      const removing = saved.includes(id);
      saved = removing ? saved.filter(value => value !== id) : [...saved, id];
      try { localStorage.setItem(storageKey, JSON.stringify(saved)); } catch { /* Keep working without persistent storage. */ }
      syncSavedButtons();
      filterServices();
      notify(`${button.dataset.serviceName} ${removing ? 'removed from' : 'added to'} your saved services.`);
    });
  });
  if (document.getElementById('service-grid')) {
    const available = new Set(cards.map(card => card.querySelector('[data-save-service]').dataset.saveService));
    saved = saved.filter(id => available.has(id));
    try { localStorage.setItem(storageKey, JSON.stringify(saved)); } catch {}
  }
  syncSavedButtons(); filterServices();
  document.querySelectorAll('[data-filter]').forEach(button => button.addEventListener('click', () => {
    category = button.dataset.filter;
    document.querySelectorAll('[data-filter]').forEach(item => {
      item.classList.toggle('active', item === button);
      item.setAttribute('aria-pressed', String(item === button));
    });
    filterServices();
  }));
  search?.addEventListener('input', filterServices);
  document.querySelector('form#search')?.addEventListener('submit', event => { event.preventDefault(); filterServices(); });
  document.getElementById('saved-filter')?.addEventListener('click', event => {
    onlySaved = !onlySaved;
    event.currentTarget.setAttribute('aria-pressed', String(onlySaved));
    filterServices();
  });
  document.getElementById('reset-filters')?.addEventListener('click', () => {
    category = 'all'; onlySaved = false; search.value = '';
    document.querySelectorAll('[data-filter]').forEach(button => {
      const active = button.dataset.filter === 'all';
      button.classList.toggle('active', active);
      button.setAttribute('aria-pressed', String(active));
    });
    document.getElementById('saved-filter').setAttribute('aria-pressed', 'false');
    filterServices(); search.focus();
  });
  window.addEventListener('storage', event => {
    if (event.key !== storageKey) return;
    try {
      const next = JSON.parse(event.newValue || '[]');
      if (!Array.isArray(next)) return;
      saved = [...new Set(next.filter(id => /^[1-9][0-9]{0,9}$/.test(String(id))).map(String))];
      syncSavedButtons(); filterServices();
    } catch { /* Ignore malformed storage written elsewhere. */ }
  });
  function focusSearch() { if (search && location.hash === '#search') search.focus({preventScroll: true}); }
  window.addEventListener('hashchange', focusSearch);
  focusSearch();
  document.querySelectorAll('[data-search-link]').forEach(link => link.addEventListener('click', () => { if (search) setTimeout(() => search.focus({preventScroll: true}), 250); }));
  document.querySelectorAll('#mainNavbar a').forEach(link => link.addEventListener('click', () => {
    const nav = document.getElementById('mainNavbar');
    if (nav.classList.contains('show') && window.bootstrap) bootstrap.Collapse.getOrCreateInstance(nav).hide();
  }));

  const form = document.getElementById('appointment-form');
  if (!form) return;
  const date = document.getElementById('preferred-date');
  const select = document.getElementById('service-id');
  const formatter = new Intl.NumberFormat('en-US');
  select.addEventListener('change', () => {
    const price = select.selectedOptions[0]?.dataset.price;
    document.getElementById('booking-price').textContent = price ? `${formatter.format(Number(price))} ₫` : 'Select a service';
  });
  date.addEventListener('input', () => {
    const day = new Date(`${date.value}T12:00:00+07:00`);
    const sunday = !Number.isNaN(day.valueOf()) && new Intl.DateTimeFormat('en-US', {weekday: 'short', timeZone: 'Asia/Ho_Chi_Minh'}).format(day) === 'Sun';
    date.setCustomValidity(sunday ? 'Please choose Monday through Saturday; the clinic is closed on Sundays.' : '');
  });
  form.addEventListener('submit', async event => {
    event.preventDefault();
    if (!form.reportValidity()) return;
    const button = document.getElementById('submit-request');
    if (button.disabled) return;
    const original = button.innerHTML;
    const errors = document.getElementById('form-errors');
    errors.hidden = true;
    button.disabled = true;
    button.textContent = 'Saving your request…';
    try {
      const response = await fetch(form.action, {method: 'POST', body: new FormData(form), headers: {Accept: 'application/json'}, credentials: 'same-origin'});
      const result = await response.json();
      if (!response.ok || !result.ok) {
        errors.replaceChildren();
        const title = document.createElement('strong'); title.textContent = 'Please check your request.'; errors.append(title);
        const list = document.createElement('ul');
        Object.entries(result.errors || {form: 'Please try again.'}).forEach(([field, message]) => {
          const item = document.createElement('li'); item.textContent = message; list.append(item);
          const input = form.elements.namedItem(field); if (input) input.setAttribute('aria-invalid', 'true');
        });
        errors.append(list); errors.hidden = false; errors.focus();
        return;
      }
      const value = result.confirmation;
      document.getElementById('confirmation-reference').textContent = value.reference;
      document.getElementById('confirmation-service').textContent = value.service_name;
      document.getElementById('confirmation-date').textContent = value.preferred_date;
      document.getElementById('confirmation-time').textContent = value.preferred_time;
      document.getElementById('confirmation-price').textContent = `${formatter.format(Number(value.quoted_price))} ₫`;
      form.hidden = true;
      const success = document.getElementById('booking-success'); success.hidden = false; success.focus();
      history.replaceState(null, '', `${location.pathname}?success=1`);
      notify('Your demo appointment request has been saved.');
    } catch {
      errors.textContent = 'We couldn’t reach the server. Check your connection and try again; retrying will not create a duplicate request.';
      errors.hidden = false; errors.focus();
    } finally { button.disabled = false; button.innerHTML = original; }
  });
  form.addEventListener('input', event => event.target.removeAttribute('aria-invalid'));
})();
