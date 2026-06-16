<?php
declare(strict_types=1);

/** @var string $bookingTrekName */
/** @var list<array> $allTreks */
?>
<div class="booking-card">
  <div class="booking-card-header">
    <h3>Book This Trek</h3>
    <p>Share your details and we will send a tailored plan and confirmation options quickly.</p>
  </div>
  <form class="booking-form" id="booking-form" onsubmit="handleBookingSubmit(event)">
    <div class="booking-grid">
      <div class="booking-field full">
        <label for="bk-name">Full Name *</label>
        <input id="bk-name" name="name" type="text" placeholder="Your full name" required>
      </div>

      <div class="booking-field full">
        <label for="bk-country">Country *</label>
        <select id="bk-country" name="country" required>
          <option value="" selected disabled>Select your country</option>
          <?php include __DIR__ . '/country-options.php'; ?>
        </select>
      </div>

      <div class="booking-field">
        <label for="bk-email">Gmail (optional)</label>
        <input id="bk-email" name="email" type="email" placeholder="you@gmail.com">
      </div>

      <div class="booking-field">
        <label for="bk-whatsapp">WhatsApp (optional)</label>
        <input id="bk-whatsapp" name="whatsapp" type="tel" placeholder="+977 98XXXXXXXX">
      </div>

      <div class="booking-field">
        <label for="bk-trek">Trekking Name *</label>
        <select id="bk-trek" name="trek" required>
          <option value="<?= cms_h($bookingTrekName) ?>" selected><?= cms_h($bookingTrekName) ?></option>
          <option value="Not sure yet">Not sure yet</option>
          <?php foreach ($allTreks as $t): ?>
            <?php $tTitle = (string)($t['title'] ?? ''); ?>
            <?php if ($tTitle === '' || $tTitle === $bookingTrekName) continue; ?>
            <option value="<?= cms_h($tTitle) ?>"><?= cms_h($tTitle) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="booking-field">
        <label for="bk-date">Start Date *</label>
        <input id="bk-date" name="startDate" type="date" required>
      </div>

      <div class="booking-field">
        <label for="bk-pax">No. of Pax *</label>
        <select id="bk-pax" name="pax" required>
          <option value="2" selected>2</option>
          <option value="1">1</option>
          <option value="3">3</option>
          <option value="4">4</option>
          <option value="5">5</option>
          <option value="6">6</option>
          <option value="7">7</option>
          <option value="8">8</option>
          <option value="9">9</option>
          <option value="10+">10+</option>
          <option value="Not sure yet">Not sure yet</option>
        </select>
      </div>

      <div class="booking-field full">
        <label for="bk-special">Special Requirement</label>
        <textarea id="bk-special" name="special" placeholder="Dietary preference, altitude concerns, private trip request, room type, pickup details, etc."></textarea>
      </div>
    </div>

    <p class="booking-note">Please provide at least one contact option (Gmail or WhatsApp). You can choose "Not sure yet" for trek or pax if you are still deciding.</p>

    <div class="booking-actions">
      <button type="submit" class="booking-submit">Send Booking Request</button>
      <p class="booking-assist">Prefer direct contact? <a href="/contact">Use full contact form</a></p>
    </div>
  </form>
</div>
