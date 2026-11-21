<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Trail Map<?= $this->endSection() ?>

<?= $this->section('content') ?>
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<?php if (!empty($mapConfig['tiles'])): ?>
<?php /* With tiles the first paint is the small image under them, not the 1.3 MB medium one */ $__lowWebp = !empty($mapConfig['webp']['low']); ?>
<link rel="preload" href="<?= esc(str_replace('.jpg', $__lowWebp ? '_low.webp' : '_low.jpg', $mapConfig['image'])) ?>" as="image" fetchpriority="high"<?= $__lowWebp ? ' type="image/webp"' : '' ?>>
<?php else: ?>
<?php $__medWebp = \App\Controllers\ResortMap::webpSizes((string) $mapConfig['image'])['med']; ?>
<link rel="preload" href="<?= esc(str_replace('.jpg', $__medWebp ? '_med.webp' : '_med.jpg', $mapConfig['image'])) ?>" as="image" fetchpriority="high"<?= $__medWebp ? ' type="image/webp"' : '' ?>>
<?php endif; ?>

<?= island('ResortMapPage', $page, '<p class="p-8 text-center text-sm">Loading trail map…</p>') ?>
<?= $this->endSection() ?>
