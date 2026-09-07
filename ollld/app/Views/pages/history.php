<?php
/** @var array $activity */
/** @var array $applications */
/** @var array|null $tenancy */
/** @var array $payments */
/** @var array $favorites */
/** @var array $offers */
/** @var array $tours */
$activity = isset($activity) ? $activity : array();
$applications = isset($applications) ? $applications : array();
$tenancy = isset($tenancy) ? $tenancy : null;
$payments = isset($payments) ? $payments : array();
$favorites = isset($favorites) ? $favorites : array();
$offers = isset($offers) ? $offers : array();
$tours = isset($tours) ? $tours : array();
$totalSpend = 0;

foreach ($payments as $payment) {
    $totalSpend += (int) $payment['amount'];
}

$actionLabels = array(
    'account_created' => 'Account opened',
    'requested_tour' => 'Booked a tour',
    'tour_scheduled' => 'Tour confirmed',
    'tour_completed' => 'Tour attended',
    'tour_no_show' => 'Tour missed',
    'tour_cancelled' => 'Tour cancelled',
    'applied' => 'Applied for a home',
    'application_approved' => 'Application approved',
    'tenancy_activated' => 'Tenancy activated',
    'tenant_payment' => 'Payment posted',
    'offer_submitted' => 'Made a purchase offer',
    'offer_accepted' => 'Offer accepted',
    'offer_countered' => 'Offer countered',
    'offer_rejected' => 'Offer declined',
    'favorite_saved' => 'Saved a property',
    'favorite_removed' => 'Removed a saved property',
    'message_sent' => 'Sent a message',
);
?>
<section class="page-hero compact">
    <div>
        <span class="eyebrow">Returning customer</span>
        <h1>Your history with Sandworth Homes.</h1>
        <p>A complete record of what you've requested, rented, bought, booked and paid with us.</p>
    </div>
</section>

<section class="section-block">
    <div class="section-heading">
        <div>
            <span class="eyebrow">Summary</span>
            <h2>What you've gotten from us</h2>
        </div>
    </div>

    <div class="manager-stats" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr))">
        <article><span>Homes applied for</span><strong><?= count($applications) ?></strong></article>
        <article><span>Active / past tenancies</span><strong><?= $tenancy ? 1 : 0 ?></strong></article>
        <article><span>Total paid</span><strong><?= htmlspecialchars(app_currency($totalSpend), ENT_QUOTES, 'UTF-8') ?></strong></article>
        <article><span>Properties saved</span><strong><?= count($favorites) ?></strong></article>
        <article><span>Offers made</span><strong><?= count($offers) ?></strong></article>
        <article><span>Tours booked</span><strong><?= count($tours) ?></strong></article>
    </div>
</section>

<?php if ($tenancy): ?>
<section class="section-block" style="margin-top:8px;">
    <div class="detail-card" style="border-left:4px solid var(--green); border-radius:0 var(--r-lg) var(--r-lg) 0">
        <span class="eyebrow">Tenancy record</span>
        <h2 style="margin:6px 0; font-size:1.1rem"><?= htmlspecialchars((string) $tenancy['property']['title'], ENT_QUOTES, 'UTF-8') ?></h2>
        <p style="color:var(--muted); font-size:.9rem; margin:0 0 12px">Status: <?= htmlspecialchars((string) ucfirst($tenancy['status']), ENT_QUOTES, 'UTF-8') ?> · Move-in: <?= htmlspecialchars((string) $tenancy['startDate'], ENT_QUOTES, 'UTF-8') ?> · Monthly rent: <?= htmlspecialchars(app_currency($tenancy['monthlyRent']), ENT_QUOTES, 'UTF-8') ?></p>
        <?php if ($payments !== array()): ?>
            <div style="display:grid; gap:6px">
                <?php foreach ($payments as $payment): ?>
                    <div style="display:flex; justify-content:space-between; font-size:.85rem; border-top:1px solid var(--line-light); padding-top:6px">
                        <span style="color:var(--muted)"><?= htmlspecialchars((string) $payment['description'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars((string) $payment['createdAt'], ENT_QUOTES, 'UTF-8') ?></span>
                        <strong><?= htmlspecialchars(app_currency($payment['amount']), ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<section class="section-block" style="margin-top:8px;">
    <div class="section-heading">
        <div>
            <span class="eyebrow">Timeline</span>
            <h2>Full activity log</h2>
        </div>
    </div>

    <?php if ($activity === array()): ?>
        <article class="empty-state">
            <h2>No activity recorded yet.</h2>
            <p>Every application, tour, offer, payment and message will be logged here for future reference.</p>
        </article>
    <?php else: ?>
        <div style="max-width:820px; display:grid; gap:0">
            <?php foreach ($activity as $index => $entry): ?>
                <div style="display:flex; gap:14px; position:relative; padding-bottom:16px">
                    <?php if ($index < count($activity) - 1): ?>
                        <div style="width:2px; background:var(--line-light); position:absolute; left:7px; top:24px; bottom:0"></div>
                    <?php endif; ?>
                    <div style="width:16px; height:16px; border-radius:50%; background:var(--primary); flex:0 0 16px; margin-top:3px"></div>
                    <div style="flex:1">
                        <div style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:6px">
                            <strong style="font-size:.92rem"><?= htmlspecialchars(isset($actionLabels[$entry['action']]) ? $actionLabels[$entry['action']] : ucwords(str_replace('_', ' ', $entry['action'])), ENT_QUOTES, 'UTF-8') ?></strong>
                            <span style="font-size:.78rem; color:var(--muted)"><?= htmlspecialchars((string) $entry['createdAt'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <?php if ($entry['detail'] !== ''): ?>
                            <p style="margin:3px 0 0; font-size:.86rem; color:var(--ink-mid)"><?= htmlspecialchars((string) $entry['detail'], ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
