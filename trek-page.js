// Trek page interactions: itinerary accordion, FAQ, booking form
function toggleAcc(id) {
  const item = document.getElementById(id);
  const content = document.getElementById(id + '-content');
  if (!item || !content) return;
  const isOpen = item.classList.contains('open');
  document.querySelectorAll('.accordion-item').forEach((el) => el.classList.remove('open'));
  document.querySelectorAll('.accordion-content').forEach((el) => el.classList.remove('open'));
  if (!isOpen) {
    item.classList.add('open');
    content.classList.add('open');
  }
}

function toggleFaq(btn) {
  const item = btn.closest('.faq-item');
  if (!item) return;
  const isOpen = item.classList.contains('open');
  document.querySelectorAll('.faq-item').forEach((el) => el.classList.remove('open'));
  if (!isOpen) item.classList.add('open');
}

async function handleBookingSubmit(event) {
  event.preventDefault();

  const form = document.getElementById('booking-form');
  const name = document.getElementById('bk-name').value.trim();
  const country = document.getElementById('bk-country').value;
  const email = document.getElementById('bk-email').value.trim();
  const whatsapp = document.getElementById('bk-whatsapp').value.trim();
  const trek = document.getElementById('bk-trek').value;
  const startDate = document.getElementById('bk-date').value;
  const pax = document.getElementById('bk-pax').value;
  const special = document.getElementById('bk-special').value.trim();

  if (!email && !whatsapp) {
    alert('Please provide at least one contact option: Gmail or WhatsApp.');
    return;
  }

  const payload = new URLSearchParams({
    type: 'booking',
    name,
    country,
    email,
    whatsapp,
    trek,
    startDate,
    pax,
    special
  });

  const DP = window.DiscoverParbat || {};
  await DP.submitInquiry(payload, {
    whatsappMessage: DP.formatBookingMessage({
      name, country, email, whatsapp, trek, startDate, pax, special
    }),
    onSuccess: () => {
      form.reset();
      window.location.href = '/success';
    }
  });
}

// ─── BOOKING FORM TREK LIST ─────────────────────────────
// Edit this ONE list to change the "Trekking Name" dropdown on every trek page.
// Each name is exactly what arrives in the booking message.
const DP_TREKS = [
  'Kokhe Danda Trek',
  'Short Kokhe Danda Trek',
  'Multiple Viewpoints Trek',
  'Short Multiple Viewpoints Trek',
  'Mardi Himal Trek',
  'Mardi Himal and Poon Hill Trek',
  'Poon Hill Trek',
  'Khopra Ridge Trek',
  'Pikey Peak Trek',
  'Annapurna Base Camp Trek',
  'Annapurna Circuit Trek'
  // add new treks here, e.g. 'New Trek Name',
];

function buildTrekDropdown() {
  const sel = document.getElementById('bk-trek');
  if (!sel) return;

  // The trek this page marked "selected" in its HTML
  const current = sel.value;

  const make = (name, selected) => {
    const o = document.createElement('option');
    o.value = name;
    o.textContent = name;
    if (selected) o.selected = true;
    return o;
  };

  sel.innerHTML = '';

  // Page's own trek first (kept even if it is missing from DP_TREKS)
  if (current && current !== 'Not sure yet') {
    sel.appendChild(make(current, true));
  }
  sel.appendChild(make('Not sure yet', current === 'Not sure yet'));
  DP_TREKS
    .filter((t) => t !== current)
    .forEach((t) => sel.appendChild(make(t, false)));
}

document.addEventListener('DOMContentLoaded', () => {
  buildTrekDropdown();

  const dateInput = document.getElementById('bk-date');
  if (dateInput) {
    const today = new Date();
    const yyyy = today.getFullYear();
    const mm = String(today.getMonth() + 1).padStart(2, '0');
    const dd = String(today.getDate()).padStart(2, '0');
    dateInput.min = `${yyyy}-${mm}-${dd}`;
  }

  const carousel = document.getElementById('trek-gallery');
  if (carousel) {
    const slides = Array.from(carousel.querySelectorAll('.gallery-slide'));
    const dots = Array.from(carousel.querySelectorAll('.gallery-dot'));
    const counter = document.getElementById('gallery-counter');
    const total = slides.length;
    let current = 0;
    let timer = null;

    const goTo = (index) => {
      if (!total) return;
      current = (index + total) % total;
      slides.forEach((slide, i) => slide.classList.toggle('active', i === current));
      dots.forEach((dot, i) => dot.classList.toggle('active', i === current));
      if (counter) counter.textContent = `${current + 1} / ${total}`;
    };

    const next = () => goTo(current + 1);
    const prev = () => goTo(current - 1);

    const startAuto = () => {
      stopAuto();
      if (total > 1) timer = window.setInterval(next, 4000);
    };
    const stopAuto = () => {
      if (timer) {
        window.clearInterval(timer);
        timer = null;
      }
    };

    carousel.querySelector('.gallery-arrow-next')?.addEventListener('click', () => {
      next();
      startAuto();
    });
    carousel.querySelector('.gallery-arrow-prev')?.addEventListener('click', () => {
      prev();
      startAuto();
    });
    dots.forEach((dot) => {
      dot.addEventListener('click', () => {
        goTo(Number(dot.dataset.index || 0));
        startAuto();
      });
    });

    carousel.addEventListener('mouseenter', stopAuto);
    carousel.addEventListener('mouseleave', startAuto);

    startAuto();
  }
});
