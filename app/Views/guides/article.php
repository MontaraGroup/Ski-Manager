<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?><?= esc($page['title'] ?? 'Guide') ?><?= $this->endSection() ?>

<?= $this->section('extra_head') ?>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Article",
  "headline": <?= json_encode($page['title'] ?? 'Ski Manager Guide') ?>,
  "description": <?= json_encode($page['summary'] ?? '') ?>,
  "url": <?= json_encode('https://ski-manager.net/guides/' . ($page['slug'] ?? '')) ?>,
  "image": "https://ski-manager.net/og-image.png",
  "author": {
    "@type": "Organization",
    "name": "Ski Manager",
    "url": "https://ski-manager.net"
  },
  "publisher": {
    "@type": "Organization",
    "name": "Ski Manager",
    "url": "https://ski-manager.net",
    "logo": {
      "@type": "ImageObject",
      "url": "https://ski-manager.net/apple-touch-icon.png"
    }
  },
  "mainEntityOfPage": {
    "@type": "WebPage",
    "@id": <?= json_encode('https://ski-manager.net/guides/' . ($page['slug'] ?? '')) ?>
  }
}
</script>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?= island('GuideArticlePage', $page, '<p class="max-w-3xl mx-auto p-4 lg:p-8 text-sm">Loading guide article…</p>') ?>
<?= $this->endSection() ?>
