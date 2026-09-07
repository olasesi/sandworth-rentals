<?php
/** @var array $report */
/** @var array $pipeline */
$report = isset($report) ? $report : array();
$pipeline = isset($pipeline) ? $pipeline : array();
?>
<section class="page-hero compact">
    <div>
        <span class="eyebrow">Sandworth impact report</span>
        <h1>Admin | Impact Report</h1>
        <p>A data view of bookings, Revenue and Conversions </p>
    </div>
</section>

<section class="manager-stats plan-stats">
    <article><span>Renter accounts</span><strong><?= (int) $report['totalUsers'] ?></strong></article>
    <article><span>Applications</span><strong><?= (int) $report['submittedApplications'] + (int) $report['approvedApplications'] + (int) $report['activeTenancies'] ?></strong></article>
    <article><span>Active tenancies</span><strong><?= (int) $report['activeTenancies'] ?></strong></article>
    <article><span>Revenue collected</span><strong><?= htmlspecialchars((string) $report['totalRevenueText'], ENT_QUOTES, 'UTF-8') ?></strong></article>
    <article><span>This month</span><strong><?= htmlspecialchars((string) $report['monthlyRevenueText'], ENT_QUOTES, 'UTF-8') ?></strong></article>
</section>

<section class="section-block">
    <div class="section-heading">
        <div>
            <span class="eyebrow">What the app automated</span>
            <h2>Self-serve wins (no admin effort)</h2>
        </div>
    </div>
    <div class="manager-stats" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr))">
        <article><span>Approved applications</span><strong><?= (int) $report['autoApproved'] ?></strong><p class="muted-text" style="font-size:.75rem; margin:6px 0 0">Qualified Applicants.</p></article>
        <article><span>Online payments taken</span><strong><?= (int) (isset($report['activeTenancies']) ? $report['activeTenancies'] : 0) ?></strong><p class="muted-text" style="font-size:.75rem; margin:6px 0 0">Online payments confirmed.</p></article>
        <article><span>In-app messages answered</span><strong><?= (int) $report['totalMessages'] ?></strong><p class="muted-text" style="font-size:.75rem; margin:6px 0 0">Enquiries handled, email &calls.</p></article>
        <article><span>Homes shortlisted</span><strong><?= (int) $report['totalFavorites'] ?></strong><p class="muted-text" style="font-size:.75rem; margin:6px 0 0">Prospective Customers count.</p></article>
    </div>
</section>

<section class="section-block" style="margin-top:8px;">
    <div class="section-heading">
        <div>
            <span class="eyebrow">Sales funnel</span>
            <h2>Tours, offers &amp; conversions</h2>
        </div>
    </div>
    <div class="manager-stats" style="grid-template-columns:repeat(auto-fit,minmax(190px,1fr))">
        <article><span>Tours booked</span><strong><?= (int) $report['totalTours'] ?></strong></article>
        <article><span>Tours completed</span><strong><?= (int) $report['completedTours'] ?></strong></article>
        <article><span>Tour no-shows</span><strong><?= (int) $report['noShowTours'] ?></strong></article>
        <article><span>Purchase offers</span><strong><?= (int) $report['totalOffers'] ?></strong></article>
        <article><span>Offers accepted</span><strong><?= (int) $report['acceptedOffers'] ?></strong></article>
        <article><span>Avg days to lease</span><strong><?= (int) $report['avgDaysToLease'] ?> days</strong></article>
    </div>
</section>

<section class="section-block" style="margin-top:8px;">
    <div class="pipeline-board">
        <div class="section-heading">
            <div>
                <span class="eyebrow">Renter pipeline</span>
                <h2>Applications by stage</h2>
            </div>
        </div>
        <div class="timeline-list">
            <?php foreach ($pipeline as $stage): ?>
                <article class="timeline-card">
                    <span class="timeline-index"><?= (int) $stage['count'] ?></span>
                    <div>
                        <h3><?= htmlspecialchars((string) $stage['stage'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <p><?= htmlspecialchars((string) $stage['description'], ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
