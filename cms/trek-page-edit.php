<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
cms_require_login();

$id = trim((string)($_GET['id'] ?? ''));
$trek = $id !== '' ? cms_get_trek_by_id($id) : null;

if ($trek === null) {
    cms_flash('error', 'Trek not found. Save listing details first.');
    header('Location: treks.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stats = [];
    for ($i = 0; $i < 4; $i++) {
        $value = cms_post_str('stat_value_' . $i);
        $label = cms_post_str('stat_label_' . $i);
        if ($value !== '' || $label !== '') {
            $stats[] = ['value' => $value, 'label' => $label];
        }
    }

    $itinerary = [];
    $days = $_POST['it_day'] ?? [];
    $titles = $_POST['it_title'] ?? [];
    $bodies = $_POST['it_body'] ?? [];
    $alts = $_POST['it_altitude'] ?? [];
    $dists = $_POST['it_distance'] ?? [];
    $times = $_POST['it_time'] ?? [];
    $stays = $_POST['it_stay'] ?? [];
    if (is_array($days)) {
        foreach ($days as $idx => $day) {
            $title = trim((string)($titles[$idx] ?? ''));
            $body = trim((string)($bodies[$idx] ?? ''));
            if ($title === '' && $body === '') {
                continue;
            }
            $meta = [];
            if (trim((string)($alts[$idx] ?? '')) !== '') {
                $meta['Altitude'] = trim((string)$alts[$idx]);
            }
            if (trim((string)($dists[$idx] ?? '')) !== '') {
                $meta['Distance'] = trim((string)$dists[$idx]);
            }
            if (trim((string)($times[$idx] ?? '')) !== '') {
                $meta['Time'] = trim((string)$times[$idx]);
            }
            if (trim((string)($stays[$idx] ?? '')) !== '') {
                $meta['Stay'] = trim((string)$stays[$idx]);
            }
            $itinerary[] = [
                'day' => max(1, (int)$day),
                'title' => $title,
                'paragraphs' => $body !== '' ? preg_split('/\n\s*\n/', $body) ?: [$body] : [],
                'meta' => $meta,
            ];
        }
    }

    $faqs = [];
    $questions = $_POST['faq_q'] ?? [];
    $answers = $_POST['faq_a'] ?? [];
    if (is_array($questions)) {
        foreach ($questions as $idx => $q) {
            $q = trim((string)$q);
            $a = trim((string)($answers[$idx] ?? ''));
            if ($q === '' && $a === '') {
                continue;
            }
            $faqs[] = ['question' => $q, 'answer' => $a];
        }
    }

    $priceRows = [];
    $groups = $_POST['price_group'] ?? [];
    $prices = $_POST['price_amount'] ?? [];
    if (is_array($groups)) {
        foreach ($groups as $idx => $group) {
            $group = trim((string)$group);
            $price = trim((string)($prices[$idx] ?? ''));
            if ($group === '' && $price === '') {
                continue;
            }
            $priceRows[] = ['group' => $group, 'price' => $price];
        }
    }

    $overviewRaw = cms_post_str('overview_paragraphs');
    $overviewParagraphs = $overviewRaw !== ''
        ? preg_split('/\n\s*\n/', $overviewRaw) ?: cms_lines_to_array($overviewRaw)
        : [];

    $page = [
        'meta_title' => cms_post_str('meta_title'),
        'meta_description' => cms_post_str('meta_description'),
        'meta_keywords' => cms_post_str('meta_keywords'),
        'hero_image' => cms_post_str('hero_image'),
        'hero_eyebrow' => cms_post_str('hero_eyebrow'),
        'hero_title_html' => cms_post_str('hero_title_html'),
        'hero_subtitle' => cms_post_str('hero_subtitle'),
        'stats' => $stats,
        'gallery' => cms_lines_to_array(cms_post_str('gallery')),
        'overview_title' => cms_post_str('overview_title'),
        'overview_paragraphs' => array_values(array_filter(array_map('trim', $overviewParagraphs ?: []))),
        'highlights' => cms_lines_to_array(cms_post_str('highlights')),
        'right_for_you_yes' => cms_lines_to_array(cms_post_str('right_for_you_yes')),
        'right_for_you_no' => cms_lines_to_array(cms_post_str('right_for_you_no')),
        'itinerary' => $itinerary,
        'faqs' => $faqs,
        'pricing' => [
            'rows' => $priceRows,
            'included' => cms_lines_to_array(cms_post_str('included')),
            'excluded' => cms_lines_to_array(cms_post_str('excluded')),
        ],
        'booking_trek_name' => cms_post_str('booking_trek_name'),
    ];

    $trek['cms_page'] = cms_post_bool('cms_page');
    $trek['page'] = $page;

    cms_save_trek($trek);
    cms_flash('success', 'Trek page content saved.');
    header('Location: trek-page-edit.php?id=' . urlencode($id));
    exit;
}

$page = cms_merge_trek_page(is_array($trek['page'] ?? null) ? $trek['page'] : null);
$stats = $page['stats'];
while (count($stats) < 4) {
    $stats[] = ['value' => '', 'label' => ''];
}

ob_start();
?>
<p class="cms-help" style="margin-bottom:18px;">
  Editing full page for <strong><?= cms_h((string)$trek['title']) ?></strong>.
  <a href="trek-edit.php?id=<?= urlencode($id) ?>">← Back to listing</a>
  <?php if (!empty($trek['published']) && !empty($trek['cms_page'])): ?>
    · <a href="/<?= cms_h((string)$trek['slug']) ?>" target="_blank" rel="noopener">View live page</a>
  <?php endif; ?>
</p>

<form method="post" class="cms-form cms-trek-page-form">
  <div class="cms-field cms-check">
    <label><input type="checkbox" name="cms_page" value="1" <?= !empty($trek['cms_page']) ? 'checked' : '' ?>> Use CMS for this trek page (when enabled, /<?= cms_h((string)$trek['slug']) ?> is served from CMS)</label>
  </div>

  <fieldset class="cms-fieldset">
    <legend>SEO</legend>
    <div class="cms-field">
      <label for="meta_title">Page title</label>
      <input id="meta_title" name="meta_title" value="<?= cms_h((string)$page['meta_title']) ?>">
    </div>
    <div class="cms-field">
      <label for="meta_description">Meta description</label>
      <textarea id="meta_description" name="meta_description" rows="3"><?= cms_h((string)$page['meta_description']) ?></textarea>
    </div>
    <div class="cms-field">
      <label for="meta_keywords">Meta keywords</label>
      <textarea id="meta_keywords" name="meta_keywords" rows="2"><?= cms_h((string)$page['meta_keywords']) ?></textarea>
    </div>
  </fieldset>

  <fieldset class="cms-fieldset">
    <legend>Hero</legend>
    <?php
    $imageInputId = 'hero_image';
    $imageLabel = 'Hero image';
    $imageValue = (string)($page['hero_image'] !== '' ? $page['hero_image'] : ($trek['image'] ?? ''));
    include __DIR__ . '/includes/image-field.php';
    ?>
    <div class="cms-grid-2">
      <div class="cms-field">
        <label for="hero_eyebrow">Eyebrow text</label>
        <input id="hero_eyebrow" name="hero_eyebrow" value="<?= cms_h((string)$page['hero_eyebrow']) ?>" placeholder="Parbat, Nepal · 5 Days">
      </div>
      <div class="cms-field">
        <label for="booking_trek_name">Booking form trek name</label>
        <input id="booking_trek_name" name="booking_trek_name" value="<?= cms_h((string)$page['booking_trek_name']) ?>" placeholder="<?= cms_h((string)$trek['title']) ?>">
      </div>
    </div>
    <div class="cms-field">
      <label for="hero_title_html">Hero title (use &lt;br&gt; for line break)</label>
      <input id="hero_title_html" name="hero_title_html" value="<?= cms_h((string)$page['hero_title_html']) ?>">
    </div>
    <div class="cms-field">
      <label for="hero_subtitle">Hero subtitle</label>
      <input id="hero_subtitle" name="hero_subtitle" value="<?= cms_h((string)$page['hero_subtitle']) ?>">
    </div>
  </fieldset>

  <fieldset class="cms-fieldset">
    <legend>Quick stats</legend>
    <div class="cms-grid-2">
      <?php for ($i = 0; $i < 4; $i++): ?>
        <div class="cms-field">
          <label>Stat <?= $i + 1 ?> value</label>
          <input name="stat_value_<?= $i ?>" value="<?= cms_h((string)($stats[$i]['value'] ?? '')) ?>">
        </div>
        <div class="cms-field">
          <label>Stat <?= $i + 1 ?> label</label>
          <input name="stat_label_<?= $i ?>" value="<?= cms_h((string)($stats[$i]['label'] ?? '')) ?>">
        </div>
      <?php endfor; ?>
    </div>
  </fieldset>

  <fieldset class="cms-fieldset">
    <legend>Photo gallery</legend>
    <div class="cms-field">
      <label for="gallery">Gallery images (one filename per line — shown in carousel on trek page)</label>
      <textarea id="gallery" name="gallery" rows="6"><?= cms_h(implode("\n", (array)$page['gallery'])) ?></textarea>
    </div>
  </fieldset>

  <fieldset class="cms-fieldset">
    <legend>Overview</legend>
    <div class="cms-field">
      <label for="overview_title">Section title</label>
      <input id="overview_title" name="overview_title" value="<?= cms_h((string)$page['overview_title']) ?>">
    </div>
    <div class="cms-field">
      <label for="overview_paragraphs">Paragraphs (blank line between paragraphs)</label>
      <textarea id="overview_paragraphs" name="overview_paragraphs" rows="10"><?= cms_h(implode("\n\n", (array)$page['overview_paragraphs'])) ?></textarea>
    </div>
    <div class="cms-field">
      <label for="highlights">Highlights (one per line)</label>
      <textarea id="highlights" name="highlights" rows="6"><?= cms_h(implode("\n", (array)$page['highlights'])) ?></textarea>
    </div>
    <div class="cms-grid-2">
      <div class="cms-field">
        <label for="right_for_you_yes">Right for you — yes (one per line)</label>
        <textarea id="right_for_you_yes" name="right_for_you_yes" rows="6"><?= cms_h(implode("\n", (array)$page['right_for_you_yes'])) ?></textarea>
      </div>
      <div class="cms-field">
        <label for="right_for_you_no">Right for you — no (one per line)</label>
        <textarea id="right_for_you_no" name="right_for_you_no" rows="4"><?= cms_h(implode("\n", (array)$page['right_for_you_no'])) ?></textarea>
      </div>
    </div>
  </fieldset>

  <fieldset class="cms-fieldset">
    <legend>Itinerary</legend>
    <div id="itinerary-rows" class="cms-repeatable">
      <?php
      $itinerary = (array)($page['itinerary'] ?? []);
      if ($itinerary === []) {
          $itinerary = [['day' => 1, 'title' => '', 'paragraphs' => [], 'meta' => []]];
      }
      foreach ($itinerary as $row):
          $body = implode("\n\n", (array)($row['paragraphs'] ?? []));
          $meta = is_array($row['meta'] ?? null) ? $row['meta'] : [];
      ?>
        <div class="cms-repeat-row" data-type="itinerary">
          <div class="cms-grid-2">
            <div class="cms-field"><label>Day</label><input name="it_day[]" type="number" min="1" value="<?= (int)($row['day'] ?? 1) ?>"></div>
            <div class="cms-field"><label>Title</label><input name="it_title[]" value="<?= cms_h((string)($row['title'] ?? '')) ?>"></div>
          </div>
          <div class="cms-field"><label>Description (blank line between paragraphs)</label><textarea name="it_body[]" rows="4"><?= cms_h($body) ?></textarea></div>
          <div class="cms-grid-2">
            <div class="cms-field"><label>Altitude</label><input name="it_altitude[]" value="<?= cms_h((string)($meta['Altitude'] ?? '')) ?>"></div>
            <div class="cms-field"><label>Distance</label><input name="it_distance[]" value="<?= cms_h((string)($meta['Distance'] ?? '')) ?>"></div>
            <div class="cms-field"><label>Time</label><input name="it_time[]" value="<?= cms_h((string)($meta['Time'] ?? '')) ?>"></div>
            <div class="cms-field"><label>Stay</label><input name="it_stay[]" value="<?= cms_h((string)($meta['Stay'] ?? '')) ?>"></div>
          </div>
          <button type="button" class="cms-btn cms-btn-ghost cms-remove-row">Remove day</button>
        </div>
      <?php endforeach; ?>
    </div>
    <button type="button" class="cms-btn cms-btn-ghost" data-add-row="itinerary">+ Add day</button>
  </fieldset>

  <fieldset class="cms-fieldset">
    <legend>FAQ</legend>
    <div id="faq-rows" class="cms-repeatable">
      <?php
      $faqs = (array)($page['faqs'] ?? []);
      if ($faqs === []) {
          $faqs = [['question' => '', 'answer' => '']];
      }
      foreach ($faqs as $faq):
      ?>
        <div class="cms-repeat-row" data-type="faq">
          <div class="cms-field"><label>Question</label><input name="faq_q[]" value="<?= cms_h((string)($faq['question'] ?? '')) ?>"></div>
          <div class="cms-field"><label>Answer (HTML links allowed)</label><textarea name="faq_a[]" rows="3"><?= cms_h((string)($faq['answer'] ?? '')) ?></textarea></div>
          <button type="button" class="cms-btn cms-btn-ghost cms-remove-row">Remove FAQ</button>
        </div>
      <?php endforeach; ?>
    </div>
    <button type="button" class="cms-btn cms-btn-ghost" data-add-row="faq">+ Add FAQ</button>
  </fieldset>

  <fieldset class="cms-fieldset">
    <legend>Pricing &amp; inclusions</legend>
    <div id="price-rows" class="cms-repeatable">
      <?php
      $priceRows = (array)($page['pricing']['rows'] ?? []);
      if ($priceRows === []) {
          $priceRows = [['group' => '', 'price' => '']];
      }
      foreach ($priceRows as $row):
      ?>
        <div class="cms-repeat-row cms-repeat-row-inline" data-type="price">
          <div class="cms-field"><label>Group size</label><input name="price_group[]" value="<?= cms_h((string)($row['group'] ?? $row['label'] ?? '')) ?>"></div>
          <div class="cms-field"><label>Price</label><input name="price_amount[]" value="<?= cms_h((string)($row['price'] ?? '')) ?>"></div>
          <button type="button" class="cms-btn cms-btn-ghost cms-remove-row">Remove</button>
        </div>
      <?php endforeach; ?>
    </div>
    <button type="button" class="cms-btn cms-btn-ghost" data-add-row="price">+ Add price row</button>
    <div class="cms-grid-2" style="margin-top:16px;">
      <div class="cms-field">
        <label for="included">What's included (one per line)</label>
        <textarea id="included" name="included" rows="6"><?= cms_h(implode("\n", (array)($page['pricing']['included'] ?? []))) ?></textarea>
      </div>
      <div class="cms-field">
        <label for="excluded">What's not included (one per line)</label>
        <textarea id="excluded" name="excluded" rows="6"><?= cms_h(implode("\n", (array)($page['pricing']['excluded'] ?? []))) ?></textarea>
      </div>
    </div>
  </fieldset>

  <div class="cms-form-actions">
    <button class="cms-btn cms-btn-primary" type="submit">Save page content</button>
    <a class="cms-btn cms-btn-ghost" href="treks.php">Cancel</a>
  </div>
</form>

<template id="tpl-itinerary">
  <div class="cms-repeat-row" data-type="itinerary">
    <div class="cms-grid-2">
      <div class="cms-field"><label>Day</label><input name="it_day[]" type="number" min="1" value="1"></div>
      <div class="cms-field"><label>Title</label><input name="it_title[]" value=""></div>
    </div>
    <div class="cms-field"><label>Description</label><textarea name="it_body[]" rows="4"></textarea></div>
    <div class="cms-grid-2">
      <div class="cms-field"><label>Altitude</label><input name="it_altitude[]" value=""></div>
      <div class="cms-field"><label>Distance</label><input name="it_distance[]" value=""></div>
      <div class="cms-field"><label>Time</label><input name="it_time[]" value=""></div>
      <div class="cms-field"><label>Stay</label><input name="it_stay[]" value=""></div>
    </div>
    <button type="button" class="cms-btn cms-btn-ghost cms-remove-row">Remove day</button>
  </div>
</template>

<template id="tpl-faq">
  <div class="cms-repeat-row" data-type="faq">
    <div class="cms-field"><label>Question</label><input name="faq_q[]" value=""></div>
    <div class="cms-field"><label>Answer</label><textarea name="faq_a[]" rows="3"></textarea></div>
    <button type="button" class="cms-btn cms-btn-ghost cms-remove-row">Remove FAQ</button>
  </div>
</template>

<template id="tpl-price">
  <div class="cms-repeat-row cms-repeat-row-inline" data-type="price">
    <div class="cms-field"><label>Group size</label><input name="price_group[]" value=""></div>
    <div class="cms-field"><label>Price</label><input name="price_amount[]" value=""></div>
    <button type="button" class="cms-btn cms-btn-ghost cms-remove-row">Remove</button>
  </div>
</template>

<script src="assets/trek-editor.js" defer></script>
<?php
cms_page('Edit trek page: ' . (string)$trek['title'], (string)ob_get_clean(), 'treks');
