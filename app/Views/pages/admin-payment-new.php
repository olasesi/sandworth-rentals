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
$scOwed = (int) $sc['outstanding'];

$formType = $hasOldInput && isset($oldInput['charge_type']) ? (string) $oldInput['charge_type'] : $payType;
$formAmount = $hasOldInput && isset($oldInput['amount']) ? (string) $oldInput['amount'] : ($formType === 'service_charge' ? $scOwed : ($formType === 'rent' ? $rentOwed : ''));
$formLabel = $hasOldInput && isset($oldInput['label']) ? (string) $oldInput['label'] : ($formType === 'rent' ? 'Rent installment' : ($formType === 'service_charge' ? 'Service charge' : ($formType === 'deposit' ? 'Security deposit' : '')));
$formChannel = $hasOldInput && isset($oldInput['channel']) ? (string) $oldInput['channel'] : 'cash';
$formPaidAt = $hasOldInput && isset($oldInput['paid_at']) ? (string) $oldInput['paid_at'] : '';
$formReference = $hasOldInput && isset($oldInput['reference']) ? (string) $oldInput['reference'] : '';

$activeAdminPage = 'tenants';
$adminTitle = 'Record a tenant payment.';
$adminDescription = 'Post a rent, service charge, deposit, or other payment to this tenant ledger. Service charges are applied to the oldest unpaid billing months automatically.';

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
                    <label for="payment-amount">Amount paid</label>
                    <input id="payment-amount" name="amount" type="number" min="1" placeholder="0" value="<?= htmlspecialchars($formAmount, ENT_QUOTES, 'UTF-8') ?>" required>
                </div>

                <div class="admin-field">
                    <label for="payment-label">Description</label>
                    <input id="payment-label" name="label" type="text" placeholder="Rent installment, service charge, deposit" value="<?= htmlspecialchars($formLabel, ENT_QUOTES, 'UTF-8') ?>" required>
                </div>

                <div class="admin-field">
                    <label for="payment-type">Type</label>
                    <select id="payment-type" name="charge_type">
                        <option value="rent"<?= $formType === 'rent' ? ' selected' : '' ?>>Rent</option>
                        <option value="service_charge"<?= $formType === 'service_charge' ? ' selected' : '' ?>>Service charge</option>
                        <option value="deposit"<?= $formType === 'deposit' ? ' selected' : '' ?>>Deposit</option>
                        <option value="other"<?= $formType === 'other' ? ' selected' : '' ?>>Other</option>
                    </select>
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

            <?php if ($formType === 'service_charge' && $scOwed > 0): ?>
                <p class="muted-text" style="margin-top:0">Service charge outstanding is <strong><?= htmlspecialchars(app_currency($scOwed), ENT_QUOTES, 'UTF-8') ?></strong>. The payment is applied to the oldest unpaid billing months automatically and cannot exceed this amount.</p>
            <?php elseif ($formType === 'service_charge'): ?>
                <p class="muted-text" style="margin-top:0">This tenant has no outstanding service charge at the moment.</p>
            <?php elseif ($formType === 'rent' && $rentOwed > 0): ?>
                <p class="muted-text" style="margin-top:0">Rent owed for this tenure is <strong><?= htmlspecialchars(app_currency($rentOwed), ENT_QUOTES, 'UTF-8') ?></strong>.</p>
            <?php endif; ?>

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

    if (paymentType && paymentLabel) {
        var labelDefaults = {
            'rent': 'Rent installment',
            'service_charge': 'Service charge',
            'deposit': 'Security deposit',
            'other': 'Other payment'
        };

        paymentType.addEventListener('change', function () {
            if (labelDefaults.hasOwnProperty(paymentType.value)) {
                paymentLabel.value = labelDefaults[paymentType.value];
                paymentLabel.placeholder = labelDefaults[paymentType.value];
            }
        });
    }
})();
</script>