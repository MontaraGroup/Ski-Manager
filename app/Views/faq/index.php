<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>FAQ - Frequently Asked Questions<?= $this->endSection() ?>

<?= $this->section('extra_head') ?>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    <?php
    $__faqSchemaItems = [];
    foreach (\App\Controllers\Faq::FAQS as [$__q, $__a]) {
        $__faqSchemaItems[] = json_encode([
            '@type' => 'Question',
            'name' => $__q,
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $__a,
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
    echo implode(",\n    ", $__faqSchemaItems);
    ?>
  ]
}
</script>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?= island('FaqPage', $page, '<p class="max-w-3xl mx-auto p-4 lg:p-8 text-sm">Loading FAQ…</p>') ?>
<?= $this->endSection() ?>
