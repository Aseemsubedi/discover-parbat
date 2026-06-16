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

document.addEventListener('DOMContentLoaded', () => {
  const dateInput = document.getElementById('bk-date');
  if (dateInput) {
    const today = new Date();
    const yyyy = today.getFullYear();
    const mm = String(today.getMonth() + 1).padStart(2, '0');
    const dd = String(today.getDate()).padStart(2, '0');
    dateInput.min = `${yyyy}-${mm}-${dd}`;
  }

  const gallery = document.getElementById('trek-gallery');
  const lightbox = document.getElementById('gallery-lightbox');
  const lightboxImg = document.getElementById('gallery-lightbox-img');
  if (!gallery || !lightbox || !lightboxImg) return;

  const items = Array.from(gallery.querySelectorAll('.gallery-item'));
  const sources = items.map((btn) => btn.dataset.src || '');
  let current = 0;

  const show = (index) => {
    if (!sources.length) return;
    current = (index + sources.length) % sources.length;
    lightboxImg.src = sources[current];
    lightboxImg.alt = items[current]?.querySelector('img')?.alt || 'Trek photo';
    lightbox.classList.add('open');
    lightbox.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  };

  const close = () => {
    lightbox.classList.remove('open');
    lightbox.setAttribute('aria-hidden', 'true');
    lightboxImg.src = '';
    document.body.style.overflow = '';
  };

  items.forEach((btn, index) => {
    btn.addEventListener('click', () => show(index));
  });

  lightbox.querySelector('.gallery-lightbox-close')?.addEventListener('click', close);
  lightbox.querySelector('.gallery-lightbox-prev')?.addEventListener('click', () => show(current - 1));
  lightbox.querySelector('.gallery-lightbox-next')?.addEventListener('click', () => show(current + 1));

  lightbox.addEventListener('click', (e) => {
    if (e.target === lightbox) close();
  });

  document.addEventListener('keydown', (e) => {
    if (!lightbox.classList.contains('open')) return;
    if (e.key === 'Escape') close();
    if (e.key === 'ArrowLeft') show(current - 1);
    if (e.key === 'ArrowRight') show(current + 1);
  });
});
