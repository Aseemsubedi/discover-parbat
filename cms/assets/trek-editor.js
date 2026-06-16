document.addEventListener('DOMContentLoaded', () => {
  const containerMap = {
    itinerary: document.getElementById('itinerary-rows'),
    faq: document.getElementById('faq-rows'),
    price: document.getElementById('price-rows')
  };

  document.querySelectorAll('[data-add-row]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const type = btn.getAttribute('data-add-row');
      const container = containerMap[type];
      const tpl = document.getElementById('tpl-' + type);
      if (!container || !tpl) return;
      container.appendChild(tpl.content.cloneNode(true));
    });
  });

  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.cms-remove-row');
    if (!btn) return;
    const row = btn.closest('.cms-repeat-row');
    if (row) row.remove();
  });
});
