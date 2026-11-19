<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Trail Map<?= $this->endSection() ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="/css/leaflet.css" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<?php $__medWebp = \App\Controllers\ResortMap::webpSizes((string) $mapConfig['image'])['med']; ?>
<link rel="preload" href="<?= esc(str_replace('.jpg', $__medWebp ? '_med.webp' : '_med.jpg', $mapConfig['image'])) ?>" as="image" fetchpriority="high"<?= $__medWebp ? ' type="image/webp"' : '' ?>>
<script data-cfasync="false" src="/js/leaflet.js"></script>

<?= island('ResortMapPage', $page, '<p class="p-8 text-center text-sm">Loading trail map…</p>') ?>
<?= $this->endSection() ?>
