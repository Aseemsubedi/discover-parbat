<?php
declare(strict_types=1);

/**
 * Open Graph + Twitter Card tags for link previews.
 *
 * @var string $socialTitle
 * @var string $socialDescription
 * @var string $socialUrl
 * @var string $socialImage Absolute image URL
 * @var string $socialType website|article
 * @var string $socialSiteName
 */
$socialSiteName = $socialSiteName ?? 'Discover Parbat';
$socialType = $socialType ?? 'website';
?>
<!-- Social sharing -->
<meta property="og:type" content="<?= cms_h($socialType) ?>">
<meta property="og:site_name" content="<?= cms_h($socialSiteName) ?>">
<meta property="og:title" content="<?= cms_h($socialTitle) ?>">
<meta property="og:description" content="<?= cms_h($socialDescription) ?>">
<meta property="og:url" content="<?= cms_h($socialUrl) ?>">
<meta property="og:image" content="<?= cms_h($socialImage) ?>">
<meta property="og:image:secure_url" content="<?= cms_h($socialImage) ?>">
<meta property="og:image:width" content="<?= cms_h((string)($socialImageWidth ?? '1200')) ?>">
<meta property="og:image:height" content="<?= cms_h((string)($socialImageHeight ?? '630')) ?>">
<meta property="og:image:alt" content="<?= cms_h($socialImageAlt ?? $socialTitle) ?>">
<meta property="og:locale" content="en_US">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= cms_h($socialTitle) ?>">
<meta name="twitter:description" content="<?= cms_h($socialDescription) ?>">
<meta name="twitter:image" content="<?= cms_h($socialImage) ?>">
<meta name="twitter:image:alt" content="<?= cms_h($socialImageAlt ?? $socialTitle) ?>">
<meta name="theme-color" content="#1a3d2b">
<?php if ($socialType === 'article' && !empty($socialPublishedAt)): ?>
<meta property="article:published_time" content="<?= cms_h((string)$socialPublishedAt) ?>">
<?php endif; ?>
