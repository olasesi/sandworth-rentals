<?php
/** @var array $properties */
/** @var array $charges */
/** @var array $oldInput */

$activeAdminPage = 'charges';
$adminTitle = 'Rent & service charge management.';
$adminDescription = 'Set the annual rent and monthly service charge for each property. Service charge changes are recorded in a history table and apply from the effective date you choose.';

require dirname(__DIR__) . '/partials/admin-header.php';
?>

<section class="admin-workspace">
    <section class="admin-section">
        <div class="section-heading">
            <div>
                <span class="eyebrow">Charges</span>
                <h2>Rent and service charge per property</h2>
                <p class="muted-text">Annual rent applies to new and renewed tenancies only. Service charge history is applied retroactively to tenant billing schedules.</p>
            </div>
        </div>

        <?php if ($properties === array()): ?>
            <article class="empty-state">
                <h2>No properties loaded.</h2>
                <p>Load a property first before setting rent or service charges.</p>
            </article>
        <?php else: ?>
            <div class="record-table">
                <div class="record-table-row record-table-row--charges record-table-head">
                    <span>Property</span>
                    <span>Purpose</span>
                    <span>Annual Rent</span>
                    <span>Monthly (derived)</span>
                    <span>Service Charge</span>
                    <span>Deposit</span>
                    <span>Rent History</span>
                    <span>SC History</span>
                </div>
                <?php foreach ($properties as $property): ?>
                    <?php
                        $id = (int) $property['id'];
                        $cc = isset($charges[$id]) ? $charges[$id] : array('current' => array('monthlyRent' => 0, 'annualRent' => 0, 'serviceCharge' => 0, 'securityDeposit' => 0), 'history' => array(), 'rentHistory' => array());
                        $current = $cc['current'];
                        $history = $cc['history'];
                        $rentHistory = isset($cc['rentHistory']) ? $cc['rentHistory'] : array();
                        $purpose = isset($property['purpose']) ? (string) $property['purpose'] : 'rent';
                        $historyCount = count($history);
                        $recentHistory = array_slice($history, max(0, $historyCount - 3), 3);
                        $rentHistoryCount = count($rentHistory);
                        $recentRentHistory = array_slice($rentHistory, max(0, $rentHistoryCount - 3), 3);
                        $showAnnualRent = (int) $current['annualRent'] > 0 ? (int) $current['annualRent'] : (int) $property['monthlyRent'];
                    ?>
                    <div class="record-table-row record-table-row--charges">
                        <span>
                            <strong><?= htmlspecialchars((string) $property['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <span class="muted-text" style="display:block; font-size:.8rem"><?= htmlspecialchars((string) $property['location'], ENT_QUOTES, 'UTF-8') ?></span>
                        </span>
                        <span><?= htmlspecialchars(ucfirst($purpose), ENT_QUOTES, 'UTF-8') ?></span>
                        <span style="font-weight:700"><?= htmlspecialchars(app_currency($showAnnualRent), ENT_QUOTES, 'UTF-8') ?></span>
                        <span style="font-weight:700"><?= htmlspecialchars(app_currency($purpose === 'sale' || $showAnnualRent <= 0 ? 0 : (int) round($showAnnualRent / 12)), ENT_QUOTES, 'UTF-8') ?></span>
                        <span style="font-weight:700"><?= htmlspecialchars(app_currency($current['serviceCharge']), ENT_QUOTES, 'UTF-8') ?></span>
                        <span style="font-weight:700"><?= htmlspecialchars(app_currency($current['securityDeposit']), ENT_QUOTES, 'UTF-8') ?></span>
                        <span>
                            <?php if ($recentRentHistory !== array()): ?>
                                <?php foreach ($recentRentHistory as $h): ?>
                                    <span style="display:block; font-size:.8rem">
                                        <strong><?= htmlspecialchars((string) $h['effectiveFrom'], ENT_QUOTES, 'UTF-8') ?>:</strong>
                                        <?= htmlspecialchars(app_currency((int) $h['annualRent']), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                <?php endforeach; ?>
                                <?php if ($rentHistoryCount > 3): ?>
                                    <span class="muted-text" style="display:block; font-size:.75rem">+ <?= ($rentHistoryCount - 3) ?> more</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="muted-text" style="font-size:.8rem">No history yet</span>
                            <?php endif; ?>
                        </span>
                        <span>
                            <?php if ($recentHistory !== array()): ?>
                                <?php foreach ($recentHistory as $i => $h): ?>
                                    <span style="display:block; font-size:.8rem">
                                        <strong><?= htmlspecialchars((string) $h['effectiveMonth'], ENT_QUOTES, 'UTF-8') ?>:</strong>
                                        <?= htmlspecialchars(app_currency((int) $h['serviceCharge']), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                <?php endforeach; ?>
                                <?php if ($historyCount > 3): ?>
                                    <span class="muted-text" style="display:block; font-size:.75rem">+ <?= ($historyCount - 3) ?> more</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="muted-text" style="font-size:.8rem">No history yet</span>
                            <?php endif; ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>

            <h3 style="margin-top:2rem">Update property charges</h3>
            <p class="muted-text" style="margin-bottom:1rem">Pick a property and enter the new rate. Both charges keep a dated history, so tenants are billed the rate that applied to the period being paid and anything they have already paid stays put. Rent is charged per year, so its change date is a day rather than a month.</p>

            <div class="admin-form-grid" style="grid-template-columns: 1fr 1fr; gap:2rem;">
                <!-- Rent update form -->
                <div style="border:1px solid var(--line); border-radius:12px; padding:1.5rem;">
                    <h4 style="margin-top:0">Update annual rent</h4>
                    <form action="<?= htmlspecialchars(app_url('admin-charge-update'), ENT_QUOTES, 'UTF-8') ?>" method="post" class="admin-form">
                        <input type="hidden" name="field" value="rent">
                        <div class="admin-form-grid" style="grid-template-columns:1fr 1fr;">
                            <div class="admin-field">
                                <label for="rent-property">Property</label>
                                <select id="rent-property" name="property_id" required>
                                    <option value="">Select property</option>
                                    <?php foreach ($properties as $property): ?>
                                        <?php if ((string) $property['purpose'] !== 'sale'): ?>
                                            <option value="<?= (int) $property['id'] ?>"<?= isset($oldInput['property_id']) && (int) $oldInput['property_id'] === (int) $property['id'] && isset($oldInput['field']) && $oldInput['field'] === 'rent' ? ' selected' : '' ?>><?= htmlspecialchars((string) $property['title'], ENT_QUOTES, 'UTF-8') ?></option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="admin-field">
                                <label for="rent-amount">New annual rent (<?= htmlspecialchars(app_currency(0), ENT_QUOTES, 'UTF-8') ?>)</label>
                                <input id="rent-amount" name="annual_rent" type="number" min="0" step="1000" placeholder="e.g. 5000000" value="<?= isset($oldInput['annual_rent']) ? htmlspecialchars((string) $oldInput['annual_rent'], ENT_QUOTES, 'UTF-8') : '' ?>" required>
                            </div>
                        </div>
                        <div class="admin-field">
                            <label for="rent-effective">Effective from date</label>
                            <input id="rent-effective" name="rent_effective_from" type="date" value="<?= isset($oldInput['rent_effective_from']) ? htmlspecialchars((string) $oldInput['rent_effective_from'], ENT_QUOTES, 'UTF-8') : date('Y-m-d') ?>" required>
                            <p class="muted-text">Rent is charged per year, so the new rate starts from the date you pick. Tenancies already running keep the rent they signed, and a renewal picks up whatever rate is in force when it starts.</p>
                        </div>
                        <button type="submit" class="solid-button" onclick="return confirm('Update the annual rent for this property? Tenancies already running are not affected.');">Save rent</button>
                    </form>
                </div>

                <!-- Service charge update form -->
                <div style="border:1px solid var(--line); border-radius:12px; padding:1.5rem;">
                    <h4 style="margin-top:0">Update monthly service charge</h4>
                    <form action="<?= htmlspecialchars(app_url('admin-charge-update'), ENT_QUOTES, 'UTF-8') ?>" method="post" class="admin-form">
                        <input type="hidden" name="field" value="service_charge">
                        <div class="admin-form-grid" style="grid-template-columns:1fr 1fr;">
                            <div class="admin-field">
                                <label for="sc-property">Property</label>
                                <select id="sc-property" name="property_id" required>
                                    <option value="">Select property</option>
                                    <?php foreach ($properties as $property): ?>
                                        <option value="<?= (int) $property['id'] ?>"<?= isset($oldInput['property_id']) && (int) $oldInput['property_id'] === (int) $property['id'] && isset($oldInput['field']) && $oldInput['field'] === 'service_charge' ? ' selected' : '' ?>><?= htmlspecialchars((string) $property['title'], ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="admin-field">
                                <label for="sc-amount">New monthly rate (<?= htmlspecialchars(app_currency(0), ENT_QUOTES, 'UTF-8') ?>)</label>
                                <input id="sc-amount" name="amount" type="number" min="0" step="1000" placeholder="e.g. 25000" value="<?= isset($oldInput['amount']) ? htmlspecialchars((string) $oldInput['amount'], ENT_QUOTES, 'UTF-8') : '' ?>" required>
                            </div>
                        </div>
                        <div class="admin-field">
                            <label for="sc-effective">Effective from month</label>
                            <input id="sc-effective" name="effective_from" type="month" value="<?= isset($oldInput['effective_from']) ? htmlspecialchars((string) $oldInput['effective_from'], ENT_QUOTES, 'UTF-8') : date('Y-m') ?>" required>
                            <p class="muted-text">Leave on the current month unless you are correcting a past billing rate. History is maintained per month so tenants only see the correct amount.</p>
                        </div>
                        <button type="submit" class="solid-button" onclick="return confirm('Update the service charge rate for this property? The change will appear on tenant billing schedules from the selected month onward.');">Save service charge</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </section>
</section>
