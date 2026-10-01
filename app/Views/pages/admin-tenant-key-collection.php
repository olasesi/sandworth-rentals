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
$propertyRentYearly = isset($tenant['propertyRentYearly']) ? (int) $tenant['propertyRentYearly'] : (isset($balances['annualRent']) ? (int) $balances['annualRent'] : ((int) $tenant['monthlyRent'] * 12));
$tenureYears = isset($balances['tenureYears']) ? (int) $balances['tenureYears'] : 0;
$expectedRent = isset($balances['tenureTotal']) ? (int) $balances['tenureTotal'] : ($propertyRentYearly * max(1, $tenureYears));
$priorPaid = isset($balances['tenurePaid']) ? (int) $balances['tenurePaid'] : 0;

$formAmount = $hasOldInput && isset($oldInput['amount_paid']) ? (string) $oldInput['amount_paid'] : '';
$formRecipient = $hasOldInput && isset($oldInput['recipient_name']) ? (string) $oldInput['recipient_name'] : (string) $tenant['user']['name'];
$formKeys = $hasOldInput && isset($oldInput['keys_count']) && (string) $oldInput['keys_count'] !== '' ? (int) $oldInput['keys_count'] : 1;
$formCollectedAt = $hasOldInput && isset($oldInput['collected_at']) ? (string) $oldInput['collected_at'] : '';
$formChannel = $hasOldInput && isset($oldInput['channel']) ? (string) $oldInput['channel'] : 'cash';
$formReference = $hasOldInput && isset($oldInput['reference']) ? (string) $oldInput['reference'] : '';
$formNotes = $hasOldInput && isset($oldInput['notes']) ? (string) $oldInput['notes'] : '';
$formPostPayment = $hasOldInput ? isset($oldInput['post_payment']) : true;
$initialBalance = max(0, $expectedRent - $priorPaid - ($formPostPayment && $formAmount !== '' ? (int) preg_replace('/[^\d]/', '', $formAmount) : 0));

$activeAdminPage = 'tenants';
$adminTitle = 'Hand over the keys.';
$adminDescription = 'Acknowledge that the tenant has received the apartment keys after payment. The balance against the rent for the whole term is shown while you type and again when the form is submitted.';

require dirname(__DIR__) . '/partials/admin-header.php';
?>

<section class="admin-workspace">
    <section class="admin-section">
        <div class="section-heading">
            <div>
                <span class="eyebrow">Key collection</span>
                <h2>Keys received by <?= htmlspecialchars((string) $tenant['user']['name'], ENT_QUOTES, 'UTF-8') ?></h2>
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
                <span>Yearly rent for this property</span>
                <strong><?= $propertyRentYearly > 0 ? htmlspecialchars(app_currency($propertyRentYearly), ENT_QUOTES, 'UTF-8') : '—' ?></strong>
            </article>
            <article>
                <span>Rent for the <?= (int) max(1, $tenureYears) ?> year term</span>
                <strong><?= $expectedRent > 0 ? htmlspecialchars(app_currency($expectedRent), ENT_QUOTES, 'UTF-8') : '—' ?></strong>
            </article>
            <article>
                <span>Paid on this term</span>
                <strong><?= htmlspecialchars(app_currency($priorPaid), ENT_QUOTES, 'UTF-8') ?></strong>
            </article>
            <article>
                <span>Rent payable on the term</span>
                <strong class="<?= max(0, $expectedRent - $priorPaid) > 0 ? 'admin-amount-due' : 'admin-amount-ok' ?>"><?= htmlspecialchars(app_currency(max(0, $expectedRent - $priorPaid)), ENT_QUOTES, 'UTF-8') ?></strong>
            </article>
            <article>
                <span>Balance after this installment</span>
                <strong id="key-balance-indicator" class="<?= $initialBalance > 0 ? 'admin-amount-due' : 'admin-amount-ok' ?>"><?= htmlspecialchars(app_currency($initialBalance), ENT_QUOTES, 'UTF-8') ?></strong>
            </article>
        </div>

        <form action="<?= htmlspecialchars(app_url('admin-tenant-key-collection'), ENT_QUOTES, 'UTF-8') ?>" method="post" class="admin-form" id="key-collection-form">
            <input type="hidden" name="unit_table" value="<?= htmlspecialchars($unitTable, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="unit_id" value="<?= (int) $tenant['unitId'] ?>">

            <div class="admin-form-grid">
                <div class="admin-field">
                    <label for="key-amount">Rent installment paid at handover</label>
                    <input id="key-amount" name="amount_paid" type="number" min="0" placeholder="0" value="<?= htmlspecialchars($formAmount, ENT_QUOTES, 'UTF-8') ?>">
                    <span class="muted-text" style="display:block; margin-top:6px">Any amount, even if it is less than the full rent. The rest stays as balance.</span>
                </div>

                <div class="admin-field">
                    <label for="key-recipient">Keys received by</label>
                    <input id="key-recipient" name="recipient_name" type="text" value="<?= htmlspecialchars($formRecipient, ENT_QUOTES, 'UTF-8') ?>" placeholder="Tenant or representative name">
                </div>

                <div class="admin-field">
                    <label for="key-count">Number of keys</label>
                    <input id="key-count" name="keys_count" type="number" min="1" value="<?= (int) $formKeys ?>">
                </div>

                <div class="admin-field">
                    <label for="key-date">Date handed over</label>
                    <input id="key-date" name="collected_at" type="date" value="<?= htmlspecialchars($formCollectedAt, ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="admin-field">
                    <label for="key-channel">Channel</label>
                    <select id="key-channel" name="channel">
                        <option value="cash"<?= $formChannel === 'cash' ? ' selected' : '' ?>>Cash</option>
                        <option value="transfer"<?= $formChannel === 'transfer' ? ' selected' : '' ?>>Transfer</option>
                        <option value="card"<?= $formChannel === 'card' ? ' selected' : '' ?>>Card</option>
                    </select>
                </div>

                <div class="admin-field">
                    <label for="key-reference">Reference</label>
                    <input id="key-reference" name="reference" type="text" placeholder="Auto if left blank" value="<?= htmlspecialchars($formReference, ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>

            <div class="admin-field" style="max-width:100%">
                <label for="key-notes">Notes</label>
                <input id="key-notes" name="notes" type="text" placeholder="Condition of keys, handover remarks" value="<?= htmlspecialchars($formNotes, ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="admin-field" style="max-width:100%; margin-top:6px">
                <label style="display:flex; gap:8px; align-items:flex-start; font-weight:500">
                    <input type="checkbox" name="post_payment" value="1"<?= $formPostPayment ? ' checked' : '' ?> style="margin-top:4px">
                    <span>Also post the amount above as a rent payment on this tenant's ledger (leave unchecked if the payment was already recorded separately).</span>
                </label>
            </div>

            <p class="muted-text" id="key-balance-note" style="margin-top:0">
                <?= $initialBalance > 0
                    ? 'A balance of ' . htmlspecialchars(app_currency($initialBalance), ENT_QUOTES, 'UTF-8') . ' will remain on the rent for the term. The form can still be submitted.'
                    : 'This payment settles the rent for the term in full.' ?>
            </p>

            <div class="admin-form-actions">
                <button type="submit" class="solid-button">Confirm key handover</button>
                <a class="ghost-button" href="<?= htmlspecialchars(app_url('admin-tenants', array('view_tenant' => $tenant['unitId'], 'unit_table' => $unitTable)), ENT_QUOTES, 'UTF-8') ?>">Cancel</a>
            </div>
        </form>
    </section>
</section>

<script>
(function () {
    var amountInput = document.getElementById('key-amount');
    var balanceEl = document.getElementById('key-balance-indicator');
    var noteEl = document.getElementById('key-balance-note');
    var postCheck = document.querySelector('input[name="post_payment"]');
    var expected = <?= (int) $expectedRent ?>;
    var priorPaid = <?= (int) $priorPaid ?>;

    if (!amountInput || !balanceEl || !noteEl) {
        return;
    }

    var symbol = '<?= htmlspecialchars(app_currency_symbol(), ENT_QUOTES, 'UTF-8') ?>';

    var formatter = function (value) {
        return symbol + String(value).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    };

    var update = function () {
        var raw = (amountInput.value || '').replace(/[^\d]/g, '');
        var paid = raw === '' ? 0 : parseInt(raw, 10);
        var countsTowardRent = postCheck && postCheck.checked;
        var balance = Math.max(0, expected - priorPaid - (countsTowardRent ? paid : 0));

        balanceEl.textContent = formatter(balance);
        balanceEl.className = balance > 0 ? 'admin-amount-due' : 'admin-amount-ok';

        noteEl.textContent = balance > 0
            ? 'A balance of ' + formatter(balance) + ' will remain on the rent for the term. The form can still be submitted.'
            : 'This payment settles the rent for the term in full.';
    };

    amountInput.addEventListener('input', update);
    if (postCheck) {
        postCheck.addEventListener('change', update);
    }
    update();
})();
</script>
