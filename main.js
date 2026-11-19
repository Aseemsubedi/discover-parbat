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

/* GA4 enquiry tracking
   - "generate_lead": only when an inquiry form email was actually sent (confirmed lead)
   - "contact_click": WhatsApp / email / phone link clicks and the WhatsApp fallback
     (the visitor may not press send, so these are not counted as leads) */
DP.trackEvent = function (name, method, extra) {
  if (typeof gtag !== 'function') return;
  const params = Object.assign({ method: method, transport_type: 'beacon' }, extra || {});
  gtag('event', name, params);
};

DP.trackLead = function (method, extra) {
  DP.trackEvent('generate_lead', method, extra);
};

DP.trackContactClick = function (method, extra) {
  DP.trackEvent('contact_click', method, extra);
};

document.addEventListener('click', function (e) {
  const a = e.target.closest ? e.target.closest('a') : null;
  if (!a) return;
  const href = a.getAttribute('href') || '';
  if (/wa\.me|api\.whatsapp\.com|^whatsapp:/i.test(href)) DP.trackContactClick('whatsapp');
  else if (href.indexOf('mailto:') === 0) DP.trackContactClick('email');
  else if (href.indexOf('tel:') === 0) DP.trackContactClick('phone');
});

DP.buildWhatsAppUrl = function (text) {
  return 'https://wa.me/' + DP.WHATSAPP_NUMBER + '?text=' + encodeURIComponent(text);
};

DP.openWhatsAppFallback = function (message) {
  DP.trackContactClick('whatsapp_fallback');
  window.location.href = DP.buildWhatsAppUrl(message);
};

/* Submitting state: spinner + "Sending..." on the form's submit button while
   inquiry.php is working, so visitors don't think nothing happened. */
(function () {
  const style = document.createElement('style');
  style.textContent =
    '.dp-sending{opacity:.8;cursor:wait!important;pointer-events:none}' +
    '.dp-spinner{display:inline-block;width:1em;height:1em;margin-right:.5em;vertical-align:-.15em;' +
    'border:2px solid currentColor;border-right-color:transparent;border-radius:50%;' +
    'animation:dp-spin .7s linear infinite}' +
    '@keyframes dp-spin{to{transform:rotate(360deg)}}';
  document.head.appendChild(style);
})();

/* Remember which form is being submitted (forms call submitInquiry from onsubmit) */
document.addEventListener('submit', function (e) { DP._activeForm = e.target; }, true);

DP.setSending = function (el, sending) {
  if (!el) return;
  const btns = (el.tagName === 'BUTTON' || el.tagName === 'INPUT')
    ? [el]
    : Array.prototype.slice.call(el.querySelectorAll('button[type="submit"], input[type="submit"]'));
  btns.forEach(function (btn) {
    if (sending) {
      if (btn.dataset.dpLabel === undefined) btn.dataset.dpLabel = btn.innerHTML;
      btn.disabled = true;
      btn.classList.add('dp-sending');
      btn.setAttribute('aria-busy', 'true');
      if (btn.tagName === 'INPUT') btn.value = 'Sending...';
      else btn.innerHTML = '<span class="dp-spinner" aria-hidden="true"></span>Sending your enquiry...';
    } else if (btn.dataset.dpLabel !== undefined) {
      btn.disabled = false;
      btn.classList.remove('dp-sending');
      btn.removeAttribute('aria-busy');
      if (btn.tagName === 'INPUT') btn.value = btn.dataset.dpLabel;
      else btn.innerHTML = btn.dataset.dpLabel;
      delete btn.dataset.dpLabel;
    }
  });
};

/* Reset buttons if the visitor comes back with the browser Back button */
window.addEventListener('pageshow', function () {
  document.querySelectorAll('.dp-sending').forEach(function (b) { DP.setSending(b, false); });
  document.querySelectorAll('[data-dp-busy]').forEach(function (f) { delete f.dataset.dpBusy; });
});

DP.submitInquiry = async function (payload, options) {
  const opts = options || {};
  const successUrl = opts.successUrl !== undefined ? opts.successUrl : '/success';
  /* opts.button for click-submitted forms (contact page), else the form being submitted */
  const form = opts.button || opts.form || DP._activeForm || null;
  DP._activeForm = null;

  if (form && form.dataset.dpBusy === '1') return false; /* block double submits */
  if (form) form.dataset.dpBusy = '1';
  DP.setSending(form, true);

  const done = function () {
    if (form) delete form.dataset.dpBusy;
    DP.setSending(form, false);
  };

  try {
    const res = await fetch('inquiry.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
      body: payload.toString()
    });
    const data = await res.json();
    if (!res.ok || !data.ok) throw new Error(data.error || 'Send failed');

    DP.trackLead('form', { form_type: payload.get('type') || 'inquiry' });

    if (typeof opts.onSuccess === 'function') {
      done();
      opts.onSuccess();
    } else if (successUrl) {
      /* keep the spinner showing until the success page loads */
      window.location.href = successUrl;
    } else {
      done();
    }
    return true;
  } catch (e) {
    done();
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
