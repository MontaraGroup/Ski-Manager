<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Ski Lessons<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-5xl mx-auto p-4 lg:p-8">
    <div class="flex items-center gap-3 mb-6">
        <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
        <h1 class="text-2xl font-bold"><i class="fa-solid fa-chalkboard-user mr-2 text-info"></i>Ski School</h1>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4 text-center"><div class="text-3xl font-bold">0</div><div class="text-xs text-base-content/50">Instructors</div></div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4 text-center"><div class="text-3xl font-bold">0</div><div class="text-xs text-base-content/50">Lessons Today</div></div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4 text-center"><div class="text-3xl font-bold text-success"><?= currency(0) ?></div><div class="text-xs text-base-content/50">Lesson Revenue</div></div></div>
    </div>
    <div class="card bg-base-100 shadow-sm mb-6"><div class="card-body p-4">
        <h2 class="font-semibold mb-3">Lesson Types</h2>
        <div class="overflow-x-auto"><table class="table table-sm">
            <thead><tr><th>Lesson</th><th>Duration</th><th>Price</th><th>Instructor Needed</th><th>Max Students</th></tr></thead>
            <tbody>
                <tr><td><i class="fa-solid fa-person-skiing mr-1 text-success"></i>Beginner Group</td><td>2 hours</td><td><?= currency(40) ?></td><td>1</td><td>8</td></tr>
                <tr><td><i class="fa-solid fa-person-skiing mr-1 text-info"></i>Intermediate Group</td><td>2 hours</td><td><?= currency(50) ?></td><td>1</td><td>6</td></tr>
                <tr><td><i class="fa-solid fa-person-skiing mr-1 text-warning"></i>Advanced Group</td><td>3 hours</td><td><?= currency(70) ?></td><td>1</td><td>4</td></tr>
                <tr><td><i class="fa-solid fa-user mr-1 text-primary"></i>Private Lesson</td><td>1 hour</td><td><?= currency(120) ?></td><td>1</td><td>1</td></tr>
                <tr><td><i class="fa-solid fa-child mr-1 text-error"></i>Kids Camp</td><td>4 hours</td><td><?= currency(60) ?></td><td>2</td><td>10</td></tr>
            </tbody>
        </table></div>
    </div></div>
    <div class="alert alert-info"><i class="fa-solid fa-info-circle"></i><span><a href="/staff/hire" class="link font-semibold">Hire instructors</a> to offer ski lessons. Each instructor can teach one lesson at a time.</span></div>
</div>
<?= $this->endSection() ?>
