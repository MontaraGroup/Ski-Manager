<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Emergency & Rescue<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-5xl mx-auto p-4 lg:p-8">
    <div class="flex items-center gap-3 mb-6">
        <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
        <h1 class="text-2xl font-bold"><i class="fa-solid fa-truck-medical mr-2 text-error"></i>Emergency & Rescue</h1>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4 text-center"><i class="fa-solid fa-shield-halved text-3xl text-error mb-2"></i><div class="font-semibold">Ski Patrol</div><div class="text-xs text-base-content/50">0 stations active</div><a href="/ski-patrol" class="btn btn-outline btn-xs mt-2">Manage</a></div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4 text-center"><i class="fa-solid fa-kit-medical text-3xl text-warning mb-2"></i><div class="font-semibold">Medical Staff</div><div class="text-xs text-base-content/50">0 medics hired</div><a href="/staff/hire" class="btn btn-outline btn-xs mt-2">Hire</a></div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4 text-center"><i class="fa-solid fa-shield-halved text-3xl text-info mb-2"></i><div class="font-semibold">Insurance</div><div class="text-xs text-base-content/50">0 policies active</div><a href="/insurance" class="btn btn-outline btn-xs mt-2">Manage</a></div></div>
    </div>
    <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
        <h2 class="font-semibold mb-3"><i class="fa-solid fa-triangle-exclamation mr-1 text-warning"></i>Recent Incidents</h2>
        <div class="text-center py-8 text-base-content/40"><i class="fa-solid fa-check-circle text-success text-2xl mb-2"></i><p class="text-sm">No incidents reported. Keep your patrol and medical coverage up!</p></div>
    </div></div>
</div>
<?= $this->endSection() ?>
