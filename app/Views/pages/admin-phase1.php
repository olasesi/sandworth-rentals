<?php
/** @var array $portfolio */
/** @var string $currentCurrencyCode */
/** @var array $applications */
/** @var array $tourRequests */
/** @var array $offers */
/** @var array $maintenanceTickets */
/** @var array $messages */
/** @var array $dueUnits */
/** @var int $dueUnitsCount */
$activeAdminPage = 'overview';
$dueUnitsCount = isset($dueUnitsCount) ? (int) $dueUnitsCount : 0;
$dueUnits = isset($dueUnits) ? $dueUnits : array();
$adminTitle = 'Phase 1 operations desk';
$adminDescription = 'Run housing activity from one admin workspace: approvals, tours, offers, maintenance, and customer conversations.';
require __DIR__ . '/_admin_phase1_header.php';
?>

<section class="admin-workspace">
    <div class="admin-toolbar">
        <div>
            <span class="eyebrow">Workspace settings</span>
            <h2>Operations controls</h2>
            <p>Keep the public pricing currency aligned while the Phase 1 workflows run from one lean admin surface.</p>
        </div>
        <form action="<?= htmlspecialchars(app_url('admin-currency-update'), ENT_QUOTES, 'UTF-8') ?>" method="post" class="admin-toolbar-form">
            <div class="admin-field">
                <label for="currency-code">Display currency</label>
                <select id="currency-code" name="currency_code" required>
                    <option value="USD" <?= $currentCurrencyCode === 'USD' ? 'selected' : '' ?>>Dollar ($)</option>
                    <option value="NGN" <?= $currentCurrencyCode === 'NGN' ? 'selected' : '' ?>>Naira (&#8358;)</option>
                </select>
            </div>
            <button type="submit" class="ghost-button">Update currency</button>
        </form>
    </div>

    <?php if ($dueUnitsCount > 0): ?>
        <div class="admin-alert-banner">
            <p><strong><?= (int) $dueUnitsCount ?> unit(s) with outstanding rent balances.</strong> <a href="<?= htmlspecialchars(app_url('admin-rent-due'), ENT_QUOTES, 'UTF-8') ?>">Review and send reminders</a></p>
        </div>
    <?php endif; ?>

    <section class="admin-overview-grid">
        <a class="detail-card admin-shortcut-card" href="<?= htmlspecialchars(app_url('admin-rent-due'), ENT_QUOTES, 'UTF-8') ?>">
            <span class="eyebrow">Rent due<?= $dueUnitsCount > 0 ? ' (' . (int) $dueUnitsCount . ')' : '' ?></span>
            <h2>Outstanding balances</h2>
            <p>View every occupied unit where rent is owed and send payment reminders by email.</p>
        </a>
        <a class="detail-card admin-shortcut-card" href="<?= htmlspecialchars(app_url('admin-applications'), ENT_QUOTES, 'UTF-8') ?>">
            <span class="eyebrow">Applications</span>
            <h2>Review and approve renters</h2>
            <p>Rule-based auto-approval handles the easy cases, while the admin queue keeps exceptions visible.</p>
        </a>
        <a class="detail-card admin-shortcut-card" href="<?= htmlspecialchars(app_url('admin-tours'), ENT_QUOTES, 'UTF-8') ?>">
            <span class="eyebrow">Tours</span>
            <h2>Publish and manage viewing windows</h2>
            <p>Create one slot at a time or generate a full block of self-serve openings for a property.</p>
        </a>
        <a class="detail-card admin-shortcut-card" href="<?= htmlspecialchars(app_url('admin-offers'), ENT_QUOTES, 'UTF-8') ?>">
            <span class="eyebrow">Offers</span>
            <h2>Track buyer negotiations</h2>
            <p>Review submitted offers, add notes, and move each one through acceptance or decline.</p>
        </a>
        <a class="detail-card admin-shortcut-card" href="<?= htmlspecialchars(app_url('admin-messages'), ENT_QUOTES, 'UTF-8') ?>">
            <span class="eyebrow">Inbox</span>
            <h2>Reply to users in-app</h2>
            <p>Keep renter, buyer, and support conversations inside the platform instead of losing them to email threads.</p>
        </a>
        <a class="detail-card admin-shortcut-card" href="<?= htmlspecialchars(app_url('admin-maintenance'), ENT_QUOTES, 'UTF-8') ?>">
            <span class="eyebrow">Maintenance</span>
            <h2>Respond to live tenant issues</h2>
            <p>Move repair tickets from open to resolved with notes the tenant can see in their tenancy workspace.</p>
        </a>
        <a class="detail-card admin-shortcut-card" href="<?= htmlspecialchars(app_url('admin-properties'), ENT_QUOTES, 'UTF-8') ?>">
            <span class="eyebrow">Properties</span>
            <h2>Keep inventory current</h2>
            <p>Update availability, pricing, images, and listing details that feed every public workflow.</p>
        </a>
        <a class="detail-card admin-shortcut-card" href="<?= htmlspecialchars(app_url('admin-tenants'), ENT_QUOTES, 'UTF-8') ?>">
            <span class="eyebrow">Tenants</span>
            <h2>Register and manage tenants</h2>
            <p>Record each tenant's tenure dates, rent terms, and every amount paid across the tenancy timeline.</p>
        </a>
    </section>

    <section class="admin-section">
        <div class="section-heading">
            <div>
                <span class="eyebrow">Recent applications</span>
                <h2>Renter pipeline</h2>
            </div>
            <a class="ghost-button" href="<?= htmlspecialchars(app_url('admin-applications'), ENT_QUOTES, 'UTF-8') ?>">View all applications</a>
        </div>
        <div class="results-list">
            <?php foreach ($applications as $application): ?>
                <article class="result-card">
                    <div class="result-card-body result-card-body-wide">
                        <div class="result-card-top">
                            <div>
                                <h2><?= htmlspecialchars((string) $application['property']['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                                <p class="muted-text"><?= htmlspecialchars((string) $application['user']['name'], ENT_QUOTES, 'UTF-8') ?> | <?= htmlspecialchars((string) $application['annualIncome'], ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                            <span class="type-pill"><?= htmlspecialchars((string) ucfirst($application['status']), ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="admin-section">
        <div class="section-heading">
            <div>
                <span class="eyebrow">Upcoming tours</span>
                <h2>Self-serve visits</h2>
            </div>
            <a class="ghost-button" href="<?= htmlspecialchars(app_url('admin-tours'), ENT_QUOTES, 'UTF-8') ?>">Open tour desk</a>
        </div>
        <div class="results-list">
            <?php foreach ($tourRequests as $tourRequest): ?>
                <article class="result-card">
                    <div class="result-card-body result-card-body-wide">
                        <div class="result-card-top">
                            <div>
                                <h2><?= htmlspecialchars((string) $tourRequest['property']['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                                <p class="muted-text"><?= htmlspecialchars((string) $tourRequest['fullName'], ENT_QUOTES, 'UTF-8') ?> | <?= htmlspecialchars((string) $tourRequest['slotLabel'], ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                            <span class="admin-status-pill <?= htmlspecialchars((string) $tourRequest['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ucfirst($tourRequest['status']), ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="admin-overview-grid">
        <article class="detail-card">
            <span class="eyebrow">Open offers</span>
            <h2 style="font-size:1.1rem">Buyer activity</h2>
            <?php if ($offers === array()): ?>
                <p>No buyer offers yet.</p>
            <?php else: ?>
                <?php foreach ($offers as $offer): ?>
                    <p style="margin:0 0 10px"><?= htmlspecialchars((string) $offer['property']['title'], ENT_QUOTES, 'UTF-8') ?> | <?= htmlspecialchars(app_currency($offer['offerAmount']), ENT_QUOTES, 'UTF-8') ?></p>
                <?php endforeach; ?>
            <?php endif; ?>
        </article>
        <article class="detail-card">
            <span class="eyebrow">Open maintenance</span>
            <h2 style="font-size:1.1rem">Tenant issues</h2>
            <?php if ($maintenanceTickets === array()): ?>
                <p>No maintenance tickets yet.</p>
            <?php else: ?>
                <?php foreach ($maintenanceTickets as $ticket): ?>
                    <p style="margin:0 0 10px"><?= htmlspecialchars((string) $ticket['title'], ENT_QUOTES, 'UTF-8') ?> | <?= htmlspecialchars((string) ucfirst($ticket['priority']), ENT_QUOTES, 'UTF-8') ?></p>
                <?php endforeach; ?>
            <?php endif; ?>
        </article>
        <article class="detail-card">
            <span class="eyebrow">Latest messages</span>
            <h2 style="font-size:1.1rem">User inbox</h2>
            <?php if ($messages === array()): ?>
                <p>No new messages yet.</p>
            <?php else: ?>
                <?php foreach ($messages as $message): ?>
                    <p style="margin:0 0 10px"><?= htmlspecialchars((string) $message['user']['name'], ENT_QUOTES, 'UTF-8') ?> | <?= htmlspecialchars((string) substr($message['body'], 0, 70), ENT_QUOTES, 'UTF-8') ?></p>
                <?php endforeach; ?>
            <?php endif; ?>
        </article>
    </section>
</section>
