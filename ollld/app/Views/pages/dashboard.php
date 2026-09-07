<?php
/** @var array $applications */
/** @var array|null $tenancy */
?>
<section class="page-hero compact">
    <div>
        <span class="eyebrow">My dashboard</span>
        <h1>Your renter hub — applications, approvals &amp; tenancy.</h1>
        <p>Track every stage of your rental journey from application to active tenancy.</p>
    </div>
</section>

<?php if ($tenancy): ?>
    <div class="detail-card" style="margin:24px 0; border-left:4px solid var(--green); border-radius:0 var(--r-lg) var(--r-lg) 0; display:flex; align-items:center; justify-content:space-between; gap:20px; flex-wrap:wrap">
        <div>
            <span class="eyebrow">Active tenancy</span>
            <h2 style="margin:4px 0 6px; font-size:1.1rem"><?= htmlspecialchars((string) $tenancy['property']['title'], ENT_QUOTES, 'UTF-8') ?></h2>
            <p style="font-size:.875rem; color:var(--muted); margin:0">
                Move-in: <?= htmlspecialchars((string) $tenancy['startDate'], ENT_QUOTES, 'UTF-8') ?> &nbsp;·&nbsp;
                Monthly rent: <?= htmlspecialchars(app_currency($tenancy['monthlyRent']), ENT_QUOTES, 'UTF-8') ?>
            </p>
        </div>
        <a class="solid-button" href="<?= htmlspecialchars(app_url('tenancy'), ENT_QUOTES, 'UTF-8') ?>">Open tenancy app</a>
    </div>
<?php endif; ?>

<section class="section-block">
    <div class="section-heading">
        <div>
            <span class="eyebrow">Applications</span>
            <h2>Your rental journey</h2>
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

<?php
$favorites = isset($favorites) ? $favorites : array();
$offers    = isset($offers) ? $offers : array();
$conversation = isset($conversation) ? $conversation : array();
$tours     = isset($tours) ? $tours : array();
?>
<section class="section-block" style="margin-top:8px;">
    <div class="section-heading">
        <div>
            <span class="eyebrow">Your activity</span>
            <h2>Shortlist, offers, tours &amp; messages</h2>
        </div>
        <a class="ghost-button" href="<?= htmlspecialchars(app_url('history'), ENT_QUOTES, 'UTF-8') ?>"
           style="background:transparent; border-color:var(--line); color:var(--muted)">Full history</a>
    </div>

    <div class="manager-stats" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr))">
        <article>
            <span>Saved properties</span>
            <strong><?= count($favorites) ?></strong>
            <a class="text-link" href="<?= htmlspecialchars(app_url('saved'), ENT_QUOTES, 'UTF-8') ?>">View saved</a>
        </article>
        <article>
            <span>Purchase offers</span>
            <strong><?= count($offers) ?></strong>
            <a class="text-link" href="<?= htmlspecialchars(app_url('offers'), ENT_QUOTES, 'UTF-8') ?>">View offers</a>
        </article>
        <article>
            <span>Site tours</span>
            <strong><?= count($tours) ?></strong>
            <a class="text-link" href="<?= htmlspecialchars(app_url('tours'), ENT_QUOTES, 'UTF-8') ?>">View tours</a>
        </article>
        <article>
            <span>Messages</span>
            <strong><?= count($conversation) ?></strong>
            <a class="text-link" href="<?= htmlspecialchars(app_url('messages'), ENT_QUOTES, 'UTF-8') ?>">Open inbox</a>
        </article>
    </div>
</section>
