<?php
/** @var array $portfolio */
/** @var array $registration */
/** @var string $payType */
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
$rentOwed = (int) $balances['totalOwed'];
$rentTenureOwed = (int) $balances['tenureOwed'];
$rentPriorArrears = (int) $balances['priorArrears'];
$scOwed = (int) $sc['outstanding'];
$scArrears = (int) $sc['arrears'];
$scCurrent = (int) $sc['currentPeriod'];

$rentPeriods = $tenant['rentPeriods'];
$scPeriods = $tenant['scPeriods'];

// The page is opened for one kind of payment, so the admin never picks a type: a rent payment can
// only clear rent and a service charge payment can only clear service charge.
$isRent = $payType === 'rent';
$isServiceCharge = $payType === 'service_charge';
$isDeposit = ! $isRent && ! $isServiceCharge && $payType === 'deposit';

$periods = $isRent ? (array) $rentPeriods['periods'] : (array) $scPeriods['periods'];
$settledPeriods = 0;
$openPeriods = array();

foreach ($periods as $period) {
    if (! empty($period['isSettled'])) {
        $settledPeriods++;
    } else {
        $openPeriods[] = $period;
    }
}

$defaultPeriod = '';

foreach ($openPeriods as $period) {
    $defaultPeriod = (string) $period['key'];
    break;
}

$formPeriod = $hasOldInput && isset($oldInput['period_key']) && (string) $oldInput['period_key'] !== ''
    ? (string) $oldInput['period_key']
    : $defaultPeriod;

$selectedPeriod = null;

foreach ($periods as $period) {
    if ((string) $period['key'] === $formPeriod) {
        $selectedPeriod = $period;
        break;
    }
}

$periodOwed = $selectedPeriod !== null ? (int) $selectedPeriod['remaining'] : 0;
$periodSettled = $selectedPeriod !== null && ! empty($selectedPeriod['isSettled']);
$totalOwed = $isRent ? $rentOwed : ($isServiceCharge ? $scOwed : 0);

if ($isRent) {
    $pageEyebrow = 'Record rent payment';
    $pageHeading = 'Post a rent payment for ' . (string) $tenant['user']['name'];
    $pageIntro = 'This is posted as rent, so it can only settle rent. Pick the year of the term it is for; a payment for a year that has already closed clears that year and not the current one.';
    $targetHeading = 'Rent owed on this term';
    $targetLines = array(
        array('label' => 'Rent payable on this term', 'amount' => $rentTenureOwed),
        array('label' => 'Arrears carried from an earlier period', 'amount' => $rentPriorArrears),
        array('label' => 'Rent owed in total', 'amount' => $rentOwed),
    );
    $periodUnit = 'year of the term';
} elseif ($isServiceCharge) {
    $pageEyebrow = 'Record service charge payment';
    $pageHeading = 'Post a service charge payment for ' . (string) $tenant['user']['name'];
    $pageIntro = 'This is posted as service charge, so it can only settle service charge. Pick the billing year it is for and it is applied to the oldest unpaid months in that year.';
    $targetHeading = 'Service charge owed';
    $targetLines = array(
        array('label' => 'Arrears from earlier months', 'amount' => $scArrears),
        array('label' => 'Current billing year', 'amount' => $scCurrent),
        array('label' => 'Service charge owed in total', 'amount' => $scOwed),
    );
    $periodUnit = 'billing year';
} elseif ($isDeposit) {
    $pageEyebrow = 'Record caution deposit';
    $pageHeading = 'Post the caution deposit for ' . (string) $tenant['user']['name'];
    $pageIntro = 'The deposit is kept on the ledger and is not settled against rent or service charge.';
    $targetHeading = 'Caution deposit on this registration';
    $targetLines = array(array('label' => 'Caution deposit on this registration', 'amount' => (int) $tenant['securityDeposit']));
    $periodUnit = '';
} else {
    $pageEyebrow = 'Record other payment';
    $pageHeading = 'Post an other payment for ' . (string) $tenant['user']['name'];
    $pageIntro = 'Recorded on the ledger without reducing rent or service charge.';
    $targetHeading = 'Other payment';
    $targetLines = array();
    $periodUnit = '';
}

$formAmount = $hasOldInput && isset($oldInput['amount'])
    ? (string) $oldInput['amount']
    : ($periodOwed > 0 ? (string) $periodOwed : '');
$formLabel = $hasOldInput && isset($oldInput['label']) ? (string) $oldInput['label'] : ($isServiceCharge ? 'Service charge' : ($isDeposit ? 'Caution deposit' : ($isRent ? 'Rent installment' : 'Other payment')));
$formChannel = $hasOldInput && isset($oldInput['channel']) ? (string) $oldInput['channel'] : 'cash';
$formPaidAt = $hasOldInput && isset($oldInput['paid_at']) ? (string) $oldInput['paid_at'] : '';
$formReference = $hasOldInput && isset($oldInput['reference']) ? (string) $oldInput['reference'] : '';

$activeAdminPage = 'tenants';
$adminTitle = $pageHeading . '.';
$adminDescription = $pageIntro;

$periodOptionsJson = json_encode(array_map(function ($period) {
    return array(
        'key' => (string) $period['key'],
        'label' => (string) $period['label'],
        'billed' => (int) $period['billed'],
        'paid' => (int) $period['paid'],
        'remaining' => (int) $period['remaining'],
        'isArrears' => ! empty($period['isArrears']),
        'isCurrent' => ! empty($period['isCurrent']),
        'isSettled' => ! empty($period['isSettled']),
    );
}, $periods));

require dirname(__DIR__) . '/partials/admin-header.php';
?>

<section class="admin-workspace">
    <section class="admin-section">
        <div class="section-heading">
            <div>
                <span class="eyebrow"><?= htmlspecialchars($pageEyebrow, ENT_QUOTES, 'UTF-8') ?></span>
                <h2><?= htmlspecialchars($pageHeading, ENT_QUOTES, 'UTF-8') ?></h2>
            </div>
            <div class="admin-page-actions">
                <a class="ghost-button" href="<?= htmlspecialchars(app_url('admin-tenant-payment-edit', array('unit_table' => $unitTable, 'unit_id' => $tenant['unitId'])), ENT_QUOTES, 'UTF-8') ?>">Tenancy payments</a>
                <a class="ghost-button" href="<?= htmlspecialchars(app_url('admin-tenants', array('view_tenant' => $tenant['unitId'], 'unit_table' => $unitTable)), ENT_QUOTES, 'UTF-8') ?>">Back to tenant</a>
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
                <span>Monthly rent</span>
                <strong><?= htmlspecialchars(app_currency($tenant['monthlyRent']), ENT_QUOTES, 'UTF-8') ?></strong>
            </article>
            <article>
                <span>Rent owed</span>
                <strong<?= $rentOwed > 0 ? ' class="text-danger"' : '' ?>><?= htmlspecialchars(app_currency($rentOwed), ENT_QUOTES, 'UTF-8') ?></strong>
            </article>
            <article>
                <span>Service charge outstanding</span>
                <strong<?= $scOwed > 0 ? ' class="text-danger"' : '' ?>><?= htmlspecialchars(app_currency($scOwed), ENT_QUOTES, 'UTF-8') ?></strong>
            </article>
            <article>
                <span>Total paid to date</span>
                <strong><?= htmlspecialchars(app_currency($tenant['totalPaid']), ENT_QUOTES, 'UTF-8') ?></strong>
            </article>
        </div>

        <form action="<?= htmlspecialchars(app_url('admin-tenant-payment'), ENT_QUOTES, 'UTF-8') ?>" method="post" class="admin-form">
            <input type="hidden" name="unit_table" value="<?= htmlspecialchars($unitTable, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="unit_id" value="<?= (int) $tenant['unitId'] ?>">
            <input type="hidden" name="view_tenant" value="<?= (int) $tenant['unitId'] ?>">
            <input type="hidden" name="charge_type" value="<?= htmlspecialchars($isRent ? 'rent' : ($isServiceCharge ? 'service_charge' : ($isDeposit ? 'deposit' : 'other')), ENT_QUOTES, 'UTF-8') ?>">

            <?php if ($periods !== array()): ?>
                <div class="admin-form-grid">
                    <div class="admin-field">
                        <label for="payment-period">Rent paid for this <?= htmlspecialchars($isRent ? 'year of the term' : 'billing year', ENT_QUOTES, 'UTF-8') ?></label>
                        <select id="payment-period" name="period_key">
                            <?php foreach ($periods as $period): ?>
                                <option value="<?= htmlspecialchars((string) $period['key'], ENT_QUOTES, 'UTF-8') ?>"<?= (string) $period['key'] === $formPeriod ? ' selected' : '' ?><?= ! empty($period['isSettled']) ? ' disabled' : '' ?>>
                                    <?= htmlspecialchars((string) $period['label'], ENT_QUOTES, 'UTF-8') ?> — <?= ! empty($period['isSettled']) ? 'settled' : htmlspecialchars(app_currency((int) $period['remaining']), ENT_QUOTES, 'UTF-8') . ' owing' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="muted-text" id="payment-period-hint" style="display:block; margin-top:6px"><?php
                            if ($periodSettled) {
                                echo 'The rent for this period is already settled in full, so there is nothing to pay against it. Pick a period that is still owing.';
                            } else {
                                echo 'The payment is applied to this period, so it clears these arrears and not a later one.';
                            }
                        ?></span>
                    </div>
                </div>
            <?php elseif ($isRent || $isServiceCharge): ?>
                <div class="admin-editor-state" style="margin-bottom:18px">
                    <strong>Nothing to pay against</strong>
                    <p class="muted-text" style="margin:6px 0 0"><?= $isRent
                        ? 'The rent for every year of this term has been paid in full, so there is no rent outstanding to record a payment against.'
                        : 'Every billed month of service charge on this tenancy has been paid, so there is no service charge outstanding to record a payment against.' ?></p>
                </div>
            <?php endif; ?>

            <div class="admin-form-grid">
                <div class="admin-field">
                    <label for="payment-amount">Amount paid</label>
                    <input id="payment-amount" name="amount" type="number" min="1" placeholder="0" value="<?= htmlspecialchars($formAmount, ENT_QUOTES, 'UTF-8') ?>"<?= ($isRent || $isServiceCharge) && $periods === array() ? ' disabled' : '' ?> required>
                    <span class="muted-text" id="payment-amount-hint" style="display:block; margin-top:6px"><?= $periodOwed > 0
                        ? 'Pre-filled with what is still owing for the period picked above.'
                        : 'Enter the amount received.' ?></span>
                </div>

                <div class="admin-field">
                    <label for="payment-label">Description</label>
                    <input id="payment-label" name="label" type="text" placeholder="Rent installment, service charge, deposit" value="<?= htmlspecialchars($formLabel, ENT_QUOTES, 'UTF-8') ?>" required>
                </div>

                <div class="admin-field">
                    <label for="payment-channel">Channel</label>
                    <select id="payment-channel" name="channel">
                        <option value="cash"<?= $formChannel === 'cash' ? ' selected' : '' ?>>Cash</option>
                        <option value="transfer"<?= $formChannel === 'transfer' ? ' selected' : '' ?>>Transfer</option>
                        <option value="card"<?= $formChannel === 'card' ? ' selected' : '' ?>>Card</option>
                    </select>
                </div>

                <div class="admin-field">
                    <label for="payment-date">Date paid</label>
                    <input id="payment-date" name="paid_at" type="date" value="<?= htmlspecialchars($formPaidAt, ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="admin-field">
                    <label for="payment-reference">Reference</label>
                    <input id="payment-reference" name="reference" type="text" placeholder="Auto if left blank" value="<?= htmlspecialchars($formReference, ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>

            <div class="admin-editor-state" style="margin-top:18px">
                <strong id="payment-target-heading"><?= htmlspecialchars($targetHeading, ENT_QUOTES, 'UTF-8') ?></strong>
                <div class="manager-stats" style="margin:12px 0 0" id="payment-target-lines">
                    <?php foreach ($targetLines as $line): ?>
                        <article>
                            <span><?= htmlspecialchars((string) $line['label'], ENT_QUOTES, 'UTF-8') ?></span>
                            <strong<?= (int) $line['amount'] > 0 ? ' class="text-danger"' : '' ?>><?= htmlspecialchars(app_currency((int) $line['amount']), ENT_QUOTES, 'UTF-8') ?></strong>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>

            <p class="muted-text" id="payment-balance-note" style="margin-top:0">
                <?php
                $enteredAmount = $formAmount !== '' ? (int) preg_replace('/[^\d]/', '', $formAmount) : 0;
                $balanceAfter = $selectedPeriod !== null ? max(0, (int) $selectedPeriod['remaining'] - $enteredAmount) : 0;
                ?>
                <?php if ($selectedPeriod !== null): ?>
                    <?= $periodSettled
                        ? 'This period is already settled, so pick another one to record a payment.'
                        : ($balanceAfter > 0
                            ? 'A balance of ' . htmlspecialchars(app_currency($balanceAfter), ENT_QUOTES, 'UTF-8') . ' will remain on ' . htmlspecialchars(strtolower((string) $selectedPeriod['shortLabel']), ENT_QUOTES, 'UTF-8') . '. The form can still be submitted.'
                            : 'This payment settles ' . htmlspecialchars(strtolower((string) $selectedPeriod['shortLabel']), ENT_QUOTES, 'UTF-8') . ' in full.') ?>
                <?php else: ?>
                    <?= $totalOwed > 0
                        ? 'Pick the period this payment is for so it is applied to the right arrears.'
                        : 'This is recorded on the ledger without reducing rent or service charge.' ?>
                <?php endif; ?>
            </p>

            <div class="admin-form-actions">
                <button type="submit" class="solid-button"><?= $isRent ? 'Record rent payment' : ($isServiceCharge ? 'Record service charge payment' : 'Record payment') ?></button>
                <a class="ghost-button" href="<?= htmlspecialchars(app_url('admin-tenants', array('view_tenant' => $tenant['unitId'], 'unit_table' => $unitTable)), ENT_QUOTES, 'UTF-8') ?>">Cancel</a>
            </div>
        </form>
    </section>
</section>

<script>
(function () {
    var periodSelect = document.getElementById('payment-period');
    var amountInput = document.getElementById('payment-amount');
    var amountHint = document.getElementById('payment-amount-hint');
    var periodHint = document.getElementById('payment-period-hint');
    var balanceNote = document.getElementById('payment-balance-note');
    var symbol = '<?= htmlspecialchars(app_currency_symbol(), ENT_QUOTES, 'UTF-8') ?>';
    var periods = <?= $periodOptionsJson !== '' ? $periodOptionsJson : '[]' ?>;
    var arrearsWord = <?= json_encode($isRent ? 'year' : 'billing year') ?>;

    var formatter = function (value) {
        return symbol + String(value).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    };

    var find = function (key) {
        var found = null;

        periods.forEach(function (period) {
            if (period.key === key) {
                found = period;
            }
        });

        return found;
    };

    var update = function (resetAmount) {
        if (!periodSelect || amountInput.disabled) {
            return;
        }

        var period = find(periodSelect.value);

        if (!period) {
            return;
        }

        if (period.isSettled) {
            if (periodHint) {
                periodHint.textContent = 'The rent for this period is already settled in full, so there is nothing to pay against it. Pick a period that is still owing.';
            }

            if (amountInput) {
                amountInput.value = '';
            }
        } else {
            if (periodHint) {
                periodHint.textContent = 'The payment is applied to this period, so it clears these arrears and not a later one.';
            }

            if (resetAmount && amountInput) {
                amountInput.value = period.remaining > 0 ? period.remaining : '';
            }

            if (amountHint) {
                amountHint.textContent = period.remaining > 0
                    ? 'Pre-filled with the ' + formatter(period.remaining) + ' still owing for this period.'
                    : 'Enter the amount received.';
            }
        }

        var raw = amountInput ? (amountInput.value || '').replace(/[^\d]/g, '') : '';
        var entered = raw === '' ? 0 : parseInt(raw, 10);
        var balance = Math.max(0, period.remaining - entered);

        if (balanceNote) {
            if (period.isSettled) {
                balanceNote.textContent = 'This period is already settled, so pick another one to record a payment.';
            } else if (balance > 0) {
                balanceNote.textContent = 'A balance of ' + formatter(balance) + ' will remain on this ' + arrearsWord + '. The form can still be submitted.';
            } else {
                balanceNote.textContent = 'This payment settles this ' + arrearsWord + ' in full.';
            }
        }
    };

    if (periodSelect) {
        periodSelect.addEventListener('change', function () { update(true); });
    }

    if (amountInput) {
        amountInput.addEventListener('input', function () { update(false); });
    }

    update(false);
})();
</script>
