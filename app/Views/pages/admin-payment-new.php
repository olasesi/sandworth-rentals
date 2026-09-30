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

// Each payment type settles a different balance, so the form shows and defaults to the one the admin picked.
$typeTargets = array(
    'rent' => array(
        'label' => 'Rent installment',
        'target' => 'rent',
        'owed' => $rentOwed,
        'heading' => 'Rent arrears for this tenure',
        'total' => $rentOwed,
        'lines' => array(
            array('label' => 'Rent payable on this tenure', 'amount' => $rentTenureOwed),
            array('label' => 'Arrears carried from an earlier tenure', 'amount' => $rentPriorArrears),
        ),
        'note' => $rentOwed > 0
            ? 'This payment is posted to the tenant ledger and is treated as rent, so it reduces the rent payable for the tenure.'
            : 'There is no rent outstanding for this tenure at the moment.',
    ),
    'service_charge' => array(
        'label' => 'Service charge',
        'target' => 'service_charge',
        'owed' => $scOwed,
        'heading' => 'Service charge arrears',
        'total' => $scOwed,
        'lines' => array(
            array('label' => 'Arrears from earlier months', 'amount' => $scArrears),
            array('label' => 'Current billing year', 'amount' => $scCurrent),
        ),
        'note' => $scOwed > 0
            ? 'The payment is applied to the oldest unpaid billing months first and cannot exceed this amount.'
            : 'There is no outstanding service charge at the moment.',
    ),
    'deposit' => array(
        'label' => 'Security deposit',
        'target' => 'deposit',
        'owed' => 0,
        'heading' => 'Caution deposit',
        'total' => 0,
        'lines' => array(
            array('label' => 'Caution deposit on this registration', 'amount' => (int) $tenant['securityDeposit']),
        ),
        'note' => 'A deposit is recorded on the ledger and is not settled against rent or service charge.',
    ),
    'other' => array(
        'label' => 'Other payment',
        'target' => 'other',
        'owed' => 0,
        'heading' => 'Other payment',
        'total' => 0,
        'lines' => array(),
        'note' => 'Recorded on the ledger without reducing rent or service charge.',
    ),
);

$formType = $hasOldInput && isset($oldInput['charge_type']) ? (string) $oldInput['charge_type'] : $payType;

if (! isset($typeTargets[$formType])) {
    $formType = 'rent';
}

$formTarget = $typeTargets[$formType];
$formAmount = $hasOldInput && isset($oldInput['amount'])
    ? (string) $oldInput['amount']
    : ($formTarget['owed'] > 0 ? (string) $formTarget['owed'] : '');
$formLabel = $hasOldInput && isset($oldInput['label']) ? (string) $oldInput['label'] : (string) $formTarget['label'];
$formChannel = $hasOldInput && isset($oldInput['channel']) ? (string) $oldInput['channel'] : 'cash';
$formPaidAt = $hasOldInput && isset($oldInput['paid_at']) ? (string) $oldInput['paid_at'] : '';
$formReference = $hasOldInput && isset($oldInput['reference']) ? (string) $oldInput['reference'] : '';

$activeAdminPage = 'tenants';
$adminTitle = 'Record a tenant payment.';
$adminDescription = 'Post a rent, service charge, deposit, or other payment to this tenant ledger. The form follows the payment type you choose, so a rent payment settles rent arrears and a service charge payment settles service charge arrears.';

require dirname(__DIR__) . '/partials/admin-header.php';
?>

<section class="admin-workspace">
    <section class="admin-section">
        <div class="section-heading">
            <div>
                <span class="eyebrow">Record payment</span>
                <h2>Post a payment to <?= htmlspecialchars((string) $tenant['user']['name'], ENT_QUOTES, 'UTF-8') ?></h2>
            </div>
            <div class="admin-page-actions">
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

            <div class="admin-form-grid">
                <div class="admin-field">
                    <label for="payment-type">Type</label>
                    <select id="payment-type" name="charge_type">
                        <option value="rent"<?= $formType === 'rent' ? ' selected' : '' ?>>Rent</option>
                        <option value="service_charge"<?= $formType === 'service_charge' ? ' selected' : '' ?>>Service charge</option>
                        <option value="deposit"<?= $formType === 'deposit' ? ' selected' : '' ?>>Deposit</option>
                        <option value="other"<?= $formType === 'other' ? ' selected' : '' ?>>Other</option>
                    </select>
                    <span class="muted-text" style="display:block; margin-top:6px">The type decides which balance this payment settles.</span>
                </div>

                <div class="admin-field">
                    <label for="payment-amount">Amount paid</label>
                    <input id="payment-amount" name="amount" type="number" min="1" placeholder="0" value="<?= htmlspecialchars($formAmount, ENT_QUOTES, 'UTF-8') ?>" required>
                    <span class="muted-text" id="payment-amount-hint" style="display:block; margin-top:6px">Pre-filled with the amount owed for the selected type.</span>
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
                <strong id="payment-target-heading"><?= htmlspecialchars((string) $formTarget['heading'], ENT_QUOTES, 'UTF-8') ?></strong>
                <p class="muted-text" id="payment-target-note" style="margin:6px 0 0"><?= htmlspecialchars((string) $formTarget['note'], ENT_QUOTES, 'UTF-8') ?></p>
                <div class="manager-stats" style="margin:12px 0 0" id="payment-target-lines">
                    <?php foreach ($formTarget['lines'] as $line): ?>
                        <article>
                            <span><?= htmlspecialchars((string) $line['label'], ENT_QUOTES, 'UTF-8') ?></span>
                            <strong<?= (int) $line['amount'] > 0 ? ' class="text-danger"' : '' ?>><?= htmlspecialchars(app_currency((int) $line['amount']), ENT_QUOTES, 'UTF-8') ?></strong>
                        </article>
                    <?php endforeach; ?>
                    <article>
                        <span>Total to settle</span>
                        <strong<?= (int) $formTarget['total'] > 0 ? ' class="text-danger"' : '' ?>><?= htmlspecialchars(app_currency((int) $formTarget['total']), ENT_QUOTES, 'UTF-8') ?></strong>
                    </article>
                </div>
            </div>

            <p class="muted-text" id="payment-balance-note" style="margin-top:0">
                <?php
                $enteredAmount = $formAmount !== '' ? (int) preg_replace('/[^\d]/', '', $formAmount) : 0;
                $balanceAfter = max(0, (int) $formTarget['total'] - $enteredAmount);
                ?>
                <?= $balanceAfter > 0
                    ? 'A balance of ' . htmlspecialchars(app_currency($balanceAfter), ENT_QUOTES, 'UTF-8') . ' will remain on the ' . htmlspecialchars(strtolower((string) $formTarget['heading']), ENT_QUOTES, 'UTF-8') . '. The form can still be submitted.'
                    : 'This payment settles the ' . htmlspecialchars(strtolower((string) $formTarget['heading']), ENT_QUOTES, 'UTF-8') . ' in full.' ?>
            </p>

            <div class="admin-form-actions">
                <button type="submit" class="solid-button">Record payment</button>
                <a class="ghost-button" href="<?= htmlspecialchars(app_url('admin-tenants', array('view_tenant' => $tenant['unitId'], 'unit_table' => $unitTable)), ENT_QUOTES, 'UTF-8') ?>">Cancel</a>
            </div>
        </form>
    </section>
</section>

<script>
(function () {
    var paymentType = document.getElementById('payment-type');
    var paymentLabel = document.getElementById('payment-label');
    var paymentAmount = document.getElementById('payment-amount');
    var amountHint = document.getElementById('payment-amount-hint');
    var targetHeading = document.getElementById('payment-target-heading');
    var targetNote = document.getElementById('payment-target-note');
    var targetLines = document.getElementById('payment-target-lines');
    var balanceNote = document.getElementById('payment-balance-note');
    var symbol = '<?= htmlspecialchars(app_currency_symbol(), ENT_QUOTES, 'UTF-8') ?>';

    var targets = <?= json_encode(array_map(function ($target) {
        return array(
            'label' => $target['label'],
            'heading' => $target['heading'],
            'total' => (int) $target['total'],
            'note' => $target['note'],
            'lines' => array_map(function ($line) {
                return array('label' => $line['label'], 'amount' => (int) $line['amount']);
            }, $target['lines']),
        );
    }, $typeTargets)) ?>;

    var formatter = function (value) {
        return symbol + String(value).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    };

    var update = function (resetFields) {
        var target = targets[paymentType.value];

        if (!target) {
            return;
        }

        if (resetFields) {
            if (paymentLabel) {
                paymentLabel.value = target.label;
                paymentLabel.placeholder = target.label;
            }

            if (paymentAmount) {
                paymentAmount.value = target.total > 0 ? target.total : '';
            }
        }

        if (targetHeading) {
            targetHeading.textContent = target.heading;
        }

        if (targetNote) {
            targetNote.textContent = target.note;
        }

        if (targetLines) {
            targetLines.innerHTML = '';

            target.lines.forEach(function (line) {
                var article = document.createElement('article');
                var label = document.createElement('span');
                var amount = document.createElement('strong');

                label.textContent = line.label;
                amount.textContent = formatter(line.amount);

                if (line.amount > 0) {
                    amount.className = 'text-danger';
                }

                article.appendChild(label);
                article.appendChild(amount);
                targetLines.appendChild(article);
            });

            var totalArticle = document.createElement('article');
            var totalLabel = document.createElement('span');
            var totalAmount = document.createElement('strong');

            totalLabel.textContent = 'Total to settle';
            totalAmount.textContent = formatter(target.total);

            if (target.total > 0) {
                totalAmount.className = 'text-danger';
            }

            totalArticle.appendChild(totalLabel);
            totalArticle.appendChild(totalAmount);
            targetLines.appendChild(totalArticle);
        }

        var raw = paymentAmount ? (paymentAmount.value || '').replace(/[^\d]/g, '') : '';
        var entered = raw === '' ? 0 : parseInt(raw, 10);
        var balance = Math.max(0, target.total - entered);

        if (amountHint) {
            amountHint.textContent = target.total > 0
                ? 'Pre-filled with the ' + formatter(target.total) + ' owed for this type.'
                : 'Nothing is owed for this type, so enter the amount paid.';
        }

        if (balanceNote) {
            balanceNote.textContent = balance > 0
                ? 'A balance of ' + formatter(balance) + ' will remain on the ' + target.heading.toLowerCase() + '. The form can still be submitted.'
                : 'This payment settles the ' + target.heading.toLowerCase() + ' in full.';
        }
    };

    if (paymentType) {
        paymentType.addEventListener('change', function () { update(true); });
    }

    if (paymentAmount) {
        paymentAmount.addEventListener('input', function () { update(false); });
    }

    update(false);
})();
</script>
