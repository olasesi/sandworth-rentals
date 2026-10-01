<?php
/** @var array $portfolio */
/** @var array $registration */
/** @var array $oldInput */

$tenant = $registration;
$hasOldInput = $oldInput !== array();
$unitTable = (string) $tenant['unitTable'];

if ($unitTable === 'mall_shops') {
    $unitLabel = $tenant['shopName'] !== '' ? $tenant['shopName'] : ($tenant['shopNumber'] !== '' ? 'Shop ' . $tenant['shopNumber'] : 'Shop #' . $tenant['unitId']);
} elseif ($unitTable === 'apartment_flats') {
    $unitLabel = $tenant['flatNumber'] !== '' ? $tenant['flatNumber'] : 'Flat #' . $tenant['unitId'];
} else {
    $unitLabel = $tenant['unitNumber'] !== '' ? $tenant['unitNumber'] : 'Unit #' . $tenant['unitId'];
}

$balances = $tenant['balances'];
$sc = $tenant['scSummary'];
$rentYears = $tenant['rentYears'];
$classifications = $tenant['paymentTypes'];
$tenureBlocks = $tenant['tenureBlocks'];
$rentPeriods = $tenant['rentPeriods'];
$scPeriods = $tenant['scPeriods'];
$scMonthPeriods = $tenant['scMonthPeriods'];
$rentPeriodPayments = $tenant['rentPeriodPayments'];
$scMonthPayments = $tenant['scMonthPayments'];

$rentOwed = (int) $balances['totalOwed'];
$scOwed = (int) $sc['outstanding'];
$rentTenureOwed = (int) $balances['tenureOwed'];
$rentPriorArrears = (int) $balances['priorArrears'];
$scArrears = (int) $sc['arrears'];
$scCurrent = (int) $sc['currentPeriod'];

$typeLabels = array(
    'rent' => 'Rent',
    'service_charge' => 'Service charge',
    'deposit' => 'Deposit',
    'other' => 'Other',
);

$typeTargets = array(
    'rent' => array(
        'label' => 'Rent installment',
        'owed' => $rentOwed,
        'heading' => 'Rent arrears for this tenure',
        'total' => $rentOwed,
        'lines' => array(
            array('label' => 'Rent payable on this tenure', 'amount' => $rentTenureOwed),
            array('label' => 'Arrears carried from an earlier period', 'amount' => $rentPriorArrears),
        ),
        'note' => 'A rent payment reduces the rent payable for the tenure, oldest year first.',
    ),
    'service_charge' => array(
        'label' => 'Service charge',
        'owed' => $scOwed,
        'heading' => 'Service charge arrears',
        'total' => $scOwed,
        'lines' => array(
            array('label' => 'Arrears from earlier months', 'amount' => $scArrears),
            array('label' => 'Current billing year', 'amount' => $scCurrent),
        ),
        'note' => 'A service charge payment is applied to the oldest unpaid billing months first.',
    ),
    'deposit' => array(
        'label' => 'Security deposit',
        'owed' => 0,
        'heading' => 'Caution deposit',
        'total' => 0,
        'lines' => array(
            array('label' => 'Caution deposit on this registration', 'amount' => (int) $tenant['securityDeposit']),
        ),
        'note' => 'A deposit sits on the ledger and is not settled against rent or service charge.',
    ),
    'other' => array(
        'label' => 'Other payment',
        'owed' => 0,
        'heading' => 'Other payment',
        'total' => 0,
        'lines' => array(),
        'note' => 'Recorded on the ledger without reducing rent or service charge.',
    ),
);

// Payments grouped the way they settle, so the admin sees arrears and what has cleared them together.
$rentPayments = array();
$serviceChargePayments = array();
$otherPayments = array();

foreach (array_keys((array) $tenant['payments']) as $paymentIndex) {
    $payment = (array) $tenant['payments'][$paymentIndex];
    $meta = isset($classifications[(int) $payment['id']]) ? $classifications[(int) $payment['id']] : array('type' => 'other', 'rentYearNumber' => 0, 'rentYearLabel' => '', 'settlesArrears' => false, 'periodKey' => '');
    $payment['metaType'] = (string) $meta['type'];
    $payment['metaYearLabel'] = (string) $meta['rentYearLabel'];
    $payment['metaSettlesArrears'] = (bool) $meta['settlesArrears'];
    $payment['metaPeriodKey'] = (string) $meta['periodKey'];
    $tenant['payments'][$paymentIndex] = $payment;

    if (! isset($typeLabels[$payment['metaType']])) {
        $payment['metaType'] = 'other';
    }

    if ($payment['metaType'] === 'rent') {
        $rentPayments[] = $payment;
    } elseif ($payment['metaType'] === 'service_charge') {
        $serviceChargePayments[] = $payment;
    } else {
        $otherPayments[] = $payment;
    }
}

$activeAdminPage = 'tenants';
$adminTitle = 'Edit the tenancy payments.';
$adminDescription = 'Review the payments posted to this tenant, change the rent or service charge a payment settles, and see how each one clears the arrears.';

/**
 * One editable line of the breakdown: what is billed, what has been paid against it, the payments
 * already sitting on that period, and a form to post more straight against the period.
 */
$renderBreakdownEditor = function (array $period, $chargeType, array $payments) use ($tenant, $unitTable) {
    $chargeType = (string) $chargeType;
    $periodKey = (string) $period['key'];
    $remaining = (int) $period['remaining'];
    $slug = $chargeType . '-' . preg_replace('/[^a-z0-9]+/i', '-', $periodKey);
    $isSettled = $remaining <= 0;
    ?>
    <div class="breakdown-editor" data-breakdown-row>
        <div class="breakdown-editor__payments">
            <?php if ((array) $payments === array()): ?>
                <p class="muted-text" style="margin:0">Nothing has been posted against this period yet.</p>
            <?php else: ?>
                <?php foreach ($payments as $payment): ?>
                    <div class="breakdown-editor__payment">
                        <span>
                            <strong><?= htmlspecialchars(app_currency((int) $payment['amount']), ENT_QUOTES, 'UTF-8') ?></strong>
                            <span class="muted-text" style="display:block; font-size:.78rem">
                                <?= htmlspecialchars((string) $payment['date'], ENT_QUOTES, 'UTF-8') ?>
                                &middot; <?= htmlspecialchars(ucwords((string) $payment['channel']), ENT_QUOTES, 'UTF-8') ?>
                                <?php if ((string) $payment['reference'] !== ''): ?>
                                    &middot; <?= htmlspecialchars((string) $payment['reference'], ENT_QUOTES, 'UTF-8') ?>
                                <?php endif; ?>
                            </span>
                        </span>
                        <span class="breakdown-editor__actions">
                            <a class="ghost-button" href="#payment-<?= (int) $payment['paymentId'] ?>">Edit</a>
                            <button
                                type="submit"
                                class="ghost-button danger-button"
                                form="remove-<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>-<?= (int) $payment['paymentId'] ?>"
                                onclick="return confirm('Remove this payment from the ledger? The period goes back to what is still owing.')"
                            >Remove</button>
                        </span>
                    </div>
                    <form id="remove-<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>-<?= (int) $payment['paymentId'] ?>" action="<?= htmlspecialchars(app_url('admin-tenant-payment-delete'), ENT_QUOTES, 'UTF-8') ?>" method="post" hidden>
                        <input type="hidden" name="unit_table" value="<?= htmlspecialchars((string) $unitTable, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="unit_id" value="<?= (int) $tenant['unitId'] ?>">
                        <input type="hidden" name="payment_id" value="<?= (int) $payment['paymentId'] ?>">
                    </form>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <form action="<?= htmlspecialchars(app_url('admin-tenant-period-payment'), ENT_QUOTES, 'UTF-8') ?>" method="post" class="breakdown-editor__form">
            <input type="hidden" name="unit_table" value="<?= htmlspecialchars((string) $unitTable, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="unit_id" value="<?= (int) $tenant['unitId'] ?>">
            <input type="hidden" name="charge_type" value="<?= htmlspecialchars($chargeType, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="period_key" value="<?= htmlspecialchars($periodKey, ENT_QUOTES, 'UTF-8') ?>">

            <label for="amount-<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>">Amount</label>
            <input id="amount-<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>" name="amount" type="number" min="1" step="1" value="<?= $remaining > 0 ? $remaining : '' ?>" <?= $isSettled ? 'disabled' : '' ?> required>

            <label for="date-<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>">Date</label>
            <input id="date-<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>" name="paid_at" type="date" value="<?= htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" <?= $isSettled ? 'disabled' : '' ?>>

            <label for="channel-<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>">Channel</label>
            <select id="channel-<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>" name="channel" <?= $isSettled ? 'disabled' : '' ?>>
                <?php foreach (array('cash', 'transfer', 'pos', 'cheque') as $channel): ?>
                    <option value="<?= htmlspecialchars($channel, ENT_QUOTES, 'UTF-8') ?>"<?= $channel === 'transfer' ? ' selected' : '' ?>><?= htmlspecialchars(ucfirst($channel), ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>

            <label for="label-<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>">Description</label>
            <input id="label-<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>" name="label" type="text" placeholder="Optional note" <?= $isSettled ? 'disabled' : '' ?>>

            <button type="submit" class="solid-button" <?= $isSettled ? 'disabled' : '' ?>>
                <?= $isSettled ? 'Settled' : 'Post payment' ?>
            </button>
        </form>
    </div>
    <?php
};

require dirname(__DIR__) . '/partials/admin-header.php';
?>

<section class="admin-workspace">
    <section class="admin-section">
        <div class="section-heading">
            <div>
                <span class="eyebrow">Tenancy payments</span>
                <h2>Payments on <?= htmlspecialchars((string) $tenant['user']['name'], ENT_QUOTES, 'UTF-8') ?></h2>
            </div>
            <div class="admin-page-actions">
                <a class="ghost-button" href="<?= htmlspecialchars(app_url('admin-tenants', array('view_tenant' => $tenant['unitId'], 'unit_table' => $unitTable)), ENT_QUOTES, 'UTF-8') ?>">&larr; Back to tenant</a>
                <a class="ghost-button" href="<?= htmlspecialchars(app_url('admin-tenants', array('edit_tenant' => $tenant['unitId'], 'unit_table' => $unitTable)), ENT_QUOTES, 'UTF-8') ?>">Edit tenant information</a>
                <a class="solid-button" href="<?= htmlspecialchars(app_url('admin-tenant-payment-new', array('unit_table' => $unitTable, 'unit_id' => $tenant['unitId'], 'type' => 'rent')), ENT_QUOTES, 'UTF-8') ?>">Record rent payment</a>
                <a class="solid-button solid-button-navy" href="<?= htmlspecialchars(app_url('admin-tenant-payment-new', array('unit_table' => $unitTable, 'unit_id' => $tenant['unitId'], 'type' => 'service_charge')), ENT_QUOTES, 'UTF-8') ?>">Record SC payment</a>
                <a class="ghost-button" href="<?= htmlspecialchars(app_url('admin-tenant-payment-new', array('unit_table' => $unitTable, 'unit_id' => $tenant['unitId'], 'type' => 'deposit')), ENT_QUOTES, 'UTF-8') ?>">Record deposit</a>
                <a class="ghost-button" href="<?= htmlspecialchars(app_url('admin-tenant-payment-new', array('unit_table' => $unitTable, 'unit_id' => $tenant['unitId'], 'type' => 'other')), ENT_QUOTES, 'UTF-8') ?>">Record other</a>
            </div>
        </div>

        <div class="admin-editor-state">
            <span class="type-pill"><?= htmlspecialchars((string) $unitTable, ENT_QUOTES, 'UTF-8') ?> #<?= (int) $tenant['unitId'] ?></span>
            <p class="muted-text">
                <?= htmlspecialchars((string) ($tenant['property'] ? $tenant['property']['title'] : 'Property'), ENT_QUOTES, 'UTF-8') ?>
                · <?= htmlspecialchars($unitLabel, ENT_QUOTES, 'UTF-8') ?>
                · <?= htmlspecialchars(trim((string) $tenant['tenure'] . ' (' . $tenant['startDate'] . ' → ' . $tenant['endDate'] . ')'), ENT_QUOTES, 'UTF-8') ?>
            </p>
        </div>

        <div class="manager-stats" style="margin-bottom:22px">
            <article>
                <span>Rent for the tenure</span>
                <strong><?= htmlspecialchars(app_currency((int) $balances['tenureTotal']), ENT_QUOTES, 'UTF-8') ?></strong>
                <span class="muted-text" style="font-size:.78rem"><?= (int) $balances['tenureYears'] ?> year<?= (int) $balances['tenureYears'] === 1 ? '' : 's' ?> at <?= htmlspecialchars(app_currency((int) $balances['annualRent']), ENT_QUOTES, 'UTF-8') ?>/yr</span>
            </article>
            <article>
                <span>Rent paid</span>
                <strong><?= htmlspecialchars(app_currency((int) $balances['tenurePaid']), ENT_QUOTES, 'UTF-8') ?></strong>
            </article>
            <article>
                <span>Rent owed</span>
                <strong<?= $rentOwed > 0 ? ' class="text-danger"' : '' ?>><?= htmlspecialchars(app_currency($rentOwed), ENT_QUOTES, 'UTF-8') ?></strong>
            </article>
            <article>
                <span>Service charge owed</span>
                <strong<?= $scOwed > 0 ? ' class="text-danger"' : '' ?>><?= htmlspecialchars(app_currency($scOwed), ENT_QUOTES, 'UTF-8') ?></strong>
            </article>
        </div>

        <div class="section-heading" style="margin-bottom:12px">
            <div>
                <span class="eyebrow">Rent</span>
                <h3>Rent payments and the arrears they clear</h3>
            </div>
        </div>

        <div class="manager-stats" style="margin-bottom:14px">
            <article>
                <span>Rent payable on this term</span>
                <strong<?= $rentTenureOwed > 0 ? ' class="text-danger"' : '' ?>><?= htmlspecialchars(app_currency($rentTenureOwed), ENT_QUOTES, 'UTF-8') ?></strong>
            </article>
            <article>
                <span>Arrears from an earlier period</span>
                <strong<?= $rentPriorArrears > 0 ? ' class="text-danger"' : '' ?>><?= htmlspecialchars(app_currency($rentPriorArrears), ENT_QUOTES, 'UTF-8') ?></strong>
            </article>
            <article class="<?= $rentOwed > 0 ? 'stat-highlight-danger' : '' ?>">
                <span>Rent owed in total</span>
                <strong<?= $rentOwed > 0 ? ' class="text-danger"' : '' ?>><?= htmlspecialchars(app_currency($rentOwed), ENT_QUOTES, 'UTF-8') ?></strong>
            </article>
        </div>

        <details class="prior-tenure-block" open style="border:1px solid var(--line); border-radius:12px; margin-bottom:16px; padding:14px 18px;">
            <summary style="cursor:pointer; font-weight:600;">Rent by year of the tenure</summary>
            <p class="muted-text" style="margin:10px 0 0">Every year can be paid, adjusted or cleared on its own. Posting a payment here keeps it against that year; editing or removing it stays in the history below.</p>
            <div class="manager-stats" style="margin:12px 0">
                <article>
                    <span>Total billed</span>
                    <strong><?= htmlspecialchars(app_currency((int) $rentPeriods['totalBilled']), ENT_QUOTES, 'UTF-8') ?></strong>
                </article>
                <article>
                    <span>Total paid</span>
                    <strong><?= htmlspecialchars(app_currency((int) $rentPeriods['totalPaid']), ENT_QUOTES, 'UTF-8') ?></strong>
                </article>
                <article>
                    <span>Arrears</span>
                    <strong<?= $rentOwed > 0 ? ' class="text-danger"' : '' ?>><?= htmlspecialchars(app_currency($rentOwed), ENT_QUOTES, 'UTF-8') ?></strong>
                </article>
            </div>
            <div class="record-table">
                <div class="record-table-row record-table-head">
                    <span>Year</span>
                    <span>Billed</span>
                    <span>Paid</span>
                    <span>Remaining</span>
                    <span>Adjust</span>
                </div>
                <?php foreach ((array) $rentPeriods['periods'] as $period): ?>
                    <?php $periodKey = (string) $period['key']; ?>
                    <div class="record-table-row record-table-row--breakdown<?= (int) $period['remaining'] > 0 ? ' record-table-row--highlight' : '' ?>">
                        <span>
                            <strong><?= htmlspecialchars((string) $period['label'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <?php if ((int) $period['isArrears']): ?>
                                <span class="type-pill pill-owing" style="margin-left:8px">Arrears</span>
                            <?php endif; ?>
                            <?php if (! empty($period['isCurrent'])): ?>
                                <span class="type-pill pill-active" style="margin-left:8px">Running year</span>
                            <?php endif; ?>
                        </span>
                        <span><?= htmlspecialchars(app_currency((int) $period['billed']), ENT_QUOTES, 'UTF-8') ?></span>
                        <span><?= htmlspecialchars(app_currency((int) $period['paid']), ENT_QUOTES, 'UTF-8') ?></span>
                        <span>
                            <strong<?= (int) $period['remaining'] > 0 ? ' class="text-danger"' : '' ?>><?= htmlspecialchars(app_currency((int) $period['remaining']), ENT_QUOTES, 'UTF-8') ?></strong>
                        </span>
                        <span>
                            <?php $renderBreakdownEditor($period, 'rent', isset($rentPeriodPayments[$periodKey]) ? (array) $rentPeriodPayments[$periodKey] : array()); ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div data-unallocated-payments style="margin-top:14px">
                <p class="muted-text" style="margin:0 0 8px">Rent payments not tied to one year of the tenure. They still clear the rent balance, oldest year first.</p>
                <?php $renderBreakdownEditor(array('key' => 'unallocated', 'label' => 'Unallocated rent payments', 'billed' => 0, 'paid' => 0, 'remaining' => 0, 'isSettled' => true), 'rent', isset($rentPeriodPayments['unallocated']) ? (array) $rentPeriodPayments['unallocated'] : array()); ?>
            </div>
        </details>

        <?php
        $renderPaymentEditor = function (array $payment) use ($tenant, $unitTable, $typeLabels, $typeTargets, $rentPeriods, $scPeriods) {
            $paymentId = (int) $payment['id'];
            $type = (string) $payment['metaType'];
            if (! isset($typeTargets[$type])) {
                $type = 'other';
            }

            $target = $typeTargets[$type];
            $label = $payment['description'];
            $prefix = $type === 'rent' ? 'Rent payment' : ($type === 'service_charge' ? 'Service charge' : ($type === 'deposit' ? 'Deposit payment' : 'Other payment'));
            $customLabel = stripos($label, $prefix . ' - ') === 0 ? substr($label, strlen($prefix) + 3) : $label;
            $paidDate = $payment['createdAt'] !== '' ? substr($payment['createdAt'], 0, 10) : '';
            $currentPeriodKey = (string) $payment['metaPeriodKey'];
            ?>
            <form action="<?= htmlspecialchars(app_url('admin-tenant-payment-update'), ENT_QUOTES, 'UTF-8') ?>" method="post" class="payment-editor" data-payment-editor>
                <input type="hidden" name="unit_table" value="<?= htmlspecialchars((string) $unitTable, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="unit_id" value="<?= (int) $tenant['unitId'] ?>">
                <input type="hidden" name="payment_id" value="<?= $paymentId ?>">

                <div class="record-table-row" style="align-items:flex-start; gap:14px; flex-wrap:wrap">
                    <span style="min-width:150px">
                        <strong><?= htmlspecialchars((string) ($payment['createdAt'] !== '' ? substr($payment['createdAt'], 0, 10) : '—'), ENT_QUOTES, 'UTF-8') ?></strong>
                        <span class="muted-text" style="display:block; font-size:.78rem"><?= htmlspecialchars((string) $payment['reference'], ENT_QUOTES, 'UTF-8') ?></span>
                    </span>
                    <span style="min-width:110px">
                        <span class="type-pill <?= $type === 'rent' ? 'pill-active' : ($type === 'service_charge' ? 'pill-ongoing' : '') ?>"><?= htmlspecialchars(isset($typeLabels[$type]) ? $typeLabels[$type] : 'Other', ENT_QUOTES, 'UTF-8') ?></span>
                        <?php if ($payment['metaYearLabel'] !== ''): ?>
                            <span class="muted-text" style="display:block; font-size:.78rem"><?= htmlspecialchars((string) $payment['metaYearLabel'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                        <?php if ($payment['metaSettlesArrears']): ?>
                            <span class="type-pill pill-owing" style="margin-top:4px">Clears arrears</span>
                        <?php endif; ?>
                    </span>
                    <span style="flex:1; min-width:260px">
                        <label for="payment-type-<?= $paymentId ?>">Type</label>
                        <select id="payment-type-<?= $paymentId ?>" name="charge_type" data-payment-type>
                            <?php foreach ($typeLabels as $typeKey => $typeLabel): ?>
                                <option value="<?= htmlspecialchars((string) $typeKey, ENT_QUOTES, 'UTF-8') ?>"<?= $typeKey === $type ? ' selected' : '' ?>><?= htmlspecialchars((string) $typeLabel, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </span>
                    <span style="flex:1; min-width:260px">
                        <label for="payment-period-<?= $paymentId ?>">Applied to</label>
                        <select id="payment-period-<?= $paymentId ?>" name="period_key" data-payment-period>
                            <?php if ((array) $rentPeriods['periods'] !== array()): ?>
                                <optgroup label="Rent year of the tenure" data-period-group="rent">
                                    <?php foreach ((array) $rentPeriods['periods'] as $period): ?>
                                        <option value="<?= htmlspecialchars((string) $period['key'], ENT_QUOTES, 'UTF-8') ?>" data-period-type="rent"<?= (string) $period['key'] === $currentPeriodKey ? ' selected' : '' ?>>
                                            <?= htmlspecialchars((string) $period['label'], ENT_QUOTES, 'UTF-8') ?> — <?= ! empty($period['isSettled']) ? 'settled' : htmlspecialchars(app_currency((int) $period['remaining']), ENT_QUOTES, 'UTF-8') . ' owing' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endif; ?>
                            <?php if ((array) $scPeriods['periods'] !== array()): ?>
                                <optgroup label="Service charge billing year" data-period-group="service_charge">
                                    <?php foreach ((array) $scPeriods['periods'] as $period): ?>
                                        <option value="<?= htmlspecialchars((string) $period['key'], ENT_QUOTES, 'UTF-8') ?>" data-period-type="service_charge"<?= (string) $period['key'] === $currentPeriodKey ? ' selected' : '' ?>>
                                            <?= htmlspecialchars((string) $period['label'], ENT_QUOTES, 'UTF-8') ?> — <?= ! empty($period['isSettled']) ? 'settled' : htmlspecialchars(app_currency((int) $period['remaining']), ENT_QUOTES, 'UTF-8') . ' owing' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endif; ?>
                        </select>
                    </span>
                    <span style="min-width:130px">
                        <label for="payment-amount-<?= $paymentId ?>">Amount</label>
                        <input id="payment-amount-<?= $paymentId ?>" name="amount" type="number" min="1" step="1" value="<?= (int) $payment['amount'] ?>" data-payment-amount>
                    </span>
                    <span style="min-width:150px">
                        <label for="payment-date-<?= $paymentId ?>">Date paid</label>
                        <input id="payment-date-<?= $paymentId ?>" name="paid_at" type="date" value="<?= htmlspecialchars((string) $paidDate, ENT_QUOTES, 'UTF-8') ?>">
                    </span>
                    <span style="min-width:130px">
                        <label for="payment-channel-<?= $paymentId ?>">Channel</label>
                        <select id="payment-channel-<?= $paymentId ?>" name="channel">
                            <?php foreach (array('cash' => 'Cash', 'transfer' => 'Transfer', 'card' => 'Card') as $channelKey => $channelLabel): ?>
                                <option value="<?= htmlspecialchars((string) $channelKey, ENT_QUOTES, 'UTF-8') ?>"<?= (string) $payment['channel'] === (string) $channelKey ? ' selected' : '' ?>><?= htmlspecialchars((string) $channelLabel, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </span>
                    <span style="min-width:180px">
                        <label for="payment-reference-<?= $paymentId ?>">Reference</label>
                        <input id="payment-reference-<?= $paymentId ?>" name="reference" type="text" value="<?= htmlspecialchars((string) $payment['reference'], ENT_QUOTES, 'UTF-8') ?>">
                    </span>
                    <span style="min-width:180px">
                        <label for="payment-label-<?= $paymentId ?>">Description</label>
                        <input id="payment-label-<?= $paymentId ?>" name="label" type="text" value="<?= htmlspecialchars((string) $customLabel, ENT_QUOTES, 'UTF-8') ?>" data-payment-label>
                    </span>
                </div>

                <div class="admin-editor-state" style="margin:10px 0 0">
                    <p class="muted-text" style="margin:0" data-payment-target-note><?= htmlspecialchars((string) $target['note'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="muted-text" style="margin:6px 0 0" data-payment-balance-note></p>
                </div>

                <div class="admin-form-actions" style="margin-top:12px">
                    <button type="submit" class="solid-button">Save payment</button>
                </div>
            </form>
            <?php
        };
        ?>

        <div class="record-table" style="margin-bottom:26px">
            <div class="record-table-row record-table-head">
                <span>Payment</span>
                <span>Type</span>
                <span>Amount</span>
                <span>Channel</span>
                <span>Reference</span>
            </div>
            <?php if ($rentPayments === array()): ?>
                <div class="record-table-row">
                    <span>No rent payments have been recorded for this tenant.</span>
                    <span></span><span></span><span></span><span></span>
                </div>
            <?php else: ?>
                <?php foreach ($rentPayments as $payment): ?>
                    <div class="record-table-row">
                        <span><strong><?= htmlspecialchars((string) substr((string) $payment['createdAt'], 0, 10), ENT_QUOTES, 'UTF-8') ?></strong><?php if ($payment['metaYearLabel'] !== ''): ?><span class="muted-text" style="display:block; font-size:.78rem"><?= htmlspecialchars((string) $payment['metaYearLabel'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?></span>
                        <span>
                            <?= htmlspecialchars((string) $payment['description'], ENT_QUOTES, 'UTF-8') ?>
                            <?php if ($payment['metaSettlesArrears']): ?>
                                <span class="type-pill pill-owing" style="margin-left:8px">Clears arrears</span>
                            <?php endif; ?>
                        </span>
                        <span><strong><?= htmlspecialchars(app_currency((int) $payment['amount']), ENT_QUOTES, 'UTF-8') ?></strong></span>
                        <span><?= htmlspecialchars(ucwords((string) $payment['channel']), ENT_QUOTES, 'UTF-8') ?></span>
                        <span><?= htmlspecialchars((string) $payment['reference'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="section-heading" style="margin-bottom:12px">
            <div>
                <span class="eyebrow">Service charges</span>
                <h3>Service charge payments and the months they clear</h3>
            </div>
        </div>

        <div class="manager-stats" style="margin-bottom:14px">
            <article>
                <span>Arrears from earlier months</span>
                <strong<?= $scArrears > 0 ? ' class="text-danger"' : '' ?>><?= htmlspecialchars(app_currency($scArrears), ENT_QUOTES, 'UTF-8') ?></strong>
            </article>
            <article>
                <span>Current billing year</span>
                <strong<?= $scCurrent > 0 ? ' class="text-danger"' : '' ?>><?= htmlspecialchars(app_currency($scCurrent), ENT_QUOTES, 'UTF-8') ?></strong>
            </article>
            <article class="<?= $scOwed > 0 ? 'stat-highlight-danger' : '' ?>">
                <span>Service charge owed in total</span>
                <strong<?= $scOwed > 0 ? ' class="text-danger"' : '' ?>><?= htmlspecialchars(app_currency($scOwed), ENT_QUOTES, 'UTF-8') ?></strong>
            </article>
        </div>

        <?php if ((array) $scMonthPeriods['periods'] !== array()): ?>
            <details class="prior-tenure-block" style="border:1px solid var(--line); border-radius:12px; margin-bottom:16px; padding:14px 18px;">
                <summary style="cursor:pointer; font-weight:600;">Service charge month by month</summary>
                <p class="muted-text" style="margin:10px 0 0">Each month can be paid, adjusted or cleared on its own. Posting a payment here puts it against that month only, so a single month can be settled without touching the rest of the year.</p>
                <div class="record-table" style="margin-top:12px">
                    <div class="record-table-row record-table-head">
                        <span>Month</span>
                        <span>Rate</span>
                        <span>Paid</span>
                        <span>Remaining</span>
                        <span>Adjust</span>
                    </div>
                    <?php foreach ((array) $scMonthPeriods['periods'] as $month): ?>
                        <?php $monthKey = (string) $month['key']; ?>
                        <div class="record-table-row record-table-row--breakdown<?= (int) $month['remaining'] > 0 ? ' record-table-row--highlight' : '' ?>">
                            <span>
                                <strong><?= htmlspecialchars((string) $month['label'], ENT_QUOTES, 'UTF-8') ?></strong>
                                <?php if (! empty($month['isArrears']) && (int) $month['remaining'] > 0): ?>
                                    <span class="type-pill pill-owing" style="margin-left:8px">Arrears</span>
                                <?php endif; ?>
                            </span>
                            <span><?= htmlspecialchars(app_currency((int) $month['billed']), ENT_QUOTES, 'UTF-8') ?></span>
                            <span><?= htmlspecialchars(app_currency((int) $month['paid']), ENT_QUOTES, 'UTF-8') ?></span>
                            <span>
                                <strong<?= (int) $month['remaining'] > 0 ? ' class="text-danger"' : '' ?>><?= htmlspecialchars(app_currency((int) $month['remaining']), ENT_QUOTES, 'UTF-8') ?></strong>
                            </span>
                            <span>
                                <?php $renderBreakdownEditor($month, 'service_charge', isset($scMonthPayments[$monthKey]) ? (array) $scMonthPayments[$monthKey] : array()); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </details>
        <?php endif; ?>

        <div class="record-table" style="margin-bottom:26px">
            <div class="record-table-row record-table-head">
                <span>Payment</span>
                <span>Description</span>
                <span>Amount</span>
                <span>Channel</span>
                <span>Reference</span>
            </div>
            <?php if ($serviceChargePayments === array()): ?>
                <div class="record-table-row">
                    <span>No service charge payments have been recorded for this tenant.</span>
                    <span></span><span></span><span></span><span></span>
                </div>
            <?php else: ?>
                <?php foreach ($serviceChargePayments as $payment): ?>
                    <div class="record-table-row">
                        <span><strong><?= htmlspecialchars((string) substr((string) $payment['createdAt'], 0, 10), ENT_QUOTES, 'UTF-8') ?></strong></span>
                        <span>
                            <?= htmlspecialchars((string) $payment['description'], ENT_QUOTES, 'UTF-8') ?>
                            <?php if ($payment['metaSettlesArrears']): ?>
                                <span class="type-pill pill-owing" style="margin-left:8px">Clears arrears</span>
                            <?php endif; ?>
                        </span>
                        <span><strong><?= htmlspecialchars(app_currency((int) $payment['amount']), ENT_QUOTES, 'UTF-8') ?></strong></span>
                        <span><?= htmlspecialchars(ucwords((string) $payment['channel']), ENT_QUOTES, 'UTF-8') ?></span>
                        <span><?= htmlspecialchars((string) $payment['reference'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if ($otherPayments !== array()): ?>
            <div class="section-heading" style="margin-bottom:12px">
                <div>
                    <span class="eyebrow">Other</span>
                    <h3>Deposits and other payments</h3>
                </div>
            </div>

            <div class="record-table" style="margin-bottom:26px">
                <div class="record-table-row record-table-head">
                    <span>Payment</span>
                    <span>Description</span>
                    <span>Amount</span>
                    <span>Channel</span>
                    <span>Reference</span>
                </div>
                <?php foreach ($otherPayments as $payment): ?>
                    <div class="record-table-row">
                        <span><strong><?= htmlspecialchars((string) substr((string) $payment['createdAt'], 0, 10), ENT_QUOTES, 'UTF-8') ?></strong></span>
                        <span><?= htmlspecialchars((string) $payment['description'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span><strong><?= htmlspecialchars(app_currency((int) $payment['amount']), ENT_QUOTES, 'UTF-8') ?></strong></span>
                        <span><?= htmlspecialchars(ucwords((string) $payment['channel']), ENT_QUOTES, 'UTF-8') ?></span>
                        <span><?= htmlspecialchars((string) $payment['reference'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="section-heading" style="margin-bottom:12px">
            <div>
                <span class="eyebrow">Adjust</span>
                <h3>Change what a payment settles</h3>
            </div>
        </div>
        <p class="muted-text" style="margin-top:0">
            Pick a payment, change its type, amount, date, or description, and save. Rent payments clear the rent arrears of the tenure oldest year first, and a payment turned into a service charge is re-allocated to the oldest unpaid months. Nothing is deleted, so the ledger history stays intact.
        </p>

        <div class="admin-editor-state" style="margin-bottom:14px">
            <label for="payment-editor-picker" style="display:block; margin-bottom:6px">Payment to edit</label>
            <select id="payment-editor-picker">
                <option value="">Choose a payment</option>
                <?php foreach ((array) $tenant['payments'] as $payment): ?>
                    <?php $meta = isset($classifications[(int) $payment['id']]) ? $classifications[(int) $payment['id']] : array('type' => 'other'); ?>
                    <option value="<?= (int) $payment['id'] ?>" data-payment-type-key="<?= htmlspecialchars((string) $meta['type'], ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars(trim((string) substr((string) $payment['createdAt'], 0, 10) . ' · ' . $payment['description'] . ' · ' . app_currency((int) $payment['amount'])), ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div id="payment-editors">
            <?php foreach ((array) $tenant['payments'] as $payment): ?>
                <div class="payment-editor-shell" data-payment-shell="<?= (int) $payment['id'] ?>" style="display:none">
                    <?php $renderPaymentEditor($payment); ?>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ((array) $tenant['payments'] === array()): ?>
            <p class="muted-text">No payments have been recorded for this tenant yet.</p>
        <?php endif; ?>

        <?php if (isset($tenureBlocks['tenures']) && count((array) $tenureBlocks['tenures']) > 1): ?>
            <div class="section-heading" style="margin:26px 0 12px">
                <div>
                    <span class="eyebrow">History</span>
                    <h3>Earlier tenancy periods</h3>
                </div>
            </div>
            <div class="record-table">
                <div class="record-table-row record-table-head">
                    <span>Term</span>
                    <span>Period</span>
                    <span>Billed</span>
                    <span>Paid</span>
                    <span>Remaining</span>
                </div>
                <?php foreach ((array) $tenureBlocks['tenures'] as $blockIndex => $block): ?>
                    <?php if (! empty($block['isOngoing'])) { continue; } ?>
                    <div class="record-table-row">
                        <span><strong><?= htmlspecialchars((string) ($block['periodLabel'] !== '' ? $block['periodLabel'] : $block['tenureLabel']), ENT_QUOTES, 'UTF-8') ?></strong></span>
                        <span><?= htmlspecialchars(trim((string) $block['startDate'] . ' → ' . $block['endDate']), ENT_QUOTES, 'UTF-8') ?></span>
                        <span><?= htmlspecialchars(app_currency((int) $block['totalRates']), ENT_QUOTES, 'UTF-8') ?></span>
                        <span><?= htmlspecialchars(app_currency((int) $block['totalPaid']), ENT_QUOTES, 'UTF-8') ?></span>
                        <span><strong<?= (int) $block['totalRemaining'] > 0 ? ' class="text-danger"' : '' ?>><?= htmlspecialchars(app_currency((int) $block['totalRemaining']), ENT_QUOTES, 'UTF-8') ?></strong></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</section>

<script>
(function () {
    var picker = document.getElementById('payment-editor-picker');
    var shells = Array.prototype.slice.call(document.querySelectorAll('[data-payment-shell]'));
    var symbol = '<?= htmlspecialchars(app_currency_symbol(), ENT_QUOTES, 'UTF-8') ?>';

    var targets = <?= json_encode(array_map(function ($target) {
        return array(
            'label' => $target['label'],
            'heading' => $target['heading'],
            'total' => (int) $target['total'],
            'note' => $target['note'],
        );
    }, $typeTargets)) ?>;

    var formatter = function (value) {
        return symbol + String(value).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    };

    if (picker) {
        picker.addEventListener('change', function () {
            var chosen = picker.value;

            shells.forEach(function (shell) {
                shell.style.display = shell.getAttribute('data-payment-shell') === chosen ? 'block' : 'none';
            });
        });
    }

    // The breakdown rows link straight to the editor of a payment, so the matching editor is
    // opened in the history section before the page jumps to it.
    Array.prototype.slice.call(document.querySelectorAll('a[href^="#payment-"]')).forEach(function (link) {
        link.addEventListener('click', function () {
            var paymentId = link.getAttribute('href').replace('#payment-', '');

            if (!picker) {
                return;
            }

            picker.value = paymentId;
            picker.dispatchEvent(new Event('change'));
        });
    });

    // A payment that is not tied to one period is grouped here so it is never hidden from the admin.
    Array.prototype.slice.call(document.querySelectorAll('[data-unallocated-payments]')).forEach(function (panel) {
        if (panel.querySelector('.breakdown-editor__payment') === null) {
            panel.parentNode.removeChild(panel);
        }
    });

    Array.prototype.slice.call(document.querySelectorAll('[data-payment-editor]')).forEach(function (editor) {
        var typeSelect = editor.querySelector('[data-payment-type]');
        var periodSelect = editor.querySelector('[data-payment-period]');
        var amountInput = editor.querySelector('[data-payment-amount]');
        var labelInput = editor.querySelector('[data-payment-label]');
        var noteEl = editor.querySelector('[data-payment-target-note]');
        var balanceEl = editor.querySelector('[data-payment-balance-note]');

        var syncPeriods = function () {
            if (!periodSelect || !typeSelect) {
                return;
            }

            var wanted = typeSelect.value === 'rent' ? 'rent' : (typeSelect.value === 'service_charge' ? 'service_charge' : '');
            var firstEnabled = null;

            Array.prototype.slice.call(periodSelect.options).forEach(function (option) {
                var matches = wanted !== '' && option.getAttribute('data-period-type') === wanted;
                option.disabled = !matches;

                if (matches && firstEnabled === null) {
                    firstEnabled = option;
                }
            });

            if (periodSelect.selectedOptions.length === 0 || periodSelect.selectedOptions[0].disabled) {
                periodSelect.value = firstEnabled ? firstEnabled.value : '';
            }
        };

        var refresh = function (resetLabel) {
            var target = targets[typeSelect.value];

            if (!target) {
                return;
            }

            if (resetLabel && labelInput) {
                labelInput.value = target.label;
            }

            if (noteEl) {
                noteEl.textContent = target.note;
            }

            var raw = amountInput ? (amountInput.value || '').replace(/[^\d]/g, '') : '';
            var entered = raw === '' ? 0 : parseInt(raw, 10);
            var balance = Math.max(0, target.total - entered);

            if (balanceEl) {
                balanceEl.textContent = balance > 0
                    ? 'A balance of ' + formatter(balance) + ' will remain on the ' + target.heading.toLowerCase() + ' after this payment.'
                    : 'This payment settles the ' + target.heading.toLowerCase() + ' in full.';
            }
        };

        if (typeSelect) {
            typeSelect.addEventListener('change', function () { syncPeriods(); refresh(true); });
        }

        if (amountInput) {
            amountInput.addEventListener('input', function () { refresh(false); });
        }

        syncPeriods();
        refresh(false);
    });
})();
</script>
