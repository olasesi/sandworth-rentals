<?php
/** @var array $property */
/** @var array $unit */
/** @var string $unitTable */
/** @var string $unitLabel */
/** @var array $billing */
/** @var array $history */
/** @var array $payments */
/** @var array $portfolio */

$isMall = $unitTable === 'mall_shops';
$isApartment = $unitTable === 'apartment_flats';
$isResidential = $unitTable === 'residential_units';

$unitTypeLabel = $isMall ? 'Shop' : ($isApartment ? 'Flat' : 'Unit');
$occupantName = trim((string) $unit['ownerName']);
$hasOccupant = $unit['userId'] > 0 && $occupantName !== '';
$isActive = $unit['status'] === 'active';
$isVacant = in_array($unit['status'], array('vacant', 'ended'), true);

$activeAdminPage = 'inventory';
$adminTitle = $unitLabel . ' · ' . $property['title'];
$adminDescription = $unitTypeLabel . ' overview for ' . $property['title'] . ', including billing summary, occupant history, and payment timeline.';

require dirname(__DIR__) . '/partials/admin-header.php';
?>

<section class="admin-workspace">
    <section class="admin-section">
        <div class="section-heading">
            <div>
                <span class="eyebrow"><?= htmlspecialchars($unitTypeLabel, ENT_QUOTES, 'UTF-8') ?> details</span>
                <h2><?= htmlspecialchars($unitLabel, ENT_QUOTES, 'UTF-8') ?></h2>
                <p class="muted-text"><?= htmlspecialchars($property['title'], ENT_QUOTES, 'UTF-8') ?> &middot; <?= htmlspecialchars($property['location'], ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <div class="admin-page-actions">
                <span class="admin-status-pill <?= htmlspecialchars($unit['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(ucfirst($unit['status']), ENT_QUOTES, 'UTF-8') ?></span>
                <a class="ghost-button" href="<?= htmlspecialchars(app_url('admin-units', array('property_id' => $property['id'])), ENT_QUOTES, 'UTF-8') ?>">Back to <?= $isMall ? 'shops' : ($isApartment ? 'flats' : 'units') ?></a>
                <a class="ghost-button" href="<?= htmlspecialchars(app_url('admin-units', array('property_id' => $property['id'], 'edit_unit' => $unit['id'])), ENT_QUOTES, 'UTF-8') ?>">Edit <?= $unitTypeLabel ?></a>
            </div>
        </div>
    </section>

    <section class="admin-overview-grid">
        <div class="detail-card admin-unit-detail-card">
            <span class="eyebrow">Current occupant</span>
            <?php if ($hasOccupant): ?>
                <h3><?= htmlspecialchars($occupantName, ENT_QUOTES, 'UTF-8') ?></h3>
                <p><?= htmlspecialchars($unit['ownerEmail'], ENT_QUOTES, 'UTF-8') ?></p>
                <?php if ($unit['ownerPhone'] !== ''): ?>
                    <p><?= htmlspecialchars($unit['ownerPhone'], ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
            <?php else: ?>
                <p class="muted-text">This <?= strtolower($unitTypeLabel) ?> has no occupant assigned.</p>
            <?php endif; ?>
        </div>

        <div class="detail-card admin-unit-detail-card">
            <span class="eyebrow">Identity</span>
            <?php if ($isMall): ?>
                <p><strong>Shop number:</strong> <?= htmlspecialchars($unit['shopNumber'] !== '' ? $unit['shopNumber'] : '—', ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Shop name:</strong> <?= htmlspecialchars($unit['shopName'] !== '' ? $unit['shopName'] : '—', ENT_QUOTES, 'UTF-8') ?></p>
            <?php elseif ($isApartment): ?>
                <p><strong>Block:</strong> <?= htmlspecialchars($unit['block'] !== '' ? $unit['block'] : '—', ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Floor:</strong> <?= htmlspecialchars($unit['floor'] !== '' ? $unit['floor'] : '—', ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Flat number:</strong> <?= htmlspecialchars($unit['flatNumber'] !== '' ? $unit['flatNumber'] : '—', ENT_QUOTES, 'UTF-8') ?></p>
            <?php else: ?>
                <p><strong>Block:</strong> <?= htmlspecialchars($unit['block'] !== '' ? $unit['block'] : '—', ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Unit number:</strong> <?= htmlspecialchars($unit['unitNumber'] !== '' ? $unit['unitNumber'] : '—', ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
            <p><strong>Created:</strong> <?= htmlspecialchars(app_format_datetime($unit['createdAt']), ENT_QUOTES, 'UTF-8') ?></p>
        </div>

        <div class="detail-card admin-unit-detail-card">
            <span class="eyebrow">Occupancy notes</span>
            <?php if (trim($unit['notes']) !== ''): ?>
                <p><?= nl2br(htmlspecialchars($unit['notes'], ENT_QUOTES, 'UTF-8')) ?></p>
            <?php else: ?>
                <p class="muted-text">No notes recorded for this <?= strtolower($unitTypeLabel) ?>.</p>
            <?php endif; ?>
        </div>
    </section>

    <section class="admin-section">
        <div class="section-heading">
            <div>
                <span class="eyebrow">Billing</span>
                <h2>Rent and payment summary</h2>
            </div>
        </div>

        <div class="admin-tenant-summary-grid">
            <div class="admin-tenant-summary">
                <p class="muted-text">Tenure / term</p>
                <strong><?= htmlspecialchars($billing['tenure'] !== '' ? $billing['tenure'] : '—', ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
            <div class="admin-tenant-summary">
                <p class="muted-text">Monthly rent</p>
                <strong><?= htmlspecialchars(app_currency($billing['monthlyRent']), ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
            <div class="admin-tenant-summary">
                <p class="muted-text">Service charge</p>
                <strong><?= htmlspecialchars(app_currency($billing['serviceCharge']), ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
            <div class="admin-tenant-summary">
                <p class="muted-text">Security deposit</p>
                <strong><?= htmlspecialchars(app_currency($billing['securityDeposit']), ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
            <div class="admin-tenant-summary">
                <p class="muted-text">Start date</p>
                <strong><?= htmlspecialchars($billing['startDate'] !== '' ? $billing['startDate'] : '—', ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
            <div class="admin-tenant-summary">
                <p class="muted-text">End date</p>
                <strong><?= htmlspecialchars($billing['endDate'] !== '' ? $billing['endDate'] : '—', ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
            <div class="admin-tenant-summary">
                <p class="muted-text">Months elapsed</p>
                <strong><?= (int) $billing['monthsElapsed'] ?></strong>
            </div>
            <div class="admin-tenant-summary">
                <p class="muted-text">Total paid</p>
                <strong><?= htmlspecialchars(app_currency($billing['totalPaid']), ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
            <div class="admin-tenant-summary">
                <p class="muted-text">Expected to date</p>
                <strong><?= htmlspecialchars(app_currency($billing['expectedTotal']), ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
            <div class="admin-tenant-summary <?= $billing['isDue'] ? 'admin-tenant-summary--due' : '' ?>">
                <p class="muted-text">Balance due</p>
                <strong class="<?= $billing['isDue'] ? 'text-danger' : '' ?>"><?= htmlspecialchars(app_currency($billing['balanceDue']), ENT_QUOTES, 'UTF-8') ?></strong>
                <?php if ($billing['isDue']): ?>
                    <span class="admin-status-pill active">Outstanding</span>
                <?php else: ?>
                    <span class="admin-status-pill inactive">Clear</span>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php if ($billing['isDue'] && $unit['userId'] > 0 && $unit['ownerEmail'] !== ''): ?>
        <section class="admin-section">
            <div class="section-heading">
                <div>
                    <span class="eyebrow">Send rent reminder</span>
                    <h2>Email the occupant about the outstanding balance</h2>
                </div>
            </div>
            <form action="<?= htmlspecialchars(app_url('admin-rent-reminder-send'), ENT_QUOTES, 'UTF-8') ?>" method="post" class="admin-form">
                <input type="hidden" name="unit_table" value="<?= htmlspecialchars($unitTable, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="unit_id" value="<?= (int) $unit['id'] ?>">
                <input type="hidden" name="property_id" value="<?= (int) $property['id'] ?>">
                <input type="hidden" name="return_to" value="admin-unit-detail">
                <p class="muted-text">This will email <?= htmlspecialchars($unit['ownerName'], ENT_QUOTES, 'UTF-8') ?> at <?= htmlspecialchars($unit['ownerEmail'], ENT_QUOTES, 'UTF-8') ?> with a breakdown of rent expected, paid, and balance due.</p>
                <div class="admin-form-actions">
                    <button type="submit" class="solid-button" onclick="return confirm('Send rent reminder to <?= htmlspecialchars(addslashes($unit['ownerName']), ENT_QUOTES, 'UTF-8') ?>?');">Send reminder</button>
                </div>
            </form>
        </section>
    <?php endif; ?>

    <section class="admin-section">
        <div class="section-heading">
            <div>
                <span class="eyebrow">Payment history</span>
                <h2>Payments received for this <?= strtolower($unitTypeLabel) ?></h2>
            </div>
        </div>

        <?php if ($payments === array()): ?>
            <article class="empty-state">
                <h2>No payments recorded yet.</h2>
                <p>Use the Tenants page to post a payment for this occupant.</p>
            </article>
        <?php else: ?>
            <div class="record-table">
                <div class="record-table-row record-table-head">
                    <span>Amount</span>
                    <span>Description</span>
                    <span>Channel</span>
                    <span>Reference</span>
                    <span>Date</span>
                </div>
                <?php foreach ($payments as $payment): ?>
                    <div class="record-table-row">
                        <span style="font-weight:700"><?= htmlspecialchars(app_currency($payment['amount']), ENT_QUOTES, 'UTF-8') ?></span>
                        <span><?= htmlspecialchars($payment['description'] !== '' ? $payment['description'] : '—', ENT_QUOTES, 'UTF-8') ?></span>
                        <span><?= htmlspecialchars(ucfirst($payment['channel']), ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="muted-text" style="font-size:.85rem"><?= htmlspecialchars($payment['reference'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="muted-text"><?= htmlspecialchars(app_format_datetime($payment['createdAt']), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="admin-section">
        <div class="section-heading">
            <div>
                <span class="eyebrow">Occupant history</span>
                <h2>People who have occupied this <?= strtolower($unitTypeLabel) ?></h2>
            </div>
        </div>

        <?php if ($history === array()): ?>
            <article class="empty-state">
                <h2>No occupant history yet.</h2>
                <p>History is recorded automatically each time a tenant is registered or moved.</p>
            </article>
        <?php else: ?>
            <div class="record-table">
                <div class="record-table-row record-table-head">
                    <span>Tenant</span>
                    <span>Tenure</span>
                    <span>Rent</span>
                    <span>Start</span>
                    <span>End</span>
                    <span>Status</span>
                </div>
                <?php foreach ($history as $entry): ?>
                    <div class="record-table-row">
                        <span>
                            <strong><?= htmlspecialchars($entry['userName'] !== '' ? $entry['userName'] : '—', ENT_QUOTES, 'UTF-8') ?></strong>
                            <span class="muted-text" style="display:block; font-size:.8rem"><?= htmlspecialchars($entry['userEmail'], ENT_QUOTES, 'UTF-8') ?></span>
                        </span>
                        <span><?= htmlspecialchars($entry['tenure'] !== '' ? $entry['tenure'] : '—', ENT_QUOTES, 'UTF-8') ?></span>
                        <span style="font-weight:700"><?= htmlspecialchars(app_currency($entry['monthlyRent']), ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="muted-text"><?= htmlspecialchars($entry['startDate'] !== '' ? $entry['startDate'] : '—', ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="muted-text"><?= htmlspecialchars($entry['endDate'] !== '' ? $entry['endDate'] : '—', ENT_QUOTES, 'UTF-8') ?></span>
                        <span>
                            <span class="admin-status-pill <?= htmlspecialchars($entry['occupancyStatus'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars(ucfirst($entry['occupancyStatus']), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <?php if ($entry['endedAt'] !== ''): ?>
                                <span class="muted-text" style="display:block; font-size:.8rem">Ended <?= htmlspecialchars(app_format_datetime($entry['endedAt']), ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</section>
