/* Discover Parbat — shared scripts
   Header scroll shadow, sticky CTA visibility, and mobile hamburger menu. */

(function () {
  const header    = document.getElementById('main-header');
  let stickyRoot  = document.getElementById('sticky-enquire');
  const hero      = document.querySelector('.hero');
  const navToggle = document.querySelector('.nav-toggle');
  const mobileNav = document.getElementById('mobile-nav');

  if (navToggle && mobileNav) {
    const setOpen = (open) => {
      navToggle.setAttribute('aria-expanded', String(open));
      mobileNav.classList.toggle('open', open);
      mobileNav.setAttribute('aria-hidden', String(!open));
      document.body.style.overflow = open ? 'hidden' : '';
    };
    setOpen(false);

    navToggle.addEventListener('click', () => {
      const open = navToggle.getAttribute('aria-expanded') !== 'true';
      setOpen(open);
    });

    mobileNav.addEventListener('click', (e) => {
      if (e.target.tagName === 'A') setOpen(false);
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && navToggle.getAttribute('aria-expanded') === 'true') {
        setOpen(false);
        navToggle.focus();
      }
    });

    window.addEventListener('resize', () => {
      if (window.innerWidth > 760 && navToggle.getAttribute('aria-expanded') === 'true') {
        setOpen(false);
      }
    });
  }

  // Ensure sticky enquire exists on every page.
  if (!stickyRoot) {
    stickyRoot = document.createElement('div');
    stickyRoot.id = 'sticky-enquire';
    stickyRoot.className = 'sticky-enquire';
    stickyRoot.innerHTML = `
      <button class="sticky-btn" type="button" aria-expanded="false" aria-controls="enquiry-menu">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 2 11 13"/><path d="M22 2 15 22 11 13 2 9 22 2z"/></svg>
        Enquire Now
      </button>
    `;
    document.body.appendChild(stickyRoot);
  }

  const stickyTrigger = stickyRoot.querySelector('.sticky-btn');
  let enquiryMenu = stickyRoot.querySelector('#enquiry-menu');

  if (!enquiryMenu) {
    enquiryMenu = document.createElement('div');
    enquiryMenu.id = 'enquiry-menu';
    enquiryMenu.className = 'enquiry-menu';
    enquiryMenu.innerHTML = `
      <a class="enquiry-option" href="https://wa.me/9779867649780" target="_blank" rel="noopener">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 11.5a9.5 9.5 0 1 1-2.8-6.7A9.5 9.5 0 0 1 21 11.5Z"/><path d="m8.5 8.8 1.2-.4c.3-.1.6 0 .7.3l.6 1.4c.1.2 0 .5-.2.7l-.4.5a7 7 0 0 0 2.9 2.9l.5-.4c.2-.2.5-.2.7-.1l1.4.6c.3.1.4.4.3.7l-.4 1.2c-.1.3-.4.5-.8.5A8.4 8.4 0 0 1 7.9 10c0-.3.2-.6.6-.8Z"/></svg>
        WhatsApp
      </a>
      <a class="enquiry-option" href="mailto:info@discoverparbat.com?subject=Trek%20Enquiry&body=Hi%2C%0A%0AI%27m%20interested%20in%20trekking%20with%20Discover%20Parbat.%20Could%20you%20share%20more%20details%3F%0A%0AThank%20you.">
        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/></svg>
        Email
      </a>
    `;
    stickyRoot.appendChild(enquiryMenu);
  }

  if (stickyTrigger) {
    const closeMenu = () => {
      stickyTrigger.setAttribute('aria-expanded', 'false');
      enquiryMenu.classList.remove('open');
    };
    const toggleMenu = () => {
      const isOpen = stickyTrigger.getAttribute('aria-expanded') === 'true';
      stickyTrigger.setAttribute('aria-expanded', String(!isOpen));
      enquiryMenu.classList.toggle('open', !isOpen);
    };
    stickyTrigger.addEventListener('click', (e) => {
      e.preventDefault();
      toggleMenu();
    });
    document.addEventListener('click', (e) => {
      if (!stickyRoot.contains(e.target)) closeMenu();
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') closeMenu();
    });
  }

  const onScroll = () => {
    if (header) header.classList.toggle('scrolled', window.scrollY > 40);
    if (stickyRoot) {
      const heroH = hero ? hero.offsetHeight : 400;
      stickyRoot.classList.toggle('visible', window.scrollY > heroH * 0.6);
    }
  };
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();
})();

/* Inquiry submit + WhatsApp fallback when email send fails */
window.DiscoverParbat = window.DiscoverParbat || {};
const DP = window.DiscoverParbat;

DP.WHATSAPP_NUMBER = '9779867649780';

DP.buildWhatsAppUrl = function (text) {
  return 'https://wa.me/' + DP.WHATSAPP_NUMBER + '?text=' + encodeURIComponent(text);
};

DP.openWhatsAppFallback = function (message) {
  window.location.href = DP.buildWhatsAppUrl(message);
};

DP.submitInquiry = async function (payload, options) {
  const opts = options || {};
  const successUrl = opts.successUrl !== undefined ? opts.successUrl : 'success.html';

  try {
    const res = await fetch('inquiry.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
      body: payload.toString()
    });
    const data = await res.json();
    if (!res.ok || !data.ok) throw new Error(data.error || 'Send failed');

    if (typeof opts.onSuccess === 'function') {
      opts.onSuccess();
    } else if (successUrl) {
      window.location.href = successUrl;
    }
    return true;
  } catch (e) {
    if (opts.whatsappMessage) {
      DP.openWhatsAppFallback(opts.whatsappMessage);
    }
    return false;
  }
};

DP.formatCustomTrekMessage = function (d) {
  return [
    'Custom Trek Inquiry',
    '',
    'Name: ' + d.name,
    'Country: ' + d.country,
    'Preferred Trek: ' + (d.trek || 'Not specified'),
    'Preferred Duration: ' + d.days + ' days',
    'Preferred Start Date: ' + d.date,
    'No. of Pax: ' + d.pax,
    'Focus: ' + (d.focus || 'Not specified'),
    'Email: ' + (d.email || 'Not provided'),
    'WhatsApp: ' + (d.whatsapp || 'Not provided'),
    '',
    'Special Requirement:',
    d.note || 'None'
  ].join('\n');
};

DP.formatBookingMessage = function (d) {
  return [
    'Booking Inquiry',
    '',
    'Name: ' + d.name,
    'Country: ' + d.country,
    'Trek: ' + d.trek,
    'Start Date: ' + d.startDate,
    'No. of Pax: ' + d.pax,
    'Email: ' + (d.email || 'Not provided'),
    'WhatsApp: ' + (d.whatsapp || 'Not provided'),
    '',
    'Special Requirement:',
    d.special || 'None'
  ].join('\n');
};

DP.formatContactMessage = function (d) {
  const fullName = [d.fname, d.lname].filter(Boolean).join(' ').trim();
  return [
    'Contact Inquiry',
    '',
    'Name: ' + fullName,
    'Email: ' + d.email,
    'Country: ' + (d.country || 'Not provided'),
    'Trek: ' + (d.trek || 'Not specified'),
    'Group Size: ' + (d.group || 'Not specified'),
    'Preferred Start Date: ' + (d.date || 'Not specified'),
    '',
    'Message:',
    d.message || 'No additional message'
  ].join('\n');
};

DP.formatShopMessage = function (d) {
  return [
    'Shop Order Inquiry',
    '',
    'Name: ' + d.name,
    'Country: ' + d.country,
    'Photo Code: ' + d.itemCode,
    'Format: ' + d.format,
    'Quantity: ' + d.qty,
    'Email: ' + (d.email || 'Not provided'),
    'WhatsApp: ' + (d.whatsapp || 'Not provided'),
    '',
    'Interest / Note:',
    d.note || 'None'
  ].join('\n');
};
