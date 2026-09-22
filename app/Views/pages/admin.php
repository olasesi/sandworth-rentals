<?php
/** @var array $portfolio */
/** @var string $currentCurrencyCode */
/** @var array $applications */
/** @var array $tourRequests */

$activeAdminPage = 'overview';
$adminTitle = 'Sandworth Homes | Admin workspace.';
$adminDescription = 'Dedicated pages for: Editorial content, Property editing, Renter applications, and Loaded inventory.';

require dirname(__DIR__) . '/partials/admin-header.php';
?>

<section class="admin-workspace">
    <div class="admin-toolbar">
        <div>
            <span class="eyebrow">Workspace settings</span>
            <h2>Operations controls</h2>
            <p>Switch the public display currency instantly and keep the listing desk ready for both rental and sales inventory.</p>
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

    <section class="admin-overview-grid">
        <a class="detail-card admin-shortcut-card" href="<?= htmlspecialchars(app_url('admin-editorial'), ENT_QUOTES, 'UTF-8') ?>">
            <span class="eyebrow">Editorial pages</span>
            <h2>Manage page content blocks</h2>
            <p>Edit the database-backed content that powers Sandworth Homes, plan, rentals, homes, and manager pages.</p>
        </a>

        <a class="detail-card admin-shortcut-card" href="<?= htmlspecialchars(app_url('admin-seo'), ENT_QUOTES, 'UTF-8') ?>">
            <span class="eyebrow">Marketing &amp; SEO</span>
            <h2>Control public metadata</h2>
            <p>Update share images, robots policy, sitemap base URL, social profiles, and public contact details from one marketing page.</p>
        </a>

        <a class="detail-card admin-shortcut-card" href="<?= htmlspecialchars(app_url('admin-properties'), ENT_QUOTES, 'UTF-8') ?>">
            <span class="eyebrow">Edit property</span>
            <h2>Load or update listings</h2>
            <p>Work on titles, prices, photos, service charges, galleries, and location details.</p>
        </a>

        <a class="detail-card admin-shortcut-card" href="<?= htmlspecialchars(app_url('admin-applications'), ENT_QUOTES, 'UTF-8') ?>">
            <span class="eyebrow">Applications</span>
            <h2>Review renter pipeline</h2>
            <p>Approve applicants and move them into payment and tenancy record.</p>
        </a>

        <a class="detail-card admin-shortcut-card" href="<?= htmlspecialchars(app_url('admin-tenants'), ENT_QUOTES, 'UTF-8') ?>">
            <span class="eyebrow">Tenants</span>
            <h2>Register &amp; manage tenants</h2>
            <p>Record each tenant, their property, tenure dates, rent, and the full payment timeline.</p>
        </a>

        <a class="detail-card admin-shortcut-card" href="<?= htmlspecialchars(app_url('admin-tours'), ENT_QUOTES, 'UTF-8') ?>">
            <span class="eyebrow">Tour desk</span>
            <h2>Manage tours and slot availability</h2>
            <p>Create viewing windows, confirm bookings, and keep upcoming tours organized by property.</p>
        </a>

        <a class="detail-card admin-shortcut-card" href="<?= htmlspecialchars(app_url('admin-inventory'), ENT_QUOTES, 'UTF-8') ?>">
            <span class="eyebrow">Loaded properties</span>
            <h2>Scan live inventory</h2>
            <p>Browse the current property table, with cleaner thumbnails, prices, status, and edit links.</p>
        </a>
    </section>

    <section class="admin-section">
        <div class="section-heading">
            <div>
                <span class="eyebrow">Recent applications</span>
                <h2>Latest renter activity</h2>
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
                                <p class="muted-text"><?= htmlspecialchars((string) $application['user']['name'], ENT_QUOTES, 'UTF-8') ?> | <?= htmlspecialchars((string) $application['user']['email'], ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                            <span class="type-pill"><?= htmlspecialchars((string) ucfirst($application['status']), ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <p>Income: <?= htmlspecialchars((string) $application['annualIncome'], ENT_QUOTES, 'UTF-8') ?> | Employer: <?= htmlspecialchars((string) $application['employer'], ENT_QUOTES, 'UTF-8') ?></p>
                        <p>Move-in: <?= htmlspecialchars((string) $application['moveInDate'], ENT_QUOTES, 'UTF-8') ?> | Occupants: <?= (int) $application['occupants'] ?></p>
                    </div>
                </article>
            <?php endforeach; ?>

            <?php if ($applications === array()): ?>
                <article class="empty-state">
                    <h2>No applications yet.</h2>
                    <p>New renter activity will appear here, and the full review workflow lives on the Applications page.</p>
                </article>
            <?php endif; ?>
        </div>
    </section>

    <section class="admin-section">
        <div class="section-heading">
            <div>
                <span class="eyebrow">Upcoming tours</span>
                <h2>Latest scheduled walkthroughs</h2>
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
                                <p class="muted-text"><?= htmlspecialchars((string) $tourRequest['fullName'], ENT_QUOTES, 'UTF-8') ?> | <?= htmlspecialchars((string) $tourRequest['phone'], ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                            <span class="admin-status-pill <?= htmlspecialchars((string) $tourRequest['status'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars((string) ucfirst($tourRequest['status']), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </div>
                        <p>Slot: <?= htmlspecialchars((string) $tourRequest['slotLabel'], ENT_QUOTES, 'UTF-8') ?></p>
                        <?php if ($tourRequest['message'] !== ''): ?>
                            <p><?= htmlspecialchars((string) $tourRequest['message'], ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>

            <?php if ($tourRequests === array()): ?>
                <article class="empty-state">
                    <h2>No tours booked yet.</h2>
                    <p>Create availability from the tour desk and new booking requests will appear here.</p>
                </article>
            <?php endif; ?>
        </div>
    </section>
</section>
