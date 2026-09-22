<?php
/** @var array $portfolio */
/** @var array $dueUnits */
/** @var array $recentLogs */

$dueUnitsCount = count($dueUnits);
$activeAdminPage = 'rent-due';
$adminTitle = 'Rent due & outstanding balances.';
$adminDescription = 'View every occupied unit where rent is outstanding. Send reminders by email, or send all at once for every due unit.';

require dirname(__DIR__) . '/partials/admin-header.php';
?>

<section class="admin-workspace">
    <section class="admin-section">
        <div class="section-heading">
            <div>
                <span class="eyebrow">Rent due</span>
                <h2>Outstanding rent balances</h2>
                <p class="muted-text">Units where the expected rent to date exceeds payments received. Balances are computed from each unit's monthly rent, start date, and tenure.</p>
            </div>
            <?php if ($dueUnitsCount > 0): ?>
                <form action="<?= htmlspecialchars(app_url('admin-rent-reminder-send-all'), ENT_QUOTES, 'UTF-8') ?>" method="post">
                    <button type="submit" class="solid-button" onclick="return confirm('Send a rent reminder to every occupant with an outstanding balance?');">Send all reminders (<?= (int) $dueUnitsCount ?>)</button>
                </form>
            <?php endif; ?>
        </div>

        <?php if ($dueUnits === array()): ?>
            <article class="empty-state">
                <h2>No outstanding balances.</h2>
                <p>All occupied units have been fully paid for their current tenure period.</p>
            </article>
        <?php else: ?>
            <div class="record-table">
                <div class="record-table-row record-table-head">
                    <span>Property</span>
                    <span><?= $dueUnits[0]['unitTable'] === 'mall_shops' ? 'Shop' : 'Unit' ?></span>
                    <span>Occupant</span>
                    <span>Expected</span>
                    <span>Paid</span>
                    <span>Balance</span>
                    <span>Action</span>
                </div>
                <?php foreach ($dueUnits as $due): ?>
                    <?php
                        $detailHref = app_url('admin-unit-detail', array('property_id' => $due['property']['id'], 'unit_id' => $due['unit']['id']));
                    ?>
                    <div class="record-table-row <?= $due['balanceDue'] > 0 ? 'record-table-row--highlight' : '' ?>">
                        <span>
                            <strong><?= htmlspecialchars($due['property']['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <span class="muted-text" style="display:block; font-size:.8rem"><?= htmlspecialchars($due['property']['location'], ENT_QUOTES, 'UTF-8') ?></span>
                        </span>
                        <span>
                            <a href="<?= htmlspecialchars($detailHref, ENT_QUOTES, 'UTF-8') ?>" class="admin-unit-link"><strong><?= htmlspecialchars($due['unitLabel'], ENT_QUOTES, 'UTF-8') ?></strong></a>
                            <span class="muted-text" style="display:block; font-size:.8rem"><?= htmlspecialchars($due['unit']['status'], ENT_QUOTES, 'UTF-8') ?></span>
                        </span>
                        <span>
                            <strong><?= htmlspecialchars($due['occupant']['name'] !== '' ? $due['occupant']['name'] : '—', ENT_QUOTES, 'UTF-8') ?></strong>
                            <span class="muted-text" style="display:block; font-size:.8rem"><?= htmlspecialchars($due['occupant']['email'], ENT_QUOTES, 'UTF-8') ?></span>
                        </span>
                        <span style="font-weight:700"><?= htmlspecialchars(app_currency($due['billing']['expectedTotal']), ENT_QUOTES, 'UTF-8') ?></span>
                        <span style="font-weight:700"><?= htmlspecialchars(app_currency($due['billing']['totalPaid']), ENT_QUOTES, 'UTF-8') ?></span>
                        <span>
                            <strong class="text-danger"><?= htmlspecialchars(app_currency($due['balanceDue']), ENT_QUOTES, 'UTF-8') ?></strong>
                        </span>
                        <span class="admin-table-actions">
                            <?php if ($due['occupant']['email'] !== '' && filter_var($due['occupant']['email'], FILTER_VALIDATE_EMAIL)): ?>
                                <form action="<?= htmlspecialchars(app_url('admin-rent-reminder-send'), ENT_QUOTES, 'UTF-8') ?>" method="post" class="admin-inline-form">
                                    <input type="hidden" name="unit_table" value="<?= htmlspecialchars($due['unitTable'], ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="unit_id" value="<?= (int) $due['unit']['id'] ?>">
                                    <input type="hidden" name="property_id" value="<?= (int) $due['property']['id'] ?>">
                                    <button type="submit" class="ghost-button" onclick="return confirm('Send rent reminder to <?= htmlspecialchars(addslashes($due['occupant']['name']), ENT_QUOTES, 'UTF-8') ?>?');">Send reminder</button>
                                </form>
                            <?php else: ?>
                                <span class="muted-text" style="font-size:.8rem">No email</span>
                            <?php endif; ?>
                            <a class="ghost-button" href="<?= htmlspecialchars($detailHref, ENT_QUOTES, 'UTF-8') ?>">View unit</a>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="admin-section">
        <div class="section-heading">
            <div>
                <span class="eyebrow">Email logs</span>
                <h2>Recent rent reminder emails</h2>
            </div>
        </div>

        <?php if ($recentLogs === array()): ?>
            <article class="empty-state">
                <h2>No emails sent yet.</h2>
                <p>Email logs for rent reminders will appear here once reminders are sent.</p>
            </article>
        <?php else: ?>
            <div class="record-table">
                <div class="record-table-row record-table-head">
                    <span>Recipient</span>
                    <span>Subject</span>
                    <span>Status</span>
                    <span>Date</span>
                </div>
                <?php foreach ($recentLogs as $log): ?>
                    <div class="record-table-row">
                        <span>
                            <strong><?= htmlspecialchars($log['recipientName'] !== '' ? $log['recipientName'] : '—', ENT_QUOTES, 'UTF-8') ?></strong>
                            <span class="muted-text" style="display:block; font-size:.8rem"><?= htmlspecialchars($log['recipientEmail'], ENT_QUOTES, 'UTF-8') ?></span>
                        </span>
                        <span class="muted-text" style="font-size:.85rem"><?= htmlspecialchars($log['subject'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span>
                            <span class="admin-status-pill <?= htmlspecialchars($log['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(ucfirst($log['status']), ENT_QUOTES, 'UTF-8') ?></span>
                            <?php if ($log['errorMessage'] !== ''): ?>
                                <span class="muted-text text-danger" style="display:block; font-size:.78rem"><?= htmlspecialchars($log['errorMessage'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </span>
                        <span class="muted-text"><?= htmlspecialchars(app_format_datetime($log['createdAt']), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</section>
