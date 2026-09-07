<?php

/** @var array<string, mixed> $portfolio */
/** @var array<int, array<string, mixed>> $pipeline */
/** @var array<int, array<string, mixed>> $recentListings */
/** @var array<string, mixed> $content */

$hero = isset($content['hero']) ? $content['hero'] : array();
$heroEyebrow = isset($hero['eyebrow']) ? $hero['eyebrow'] : 'Rental manager';
$heroTitle = isset($hero['title']) ? $hero['title'] : 'Manage your rental portfolio';
$heroDescription = isset($hero['description']) ? $hero['description'] : 'Track applications, monitor pipeline, and manage your properties in one place.';
$occupiedUnits = isset($portfolio['occupiedUnits']) ? (int) $portfolio['occupiedUnits'] : 0;
$vacantUnits = isset($portfolio['vacantUnits']) ? (int) $portfolio['vacantUnits'] : 0;
$monthlyCollected = isset($portfolio['monthlyCollected']) ? $portfolio['monthlyCollected'] : app_currency(0);
$openMaintenance = isset($portfolio['openMaintenance']) ? (int) $portfolio['openMaintenance'] : 0;
?>
<section class="page-hero compact">
    <div>
        <span class="eyebrow"><?= htmlspecialchars((string) $heroEyebrow, ENT_QUOTES, 'UTF-8') ?></span>
        <h1><?= htmlspecialchars((string) $heroTitle, ENT_QUOTES, 'UTF-8') ?></h1>
        <p><?= htmlspecialchars((string) $heroDescription, ENT_QUOTES, 'UTF-8') ?></p>
    </div>
</section>

<section class="manager-stats" style="margin:24px 0">
    <article>
        <span>Occupied units</span>
        <strong><?= $occupiedUnits ?></strong>
    </article>
    <article>
        <span>Vacant units</span>
        <strong><?= $vacantUnits ?></strong>
    </article>
    <article>
        <span>Collected this month</span>
        <strong><?= htmlspecialchars((string) $monthlyCollected, ENT_QUOTES, 'UTF-8') ?></strong>
    </article>
    <article>
        <span>Open maintenance</span>
        <strong><?= $openMaintenance ?></strong>
    </article>
</section>

<section class="manager-layout">
    <div class="pipeline-board">
        <div class="section-heading">
            <div>
                <span class="eyebrow">Pipeline</span>
                <h2>Application and tenancy stages</h2>
            </div>
        </div>

        <?php if ($pipeline !== array()): ?>
            <div class="pipeline-grid">
                <?php foreach ($pipeline as $stage): ?>
                    <article class="pipeline-card">
                        <div class="pipeline-head">
                            <h3><?= htmlspecialchars((string) $stage['stage'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <span class="type-pill"><?= (int) $stage['count'] ?></span>
                        </div>
                        <p><?= htmlspecialchars((string) $stage['description'], ENT_QUOTES, 'UTF-8') ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <article class="empty-state">
                <h2>No pipeline activity yet.</h2>
                <p>Applications and active tenancy counts will appear here as renters move through the platform.</p>
            </article>
        <?php endif; ?>

        <?php if ($recentListings !== array()): ?>
            <div class="section-heading" style="margin-top:32px">
                <div>
                    <span class="eyebrow">Recent listings</span>
                    <h2>Latest portfolio inventory</h2>
                </div>
                <a class="ghost-button" href="<?= htmlspecialchars(app_url('admin'), ENT_QUOTES, 'UTF-8') ?>">Manage all</a>
            </div>

            <div class="recent-list">
                <?php foreach ($recentListings as $listing): ?>
                    <article class="recent-card">
                        <img src="<?= htmlspecialchars((string) $listing['image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) $listing['title'], ENT_QUOTES, 'UTF-8') ?>">
                        <div>
                            <h3><?= htmlspecialchars((string) $listing['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <p><?= htmlspecialchars((string) $listing['price'], ENT_QUOTES, 'UTF-8') ?> | <?= htmlspecialchars((string) $listing['location'], ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                        <a class="ghost-button" href="<?= htmlspecialchars(app_url('property', array('id' => $listing['id'])), ENT_QUOTES, 'UTF-8') ?>">View</a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <aside class="manager-sidebar">
        <div class="detail-card">
            <span class="eyebrow">Quick actions</span>
            <div style="display:grid; gap:10px; margin-top:14px">
                <a class="solid-button" href="<?= htmlspecialchars(app_url('admin'), ENT_QUOTES, 'UTF-8') ?>" style="justify-content:center">Open admin console</a>
                <a class="ghost-button" href="<?= htmlspecialchars(app_url('rentals'), ENT_QUOTES, 'UTF-8') ?>" style="justify-content:center">Browse rental market</a>
            </div>
        </div>

        <div class="detail-card" style="margin-top:16px">
            <span class="eyebrow">Resources</span>
            <ul class="feature-list" style="margin-top:12px">
                <li>Keep listing photos current to improve lead quality.</li>
                <li>Respond to submitted applications quickly to reduce drop-off.</li>
                <li>Update availability dates as soon as a unit is taken.</li>
                <li>Use the admin console to keep pricing, service charges, and gallery images accurate.</li>
            </ul>
        </div>
    </aside>
</section>
