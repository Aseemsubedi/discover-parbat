<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/lib/countries.php';

foreach (cms_countries() as $country): ?>
  <option><?= cms_h($country) ?></option>
<?php endforeach; ?>
