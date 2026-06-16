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
  if (!dateInput) return;
  const today = new Date();
  const yyyy = today.getFullYear();
  const mm = String(today.getMonth() + 1).padStart(2, '0');
  const dd = String(today.getDate()).padStart(2, '0');
  dateInput.min = `${yyyy}-${mm}-${dd}`;
});
