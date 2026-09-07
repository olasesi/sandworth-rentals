<?php
/** @var array $applications */
/** @var array $tourRequests */
/** @var array|null $tenancy */
?>
<section class="page-hero compact">
    <div>
        <span class="eyebrow">My dashboard</span>
        <h1>Your Sandworth-Homes dashboard for tours, applications, and tenancy.</h1>
        <p>Track every stage of your rental journey from tour booking to approved move-in.</p>
    </div>
</section>

<?php if ($tenancy): ?>
    <div class="detail-card" style="margin:24px 0; border-left:4px solid var(--green); border-radius:0 var(--r-lg) var(--r-lg) 0; display:flex; align-items:center; justify-content:space-between; gap:20px; flex-wrap:wrap">
        <div>
            <span class="eyebrow">Active tenancy</span>
            <h2 style="margin:4px 0 6px; font-size:1.1rem"><?= htmlspecialchars((string) $tenancy['property']['title'], ENT_QUOTES, 'UTF-8') ?></h2>
            <p style="font-size:.875rem; color:var(--muted); margin:0">
                Move-in: <?= htmlspecialchars((string) $tenancy['startDate'], ENT_QUOTES, 'UTF-8') ?> &nbsp;|&nbsp;
                Annual rent: <?= htmlspecialchars(app_currency($tenancy['monthlyRent']), ENT_QUOTES, 'UTF-8') ?>
            </p>
        </div>
        <a class="solid-button" href="<?= htmlspecialchars(app_url('tenancy'), ENT_QUOTES, 'UTF-8') ?>">Open tenancy app</a>
    </div>
<?php endif; ?>

<section class="section-block">
    <div class="section-heading">
        <div>
            <span class="eyebrow">Tour requests</span>
            <h2>Your upcoming property visits</h2>
        </div>
        <a class="ghost-button" href="<?= htmlspecialchars(app_url('rentals'), ENT_QUOTES, 'UTF-8') ?>"
           style="background:transparent; border-color:var(--line); color:var(--muted)">
            Search more rentals
        </a>
    </div>

    <?php if ($tourRequests === array()): ?>
        <article class="empty-state">
            <h2>No tours booked yet.</h2>
            <p>Use the Book a tour action on any property page to reserve a viewing slot.</p>
        </article>
    <?php else: ?>
        <div class="pipeline-grid">
            <?php foreach ($tourRequests as $tourRequest): ?>
                <article class="pipeline-card">
                    <div class="pipeline-head">
                        <h3><?= htmlspecialchars((string) $tourRequest['property']['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <span class="admin-status-pill <?= htmlspecialchars((string) $tourRequest['status'], ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars((string) ucfirst($tourRequest['status']), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>
                    <p class="muted-text" style="font-size:.85rem; margin:0 0 6px"><?= htmlspecialchars((string) $tourRequest['property']['location'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="muted-text" style="font-size:.85rem; margin:0 0 10px">Scheduled: <?= htmlspecialchars((string) $tourRequest['slotLabel'], ENT_QUOTES, 'UTF-8') ?></p>
                    <?php if ($tourRequest['message'] !== ''): ?>
                        <p style="font-size:.85rem; margin:0 0 10px"><?= htmlspecialchars((string) $tourRequest['message'], ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                    <?php if ($tourRequest['status'] === 'confirmed'): ?>
                        <p class="muted-text" style="font-size:.8rem; margin:0">Confirmed by <?= htmlspecialchars((string) $tourRequest['confirmedBy'], ENT_QUOTES, 'UTF-8') ?>.</p>
                    <?php elseif ($tourRequest['status'] === 'requested'): ?>
                        <p class="muted-text" style="font-size:.8rem; margin:0">Awaiting scheduling confirmation from the leasing desk.</p>
                    <?php elseif ($tourRequest['status'] === 'cancelled'): ?>
                        <p class="muted-text" style="font-size:.8rem; margin:0">This visit was cancelled. You can book another slot when one opens.</p>
                    <?php elseif ($tourRequest['status'] === 'completed'): ?>
                        <p class="muted-text" style="font-size:.8rem; margin:0">Tour completed.</p>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="section-block">
    <div class="section-heading">
        <div>
            <span class="eyebrow">Applications</span>
            <h2>Your journey with Sandworth Homes</h2>
        </div>
        <a class="ghost-button" href="<?= htmlspecialchars(app_url('rentals'), ENT_QUOTES, 'UTF-8') ?>"
           style="background:transparent; border-color:var(--line); color:var(--muted)">
            Search more rentals
        </a>
    </div>

    <?php if ($applications === array()): ?>
        <article class="empty-state">
            <h2>No applications yet.</h2>
            <p>Start with a rental search, choose a property, and submit your application.</p>
            <a class="solid-button" href="<?= htmlspecialchars(app_url('rentals'), ENT_QUOTES, 'UTF-8') ?>" style="margin-top:20px">Browse rentals</a>
        </article>
    <?php else: ?>
    <div class="pipeline-grid">
        <?php foreach ($applications as $application): ?>
            <article class="pipeline-card">
                <div class="pipeline-head">
                    <h3><?= htmlspecialchars((string) $application['property']['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                    <span class="admin-status-pill <?= htmlspecialchars((string) $application['status'], ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars((string) ucfirst($application['status']), ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </div>
                <p class="muted-text" style="font-size:.85rem; margin:0 0 6px"><?= htmlspecialchars((string) $application['property']['location'], ENT_QUOTES, 'UTF-8') ?></p>
                <p class="muted-text" style="font-size:.8rem; margin:0 0 14px">Submitted: <?= htmlspecialchars((string) $application['submittedAt'], ENT_QUOTES, 'UTF-8') ?></p>

                <?php if ($application['status'] === 'approved'): ?>
                    <a class="solid-button" style="width:100%; justify-content:center"
                       href="<?= htmlspecialchars(app_url('payment', array('application_id' => $application['id'])), ENT_QUOTES, 'UTF-8') ?>">
                        Pay move-in charges
                    </a>
                <?php elseif ($application['status'] === 'active'): ?>
                    <a class="ghost-button" style="width:100%; justify-content:center; border-color:var(--line); color:var(--ink-mid); background:transparent"
                       href="<?= htmlspecialchars(app_url('tenancy'), ENT_QUOTES, 'UTF-8') ?>">
                        Open tenancy
                    </a>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>
